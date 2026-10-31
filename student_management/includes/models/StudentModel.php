<?php
declare(strict_types=1);

final class StudentModel
{
    public const GENDERS  = ['Male', 'Female', 'Other'];
    public const STATUSES = ['Active', 'Inactive', 'Graduated'];

    public static function all(): array
    {
        $sql = 'SELECT s.id, s.reg_no, s.first_name, s.last_name, s.email, s.phone, s.gender,
                       s.department, s.batch_year, s.status,
                       (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = s.id) AS course_count
                FROM students s ORDER BY s.reg_no';
        return db()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM students WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $c = db()->prepare('SELECT course_id FROM enrollments WHERE student_id = :id');
        $c->execute([':id' => $id]);
        $row['course_ids'] = array_map('intval', $c->fetchAll(PDO::FETCH_COLUMN));
        return $row;
    }

    public static function regExists(string $regNo, int $excludeId = 0): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM students WHERE reg_no = :r AND id <> :id LIMIT 1');
        $stmt->execute([':r' => $regNo, ':id' => $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function emailExists(string $email, int $excludeId = 0): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM students WHERE email = :e AND id <> :id LIMIT 1');
        $stmt->execute([':e' => $email, ':id' => $excludeId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Normalise raw input into trusted field values. */
    public static function clean(array $in): array
    {
        $t = static fn(string $k): string => trim((string) ($in[$k] ?? ''));
        $dob = $t('dob');
        return [
            'reg_no'     => strtoupper($t('reg_no')),
            'first_name' => $t('first_name'),
            'last_name'  => $t('last_name'),
            'email'      => strtolower($t('email')),
            'phone'      => $t('phone') ?: null,
            'dob'        => $dob ?: null,
            'gender'     => $t('gender') ?: 'Male',
            'address'    => $t('address') ?: null,
            'department' => $t('department'),
            'batch_year' => (int) ($in['batch_year'] ?? 0),
            'status'     => $t('status') ?: 'Active',
            'course_ids' => array_values(array_unique(array_map('intval', (array) ($in['course_ids'] ?? [])))),
        ];
    }

    /** Server-side validation. Returns [field => message]. */
    public static function validate(array $d, int $id = 0): array
    {
        $err = [];
        if (!preg_match('/^[A-Z0-9][A-Z0-9\-\/]{2,29}$/', $d['reg_no'])) {
            $err['reg_no'] = 'Reg. No must be 3-30 characters (letters, numbers, - or /).';
        } elseif (self::regExists($d['reg_no'], $id)) {
            $err['reg_no'] = 'This registration number is already taken.';
        }
        foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $f => $label) {
            if (!preg_match("/^[\p{L}][\p{L} .'\-]{0,58}$/u", $d[$f])) {
                $err[$f] = "$label is required (letters only).";
            }
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 120) {
            $err['email'] = 'Enter a valid email address.';
        } elseif (self::emailExists($d['email'], $id)) {
            $err['email'] = 'This email is already registered.';
        }
        if ($d['phone'] !== null && !preg_match('/^[0-9+\-\s]{7,20}$/', $d['phone'])) {
            $err['phone'] = 'Enter a valid phone number.';
        }
        if ($d['dob'] !== null && (!valid_date($d['dob']) || $d['dob'] >= date('Y-m-d'))) {
            $err['dob'] = 'Date of birth must be a valid past date.';
        }
        if (!in_array($d['gender'], self::GENDERS, true)) {
            $err['gender'] = 'Invalid gender.';
        }
        if ($d['department'] === '' || strlen($d['department']) > 80) {
            $err['department'] = 'Department is required.';
        }
        if ($d['batch_year'] < 2000 || $d['batch_year'] > (int) date('Y') + 1) {
            $err['batch_year'] = 'Enter a valid batch year.';
        }
        if (!in_array($d['status'], self::STATUSES, true)) {
            $err['status'] = 'Invalid status.';
        }
        return $err;
    }

    /**
     * Insert or update a student, sync course enrolments and (on create)
     * create the student's login - all in one transaction.
     */
    public static function save(array $d, int $id = 0): int
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $fields = [
                ':reg_no' => $d['reg_no'], ':first_name' => $d['first_name'], ':last_name' => $d['last_name'],
                ':email' => $d['email'], ':phone' => $d['phone'], ':dob' => $d['dob'], ':gender' => $d['gender'],
                ':address' => $d['address'], ':department' => $d['department'],
                ':batch_year' => $d['batch_year'], ':status' => $d['status'],
            ];

            if ($id === 0) {
                $pdo->prepare('INSERT INTO students (reg_no, first_name, last_name, email, phone, dob, gender, address, department, batch_year, status)
                               VALUES (:reg_no, :first_name, :last_name, :email, :phone, :dob, :gender, :address, :department, :batch_year, :status)')
                    ->execute($fields);
                $id = (int) $pdo->lastInsertId();

                $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, student_id)
                               VALUES (:u, :p, :n, :e, 'student', :sid)")
                    ->execute([
                        ':u' => $d['reg_no'],
                        ':p' => password_hash(DEFAULT_STUDENT_PASSWORD, PASSWORD_DEFAULT),
                        ':n' => $d['first_name'] . ' ' . $d['last_name'],
                        ':e' => $d['email'],
                        ':sid' => $id,
                    ]);
            } else {
                $fields[':id'] = $id;
                $pdo->prepare('UPDATE students SET reg_no=:reg_no, first_name=:first_name, last_name=:last_name, email=:email,
                               phone=:phone, dob=:dob, gender=:gender, address=:address, department=:department,
                               batch_year=:batch_year, status=:status WHERE id=:id')
                    ->execute($fields);

                // keep the login in sync with the student record
                $pdo->prepare("UPDATE users SET username=:u, full_name=:n, email=:e, is_active=:a WHERE student_id=:sid")
                    ->execute([
                        ':u' => $d['reg_no'], ':n' => $d['first_name'] . ' ' . $d['last_name'],
                        ':e' => $d['email'], ':a' => $d['status'] === 'Inactive' ? 0 : 1, ':sid' => $id,
                    ]);
            }

            self::syncEnrollments($id, $d['course_ids']);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function syncEnrollments(int $studentId, array $courseIds): void
    {
        $pdo = db();
        // only accept real course ids
        $valid = array_map('intval', $pdo->query('SELECT id FROM courses')->fetchAll(PDO::FETCH_COLUMN));
        $courseIds = array_values(array_intersect($courseIds, $valid));

        if ($courseIds) {
            $in = implode(',', array_fill(0, count($courseIds), '?'));
            $del = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id NOT IN ($in)");
            $del->execute(array_merge([$studentId], $courseIds));
        } else {
            $pdo->prepare('DELETE FROM enrollments WHERE student_id = ?')->execute([$studentId]);
        }
        $ins = $pdo->prepare('INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)');
        foreach ($courseIds as $cid) {
            $ins->execute([$studentId, $cid]);
        }
    }

    public static function delete(int $id): bool
    {
        // users / enrollments / attendance / marks are removed by ON DELETE CASCADE
        $stmt = db()->prepare('DELETE FROM students WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function departments(): array
    {
        return db()->query('SELECT DISTINCT department FROM students ORDER BY department')->fetchAll(PDO::FETCH_COLUMN);
    }
}

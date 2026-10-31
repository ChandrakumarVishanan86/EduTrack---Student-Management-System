<?php
declare(strict_types=1);

final class MarkModel
{
    /** Enrolled students of a course with their current mark (null if none). */
    public static function roster(int $courseId): array
    {
        $stmt = db()->prepare(
            'SELECT s.id, s.reg_no, CONCAT(s.first_name, " ", s.last_name) AS name, m.marks
             FROM enrollments en
             JOIN students s ON s.id = en.student_id
             LEFT JOIN marks m ON m.student_id = s.id AND m.course_id = en.course_id
             WHERE en.course_id = :c ORDER BY s.reg_no'
        );
        $stmt->execute([':c' => $courseId]);
        return array_map(static function ($r) {
            $r['marks'] = $r['marks'] !== null ? (float) $r['marks'] : null;
            return $r;
        }, $stmt->fetchAll());
    }

    /**
     * Validate every entry (0-100) and save in one transaction.
     * Blank marks are skipped. Returns [saved count, errors[]].
     * @param array<int,array{student_id:int,marks:mixed}> $entries
     */
    public static function save(int $courseId, array $entries, int $userId): array
    {
        $pdo = db();
        $q = $pdo->prepare('SELECT student_id FROM enrollments WHERE course_id = :c');
        $q->execute([':c' => $courseId]);
        $allowed = array_flip(array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN)));

        $errors = [];
        $clean  = [];
        foreach ($entries as $row) {
            $sid = (int) ($row['student_id'] ?? 0);
            $raw = $row['marks'] ?? '';
            if ($raw === '' || $raw === null) {
                continue;                                        // not entered
            }
            if (!isset($allowed[$sid])) {
                $errors[$sid] = 'Student is not enrolled in this course.';
                continue;
            }
            if (!is_numeric($raw) || (float) $raw < 0 || (float) $raw > 100) {
                $errors[$sid] = 'Marks must be a number between 0 and 100.';
                continue;
            }
            $clean[$sid] = round((float) $raw, 2);
        }
        if ($errors) {
            return [0, $errors];
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO marks (student_id, course_id, marks, grade, grade_point, entered_by)
                 VALUES (:s, :c, :m, :g, :gp, :u)
                 ON DUPLICATE KEY UPDATE marks = VALUES(marks), grade = VALUES(grade),
                                         grade_point = VALUES(grade_point), entered_by = VALUES(entered_by)'
            );
            foreach ($clean as $sid => $m) {
                $stmt->execute([
                    ':s' => $sid, ':c' => $courseId, ':m' => $m,
                    ':g' => grade_from_marks($m), ':gp' => grade_point($m), ':u' => $userId,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return [count($clean), []];
    }

    /** Result sheet of a student: rows + average + GPA (= average / 25). */
    public static function transcript(int $studentId): array
    {
        $stmt = db()->prepare(
            'SELECT c.code, c.name, c.credits, m.marks, m.grade, m.grade_point
             FROM marks m JOIN courses c ON c.id = m.course_id
             WHERE m.student_id = :s ORDER BY c.code'
        );
        $stmt->execute([':s' => $studentId]);
        $rows = $stmt->fetchAll();

        $avg = $rows ? array_sum(array_column($rows, 'marks')) / count($rows) : 0.0;
        return [
            'rows' => $rows,
            'average' => round($avg, 2),
            'gpa' => round($avg / 25, 2),
            'grade' => $rows ? grade_from_marks($avg) : '-',
        ];
    }
}

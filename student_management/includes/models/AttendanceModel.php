<?php
declare(strict_types=1);

final class AttendanceModel
{
    /** Enrolled students of a course with their status for a given date (null = not yet marked). */
    public static function roster(int $courseId, string $date): array
    {
        $stmt = db()->prepare(
            'SELECT s.id, s.reg_no, CONCAT(s.first_name, " ", s.last_name) AS name, a.status
             FROM enrollments en
             JOIN students s ON s.id = en.student_id AND s.status = "Active"
             LEFT JOIN attendance a ON a.student_id = s.id AND a.course_id = en.course_id AND a.att_date = :d
             WHERE en.course_id = :c
             ORDER BY s.reg_no'
        );
        $stmt->execute([':d' => $date, ':c' => $courseId]);
        return $stmt->fetchAll();
    }

    /**
     * Bulk save inside a PDO transaction (all rows or none).
     * @param array<int,array{student_id:int,status:string}> $records
     */
    public static function save(int $courseId, string $date, array $records, int $userId): int
    {
        $pdo = db();
        $enrolled = $pdo->prepare('SELECT student_id FROM enrollments WHERE course_id = :c');
        $enrolled->execute([':c' => $courseId]);
        $allowed = array_flip(array_map('intval', $enrolled->fetchAll(PDO::FETCH_COLUMN)));

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO attendance (student_id, course_id, att_date, status, marked_by)
                 VALUES (:s, :c, :d, :st, :u)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)'
            );
            $count = 0;
            foreach ($records as $r) {
                $sid = (int) ($r['student_id'] ?? 0);
                $st  = $r['status'] ?? '';
                if (!isset($allowed[$sid]) || !in_array($st, ['present', 'absent'], true)) {
                    throw new InvalidArgumentException('Invalid attendance record for student #' . $sid);
                }
                $stmt->execute([':s' => $sid, ':c' => $courseId, ':d' => $date, ':st' => $st, ':u' => $userId]);
                $count++;
            }
            $pdo->commit();
            return $count;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Overall attendance % for one student. */
    public static function percentage(int $studentId): float
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) total, SUM(status='present') present FROM attendance WHERE student_id = :s"
        );
        $stmt->execute([':s' => $studentId]);
        $r = $stmt->fetch();
        return $r['total'] > 0 ? round(100 * $r['present'] / $r['total'], 1) : 0.0;
    }

    /** Per-course attendance for one student. */
    public static function byCourse(int $studentId): array
    {
        $stmt = db()->prepare(
            "SELECT c.code, c.name, COUNT(a.id) total, COALESCE(SUM(a.status='present'),0) present
             FROM enrollments en
             JOIN courses c ON c.id = en.course_id
             LEFT JOIN attendance a ON a.course_id = c.id AND a.student_id = en.student_id
             WHERE en.student_id = :s GROUP BY c.id ORDER BY c.code"
        );
        $stmt->execute([':s' => $studentId]);
        return array_map(static function ($r) {
            $r['percent'] = $r['total'] > 0 ? round(100 * $r['present'] / $r['total'], 1) : 0.0;
            return $r;
        }, $stmt->fetchAll());
    }
}

<?php
declare(strict_types=1);

final class DashboardModel
{
    private static function scalar(string $sql, array $p = []): int|float
    {
        $s = db()->prepare($sql);
        $s->execute($p);
        return (float) ($s->fetchColumn() ?: 0);
    }

    public static function admin(): array
    {
        $pdo = db();
        $att = $pdo->query("SELECT COUNT(*) t, SUM(status='present') p FROM attendance")->fetch();
        return [
            'students'   => (int) self::scalar("SELECT COUNT(*) FROM students WHERE status = 'Active'"),
            'lecturers'  => (int) self::scalar("SELECT COUNT(*) FROM users WHERE role = 'lecturer' AND is_active = 1"),
            'courses'    => (int) self::scalar('SELECT COUNT(*) FROM courses'),
            'att_percent' => $att['t'] > 0 ? round(100 * $att['p'] / $att['t'], 1) : 0,
            'avg_marks'  => round(self::scalar('SELECT AVG(marks) FROM marks'), 1),
            'by_dept'    => $pdo->query('SELECT department label, COUNT(*) value FROM students GROUP BY department ORDER BY value DESC')->fetchAll(),
            'course_avg' => $pdo->query('SELECT c.code label, ROUND(AVG(m.marks),1) value FROM courses c JOIN marks m ON m.course_id = c.id GROUP BY c.id ORDER BY c.code')->fetchAll(),
            'recent'     => $pdo->query('SELECT id, reg_no, first_name, last_name, department, created_at FROM students ORDER BY id DESC LIMIT 5')->fetchAll(),
            'grades'     => $pdo->query('SELECT grade label, COUNT(*) value FROM marks GROUP BY grade ORDER BY FIELD(grade,"A+","A","B+","B","C+","C","D","F")')->fetchAll(),
        ];
    }

    public static function lecturer(int $userId): array
    {
        $pdo = db();
        $courses = $pdo->prepare(
            "SELECT c.id, c.code, c.name,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) students,
                    (SELECT ROUND(100*SUM(a.status='present')/NULLIF(COUNT(*),0),1) FROM attendance a WHERE a.course_id = c.id) att,
                    (SELECT ROUND(AVG(m.marks),1) FROM marks m WHERE m.course_id = c.id) avg_marks
             FROM courses c WHERE c.lecturer_id = :u ORDER BY c.code"
        );
        $courses->execute([':u' => $userId]);
        $courses = $courses->fetchAll();

        $dist = $pdo->prepare(
            'SELECT m.grade label, COUNT(*) value FROM marks m JOIN courses c ON c.id = m.course_id
             WHERE c.lecturer_id = :u GROUP BY m.grade ORDER BY FIELD(m.grade,"A+","A","B+","B","C+","C","D","F")'
        );
        $dist->execute([':u' => $userId]);

        $today = $pdo->prepare(
            "SELECT COUNT(DISTINCT a.course_id) FROM attendance a JOIN courses c ON c.id = a.course_id
             WHERE c.lecturer_id = :u AND a.att_date = CURDATE()"
        );
        $today->execute([':u' => $userId]);

        return [
            'courses'        => $courses,
            'total_students' => (int) array_sum(array_column($courses, 'students')),
            'grades'         => $dist->fetchAll(),
            'marked_today'   => (int) $today->fetchColumn(),
        ];
    }

    public static function student(int $studentId): array
    {
        $stmt = db()->prepare('SELECT * FROM students WHERE id = :id');
        $stmt->execute([':id' => $studentId]);
        $transcript = MarkModel::transcript($studentId);
        return [
            'profile'    => $stmt->fetch() ?: [],
            'att_percent' => AttendanceModel::percentage($studentId),
            'att_courses' => AttendanceModel::byCourse($studentId),
            'transcript' => $transcript,
        ];
    }
}

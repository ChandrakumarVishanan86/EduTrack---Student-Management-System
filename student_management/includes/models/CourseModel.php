<?php
declare(strict_types=1);

final class CourseModel
{
    /** All courses (admin) or only the lecturer's own courses. */
    public static function forUser(array $user): array
    {
        $sql = 'SELECT c.*, u.full_name AS lecturer_name,
                       (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
                FROM courses c LEFT JOIN users u ON u.id = c.lecturer_id';
        $params = [];
        if ($user['role'] === 'lecturer') {
            $sql .= ' WHERE c.lecturer_id = :lid';
            $params[':lid'] = $user['id'];
        }
        $sql .= ' ORDER BY c.code';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return db()->query('SELECT id, code, name FROM courses ORDER BY code')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM courses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Can this user take attendance / enter marks for this course? */
    public static function canManage(array $user, int $courseId): bool
    {
        if ($user['role'] === 'admin') {
            return self::find($courseId) !== null;
        }
        if ($user['role'] !== 'lecturer') {
            return false;
        }
        $stmt = db()->prepare('SELECT 1 FROM courses WHERE id = :c AND lecturer_id = :l');
        $stmt->execute([':c' => $courseId, ':l' => $user['id']]);
        return (bool) $stmt->fetchColumn();
    }
}

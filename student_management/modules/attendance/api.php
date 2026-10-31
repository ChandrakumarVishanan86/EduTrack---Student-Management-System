<?php
/**
 * Attendance JSON API
 *   GET  ?action=roster&course_id=1&date=2026-10-30
 *   POST (JSON) {action:"save", course_id, date, records:[{student_id,status}]}
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$user = Auth::guard(['admin', 'lecturer'], json: true);

/** Validate course + date shared by both actions. */
function check_context(array $user, int $courseId, string $date): void
{
    if (!valid_date($date)) {
        json_response(['success' => false, 'message' => 'Choose a valid date.'], 422);
    }
    if ($date > date('Y-m-d')) {
        json_response(['success' => false, 'message' => 'Attendance cannot be marked for a future date.'], 422);
    }
    if (!CourseModel::canManage($user, $courseId)) {
        json_response(['success' => false, 'message' => 'You are not assigned to this course.'], 403);
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (($_GET['action'] ?? '') !== 'roster') {
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
        }
        $courseId = (int) ($_GET['course_id'] ?? 0);
        $date     = (string) ($_GET['date'] ?? '');
        check_context($user, $courseId, $date);

        $rows = AttendanceModel::roster($courseId, $date);
        json_response([
            'success'  => true,
            'students' => $rows,
            'marked'   => count(array_filter($rows, static fn($r) => $r['status'] !== null)),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_input();
        require_csrf($in);
        if (($in['action'] ?? '') !== 'save') {
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
        }
        $courseId = (int) ($in['course_id'] ?? 0);
        $date     = (string) ($in['date'] ?? '');
        $records  = $in['records'] ?? [];
        check_context($user, $courseId, $date);

        if (!is_array($records) || !$records) {
            json_response(['success' => false, 'message' => 'No students to save.'], 422);
        }
        try {
            $n = AttendanceModel::save($courseId, $date, $records, $user['id']);
        } catch (InvalidArgumentException $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 422);
        }
        json_response(['success' => true, 'saved' => $n, 'message' => "Attendance saved for $n students."]);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);

} catch (Throwable $e) {
    error_log('[attendance api] ' . $e->getMessage());
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : 'Server error. Nothing was saved.'], 500);
}

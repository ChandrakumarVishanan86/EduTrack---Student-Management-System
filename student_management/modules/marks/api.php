<?php
/**
 * Marks JSON API
 *   GET  ?action=roster&course_id=1
 *   POST (JSON) {action:"save", course_id, entries:[{student_id, marks}]}
 * Marks are validated here again (0-100) - never trust the browser.
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$user = Auth::guard(['admin', 'lecturer'], json: true);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (($_GET['action'] ?? '') !== 'roster') {
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
        }
        $courseId = (int) ($_GET['course_id'] ?? 0);
        if (!CourseModel::canManage($user, $courseId)) {
            json_response(['success' => false, 'message' => 'You are not assigned to this course.'], 403);
        }
        json_response(['success' => true, 'students' => MarkModel::roster($courseId)]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_input();
        require_csrf($in);
        if (($in['action'] ?? '') !== 'save') {
            json_response(['success' => false, 'message' => 'Unknown action.'], 400);
        }
        $courseId = (int) ($in['course_id'] ?? 0);
        if (!CourseModel::canManage($user, $courseId)) {
            json_response(['success' => false, 'message' => 'You are not assigned to this course.'], 403);
        }
        $entries = $in['entries'] ?? [];
        if (!is_array($entries)) {
            json_response(['success' => false, 'message' => 'Invalid data.'], 422);
        }

        [$saved, $errors] = MarkModel::save($courseId, $entries, $user['id']);
        if ($errors) {
            json_response([
                'success' => false,
                'message' => 'Some marks are invalid (must be 0-100). Nothing was saved.',
                'errors'  => $errors,                 // [student_id => message]
            ], 422);
        }
        json_response(['success' => true, 'saved' => $saved, 'message' => "Marks saved for $saved students."]);
    }

    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);

} catch (Throwable $e) {
    error_log('[marks api] ' . $e->getMessage());
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : 'Server error. Nothing was saved.'], 500);
}

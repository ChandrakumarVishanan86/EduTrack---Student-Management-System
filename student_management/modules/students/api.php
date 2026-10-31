<?php
/**
 * Students JSON API
 *   GET  ?action=list
 *   GET  ?action=get&id=1
 *   GET  ?action=check_reg&reg_no=S2024001&exclude_id=0
 *   POST action=save   (form fields, id optional)   [admin]
 *   POST action=delete (id)                         [admin]
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$user   = Auth::guard(['admin', 'lecturer'], json: true);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

try {
    // ------------------------------------------------------------ reads
    if ($method === 'GET') {
        switch ($action) {
            case 'list':
                json_response(['success' => true, 'data' => StudentModel::all()]);

            case 'get':
                $s = StudentModel::find((int) ($_GET['id'] ?? 0));
                $s ? json_response(['success' => true, 'data' => $s])
                   : json_response(['success' => false, 'message' => 'Student not found.'], 404);

            case 'check_reg':
                $reg = strtoupper(trim((string) ($_GET['reg_no'] ?? '')));
                json_response([
                    'success' => true,
                    'exists'  => $reg !== '' && StudentModel::regExists($reg, (int) ($_GET['exclude_id'] ?? 0)),
                ]);
        }
        json_response(['success' => false, 'message' => 'Unknown action.'], 400);
    }

    // ----------------------------------------------------------- writes
    if ($method !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
    Auth::guard(['admin'], json: true);      // only admins may modify students
    require_csrf();

    switch ($action) {
        case 'save':
            $id   = (int) ($_POST['id'] ?? 0);
            if ($id > 0 && !StudentModel::find($id)) {
                json_response(['success' => false, 'message' => 'Student not found.'], 404);
            }
            $data   = StudentModel::clean($_POST);
            $errors = StudentModel::validate($data, $id);
            if ($errors) {
                json_response(['success' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $errors], 422);
            }
            $newId = StudentModel::save($data, $id);
            json_response([
                'success' => true,
                'id'      => $newId,
                'message' => $id ? 'Student updated successfully.'
                                 : 'Student added. Login: ' . $data['reg_no'] . ' / ' . DEFAULT_STUDENT_PASSWORD,
            ]);

        case 'delete':
            $ok = StudentModel::delete((int) ($_POST['id'] ?? 0));
            json_response($ok
                ? ['success' => true, 'message' => 'Student deleted.']
                : ['success' => false, 'message' => 'Student not found.'], $ok ? 200 : 404);
    }
    json_response(['success' => false, 'message' => 'Unknown action.'], 400);

} catch (Throwable $e) {
    error_log('[students api] ' . $e->getMessage());
    json_response(['success' => false, 'message' => APP_DEBUG ? $e->getMessage() : 'Server error. Please try again.'], 500);
}

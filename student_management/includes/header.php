<?php
/**
 * Shared page header + sidebar.
 *
 * Before including, a page may set:
 *   $pageTitle  string
 *   $activeNav  'dashboard' | 'students' | 'attendance' | 'marks' | 'report'
 *   $libs       array of 'datatables' | 'flatpickr' | 'chart'
 */
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user      = Auth::guard();
$pageTitle = $pageTitle ?? 'Dashboard';
$activeNav = $activeNav ?? '';
$libs      = $libs ?? [];

$nav = [
    ['key' => 'dashboard',  'label' => 'Dashboard',  'icon' => 'bi-grid-1x2-fill',      'href' => Auth::homeFor($user['role']), 'roles' => ['admin', 'lecturer', 'student']],
    ['key' => 'students',   'label' => 'Students',   'icon' => 'bi-people-fill',        'href' => 'students',   'roles' => ['admin', 'lecturer']],
    ['key' => 'attendance', 'label' => 'Attendance', 'icon' => 'bi-calendar2-check-fill', 'href' => 'attendance', 'roles' => ['admin', 'lecturer']],
    ['key' => 'marks',      'label' => 'Marks',      'icon' => 'bi-journal-check',      'href' => 'marks',      'roles' => ['admin', 'lecturer']],
];
if ($user['role'] === 'student' && $user['student_id']) {
    $nav[] = ['key' => 'report', 'label' => 'My Result PDF', 'icon' => 'bi-file-earmark-pdf-fill', 'href' => 'report/' . $user['student_id'], 'roles' => ['student'], 'target' => '_blank'];
}
$initials = strtoupper(mb_substr($user['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<?php if (in_array('datatables', $libs, true)): ?>
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<?php endif; ?>
<?php if (in_array('flatpickr', $libs, true)): ?>
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
<?php endif; ?>
    <link href="<?= url('assets/css/style.css') ?>?v=1" rel="stylesheet">
</head>
<body class="app-body">

<div class="app-shell">
    <!-- ============ Sidebar ============ -->
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
            <span class="brand-text"><?= e(APP_NAME) ?></span>
            <button type="button" class="btn-close btn-close-white d-lg-none ms-auto" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-caption">Menu</span>
            <?php foreach ($nav as $item): if (!in_array($user['role'], $item['roles'], true)) continue; ?>
                <a href="<?= url($item['href']) ?>" <?= isset($item['target']) ? 'target="' . $item['target'] . '"' : '' ?>
                   class="side-link <?= $activeNav === $item['key'] ? 'active' : '' ?>">
                    <i class="bi <?= $item['icon'] ?>"></i><span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <span class="avatar"><?= e($initials) ?></span>
                <div class="lh-sm">
                    <div class="fw-semibold text-truncate" style="max-width:130px"><?= e($user['full_name']) ?></div>
                    <small class="text-capitalize opacity-75"><?= e($user['role']) ?></small>
                </div>
            </div>
            <a href="<?= url('logout') ?>" class="btn btn-sm btn-outline-light w-100 mt-3" data-confirm="Sign out of your account?">
                <i class="bi bi-box-arrow-right me-1"></i> Sign out
            </a>
        </div>
    </aside>

    <!-- ============ Main ============ -->
    <div class="main-area">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menu">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div>
                <h1 class="page-title mb-0"><?= e($pageTitle) ?></h1>
                <small class="text-muted d-none d-sm-block"><?= date('l, d F Y') ?></small>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="badge role-badge role-<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span>
            </div>
        </header>

        <main class="content fade-in">
<?php foreach (pull_flash() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($f['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
<?php endforeach; ?>

<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['lecturer']);

$d = DashboardModel::lecturer($user['id']);

$pageTitle   = 'Lecturer Dashboard';
$activeNav   = 'dashboard';
$libs        = ['chart'];
$pageScripts = ['assets/js/dashboard.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="welcome-banner mb-4">
    <div>
        <h4 class="fw-bold mb-1">Hello, <?= e($user['full_name']) ?> 👋</h4>
        <p class="mb-0 opacity-75">You teach <?= count($d['courses']) ?> course(s). Today <?= $d['marked_today'] ?> of them have attendance recorded.</p>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <a href="<?= url('attendance') ?>" class="btn btn-light fw-semibold"><i class="bi bi-calendar2-check me-1"></i> Mark attendance</a>
        <a href="<?= url('marks') ?>" class="btn btn-outline-light fw-semibold"><i class="bi bi-journal-check me-1"></i> Enter marks</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card stat-indigo hover-lift">
            <div class="stat-icon"><i class="bi bi-book-half"></i></div>
            <div><div class="stat-num" data-count="<?= count($d['courses']) ?>">0</div><div class="stat-label">My courses</div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card stat-cyan hover-lift">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div><div class="stat-num" data-count="<?= $d['total_students'] ?>">0</div><div class="stat-label">Enrolments</div></div>
        </div>
    </div>
    <div class="col-sm-12 col-xl-4">
        <div class="stat-card stat-amber hover-lift">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div><div class="stat-num" data-count="<?= $d['marked_today'] ?>">0</div><div class="stat-label">Classes marked today</div></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card-panel">
            <h6 class="fw-bold mb-3">My courses</h6>
            <div class="row g-3">
                <?php foreach ($d['courses'] as $c): $att = $c['att'] !== null ? (float) $c['att'] : 0; ?>
                    <div class="col-md-6">
                        <div class="course-card hover-lift">
                            <div class="circle-progress circle-sm" data-value="<?= $att ?>">
                                <div class="cp-inner"><span class="cp-value">0%</span></div>
                            </div>
                            <div class="flex-fill">
                                <div class="fw-bold"><?= e($c['code']) ?></div>
                                <div class="small text-muted text-truncate"><?= e($c['name']) ?></div>
                                <div class="small mt-1">
                                    <span class="me-2"><i class="bi bi-people"></i> <?= (int) $c['students'] ?></span>
                                    <span><i class="bi bi-bar-chart"></i> avg <?= $c['avg_marks'] !== null ? e((string) $c['avg_marks']) : '–' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$d['courses']): ?><div class="col-12 text-muted">No courses assigned yet.</div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-panel h-100">
            <h6 class="fw-bold mb-3">Grade distribution</h6>
            <div class="chart-box"><canvas id="chartGrades"></canvas></div>
        </div>
    </div>
</div>

<script>window.DASH = { grades: <?= json_encode($d['grades']) ?> };</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

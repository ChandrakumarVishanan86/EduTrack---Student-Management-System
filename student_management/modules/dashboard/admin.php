<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
Auth::guard(['admin']);

$d = DashboardModel::admin();

$pageTitle   = 'Admin Dashboard';
$activeNav   = 'dashboard';
$libs        = ['chart'];
$pageScripts = ['assets/js/dashboard.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-indigo hover-lift">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div><div class="stat-num" data-count="<?= $d['students'] ?>">0</div><div class="stat-label">Active students</div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-cyan hover-lift">
            <div class="stat-icon"><i class="bi bi-person-workspace"></i></div>
            <div><div class="stat-num" data-count="<?= $d['lecturers'] ?>">0</div><div class="stat-label">Lecturers</div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-amber hover-lift">
            <div class="stat-icon"><i class="bi bi-book-half"></i></div>
            <div><div class="stat-num" data-count="<?= $d['courses'] ?>">0</div><div class="stat-label">Courses</div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card stat-pink hover-lift">
            <div class="stat-icon"><i class="bi bi-award-fill"></i></div>
            <div><div class="stat-num" data-count="<?= $d['avg_marks'] ?>" data-decimals="1">0</div><div class="stat-label">Average marks</div></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-3">
        <div class="card-panel h-100 text-center d-flex flex-column align-items-center justify-content-center">
            <h6 class="fw-bold mb-3 align-self-start">Overall attendance</h6>
            <div class="circle-progress" data-value="<?= $d['att_percent'] ?>">
                <div class="cp-inner"><span class="cp-value">0%</span><small>present</small></div>
            </div>
            <small class="text-muted mt-3">Across all courses &amp; dates</small>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-panel h-100">
            <h6 class="fw-bold mb-3">Average marks by course</h6>
            <div class="chart-box"><canvas id="chartCourseAvg"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-panel h-100">
            <h6 class="fw-bold mb-3">Students by department</h6>
            <div class="chart-box"><canvas id="chartDept"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-panel h-100">
            <h6 class="fw-bold mb-3">Grade distribution</h6>
            <div class="chart-box"><canvas id="chartGrades"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-panel h-100">
            <div class="d-flex align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recently added</h6>
                <a href="<?= url('students') ?>" class="ms-auto small fw-semibold text-decoration-none">View all →</a>
            </div>
            <ul class="list-unstyled recent-list mb-0">
                <?php foreach ($d['recent'] as $s): ?>
                    <li>
                        <span class="avatar avatar-sm"><?= e(strtoupper(mb_substr($s['first_name'], 0, 1) . mb_substr($s['last_name'], 0, 1))) ?></span>
                        <div class="flex-fill lh-sm">
                            <div class="fw-semibold"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></div>
                            <small class="text-muted"><?= e($s['department']) ?></small>
                        </div>
                        <span class="reg-pill"><?= e($s['reg_no']) ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$d['recent']): ?><li class="text-muted">No students yet.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
window.DASH = {
    courseAvg: <?= json_encode($d['course_avg']) ?>,
    dept:      <?= json_encode($d['by_dept']) ?>,
    grades:    <?= json_encode($d['grades']) ?>
};
</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

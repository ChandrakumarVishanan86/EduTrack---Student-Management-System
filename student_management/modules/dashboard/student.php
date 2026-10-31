<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user = Auth::guard(['student']);

if (!$user['student_id']) {
    flash('danger', 'Your login is not linked to a student record.');
    redirect('logout');
}
$d = DashboardModel::student($user['student_id']);
$p = $d['profile'];
$t = $d['transcript'];

$chart = array_map(static fn($r) => ['label' => $r['code'], 'value' => (float) $r['marks']], $t['rows']);

$pageTitle   = 'My Dashboard';
$activeNav   = 'dashboard';
$libs        = ['chart'];
$pageScripts = ['assets/js/dashboard.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="welcome-banner mb-4">
    <span class="avatar avatar-lg"><?= e(strtoupper(mb_substr($p['first_name'] ?? 'S', 0, 1) . mb_substr($p['last_name'] ?? '', 0, 1))) ?></span>
    <div>
        <h4 class="fw-bold mb-1"><?= e(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?></h4>
        <p class="mb-0 opacity-75"><?= e($p['reg_no'] ?? '') ?> · <?= e($p['department'] ?? '') ?> · Batch <?= e((string) ($p['batch_year'] ?? '')) ?></p>
    </div>
    <a href="<?= url('report/' . $user['student_id']) ?>" target="_blank" class="btn btn-light fw-semibold ms-auto">
        <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i> Download result PDF
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card-panel h-100 text-center d-flex flex-column align-items-center justify-content-center">
            <h6 class="fw-bold mb-3 align-self-start">My attendance</h6>
            <div class="circle-progress" data-value="<?= $d['att_percent'] ?>">
                <div class="cp-inner"><span class="cp-value">0%</span><small>present</small></div>
            </div>
            <small class="mt-3 <?= $d['att_percent'] < 75 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                <?= $d['att_percent'] < 75 ? '<i class="bi bi-exclamation-triangle-fill"></i> Below the 75% requirement' : 'Great - keep it up!' ?>
            </small>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-4 h-100">
            <div class="col-sm-4">
                <div class="stat-card stat-indigo hover-lift flex-column text-center h-100">
                    <div class="stat-num" data-count="<?= $t['gpa'] ?>" data-decimals="2">0</div>
                    <div class="stat-label">GPA (of 4.00)</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card stat-pink hover-lift flex-column text-center h-100">
                    <div class="stat-num" data-count="<?= $t['average'] ?>" data-decimals="1">0</div>
                    <div class="stat-label">Average marks</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card stat-amber hover-lift flex-column text-center h-100">
                    <div class="stat-num"><?= e($t['grade']) ?></div>
                    <div class="stat-label">Overall grade</div>
                </div>
            </div>
            <div class="col-12">
                <div class="card-panel">
                    <h6 class="fw-bold mb-3">Results by course</h6>
                    <div class="chart-box chart-sm"><canvas id="chartResults"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card-panel h-100">
            <h6 class="fw-bold mb-3">Result sheet</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Course</th><th class="text-center">Credits</th><th class="text-center">Marks</th><th class="text-center">Grade</th><th class="text-center">GP</th></tr></thead>
                    <tbody>
                    <?php foreach ($t['rows'] as $r): ?>
                        <tr>
                            <td><strong><?= e($r['code']) ?></strong> <span class="text-muted small"><?= e($r['name']) ?></span></td>
                            <td class="text-center"><?= (int) $r['credits'] ?></td>
                            <td class="text-center fw-semibold"><?= number_format((float) $r['marks'], 1) ?></td>
                            <td class="text-center"><span class="grade-chip g-<?= e(str_replace('+', 'plus', $r['grade'])) ?>"><?= e($r['grade']) ?></span></td>
                            <td class="text-center"><?= number_format((float) $r['grade_point'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$t['rows']): ?><tr><td colspan="5" class="text-center text-muted py-4">No results published yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-panel h-100">
            <h6 class="fw-bold mb-3">Attendance by course</h6>
            <?php foreach ($d['att_courses'] as $c): $cls = $c['percent'] >= 75 ? 'ok' : ($c['percent'] >= 60 ? 'warn' : 'bad'); ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><strong><?= e($c['code']) ?></strong> <span class="text-muted"><?= (int) $c['present'] ?>/<?= (int) $c['total'] ?> classes</span></span>
                        <strong><?= $c['percent'] ?>%</strong>
                    </div>
                    <div class="bar-track"><div class="bar-fill bar-<?= $cls ?>" style="--w:<?= $c['percent'] ?>%"></div></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$d['att_courses']): ?><p class="text-muted mb-0">You are not enrolled in any course.</p><?php endif; ?>
        </div>
    </div>
</div>

<script>window.DASH = { results: <?= json_encode($chart) ?> };</script>
<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

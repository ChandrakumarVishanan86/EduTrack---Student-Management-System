<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user    = Auth::guard(['admin', 'lecturer']);
$courses = CourseModel::forUser($user);

$pageTitle   = 'Mark Attendance';
$activeNav   = 'attendance';
$libs        = ['flatpickr'];
$pageScripts = ['assets/js/attendance.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-semibold" for="courseSelect"><i class="bi bi-book me-1"></i> Course</label>
            <select class="form-select form-select-lg" id="courseSelect">
                <option value="">— Select a course —</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= e($c['code'] . ' · ' . $c['name']) ?> (<?= (int) $c['student_count'] ?> students)</option>
                <?php endforeach; ?>
            </select>
            <?php if (!$courses): ?><div class="form-text text-danger">No courses are assigned to you yet.</div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold" for="attDate"><i class="bi bi-calendar-event me-1"></i> Date</label>
            <input type="text" class="form-control form-control-lg" id="attDate" placeholder="Pick a date" readonly>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-gradient btn-lg" id="btnLoad"><i class="bi bi-arrow-repeat me-1"></i> Load students</button>
        </div>
    </div>
</div>

<!-- Empty state -->
<div class="empty-state" id="emptyState">
    <i class="bi bi-calendar2-check"></i>
    <h5>Pick a course and date</h5>
    <p class="text-muted mb-0">The student list will appear here so you can mark attendance.</p>
</div>

<!-- Roster -->
<div class="card-panel d-none" id="rosterPanel">
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <div>
            <h5 class="mb-0 fw-bold" id="rosterTitle">Class list</h5>
            <small class="text-muted" id="rosterSub"></small>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <input type="search" class="form-control" id="rosterSearch" placeholder="Filter students…" style="width:200px">
            <button class="btn btn-soft-success" id="allPresent"><i class="bi bi-check2-all"></i> All present</button>
            <button class="btn btn-soft-danger" id="allAbsent"><i class="bi bi-x-lg"></i> All absent</button>
        </div>
    </div>

    <div class="att-summary mb-3">
        <div class="sum-box sum-total"><span id="sumTotal">0</span><small>Total</small></div>
        <div class="sum-box sum-present"><span id="sumPresent">0</span><small>Present</small></div>
        <div class="sum-box sum-absent"><span id="sumAbsent">0</span><small>Absent</small></div>
        <div class="sum-bar flex-fill"><div class="sum-bar-fill" id="sumBar"></div><small id="sumPct">0%</small></div>
    </div>

    <div id="rosterList" class="roster-list"></div>

    <div class="d-flex justify-content-end mt-4">
        <button class="btn btn-gradient btn-lg px-5" id="btnSave"><i class="bi bi-cloud-check me-2"></i> Save attendance</button>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
$user    = Auth::guard(['admin', 'lecturer']);
$courses = CourseModel::forUser($user);

$pageTitle   = 'Marks Entry';
$activeNav   = 'marks';
$libs        = [];
$pageScripts = ['assets/js/marks.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold" for="courseSelect"><i class="bi bi-book me-1"></i> Course</label>
            <select class="form-select form-select-lg" id="courseSelect">
                <option value="">— Select a course —</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= e($c['code'] . ' · ' . $c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-grid">
            <button class="btn btn-gradient btn-lg" id="btnLoad"><i class="bi bi-arrow-repeat me-1"></i> Load students</button>
        </div>
    </div>
</div>

<div class="empty-state" id="emptyState">
    <i class="bi bi-journal-check"></i>
    <h5>Select a course to enter marks</h5>
    <p class="text-muted mb-0">Marks must be between 0 and 100. GPA is calculated automatically (average ÷ 25).</p>
</div>

<div class="row g-4 d-none" id="marksPanel">
    <div class="col-xl-9">
        <div class="card-panel">
            <div class="d-flex align-items-center mb-3">
                <h5 class="mb-0 fw-bold" id="marksTitle">Marks</h5>
                <small class="text-muted ms-auto">Leave blank to skip a student</small>
            </div>
            <div class="table-responsive">
                <table class="table align-middle marks-table mb-0">
                    <thead><tr><th>Reg. No</th><th>Student</th><th style="width:170px">Marks (0-100)</th><th class="text-center">Grade</th><th class="text-center">Grade pt</th></tr></thead>
                    <tbody id="marksBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <div class="card-panel sticky-panel">
            <h6 class="fw-bold text-uppercase small text-muted mb-3">Live summary</h6>
            <div class="gpa-display mb-3">
                <small>Class GPA</small>
                <div class="gpa-value" id="gpaValue">0.00</div>
                <small class="text-muted">GPA = average ÷ 25</small>
            </div>
            <ul class="list-unstyled summary-list mb-4">
                <li><span>Entered</span><strong id="sEntered">0 / 0</strong></li>
                <li><span>Average</span><strong id="sAvg">0.00</strong></li>
                <li><span>Highest</span><strong id="sMax">–</strong></li>
                <li><span>Lowest</span><strong id="sMin">–</strong></li>
                <li><span>Pass rate (≥50)</span><strong id="sPass">–</strong></li>
            </ul>
            <div class="alert alert-danger py-2 small d-none" id="errBox"></div>
            <button class="btn btn-gradient w-100 btn-lg" id="btnSave"><i class="bi bi-cloud-check me-2"></i> Save marks</button>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

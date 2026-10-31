<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
Auth::guard(['admin', 'lecturer']);

$isAdmin     = Auth::hasRole('admin');
$courses     = CourseModel::all();
$departments = StudentModel::departments();

$pageTitle   = 'Students';
$activeNav   = 'students';
$libs        = ['datatables', 'flatpickr'];
$pageScripts = ['assets/js/students.js'];
require_once dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="card-panel">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div>
            <h5 class="mb-0 fw-bold">Student Directory</h5>
            <small class="text-muted">Search, sort and manage every student record.</small>
        </div>
        <?php if ($isAdmin): ?>
        <button class="btn btn-gradient ms-auto" id="btnAdd"><i class="bi bi-plus-lg me-1"></i> Add Student</button>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table id="studentsTable" class="table table-hover align-middle w-100" data-admin="<?= $isAdmin ? '1' : '0' ?>">
            <thead>
                <tr>
                    <th>Reg. No</th><th>Student</th><th>Department</th><th>Batch</th>
                    <th class="text-center">Courses</th><th>Status</th><th class="text-end no-sort">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- ============ Add / Edit modal ============ -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" id="studentForm" novalidate autocomplete="off">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="f_id" value="0">
                <input type="hidden" name="action" value="save">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="f_reg_no">Registration No <span class="text-danger">*</span></label>
                        <div class="input-group has-validation">
                            <input type="text" class="form-control text-uppercase" id="f_reg_no" name="reg_no" maxlength="30" placeholder="S2025001" required>
                            <span class="input-group-text reg-status" id="regStatus"><i class="bi bi-hash"></i></span>
                            <div class="invalid-feedback" data-for="reg_no">Registration number is required.</div>
                            <div class="valid-feedback">Available.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="f_email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="f_email" name="email" maxlength="120" required>
                        <div class="invalid-feedback" data-for="email">Enter a valid email.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="f_first_name">First name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="f_first_name" name="first_name" maxlength="60" required>
                        <div class="invalid-feedback" data-for="first_name">First name is required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="f_last_name">Last name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="f_last_name" name="last_name" maxlength="60" required>
                        <div class="invalid-feedback" data-for="last_name">Last name is required.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="f_phone">Phone</label>
                        <input type="tel" class="form-control" id="f_phone" name="phone" maxlength="20" placeholder="0771234567">
                        <div class="invalid-feedback" data-for="phone">Enter a valid phone number.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="f_dob">Date of birth</label>
                        <input type="text" class="form-control" id="f_dob" name="dob" placeholder="YYYY-MM-DD">
                        <div class="invalid-feedback" data-for="dob">Choose a valid past date.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="f_gender">Gender</label>
                        <select class="form-select" id="f_gender" name="gender">
                            <?php foreach (StudentModel::GENDERS as $g): ?><option><?= e($g) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="f_department">Department <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="f_department" name="department" list="deptList" maxlength="80" required>
                        <datalist id="deptList">
                            <?php foreach (array_unique(array_merge($departments, ['Computer Science', 'Information Technology', 'Software Engineering'])) as $d): ?>
                                <option value="<?= e($d) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="invalid-feedback" data-for="department">Department is required.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="f_batch_year">Batch year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="f_batch_year" name="batch_year" min="2000" max="<?= (int) date('Y') + 1 ?>" value="<?= date('Y') ?>" required>
                        <div class="invalid-feedback" data-for="batch_year">Enter a valid year.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="f_status">Status</label>
                        <select class="form-select" id="f_status" name="status">
                            <?php foreach (StudentModel::STATUSES as $s): ?><option><?= e($s) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="f_address">Address</label>
                        <input type="text" class="form-control" id="f_address" name="address" maxlength="255">
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Enrolled courses</label>
                        <div class="course-grid">
                            <?php foreach ($courses as $c): ?>
                                <label class="course-chip">
                                    <input type="checkbox" name="course_ids[]" value="<?= (int) $c['id'] ?>">
                                    <span><strong><?= e($c['code']) ?></strong> <?= e($c['name']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-12" id="loginNote">
                        <div class="alert alert-info py-2 mb-0 small">
                            <i class="bi bi-info-circle me-1"></i>
                            A student login is created automatically: username = Reg. No, password = <code><?= e(DEFAULT_STUDENT_PASSWORD) ?></code>.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-gradient" id="btnSave"><i class="bi bi-check2-circle me-1"></i> Save student</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__, 2) . '/includes/footer.php'; ?>

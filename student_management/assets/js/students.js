/* =========================================================
   students.js - DataTable + Add/Edit modal + AJAX reg_no check
   ========================================================= */
(function () {
    'use strict';

    var API = APP.base + '/students/api';
    var tableEl = document.getElementById('studentsTable');
    var isAdmin = tableEl.dataset.admin === '1';
    var esc = SMS.esc;
    var table;

    /* ---------------- DataTable (AJAX source) ---------------- */
    function statusBadge(s) {
        var cls = { Active: 'success', Inactive: 'secondary', Graduated: 'info' }[s] || 'secondary';
        return '<span class="badge badge-soft-' + cls + '">' + esc(s) + '</span>';
    }

    table = $(tableEl).DataTable({
        ajax: { url: API + '?action=list', dataSrc: 'data' },
        responsive: false,
        pageLength: 10,
        order: [[0, 'asc']],
        language: { search: '', searchPlaceholder: 'Search students…', emptyTable: 'No students found.' },
        columnDefs: [{ targets: 'no-sort', orderable: false, searchable: false }],
        columns: [
            { data: 'reg_no', render: function (d) { return '<span class="reg-pill">' + esc(d) + '</span>'; } },
            {
                data: null, render: function (r) {
                    var ini = esc((r.first_name[0] || '') + (r.last_name[0] || ''));
                    return '<div class="d-flex align-items-center gap-2"><span class="avatar avatar-sm">' + ini + '</span>' +
                        '<div class="lh-sm"><div class="fw-semibold">' + esc(r.first_name + ' ' + r.last_name) + '</div>' +
                        '<small class="text-muted">' + esc(r.email) + '</small></div></div>';
                }
            },
            { data: 'department', render: esc },
            { data: 'batch_year' },
            { data: 'course_count', className: 'text-center' },
            { data: 'status', render: statusBadge },
            {
                data: null, className: 'text-end text-nowrap', render: function (r) {
                    var h = '<a class="btn btn-icon btn-soft-danger" title="Result PDF" target="_blank" href="' + APP.base + '/report/' + r.id + '"><i class="bi bi-file-earmark-pdf"></i></a> ';
                    if (isAdmin) {
                        h += '<button class="btn btn-icon btn-soft-primary" title="Edit" data-act="edit" data-id="' + r.id + '"><i class="bi bi-pencil-square"></i></button> ' +
                            '<button class="btn btn-icon btn-soft-danger" title="Delete" data-act="del" data-id="' + r.id + '" data-name="' + esc(r.first_name + ' ' + r.last_name) + '"><i class="bi bi-trash3"></i></button>';
                    }
                    return h;
                }
            }
        ]
    });

    if (!isAdmin) return;           // lecturers get a read-only directory

    /* ---------------- Modal + form ---------------- */
    var modalEl = document.getElementById('studentModal');
    var modal = new bootstrap.Modal(modalEl);
    var form = document.getElementById('studentForm');
    var regInput = document.getElementById('f_reg_no');
    var regStatus = document.getElementById('regStatus');
    var regOk = false, regTimer = null, regSeq = 0;

    var dobPicker = flatpickr('#f_dob', { dateFormat: 'Y-m-d', maxDate: 'today', altInput: true, altFormat: 'd M Y', allowInput: false });

    function clearErrors() {
        form.querySelectorAll('.is-invalid, .is-valid').forEach(function (el) { el.classList.remove('is-invalid', 'is-valid'); });
        regStatus.innerHTML = '<i class="bi bi-hash"></i>';
        regStatus.className = 'input-group-text reg-status';
    }

    function setInvalid(name, msg) {
        var input = form.querySelector('[name="' + name + '"]');
        if (!input) return;
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        var fb = form.querySelector('.invalid-feedback[data-for="' + name + '"]');
        if (fb && msg) fb.textContent = msg;
    }

    function resetForm(title) {
        form.reset();
        clearErrors();
        dobPicker.clear();
        document.getElementById('f_id').value = '0';
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('f_batch_year').value = new Date().getFullYear();
        document.getElementById('loginNote').style.display = title.indexOf('Add') === 0 ? '' : 'none';
        regOk = false;
    }

    /* ---- AJAX unique reg_no check (debounced) ---- */
    function checkReg() {
        var v = regInput.value.trim().toUpperCase();
        regInput.value = v;
        var id = document.getElementById('f_id').value;
        if (v === '') { regOk = false; clearErrors(); return; }
        if (!/^[A-Z0-9][A-Z0-9\-\/]{2,29}$/.test(v)) {
            regOk = false;
            setInvalid('reg_no', 'Use 3-30 characters: letters, numbers, - or /.');
            regStatus.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
            return;
        }
        var seq = ++regSeq;
        regStatus.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        SMS.api(API + '?action=check_reg&reg_no=' + encodeURIComponent(v) + '&exclude_id=' + encodeURIComponent(id)).then(function (res) {
            if (seq !== regSeq) return;                        // stale response
            if (!res.success) { regStatus.innerHTML = '<i class="bi bi-hash"></i>'; return; }
            if (res.exists) {
                regOk = false;
                setInvalid('reg_no', 'This registration number is already taken.');
                regStatus.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
            } else {
                regOk = true;
                regInput.classList.remove('is-invalid');
                regInput.classList.add('is-valid');
                regStatus.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            }
        });
    }
    regInput.addEventListener('input', function () {
        regOk = false;
        clearTimeout(regTimer);
        regTimer = setTimeout(checkReg, 350);
    });

    /* ---- client side validation (server re-validates everything) ---- */
    function validate() {
        var ok = true;
        function need(name, test, msg) {
            var el = form.elements[name];
            var val = (el.value || '').trim();
            if (!test(val)) { setInvalid(name, msg); ok = false; } else { el.classList.remove('is-invalid'); }
        }
        need('first_name', function (v) { return /^[A-Za-zÀ-ɏ][A-Za-zÀ-ɏ .'\-]*$/.test(v); }, 'First name is required (letters only).');
        need('last_name', function (v) { return /^[A-Za-zÀ-ɏ][A-Za-zÀ-ɏ .'\-]*$/.test(v); }, 'Last name is required (letters only).');
        need('email', function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v); }, 'Enter a valid email address.');
        need('phone', function (v) { return v === '' || /^[0-9+\-\s]{7,20}$/.test(v); }, 'Enter a valid phone number.');
        need('department', function (v) { return v !== ''; }, 'Department is required.');
        need('batch_year', function (v) { var y = +v; return y >= 2000 && y <= new Date().getFullYear() + 1; }, 'Enter a valid batch year.');
        if (!/^[A-Z0-9][A-Z0-9\-\/]{2,29}$/.test(regInput.value.trim().toUpperCase())) {
            setInvalid('reg_no', 'Registration number is required (3-30 chars).'); ok = false;
        } else if (regInput.classList.contains('is-invalid')) {
            ok = false;                                         // taken
        }
        return ok;
    }

    /* ---- open modal: add ---- */
    document.getElementById('btnAdd').addEventListener('click', function () {
        resetForm('Add Student');
        modal.show();
    });

    /* ---- table buttons: edit / delete ---- */
    tableEl.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-act]');
        if (!btn) return;
        var id = btn.dataset.id;

        if (btn.dataset.act === 'edit') {
            SMS.api(API + '?action=get&id=' + id).then(function (res) {
                if (!res.success) { SMS.toast(res.message, 'danger'); return; }
                var s = res.data;
                resetForm('Edit Student');
                document.getElementById('f_id').value = s.id;
                ['reg_no', 'first_name', 'last_name', 'email', 'phone', 'gender', 'address', 'department', 'batch_year', 'status'].forEach(function (k) {
                    form.elements[k].value = s[k] == null ? '' : s[k];
                });
                if (s.dob) dobPicker.setDate(s.dob, false);
                form.querySelectorAll('input[name="course_ids[]"]').forEach(function (cb) {
                    cb.checked = s.course_ids.indexOf(parseInt(cb.value, 10)) !== -1;
                });
                regOk = true;
                modal.show();
            });
        }

        if (btn.dataset.act === 'del') {
            SMS.confirmBox('Delete ' + btn.dataset.name + '? Their attendance, marks and login will be removed too.', 'Delete').then(function (yes) {
                if (!yes) return;
                var fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);
                SMS.api(API, { method: 'POST', body: fd }).then(function (res) {
                    SMS.toast(res.message, res.success ? 'success' : 'danger');
                    if (res.success) table.ajax.reload(null, false);
                });
            });
        }
    });

    /* ---- submit ---- */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(regTimer);
        if (!validate()) { SMS.toast('Please correct the highlighted fields.', 'warning'); return; }

        var btn = document.getElementById('btnSave');
        var label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

        var fd = new FormData(form);
        SMS.api(API, { method: 'POST', body: fd }).then(function (res) {
            btn.disabled = false;
            btn.innerHTML = label;
            if (res.success) {
                modal.hide();
                SMS.toast(res.message, 'success');
                table.ajax.reload(null, false);
            } else {
                if (res.errors) Object.keys(res.errors).forEach(function (k) { setInvalid(k, res.errors[k]); });
                SMS.toast(res.message || 'Could not save.', 'danger');
            }
        });
    });
})();

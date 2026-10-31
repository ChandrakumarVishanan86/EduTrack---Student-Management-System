/* =========================================================
   attendance.js - load roster via AJAX, toggle, bulk save
   ========================================================= */
(function () {
    'use strict';

    var API = APP.base + '/attendance/api';
    var esc = SMS.esc;
    var $ = function (id) { return document.getElementById(id); };

    var course = $('courseSelect'), dateInput = $('attDate');
    var panel = $('rosterPanel'), empty = $('emptyState'), list = $('rosterList');
    var loaded = { course: 0, date: '' };

    var picker = flatpickr(dateInput, {
        dateFormat: 'Y-m-d', altInput: true, altFormat: 'D, d M Y',
        defaultDate: 'today', maxDate: 'today', disableMobile: true,
        onChange: function () { if (course.value) load(); }
    });

    function currentDate() { return picker.input.value; }

    /* ---------- load roster ---------- */
    function load() {
        var cid = course.value, d = currentDate();
        if (!cid) { SMS.toast('Select a course first.', 'warning'); return; }
        if (!d) { SMS.toast('Pick a date.', 'warning'); return; }

        var btn = $('btnLoad'), label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading…';

        SMS.api(API + '?action=roster&course_id=' + encodeURIComponent(cid) + '&date=' + encodeURIComponent(d)).then(function (res) {
            btn.disabled = false;
            btn.innerHTML = label;
            if (!res.success) { SMS.toast(res.message, 'danger'); return; }
            loaded = { course: cid, date: d };
            render(res);
        });
    }

    function render(res) {
        empty.classList.add('d-none');
        panel.classList.remove('d-none');
        var opt = course.options[course.selectedIndex];
        $('rosterTitle').textContent = opt.text.replace(/\s*\(\d+ students\)$/, '');
        $('rosterSub').textContent = res.marked > 0
            ? 'Attendance was already recorded for this date - you are editing it.'
            : 'Not marked yet. Everyone defaults to Present.';

        if (!res.students.length) {
            list.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-people fs-1 d-block mb-2"></i>No active students are enrolled in this course.</div>';
            updateSummary();
            return;
        }

        list.innerHTML = res.students.map(function (s, i) {
            var present = s.status !== 'absent';           // unmarked => present
            var ini = esc(s.name.split(' ').map(function (p) { return p[0]; }).slice(0, 2).join(''));
            return '<div class="roster-row" style="animation-delay:' + Math.min(i * 30, 600) + 'ms" data-name="' + esc((s.name + ' ' + s.reg_no).toLowerCase()) + '">' +
                '<span class="avatar">' + ini + '</span>' +
                '<div class="flex-fill lh-sm"><div class="fw-semibold">' + esc(s.name) + '</div><small class="text-muted">' + esc(s.reg_no) + '</small></div>' +
                '<span class="att-label ' + (present ? 'is-present' : 'is-absent') + '">' + (present ? 'Present' : 'Absent') + '</span>' +
                '<label class="att-toggle" title="Toggle present / absent">' +
                '<input type="checkbox" data-sid="' + s.id + '"' + (present ? ' checked' : '') + '>' +
                '<span class="att-slider"></span></label></div>';
        }).join('');
        updateSummary();
    }

    /* ---------- toggles & summary ---------- */
    function boxes() { return list.querySelectorAll('input[type=checkbox][data-sid]'); }

    function paintRow(cb) {
        var lab = cb.closest('.roster-row').querySelector('.att-label');
        lab.textContent = cb.checked ? 'Present' : 'Absent';
        lab.className = 'att-label ' + (cb.checked ? 'is-present' : 'is-absent');
    }

    function updateSummary() {
        var all = boxes(), p = 0;
        all.forEach(function (c) { if (c.checked) p++; });
        var t = all.length, pct = t ? Math.round(p / t * 100) : 0;
        $('sumTotal').textContent = t;
        $('sumPresent').textContent = p;
        $('sumAbsent').textContent = t - p;
        $('sumPct').textContent = pct + '%';
        $('sumBar').style.width = pct + '%';
        $('btnSave').disabled = t === 0;
    }

    list.addEventListener('change', function (e) {
        if (e.target.matches('input[data-sid]')) { paintRow(e.target); updateSummary(); }
    });

    function setAll(v) {
        boxes().forEach(function (c) {
            if (c.closest('.roster-row').classList.contains('d-none')) return;   // only visible rows
            c.checked = v; paintRow(c);
        });
        updateSummary();
    }
    $('allPresent').addEventListener('click', function () { setAll(true); });
    $('allAbsent').addEventListener('click', function () { setAll(false); });

    $('rosterSearch').addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        list.querySelectorAll('.roster-row').forEach(function (r) {
            r.classList.toggle('d-none', q !== '' && r.dataset.name.indexOf(q) === -1);
        });
    });

    /* ---------- bulk save (single request -> PDO transaction) ---------- */
    $('btnSave').addEventListener('click', function () {
        if (!loaded.course) return;
        var records = [];
        boxes().forEach(function (c) { records.push({ student_id: parseInt(c.dataset.sid, 10), status: c.checked ? 'present' : 'absent' }); });
        if (!records.length) return;

        var btn = this, label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

        SMS.api(API, {
            method: 'POST',
            body: { action: 'save', course_id: parseInt(loaded.course, 10), date: loaded.date, records: records }
        }).then(function (res) {
            btn.disabled = false;
            btn.innerHTML = label;
            SMS.toast(res.message, res.success ? 'success' : 'danger');
            if (res.success) $('rosterSub').textContent = 'Saved just now. You can still edit and save again.';
        });
    });

    $('btnLoad').addEventListener('click', load);
    course.addEventListener('change', function () { if (course.value) load(); });

    // Warn about unsaved changes when switching course/date? keep it simple: reload replaces the list.
})();

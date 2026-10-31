/* =========================================================
   marks.js - validation (0-100), live grade + GPA (avg / 25)
   The same rules are enforced again in PHP (MarkModel::save).
   ========================================================= */
(function () {
    'use strict';

    var API = APP.base + '/marks/api';
    var esc = SMS.esc;
    var $ = function (id) { return document.getElementById(id); };
    var course = $('courseSelect'), body = $('marksBody');
    var loadedCourse = 0;

    /* ---------- load ---------- */
    function load() {
        if (!course.value) { SMS.toast('Select a course first.', 'warning'); return; }
        var btn = $('btnLoad'), label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading…';

        SMS.api(API + '?action=roster&course_id=' + encodeURIComponent(course.value)).then(function (res) {
            btn.disabled = false;
            btn.innerHTML = label;
            if (!res.success) { SMS.toast(res.message, 'danger'); return; }
            loadedCourse = course.value;
            $('emptyState').classList.add('d-none');
            $('marksPanel').classList.remove('d-none');
            $('marksTitle').textContent = course.options[course.selectedIndex].text;

            if (!res.students.length) {
                body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-5">No students are enrolled in this course.</td></tr>';
            } else {
                body.innerHTML = res.students.map(function (s) {
                    return '<tr data-sid="' + s.id + '"><td><span class="reg-pill">' + esc(s.reg_no) + '</span></td>' +
                        '<td class="fw-semibold">' + esc(s.name) + '</td>' +
                        '<td><input type="number" class="form-control mark-input" min="0" max="100" step="0.01" inputmode="decimal" ' +
                        'value="' + (s.marks === null ? '' : s.marks) + '" placeholder="–" data-sid="' + s.id + '"></td>' +
                        '<td class="text-center"><span class="grade-chip g-none">–</span></td>' +
                        '<td class="text-center gp-cell">–</td></tr>';
                }).join('');
            }
            recalc();
        });
    }

    /* ---------- validation of a single input ---------- */
    function validInput(inp) {
        var raw = inp.value.trim();
        if (raw === '') { inp.classList.remove('is-invalid'); return true; }
        var v = Number(raw);
        var ok = isFinite(v) && v >= 0 && v <= 100;
        inp.classList.toggle('is-invalid', !ok);
        return ok;
    }

    /* ---------- recalc rows + summary + GPA ---------- */
    function recalc() {
        var vals = [], total = 0, bad = 0;
        body.querySelectorAll('tr[data-sid]').forEach(function (tr) {
            total++;
            var inp = tr.querySelector('.mark-input');
            var chip = tr.querySelector('.grade-chip'), gp = tr.querySelector('.gp-cell');
            var ok = validInput(inp);
            if (!ok) bad++;
            if (ok && inp.value.trim() !== '') {
                var m = Number(inp.value);
                vals.push(m);
                var g = SMS.grade(m);
                chip.textContent = g;
                chip.className = 'grade-chip g-' + g.replace('+', 'plus');
                gp.textContent = SMS.gradePoint(m).toFixed(2);
            } else {
                chip.textContent = '–'; chip.className = 'grade-chip g-none'; gp.textContent = '–';
            }
        });

        var n = vals.length;
        var avg = n ? vals.reduce(function (a, b) { return a + b; }, 0) / n : 0;
        var gpa = avg / 25;                                    // <-- GPA = avg / 25

        $('gpaValue').textContent = gpa.toFixed(2);
        $('sEntered').textContent = n + ' / ' + total;
        $('sAvg').textContent = avg.toFixed(2);
        $('sMax').textContent = n ? Math.max.apply(null, vals).toFixed(1) : '–';
        $('sMin').textContent = n ? Math.min.apply(null, vals).toFixed(1) : '–';
        $('sPass').textContent = n ? Math.round(vals.filter(function (v) { return v >= 50; }).length / n * 100) + '%' : '–';

        var box = $('errBox');
        box.classList.toggle('d-none', bad === 0);
        box.textContent = bad ? bad + ' mark(s) outside 0-100. Fix them to save.' : '';
        $('btnSave').disabled = bad > 0 || total === 0;
    }

    body.addEventListener('input', function (e) { if (e.target.classList.contains('mark-input')) recalc(); });

    /* Enter moves to the next row (faster data entry) */
    body.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || !e.target.classList.contains('mark-input')) return;
        e.preventDefault();
        var all = Array.prototype.slice.call(body.querySelectorAll('.mark-input'));
        var next = all[all.indexOf(e.target) + 1];
        if (next) { next.focus(); next.select(); }
    });

    /* ---------- save ---------- */
    $('btnSave').addEventListener('click', function () {
        // validate once more *before* submit and recompute the GPA
        var entries = [], bad = false;
        body.querySelectorAll('.mark-input').forEach(function (inp) {
            if (!validInput(inp)) { bad = true; return; }
            entries.push({ student_id: parseInt(inp.dataset.sid, 10), marks: inp.value.trim() });
        });
        recalc();
        if (bad) { SMS.toast('Marks must be between 0 and 100.', 'danger'); return; }
        if (!entries.some(function (e) { return e.marks !== ''; })) { SMS.toast('Enter at least one mark.', 'warning'); return; }

        var btn = this, label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

        SMS.api(API, { method: 'POST', body: { action: 'save', course_id: parseInt(loadedCourse, 10), entries: entries } }).then(function (res) {
            btn.innerHTML = label;
            recalc();
            if (res.errors) {
                Object.keys(res.errors).forEach(function (sid) {
                    var inp = body.querySelector('.mark-input[data-sid="' + sid + '"]');
                    if (inp) inp.classList.add('is-invalid');
                });
            }
            SMS.toast(res.message, res.success ? 'success' : 'danger');
        });
    });

    $('btnLoad').addEventListener('click', load);
    course.addEventListener('change', function () { if (course.value) load(); });
})();

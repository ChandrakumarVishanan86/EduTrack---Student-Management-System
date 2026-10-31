/* =========================================================
   main.js - shared helpers (vanilla JS)
   Exposes window.SMS: toast, api, confirmBox, esc, grade, gradePoint
   ========================================================= */
(function () {
    'use strict';

    var APP = window.APP || { base: '', csrf: '' };

    /* ---------- escape for innerHTML ---------- */
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* ---------- grading (mirrors grade_from_marks() in PHP) ---------- */
    function grade(m) {
        if (m >= 90) return 'A+';
        if (m >= 80) return 'A';
        if (m >= 75) return 'B+';
        if (m >= 70) return 'B';
        if (m >= 65) return 'C+';
        if (m >= 60) return 'C';
        if (m >= 50) return 'D';
        return 'F';
    }
    function gradePoint(m) { return Math.round((m / 25) * 100) / 100; }   // marks / 25

    /* ---------- toasts ---------- */
    function toast(message, type) {
        type = type || 'success';
        var host = document.getElementById('toastHost');
        if (!host) { alert(message); return; }
        var icons = { success: 'check-circle-fill', danger: 'x-octagon-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
        var el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + type + ' border-0 shadow';
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="bi bi-' + (icons[type] || icons.info) + ' me-2"></i>' +
            esc(message) + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        host.appendChild(el);
        var t = new bootstrap.Toast(el, { delay: 4500 });
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        t.show();
    }

    /* ---------- fetch wrapper (always returns parsed JSON) ---------- */
    function api(url, opts) {
        opts = opts || {};
        var init = {
            method: opts.method || 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': APP.csrf }
        };
        if (opts.body instanceof FormData) {
            init.body = opts.body;
        } else if (opts.body) {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(opts.body);
        }
        return fetch(url, init).then(function (res) {
            return res.json().catch(function () {
                return { success: false, message: 'Unexpected server response (' + res.status + ').' };
            }).then(function (data) {
                data.httpStatus = res.status;
                if (res.status === 401) { window.location = APP.base + '/login'; }
                return data;
            });
        }).catch(function () {
            return { success: false, message: 'Network error. Check your connection.' };
        });
    }

    /* ---------- confirm dialog (Promise<boolean>) ---------- */
    function confirmBox(message, okLabel, variant) {
        return new Promise(function (resolve) {
            var wrap = document.createElement('div');
            wrap.className = 'modal fade';
            wrap.tabIndex = -1;
            wrap.innerHTML =
                '<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content text-center p-3">' +
                '<div class="confirm-icon mx-auto mb-3"><i class="bi bi-question-lg"></i></div>' +
                '<p class="mb-4 fw-medium">' + esc(message) + '</p>' +
                '<div class="d-flex gap-2 justify-content-center">' +
                '<button class="btn btn-light flex-fill" data-bs-dismiss="modal">Cancel</button>' +
                '<button class="btn btn-' + (variant || 'danger') + ' flex-fill" data-ok>' + esc(okLabel || 'Confirm') + '</button>' +
                '</div></div></div>';
            document.body.appendChild(wrap);
            var m = new bootstrap.Modal(wrap), answered = false;
            wrap.querySelector('[data-ok]').addEventListener('click', function () { answered = true; m.hide(); });
            wrap.addEventListener('hidden.bs.modal', function () { wrap.remove(); resolve(answered); });
            m.show();
        });
    }

    /* ---------- links with data-confirm ---------- */
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-confirm]');
        if (!a) return;
        e.preventDefault();
        confirmBox(a.dataset.confirm, 'Yes, continue', 'primary').then(function (ok) { if (ok) window.location = a.href; });
    });

    /* ---------- animated counters ---------- */
    function animateCounters() {
        document.querySelectorAll('[data-count]').forEach(function (el) {
            var target = parseFloat(el.dataset.count) || 0;
            var decimals = parseInt(el.dataset.decimals || '0', 10);
            var suffix = el.dataset.suffix || '';
            var start = null, dur = 1000;
            function step(ts) {
                if (start === null) start = ts;
                var p = Math.min((ts - start) / dur, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = (target * eased).toFixed(decimals) + suffix;
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }

    /* ---------- circle progress (CSS conic-gradient, animated via --p) ---------- */
    function animateCircles() {
        document.querySelectorAll('.circle-progress[data-value]').forEach(function (el) {
            var v = Math.max(0, Math.min(100, parseFloat(el.dataset.value) || 0));
            el.classList.toggle('low', v < 75);
            el.classList.toggle('critical', v < 60);
            setTimeout(function () { el.style.setProperty('--p', v); }, 150);
            var label = el.querySelector('.cp-value');
            if (label) label.setAttribute('data-count', v), label.dataset.decimals = '1', label.dataset.suffix = '%';
        });
    }

    /* ---------- Chart.js defaults + palette ---------- */
    var PALETTE = ['#6366f1', '#06b6d4', '#f59e0b', '#ec4899', '#10b981', '#8b5cf6', '#ef4444', '#64748b'];
    function chartDefaults() {
        if (!window.Chart) return;
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.color = '#64748b';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.animation.duration = 1100;
        Chart.defaults.animation.easing = 'easeOutQuart';
    }

    document.addEventListener('DOMContentLoaded', function () {
        chartDefaults();
        animateCircles();
        animateCounters();
        document.querySelectorAll('.alert-dismissible').forEach(function (a) {
            setTimeout(function () { bootstrap.Alert.getOrCreateInstance(a).close(); }, 7000);
        });
    });

    window.SMS = { esc: esc, grade: grade, gradePoint: gradePoint, toast: toast, api: api, confirmBox: confirmBox, palette: PALETTE };
})();

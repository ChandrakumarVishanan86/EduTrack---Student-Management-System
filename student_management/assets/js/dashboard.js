/* =========================================================
   dashboard.js - builds the charts for all 3 dashboards
   Data comes from window.DASH (set by each dashboard page)
   ========================================================= */
(function () {
    'use strict';
    if (!window.Chart || !window.DASH) return;

    var P = SMS.palette, D = window.DASH;
    function el(id) { return document.getElementById(id); }
    function labels(a) { return a.map(function (x) { return x.label; }); }
    function values(a) { return a.map(function (x) { return +x.value; }); }

    var grid = { color: 'rgba(100,116,139,.12)' };

    /* Bar: average marks per course (admin) */
    if (el('chartCourseAvg') && D.courseAvg) {
        new Chart(el('chartCourseAvg'), {
            type: 'bar',
            data: { labels: labels(D.courseAvg), datasets: [{ label: 'Average marks', data: values(D.courseAvg), backgroundColor: P[0], borderRadius: 8, maxBarThickness: 36 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100, grid: grid }, x: { grid: { display: false } } } }
        });
    }

    /* Doughnut: students by department (admin) */
    if (el('chartDept') && D.dept) {
        new Chart(el('chartDept'), {
            type: 'doughnut',
            data: { labels: labels(D.dept), datasets: [{ data: values(D.dept), backgroundColor: P, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }] },
            options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom' } } }
        });
    }

    /* Grade distribution (admin + lecturer) */
    if (el('chartGrades') && D.grades) {
        var gc = { 'A+': '#10b981', 'A': '#34d399', 'B+': '#06b6d4', 'B': '#6366f1', 'C+': '#8b5cf6', 'C': '#f59e0b', 'D': '#fb923c', 'F': '#ef4444' };
        new Chart(el('chartGrades'), {
            type: 'bar',
            data: { labels: labels(D.grades), datasets: [{ label: 'Students', data: values(D.grades), backgroundColor: D.grades.map(function (g) { return gc[g.label] || P[0]; }), borderRadius: 8, maxBarThickness: 42 }] },
            options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: grid }, x: { grid: { display: false } } } }
        });
    }

    /* Student result chart */
    if (el('chartResults') && D.results) {
        var colors = D.results.map(function (r) { return r.value >= 75 ? '#10b981' : r.value >= 50 ? '#6366f1' : '#ef4444'; });
        new Chart(el('chartResults'), {
            type: 'bar',
            data: { labels: labels(D.results), datasets: [{ label: 'Marks', data: values(D.results), backgroundColor: colors, borderRadius: 10, maxBarThickness: 48 }] },
            options: {
                maintainAspectRatio: false, plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100, grid: grid }, x: { grid: { display: false } } }
            }
        });
    }
})();

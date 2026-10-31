<?php
/** Closes the layout opened in header.php and loads scripts. */
declare(strict_types=1);
$libs        = $libs ?? [];
$pageScripts = $pageScripts ?? [];      // e.g. ['assets/js/students.js']
$user        = Auth::user();
?>
        </main>

        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; Built with PHP 8, PDO &amp; Bootstrap 5</span>
        </footer>
    </div>
</div>

<!-- Toast container (used by main.js) -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastHost" style="z-index:2000"></div>

<script>
    window.APP = {
        base: <?= json_encode(APP_BASE) ?>,
        csrf: <?= json_encode(csrf_token()) ?>,
        role: <?= json_encode($user['role'] ?? null) ?>
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (in_array('datatables', $libs, true)): ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<?php endif; ?>
<?php if (in_array('flatpickr', $libs, true)): ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<?php endif; ?>
<?php if (in_array('chart', $libs, true)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= url('assets/js/main.js') ?>?v=1"></script>
<?php foreach ($pageScripts as $src): ?>
<script src="<?= url($src) ?>?v=1"></script>
<?php endforeach; ?>
</body>
</html>

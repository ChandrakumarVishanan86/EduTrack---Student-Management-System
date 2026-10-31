<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (Auth::check()) {
    redirect(Auth::homeFor(Auth::user()['role']));
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify()) {
        $error = 'Your session expired. Please try again.';
    } elseif (($wait = Auth::lockedFor()) > 0) {
        $error = "Too many failed attempts. Try again in {$wait} seconds.";
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $user = Auth::attempt($username, $password);
        if ($user) {
            redirect(Auth::homeFor($user['role']));          // role based redirect
        }
        $error = ($wait = Auth::lockedFor()) > 0
            ? "Too many failed attempts. Try again in {$wait} seconds."
            : 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>?v=1" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-bg" aria-hidden="true">
        <span class="orb orb-1"></span><span class="orb orb-2"></span><span class="orb orb-3"></span>
    </div>

    <div class="container min-vh-100 d-flex align-items-center py-4">
        <div class="row w-100 g-0 login-wrap mx-auto">
            <!-- Left: pitch -->
            <div class="col-lg-6 d-none d-lg-flex login-hero">
                <div class="m-auto text-white p-5">
                    <span class="brand-mark brand-mark-lg mb-4"><i class="bi bi-mortarboard-fill"></i></span>
                    <h1 class="display-6 fw-bold mb-3">Manage students,<br>attendance &amp; results<br>in one place.</h1>
                    <p class="opacity-75 mb-4">Role-based dashboards for administrators, lecturers and students.</p>
                    <ul class="list-unstyled hero-points">
                        <li><i class="bi bi-check-circle-fill"></i> Live attendance tracking</li>
                        <li><i class="bi bi-check-circle-fill"></i> Automatic GPA calculation</li>
                        <li><i class="bi bi-check-circle-fill"></i> One-click PDF result sheets</li>
                    </ul>
                </div>
            </div>

            <!-- Right: form -->
            <div class="col-lg-6">
                <div class="login-card h-100">
                    <h2 class="fw-bold mb-1">Welcome back 👋</h2>
                    <p class="text-muted mb-4">Sign in to continue to <?= e(APP_NAME) ?></p>

                    <?php foreach (pull_flash() as $f): ?>
                        <div class="alert alert-<?= e($f['type']) ?> py-2"><?= e($f['message']) ?></div>
                    <?php endforeach; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center shake" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= url('login') ?>" id="loginForm" novalidate autocomplete="on">
                        <?= csrf_field() ?>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="Username"
                                   value="<?= e($username) ?>" required autofocus autocomplete="username">
                            <label for="username"><i class="bi bi-person me-1"></i> Username / Reg. No</label>
                            <div class="invalid-feedback">Enter your username.</div>
                        </div>

                        <div class="form-floating mb-3 position-relative">
                            <input type="password" class="form-control pe-5" id="password" name="password" placeholder="Password"
                                   required autocomplete="current-password">
                            <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
                            <button type="button" class="btn-eye" id="togglePw" aria-label="Show password"><i class="bi bi-eye"></i></button>
                            <div class="invalid-feedback">Enter your password.</div>
                        </div>

                        <button class="btn btn-gradient w-100 py-2 fw-semibold" type="submit" id="loginBtn">
                            <span class="btn-label">Sign in <i class="bi bi-arrow-right ms-1"></i></span>
                        </button>
                    </form>

                    <div class="demo-box mt-4">
                        <small class="text-muted d-block mb-2">Demo accounts (click to fill)</small>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-soft demo-fill" data-u="admin" data-p="admin123"><i class="bi bi-shield-lock"></i> Admin</button>
                            <button type="button" class="btn btn-sm btn-soft demo-fill" data-u="lecturer1" data-p="lecturer123"><i class="bi bi-person-workspace"></i> Lecturer</button>
                            <button type="button" class="btn btn-sm btn-soft demo-fill" data-u="S2024001" data-p="student123"><i class="bi bi-backpack2"></i> Student</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
(function () {
    var form = document.getElementById('loginForm');
    var pw = document.getElementById('password'), un = document.getElementById('username');

    document.getElementById('togglePw').addEventListener('click', function () {
        var show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        this.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
    });

    document.querySelectorAll('.demo-fill').forEach(function (b) {
        b.addEventListener('click', function () { un.value = b.dataset.u; pw.value = b.dataset.p; un.classList.remove('is-invalid'); pw.classList.remove('is-invalid'); });
    });

    form.addEventListener('submit', function (e) {
        var ok = true;
        [un, pw].forEach(function (f) {
            var bad = f.value.trim() === '';
            f.classList.toggle('is-invalid', bad);
            if (bad) ok = false;
        });
        if (!ok) { e.preventDefault(); return; }
        var btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in…';
    });
})();
</script>
</body>
</html>

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

// /dashboard -> role-specific dashboard
$user = Auth::guard();
redirect(Auth::homeFor($user['role']));

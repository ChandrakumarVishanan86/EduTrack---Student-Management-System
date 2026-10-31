<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

// Entry point: send everyone to the right place.
redirect(Auth::check() ? Auth::homeFor(Auth::user()['role']) : 'login');

<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

Auth::logout();
Auth::start();                       // fresh session just to carry the message
flash('success', 'You have been signed out.');
redirect('login');

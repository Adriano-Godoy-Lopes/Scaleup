<?php
require_once __DIR__.'/functions.php';
if (session_status()===PHP_SESSION_NONE) session_start();
function logged_in(): bool { return !empty($_SESSION['user']); }
function require_login(): void { if(!logged_in()){ header('Location: login.php'); exit; } }

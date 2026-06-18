<?php
session_start();

// Clear cached auth verification flag
unset($_SESSION['auth_verified']);

require_once __DIR__ . '/../vendor/autoload.php';
$supabase = require_once __DIR__ . '/../Config/supabase.php';
use Core\Services\AuthService;

$authService = new AuthService($supabase);
$authService->destroySession();
header("Location: index.php");
exit;
?>
<?php
// index.php — Redirect ke login atau dashboard
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/dashboard.php');
} else {
    redirect(BASE_URL . '/login.php');
}

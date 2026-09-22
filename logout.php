<?php
// logout.php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
redirect(BASE_URL . '/login.php');

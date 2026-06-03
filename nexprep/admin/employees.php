<?php
require_once __DIR__.'/../includes/config.php';
startSecureSession();
requireRole('admin');
header("Location: ".APP_URL."/admin/instructors.php");
exit;

<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
header("Location: ".APP_URL."/admin/users.php?role=student");
exit;

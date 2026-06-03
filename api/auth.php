<?php
require_once __DIR__.'/../includes/config.php';
startSecureSession();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---- LOGOUT ----
if ($action === 'logout') {
    session_unset(); session_destroy();
    header('Location: '.APP_URL.'/index.php'); exit;
}

header('Content-Type: application/json');

// ---- LOGIN ----
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']    ?? '');
    $pass  = trim($_POST['password'] ?? '');
    if (!$email || !$pass) jsonResponse(false, 'Please enter email and password.');

    $db   = getDB();
    $stmt = $db->prepare("SELECT id,name,email,password,role,status FROM users WHERE email=? LIMIT 1");
    $stmt->execute([strtolower($email)]);
    $user = $stmt->fetch();

    if (!$user) jsonResponse(false, 'No account found with that email address.');
    if (!password_verify($pass, $user['password'])) jsonResponse(false, 'Incorrect password. Please try again.');
    if ($user['status'] === 'suspended') jsonResponse(false, 'Your account has been suspended. Contact the admin.');

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['last_active']= time();

    $dest = match($user['role']) {
        'admin'      => APP_URL.'/admin/dashboard.php',
        'instructor' => APP_URL.'/instructor/dashboard.php',
        default      => APP_URL.'/student/dashboard.php',
    };
    jsonResponse(true, 'Login successful!', ['redirect' => $dest, 'role' => $user['role']]);
}

// ---- REGISTER ----
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']        ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $pass    = $_POST['password']  ?? '';
    $confirm = $_POST['confirm']   ?? '';
    $target  = trim($_POST['target_exam'] ?? '');
    $city    = trim($_POST['city']        ?? '');

    if (!$name || !$email || !$pass || !$confirm) jsonResponse(false, 'All fields are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  jsonResponse(false, 'Invalid email address.');
    if (strlen($pass) < 6)    jsonResponse(false, 'Password must be at least 6 characters.');
    if ($pass !== $confirm)   jsonResponse(false, 'Passwords do not match.');

    $db = getDB();
    $chk = $db->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $chk->execute([$email]);
    if ($chk->fetch()) jsonResponse(false, 'An account with this email already exists.');

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'student')")->execute([$name,$email,$hash]);
    $uid = (int)$db->lastInsertId();
    $db->prepare("INSERT INTO student_profiles (user_id,target_exam,city) VALUES (?,?,?)")->execute([$uid,$target,$city]);
    jsonResponse(true, 'Account created! You can now log in.');
}

// ---- CHANGE PASSWORD ----
if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin();
    $uid     = currentUser()['id'];
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$current || !$new || !$confirm) jsonResponse(false, 'All fields required.');
    if ($new !== $confirm)               jsonResponse(false, 'New passwords do not match.');
    if (strlen($new) < 6)               jsonResponse(false, 'Password must be at least 6 characters.');
    $db   = getDB();
    $row  = $db->prepare("SELECT password FROM users WHERE id=?");
    $row->execute([$uid]);
    $u = $row->fetch();
    if (!$u || !password_verify($current, $u['password'])) jsonResponse(false, 'Current password is incorrect.');
    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $uid]);
    jsonResponse(true, 'Password changed successfully.');
}

jsonResponse(false, 'Invalid request.');

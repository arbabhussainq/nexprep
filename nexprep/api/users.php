<?php
// ============================================================
// NexPrep - Users API  (api/users.php)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
startSecureSession();
requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db     = getDB();
$me     = currentUser();

// ============================================================
// LIST USERS
// ============================================================
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    requireRole('admin');
    $role   = $_GET['role']   ?? '';
    $search = $_GET['search'] ?? '';
    $sql    = "SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
                      sp.target_exam, sp.total_tests_taken, sp.rank_points
               FROM users u LEFT JOIN student_profiles sp ON u.id = sp.user_id
               WHERE 1=1";
    $params = [];
    if ($role)   { $sql .= " AND u.role = ?"; $params[] = $role; }
    if ($search) { $sql .= " AND (u.name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    $sql .= " ORDER BY u.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    jsonResponse(true, '', ['users' => $stmt->fetchAll()]);
}

// ============================================================
// CREATE USER (admin creates employee or student)
// ============================================================
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role']     ?? 'student');

    if (!$name || !$email || !$password) jsonResponse(false, 'All fields required.');
    if (!in_array($role, ['admin','employee','student'])) jsonResponse(false, 'Invalid role.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, 'Invalid email.');

    $check = $db->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);
    if ($check->fetch()) jsonResponse(false, 'Email already in use.');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
       ->execute([$name,$email,$hash,$role]);
    $uid = $db->lastInsertId();

    if ($role === 'student') {
        $db->prepare("INSERT INTO student_profiles (user_id) VALUES (?)")->execute([$uid]);
    }
    jsonResponse(true, 'User created successfully.', ['user_id' => $uid]);
}

// ============================================================
// UPDATE USER STATUS
// ============================================================
if ($action === 'set_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    $id     = intval($_POST['id']     ?? 0);
    $status = trim($_POST['status']   ?? '');
    if (!$id || !in_array($status, ['active','suspended'])) jsonResponse(false, 'Invalid.');
    if ($id === $me['id']) jsonResponse(false, 'Cannot change your own status.');
    $db->prepare("UPDATE users SET status=? WHERE id=?")->execute([$status, $id]);
    jsonResponse(true, 'Status updated.');
}

// ============================================================
// CHANGE ROLE
// ============================================================
if ($action === 'set_role' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    $id   = intval($_POST['id']   ?? 0);
    $role = trim($_POST['role']   ?? '');
    if (!$id || !in_array($role, ['admin','employee','student'])) jsonResponse(false, 'Invalid.');
    if ($id === $me['id']) jsonResponse(false, 'Cannot change your own role.');
    $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $id]);
    jsonResponse(true, 'Role updated.');
}

// ============================================================
// DELETE USER
// ============================================================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    $id = intval($_POST['id'] ?? 0);
    if (!$id) jsonResponse(false, 'Invalid.');
    if ($id === $me['id']) jsonResponse(false, 'Cannot delete yourself.');
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    jsonResponse(true, 'User deleted.');
}

// ============================================================
// GET PROFILE (own)
// ============================================================
if ($action === 'get_profile' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid  = $me['id'];
    $stmt = $db->prepare("SELECT u.id,u.name,u.email,u.role,u.created_at,sp.target_exam,sp.city,sp.total_tests_taken,sp.total_score,sp.rank_points FROM users u LEFT JOIN student_profiles sp ON u.id=sp.user_id WHERE u.id=?");
    $stmt->execute([$uid]);
    jsonResponse(true, '', ['profile' => $stmt->fetch()]);
}

// ============================================================
// UPDATE PROFILE
// ============================================================
if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid        = $me['id'];
    $name       = trim($_POST['name']        ?? '');
    $target     = trim($_POST['target_exam'] ?? '');
    $city       = trim($_POST['city']        ?? '');
    if (!$name) jsonResponse(false, 'Name required.');
    $db->prepare("UPDATE users SET name=? WHERE id=?")->execute([$name,$uid]);
    $db->prepare("UPDATE student_profiles SET target_exam=?,city=? WHERE user_id=?")->execute([$target,$city,$uid]);
    $_SESSION['user_name'] = $name;
    jsonResponse(true, 'Profile updated.');
}

// ============================================================
// STATS for admin dashboard
// ============================================================
if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    requireRole('admin','employee');
    $stats = [];
    $stats['total_students']  = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $stats['total_employees'] = $db->query("SELECT COUNT(*) FROM users WHERE role='employee'")->fetchColumn();
    $stats['total_tests']     = $db->query("SELECT COUNT(*) FROM tests")->fetchColumn();
    $stats['total_attempts']  = $db->query("SELECT COUNT(*) FROM test_attempts WHERE status='completed'")->fetchColumn();
    $stats['total_questions'] = $db->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    jsonResponse(true, '', ['stats' => $stats]);
}

// ============================================================
// ADMIN RESET PASSWORD
// ============================================================
if ($action === 'admin_reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    $id  = intval($_POST['id']       ?? 0);
    $pwd = trim($_POST['password']   ?? '');
    if (!$id || strlen($pwd) < 6) jsonResponse(false, 'Invalid request or password too short.');
    $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($pwd, PASSWORD_BCRYPT), $id]);
    jsonResponse(true, 'Password reset successfully.');
}

// ============================================================
// DANGER ZONE ACTIONS (admin only)
// ============================================================
if ($action === 'reset_leaderboard' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    $db->exec("DELETE FROM leaderboard");
    $db->exec("UPDATE student_profiles SET rank_points=0");
    jsonResponse(true, 'Leaderboard reset. All rank points cleared.');
}

if ($action === 'clear_attempts' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin');
    $db->exec("DELETE FROM attempt_answers");
    $db->exec("DELETE FROM test_attempts");
    $db->exec("DELETE FROM leaderboard");
    $db->exec("UPDATE student_profiles SET total_tests_taken=0, total_score=0, rank_points=0");
    jsonResponse(true, 'All attempts cleared. Student profiles reset.');
}

jsonResponse(false, 'Unknown action.');

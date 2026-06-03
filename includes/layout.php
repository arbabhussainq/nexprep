<?php
// ============================================================
// NexPrep - Layout Partials (Light Theme)
// ============================================================
if (!defined('APP_NAME')) require_once __DIR__.'/config.php';

function renderHead(string $title = '', string $extra = ''): void {
    $t   = $title ? "$title | ".APP_NAME : APP_NAME;
    $url = APP_URL;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>$t</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link href="{$url}/assets/css/nexprep.css" rel="stylesheet">
$extra
</head>
HTML;
}

function renderSidebar(): void {
    startSecureSession();
    $u    = currentUser();
    $role = $u['role'];
    $url  = APP_URL;
    $init = strtoupper(mb_substr($u['name'], 0, 1));
    $name = sanitize($u['name']);
    $roleLabel = match($role) {
        'admin'      => 'Administrator',
        'instructor' => 'Instructor',
        default      => 'Student',
    };

    // Build nav sections
    $studentNav = $instructorNav = $adminNav = '';

    if ($role === 'student') {
        $studentNav = <<<HTML
        <div class="nav-section">Student</div>
        <a href="{$url}/student/dashboard.php"><i class="bi bi-grid-fill"></i>Dashboard</a>
        <a href="{$url}/student/tests.php"><i class="bi bi-journal-check"></i>Take Tests</a>
        <a href="{$url}/student/results.php"><i class="bi bi-bar-chart-fill"></i>My Results</a>
        <a href="{$url}/student/leaderboard.php"><i class="bi bi-trophy-fill"></i>Leaderboard</a>
        <a href="{$url}/student/profile.php"><i class="bi bi-person-fill"></i>My Profile</a>
HTML;
    }

    if ($role === 'instructor') {
        $instructorNav = <<<HTML
        <div class="nav-section">Instructor</div>
        <a href="{$url}/instructor/dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
        <a href="{$url}/instructor/tests.php"><i class="bi bi-journal-plus"></i>My Tests</a>
        <a href="{$url}/instructor/questions.php"><i class="bi bi-question-circle-fill"></i>MCQ Bank</a>
        <a href="{$url}/instructor/leaderboard.php"><i class="bi bi-trophy-fill"></i>Leaderboard</a>
        <a href="{$url}/instructor/profile.php"><i class="bi bi-person-fill"></i>Profile</a>
HTML;
    }

    if ($role === 'admin') {
        $adminNav = <<<HTML
        <div class="nav-section">Overview</div>
        <a href="{$url}/admin/dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
        <a href="{$url}/admin/reports.php"><i class="bi bi-bar-chart-fill"></i>Reports</a>
        <div class="nav-section">Content</div>
        <a href="{$url}/admin/tests.php"><i class="bi bi-journal-check"></i>All Tests</a>
        <a href="{$url}/admin/leaderboard.php"><i class="bi bi-trophy-fill"></i>Leaderboard</a>
        <div class="nav-section">Users</div>
        <a href="{$url}/admin/users.php"><i class="bi bi-people-fill"></i>All Users</a>
        <a href="{$url}/admin/instructors.php"><i class="bi bi-person-badge-fill"></i>Instructors</a>
        <a href="{$url}/admin/students.php"><i class="bi bi-mortarboard-fill"></i>Students</a>
        <div class="nav-section">System</div>
        <a href="{$url}/admin/settings.php"><i class="bi bi-gear-fill"></i>Settings</a>
HTML;
    }

    echo <<<HTML
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <span class="logo-icon">⚡</span>
        <div>
            <div class="brand-name">NexPrep</div>
            <div class="brand-tag">Exam Prep</div>
        </div>
    </div>
    <div class="sidebar-user">
        <div class="user-ava">$init</div>
        <div>
            <div class="u-name">$name</div>
            <div class="u-role">$roleLabel</div>
        </div>
    </div>
    <nav class="sidebar-nav" id="sidebarNav">
        $studentNav
        $instructorNav
        $adminNav
    </nav>
    <div class="sidebar-footer">
        <a href="{$url}/api/auth.php?action=logout">
            <i class="bi bi-box-arrow-left"></i>Sign Out
        </a>
    </div>
</aside>
HTML;
}

function renderTopbar(string $title): void {
    $url = APP_URL;
    $u   = currentUser();
    $init = strtoupper(mb_substr($u['name'], 0, 1));
    echo <<<HTML
<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar-title">$title</span>
    </div>
    <div class="topbar-right">
        <div class="topbar-avatar" title="{$u['name']}">{$init}</div>
    </div>
</header>
HTML;
}

function renderScripts(string $extra = ''): void {
    $url = APP_URL;
    echo <<<HTML
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{$url}/assets/js/nexprep.js"></script>
$extra
</body></html>
HTML;
}

function renderPage(string $title, string $pageTitle, string $content, string $extra = ''): void {
    renderHead($title);
    echo '<body><div class="app-layout">';
    renderSidebar();
    echo '<div class="main-content">';
    renderTopbar($pageTitle);
    echo '<div class="page-content fade-up">'.$content.'</div>';
    echo '</div></div>';
    renderScripts($extra);
}

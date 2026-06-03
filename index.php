<?php
require_once __DIR__.'/includes/config.php';
startSecureSession();
if (isLoggedIn()) {
    $dest = match($_SESSION['role'] ?? '') {
        'admin'      => '/admin/dashboard.php',
        'instructor' => '/instructor/dashboard.php',
        default      => '/student/dashboard.php',
    };
    redirect(APP_URL.$dest);
}
$page = $_GET['page'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $page==='register'?'Create Account':'Sign In' ?> | NexPrep</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/nexprep.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
  <!-- Left branding -->
  <div class="auth-left">
    <div class="auth-brand">
      <div class="logo">⚡</div>
      <h1>NexPrep</h1>
      <p>Pakistan's #1 Engineering Entrance Exam Prep Platform</p>
    </div>
    <div class="auth-features">
      <?php foreach ([
        ['⚡','Physics','bi-lightning-charge-fill'],
        ['🧪','Chemistry','bi-flask-fill'],
        ['🔢','Mathematics','bi-calculator-fill'],
        ['📖','English','bi-book-half'],
      ] as [$em,$label,$icon]): ?>
      <div class="auth-feature">
        <div class="auth-feature-icon"><i class="bi <?= $icon ?>"></i></div>
        <div>
          <strong><?= $label ?></strong>
          <div style="font-size:0.82rem;color:rgba(255,255,255,0.55);">MUET · NED · ECAT · GIKI · NUST</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:2.5rem;padding:1rem;background:rgba(255,255,255,0.08);border-radius:10px;font-size:0.82rem;color:rgba(255,255,255,0.6);">
      <strong style="color:rgba(255,255,255,0.85);">Demo credentials</strong><br>
      Admin: <code style="color:#a5f3fc;">admin@nexprep.pk</code> / <code style="color:#a5f3fc;">password</code><br>
      Instructor: <code style="color:#a5f3fc;">sara@nexprep.pk</code> / <code style="color:#a5f3fc;">password</code><br>
      Student: <code style="color:#a5f3fc;">ahmed@student.com</code> / <code style="color:#a5f3fc;">password</code>
    </div>
  </div>

  <!-- Right form -->
  <div class="auth-right">
    <?php if ($page === 'login'): ?>
    <div class="auth-form-title">Welcome back 👋</div>
    <div class="auth-form-sub">Sign in to continue your exam prep journey</div>
    <div id="alertBox" class="mb-3"></div>
    <div class="mb-3">
      <label class="form-label">Email Address</label>
      <input type="email" class="form-control" id="email" placeholder="you@example.com" autocomplete="email">
    </div>
    <div class="mb-4">
      <label class="form-label">Password</label>
      <div class="input-group">
        <input type="password" class="form-control" id="password" placeholder="Enter your password" autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()">
        <button class="input-group-text" type="button" onclick="togglePwd(this,'password')" style="cursor:pointer;border:1.5px solid #e2e8f0;border-left:none;">
          <i class="bi bi-eye"></i>
        </button>
      </div>
    </div>
    <button class="btn btn-primary w-100 btn-lg" id="loginBtn" onclick="doLogin()">
      <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
    </button>
    <p class="text-center mt-3" style="color:#64748b;font-size:0.875rem;">
      New to NexPrep? <a href="?page=register" style="color:#4f46e5;font-weight:600;">Create free account</a>
    </p>

    <?php else: ?>
    <div class="auth-form-title">Create Account</div>
    <div class="auth-form-sub">Join thousands of engineering students preparing smarter</div>
    <div id="alertBox" class="mb-3"></div>
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" id="regName" placeholder="Muhammad Ahmed">
      </div>
      <div class="col-12">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="regEmail" placeholder="ahmed@example.com">
      </div>
      <div class="col-6">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" id="regPass" placeholder="Min 6 chars">
      </div>
      <div class="col-6">
        <label class="form-label">Confirm</label>
        <input type="password" class="form-control" id="regConfirm" placeholder="Repeat">
      </div>
      <div class="col-6">
        <label class="form-label">Target Exam</label>
        <select class="form-select" id="regTarget">
          <option value="">Select...</option>
          <?php foreach (['MUET','NED','ECAT','GIKI','NUST','Other'] as $e): ?>
          <option><?= $e ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6">
        <label class="form-label">City</label>
        <input type="text" class="form-control" id="regCity" placeholder="Karachi">
      </div>
      <div class="col-12 mt-1">
        <button class="btn btn-primary w-100 btn-lg" id="regBtn" onclick="doRegister()">
          <i class="bi bi-person-plus-fill me-2"></i>Create Account
        </button>
      </div>
    </div>
    <p class="text-center mt-3" style="color:#64748b;font-size:0.875rem;">
      Already have an account? <a href="?page=login" style="color:#4f46e5;font-weight:600;">Sign in</a>
    </p>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API = '<?= APP_URL ?>/api';

function togglePwd(btn, id) {
  const inp = document.getElementById(id);
  const ico = btn.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text';     ico.className = 'bi bi-eye-slash'; }
  else                         { inp.type = 'password'; ico.className = 'bi bi-eye'; }
}

function showAlert(type, msg) {
  const box = document.getElementById('alertBox');
  const cls = type==='success' ? 'alert-success-np' : 'alert-danger-np';
  const ico = type==='success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
  box.innerHTML = `<div class="${cls}"><i class="bi ${ico}"></i>${msg}</div>`;
}

async function doLogin() {
  const btn   = document.getElementById('loginBtn');
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  if (!email || !pass) { showAlert('error', 'Please enter your email and password.'); return; }
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in…';
  try {
    const fd = new FormData();
    fd.append('action','login'); fd.append('email',email); fd.append('password',pass);
    const r = await fetch(`${API}/auth.php`, { method:'POST', body:fd });
    const d = await r.json();
    if (d.success) {
      showAlert('success', 'Login successful! Redirecting…');
      window.location.href = d.redirect;
    } else {
      showAlert('error', d.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Sign In';
    }
  } catch(e) {
    showAlert('error', 'Connection error. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Sign In';
  }
}

async function doRegister() {
  const btn = document.getElementById('regBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating…';
  const fd = new FormData();
  fd.append('action','register');
  fd.append('name',    document.getElementById('regName').value.trim());
  fd.append('email',   document.getElementById('regEmail').value.trim());
  fd.append('password',document.getElementById('regPass').value);
  fd.append('confirm', document.getElementById('regConfirm').value);
  fd.append('target_exam', document.getElementById('regTarget').value);
  fd.append('city',    document.getElementById('regCity').value.trim());
  try {
    const r = await fetch(`${API}/auth.php`, { method:'POST', body:fd });
    const d = await r.json();
    if (d.success) {
      showAlert('success', d.message + ' Redirecting to login…');
      setTimeout(() => window.location.href = '?page=login', 1500);
    } else {
      showAlert('error', d.message);
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Create Account';
    }
  } catch(e) {
    showAlert('error','Connection error.'); btn.disabled=false;
    btn.innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Create Account';
  }
}
</script>
</body></html>

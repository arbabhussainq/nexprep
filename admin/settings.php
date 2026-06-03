<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db=getDB(); $u=currentUser();
$stmt=$db->prepare("SELECT * FROM users WHERE id=?");$stmt->execute([$u['id']]);$admin=$stmt->fetch();
renderHead('Settings');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Settings'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2>Settings</h2><p>Admin account and system configuration.</p></div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card">
    <div class="card-header-np"><div class="card-title-np"><i class="bi bi-person-fill text-primary-color"></i>Admin Profile</div></div>
    <div class="card-body">
      <div class="d-flex align-items-center gap-3 mb-3">
        <div class="user-ava" style="width:52px;height:52px;font-size:1.2rem;"><?= strtoupper(substr($admin['name'],0,1)) ?></div>
        <div><div style="font-weight:700;font-size:1rem;"><?= sanitize($admin['name']) ?></div><div style="font-size:0.82rem;color:var(--text-muted);"><?= sanitize($admin['email']) ?></div><span class="badge-active">Administrator</span></div>
      </div>
      <div id="pAlert" class="mb-3"></div>
      <div class="mb-3"><label class="form-label">Display Name</label><input type="text" class="form-control" id="aName" value="<?= sanitize($admin['name']) ?>"></div>
      <button class="btn btn-primary" onclick="saveName()"><i class="bi bi-save me-2"></i>Save Changes</button>
    </div>
  </div></div>

  <div class="col-lg-6"><div class="card">
    <div class="card-header-np"><div class="card-title-np"><i class="bi bi-lock-fill text-primary-color"></i>Change Password</div></div>
    <div class="card-body">
      <div id="pwAlert" class="mb-3"></div>
      <div class="mb-2"><label class="form-label">Current Password</label><input type="password" class="form-control" id="pwCur" placeholder="••••••••"></div>
      <div class="mb-2"><label class="form-label">New Password</label><input type="password" class="form-control" id="pwNew" placeholder="Min 6 characters"></div>
      <div class="mb-3"><label class="form-label">Confirm New</label><input type="password" class="form-control" id="pwConf" placeholder="Repeat"></div>
      <button class="btn btn-outline-primary" onclick="changePwd()"><i class="bi bi-key me-2"></i>Update Password</button>
    </div>
  </div></div>

  <div class="col-lg-6"><div class="card">
    <div class="card-header-np"><div class="card-title-np"><i class="bi bi-info-circle-fill text-primary-color"></i>System Info</div></div>
    <div class="card-body">
      <?php foreach([['App','NexPrep v'.APP_VERSION],['PHP',phpversion()],['Database',DB_NAME],['Session Timeout',SESSION_TIMEOUT/60 .' min'],['Points / Correct',POINTS_CORRECT],['Points / Wrong',POINTS_WRONG],['Time Bonus',POINTS_TIME_BONUS.' max']] as [$k,$v]): ?>
      <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);font-size:0.875rem;">
        <span style="color:var(--text-secondary);"><?= $k ?></span>
        <code style="color:var(--primary);font-size:0.82rem;"><?= htmlspecialchars($v) ?></code>
      </div>
      <?php endforeach; ?>
    </div>
  </div></div>

  <div class="col-lg-6"><div class="card" style="border-color:#fca5a5;">
    <div class="card-header-np" style="background:#fff5f5;"><div class="card-title-np" style="color:var(--danger);"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</div></div>
    <div class="card-body">
      <div class="mb-3 p-3" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;">
        <div style="font-weight:600;font-size:0.875rem;margin-bottom:0.25rem;">Reset Leaderboard</div>
        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.75rem;">Clears all leaderboard entries and rank points. Cannot be undone.</div>
        <button class="btn btn-sm btn-danger-soft" onclick="danger('reset_leaderboard')"><i class="bi bi-trophy me-1"></i>Reset</button>
      </div>
      <div class="p-3" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;">
        <div style="font-weight:600;font-size:0.875rem;margin-bottom:0.25rem;">Clear All Attempts</div>
        <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.75rem;">Removes all test attempts and answers. Resets all student scores.</div>
        <button class="btn btn-sm btn-danger-soft" onclick="danger('clear_attempts')"><i class="bi bi-trash me-1"></i>Clear All</button>
      </div>
    </div>
  </div></div>
</div>
</div></div></div>
<?php renderScripts('<script>
const API="'.APP_URL.'/api";
async function saveName(){const fd=new FormData();fd.append("action","update_profile");fd.append("name",document.getElementById("aName").value.trim());fd.append("target_exam","");fd.append("city","");const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();document.getElementById("pAlert").innerHTML=`<div class="${d.success?"alert-success-np":"alert-danger-np"}"><i class="bi bi-${d.success?"check-circle-fill":"exclamation-triangle-fill"} me-2"></i>${d.message}</div>`;}
async function changePwd(){const fd=new FormData();fd.append("action","change_password");fd.append("current_password",document.getElementById("pwCur").value);fd.append("new_password",document.getElementById("pwNew").value);fd.append("confirm_password",document.getElementById("pwConf").value);const r=await fetch(`${API}/auth.php`,{method:"POST",body:fd});const d=await r.json();document.getElementById("pwAlert").innerHTML=`<div class="${d.success?"alert-success-np":"alert-danger-np"}"><i class="bi bi-${d.success?"check-circle-fill":"exclamation-triangle-fill"} me-2"></i>${d.message}</div>`;}
async function danger(action){if(!confirm("Are you sure? This cannot be undone."))return;if(!confirm("Double confirm — proceed?"))return;const fd=new FormData();fd.append("action",action);const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();alert(d.message);}
</script>'); ?>

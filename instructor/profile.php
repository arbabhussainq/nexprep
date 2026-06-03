<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('instructor');
$db=getDB(); $uid=currentUser()['id'];
$stmt=$db->prepare("SELECT * FROM users WHERE id=?"); $stmt->execute([$uid]); $user=$stmt->fetch();
renderHead('Profile');
?>
<body><div class="app-layout"><?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Profile'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2>My Profile</h2></div>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="card"><div class="card-header-np"><div class="card-title-np"><i class="bi bi-person-fill text-primary-color"></i>Account Details</div></div>
    <div class="card-body">
      <div id="pAlert" class="mb-3"></div>
      <div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" id="pName" value="<?= sanitize($user['name']) ?>"></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled></div>
      <button class="btn btn-primary" onclick="saveName()"><i class="bi bi-save me-2"></i>Save</button>
    </div></div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-header-np"><div class="card-title-np"><i class="bi bi-lock-fill text-primary-color"></i>Change Password</div></div>
    <div class="card-body">
      <div id="pwAlert" class="mb-3"></div>
      <div class="mb-2"><label class="form-label">Current Password</label><input type="password" class="form-control" id="pwCur" placeholder="••••••"></div>
      <div class="mb-2"><label class="form-label">New Password</label><input type="password" class="form-control" id="pwNew" placeholder="Min 6 chars"></div>
      <div class="mb-3"><label class="form-label">Confirm New</label><input type="password" class="form-control" id="pwConf" placeholder="Repeat"></div>
      <button class="btn btn-outline-primary" onclick="changePwd()"><i class="bi bi-key me-2"></i>Update</button>
    </div></div>
  </div>
</div>
</div></div></div>
<?php renderScripts('<script>
const API="'.APP_URL.'/api";
async function saveName(){const fd=new FormData();fd.append("action","update_profile");fd.append("name",document.getElementById("pName").value.trim());fd.append("target_exam","");fd.append("city","");const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();document.getElementById("pAlert").innerHTML=`<div class="${d.success?"alert-success-np":"alert-danger-np}"><i class="bi bi-${d.success?"check-circle-fill":"exclamation-triangle-fill"} me-2"></i>${d.message}</div>`;}
async function changePwd(){const fd=new FormData();fd.append("action","change_password");fd.append("current_password",document.getElementById("pwCur").value);fd.append("new_password",document.getElementById("pwNew").value);fd.append("confirm_password",document.getElementById("pwConf").value);const r=await fetch(`${API}/auth.php`,{method:"POST",body:fd});const d=await r.json();document.getElementById("pwAlert").innerHTML=`<div class="${d.success?"alert-success-np":"alert-danger-np}"><i class="bi bi-${d.success?"check-circle-fill":"exclamation-triangle-fill"} me-2"></i>${d.message}</div>`;}
</script>'); ?>

<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db=getDB();
$stmt=$db->query("SELECT u.id,u.name,u.email,u.status,u.created_at,(SELECT COUNT(*) FROM tests WHERE created_by=u.id) AS tests_cnt,(SELECT COUNT(*) FROM questions q JOIN tests t ON q.test_id=t.id WHERE t.created_by=u.id) AS mcq_cnt FROM users u WHERE u.role='instructor' ORDER BY u.created_at DESC");
$instructors=$stmt->fetchAll();
renderHead('Instructors');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Instructors'); ?>
<div class="page-content fade-up">
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
  <div class="page-header mb-0"><h2>Instructors</h2><p>Instructors create tests and add MCQs. Manage their accounts here.</p></div>
  <button class="btn btn-primary" onclick="openCreate()"><i class="bi bi-person-badge-fill me-2"></i>Add Instructor</button>
</div>
<div id="pageAlert" class="mb-3"></div>
<?php if(empty($instructors)): ?>
<div class="card"><div class="empty-state"><i class="bi bi-person-badge"></i><h5>No instructors yet</h5><p>Add instructors to create and manage tests.</p><button class="btn btn-primary mt-2" onclick="openCreate()">Add First Instructor</button></div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach($instructors as $i): ?>
<div class="col-md-6 col-xl-4">
  <div class="card h-100">
    <div class="card-body">
      <div class="d-flex align-items-start gap-3 mb-3">
        <div class="user-ava" style="width:46px;height:46px;font-size:1rem;"><?= strtoupper(substr($i['name'],0,1)) ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:0.95rem;"><?= sanitize($i['name']) ?></div>
          <div style="font-size:0.78rem;color:var(--text-muted);"><?= sanitize($i['email']) ?></div>
          <span class="badge-<?= $i['status']==='active'?'active':'inactive' ?>" style="margin-top:3px;display:inline-block;"><?= ucfirst($i['status']) ?></span>
        </div>
      </div>
      <div class="row g-2 text-center mb-3">
        <div class="col-6" style="padding:0.75rem;background:#f8fafc;border-radius:8px;">
          <div style="font-size:1.5rem;font-weight:700;color:var(--primary);"><?= $i['tests_cnt'] ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;">Tests</div>
        </div>
        <div class="col-6" style="padding:0.75rem;background:#f8fafc;border-radius:8px;">
          <div style="font-size:1.5rem;font-weight:700;color:var(--success);"><?= $i['mcq_cnt'] ?></div>
          <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;">MCQs</div>
        </div>
      </div>
      <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:0.75rem;">Joined <?= date('d M Y',strtotime($i['created_at'])) ?></div>
      <div class="d-flex gap-2">
        <?php if($i['status']==='active'): ?>
        <button class="btn btn-sm btn-warning-soft flex-grow-1" onclick="setStatus(<?= $i['id'] ?>,'suspended')"><i class="bi bi-slash-circle me-1"></i>Suspend</button>
        <?php else: ?>
        <button class="btn btn-sm btn-success-soft flex-grow-1" onclick="setStatus(<?= $i['id'] ?>,'active')"><i class="bi bi-check-circle me-1"></i>Activate</button>
        <?php endif; ?>
        <button class="btn btn-sm btn-light-primary" onclick="resetPwd(<?= $i['id'] ?>,'<?= addslashes(sanitize($i['name'])) ?>')" title="Reset Password"><i class="bi bi-key"></i></button>
        <button class="btn btn-sm btn-danger-soft" onclick="deleteInst(<?= $i['id'] ?>,'<?= addslashes(sanitize($i['name'])) ?>')" title="Delete"><i class="bi bi-trash"></i></button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></div></div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-badge-fill me-2 text-primary-color"></i>Add Instructor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div id="cAlert" class="mb-3"></div>
    <div class="row g-3">
      <div class="col-12"><label class="form-label">Full Name</label><input type="text" class="form-control" id="iName" placeholder="Instructor name"></div>
      <div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" id="iEmail" placeholder="instructor@nexprep.pk"></div>
      <div class="col-12"><label class="form-label">Password</label><input type="password" class="form-control" id="iPwd" placeholder="Temporary password"></div>
    </div>
    <div style="margin-top:0.75rem;padding:0.65rem 0.85rem;background:var(--info-bg);border:1px solid #bae6fd;border-radius:8px;font-size:0.8rem;color:var(--info);">
      <i class="bi bi-info-circle-fill me-1"></i>Instructors can create tests and add MCQs but cannot manage users or settings.
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="createInstructor()"><i class="bi bi-person-badge-fill me-2"></i>Add</button></div>
</div></div></div>

<!-- Reset Pwd Modal -->
<div class="modal fade" id="pwdModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-key me-2 text-primary-color"></i>Reset Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" id="pUid"><p id="pName" style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:0.75rem;"></p>
    <div id="pAlert" class="mb-2"></div>
    <label class="form-label">New Password</label><input type="password" class="form-control" id="pPwd" placeholder="Min 6 chars">
  </div>
  <div class="modal-footer"><button class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="doReset()"><i class="bi bi-key me-2"></i>Reset</button></div>
</div></div></div>

<?php renderScripts('<script>
const API="'.APP_URL.'/api";let cModal,pModal;
document.addEventListener("DOMContentLoaded",()=>{cModal=new bootstrap.Modal(document.getElementById("createModal"));pModal=new bootstrap.Modal(document.getElementById("pwdModal"));});
function openCreate(){cModal.show();}
async function createInstructor(){
  const fd=new FormData();fd.append("action","create");fd.append("name",document.getElementById("iName").value.trim());fd.append("email",document.getElementById("iEmail").value.trim());fd.append("password",document.getElementById("iPwd").value);fd.append("role","instructor");
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){cModal.hide();document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>Instructor added!</div>`;setTimeout(()=>location.reload(),700);}
  else document.getElementById("cAlert").innerHTML=`<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>${d.message}</div>`;
}
async function setStatus(id,status){
  const fd=new FormData();fd.append("action","set_status");fd.append("id",id);fd.append("status",status);
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>${d.message}</div>`;setTimeout(()=>location.reload(),600);}else alert(d.message);
}
function resetPwd(id,name){document.getElementById("pUid").value=id;document.getElementById("pName").textContent="Instructor: "+name;document.getElementById("pPwd").value="";document.getElementById("pAlert").innerHTML="";pModal.show();}
async function doReset(){
  const pwd=document.getElementById("pPwd").value;if(!pwd||pwd.length<6){document.getElementById("pAlert").innerHTML=`<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>Min 6 chars.</div>`;return;}
  const fd=new FormData();fd.append("action","admin_reset_password");fd.append("id",document.getElementById("pUid").value);fd.append("password",pwd);
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){pModal.hide();document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>Password reset.</div>`;}
  else document.getElementById("pAlert").innerHTML=`<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>${d.message}</div>`;
}
async function deleteInst(id,name){
  if(!confirm(`Delete instructor "${name}"? Their tests remain but they lose access.`))return;
  const fd=new FormData();fd.append("action","delete");fd.append("id",id);
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>Instructor removed.</div>`;setTimeout(()=>location.reload(),600);}else alert(d.message);
}
</script>'); ?>

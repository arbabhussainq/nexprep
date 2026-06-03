<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db=getDB(); $me=currentUser();
$search=trim($_GET['search']??''); $role=$_GET['role']??'';
$sql="SELECT u.id,u.name,u.email,u.role,u.status,u.created_at,sp.target_exam,sp.total_tests_taken,sp.rank_points FROM users u LEFT JOIN student_profiles sp ON u.id=sp.user_id WHERE 1=1";
$p=[];
if($role){$sql.=" AND u.role=?";$p[]=$role;}
if($search){$sql.=" AND (u.name LIKE ? OR u.email LIKE ?)";$p[]="%$search%";$p[]="%$search%";}
$sql.=" ORDER BY u.created_at DESC";
$stmt=$db->prepare($sql);$stmt->execute($p);$users=$stmt->fetchAll();
$counts=['all'=>$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),'student'=>$db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),'instructor'=>$db->query("SELECT COUNT(*) FROM users WHERE role='instructor'")->fetchColumn(),'admin'=>$db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()];
renderHead('Manage Users');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('User Management'); ?>
<div class="page-content fade-up">
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
  <div class="page-header mb-0"><h2>All Users</h2><p>Manage student, instructor and admin accounts.</p></div>
  <button class="btn btn-primary" onclick="openCreate()"><i class="bi bi-person-plus-fill me-2"></i>Create User</button>
</div>

<!-- Filter tabs -->
<div class="d-flex gap-2 flex-wrap mb-4">
  <?php foreach([['All Users','','','bi-people'],['Students','student','student','bi-mortarboard'],['Instructors','instructor','instructor','bi-person-badge'],['Admins','admin','admin','bi-shield']] as [$lbl,$rval,$rkey,$icon]): ?>
  <a href="?role=<?= $rval ?>" class="btn btn-sm <?= $role===$rval?'btn-primary':'btn-outline-primary' ?>">
    <i class="bi <?= $icon ?> me-1"></i><?= $lbl ?> <span style="opacity:0.7;">(<?= $rkey?$counts[$rkey]:$counts['all'] ?>)</span>
  </a>
  <?php endforeach; ?>
  <form method="GET" class="d-flex gap-2 ms-auto">
    <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
    <input type="text" class="form-control form-control-sm" name="search" value="<?= sanitize($search) ?>" placeholder="Search name or email…" style="width:200px;">
    <button type="submit" class="btn btn-sm btn-light-primary"><i class="bi bi-search"></i></button>
    <?php if($search): ?><a href="?role=<?= $role ?>" class="btn btn-sm btn-outline-primary">Clear</a><?php endif; ?>
  </form>
</div>

<div id="pageAlert" class="mb-3"></div>

<?php if(empty($users)): ?>
<div class="card"><div class="empty-state"><i class="bi bi-people"></i><h5>No users found</h5></div></div>
<?php else: ?>
<div class="card"><div class="table-responsive"><table class="np-table">
  <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Target</th><th>Tests</th><th>Points</th><th>Joined</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($users as $u): $isMe=$u['id']==$me['id']; ?>
  <tr>
    <td>
      <div class="d-flex align-items-center gap-2">
        <div class="user-ava" style="width:32px;height:32px;font-size:0.75rem;"><?= strtoupper(substr($u['name'],0,1)) ?></div>
        <div><div style="font-weight:600;font-size:0.85rem;"><?= sanitize($u['name']) ?><?= $isMe?' <span style="color:var(--primary);font-size:0.7rem;">(You)</span>':'' ?></div><div style="font-size:0.72rem;color:var(--text-muted);"><?= sanitize($u['email']) ?></div></div>
      </div>
    </td>
    <td>
      <span style="font-size:0.75rem;font-weight:600;padding:2px 8px;border-radius:4px;background:<?= $u['role']==='admin'?'#fef3c7':($u['role']==='instructor'?'#e0f2fe':'var(--primary-light)') ?>;color:<?= $u['role']==='admin'?'#92400e':($u['role']==='instructor'?'#075985':'var(--primary)') ?>;"><?= ucfirst($u['role']) ?></span>
    </td>
    <td><span class="badge-<?= $u['status']==='active'?'active':'inactive' ?>"><?= ucfirst($u['status']) ?></span></td>
    <td style="color:var(--text-muted);font-size:0.82rem;"><?= sanitize($u['target_exam']??'—') ?></td>
    <td><?= $u['total_tests_taken']??0 ?></td>
    <td style="font-weight:600;color:var(--primary);"><?= $u['rank_points']??0 ?></td>
    <td style="color:var(--text-muted);font-size:0.78rem;"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
    <td>
      <?php if(!$isMe): ?>
      <div class="d-flex gap-1">
        <?php if($u['status']==='active'): ?>
        <button class="btn btn-sm btn-warning-soft" onclick="setStatus(<?= $u['id'] ?>,'suspended')" title="Suspend"><i class="bi bi-slash-circle"></i></button>
        <?php else: ?>
        <button class="btn btn-sm btn-success-soft" onclick="setStatus(<?= $u['id'] ?>,'active')" title="Activate"><i class="bi bi-check-circle"></i></button>
        <?php endif; ?>
        <button class="btn btn-sm btn-light-primary" onclick="openRole(<?= $u['id'] ?>,'<?= $u['role'] ?>','<?= addslashes(sanitize($u['name'])) ?>')" title="Change Role"><i class="bi bi-person-gear"></i></button>
        <button class="btn btn-sm btn-danger-soft" onclick="deleteUser(<?= $u['id'] ?>,'<?= addslashes(sanitize($u['name'])) ?>')" title="Delete"><i class="bi bi-trash"></i></button>
      </div>
      <?php else: echo '<span style="color:var(--text-muted);font-size:0.75rem;">—</span>'; endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
</div></div></div>

<!-- Create User Modal -->
<div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus-fill me-2 text-primary-color"></i>Create User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><div id="cAlert" class="mb-3"></div>
    <div class="row g-3">
      <div class="col-12"><label class="form-label">Full Name</label><input type="text" class="form-control" id="cName" placeholder="Full name"></div>
      <div class="col-12"><label class="form-label">Email</label><input type="email" class="form-control" id="cEmail" placeholder="email@example.com"></div>
      <div class="col-md-6"><label class="form-label">Password</label><input type="password" class="form-control" id="cPwd" placeholder="Min 6 chars"></div>
      <div class="col-md-6"><label class="form-label">Role</label>
        <select class="form-select" id="cRole"><option value="student">Student</option><option value="instructor">Instructor</option><option value="admin">Admin</option></select>
      </div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="createUser()"><i class="bi bi-person-plus me-2"></i>Create</button></div>
</div></div></div>

<!-- Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Change Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" id="rUid"><p id="rName" style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:0.75rem;"></p>
    <label class="form-label">New Role</label>
    <select class="form-select" id="rRole"><option value="student">Student</option><option value="instructor">Instructor</option><option value="admin">Admin</option></select>
  </div>
  <div class="modal-footer"><button class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="changeRole()">Update</button></div>
</div></div></div>

<?php renderScripts('<script>
const API="'.APP_URL.'/api"; let cModal,rModal;
document.addEventListener("DOMContentLoaded",()=>{cModal=new bootstrap.Modal(document.getElementById("createModal"));rModal=new bootstrap.Modal(document.getElementById("roleModal"));});
function openCreate(){cModal.show();}
async function createUser(){
  const fd=new FormData();fd.append("action","create");fd.append("name",document.getElementById("cName").value.trim());fd.append("email",document.getElementById("cEmail").value.trim());fd.append("password",document.getElementById("cPwd").value);fd.append("role",document.getElementById("cRole").value);
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){cModal.hide();document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>${d.message}</div>`;setTimeout(()=>location.reload(),700);}
  else document.getElementById("cAlert").innerHTML=`<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>${d.message}</div>`;
}
async function setStatus(id,status){
  if(!confirm(status==="suspended"?"Suspend this user?":"Activate this user?"))return;
  const fd=new FormData();fd.append("action","set_status");fd.append("id",id);fd.append("status",status);
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>${d.message}</div>`;setTimeout(()=>location.reload(),600);}else alert(d.message);
}
function openRole(id,role,name){document.getElementById("rUid").value=id;document.getElementById("rName").textContent="User: "+name;document.getElementById("rRole").value=role;rModal.show();}
async function changeRole(){
  const fd=new FormData();fd.append("action","set_role");fd.append("id",document.getElementById("rUid").value);fd.append("role",document.getElementById("rRole").value);
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){rModal.hide();document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>${d.message}</div>`;setTimeout(()=>location.reload(),600);}else alert(d.message);
}
async function deleteUser(id,name){
  if(!confirm(`Permanently delete "${name}"? All their data will be removed.`))return;
  const fd=new FormData();fd.append("action","delete");fd.append("id",id);
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>User deleted.</div>`;setTimeout(()=>location.reload(),600);}else alert(d.message);
}
</script>'); ?>

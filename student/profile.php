<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db=getDB(); $uid=currentUser()['id'];
$stmt=$db->prepare("SELECT u.*,sp.target_exam,sp.city,sp.total_tests_taken,sp.total_score,sp.rank_points FROM users u LEFT JOIN student_profiles sp ON u.id=sp.user_id WHERE u.id=?");
$stmt->execute([$uid]); $profile=$stmt->fetch();
$subjStats=$db->prepare("SELECT t.subject,COUNT(*) AS cnt,ROUND(AVG(ta.percentage),1) AS avg_pct FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.user_id=? AND ta.status='completed' GROUP BY t.subject");
$subjStats->execute([$uid]); $subjects=$subjStats->fetchAll();
$init=strtoupper(substr($profile['name'],0,1));
renderHead('My Profile');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('My Profile'); ?>
<div class="page-content fade-up">
<div class="row g-3">

  <!-- Profile card -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-body text-center">
        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#818cf8,#4f46e5);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.6rem;color:white;margin:0 auto 1rem;border:4px solid var(--primary-light);"><?= $init ?></div>
        <div style="font-weight:700;font-size:1.1rem;"><?= sanitize($profile['name']) ?></div>
        <div style="color:var(--text-muted);font-size:0.82rem;margin-bottom:0.5rem;"><?= sanitize($profile['email']) ?></div>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
          <?php if($profile['target_exam']): ?><span class="badge-pill"><?= sanitize($profile['target_exam']) ?></span><?php endif; ?>
          <?php if($profile['city']): ?><span style="color:var(--text-muted);font-size:0.82rem;"><i class="bi bi-geo-alt me-1"></i><?= sanitize($profile['city']) ?></span><?php endif; ?>
        </div>
        <hr style="border-color:var(--border);margin:1rem 0;">
        <div class="row g-2 text-center">
          <?php foreach([[$profile['total_tests_taken'],'Tests','var(--primary)'],[$profile['rank_points'],'Points','var(--success)'],[$profile['total_score'],'Score','var(--warning)']] as [$v,$l,$c]): ?>
          <div class="col-4">
            <div style="font-weight:700;font-size:1.3rem;color:<?= $c ?>;"><?= $v ?></div>
            <div style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;"><?= $l ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <hr style="border-color:var(--border);margin:1rem 0;">
        <div style="font-size:0.75rem;color:var(--text-muted);">Member since <?= date('M Y',strtotime($profile['created_at'])) ?></div>
      </div>
    </div>

    <!-- Subject performance -->
    <?php if(!empty($subjects)): ?>
    <div class="card">
      <div class="card-header-np"><div class="card-title-np"><i class="bi bi-bar-chart-fill text-primary-color"></i>Subject Performance</div></div>
      <div class="card-body">
        <?php $colors=['Physics'=>'#7c3aed','Chemistry'=>'#059669','Mathematics'=>'#d97706','English'=>'#0284c7'];
        foreach($subjects as $s): $c=$colors[$s['subject']]??'#4f46e5'; ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1 align-items-center">
            <span class="<?= subjectBadge($s['subject']) ?>"><?= $s['subject'] ?></span>
            <span style="font-size:0.8rem;font-weight:700;color:<?= $c ?>;"><?= $s['avg_pct'] ?>%</span>
          </div>
          <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $s['avg_pct'] ?>%;background:<?= $c ?>;"></div></div>
          <div style="font-size:0.7rem;color:var(--text-muted);margin-top:0.2rem;"><?= $s['cnt'] ?> test<?= $s['cnt']!=1?'s':'' ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Edit forms -->
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header-np"><div class="card-title-np"><i class="bi bi-pencil-fill text-primary-color"></i>Edit Profile</div></div>
      <div class="card-body">
        <div id="pAlert" class="mb-3"></div>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" class="form-control" id="eName" value="<?= sanitize($profile['name']) ?>"></div>
          <div class="col-md-6"><label class="form-label">Email <span style="color:var(--text-muted);font-weight:400;">(read-only)</span></label><input type="email" class="form-control" value="<?= sanitize($profile['email']) ?>" disabled></div>
          <div class="col-md-6"><label class="form-label">Target Exam</label>
            <select class="form-select" id="eTarget">
              <option value="">Select...</option>
              <?php foreach(['MUET','NED','ECAT','GIKI','NUST','Other'] as $ex): ?>
              <option value="<?= $ex ?>" <?= $profile['target_exam']===$ex?'selected':'' ?>><?= $ex ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">City</label><input type="text" class="form-control" id="eCity" value="<?= sanitize($profile['city']??'') ?>" placeholder="Your city"></div>
          <div class="col-12"><button class="btn btn-primary" onclick="saveProfile()"><i class="bi bi-save me-2"></i>Save Changes</button></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header-np"><div class="card-title-np"><i class="bi bi-lock-fill text-primary-color"></i>Change Password</div></div>
      <div class="card-body">
        <div id="pwAlert" class="mb-3"></div>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Current</label><input type="password" class="form-control" id="pwCur" placeholder="••••••"></div>
          <div class="col-md-4"><label class="form-label">New Password</label><input type="password" class="form-control" id="pwNew" placeholder="Min 6 chars"></div>
          <div class="col-md-4"><label class="form-label">Confirm New</label><input type="password" class="form-control" id="pwConf" placeholder="Repeat"></div>
          <div class="col-12"><button class="btn btn-outline-primary" onclick="changePwd()"><i class="bi bi-key me-2"></i>Update Password</button></div>
        </div>
      </div>
    </div>
  </div>

</div>
</div></div></div>
<?php renderScripts('<script>
const API="'.APP_URL.'/api";
async function saveProfile(){
  const fd=new FormData();fd.append("action","update_profile");fd.append("name",document.getElementById("eName").value.trim());fd.append("target_exam",document.getElementById("eTarget").value);fd.append("city",document.getElementById("eCity").value.trim());
  const r=await fetch(`${API}/users.php`,{method:"POST",body:fd});const d=await r.json();
  document.getElementById("pAlert").innerHTML=`<div class="${d.success?"alert-success-np":"alert-danger-np"}"><i class="bi bi-${d.success?"check-circle-fill":"exclamation-triangle-fill"} me-2"></i>${d.message}</div>`;
  if(d.success)setTimeout(()=>location.reload(),800);
}
async function changePwd(){
  const fd=new FormData();fd.append("action","change_password");fd.append("current_password",document.getElementById("pwCur").value);fd.append("new_password",document.getElementById("pwNew").value);fd.append("confirm_password",document.getElementById("pwConf").value);
  const r=await fetch(`${API}/auth.php`,{method:"POST",body:fd});const d=await r.json();
  document.getElementById("pwAlert").innerHTML=`<div class="${d.success?"alert-success-np":"alert-danger-np"}"><i class="bi bi-${d.success?"check-circle-fill":"exclamation-triangle-fill"} me-2"></i>${d.message}</div>`;
  if(d.success){document.getElementById("pwCur").value="";document.getElementById("pwNew").value="";document.getElementById("pwConf").value="";}
}
</script>'); ?>

<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db=getDB(); $subject=$_GET['subject']??''; $exam=$_GET['exam']??'';
$sql="SELECT t.*,u.name AS author,(SELECT COUNT(*) FROM questions WHERE test_id=t.id) AS q_count FROM tests t JOIN users u ON t.created_by=u.id WHERE t.is_active='yes'";
$p=[];if($subject){$sql.=" AND t.subject=?";$p[]=$subject;}if($exam){$sql.=" AND t.exam_type=?";$p[]=$exam;}
$sql.=" ORDER BY t.created_at DESC";$st=$db->prepare($sql);$st->execute($p);$tests=$st->fetchAll();
$exams=$db->query("SELECT DISTINCT exam_type FROM tests WHERE is_active='yes' ORDER BY exam_type")->fetchAll(PDO::FETCH_COLUMN);
renderHead('Take Tests');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Available Tests'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2>Available Tests</h2><p>Choose a subject or exam type and start practising.</p></div>

<!-- Filters -->
<div class="card mb-4" style="padding:1rem;">
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <a href="<?= APP_URL ?>/student/tests.php" class="btn btn-sm <?= !$subject&&!$exam?'btn-primary':'btn-outline-primary' ?>">All</a>
    <?php foreach(['Physics'=>'#7c3aed','Chemistry'=>'#059669','Mathematics'=>'#d97706','English'=>'#0284c7'] as $s=>$c): ?>
    <a href="?subject=<?= urlencode($s) ?>" class="btn btn-sm <?= $subject===$s?'btn-primary':'btn-outline-primary' ?>"><i class="bi <?= subjectIcon($s) ?> me-1"></i><?= $s ?></a>
    <?php endforeach; ?>
    <select class="form-select form-select-sm ms-auto" style="width:auto;min-width:130px;" onchange="location.href='?exam='+this.value+'<?= $subject?'&subject='.urlencode($subject):'' ?>'">
      <option value="">All Exams</option>
      <?php foreach($exams as $e): ?><option value="<?= htmlspecialchars($e) ?>" <?= $exam===$e?'selected':'' ?>><?= htmlspecialchars($e) ?></option><?php endforeach; ?>
    </select>
  </div>
</div>

<?php if(empty($tests)): ?>
<div class="card"><div class="empty-state"><i class="bi bi-journal-x"></i><h5>No tests found</h5><p>Try a different filter.</p></div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach($tests as $t): $scolor=subjectColor($t['subject']); ?>
<div class="col-md-6 col-xl-4">
  <div class="test-card" onclick="openModal(<?= $t['id'] ?>,'<?= addslashes(sanitize($t['title'])) ?>','<?= $t['subject'] ?>','<?= sanitize($t['exam_type']) ?>',<?= $t['time_limit'] ?>,<?= $t['q_count'] ?>,'<?= $t['difficulty'] ?>','<?= addslashes(sanitize($t['description']??'')) ?>')">
    <div class="d-flex justify-content-between align-items-start mb-2">
      <i class="bi <?= subjectIcon($t['subject']) ?> tc-icon" style="color:<?= $scolor ?>;"></i>
      <div class="d-flex gap-1 flex-wrap justify-content-end">
        <span class="<?= subjectBadge($t['subject']) ?>"><?= $t['subject'] ?></span>
        <span class="badge-pill"><?= sanitize($t['exam_type']) ?></span>
      </div>
    </div>
    <div class="tc-title"><?= sanitize($t['title']) ?></div>
    <?php if($t['description']): ?><div class="tc-desc"><?= sanitize(substr($t['description'],0,80)) ?>…</div><?php endif; ?>
    <div class="tc-meta">
      <span class="tc-meta-item"><i class="bi bi-question-circle"></i><?= $t['q_count'] ?> MCQs</span>
      <span class="tc-meta-item"><i class="bi bi-clock"></i><?= $t['time_limit'] ?> min</span>
      <span class="diff-<?= strtolower($t['difficulty']) ?>"><?= $t['difficulty'] ?></span>
    </div>
    <button class="btn btn-primary w-100 mt-3" style="font-size:0.82rem;"><i class="bi bi-play-fill me-1"></i>Start Test</button>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></div></div>

<!-- Start modal -->
<div class="modal fade" id="startModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="mTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="row g-2 mb-3">
      <div class="col-6"><div style="text-align:center;padding:0.875rem;background:#f8fafc;border-radius:10px;border:1px solid var(--border);"><div style="font-size:1.6rem;font-weight:700;color:var(--primary);" id="mQ">—</div><div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;">MCQs</div></div></div>
      <div class="col-6"><div style="text-align:center;padding:0.875rem;background:#f8fafc;border-radius:10px;border:1px solid var(--border);"><div style="font-size:1.6rem;font-weight:700;color:var(--warning);" id="mT">—</div><div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;">Minutes</div></div></div>
    </div>
    <p id="mDesc" style="color:var(--text-secondary);font-size:0.875rem;margin-bottom:0.75rem;"></p>
    <div style="background:var(--info-bg);border:1px solid #bae6fd;border-radius:8px;padding:0.65rem 0.875rem;font-size:0.8rem;color:var(--info);"><i class="bi bi-info-circle-fill me-1"></i>Correct = +4 pts. Timer starts immediately. Submit before time runs out!</div>
  </div>
  <div class="modal-footer"><button class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="startBtn"><i class="bi bi-play-fill me-2"></i>Begin Test</button></div>
</div></div></div>

<?php renderScripts('<script>
const API="'.APP_URL.'/api"; let _tid=null, modal;
document.addEventListener("DOMContentLoaded",()=>{modal=new bootstrap.Modal(document.getElementById("startModal"));});
function openModal(id,title,subj,exam,time,q,diff,desc){
  _tid=id;
  document.getElementById("mTitle").textContent=title;
  document.getElementById("mQ").textContent=q;
  document.getElementById("mT").textContent=time;
  document.getElementById("mDesc").textContent=desc||"";
  modal.show();
}
document.getElementById("startBtn").addEventListener("click",async()=>{
  if(!_tid)return;
  const btn=document.getElementById("startBtn");btn.disabled=true;btn.innerHTML="<span class=\"spinner-border spinner-border-sm me-2\"></span>Starting…";
  const fd=new FormData();fd.append("action","start_attempt");fd.append("test_id",_tid);
  const r=await fetch(`${API}/tests.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){window.location.href="'.APP_URL.'/student/take_test.php?attempt="+d.attempt_id+"&test="+_tid;}
  else{alert(d.message);btn.disabled=false;btn.innerHTML="<i class=\"bi bi-play-fill me-2\"></i>Begin Test";}
});
</script>'); ?>

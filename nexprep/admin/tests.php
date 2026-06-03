<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db = getDB();
$tests = $db->query("SELECT t.*,u.name AS author,(SELECT COUNT(*) FROM questions WHERE test_id=t.id) AS q_count FROM tests t JOIN users u ON t.created_by=u.id ORDER BY t.created_at DESC")->fetchAll();
renderHead('All Tests');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('All Tests'); ?>
<div class="page-content fade-up">
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
  <div class="page-header mb-0"><h2>All Tests</h2><p>View and manage all tests created by instructors.</p></div>
</div>
<div id="pageAlert" class="mb-3"></div>
<?php if(empty($tests)): ?>
<div class="card"><div class="empty-state"><i class="bi bi-journal-x"></i><h5>No tests yet</h5><p>Instructors haven't created any tests yet.</p></div></div>
<?php else: ?>
<div class="card"><div class="table-responsive"><table class="np-table">
  <thead><tr><th>Title</th><th>Subject</th><th>Exam</th><th>MCQs</th><th>Time</th><th>Diff</th><th>Status</th><th>Instructor</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($tests as $t): ?>
  <tr>
    <td style="font-weight:500;max-width:180px;"><?= sanitize($t['title']) ?></td>
    <td><span class="<?= subjectBadge($t['subject']) ?>"><i class="bi <?= subjectIcon($t['subject']) ?> me-1"></i><?= $t['subject'] ?></span></td>
    <td><span class="badge-pill"><?= sanitize($t['exam_type']) ?></span></td>
    <td><span style="font-weight:600;color:<?= $t['q_count']>=25?'var(--success)':($t['q_count']>0?'var(--warning)':'var(--danger)') ?>;"><?= $t['q_count'] ?>/25</span></td>
    <td><?= $t['time_limit'] ?>m</td>
    <td><span class="diff-<?= strtolower($t['difficulty']) ?>"><?= $t['difficulty'] ?></span></td>
    <td>
      <select class="form-select form-select-sm" style="width:100px;" onchange="toggleStatus(<?= $t['id'] ?>,this.value)">
        <option value="yes" <?= $t['is_active']==='yes'?'selected':'' ?>>Active</option>
        <option value="no"  <?= $t['is_active']==='no' ?'selected':'' ?>>Inactive</option>
      </select>
    </td>
    <td style="color:var(--text-muted);font-size:0.8rem;"><?= sanitize($t['author']) ?></td>
    <td>
      <div class="d-flex gap-1">
        <a href="<?= APP_URL ?>/admin/questions.php?test=<?= $t['id'] ?>" class="btn btn-sm btn-light-primary" title="View MCQs"><i class="bi bi-question-circle"></i></a>
        <button class="btn btn-sm btn-danger-soft" onclick="deleteTest(<?= $t['id'] ?>,'<?= addslashes(sanitize($t['title'])) ?>')" title="Delete"><i class="bi bi-trash"></i></button>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
</div></div></div>
<?php renderScripts('<script>
const API="'.APP_URL.'/api";
async function toggleStatus(id,val){
  const fd=new FormData();fd.append("action","update");fd.append("id",id);fd.append("is_active",val);
  const t=document.querySelector(`[onchange*="${id}"]`).closest("tr");
  const title=t.querySelector("td:first-child").textContent.trim();
  fd.append("title",title);fd.append("exam_type","");fd.append("description","");fd.append("time_limit",30);fd.append("difficulty","Medium");
  const r=await fetch(`${API}/tests.php`,{method:"POST",body:fd});const d=await r.json();
  if(!d.success)alert(d.message);
}
async function deleteTest(id,title){
  if(!confirm(`Delete "${title}"? All MCQs and results will be removed.`))return;
  const fd=new FormData();fd.append("action","delete");fd.append("id",id);
  const r=await fetch(`${API}/tests.php`,{method:"POST",body:fd});const d=await r.json();
  if(d.success){document.getElementById("pageAlert").innerHTML=`<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>Test deleted.</div>`;setTimeout(()=>location.reload(),600);}
  else alert(d.message);
}
</script>'); ?>

<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db=getDB(); $tid=intval($_GET['test']??0);
if(!$tid) redirect(APP_URL.'/admin/tests.php');
$tSt=$db->prepare("SELECT t.*,u.name AS author FROM tests t JOIN users u ON t.created_by=u.id WHERE t.id=?"); $tSt->execute([$tid]); $test=$tSt->fetch();
if(!$test) redirect(APP_URL.'/admin/tests.php');
$qSt=$db->prepare("SELECT * FROM questions WHERE test_id=? ORDER BY id"); $qSt->execute([$tid]); $questions=$qSt->fetchAll(); $qCount=count($questions);
renderHead('MCQs');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('MCQ Viewer'); ?>
<div class="page-content fade-up">
<nav style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1rem;"><a href="<?= APP_URL ?>/admin/tests.php">All Tests</a> › <?= sanitize($test['title']) ?></nav>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <h2 style="font-size:1.25rem;font-weight:700;"><?= sanitize($test['title']) ?></h2>
    <div class="d-flex gap-2 flex-wrap mt-1">
      <span class="<?= subjectBadge($test['subject']) ?>"><?= $test['subject'] ?></span>
      <span class="badge-pill"><?= sanitize($test['exam_type']) ?></span>
      <span style="color:var(--text-muted);font-size:0.82rem;">By <?= sanitize($test['author']) ?></span>
    </div>
  </div>
  <span style="font-size:0.85rem;color:var(--text-secondary);font-weight:600;"><?= $qCount ?>/25 MCQs</span>
</div>
<div id="pageAlert" class="mb-3"></div>
<?php if(empty($questions)): ?>
<div class="card"><div class="empty-state"><i class="bi bi-question-circle"></i><h5>No MCQs yet</h5><p>The instructor hasn't added any questions to this test.</p></div></div>
<?php else: foreach($questions as $i=>$q): ?>
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex align-items-start gap-2 mb-2">
      <span style="background:var(--primary-light);color:var(--primary);font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:4px;flex-shrink:0;">Q<?= $i+1 ?></span>
      <p style="margin:0;font-weight:600;font-size:0.9rem;"><?= sanitize($q['question']) ?></p>
    </div>
    <div class="row g-2">
      <?php foreach(['A'=>'option_a','B'=>'option_b','C'=>'option_c','D'=>'option_d'] as $lbl=>$key): ?>
      <div class="col-md-6">
        <div style="padding:0.4rem 0.75rem;border-radius:7px;font-size:0.82rem;display:flex;align-items:center;gap:0.5rem;<?= $q['correct']===$lbl?'border:1.5px solid var(--success);background:var(--success-bg);color:var(--success);':'border:1px solid var(--border);color:var(--text-secondary);' ?>">
          <span style="font-weight:700;font-size:0.7rem;"><?= $lbl ?></span><?= sanitize($q[$key]) ?>
          <?php if($q['correct']===$lbl): ?><i class="bi bi-check-circle-fill ms-auto" style="color:var(--success);"></i><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if($q['explanation']): ?>
    <div style="margin-top:0.6rem;font-size:0.78rem;background:var(--info-bg);border:1px solid #bae6fd;border-radius:6px;padding:0.45rem 0.75rem;color:var(--info);"><i class="bi bi-lightbulb-fill me-1"></i><?= sanitize($q['explanation']) ?></div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; endif; ?>
</div></div></div>
<?php renderScripts(); ?>

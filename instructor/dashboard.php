<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('instructor');
$db=$getDB=getDB(); $uid=currentUser()['id'];
$myTests=$db->prepare("SELECT COUNT(*) FROM tests WHERE created_by=?"); $myTests->execute([$uid]); $testCnt=$myTests->fetchColumn();
$myQs=$db->prepare("SELECT COUNT(*) FROM questions q JOIN tests t ON q.test_id=t.id WHERE t.created_by=?"); $myQs->execute([$uid]); $qCnt=$myQs->fetchColumn();
$attempts=$db->prepare("SELECT COUNT(*) FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE t.created_by=? AND ta.status='completed'"); $attempts->execute([$uid]); $attCnt=$attempts->fetchColumn();
$tests=$db->prepare("SELECT t.*,(SELECT COUNT(*) FROM questions WHERE test_id=t.id) AS q_count FROM tests t WHERE t.created_by=? ORDER BY t.created_at DESC LIMIT 6"); $tests->execute([$uid]); $recentTests=$tests->fetchAll();
renderHead('Instructor Dashboard');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?>
<div class="main-content">
<?php renderTopbar('Dashboard'); ?>
<div class="page-content fade-up">

<div class="page-header">
  <h2>Instructor Dashboard</h2>
  <p>Welcome back, <?= sanitize(currentUser()['name']) ?>. Manage your tests and MCQs below.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php foreach([
    ['bi-journal-check','stat-icon-purple',$testCnt,'My Tests'],
    ['bi-question-circle-fill','stat-icon-green',$qCnt,'My MCQs'],
    ['bi-people-fill','stat-icon-orange',$attCnt,'Total Attempts'],
  ] as [$icon,$cls,$val,$label]): ?>
  <div class="col-md-4">
    <div class="stat-card">
      <div class="stat-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></div>
      <div><div class="stat-value" data-count="<?= $val ?>"><?= $val ?></div><div class="stat-label"><?= $label ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header-np">
        <div class="card-title-np"><i class="bi bi-journal-check text-primary-color"></i>My Tests</div>
        <a href="<?= APP_URL ?>/instructor/tests.php" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>New Test</a>
      </div>
      <?php if(empty($recentTests)): ?>
      <div class="empty-state"><i class="bi bi-journal-plus"></i><h5>No tests yet</h5><p><a href="<?= APP_URL ?>/instructor/tests.php">Create your first test</a></p></div>
      <?php else: ?>
      <div class="table-responsive"><table class="np-table">
        <thead><tr><th>Title</th><th>Subject</th><th>MCQs</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach($recentTests as $t): ?>
        <tr>
          <td style="font-weight:500;"><?= sanitize($t['title']) ?></td>
          <td><span class="<?= subjectBadge($t['subject']) ?>"><?= $t['subject'] ?></span></td>
          <td>
            <span style="color:<?= $t['q_count']>=25?'var(--success)':($t['q_count']>0?'var(--warning)':'var(--danger)') ?>;font-weight:600;"><?= $t['q_count'] ?></span>/25
          </td>
          <td><span class="badge-<?= $t['is_active']==='yes'?'active':'inactive' ?>"><?= $t['is_active']==='yes'?'Active':'Inactive' ?></span></td>
          <td><a href="<?= APP_URL ?>/instructor/questions.php?test=<?= $t['id'] ?>" class="btn btn-sm btn-light-primary"><i class="bi bi-question-circle me-1"></i>MCQs</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header-np"><div class="card-title-np"><i class="bi bi-info-circle text-primary-color"></i>Quick Guide</div></div>
      <div class="card-body" style="font-size:0.875rem;color:var(--text-secondary);line-height:1.8;">
        <div class="d-flex align-items-start gap-2 mb-2"><span style="color:var(--primary);font-weight:700;min-width:20px;">1.</span><span>Go to <strong>My Tests</strong> and create a test with subject, exam type and time limit.</span></div>
        <div class="d-flex align-items-start gap-2 mb-2"><span style="color:var(--primary);font-weight:700;min-width:20px;">2.</span><span>Open <strong>MCQ Bank</strong> to add up to <strong>25 questions</strong> with 4 options each.</span></div>
        <div class="d-flex align-items-start gap-2 mb-2"><span style="color:var(--primary);font-weight:700;min-width:20px;">3.</span><span>Mark the test as <strong>Active</strong> so students can see and take it.</span></div>
        <div class="d-flex align-items-start gap-2"><span style="color:var(--primary);font-weight:700;min-width:20px;">4.</span><span>Check the <strong>Leaderboard</strong> to see how students are performing.</span></div>
      </div>
    </div>
  </div>
</div>
</div></div></div>
<?php renderScripts(); ?>

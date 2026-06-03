<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db=getDB(); $uid=currentUser()['id'];
$stmt=$db->prepare("SELECT ta.*,t.title,t.subject,t.exam_type FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.user_id=? AND ta.status='completed' ORDER BY ta.finished_at DESC");
$stmt->execute([$uid]); $attempts=$stmt->fetchAll();
renderHead('My Results');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('My Results'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2>My Results</h2><p>All your completed tests — track your progress over time.</p></div>
<?php if(empty($attempts)): ?>
<div class="card"><div class="empty-state"><i class="bi bi-bar-chart"></i><h5>No results yet</h5><p>Complete a test to see your results here.</p><a href="<?= APP_URL ?>/student/tests.php" class="btn btn-primary mt-2">Take Your First Test</a></div></div>
<?php else: ?>
<div class="card"><div class="table-responsive"><table class="np-table">
  <thead><tr><th>#</th><th>Test</th><th>Subject</th><th>Exam</th><th>Score</th><th>Correct</th><th>Time</th><th>Points</th><th>Date</th><th></th></tr></thead>
  <tbody>
  <?php foreach($attempts as $i=>$a): $pct=floatval($a['percentage']); $c=$pct>=60?'var(--success)':($pct>=40?'var(--warning)':'var(--danger)'); $mm=floor($a['time_taken']/60);$ss=$a['time_taken']%60; ?>
  <tr>
    <td style="color:var(--text-muted);"><?= $i+1 ?></td>
    <td style="font-weight:500;max-width:180px;"><?= sanitize($a['title']) ?></td>
    <td><span class="<?= subjectBadge($a['subject']) ?>"><?= $a['subject'] ?></span></td>
    <td><span class="badge-pill"><?= sanitize($a['exam_type']) ?></span></td>
    <td><strong style="color:<?= $c ?>;"><?= $pct ?>%</strong></td>
    <td><?= $a['correct_answers'] ?>/<?= $a['total_questions'] ?></td>
    <td style="font-size:0.8rem;color:var(--text-muted);"><?= sprintf('%02d:%02d',$mm,$ss) ?></td>
    <td style="font-weight:600;color:var(--primary);">+<?= $a['rank_points'] ?></td>
    <td style="color:var(--text-muted);font-size:0.78rem;"><?= date('d M Y',strtotime($a['finished_at'])) ?></td>
    <td><a href="<?= APP_URL ?>/student/result_detail.php?attempt=<?= $a['id'] ?>" class="btn btn-sm btn-light-primary">Review</a></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
</div></div></div>
<?php renderScripts(); ?>

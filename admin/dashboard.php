<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db = getDB();
$stats = [
    'students'    => $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'instructors' => $db->query("SELECT COUNT(*) FROM users WHERE role='instructor'")->fetchColumn(),
    'tests'       => $db->query("SELECT COUNT(*) FROM tests")->fetchColumn(),
    'completions' => $db->query("SELECT COUNT(*) FROM test_attempts WHERE status='completed'")->fetchColumn(),
    'questions'   => $db->query("SELECT COUNT(*) FROM questions")->fetchColumn(),
];
$recentTests = $db->query("SELECT t.*,u.name AS author,(SELECT COUNT(*) FROM questions WHERE test_id=t.id) AS q_count FROM tests t JOIN users u ON t.created_by=u.id ORDER BY t.created_at DESC LIMIT 6")->fetchAll();
$recentAttempts = $db->query("SELECT ta.*,u.name AS sname,t.title,t.subject FROM test_attempts ta JOIN users u ON ta.user_id=u.id JOIN tests t ON ta.test_id=t.id WHERE ta.status='completed' ORDER BY ta.finished_at DESC LIMIT 6")->fetchAll();
$subjDist = $db->query("SELECT subject,COUNT(*) AS cnt FROM tests GROUP BY subject")->fetchAll();
renderHead('Admin Dashboard');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Dashboard'); ?>
<div class="page-content fade-up">

<div class="page-header">
  <h2>Admin Dashboard</h2>
  <p>Full platform overview — manage users, tests and content.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
<?php foreach([
  ['bi-mortarboard-fill','stat-icon-purple',$stats['students'],'Students'],
  ['bi-person-badge-fill','stat-icon-blue',$stats['instructors'],'Instructors'],
  ['bi-journal-check','stat-icon-green',$stats['tests'],'Total Tests'],
  ['bi-question-circle-fill','stat-icon-orange',$stats['questions'],'MCQs'],
  ['bi-clipboard2-check','stat-icon-purple',$stats['completions'],'Completions'],
] as [$icon,$cls,$val,$lbl]): ?>
<div class="col-6 col-lg">
  <div class="stat-card">
    <div class="stat-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></div>
    <div><div class="stat-value" data-count="<?= $val ?>"><?= $val ?></div><div class="stat-label"><?= $lbl ?></div></div>
  </div>
</div>
<?php endforeach; ?>
</div>

<div class="row g-3">
  <!-- Recent tests -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header-np">
        <div class="card-title-np"><i class="bi bi-journal-check text-primary-color"></i>Recent Tests</div>
        <a href="<?= APP_URL ?>/admin/tests.php" class="btn btn-sm btn-light-primary">View All</a>
      </div>
      <?php if(empty($recentTests)): ?>
      <div class="empty-state"><i class="bi bi-journal-x"></i><h5>No tests yet</h5></div>
      <?php else: ?>
      <div class="table-responsive"><table class="np-table">
        <thead><tr><th>Title</th><th>Subject</th><th>MCQs</th><th>By</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($recentTests as $t): ?>
        <tr>
          <td style="font-weight:500;max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($t['title']) ?></td>
          <td><span class="<?= subjectBadge($t['subject']) ?>"><?= $t['subject'] ?></span></td>
          <td><span style="font-weight:600;color:<?= $t['q_count']>=25?'var(--success)':'var(--warning)' ?>;"><?= $t['q_count'] ?>/25</span></td>
          <td style="color:var(--text-muted);font-size:0.8rem;"><?= sanitize($t['author']) ?></td>
          <td><span class="badge-<?= $t['is_active']==='yes'?'active':'inactive' ?>"><?= $t['is_active']==='yes'?'Active':'Inactive' ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5 d-flex flex-column gap-3">
    <!-- Subject distribution -->
    <div class="card">
      <div class="card-header-np"><div class="card-title-np"><i class="bi bi-pie-chart-fill text-primary-color"></i>Tests by Subject</div></div>
      <div class="card-body">
        <?php $total=max(1,array_sum(array_column($subjDist,'cnt')));
        $colors=['Physics'=>'#7c3aed','Chemistry'=>'#059669','Mathematics'=>'#d97706','English'=>'#0284c7'];
        foreach($subjDist as $s): $pct=round($s['cnt']/$total*100); $c=$colors[$s['subject']]??'#4f46e5'; ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between mb-1">
            <span class="<?= subjectBadge($s['subject']) ?>"><?= $s['subject'] ?></span>
            <span style="font-size:0.8rem;font-weight:600;color:<?= $c ?>;"><?= $s['cnt'] ?> (<?= $pct ?>%)</span>
          </div>
          <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $c ?>;"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Recent submissions -->
    <div class="card flex-grow-1">
      <div class="card-header-np"><div class="card-title-np"><i class="bi bi-activity text-primary-color"></i>Recent Submissions</div></div>
      <div class="card-body" style="padding-top:0.5rem;">
        <?php if(empty($recentAttempts)): ?><p style="color:var(--text-muted);font-size:0.85rem;">No submissions yet.</p>
        <?php else: foreach(array_slice($recentAttempts,0,5) as $a):
          $pct=floatval($a['percentage']);$c=$pct>=60?'var(--success)':($pct>=40?'var(--warning)':'var(--danger)'); ?>
        <div class="d-flex align-items-center gap-2 mb-2 p-2" style="background:#f8fafc;border-radius:8px;">
          <div class="user-ava" style="width:30px;height:30px;font-size:0.7rem;"><?= strtoupper(substr($a['sname'],0,1)) ?></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:0.8rem;font-weight:600;"><?= sanitize($a['sname']) ?></div>
            <div style="font-size:0.7rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($a['title']) ?></div>
          </div>
          <span style="font-weight:700;font-size:0.85rem;color:<?= $c ?>;"><?= $pct ?>%</span>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

</div></div></div>
<?php renderScripts(); ?>

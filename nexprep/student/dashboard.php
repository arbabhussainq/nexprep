<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db = getDB(); $uid = currentUser()['id'];
$prof = $db->prepare("SELECT * FROM student_profiles WHERE user_id=?"); $prof->execute([$uid]); $profile = $prof->fetch() ?: [];
$recent = $db->prepare("SELECT ta.*,t.title,t.subject,t.exam_type FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.user_id=? AND ta.status='completed' ORDER BY ta.finished_at DESC LIMIT 5"); $recent->execute([$uid]); $recents=$recent->fetchAll();
$testCount=$db->query("SELECT COUNT(*) FROM tests WHERE is_active='yes'")->fetchColumn();
$rankRow=$db->prepare("SELECT COUNT(*)+1 AS r FROM student_profiles WHERE rank_points>(SELECT rank_points FROM student_profiles WHERE user_id=?)"); $rankRow->execute([$uid]); $myRank=$rankRow->fetchColumn();
renderHead('Dashboard');
$u = currentUser();
?>
<body><div class="app-layout">
<?php renderSidebar(); ?>
<div class="main-content">
<?php renderTopbar('Dashboard'); ?>
<div class="page-content fade-up">

<div class="page-header">
  <h2>Good day, <?= sanitize(explode(' ',$u['name'])[0]) ?>! 👋</h2>
  <p>Here's your preparation summary<?= $profile['target_exam'] ? ' — preparing for <strong>'.$profile['target_exam'].'</strong>' : '' ?>.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php foreach([
    ['bi-journal-check','stat-icon-purple',$profile['total_tests_taken']??0,'Tests Taken'],
    ['bi-star-fill','stat-icon-green',$profile['rank_points']??0,'Rank Points'],
    ['bi-trophy-fill','stat-icon-orange','#'.$myRank,'Your Rank'],
    ['bi-grid-fill','stat-icon-blue',$testCount,'Available Tests'],
  ] as [$icon,$cls,$val,$label]): ?>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></div>
      <div>
        <div class="stat-value" <?= is_numeric($val)?"data-count='$val'":'' ?>><?= $val ?></div>
        <div class="stat-label"><?= $label ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <!-- Recent tests -->
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header-np">
        <div class="card-title-np"><i class="bi bi-clock-history text-primary-color"></i>Recent Activity</div>
        <a href="<?= APP_URL ?>/student/results.php" class="btn btn-sm btn-light-primary">View All</a>
      </div>
      <?php if(empty($recents)): ?>
      <div class="empty-state"><i class="bi bi-journal-x"></i><h5>No tests yet</h5><p><a href="<?= APP_URL ?>/student/tests.php">Take your first test!</a></p></div>
      <?php else: ?>
      <div class="table-responsive"><table class="np-table">
        <thead><tr><th>Test</th><th>Subject</th><th>Score</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach($recents as $a):
          $pct=floatval($a['percentage']);
          $clr=$pct>=60?'var(--success)':($pct>=40?'var(--warning)':'var(--danger)');
        ?>
        <tr>
          <td><a href="<?= APP_URL ?>/student/result_detail.php?attempt=<?= $a['id'] ?>" style="font-weight:500;color:var(--text-primary);"><?= sanitize($a['title']) ?></a><br><small style="color:var(--text-muted);"><?= sanitize($a['exam_type']) ?></small></td>
          <td><span class="<?= subjectBadge($a['subject']) ?>"><?= $a['subject'] ?></span></td>
          <td><strong style="color:<?= $clr ?>;"><?= $pct ?>%</strong><br><small style="color:var(--text-muted);"><?= $a['correct_answers'] ?>/<?= $a['total_questions'] ?></small></td>
          <td style="color:var(--text-muted);font-size:0.8rem;"><?= date('d M',strtotime($a['finished_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php endif; ?>
    </div>
  </div>
  <!-- Subject quick links -->
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header-np"><div class="card-title-np"><i class="bi bi-grid-fill text-primary-color"></i>Practice by Subject</div></div>
      <div class="card-body"><div class="row g-2">
        <?php foreach([
          ['Physics','bi-lightning-charge-fill','#7c3aed','#ede9fe'],
          ['Chemistry','bi-flask-fill','#059669','#d1fae5'],
          ['Mathematics','bi-calculator-fill','#d97706','#fef3c7'],
          ['English','bi-book-half','#0284c7','#e0f2fe'],
        ] as [$subj,$icon,$clr,$bg]):
          $n=$db->prepare("SELECT COUNT(*) FROM tests WHERE subject=? AND is_active='yes'"); $n->execute([$subj]); $cnt=$n->fetchColumn();
        ?>
        <div class="col-6">
          <a href="<?= APP_URL ?>/student/tests.php?subject=<?= urlencode($subj) ?>" style="text-decoration:none;">
            <div style="border:1.5px solid <?= $clr ?>20;background:<?= $bg ?>;border-radius:10px;padding:1rem;text-align:center;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
              <i class="bi <?= $icon ?>" style="font-size:1.75rem;color:<?= $clr ?>;display:block;margin-bottom:0.4rem;"></i>
              <div style="font-weight:600;font-size:0.875rem;color:<?= $clr ?>;"><?= $subj ?></div>
              <div style="font-size:0.72rem;color:<?= $clr ?>99;"><?= $cnt ?> test<?= $cnt!=1?'s':'' ?></div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div></div>
    </div>
  </div>
</div>
</div></div></div>
<?php renderScripts(); ?>

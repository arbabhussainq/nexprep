<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db=getDB();
$stats=['students'=>$db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),'completions'=>$db->query("SELECT COUNT(*) FROM test_attempts WHERE status='completed'")->fetchColumn(),'avg_score'=>round($db->query("SELECT AVG(percentage) FROM test_attempts WHERE status='completed'")->fetchColumn(),1),'avg_time'=>round($db->query("SELECT AVG(time_taken) FROM test_attempts WHERE status='completed'")->fetchColumn())];
$subjStats=$db->query("SELECT t.subject,COUNT(ta.id) AS attempts,ROUND(AVG(ta.percentage),1) AS avg_pct FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.status='completed' GROUP BY t.subject")->fetchAll();
$daily=$db->query("SELECT DATE(finished_at) AS day,COUNT(*) AS cnt FROM test_attempts WHERE status='completed' AND finished_at>=DATE_SUB(NOW(),INTERVAL 14 DAY) GROUP BY day ORDER BY day")->fetchAll();
$topTests=$db->query("SELECT t.title,t.subject,COUNT(ta.id) AS attempts,ROUND(AVG(ta.percentage),1) AS avg_pct FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.status='completed' GROUP BY t.id ORDER BY attempts DESC LIMIT 5")->fetchAll();
$topStudents=$db->query("SELECT u.name,sp.rank_points,sp.total_tests_taken FROM student_profiles sp JOIN users u ON sp.user_id=u.id ORDER BY sp.rank_points DESC LIMIT 5")->fetchAll();
$mm=floor($stats['avg_time']/60);$ss=$stats['avg_time']%60;
renderHead('Reports');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Reports'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2>Reports &amp; Analytics</h2><p>Platform-wide performance insights.</p></div>
<div class="row g-3 mb-4">
<?php foreach([['bi-mortarboard-fill','stat-icon-purple',$stats['students'],'Students'],['bi-clipboard2-check','stat-icon-green',$stats['completions'],'Completions'],['bi-bar-chart-fill','stat-icon-orange',$stats['avg_score'].'%','Avg Score'],['bi-stopwatch-fill','stat-icon-blue',sprintf('%dm %ds',$mm,$ss),'Avg Time']] as [$icon,$cls,$val,$lbl]): ?>
<div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></div><div><div class="stat-value"><?= $val ?></div><div class="stat-label"><?= $lbl ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100">
    <div class="card-header-np"><div class="card-title-np"><i class="bi bi-bar-chart-fill text-primary-color"></i>Performance by Subject</div></div>
    <div class="card-body">
      <?php $colors=['Physics'=>'#7c3aed','Chemistry'=>'#059669','Mathematics'=>'#d97706','English'=>'#0284c7'];
      foreach($subjStats as $s):$c=$colors[$s['subject']]??'#4f46e5'; ?>
      <div class="mb-3"><div class="d-flex justify-content-between mb-1 align-items-center">
        <span class="<?= subjectBadge($s['subject']) ?>"><?= $s['subject'] ?></span>
        <div class="d-flex gap-3" style="font-size:0.78rem;"><span style="color:var(--text-muted);"><?= $s['attempts'] ?> attempts</span><span style="color:<?= $c ?>;font-weight:700;"><?= $s['avg_pct'] ?>%</span></div>
      </div><div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $s['avg_pct'] ?>%;background:<?= $c ?>;"></div></div></div>
      <?php endforeach; ?>
    </div>
  </div></div>

  <div class="col-lg-6"><div class="card h-100">
    <div class="card-header-np"><div class="card-title-np"><i class="bi bi-activity text-primary-color"></i>Daily Activity (14 days)</div></div>
    <div class="card-body">
      <?php $dayMap=[];foreach($daily as $d)$dayMap[$d['day']]=$d['cnt'];$maxC=max(1,...array_values($dayMap)?:[1]); ?>
      <div style="display:flex;align-items:flex-end;gap:3px;height:120px;padding-bottom:1.5rem;position:relative;">
        <?php for($i=13;$i>=0;$i--):$day=date('Y-m-d',strtotime("-{$i} days"));$cnt=$dayMap[$day]??0;$h=max(4,round($cnt/$maxC*100));$lbl=date('d',strtotime($day)); ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;">
          <div style="flex:1;display:flex;align-items:flex-end;width:100%;">
            <div title="<?= $lbl ?>: <?= $cnt ?>" style="width:100%;height:<?= $h ?>%;background:<?= $cnt>0?'var(--primary)':'#e2e8f0' ?>;border-radius:3px 3px 0 0;transition:height 0.3s;"></div>
          </div>
          <span style="font-size:0.6rem;color:var(--text-muted);position:absolute;bottom:0;"><?= $lbl ?></span>
        </div>
        <?php endfor; ?>
      </div>
    </div>
  </div></div>

  <div class="col-lg-6"><div class="card">
    <div class="card-header-np"><div class="card-title-np"><i class="bi bi-journal-star text-primary-color"></i>Most Attempted Tests</div></div>
    <div class="card-body" style="padding-top:0.5rem;">
      <?php if(empty($topTests)):?><p style="color:var(--text-muted);">No data yet.</p><?php else:foreach($topTests as $i=>$t): ?>
      <div class="d-flex align-items-center gap-3 mb-2 p-2" style="background:#f8fafc;border-radius:8px;">
        <span style="width:22px;height:22px;border-radius:50%;background:var(--primary-light);color:var(--primary);font-size:0.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= $i+1 ?></span>
        <div style="flex:1;min-width:0;"><div style="font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= sanitize($t['title']) ?></div><span class="<?= subjectBadge($t['subject']) ?>" style="font-size:0.68rem;"><?= $t['subject'] ?></span></div>
        <div style="text-align:right;flex-shrink:0;"><div style="font-size:0.82rem;font-weight:600;color:var(--primary);"><?= $t['attempts'] ?> tries</div><div style="font-size:0.72rem;color:var(--text-muted);"><?= $t['avg_pct'] ?>% avg</div></div>
      </div>
      <?php endforeach;endif; ?>
    </div>
  </div></div>

  <div class="col-lg-6"><div class="card">
    <div class="card-header-np"><div class="card-title-np"><i class="bi bi-people-fill text-primary-color"></i>Top Students</div></div>
    <div class="card-body" style="padding-top:0.5rem;">
      <?php if(empty($topStudents)):?><p style="color:var(--text-muted);">No students yet.</p><?php else:foreach($topStudents as $i=>$s): $rc=$i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n')); ?>
      <div class="d-flex align-items-center gap-3 mb-2 p-2" style="background:#f8fafc;border-radius:8px;">
        <span class="rank-badge <?= $rc ?>"><?= $i+1 ?></span>
        <div style="flex:1;"><div style="font-size:0.875rem;font-weight:600;"><?= sanitize($s['name']) ?></div><div style="font-size:0.72rem;color:var(--text-muted);"><?= $s['total_tests_taken'] ?> tests</div></div>
        <div style="font-weight:700;color:var(--primary);font-size:0.9rem;"><?= $s['rank_points'] ?> pts</div>
      </div>
      <?php endforeach;endif; ?>
    </div>
  </div></div>
</div>
</div></div></div>
<?php renderScripts(); ?>

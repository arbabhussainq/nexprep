<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('instructor');
$db=getDB(); $uid=currentUser()['id'];
$tid=intval($_GET['test']??0);
$myTests=$db->prepare("SELECT id,title,subject FROM tests WHERE created_by=? ORDER BY created_at DESC"); $myTests->execute([$uid]); $myTests=$myTests->fetchAll();
$sql="SELECT lb.*,u.name,sp.target_exam,t.title AS test_title,t.subject FROM leaderboard lb JOIN users u ON lb.user_id=u.id JOIN tests t ON lb.test_id=t.id LEFT JOIN student_profiles sp ON u.id=sp.user_id WHERE t.created_by=?";
$p=[$uid];
if($tid){$sql.=" AND lb.test_id=?";$p[]=$tid;}
$sql.=" ORDER BY lb.rank_points DESC,lb.time_taken ASC LIMIT 50";
$st=$db->prepare($sql);$st->execute($p);$rows=$st->fetchAll();
renderHead('Leaderboard');
?>
<body><div class="app-layout"><?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Leaderboard'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2><i class="bi bi-trophy-fill me-2" style="color:#f59e0b;"></i>Leaderboard</h2><p>Rankings for your tests.</p></div>
<div class="card mb-3" style="padding:1rem;">
  <div class="d-flex gap-3 align-items-center flex-wrap">
    <label class="form-label mb-0">Filter:</label>
    <select class="form-select" style="width:auto;min-width:220px;" onchange="location.href='?test='+this.value">
      <option value="0">All My Tests</option>
      <?php foreach($myTests as $t): ?><option value="<?= $t['id'] ?>" <?= $tid==$t['id']?'selected':'' ?>>[<?= $t['subject'] ?>] <?= sanitize($t['title']) ?></option><?php endforeach; ?>
    </select>
  </div>
</div>
<?php if(empty($rows)): ?><div class="card"><div class="empty-state"><i class="bi bi-trophy"></i><h5>No entries yet</h5><p>Students haven't completed your tests yet.</p></div></div>
<?php else: ?>
<div class="card"><div class="table-responsive"><table class="np-table">
  <thead><tr><th>Rank</th><th>Student</th><?php if(!$tid): ?><th>Test</th><?php endif; ?><th>Points</th><th>Score %</th><th>Time</th></tr></thead>
  <tbody>
  <?php foreach($rows as $i=>$r): $mm=floor($r['time_taken']/60);$ss=$r['time_taken']%60;$rc=$i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n')); ?>
  <tr>
    <td><span class="rank-badge <?= $rc ?>"><?= $i+1 ?></span></td>
    <td><strong><?= sanitize($r['name']) ?></strong><br><small style="color:var(--text-muted);"><?= sanitize($r['target_exam']??'') ?></small></td>
    <?php if(!$tid): ?><td><span class="<?= subjectBadge($r['subject']) ?>"><?= $r['subject'] ?></span><br><small><?= sanitize(substr($r['test_title'],0,30)) ?></small></td><?php endif; ?>
    <td style="font-weight:700;color:var(--primary);"><?= $r['rank_points'] ?></td>
    <td style="color:<?= $r['percentage']>=60?'var(--success)':($r['percentage']>=40?'var(--warning)':'var(--danger)') ?>;font-weight:600;"><?= $r['percentage'] ?>%</td>
    <td style="color:var(--text-muted);font-size:0.8rem;"><?= sprintf('%02d:%02d',$mm,$ss) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
</div></div></div>
<?php renderScripts(); ?>

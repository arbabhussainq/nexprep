<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('admin');
$db=getDB(); $tid=intval($_GET['test']??0);
$tests=$db->query("SELECT id,title,subject FROM tests ORDER BY subject,title")->fetchAll();
$sql="SELECT lb.*,u.name,u.email,sp.target_exam,sp.city,t.title AS tname,t.subject FROM leaderboard lb JOIN users u ON lb.user_id=u.id JOIN tests t ON lb.test_id=t.id LEFT JOIN student_profiles sp ON u.id=sp.user_id WHERE 1=1";
$p=[];if($tid){$sql.=" AND lb.test_id=?";$p[]=$tid;}
$sql.=" ORDER BY lb.rank_points DESC,lb.time_taken ASC LIMIT 100";
$st=$db->prepare($sql);$st->execute($p);$rows=$st->fetchAll();
renderHead('Leaderboard');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Leaderboard'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2><i class="bi bi-trophy-fill me-2" style="color:#f59e0b;"></i>Leaderboard</h2><p>Full platform rankings. Best attempt per student per test shown.</p></div>
<div class="card mb-4" style="padding:1rem;">
  <div class="d-flex gap-3 align-items-center flex-wrap">
    <select class="form-select" style="width:auto;min-width:260px;" onchange="location.href='?test='+this.value">
      <option value="0">All Tests — Overall</option>
      <?php foreach($tests as $t): ?><option value="<?= $t['id'] ?>" <?= $tid==$t['id']?'selected':'' ?>>[<?= $t['subject'] ?>] <?= sanitize($t['title']) ?></option><?php endforeach; ?>
    </select>
    <span style="color:var(--text-muted);font-size:0.85rem;"><?= count($rows) ?> entries</span>
  </div>
</div>
<?php if(empty($rows)): ?><div class="card"><div class="empty-state"><i class="bi bi-trophy"></i><h5>No entries yet</h5></div></div>
<?php else: ?>
<div class="card"><div class="table-responsive"><table class="np-table">
  <thead><tr><th>Rank</th><th>Student</th><?php if(!$tid): ?><th>Test</th><?php endif; ?><th>Points</th><th>Score %</th><th>Time</th><th>City</th><th>Date</th></tr></thead>
  <tbody>
  <?php foreach($rows as $i=>$r): $mm=floor($r['time_taken']/60);$ss=$r['time_taken']%60;$rc=$i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n'));$pct=floatval($r['percentage']);$c=$pct>=60?'var(--success)':($pct>=40?'var(--warning)':'var(--danger)'); ?>
  <tr>
    <td><span class="rank-badge <?= $rc ?>"><?= $i+1 ?></span></td>
    <td><div style="font-weight:600;"><?= sanitize($r['name']) ?></div><div style="font-size:0.72rem;color:var(--text-muted);"><?= sanitize($r['email']) ?></div></td>
    <?php if(!$tid): ?><td><span class="<?= subjectBadge($r['subject']) ?>"><?= $r['subject'] ?></span><div style="font-size:0.75rem;color:var(--text-muted);"><?= sanitize(substr($r['tname'],0,30)) ?></div></td><?php endif; ?>
    <td style="font-weight:700;color:var(--primary);"><?= $r['rank_points'] ?></td>
    <td style="font-weight:600;color:<?= $c ?>;"><?= $pct ?>%</td>
    <td style="font-size:0.8rem;color:var(--text-muted);"><?= sprintf('%02d:%02d',$mm,$ss) ?></td>
    <td style="font-size:0.8rem;color:var(--text-muted);"><?= sanitize($r['city']??'—') ?></td>
    <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M Y',strtotime($r['achieved_at'])) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
</div></div></div>
<?php renderScripts(); ?>

<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db=getDB(); $uid=currentUser()['id']; $tid=intval($_GET['test']??0);
$tests=$db->query("SELECT id,title,subject FROM tests WHERE is_active='yes' ORDER BY subject,title")->fetchAll();
$sql="SELECT lb.*,u.id AS uid,u.name,t.title AS tname,t.subject,sp.target_exam FROM leaderboard lb JOIN users u ON lb.user_id=u.id JOIN tests t ON lb.test_id=t.id LEFT JOIN student_profiles sp ON u.id=sp.user_id WHERE 1=1";
$p=[];if($tid){$sql.=" AND lb.test_id=?";$p[]=$tid;}
$sql.=" ORDER BY lb.rank_points DESC,lb.time_taken ASC LIMIT 50";
$st=$db->prepare($sql);$st->execute($p);$rows=$st->fetchAll();
$myPos=null;foreach($rows as $i=>$r){if($r['uid']==$uid){$myPos=$i+1;break;}}
renderHead('Leaderboard');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Leaderboard'); ?>
<div class="page-content fade-up">
<div class="page-header"><h2><i class="bi bi-trophy-fill me-2" style="color:#f59e0b;"></i>Leaderboard</h2><p>Rankings by rank points (correct × 4 + time bonus). Best attempt shown.</p></div>
<div class="card mb-4" style="padding:1rem;">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <select class="form-select" style="width:auto;min-width:240px;" onchange="location.href='?test='+this.value">
      <option value="0">All Tests — Overall</option>
      <?php foreach($tests as $t): ?><option value="<?= $t['id'] ?>" <?= $tid==$t['id']?'selected':'' ?>>[<?= $t['subject'] ?>] <?= sanitize($t['title']) ?></option><?php endforeach; ?>
    </select>
    <?php if($myPos): ?><span style="margin-left:auto;background:var(--primary-light);color:var(--primary);border-radius:8px;padding:0.35rem 0.875rem;font-size:0.85rem;font-weight:600;"><i class="bi bi-person-fill me-1"></i>Your rank: #<?= $myPos ?></span><?php endif; ?>
  </div>
</div>

<?php if(empty($rows)): ?><div class="card"><div class="empty-state"><i class="bi bi-trophy"></i><h5>No entries yet</h5><p>Be the first to complete a test!</p></div></div>
<?php else: ?>

<!-- Podium top 3 -->
<?php if(count($rows)>=3): ?>
<div class="row g-3 mb-4 align-items-end justify-content-center">
  <?php $podium=[[1,2,'#fbbf24'],[0,1,'#9ca3af'],[2,3,'#cd7f32']];
  foreach($podium as [$pos,$rankN,$clr]): if(!isset($rows[$pos]))continue; $r=$rows[$pos]; $isMe=$r['uid']==$uid; ?>
  <div class="col-md-4 order-md-<?= $rankN ?>">
    <div class="card text-center <?= $isMe?'':''; ?>" style="<?= $isMe?'border-color:var(--primary);border-width:2px;':'' ?>">
      <div class="card-body">
        <div style="width:60px;height:60px;border-radius:50%;background:<?= $clr ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.3rem;color:<?= $rankN===2?'#fff':'#1e1b4b' ?>;margin:0 auto 0.75rem;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
          <?= strtoupper(substr($r['name'],0,1)) ?>
        </div>
        <div style="font-weight:700;font-size:0.9rem;"><?= sanitize($r['name']) ?><?= $isMe?' <span style="color:var(--primary);font-size:0.72rem;">(You)</span>':'' ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);"><?= sanitize($r['target_exam']??'') ?></div>
        <div style="font-size:1.4rem;font-weight:800;color:var(--primary);margin-top:0.5rem;"><?= $r['rank_points'] ?><span style="font-size:0.75rem;font-weight:500;color:var(--text-muted);"> pts</span></div>
        <span style="font-size:0.75rem;color:var(--text-secondary);"><?= $r['percentage'] ?>% accuracy</span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card"><div class="table-responsive"><table class="np-table">
  <thead><tr><th>Rank</th><th>Student</th><?php if(!$tid): ?><th>Test</th><?php endif; ?><th>Points</th><th>Score %</th><th>Time</th></tr></thead>
  <tbody>
  <?php foreach($rows as $i=>$r): $mm=floor($r['time_taken']/60);$ss=$r['time_taken']%60;$rc=$i===0?'rank-1':($i===1?'rank-2':($i===2?'rank-3':'rank-n'));$isMe=$r['uid']==$uid;$pct=floatval($r['percentage']);$c=$pct>=60?'var(--success)':($pct>=40?'var(--warning)':'var(--danger)'); ?>
  <tr style="<?= $isMe?'background:#f5f3ff;':'' ?>">
    <td><span class="rank-badge <?= $rc ?>"><?= $i+1 ?></span></td>
    <td><div style="font-weight:600;"><?= sanitize($r['name']) ?><?= $isMe?' <span style="color:var(--primary);font-size:0.72rem;">(You)</span>':'' ?></div><div style="font-size:0.72rem;color:var(--text-muted);"><?= sanitize($r['target_exam']??'') ?></div></td>
    <?php if(!$tid): ?><td><span class="<?= subjectBadge($r['subject']) ?>"><?= $r['subject'] ?></span><div style="font-size:0.72rem;color:var(--text-muted);"><?= sanitize(substr($r['tname'],0,30)) ?></div></td><?php endif; ?>
    <td style="font-weight:700;color:var(--primary);"><?= $r['rank_points'] ?></td>
    <td style="font-weight:600;color:<?= $c ?>;"><?= $pct ?>%</td>
    <td style="font-size:0.8rem;color:var(--text-muted);"><?= sprintf('%02d:%02d',$mm,$ss) ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
</div></div></div>
<?php renderScripts(); ?>

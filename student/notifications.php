<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db=getDB(); $uid=currentUser()['id'];
$db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
$stmt=$db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$uid]); $notifs=$stmt->fetchAll();
renderHead('Notifications');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Notifications'); ?>
<div class="page-content fade-up" style="max-width:700px;margin:0 auto;">
<div class="page-header"><h2>Notifications</h2><p>Your activity updates and messages.</p></div>
<?php if(empty($notifs)): ?>
<div class="card"><div class="empty-state"><i class="bi bi-bell-slash"></i><h5>No notifications</h5><p>You're all caught up! Notifications appear here after you complete tests.</p></div></div>
<?php else: ?>
<div class="card">
<?php foreach($notifs as $i=>$n): ?>
<div style="display:flex;align-items:flex-start;gap:1rem;padding:1rem;<?= $i<count($notifs)-1?'border-bottom:1px solid var(--border);':'' ?>">
  <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--primary);font-size:0.9rem;">
    <i class="bi bi-bell-fill"></i>
  </div>
  <div style="flex:1;">
    <div style="font-size:0.875rem;color:var(--text-primary);"><?= sanitize($n['message']) ?></div>
    <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.2rem;"><?= timeSince($n['created_at']) ?></div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div></div></div>
<?php renderScripts(); ?>

<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db=getDB(); $uid=currentUser()['id']; $aid=intval($_GET['attempt']??0);
if(!$aid) redirect(APP_URL.'/student/results.php');
$at=$db->prepare("SELECT ta.*,t.title,t.subject,t.exam_type,t.time_limit FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.id=? AND ta.user_id=? AND ta.status='completed'");
$at->execute([$aid,$uid]); $attempt=$at->fetch(); if(!$attempt) redirect(APP_URL.'/student/results.php');
$ans=$db->prepare("SELECT q.question,q.option_a,q.option_b,q.option_c,q.option_d,q.correct,q.explanation,aa.selected,aa.is_correct FROM attempt_answers aa JOIN questions q ON aa.question_id=q.id WHERE aa.attempt_id=? ORDER BY q.id");
$ans->execute([$aid]); $answers=$ans->fetchAll();
$pct=floatval($attempt['percentage']); $deg=round($pct*3.6);
$grade=$pct>=80?'Excellent! 🎉':($pct>=60?'Good job! 👍':($pct>=40?'Keep going 💪':'Needs work 📚'));
$scoreClr=$pct>=60?'var(--success)':($pct>=40?'var(--warning)':'var(--danger)');
$mm=floor($attempt['time_taken']/60);$ss=$attempt['time_taken']%60;
renderHead('Test Result');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?><div class="main-content"><?php renderTopbar('Test Result'); ?>
<div class="page-content fade-up" style="max-width:860px;margin:0 auto;">

<div class="result-hero mb-4">
  <div class="score-ring" style="background:conic-gradient(<?= $scoreClr ?> <?= $deg ?>deg,#e2e8f0 0deg);">
    <div class="score-inner"><div class="score-pct" style="color:<?= $scoreClr ?>;"><?= $pct ?>%</div><div class="score-lbl">Score</div></div>
  </div>
  <h3 style="font-weight:700;margin-bottom:0.25rem;"><?= $grade ?></h3>
  <p style="color:var(--text-secondary);"><?= sanitize($attempt['title']) ?> — <span class="<?= subjectBadge($attempt['subject']) ?>"><?= $attempt['subject'] ?></span></p>
  <div class="row g-2 justify-content-center mt-2">
    <?php foreach([[$attempt['correct_answers'],'Correct','var(--success)','bi-check-circle-fill'],[$attempt['wrong_answers'],'Wrong','var(--danger)','bi-x-circle-fill'],[$attempt['skipped'],'Skipped','var(--warning)','bi-dash-circle-fill'],[$attempt['rank_points'],'Points','var(--primary)','bi-star-fill'],[sprintf('%dm %ds',$mm,$ss),'Time','#64748b','bi-stopwatch-fill']] as [$v,$l,$c,$i]): ?>
    <div class="col-6 col-sm-4 col-md-2">
      <div style="padding:0.75rem;background:#f8fafc;border:1px solid var(--border);border-radius:10px;text-align:center;">
        <i class="bi <?= $i ?>" style="color:<?= $c ?>;font-size:1.1rem;"></i>
        <div style="font-weight:700;font-size:1.25rem;margin-top:0.25rem;color:<?= $c ?>;"><?= $v ?></div>
        <div style="font-size:0.68rem;color:var(--text-muted);text-transform:uppercase;"><?= $l ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="d-flex gap-2 justify-content-center mt-3 flex-wrap">
    <a href="<?= APP_URL ?>/student/leaderboard.php?test=<?= $attempt['test_id'] ?>" class="btn btn-outline-primary"><i class="bi bi-trophy-fill me-2"></i>Leaderboard</a>
    <a href="<?= APP_URL ?>/student/tests.php" class="btn btn-primary"><i class="bi bi-arrow-repeat me-2"></i>Take Another Test</a>
  </div>
</div>

<!-- Answer review -->
<div class="card">
  <div class="card-header-np">
    <div class="card-title-np"><i class="bi bi-list-check text-primary-color"></i>Answer Review</div>
    <div class="d-flex gap-1">
      <button class="btn btn-sm btn-outline-primary" onclick="filterQ('all')">All</button>
      <button class="btn btn-sm btn-success-soft" onclick="filterQ('correct')">Correct</button>
      <button class="btn btn-sm btn-danger-soft" onclick="filterQ('wrong')">Wrong</button>
    </div>
  </div>
  <div class="card-body" id="reviewList">
    <?php foreach($answers as $i=>$a): $isC=(bool)$a['is_correct']; $isS=$a['selected']===null; $st=$isS?'skipped':($isC?'correct':'wrong'); ?>
    <div class="review-item mb-3" data-status="<?= $st ?>">
      <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:1rem;">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <span style="background:var(--primary-light);color:var(--primary);font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:4px;">Q<?= $i+1 ?></span>
          <?php if($isS): ?><span style="color:var(--warning);font-size:0.8rem;font-weight:600;"><i class="bi bi-dash-circle-fill me-1"></i>Skipped</span>
          <?php elseif($isC): ?><span style="color:var(--success);font-size:0.8rem;font-weight:600;"><i class="bi bi-check-circle-fill me-1"></i>Correct</span>
          <?php else: ?><span style="color:var(--danger);font-size:0.8rem;font-weight:600;"><i class="bi bi-x-circle-fill me-1"></i>Wrong</span><?php endif; ?>
        </div>
        <div style="font-weight:600;margin-bottom:0.75rem;font-size:0.9rem;"><?= sanitize($a['question']) ?></div>
        <div class="row g-2">
          <?php foreach(['A'=>'option_a','B'=>'option_b','C'=>'option_c','D'=>'option_d'] as $lbl=>$key):
            $isAns=$a['selected']===$lbl; $isCorOpt=$a['correct']===$lbl; ?>
          <div class="col-md-6">
            <div style="padding:0.4rem 0.75rem;border-radius:7px;font-size:0.82rem;display:flex;align-items:center;gap:0.5rem;<?= $isCorOpt?'border:1.5px solid var(--success);background:var(--success-bg);color:var(--success);':($isAns&&!$isC?'border:1.5px solid var(--danger);background:var(--danger-bg);color:var(--danger);':'border:1px solid var(--border);color:var(--text-secondary);') ?>">
              <span style="font-weight:700;font-size:0.7rem;"><?= $lbl ?></span><?= sanitize($a[$key]) ?>
              <?php if($isCorOpt): ?><i class="bi bi-check-circle-fill ms-auto" style="color:var(--success);"></i><?php endif; ?>
              <?php if($isAns&&!$isC): ?><i class="bi bi-x-circle-fill ms-auto" style="color:var(--danger);"></i><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if($a['explanation']): ?>
        <div style="margin-top:0.6rem;font-size:0.78rem;background:var(--info-bg);border:1px solid #bae6fd;border-radius:6px;padding:0.45rem 0.75rem;color:var(--info);"><i class="bi bi-lightbulb-fill me-1"></i><?= sanitize($a['explanation']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

</div></div></div>
<?php renderScripts('<script>function filterQ(t){document.querySelectorAll(".review-item").forEach(el=>{el.style.display=(t==="all"||el.dataset.status===t)?"":"none";});}</script>'); ?>

<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('student');
$db=$getDB=getDB(); $user=currentUser();
$aid=intval($_GET['attempt']??0); $tid=intval($_GET['test']??0);
if(!$aid||!$tid) redirect(APP_URL.'/student/tests.php');
$atSt=$db->prepare("SELECT ta.*,t.title,t.subject,t.time_limit,t.exam_type FROM test_attempts ta JOIN tests t ON ta.test_id=t.id WHERE ta.id=? AND ta.user_id=? AND ta.status='in_progress'");
$atSt->execute([$aid,$user['id']]); $attempt=$atSt->fetch();
if(!$attempt) redirect(APP_URL.'/student/tests.php');
$qSt=$db->prepare("SELECT id,question,option_a,option_b,option_c,option_d FROM questions WHERE test_id=? ORDER BY id");
$qSt->execute([$tid]); $questions=$qSt->fetchAll();
if(empty($questions)) redirect(APP_URL.'/student/tests.php');
$total=count($questions);
$elapsed=max(0,time()-strtotime($attempt['started_at']));
$timeLeft=max(0,$attempt['time_limit']*60-$elapsed);
renderHead('Taking Test: '.$attempt['title']);
?>
<body style="background:#f8fafc;">
<!-- Quiz topbar -->
<div class="quiz-topbar">
  <div class="d-flex align-items-center gap-3">
    <a href="<?= APP_URL ?>/student/tests.php" onclick="return confirm('Exit test? Your progress will be lost!')" class="btn btn-sm btn-danger-soft"><i class="bi bi-x-lg"></i></a>
    <div>
      <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;"><?= sanitize($attempt['exam_type']) ?> — <span class="<?= subjectBadge($attempt['subject']) ?>"><?= $attempt['subject'] ?></span></div>
      <div style="font-weight:600;font-size:0.9rem;"><?= sanitize(mb_substr($attempt['title'],0,50)) ?></div>
    </div>
  </div>
  <div class="d-flex align-items-center gap-3">
    <div style="font-size:0.85rem;color:var(--text-secondary);">Q <span id="qNum">1</span> / <?= $total ?></div>
    <div class="quiz-timer" id="timerDisplay"><?= sprintf('%02d:%02d',floor($timeLeft/60),$timeLeft%60) ?></div>
  </div>
</div>

<div style="max-width:800px;margin:0 auto;padding:1.5rem 1rem;">
  <!-- Nav dots -->
  <div class="card mb-3" style="padding:1rem;">
    <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:0.6rem;">Navigator</div>
    <div style="display:flex;flex-wrap:wrap;gap:0.375rem;" id="qNav">
      <?php for($i=1;$i<=$total;$i++): ?>
      <button class="q-dot <?= $i===1?'current':'' ?>" id="dot-<?= $i ?>" onclick="jumpTo(<?= $i ?>)"><?= $i ?></button>
      <?php endfor; ?>
    </div>
    <div style="display:flex;gap:1rem;margin-top:0.6rem;font-size:0.72rem;color:var(--text-muted);">
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:var(--primary);border-radius:3px;display:inline-block;"></span>Answered</span>
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:12px;height:12px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:3px;display:inline-block;"></span>Unanswered</span>
    </div>
  </div>

  <!-- Question card -->
  <div class="q-card mb-3" id="qCard">
    <div class="q-num" id="qLabel">QUESTION 1 OF <?= $total ?></div>
    <div class="q-text" id="qText"></div>
    <div id="options"></div>
  </div>

  <!-- Nav buttons -->
  <div class="d-flex justify-content-between gap-2">
    <button class="btn btn-outline-primary" id="prevBtn" onclick="nav(-1)" disabled><i class="bi bi-chevron-left me-1"></i>Prev</button>
    <button class="btn btn-success-soft px-4" onclick="confirmSubmit()"><i class="bi bi-check2-circle me-2"></i>Submit Test</button>
    <button class="btn btn-outline-primary" id="nextBtn" onclick="nav(1)">Next<i class="bi bi-chevron-right ms-1"></i></button>
  </div>
</div>

<!-- Submit modal -->
<div class="modal fade" id="submitModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="bi bi-check2-circle me-2" style="color:var(--primary);"></i>Submit Test</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div id="submitSummary" class="row g-2 text-center mb-3"></div>
        <div style="background:var(--warning-bg);border:1px solid #fcd34d;border-radius:8px;padding:0.7rem 1rem;font-size:0.85rem;color:var(--warning);">
          <i class="bi bi-exclamation-triangle me-1"></i>You cannot change answers after submitting.
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-primary" data-bs-dismiss="modal">Continue Test</button>
        <button class="btn btn-primary" id="finalSubmit" onclick="submitTest()"><i class="bi bi-check2-all me-2"></i>Submit Now</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const QUESTIONS  = <?= json_encode(array_values($questions)) ?>;
const TOTAL      = QUESTIONS.length;
const ATTEMPT_ID = <?= $aid ?>;
const API        = '<?= APP_URL ?>/api';
let cur = 0, answers = {}, timeLeft = <?= $timeLeft ?>, timerInt, done = false;
const startMs = Date.now();

function render(idx) {
  const q = QUESTIONS[idx];
  document.getElementById('qNum').textContent   = idx+1;
  document.getElementById('qLabel').textContent = `QUESTION ${idx+1} OF ${TOTAL}`;
  document.getElementById('qText').textContent  = q.question;
  const opts = document.getElementById('options');
  opts.innerHTML = '';
  [['A','option_a'],['B','option_b'],['C','option_c'],['D','option_d']].forEach(([lbl,key]) => {
    const sel = answers[q.id] === lbl;
    const btn = document.createElement('button');
    btn.className = 'opt-btn' + (sel?' selected':'');
    btn.innerHTML = `<span class="opt-label">${lbl}</span><span>${q[key]}</span>`;
    btn.onclick   = () => { answers[q.id] = lbl; render(idx); };
    opts.appendChild(btn);
  });
  document.querySelectorAll('.q-dot').forEach((d,i) => {
    d.classList.remove('current','answered');
    if (i===idx) d.classList.add('current');
    if (answers[QUESTIONS[i].id]) d.classList.add('answered');
    if (i===idx && answers[QUESTIONS[i].id]) { d.classList.add('answered'); d.classList.add('current'); }
  });
  document.getElementById('prevBtn').disabled = idx===0;
  document.getElementById('nextBtn').style.display = idx===TOTAL-1 ? 'none' : '';
  const card = document.getElementById('qCard');
  card.style.animation='none'; card.offsetHeight; card.style.animation='fadeUp 0.25s ease';
}

function nav(d) { const n=cur+d; if(n>=0&&n<TOTAL){cur=n;render(cur);} }
function jumpTo(n) { cur=n-1; render(cur); }

function tick() {
  if (done) return;
  const el=document.getElementById('timerDisplay');
  el.textContent=String(Math.floor(timeLeft/60)).padStart(2,'0')+':'+String(timeLeft%60).padStart(2,'0');
  el.className='quiz-timer'+(timeLeft<=60?' danger':timeLeft<=180?' warn':'');
  if (timeLeft<=0) { clearInterval(timerInt); submitTest(true); return; }
  timeLeft--;
}
timerInt = setInterval(tick, 1000);

function confirmSubmit() {
  const answered=Object.keys(answers).length, skipped=TOTAL-answered;
  document.getElementById('submitSummary').innerHTML=`
    <div class="col-4"><div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.75rem;"><div style="font-size:1.4rem;font-weight:700;color:var(--primary);">${TOTAL}</div><div style="font-size:0.7rem;color:#64748b;">Total</div></div></div>
    <div class="col-4"><div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:0.75rem;"><div style="font-size:1.4rem;font-weight:700;color:var(--success);">${answered}</div><div style="font-size:0.7rem;color:#064e3b;">Answered</div></div></div>
    <div class="col-4"><div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:0.75rem;"><div style="font-size:1.4rem;font-weight:700;color:var(--warning);">${skipped}</div><div style="font-size:0.7rem;color:#78350f;">Skipped</div></div></div>`;
  new bootstrap.Modal(document.getElementById('submitModal')).show();
}

async function submitTest(auto=false) {
  if (done) return; done=true;
  clearInterval(timerInt);
  bootstrap.Modal.getInstance(document.getElementById('submitModal'))?.hide();
  const btn=document.getElementById('finalSubmit');
  if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Submitting…';}
  const timeTaken=Math.round((Date.now()-startMs)/1000);
  const fd=new FormData();
  fd.append('action','submit_attempt');
  fd.append('attempt_id',ATTEMPT_ID);
  fd.append('answers', JSON.stringify(answers));
  fd.append('time_taken',timeTaken);
  try {
    const r=await fetch(`${API}/tests.php`,{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){
      window.location.href='<?= APP_URL ?>/student/result_detail.php?attempt='+ATTEMPT_ID;
    } else {
      alert('Submission error: '+d.message);
      done=false; timerInt=setInterval(tick,1000);
      if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-check2-all me-2"></i>Submit Now';}
    }
  } catch(e) {
    alert('Network error. Please check your connection and try again.');
    done=false; timerInt=setInterval(tick,1000);
    if(btn){btn.disabled=false;btn.innerHTML='<i class="bi bi-check2-all me-2"></i>Submit Now';}
  }
}

render(0);
window.onbeforeunload = e => done ? null : (e.returnValue='');
</script>
<style>@keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}</style>
</body></html>

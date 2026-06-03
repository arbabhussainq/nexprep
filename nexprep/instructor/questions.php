<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('instructor');
$db = getDB(); $uid = currentUser()['id'];
$tid = intval($_GET['test'] ?? 0);
if (!$tid) redirect(APP_URL.'/instructor/tests.php');

$tSt = $db->prepare("SELECT * FROM tests WHERE id=? AND created_by=?");
$tSt->execute([$tid, $uid]);
$test = $tSt->fetch();
if (!$test) redirect(APP_URL.'/instructor/tests.php');

$qSt = $db->prepare("SELECT * FROM questions WHERE test_id=? ORDER BY id");
$qSt->execute([$tid]);
$questions = $qSt->fetchAll();
$qCount = count($questions);
renderHead('MCQ Bank: '.$test['title']);
?>
<body><div class="app-layout">
<?php renderSidebar(); ?>
<div class="main-content">
<?php renderTopbar('MCQ Bank'); ?>
<div class="page-content fade-up">

<!-- Breadcrumb -->
<nav style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1.25rem;">
  <a href="<?= APP_URL ?>/instructor/tests.php" style="color:var(--primary);">My Tests</a>
  <span class="mx-2">›</span>
  <span><?= sanitize($test['title']) ?></span>
</nav>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <h2 style="font-size:1.25rem;font-weight:700;"><?= sanitize($test['title']) ?></h2>
    <div class="d-flex gap-2 flex-wrap mt-1">
      <span class="<?= subjectBadge($test['subject']) ?>"><i class="bi <?= subjectIcon($test['subject']) ?> me-1"></i><?= $test['subject'] ?></span>
      <span class="badge-pill"><?= sanitize($test['exam_type']) ?></span>
      <span class="diff-<?= strtolower($test['difficulty']) ?>"><?= $test['difficulty'] ?></span>
      <span style="color:var(--text-muted);font-size:0.82rem;"><i class="bi bi-clock me-1"></i><?= $test['time_limit'] ?> min</span>
    </div>
  </div>
  <?php if ($qCount < 25): ?>
  <button class="btn btn-primary" id="btnAddMcq">
    <i class="bi bi-plus-circle-fill me-2"></i>Add MCQ
    <span style="opacity:0.7;font-size:0.78rem;">(<?= $qCount ?>/25)</span>
  </button>
  <?php else: ?>
  <span class="btn btn-success-soft" style="cursor:default;"><i class="bi bi-check-circle-fill me-2"></i>All 25 MCQs Added ✓</span>
  <?php endif; ?>
</div>

<!-- Progress bar -->
<div class="card mb-3" style="padding:1rem;">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <span style="font-size:0.82rem;color:var(--text-secondary);">Questions Added</span>
    <strong style="color:<?= $qCount>=25?'var(--success)':'var(--primary)' ?>;"><?= $qCount ?> / 25</strong>
  </div>
  <div class="progress-bar-wrap">
    <div class="progress-bar-fill" style="width:<?= ($qCount/25)*100 ?>%;background:<?= $qCount>=25?'var(--success)':'var(--primary)' ?>;"></div>
  </div>
  <?php if ($qCount < 25): ?>
  <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.35rem;"><?= 25-$qCount ?> more question<?= (25-$qCount)!=1?'s':'' ?> to go.</div>
  <?php endif; ?>
</div>

<div id="pageAlert" class="mb-3"></div>

<?php if (empty($questions)): ?>
<div class="card">
  <div class="empty-state">
    <i class="bi bi-question-circle"></i>
    <h5>No MCQs yet</h5>
    <p>Start adding questions. You can add up to <strong>25 MCQs</strong> per test.</p>
    <button class="btn btn-primary mt-2" id="btnAddMcqEmpty"><i class="bi bi-plus me-2"></i>Add First MCQ</button>
  </div>
</div>
<?php else: ?>
<?php foreach ($questions as $i => $q): ?>
<div class="card mb-3" id="qcard-<?= $q['id'] ?>">
  <div class="card-body">
    <div class="d-flex justify-content-between gap-3">
      <div style="flex:1;">
        <div class="d-flex align-items-start gap-2 mb-2">
          <span style="background:var(--primary-light);color:var(--primary);font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:4px;flex-shrink:0;margin-top:1px;">Q<?= $i+1 ?></span>
          <p style="margin:0;font-weight:600;font-size:0.9rem;line-height:1.5;"><?= sanitize($q['question']) ?></p>
        </div>
        <div class="row g-2">
          <?php foreach (['A'=>'option_a','B'=>'option_b','C'=>'option_c','D'=>'option_d'] as $lbl=>$key): ?>
          <div class="col-md-6">
            <div style="padding:0.4rem 0.75rem;border-radius:7px;font-size:0.82rem;display:flex;align-items:center;gap:0.5rem;<?= $q['correct']===$lbl?'border:1.5px solid var(--success);background:var(--success-bg);color:var(--success);':'border:1px solid var(--border);color:var(--text-secondary);' ?>">
              <span style="font-weight:700;font-size:0.7rem;min-width:14px;"><?= $lbl ?></span>
              <?= sanitize($q[$key]) ?>
              <?php if ($q['correct']===$lbl): ?><i class="bi bi-check-circle-fill ms-auto" style="color:var(--success);font-size:0.85rem;"></i><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($q['explanation']): ?>
        <div style="margin-top:0.6rem;font-size:0.78rem;background:var(--info-bg);border:1px solid #bae6fd;border-radius:6px;padding:0.45rem 0.75rem;color:var(--info);">
          <i class="bi bi-lightbulb-fill me-1"></i><?= sanitize($q['explanation']) ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-1 flex-shrink-0">
        <button class="btn btn-sm btn-outline-primary btn-edit-q" data-q='<?= htmlspecialchars(json_encode($q), ENT_QUOTES) ?>'><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-danger-soft btn-delete-q" data-id="<?= $q['id'] ?>"><i class="bi bi-trash"></i></button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

</div></div></div>

<!-- MCQ Modal -->
<div class="modal fade" id="mcqModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mcqTitle"><i class="bi bi-question-circle-fill me-2 text-primary-color"></i>Add MCQ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="qId">
        <div id="mAlert" class="mb-3"></div>
        <div class="mb-3">
          <label class="form-label">Question Text *</label>
          <textarea class="form-control" id="qText" rows="3" placeholder="Type your MCQ question here…"></textarea>
        </div>
        <div class="row g-3 mb-3">
          <?php foreach (['A','B','C','D'] as $opt): ?>
          <div class="col-md-6">
            <label class="form-label">Option <?= $opt ?> *</label>
            <div class="input-group">
              <span class="input-group-text" style="font-weight:700;min-width:38px;justify-content:center;"><?= $opt ?></span>
              <input type="text" class="form-control" id="opt<?= $opt ?>" placeholder="Enter option <?= $opt ?>…">
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Correct Answer *</label>
            <select class="form-select" id="qCorrect">
              <option value="">Select correct answer…</option>
              <option value="A">Option A</option>
              <option value="B">Option B</option>
              <option value="C">Option C</option>
              <option value="D">Option D</option>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label">Explanation <span style="font-weight:400;text-transform:none;color:var(--text-muted);">(shown to students after test)</span></label>
            <input type="text" class="form-control" id="qExp" placeholder="Why is this the correct answer?">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveMcqBtn"><i class="bi bi-save me-2"></i>Save Question</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/nexprep.js"></script>
<script>
var API = '<?= APP_URL ?>/api';
var TID = <?= intval($tid) ?>;
var mcqModal = null;

document.addEventListener('DOMContentLoaded', function() {
    mcqModal = new bootstrap.Modal(document.getElementById('mcqModal'));

    var btnAdd = document.getElementById('btnAddMcq');
    if (btnAdd) btnAdd.addEventListener('click', function() { openAdd(); });

    var btnAddEmpty = document.getElementById('btnAddMcqEmpty');
    if (btnAddEmpty) btnAddEmpty.addEventListener('click', function() { openAdd(); });

    document.querySelectorAll('.btn-edit-q').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openEdit(JSON.parse(this.getAttribute('data-q')));
        });
    });

    document.querySelectorAll('.btn-delete-q').forEach(function(btn) {
        btn.addEventListener('click', function() {
            deleteQ(this.getAttribute('data-id'));
        });
    });

    document.getElementById('saveMcqBtn').addEventListener('click', function() {
        saveMcq();
    });
});

function clearMcqForm() {
    document.getElementById('qId').value = '';
    document.getElementById('qText').value = '';
    document.getElementById('optA').value = '';
    document.getElementById('optB').value = '';
    document.getElementById('optC').value = '';
    document.getElementById('optD').value = '';
    document.getElementById('qCorrect').value = '';
    document.getElementById('qExp').value = '';
    document.getElementById('mAlert').innerHTML = '';
}

function openAdd() {
    clearMcqForm();
    document.getElementById('mcqTitle').innerHTML = '<i class="bi bi-plus-circle-fill me-2 text-primary-color"></i>Add New MCQ';
    mcqModal.show();
}

function openEdit(q) {
    clearMcqForm();
    document.getElementById('mcqTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary-color"></i>Edit MCQ';
    document.getElementById('qId').value = q.id;
    document.getElementById('qText').value = q.question;
    document.getElementById('optA').value = q.option_a;
    document.getElementById('optB').value = q.option_b;
    document.getElementById('optC').value = q.option_c;
    document.getElementById('optD').value = q.option_d;
    document.getElementById('qCorrect').value = q.correct;
    document.getElementById('qExp').value = q.explanation || '';
    mcqModal.show();
}

async function saveMcq() {
    var btn = document.getElementById('saveMcqBtn');
    var id  = document.getElementById('qId').value;
    var qtext = document.getElementById('qText').value.trim();
    var oa = document.getElementById('optA').value.trim();
    var ob = document.getElementById('optB').value.trim();
    var oc = document.getElementById('optC').value.trim();
    var od = document.getElementById('optD').value.trim();
    var cor = document.getElementById('qCorrect').value;

    if (!qtext || !oa || !ob || !oc || !od) {
        document.getElementById('mAlert').innerHTML = '<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fill in the question and all 4 options.</div>';
        return;
    }
    if (!cor) {
        document.getElementById('mAlert').innerHTML = '<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please select the correct answer.</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

    var fd = new FormData();
    fd.append('action',      id ? 'update_question' : 'add_question');
    if (id) fd.append('id', id);
    fd.append('test_id',     TID);
    fd.append('question',    qtext);
    fd.append('option_a',    oa);
    fd.append('option_b',    ob);
    fd.append('option_c',    oc);
    fd.append('option_d',    od);
    fd.append('correct',     cor);
    fd.append('explanation', document.getElementById('qExp').value.trim());

    try {
        var r = await fetch(API + '/tests.php', { method: 'POST', body: fd });
        var d = await r.json();
        if (d.success) {
            mcqModal.hide();
            document.getElementById('pageAlert').innerHTML = '<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>' + d.message + '</div>';
            setTimeout(function() { location.reload(); }, 600);
        } else {
            document.getElementById('mAlert').innerHTML = '<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>' + d.message + '</div>';
        }
    } catch(e) {
        document.getElementById('mAlert').innerHTML = '<div class="alert-danger-np"><i class="bi bi-exclamation-triangle-fill me-2"></i>Network error. Please try again.</div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save me-2"></i>Save Question';
}

async function deleteQ(id) {
    if (!confirm('Delete this question? This cannot be undone.')) return;
    var fd = new FormData();
    fd.append('action', 'delete_question');
    fd.append('id', id);
    fd.append('test_id', TID);
    try {
        var r = await fetch(API + '/tests.php', { method: 'POST', body: fd });
        var d = await r.json();
        if (d.success) {
            var card = document.getElementById('qcard-' + id);
            if (card) card.remove();
            document.getElementById('pageAlert').innerHTML = '<div class="alert-success-np"><i class="bi bi-check-circle-fill me-2"></i>Question deleted.</div>';
            setTimeout(function() { location.reload(); }, 500);
        } else {
            alert(d.message);
        }
    } catch(e) {
        alert('Network error.');
    }
}
</script>
</body></html>

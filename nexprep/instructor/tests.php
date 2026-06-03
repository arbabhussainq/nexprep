<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/layout.php';
startSecureSession(); requireRole('instructor');
$db = getDB(); $uid = currentUser()['id'];
$tests = $db->prepare("SELECT t.*,(SELECT COUNT(*) FROM questions WHERE test_id=t.id) AS q_count FROM tests t WHERE t.created_by=? ORDER BY t.created_at DESC");
$tests->execute([$uid]); $tests = $tests->fetchAll();
renderHead('My Tests');
?>
<body><div class="app-layout">
<?php renderSidebar(); ?>
<div class="main-content">
<?php renderTopbar('My Tests'); ?>
<div class="page-content fade-up">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div class="page-header mb-0"><h2>My Tests</h2><p>Create and manage your MCQ test series for students.</p></div>
  <button class="btn btn-primary" id="btnCreate"><i class="bi bi-plus-circle-fill me-2"></i>Create Test</button>
</div>

<div id="pageAlert" class="mb-3"></div>

<?php if (empty($tests)): ?>
<div class="card">
  <div class="empty-state">
    <i class="bi bi-journal-plus"></i>
    <h5>No tests yet</h5>
    <p>Click <strong>Create Test</strong> above to get started.</p>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="table-responsive">
  <table class="np-table">
    <thead><tr><th>Title</th><th>Subject</th><th>Exam</th><th>MCQs</th><th>Time</th><th>Difficulty</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($tests as $t): ?>
    <tr>
      <td style="font-weight:500;max-width:180px;"><?= sanitize($t['title']) ?></td>
      <td><span class="<?= subjectBadge($t['subject']) ?>"><i class="bi <?= subjectIcon($t['subject']) ?> me-1"></i><?= $t['subject'] ?></span></td>
      <td><span class="badge-pill"><?= sanitize($t['exam_type']) ?></span></td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div style="flex:1;height:5px;background:#e2e8f0;border-radius:3px;min-width:36px;">
            <div style="width:<?= ($t['q_count']/25)*100 ?>%;height:100%;background:<?= $t['q_count']>=25?'var(--success)':'var(--primary)' ?>;border-radius:3px;"></div>
          </div>
          <span style="font-size:0.78rem;font-weight:600;color:<?= $t['q_count']>=25?'var(--success)':($t['q_count']>0?'var(--warning)':'var(--danger)') ?>;"><?= $t['q_count'] ?>/25</span>
        </div>
      </td>
      <td><?= $t['time_limit'] ?>m</td>
      <td><span class="diff-<?= strtolower($t['difficulty']) ?>"><?= $t['difficulty'] ?></span></td>
      <td><span class="badge-<?= $t['is_active']==='yes'?'active':'inactive' ?>"><?= $t['is_active']==='yes'?'Active':'Inactive' ?></span></td>
      <td>
        <div class="d-flex gap-1">
          <a href="<?= APP_URL ?>/instructor/questions.php?test=<?= $t['id'] ?>" class="btn btn-sm btn-light-primary" title="Manage MCQs"><i class="bi bi-question-circle"></i></a>
          <button class="btn btn-sm btn-outline-primary btn-edit" data-test='<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>' title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-danger-soft btn-delete" data-id="<?= $t['id'] ?>" data-title="<?= sanitize($t['title']) ?>" title="Delete"><i class="bi bi-trash"></i></button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

</div><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /app-layout -->

<!-- Create/Edit Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="mTitle">Create Test</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="tId">
        <div id="mAlert" class="mb-3"></div>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Test Title *</label>
            <input type="text" class="form-control" id="tTitle" placeholder="e.g. NED Physics Practice Test 1">
          </div>
          <div class="col-md-6" id="subjectRow">
            <label class="form-label">Subject *</label>
            <select class="form-select" id="tSubject">
              <option value="">Select subject…</option>
              <option>Physics</option>
              <option>Chemistry</option>
              <option>Mathematics</option>
              <option>English</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Exam Type *</label>
            <input type="text" class="form-control" id="tExam" placeholder="MUET, NED, ECAT, GIKI…">
          </div>
          <div class="col-md-4">
            <label class="form-label">Time Limit (minutes)</label>
            <input type="number" class="form-control" id="tTime" value="30" min="5" max="120">
          </div>
          <div class="col-md-4">
            <label class="form-label">Difficulty</label>
            <select class="form-select" id="tDiff">
              <option>Easy</option><option selected>Medium</option><option>Hard</option>
            </select>
          </div>
          <div class="col-md-4 d-none" id="activeRow">
            <label class="form-label">Status</label>
            <select class="form-select" id="tActive">
              <option value="yes">Active (visible to students)</option>
              <option value="no">Inactive (hidden)</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Description <span style="font-weight:400;text-transform:none;color:var(--text-muted);">(optional)</span></label>
            <textarea class="form-control" id="tDesc" rows="2" placeholder="Brief description of what this test covers…"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveTestBtn"><i class="bi bi-save me-2"></i>Save Test</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/nexprep.js"></script>
<script>
var API = '<?= APP_URL ?>/api';
var testModal = null;

document.addEventListener('DOMContentLoaded', function() {
    testModal = new bootstrap.Modal(document.getElementById('testModal'));

    // Create button
    document.getElementById('btnCreate').addEventListener('click', function() {
        openCreate();
    });

    // Edit buttons
    document.querySelectorAll('.btn-edit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var t = JSON.parse(this.getAttribute('data-test'));
            openEdit(t);
        });
    });

    // Delete buttons
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            deleteTest(this.getAttribute('data-id'), this.getAttribute('data-title'));
        });
    });

    // Save button
    document.getElementById('saveTestBtn').addEventListener('click', function() {
        saveTest();
    });
});

function clearForm() {
    document.getElementById('tId').value = '';
    document.getElementById('tTitle').value = '';
    document.getElementById('tSubject').value = '';
    document.getElementById('tExam').value = '';
    document.getElementById('tTime').value = 30;
    document.getElementById('tDiff').value = 'Medium';
    document.getElementById('tDesc').value = '';
    document.getElementById('mAlert').innerHTML = '';
}

function openCreate() {
    clearForm();
    document.getElementById('mTitle').textContent = 'Create New Test';
    document.getElementById('subjectRow').classList.remove('d-none');
    document.getElementById('activeRow').classList.add('d-none');
    testModal.show();
}

function openEdit(t) {
    clearForm();
    document.getElementById('mTitle').textContent = 'Edit Test';
    document.getElementById('tId').value = t.id;
    document.getElementById('tTitle').value = t.title;
    document.getElementById('tSubject').value = t.subject;
    document.getElementById('tExam').value = t.exam_type;
    document.getElementById('tTime').value = t.time_limit;
    document.getElementById('tDiff').value = t.difficulty;
    document.getElementById('tDesc').value = t.description || '';
    document.getElementById('tActive').value = t.is_active;
    document.getElementById('subjectRow').classList.add('d-none');
    document.getElementById('activeRow').classList.remove('d-none');
    testModal.show();
}

async function saveTest() {
    var btn = document.getElementById('saveTestBtn');
    var id  = document.getElementById('tId').value;
    var title = document.getElementById('tTitle').value.trim();
    var subject = document.getElementById('tSubject').value;
    var exam = document.getElementById('tExam').value.trim();

    if (!title) { showAlert('mAlert','error','Please enter a test title.'); return; }
    if (!id && !subject) { showAlert('mAlert','error','Please select a subject.'); return; }
    if (!exam) { showAlert('mAlert','error','Please enter the exam type (e.g. NED, MUET).'); return; }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

    var fd = new FormData();
    fd.append('action',      id ? 'update' : 'create');
    if (id) fd.append('id', id);
    fd.append('title',       title);
    fd.append('subject',     subject);
    fd.append('exam_type',   exam);
    fd.append('time_limit',  document.getElementById('tTime').value);
    fd.append('difficulty',  document.getElementById('tDiff').value);
    fd.append('description', document.getElementById('tDesc').value.trim());
    if (id) fd.append('is_active', document.getElementById('tActive').value);

    try {
        var r = await fetch(API + '/tests.php', { method: 'POST', body: fd });
        var d = await r.json();
        if (d.success) {
            testModal.hide();
            showAlert('pageAlert', 'success', d.message);
            setTimeout(function() { location.reload(); }, 700);
        } else {
            showAlert('mAlert', 'error', d.message);
        }
    } catch(e) {
        showAlert('mAlert', 'error', 'Network error. Please try again.');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save me-2"></i>Save Test';
}

async function deleteTest(id, title) {
    if (!confirm('Delete "' + title + '"? All MCQs will also be deleted.')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    try {
        var r = await fetch(API + '/tests.php', { method: 'POST', body: fd });
        var d = await r.json();
        if (d.success) {
            showAlert('pageAlert', 'success', 'Test deleted.');
            setTimeout(function() { location.reload(); }, 600);
        } else {
            alert(d.message);
        }
    } catch(e) {
        alert('Network error.');
    }
}
</script>
</body></html>

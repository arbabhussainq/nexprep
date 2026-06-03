/* ============================================================
   NexPrep — Main JS
   ============================================================ */

// ---- Sidebar toggle ----
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

// ---- Auto-highlight active nav link ----
document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;
    const filename = path.split('/').pop() || 'index.php';
    // Also get the folder (student/admin/instructor)
    const parts = path.split('/').filter(Boolean);
    const folder = parts.length >= 2 ? parts[parts.length - 2] : '';

    document.querySelectorAll('.sidebar-nav a').forEach(a => {
        const href = a.getAttribute('href') || '';
        // Match by filename and folder
        const aFile = href.split('/').pop();
        const aParts = href.split('/').filter(Boolean);
        const aFolder = aParts.length >= 2 ? aParts[aParts.length - 2] : '';
        if (aFile && aFile === filename && aFolder === folder) {
            a.classList.add('active');
        }
    });

    // Count-up animation
    document.querySelectorAll('[data-count]').forEach(el => countUp(el));
});

// ---- Alert helper ----
function showAlert(containerId, type, msg) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const cls  = type === 'success' ? 'alert-success-np' : 'alert-danger-np';
    const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    el.innerHTML = '<div class="' + cls + '"><i class="bi ' + icon + ' me-2"></i>' + msg + '</div>';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ---- Count-up ----
function countUp(el) {
    const target = parseInt(el.dataset.count) || 0;
    if (target === 0) return;
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 50));
    const tick = () => {
        current = Math.min(current + step, target);
        el.textContent = current;
        if (current < target) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

// ---- Format seconds ----
function fmtTime(s) {
    return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
}

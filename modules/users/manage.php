<?php
session_start();
include '../../config/db.php';

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch all users including class
$res = $conn->query("SELECT id, username, role, class FROM users ORDER BY username ASC");
$users = [];
while ($r = $res->fetch_assoc()) $users[] = $r;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background:#f7f9fb; }
        .top-row { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
        .table-wrap { background: #fff; padding:12px; border-radius:6px; box-shadow:0 1px 4px rgba(0,0,0,0.06); }
        .small-btn { padding:.25rem .5rem; font-size:.85rem; }
        .mb-sm { margin-bottom: .75rem; }
        /* Show pointer cursor on buttons */
        button { cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <div class="top-row mb-sm">
        <h3 class="m-0">Manage Users</h3>
        <div>
            <a class="btn btn-outline-secondary me-2" href="../../dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Quick Add -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="quickAddForm" class="row g-2 align-items-center">
                <div class="col-auto">
                    <input id="qa_username" class="form-control" placeholder="Username (e.g. 10B20)" required>
                </div>
                <div class="col-auto">
                    <input id="qa_password" type="password" class="form-control" placeholder="Password" value="123456" required>
                </div>
                <div class="col-auto">
                    <select id="qa_role" name="role" class="form-select" required>
                        <option value="">Select Role</option>
                        <option value="student" selected>Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-auto" id="classField" style="display:none;">
                    <select id="qa_class" name="class" class="form-select">
                        <option value="8A">8A</option>
                        <option value="8B">8B</option>
                        <option value="9A">9A</option>
                        <option value="9B">9B</option>
                        <option value="10A">10A</option>
                        <option value="10B">10B</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-success" type="submit">Add user</button>
                </div>
                <div class="col-12">
                    <small class="text-muted">Quick add will create the user in the local database and show immediately.</small>
                </div>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <select id="classFilter" class="form-select">
                        <option value="">All Classes</option>
                        <option value="8A">8A</option>
                        <option value="8B">8B</option>
                        <option value="9A">9A</option>
                        <option value="9B">9B</option>
                        <option value="10A">10A</option>
                        <option value="10B">10B</option>
            </select>
        </div>
        <div class="col-md-5">
            <input id="searchBox" class="form-control" placeholder="Live search username...">
        </div>
        <div class="col-md-4 text-end">
            <small id="totalInfo" class="text-muted"></small>
        </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px">ID</th>
                        <th>Username</th>
                        <th style="width:120px">Role</th>
                        <th style="width:100px">Class</th>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTable"></tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <nav class="mt-3">
        <ul id="pagination" class="pagination justify-content-center"></ul>
    </nav>
</div>

<!-- Edit Modal (bootstrap) -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit user</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="edit_id">
          <div class="mb-2">
              <label class="form-label">Username</label>
              <input id="edit_username" class="form-control">
          </div>
          <div class="mb-2">
              <label class="form-label">Role</label>
              <select id="edit_role" class="form-select">
                  <option value="student">Student</option>
                  <option value="teacher">Teacher</option>
                  <option value="admin">Admin</option>
              </select>
          </div>
          <div class="mb-2" id="edit_classField" style="display:none;">
              <label class="form-label">Class</label>
              <select id="edit_class" class="form-select">
                        <option value="8A">8A</option>
                        <option value="8B">8B</option>
                        <option value="9A">9A</option>
                        <option value="9B">9B</option>
                        <option value="10A">10A</option>
                        <option value="10B">10B</option>
              </select>
          </div>
          <div class="mb-0">
              <label class="form-label">New password (leave blank to keep)</label>
              <input id="edit_password" type="password" class="form-control" placeholder="Optional">
          </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary small-btn" data-bs-dismiss="modal">Cancel</button>
        <button id="saveEditBtn" class="btn btn-primary small-btn">Save</button>
      </div>
    </div>
  </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
// all users from PHP
const allUsers = <?php echo json_encode($users, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
let filtered = [...allUsers];
let currentPage = 1;
const perPage = 10;

// Render table rows
function render() {
    const tbody = document.getElementById('userTable');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * perPage;
    const pageItems = filtered.slice(start, start + perPage);

    if (pageItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No users found.</td></tr>';
    } else {
        for (const u of pageItems) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${u.id}</td>
                <td>${escapeHtml(u.username)}</td>
                <td><span class="badge bg-info text-dark">${escapeHtml(u.role)}</span></td>
                <td>${u.class ? escapeHtml(u.class) : '-'}</td>
                <td>
                    <button class="btn btn-sm btn-warning me-1" onclick="openEdit(${u.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="doDelete(${u.id})">Delete</button>
                </td>
            `;
            tbody.appendChild(tr);
        }
    }
    renderPagination();
    document.getElementById('totalInfo').textContent = `Showing ${filtered.length} result(s)`;
}

// Pagination buttons
function renderPagination() {
    const total = Math.ceil(filtered.length / perPage) || 1;
    const ul = document.getElementById('pagination');
    ul.innerHTML = '';
    for (let i=1; i<=total; i++) {
        const li = document.createElement('li');
        li.className = 'page-item ' + (i === currentPage ? 'active' : '');
        li.innerHTML = `<a class="page-link" href="#" onclick="goPage(${i});return false;">${i}</a>`;
        ul.appendChild(li);
    }
}
function goPage(p) { currentPage = p; render(); }

// Filter and search logic
function filterAndSearch() {
    const cls = (document.getElementById('classFilter').value || '').toLowerCase();
    const q = (document.getElementById('searchBox').value || '').toLowerCase();
    filtered = allUsers.filter(u => {
        const uname = String(u.username).toLowerCase();
        const role = String(u.role).toLowerCase();
        const uclass = u.class ? String(u.class).toLowerCase() : '';
        const classMatch = !cls || (role === 'student' && uclass === cls);
        const searchMatch = !q || uname.includes(q);
        return classMatch && searchMatch;
    });
    currentPage = 1;
    render();
}

// Quick Add form submit
document.getElementById('quickAddForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const username = document.getElementById('qa_username').value.trim();
    const password = document.getElementById('qa_password').value || '123456';
    const role = document.getElementById('qa_role').value;
    const userClass = role === 'student' ? document.getElementById('qa_class').value : null;

    if (!username) return alert('Enter username');
    if (!role) return alert('Select role');
    if (role === 'student' && !userClass) return alert('Select class');

    try {
        const fd = new FormData();
        fd.append('username', username);
        fd.append('password', password);
        fd.append('role', role);
        if(userClass) fd.append('class', userClass);

        const res = await fetch('add_user.php', { method:'POST', body: fd });
        const j = await res.json();
        if (j.success) {
            allUsers.push(j.user);
            allUsers.sort((a,b) => a.username.localeCompare(b.username));
            filterAndSearch();
            document.getElementById('qa_username').value = '';
            document.getElementById('qa_password').value = '123456';
            document.getElementById('qa_role').value = '';
            document.getElementById('classField').style.display = 'none';
        } else {
            alert('Error: ' + (j.error || 'unknown'));
        }
    } catch (err) {
        alert('Request failed: ' + err);
    }
});

// Show/hide class field in quick add
document.getElementById('qa_role').addEventListener('change', function() {
    if (this.value === 'student') {
        document.getElementById('classField').style.display = 'block';
    } else {
        document.getElementById('classField').style.display = 'none';
    }
});
document.getElementById('qa_role').dispatchEvent(new Event('change'));

// Open Edit modal & fill fields
function openEdit(id) {
    const u = allUsers.find(x => x.id == id);
    if (!u) return;
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_username').value = u.username;
    document.getElementById('edit_role').value = u.role;
    document.getElementById('edit_password').value = '';
    document.getElementById('edit_class').value = u.class || '';
    toggleEditClassField();
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

// Toggle class field in Edit modal
function toggleEditClassField() {
    const role = document.getElementById('edit_role').value;
    document.getElementById('edit_classField').style.display = (role === 'student') ? 'block' : 'none';
}
document.getElementById('edit_role').addEventListener('change', toggleEditClassField);

// Save edited user
document.getElementById('saveEditBtn').addEventListener('click', async function() {
    const id = document.getElementById('edit_id').value;
    const username = document.getElementById('edit_username').value.trim();
    const role = document.getElementById('edit_role').value;
    const password = document.getElementById('edit_password').value;
    const userClass = role === 'student' ? document.getElementById('edit_class').value : null;

    if (!username) return alert('Username required');
    if (!role) return alert('Select role');
    if (role === 'student' && !userClass) return alert('Select class');

    try {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('username', username);
        fd.append('role', role);
        if (password) fd.append('password', password);
        if (userClass) fd.append('class', userClass);
        else fd.append('class', ''); // clear class for non-students

        const res = await fetch('edit_user.php', { method:'POST', body: fd });
        const j = await res.json();
        if (j.success) {
            const u = allUsers.find(x => x.id == id);
            if (u) {
                u.username = username;
                u.role = role;
                u.class = userClass || null;
            }
            filterAndSearch();
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
        } else {
            alert('Error: ' + (j.error || 'unknown'));
        }
    } catch (err) {
        alert('Request failed: ' + err);
    }
});

// Delete user
async function doDelete(id) {
    if (!confirm('Delete this user?')) return;
    try {
        const fd = new FormData();
        fd.append('id', id);
        const res = await fetch('delete_user.php', { method:'POST', body: fd });
        const j = await res.json();
        if (j.success) {
            const idx = allUsers.findIndex(x => x.id == id);
            if (idx >= 0) allUsers.splice(idx, 1);
            filterAndSearch();
        } else {
            alert('Error: ' + (j.error || 'unknown'));
        }
    } catch (err) {
        alert('Request failed: ' + err);
    }
}

// Escape HTML helper
function escapeHtml(s) {
    return String(s).replace(/[&<>"]/g, function(c) {
        return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;'}[c];
    });
}

// Event listeners for filters
document.getElementById('classFilter').addEventListener('change', filterAndSearch);
document.getElementById('searchBox').addEventListener('input', () => { filterAndSearch(); });

// Initial render
filterAndSearch();

</script>
</body>
</html>

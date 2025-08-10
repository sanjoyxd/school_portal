<?php
session_start();
include '../../config/db.php';

// Only admin allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Fetch all tests
$res = $conn->query("SELECT id, test_name, class, subject, test_date FROM tests ORDER BY test_date DESC, id DESC");
$tests = [];
while ($r = $res->fetch_assoc()) $tests[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Manage Tests</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f4f7fa;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .top-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            gap: 10px;
        }
        .table-wrap {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        td.test-name {
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: default;
        }
        .btn-sm {
            font-size: 0.8rem;
        }
        @media (max-width: 576px) {
            .top-row {
                flex-direction: column;
                align-items: stretch;
            }
            .filters > * {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-row">
        <h3 class="m-0">Manage Tests</h3>
        <div>
            <a href="../../dashboard.php" class="btn btn-outline-secondary me-2">← Back to Dashboard</a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Add New Test</button>
        </div>
    </div>

    <!-- Filters -->
    <div class="row filters mb-3">
        <div class="col-sm-4 col-md-3">
            <select id="classFilter" class="form-select" aria-label="Filter by class">
                <option value="">All Classes</option>
                <option value="8A">8A</option>
                <option value="8B">8B</option>
                <option value="9A">9A</option>
                <option value="9B">9B</option>
                <option value="10A">10A</option>
                <option value="10B">10B</option>
            </select>
        </div>
        <div class="col-sm-8 col-md-5">
            <input type="search" id="searchBox" class="form-control" placeholder="Search test name or subject...">
        </div>
        <div class="col-md-4 text-end align-self-center">
            <small id="totalInfo" class="text-muted"></small>
        </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">ID</th>
                        <th>Test Name</th>
                        <th style="width:80px">Class</th>
                        <th style="width:120px">Subject</th>
                        <th style="width:120px">Date</th>
                        <th style="width:220px">Actions</th>
                    </tr>
                </thead>
                <tbody id="testsTableBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <nav class="mt-3">
        <ul id="pagination" class="pagination justify-content-center"></ul>
    </nav>
</div>

<!-- Add Test Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addTestForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel">Add New Test</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label for="add_test_name" class="form-label">Test Name</label>
              <input type="text" class="form-control" id="add_test_name" name="test_name" required maxlength="100" />
          </div>
          <div class="mb-3">
              <label for="add_class" class="form-label">Class</label>
              <select id="add_class" name="class" class="form-select" required>
                  <option value="" disabled selected>Select Class</option>
                  <option value="8A">8A</option>
                  <option value="8B">8B</option>
                  <option value="9A">9A</option>
                  <option value="9B">9B</option>
                  <option value="10A">10A</option>
                  <option value="10B">10B</option>
              </select>
          </div>
          <div class="mb-3">
              <label for="add_subject" class="form-label">Subject</label>
              <input type="text" class="form-control" id="add_subject" name="subject" required maxlength="50" />
          </div>
          <div class="mb-3">
              <label for="add_test_date" class="form-label">Test Date</label>
              <input type="date" class="form-control" id="add_test_date" name="test_date" required />
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Add Test</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Test Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editTestForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Test</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="edit_id" name="id" />
          <div class="mb-3">
              <label for="edit_test_name" class="form-label">Test Name</label>
              <input type="text" class="form-control" id="edit_test_name" name="test_name" required maxlength="100" />
          </div>
          <div class="mb-3">
              <label for="edit_class" class="form-label">Class</label>
              <select id="edit_class" name="class" class="form-select" required>
                  <option value="8A">8A</option>
                  <option value="8B">8B</option>
                  <option value="9A">9A</option>
                  <option value="9B">9B</option>
                  <option value="10A">10A</option>
                  <option value="10B">10B</option>
              </select>
          </div>
          <div class="mb-3">
              <label for="edit_subject" class="form-label">Subject</label>
              <input type="text" class="form-control" id="edit_subject" name="subject" required maxlength="50" />
          </div>
          <div class="mb-3">
              <label for="edit_test_date" class="form-label">Test Date</label>
              <input type="date" class="form-control" id="edit_test_date" name="test_date" required />
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
const allTests = <?php echo json_encode($tests, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>;
let filtered = [...allTests];
let currentPage = 1;
const perPage = 10;

function escapeHtml(s){
    return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

function truncate(str, max=25){
    return str.length > max ? str.slice(0, max) + '…' : str;
}

function render() {
    const tbody = document.getElementById('testsTableBody');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * perPage;
    const pageItems = filtered.slice(start, start + perPage);

    if (pageItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No tests found.</td></tr>';
        document.getElementById('totalInfo').textContent = 'No results.';
        renderPagination();
        return;
    }

    for (const t of pageItems) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${t.id}</td>
            <td class="test-name" title="${escapeHtml(t.test_name)}">${truncate(escapeHtml(t.test_name))}</td>
            <td>${escapeHtml(t.class)}</td>
            <td>${escapeHtml(t.subject)}</td>
            <td>${t.test_date}</td>
            <td>
                <button class="btn btn-sm btn-warning me-1" onclick="openEditModal(${t.id})">Edit</button>
                <button class="btn btn-sm btn-danger me-1" onclick="deleteTest(${t.id})">Delete</button>
                <button class="btn btn-sm btn-info" onclick="window.location='manage_questions.php?test_id='+${t.id}">Questions</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    document.getElementById('totalInfo').textContent = `Showing ${filtered.length} result(s)`;
    renderPagination();
}

function renderPagination() {
    const totalPages = Math.ceil(filtered.length / perPage) || 1;
    const ul = document.getElementById('pagination');
    ul.innerHTML = '';

    for(let i=1; i <= totalPages; i++) {
        const li = document.createElement('li');
        li.className = 'page-item ' + (i === currentPage ? 'active' : '');
        li.innerHTML = `<a href="#" class="page-link">${i}</a>`;
        li.querySelector('a').addEventListener('click', e => {
            e.preventDefault();
            currentPage = i;
            render();
        });
        ul.appendChild(li);
    }
}

function filterAndSearch() {
    const cls = (document.getElementById('classFilter').value || '').toLowerCase();
    const q = (document.getElementById('searchBox').value || '').toLowerCase();

    filtered = allTests.filter(t => {
        const classMatch = !cls || (t.class || '').toLowerCase() === cls;
        const qMatch = !q || ((t.test_name || '').toLowerCase().includes(q) || (t.subject || '').toLowerCase().includes(q));
        return classMatch && qMatch;
    });

    currentPage = 1;
    render();
}

// Add Test Form submit
document.getElementById('addTestForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        const response = await fetch('add_test.php', {
            method: 'POST',
            body: formData,
        });
        const result = await response.json();
        if(result.success){
            allTests.push(result.test);
            // Keep sorted by date desc
            allTests.sort((a,b) => (b.test_date.localeCompare(a.test_date)) || (b.id - a.id));
            filterAndSearch();
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Request failed: ' + err.message);
    }
});

// Open Edit Modal and populate fields
function openEditModal(id){
    const test = allTests.find(t => t.id == id);
    if(!test) return alert('Test not found');

    document.getElementById('edit_id').value = test.id;
    document.getElementById('edit_test_name').value = test.test_name;
    document.getElementById('edit_class').value = test.class;
    document.getElementById('edit_subject').value = test.subject;
    document.getElementById('edit_test_date').value = test.test_date;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Edit Test Form submit
document.getElementById('editTestForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        const response = await fetch('edit_test.php', {
            method: 'POST',
            body: formData,
        });
        const result = await response.json();
        if(result.success){
            const idx = allTests.findIndex(t => t.id == result.test.id);
            if(idx !== -1){
                allTests[idx] = result.test;
                filterAndSearch();
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            }
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch (err) {
        alert('Request failed: ' + err.message);
    }
});

// Delete test
async function deleteTest(id){
    if(!confirm('Are you sure you want to delete this test?')) return;
    try {
        const fd = new FormData();
        fd.append('id', id);
        const response = await fetch('delete_test.php', {
            method: 'POST',
            body: fd,
        });
        const result = await response.json();
        if(result.success){
            const idx = allTests.findIndex(t => t.id == id);
            if(idx !== -1){
                allTests.splice(idx,1);
                filterAndSearch();
            }
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    } catch(err){
        alert('Request failed: ' + err.message);
    }
}

// Filters events
document.getElementById('classFilter').addEventListener('change', filterAndSearch);
document.getElementById('searchBox').addEventListener('input', filterAndSearch);

// Initial render
filterAndSearch();

</script>
</body>
</html>

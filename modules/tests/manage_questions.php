<?php
session_start();
include '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit;
}

$test_id = isset($_GET['test_id']) ? intval($_GET['test_id']) : 0;
if (!$test_id) {
    die('Invalid test ID');
}

// Fetch test info for header
$stmt = $conn->prepare("SELECT test_name, class, subject FROM tests WHERE id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$test = $result->fetch_assoc();
if (!$test) {
    die('Test not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Manage Questions for Test #<?php echo $test_id; ?></title>
<link href="../../assets/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body { background: #f9fafb; padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .container { max-width: 900px; }
    .table-wrap { background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
    .question-text {
        max-width: 350px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: default;
    }
    .btn-sm { font-size: 0.8rem; }
    @media (max-width: 576px) {
        .top-row { flex-direction: column; align-items: stretch; gap: 10px; }
    }
</style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap top-row">
        <h4>Questions for Test: <em><?php echo htmlspecialchars($test['test_name']); ?></em> (<?php echo htmlspecialchars($test['class'] . ', ' . $test['subject']); ?>)</h4>
        <div>
            <a href="manage.php" class="btn btn-outline-secondary me-2">← Back to Tests</a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Add Question</button>
        </div>
    </div>

    <div class="mb-3 row">
        <div class="col-md-6">
            <input id="searchBox" type="search" class="form-control" placeholder="Search questions...">
        </div>
        <div class="col-md-6 text-end align-self-center">
            <small id="totalInfo" class="text-muted"></small>
        </div>
    </div>

    <div class="table-wrap">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px">ID</th>
                        <th>Question</th>
                        <th style="width:130px">Correct Option</th>
                        <th style="width:170px">Actions</th>
                    </tr>
                </thead>
                <tbody id="questionsTableBody"></tbody>
            </table>
        </div>
    </div>

    <nav class="mt-3">
        <ul id="pagination" class="pagination justify-content-center"></ul>
    </nav>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="addQuestionForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add New Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="test_id" value="<?php echo $test_id; ?>" />
        <div class="mb-3">
            <label for="add_question_text" class="form-label">Question Text</label>
            <textarea id="add_question_text" name="question_text" class="form-control" rows="3" required maxlength="500"></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="add_option_a" class="form-label">Option A</label>
                <input type="text" id="add_option_a" name="option_a" class="form-control" required maxlength="150" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="add_option_b" class="form-label">Option B</label>
                <input type="text" id="add_option_b" name="option_b" class="form-control" required maxlength="150" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="add_option_c" class="form-label">Option C</label>
                <input type="text" id="add_option_c" name="option_c" class="form-control" required maxlength="150" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="add_option_d" class="form-label">Option D</label>
                <input type="text" id="add_option_d" name="option_d" class="form-control" required maxlength="150" />
            </div>
        </div>
        <div class="mb-3">
            <label for="add_correct_option" class="form-label">Correct Option</label>
            <select id="add_correct_option" name="correct_option" class="form-select" required>
                <option value="" disabled selected>Select Correct Option</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Add Question</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Question Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="editQuestionForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit_id" />
        <input type="hidden" name="test_id" value="<?php echo $test_id; ?>" />
        <div class="mb-3">
            <label for="edit_question_text" class="form-label">Question Text</label>
            <textarea id="edit_question_text" name="question_text" class="form-control" rows="3" required maxlength="500"></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="edit_option_a" class="form-label">Option A</label>
                <input type="text" id="edit_option_a" name="option_a" class="form-control" required maxlength="150" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="edit_option_b" class="form-label">Option B</label>
                <input type="text" id="edit_option_b" name="option_b" class="form-control" required maxlength="150" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="edit_option_c" class="form-label">Option C</label>
                <input type="text" id="edit_option_c" name="option_c" class="form-control" required maxlength="150" />
            </div>
            <div class="col-md-6 mb-3">
                <label for="edit_option_d" class="form-label">Option D</label>
                <input type="text" id="edit_option_d" name="option_d" class="form-control" required maxlength="150" />
            </div>
        </div>
        <div class="mb-3">
            <label for="edit_correct_option" class="form-label">Correct Option</label>
            <select id="edit_correct_option" name="correct_option" class="form-select" required>
                <option value="" disabled>Select Correct Option</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
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
const testId = <?php echo $test_id; ?>;
let allQuestions = [];
let filtered = [];
let currentPage = 1;
const perPage = 8;

function escapeHtml(s){
    return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

function truncate(str, max=60){
    return str.length > max ? str.slice(0, max) + '…' : str;
}

async function fetchQuestions(){
    try {
        const res = await fetch('get_questions.php?test_id=' + testId);
        const data = await res.json();
        if(data.success){
            allQuestions = data.questions;
            filterAndRender();
        } else {
            alert('Failed to fetch questions: ' + (data.error || 'Unknown error'));
        }
    } catch(e) {
        alert('Request failed: ' + e.message);
    }
}

function render(){
    const tbody = document.getElementById('questionsTableBody');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * perPage;
    const pageItems = filtered.slice(start, start + perPage);

    if(pageItems.length === 0){
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No questions found.</td></tr>';
        document.getElementById('totalInfo').textContent = 'No results.';
        renderPagination();
        return;
    }

    for(const q of pageItems){
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${q.id}</td>
            <td class="question-text" title="${escapeHtml(q.question_text)}">${truncate(escapeHtml(q.question_text))}</td>
            <td>${escapeHtml(q.correct_option)}</td>
            <td>
                <button class="btn btn-sm btn-warning me-1" onclick="openEditModal(${q.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteQuestion(${q.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(tr);
    }
    document.getElementById('totalInfo').textContent = `Showing ${filtered.length} result(s)`;
    renderPagination();
}

function renderPagination(){
    const totalPages = Math.ceil(filtered.length / perPage) || 1;
    const ul = document.getElementById('pagination');
    ul.innerHTML = '';
    for(let i=1; i <= totalPages; i++){
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

function filterAndRender(){
    const q = (document.getElementById('searchBox').value || '').toLowerCase();
    filtered = allQuestions.filter(item => item.question_text.toLowerCase().includes(q));
    currentPage = 1;
    render();
}

document.getElementById('searchBox').addEventListener('input', filterAndRender);

// Add Question form submit
document.getElementById('addQuestionForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const res = await fetch('add_question.php', { method:'POST', body: formData });
        const data = await res.json();
        if(data.success){
            allQuestions.push(data.question);
            filterAndRender();
            e.target.reset();
            bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    } catch(err){
        alert('Request failed: ' + err.message);
    }
});

// Open Edit modal & fill form
function openEditModal(id){
    const q = allQuestions.find(x => x.id == id);
    if(!q) return alert('Question not found');

    document.getElementById('edit_id').value = q.id;
    document.getElementById('edit_question_text').value = q.question_text;
    document.getElementById('edit_option_a').value = q.option_a;
    document.getElementById('edit_option_b').value = q.option_b;
    document.getElementById('edit_option_c').value = q.option_c;
    document.getElementById('edit_option_d').value = q.option_d;
    document.getElementById('edit_correct_option').value = q.correct_option;

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Edit question form submit
document.getElementById('editQuestionForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const res = await fetch('edit_question.php', { method:'POST', body: formData });
        const data = await res.json();
        if(data.success){
            const idx = allQuestions.findIndex(x => x.id == data.question.id);
            if(idx !== -1){
                allQuestions[idx] = data.question;
                filterAndRender();
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            }
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    } catch(err){
        alert('Request failed: ' + err.message);
    }
});

// Delete question
async function deleteQuestion(id){
    if(!confirm('Are you sure you want to delete this question?')) return;
    try {
        const fd = new FormData();
        fd.append('id', id);
        const res = await fetch('delete_question.php', { method:'POST', body: fd });
        const data = await res.json();
        if(data.success){
            const idx = allQuestions.findIndex(x => x.id == id);
            if(idx !== -1){
                allQuestions.splice(idx,1);
                filterAndRender();
            }
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    } catch(err){
        alert('Request failed: ' + err.message);
    }
}

// Initial fetch
fetchQuestions();

</script>
</body>
</html>

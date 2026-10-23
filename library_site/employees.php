<?php
session_start();
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message  = '';
$msg_type = '';

// --- AJAX: удалить сотрудника ---
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['emp_id'] ?? 0);
    try {
        $st = mysqli_prepare($conn, "DELETE FROM сотрудники WHERE employee_id = ?");
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// --- AJAX: редактировать сотрудника ---
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id        = (int)($_POST['emp_id']    ?? 0);
    $last_name = trim($_POST['last_name']  ?? '');
    $position  = trim($_POST['position']   ?? '');
    $education = trim($_POST['education']  ?? '');
    $salary    = (float)($_POST['salary']  ?? 0);
    $lib_id    = (int)($_POST['lib_id']    ?? 0);
    $birth_year= (int)($_POST['birth_year']?? 0);
    $emp_year  = (int)($_POST['emp_year']  ?? 0);
    try {
        $st = mysqli_prepare($conn,
            "UPDATE сотрудники SET last_name=?, position=?, education=?, salary=?, library_id=?, birth_year=?, employment_year=?
             WHERE employee_id=?");
        mysqli_stmt_bind_param($st, 'sssdiiii', $last_name, $position, $education, $salary, $lib_id, $birth_year, $emp_year, $id);
        mysqli_stmt_execute($st);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

$libs_q = mysqli_query($conn, "SELECT library_id, name, city FROM библиотеки ORDER BY city");
$libs_arr = [];
while ($l = mysqli_fetch_assoc($libs_q)) $libs_arr[] = $l;

$lib_id   = isset($_GET['lib'])  ? (int)$_GET['lib']  : 0;
$position = isset($_GET['pos'])  ? trim($_GET['pos'])  : '';
$sort     = isset($_GET['sort']) ? $_GET['sort']       : 'salary_desc';

$stmt = mysqli_prepare($conn, "CALL пр_СотрудникиБиблиотеки(?, ?)");
mysqli_stmt_bind_param($stmt, 'is', $lib_id, $position);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rows = [];
while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;
mysqli_next_result($conn);

// Нужны ID для редактирования — дополним данными
$emp_ids = [];
if (!empty($rows)) {
    $id_q = mysqli_query($conn,
        "SELECT e.employee_id, e.last_name, e.position, e.education, e.salary, e.library_id,
                e.birth_year, e.employment_year,
                (YEAR(CURDATE())-e.employment_year) AS exp
         FROM сотрудники e ORDER BY e.salary DESC");
    while ($r2 = mysqli_fetch_assoc($id_q)) {
        $emp_ids[$r2['last_name']] = $r2; // индекс по фамилии (упрощение)
    }
}

usort($rows, function($a, $b) use ($sort) {
    return match($sort) {
        'name_asc'   => strcmp($a['Фамилия'], $b['Фамилия']),
        'name_desc'  => strcmp($b['Фамилия'], $a['Фамилия']),
        'salary_asc' => $a['Зарплата'] <=> $b['Зарплата'],
        'exp_desc'   => $b['Стаж'] <=> $a['Стаж'],
        'exp_asc'    => $a['Стаж'] <=> $b['Стаж'],
        default      => $b['Зарплата'] <=> $a['Зарплата'],
    };
});

$avg_salary = count($rows) ? array_sum(array_column($rows,'Зарплата'))/count($rows) : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Сотрудники · USM</title>
<?php include 'head.php'; ?>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container" style="padding-top:28px">
    <div class="section-title">Сотрудники
        <a href="add_employee.php" class="btn btn-green btn-sm" style="margin-left:auto">+ Добавить сотрудника</a>
    </div>

    <form class="search-bar" method="get">
        <div class="form-group">
            <label>Библиотека</label>
            <select name="lib">
                <option value="0">Все библиотеки</option>
                <?php foreach ($libs_arr as $l): ?>
                <option value="<?= $l['library_id'] ?>" <?= $lib_id==$l['library_id']?'selected':'' ?>>
                    <?= htmlspecialchars($l['name']) ?> (<?= htmlspecialchars($l['city']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Должность</label>
            <input type="text" name="pos" value="<?= htmlspecialchars($position) ?>" placeholder="Библиотекарь">
        </div>
        <div class="form-group">
            <label>Сортировка</label>
            <select name="sort">
                <option value="salary_desc" <?= $sort==='salary_desc'?'selected':'' ?>>Зарплата ↓</option>
                <option value="salary_asc"  <?= $sort==='salary_asc' ?'selected':'' ?>>Зарплата ↑</option>
                <option value="name_asc"    <?= $sort==='name_asc'   ?'selected':'' ?>>Фамилия А→Я</option>
                <option value="exp_desc"    <?= $sort==='exp_desc'   ?'selected':'' ?>>Стаж ↓</option>
                <option value="exp_asc"     <?= $sort==='exp_asc'    ?'selected':'' ?>>Стаж ↑</option>
            </select>
        </div>
        <button class="btn" type="submit">Найти</button>
        <a class="btn btn-ghost" href="employees.php">Сбросить</a>
    </form>

    <?php if (count($rows) > 0): ?>
    <div style="display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap">
        <div class="stat-card" style="flex:1;min-width:120px;padding:14px">
            <div class="stat-num" style="font-size:24px"><?= count($rows) ?></div>
            <div class="stat-label">сотрудников</div>
        </div>
        <div class="stat-card" style="flex:1;min-width:120px;padding:14px">
            <div class="stat-num" style="font-size:20px"><?= number_format($avg_salary,0,'.','&nbsp;') ?> ₽</div>
            <div class="stat-label">средняя зарплата</div>
        </div>
    </div>
    <?php endif; ?>

    <div style="margin-bottom:8px">
        <input type="text" id="name-filter" placeholder="Фильтр по фамилии..."
               style="padding:7px 11px;border:1px solid var(--border);border-radius:5px;font-size:13px;width:240px">
    </div>

    <div class="result-count">Найдено: <b><?= count($rows) ?></b> сотрудников</div>

    <?php if (empty($rows)): ?>
        <div class="empty">Сотрудники не найдены.</div>
    <?php else: ?>
    <div class="table-wrap">
    <table id="emp-table">
        <thead><tr>
            <th>Фамилия</th><th>Должность</th><th>Библиотека</th><th>Город</th>
            <th>Зарплата</th><th>Стаж</th><th>Образование</th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            // ищем полные данные по фамилии
            $full = $emp_ids[$r['Фамилия']] ?? null;
            $eid  = $full['employee_id'] ?? 0;
        ?>
        <tr class="clickable" data-id="<?= $eid ?>"
            data-last="<?= htmlspecialchars($r['Фамилия'],ENT_QUOTES) ?>"
            data-pos="<?= htmlspecialchars($r['Должность']??'',ENT_QUOTES) ?>"
            data-edu="<?= htmlspecialchars($r['Образование']??'',ENT_QUOTES) ?>"
            data-salary="<?= $r['Зарплата'] ?>"
            data-lib="<?= $full['library_id']??0 ?>"
            data-birth="<?= $full['birth_year']??'' ?>"
            data-empyear="<?= $full['employment_year']??'' ?>"
            onclick="openEdit(this)">
            <td><b><?= htmlspecialchars($r['Фамилия']) ?></b></td>
            <td><?= htmlspecialchars($r['Должность']??'—') ?></td>
            <td><?= htmlspecialchars($r['Библиотека']) ?></td>
            <td><?= htmlspecialchars($r['Город']) ?></td>
            <td><?= $r['Зарплата'] ? number_format($r['Зарплата'],0,'.','&nbsp;').' ₽' : '—' ?></td>
            <td><?= $r['Стаж']!==null ? $r['Стаж'].' лет' : '—' ?></td>
            <td style="font-size:12px"><?= htmlspecialchars($r['Образование']??'—') ?></td>
            <td style="width:32px;text-align:center;color:var(--muted);font-size:14px">✎</td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Модалка редактирования/удаления -->
<div class="modal-bg" id="edit-modal">
    <div class="modal">
        <h3>Редактировать сотрудника</h3>
        <div id="modal-msg"></div>
        <input type="hidden" id="m-id">
        <div class="form-row" style="margin-bottom:12px">
            <div class="form-group-v">
                <label>Фамилия</label>
                <input type="text" id="m-last">
            </div>
            <div class="form-group-v">
                <label>Должность</label>
                <input type="text" id="m-pos" list="pos-list">
                <datalist id="pos-list">
                    <option>Библиотекарь</option><option>Старший библиотекарь</option>
                    <option>Заведующий</option><option>Главный библиограф</option>
                    <option>Хранитель фондов</option><option>Методист</option>
                </datalist>
            </div>
        </div>
        <div class="form-row" style="margin-bottom:12px">
            <div class="form-group-v">
                <label>Год рождения</label>
                <input type="number" id="m-birth" min="1950" max="2005">
            </div>
            <div class="form-group-v">
                <label>Год найма</label>
                <input type="number" id="m-empyear" min="1980" max="<?= date('Y') ?>">
            </div>
        </div>
        <div class="form-row" style="margin-bottom:12px">
            <div class="form-group-v">
                <label>Зарплата (₽)</label>
                <input type="number" id="m-salary" min="1">
            </div>
            <div class="form-group-v">
                <label>Образование</label>
                <select id="m-edu">
                    <option value="">— не указано —</option>
                    <option>Высшее образование</option>
                    <option>Среднее профессиональное</option>
                    <option>Среднее специальное</option>
                    <option>Среднее общее</option>
                </select>
            </div>
        </div>
        <div class="form-full" style="margin-bottom:4px">
            <div class="form-group-v">
                <label>Библиотека</label>
                <select id="m-lib">
                    <?php foreach ($libs_arr as $l): ?>
                    <option value="<?= $l['library_id'] ?>"><?= htmlspecialchars($l['name']) ?> (<?= $l['city'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-green" onclick="saveEdit()">Сохранить</button>
            <button class="btn btn-red"   onclick="deleteEmp()">Удалить</button>
            <button class="btn btn-ghost" onclick="closeModal()" style="margin-left:auto">Отмена</button>
        </div>
    </div>
</div>

<footer>Библиотечная система USM · База данных MySQL</footer>

<script>
document.getElementById('name-filter').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#emp-table tbody tr').forEach(row => {
        row.style.display = (!q || row.cells[0].textContent.toLowerCase().includes(q)) ? '' : 'none';
    });
});

let currentRow = null;

function openEdit(row) {
    currentRow = row;
    document.getElementById('m-id').value      = row.dataset.id;
    document.getElementById('m-last').value    = row.dataset.last;
    document.getElementById('m-pos').value     = row.dataset.pos;
    document.getElementById('m-edu').value     = row.dataset.edu;
    document.getElementById('m-salary').value  = row.dataset.salary;
    document.getElementById('m-birth').value   = row.dataset.birth;
    document.getElementById('m-empyear').value = row.dataset.empyear;
    document.getElementById('m-lib').value     = row.dataset.lib;
    document.getElementById('modal-msg').innerHTML = '';
    document.getElementById('edit-modal').classList.add('open');
}

function closeModal() {
    document.getElementById('edit-modal').classList.remove('open');
}

document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

async function saveEdit() {
    const fd = new FormData();
    fd.append('action',     'edit');
    fd.append('emp_id',     document.getElementById('m-id').value);
    fd.append('last_name',  document.getElementById('m-last').value);
    fd.append('position',   document.getElementById('m-pos').value);
    fd.append('education',  document.getElementById('m-edu').value);
    fd.append('salary',     document.getElementById('m-salary').value);
    fd.append('lib_id',     document.getElementById('m-lib').value);
    fd.append('birth_year', document.getElementById('m-birth').value);
    fd.append('emp_year',   document.getElementById('m-empyear').value);
    const resp = await fetch('employees.php', {method:'POST', body:fd});
    const data = await resp.json();
    if (data.ok) {
        location.reload();
    } else {
        document.getElementById('modal-msg').innerHTML =
            `<div class="msg error" style="margin-bottom:12px">${data.msg}</div>`;
    }
}

async function deleteEmp() {
    if (!confirm('Удалить сотрудника? Это действие необратимо.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('emp_id', document.getElementById('m-id').value);
    const resp = await fetch('employees.php', {method:'POST', body:fd});
    const data = await resp.json();
    if (data.ok) {
        location.reload();
    } else {
        document.getElementById('modal-msg').innerHTML =
            `<div class="msg error" style="margin-bottom:12px">${data.msg}</div>`;
    }
}
</script>
</body>
</html>

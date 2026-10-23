<?php
session_start();
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$funds_raw = mysqli_query($conn,
    "SELECT f.fund_id, f.fund_name, b.name AS lib_name, b.city
     FROM фонды f JOIN библиотеки b ON f.library_id=b.library_id ORDER BY b.city, f.fund_name");
$pubs_raw  = mysqli_query($conn,
    "SELECT p.publication_id, p.title, lt.type_name
     FROM публикации p JOIN типы_литературы lt ON p.literature_type_id=lt.type_id ORDER BY p.title");
$emps_raw  = mysqli_query($conn,
    "SELECT e.employee_id, e.last_name, b.name AS lib_name, b.city
     FROM сотрудники e JOIN библиотеки b ON e.library_id=b.library_id ORDER BY b.city, e.last_name");

$message  = '';
$msg_type = '';
$posted   = [];

if (isset($_GET['ajax_emp_by_fund'])) {
    $fund_id = (int)$_GET['ajax_emp_by_fund'];
    $r = mysqli_query($conn,
        "SELECT e.employee_id, e.last_name, b.name AS lib
         FROM сотрудники e
         JOIN библиотеки b ON e.library_id=b.library_id
         JOIN фонды f ON f.library_id=b.library_id
         WHERE f.fund_id=$fund_id ORDER BY e.last_name");
    $out=[];
    while ($row=mysqli_fetch_assoc($r)) $out[]=$row;
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $posted  = $_POST;
    $fund_id = (int)($_POST['fund_id']  ?? 0);
    $pub_id  = (int)($_POST['pub_id']   ?? 0);
    $emp_id  = (int)($_POST['emp_id']   ?? 0);
    $qty     = (int)($_POST['quantity'] ?? 0);
    $rep_date= trim($_POST['rep_date']  ?? '');

    if (!$fund_id || !$pub_id || !$emp_id || $qty<=0 || !$rep_date) {
        $message  = 'Заполните все обязательные поля.';
        $msg_type = 'error';
    } else {
        try {
            $st = mysqli_prepare($conn,
                "INSERT INTO пополнение_фондов (fund_id,publication_id,employee_id,quantity_added,replenishment_date)
                 VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($st,'iiiis',$fund_id,$pub_id,$emp_id,$qty,$rep_date);
            mysqli_stmt_execute($st);

            $type_q = mysqli_query($conn,
                "SELECT lt.type_name FROM публикации p
                 JOIN типы_литературы lt ON p.literature_type_id=lt.type_id
                 WHERE p.publication_id=$pub_id LIMIT 1");
            $tname = strtolower(mysqli_fetch_assoc($type_q)['type_name']??'');
            if (str_contains($tname,'журнал'))
                mysqli_query($conn,"UPDATE фонды SET total_journals=COALESCE(total_journals,0)+$qty WHERE fund_id=$fund_id");
            elseif (str_contains($tname,'газет'))
                mysqli_query($conn,"UPDATE фонды SET total_newspapers=COALESCE(total_newspapers,0)+$qty WHERE fund_id=$fund_id");
            else
                mysqli_query($conn,"UPDATE фонды SET total_books=COALESCE(total_books,0)+$qty WHERE fund_id=$fund_id");

            $message  = "Поступление зарегистрировано.";
            $msg_type = 'success';
            $posted   = [];
        } catch (Exception $e) {
            $message  = 'Ошибка: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

$funds_arr=[]; while ($f=mysqli_fetch_assoc($funds_raw)) $funds_arr[]=$f;
$pubs_arr =[];  while ($p=mysqli_fetch_assoc($pubs_raw))  $pubs_arr[] =$p;
$emps_arr =[];  while ($e=mysqli_fetch_assoc($emps_raw))  $emps_arr[] =$e;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Зарегистрировать пополнение · USM</title>
<?php include 'head.php'; ?>
<style>
        /* Центрируем анкету */
        .form-card {
            max-width: 600px !important;
            margin: 30px auto !important;
            background: var(--card, #fff);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        /* Центрируем сообщения об успехе/ошибке */
        .msg {
            max-width: 600px !important;
            margin: 15px auto !important;
        }
        /* Чтобы поля ложились ровно друг под другом и не косили влево */
        .form-row {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-group-v {
            display: flex;
            flex-direction: column;
            text-align: left;
        }
        .form-group-v input, .form-group-v select {
            width: 100%;
            padding: 11px 14px;
            box-sizing: border-box;
            border-radius: 6px;
            border: 1px solid var(--border, #ccc);
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container" style="padding-top:28px">
    <div class="section-title">Зарегистрировать пополнение фонда</div>

    <?php if ($message): ?>
    <div class="msg <?= $msg_type ?>"><?= htmlspecialchars($message) ?>
        <?php if ($msg_type==='success'): ?>
        · <a href="replenishments.php" style="color:inherit;font-weight:600">Журнал пополнений</a>
        · <a href="add_replenishment.php" style="color:inherit;font-weight:600">Добавить ещё</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="form-card" style="max-width:720px">
        <form method="post" id="rep-form">
            <div class="form-full">
                <div class="form-group-v">
                    <label>Фонд библиотеки *</label>
                    <select name="fund_id" id="fund-select" required>
                        <option value="">— выбрать фонд —</option>
                        <?php foreach ($funds_arr as $f): ?>
                        <option value="<?= $f['fund_id'] ?>" <?= ($posted['fund_id']??0)==$f['fund_id']?'selected':'' ?>>
                            <?= htmlspecialchars($f['fund_name']) ?> · <?= htmlspecialchars($f['lib_name']) ?>, <?= htmlspecialchars($f['city']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-full">
                <div class="form-group-v pub-search-wrap">
                    <label>Публикация *</label>
                    <input type="text" id="pub-search" placeholder="Начните вводить название...">
                    <div class="live-results" id="pub-results"></div>
                    <input type="hidden" name="pub_id" id="pub-id-hidden" value="<?= (int)($posted['pub_id']??0) ?>">
                    <div id="pub-chosen" style="margin-top:5px;font-size:13px;color:var(--teal)"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-v">
                    <label>Сотрудник *</label>
                    <select name="emp_id" id="emp-select" required>
                        <option value="">— выбрать —</option>
                        <?php foreach ($emps_arr as $e): ?>
                        <option value="<?= $e['employee_id'] ?>" <?= ($posted['emp_id']??0)==$e['employee_id']?'selected':'' ?>>
                            <?= htmlspecialchars($e['last_name']) ?> · <?= htmlspecialchars($e['lib_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-v">
                    <label>Дата поступления *</label>
                    <input type="date" name="rep_date" required value="<?= htmlspecialchars($posted['rep_date']??date('Y-m-d')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-v">
                    <label>Количество экземпляров *</label>
                    <input type="number" name="quantity" min="1" max="10000" value="<?= (int)($posted['quantity']??1) ?>" required>
                </div>
                <div class="form-group-v">
                    <label>Примечание</label>
                    <input type="text" name="notes" placeholder="Необязательно" value="<?= htmlspecialchars($posted['notes']??'') ?>">
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:8px">
                <button class="btn btn-green" type="submit">Зарегистрировать поступление</button>
                <a class="btn btn-ghost" href="replenishments.php">Отмена</a>
                <a class="btn btn-ghost" href="add_publication.php" style="margin-left:auto">Нет нужной публикации?</a>
            </div>
        </form>
    </div>
</div>

<footer>Библиотечная система USM · База данных MySQL</footer>

<script>
const allPubs = <?= json_encode($pubs_arr, JSON_UNESCAPED_UNICODE) ?>;
const pubSearch  = document.getElementById('pub-search');
const pubResults = document.getElementById('pub-results');
const pubHidden  = document.getElementById('pub-id-hidden');
const pubChosen  = document.getElementById('pub-chosen');

pubSearch.addEventListener('input', () => {
    const q = pubSearch.value.trim().toLowerCase();
    if (q.length < 2) { pubResults.classList.remove('open'); return; }
    const matches = allPubs.filter(p => p.title.toLowerCase().includes(q)).slice(0,12);
    if (!matches.length) {
        pubResults.innerHTML = '<div class="live-item" style="color:var(--muted)">Ничего не найдено</div>';
    } else {
        pubResults.innerHTML = matches.map(p =>
            `<div class="live-item" onclick="choosePub(${p.publication_id},'${p.title.replace(/'/g,"\\'")}','${p.type_name}')">
                <div>
                    <div class="live-item-title">${p.title}</div>
                    <div class="live-item-meta">${p.type_name}</div>
                </div>
            </div>`
        ).join('');
    }
    pubResults.classList.add('open');
});
function choosePub(id, title, type) {
    pubHidden.value = id;
    pubSearch.value = title;
    pubChosen.innerHTML = `<b>${title}</b> <span style="color:var(--muted)">(${type})</span>`;
    pubResults.classList.remove('open');
}
document.addEventListener('click', e => { if(!e.target.closest('.pub-search-wrap')) pubResults.classList.remove('open'); });

// Каскадные сотрудники
document.getElementById('fund-select').addEventListener('change', async function() {
    const fid = this.value;
    if (!fid) return;
    const resp = await fetch('add_replenishment.php?ajax_emp_by_fund='+fid);
    const emps = await resp.json();
    const sel = document.getElementById('emp-select');
    sel.innerHTML = '<option value="">— выбрать —</option>';
    emps.forEach(e => {
        const o = new Option(`${e.last_name} · ${e.lib}`, e.employee_id);
        sel.appendChild(o);
    });
});
</script>
</body>
</html>

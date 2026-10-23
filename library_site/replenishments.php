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

// --- AJAX: удалить пополнение ---
if (isset($_POST['action']) && $_POST['action'] === 'delete_rep') {
    $id = (int)($_POST['rep_id'] ?? 0);
    try {
        // сначала восстанавливаем счётчик фонда
        $info = mysqli_query($conn,
            "SELECT fr.fund_id, fr.quantity_added, lt.type_name
             FROM пополнение_фондов fr
             JOIN публикации p ON fr.publication_id = p.publication_id
             JOIN типы_литературы lt ON p.literature_type_id = lt.type_id
             WHERE fr.rep_id = $id");
        $inf = mysqli_fetch_assoc($info);
        if ($inf) {
            $qty = (int)$inf['quantity_added'];
            $fid = (int)$inf['fund_id'];
            $tn  = strtolower($inf['type_name']);
            if (str_contains($tn,'журнал'))
                mysqli_query($conn,"UPDATE фонды SET total_journals=GREATEST(0,COALESCE(total_journals,0)-$qty) WHERE fund_id=$fid");
            elseif (str_contains($tn,'газет'))
                mysqli_query($conn,"UPDATE фонды SET total_newspapers=GREATEST(0,COALESCE(total_newspapers,0)-$qty) WHERE fund_id=$fid");
            else
                mysqli_query($conn,"UPDATE фонды SET total_books=GREATEST(0,COALESCE(total_books,0)-$qty) WHERE fund_id=$fid");
        }
        $st = mysqli_prepare($conn,"DELETE FROM пополнение_фондов WHERE rep_id=?");
        mysqli_stmt_bind_param($st,'i',$id);
        mysqli_stmt_execute($st);
        echo json_encode(['ok'=>true]);
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    exit;
}

// --- AJAX: сохранить изменения пополнения ---
if (isset($_POST['action']) && $_POST['action'] === 'edit_rep') {
    $id      = (int)$_POST['rep_id'];
    $date    = trim($_POST['rep_date']  ?? '');
    $emp_id  = (int)$_POST['emp_id'];
    $pub_id  = (int)$_POST['pub_id'];
    $fund_id = (int)$_POST['fund_id'];
    $qty     = (int)$_POST['quantity'];
    try {
        $st = mysqli_prepare($conn,
            "UPDATE пополнение_фондов SET replenishment_date=?,employee_id=?,publication_id=?,fund_id=?,quantity_added=?
             WHERE rep_id=?");
        mysqli_stmt_bind_param($st,'siiii i',$date,$emp_id,$pub_id,$fund_id,$qty,$id);
        // fix bind_param string
        $st2 = mysqli_prepare($conn,
            "UPDATE пополнение_фондов SET replenishment_date=?,employee_id=?,publication_id=?,fund_id=?,quantity_added=?
             WHERE rep_id=?");
        mysqli_stmt_bind_param($st2,'siiiii',$date,$emp_id,$pub_id,$fund_id,$qty,$id);
        mysqli_stmt_execute($st2);
        echo json_encode(['ok'=>true]);
    } catch (Exception $e) {
        echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    }
    exit;
}

$date_from = isset($_GET['from']) ? $_GET['from'] : date('Y-01-01');
$date_to   = isset($_GET['to'])   ? $_GET['to']   : date('Y-m-d');

$stmt = mysqli_prepare($conn, "CALL пр_ОтчетПополнений(?, ?)");
mysqli_stmt_bind_param($stmt, 'ss', $date_from, $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rows = [];
$total = 0;
while ($r = mysqli_fetch_assoc($result)) { $rows[] = $r; $total += $r['Кол-во']; }
mysqli_next_result($conn);

// Получаем rep_id для каждой записи
$rep_ids = [];
$rq = mysqli_query($conn,
    "SELECT fr.rep_id, fr.replenishment_date, fr.employee_id, fr.publication_id, fr.fund_id, fr.quantity_added,
            e.last_name, p.title, f.fund_name
     FROM пополнение_фондов fr
     JOIN сотрудники e ON fr.employee_id=e.employee_id
     JOIN публикации p ON fr.publication_id=p.publication_id
     JOIN фонды f ON fr.fund_id=f.fund_id
     WHERE fr.replenishment_date BETWEEN '$date_from' AND '$date_to'
     ORDER BY fr.replenishment_date DESC");
$rep_full = [];
while ($r2 = mysqli_fetch_assoc($rq)) $rep_full[] = $r2;

// Дополнительные данные для модалки
$all_emps = mysqli_query($conn,"SELECT employee_id, last_name FROM сотрудники ORDER BY last_name");
$emps_arr = [];
while ($e = mysqli_fetch_assoc($all_emps)) $emps_arr[] = $e;

$all_pubs = mysqli_query($conn,"SELECT publication_id, title FROM публикации ORDER BY title");
$pubs_arr = [];
while ($p = mysqli_fetch_assoc($all_pubs)) $pubs_arr[] = $p;

$all_funds = mysqli_query($conn,
    "SELECT f.fund_id, f.fund_name, b.name AS lib FROM фонды f JOIN библиотеки b ON f.library_id=b.library_id ORDER BY f.fund_name");
$funds_arr = [];
while ($f2 = mysqli_fetch_assoc($all_funds)) $funds_arr[] = $f2;

$by_fund = [];
foreach ($rows as $r) { $by_fund[$r['Фонд']] = ($by_fund[$r['Фонд']]??0)+$r['Кол-во']; }
arsort($by_fund);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Пополнения фондов · USM</title>
<?php include 'head.php'; ?>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container" style="padding-top:28px">
    <div class="section-title">Пополнения фондов
        <a href="add_replenishment.php" class="btn btn-green btn-sm" style="margin-left:auto">+ Зарегистрировать поступление</a>
    </div>

    <form class="search-bar" method="get">
        <div class="form-group">
            <label>Дата с</label>
            <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>">
        </div>
        <div class="form-group">
            <label>Дата по</label>
            <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>">
        </div>
        <button class="btn" type="submit">Показать</button>
        <a class="btn btn-ghost btn-sm" href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-m-d') ?>">Этот год</a>
        <a class="btn btn-ghost btn-sm" href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>">Этот месяц</a>
        <a class="btn btn-ghost btn-sm" href="?from=2023-01-01&to=2023-12-31">2023</a>
        <a class="btn btn-ghost btn-sm" href="?from=2024-01-01&to=2024-12-31">2024</a>
    </form>

    <?php if (!empty($rows)): ?>
    <div style="display:flex;gap:14px;margin-bottom:22px;flex-wrap:wrap">
        <div class="stat-card" style="flex:1;min-width:120px;padding:14px">
            <div class="stat-num" style="font-size:26px"><?= count($rows) ?></div>
            <div class="stat-label">операций</div>
        </div>
        <div class="stat-card" style="flex:1;min-width:120px;padding:14px">
            <div class="stat-num" style="font-size:26px"><?= number_format($total,0,'','&nbsp;') ?></div>
            <div class="stat-label">единиц добавлено</div>
        </div>
        <?php if (!empty($by_fund)): $max=max(array_values($by_fund)); ?>
        <div class="stat-card" style="flex:2;min-width:220px;text-align:left;padding:14px 18px">
            <div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;font-weight:500">Топ фондов</div>
            <?php foreach (array_slice($by_fund,0,4) as $fname=>$cnt): ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;font-size:12px">
                <div style="flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:160px"><?= htmlspecialchars($fname) ?></div>
                <div style="flex:2;background:var(--warm);border-radius:3px;height:8px;overflow:hidden">
                    <div style="height:100%;background:var(--gold);width:<?= round($cnt/$max*100) ?>%"></div>
                </div>
                <div style="color:var(--teal);font-weight:600;width:32px;text-align:right"><?= $cnt ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="result-count">Результатов: <b><?= count($rows) ?></b> · <?= htmlspecialchars($date_from) ?> — <?= htmlspecialchars($date_to) ?></div>

    <?php if (empty($rows)): ?>
        <div class="empty">Пополнений за указанный период не найдено.
            <br><a href="add_replenishment.php" class="btn btn-green btn-sm" style="margin-top:14px;display:inline-block">Зарегистрировать поступление</a>
        </div>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr>
            <th>Дата</th><th>Фонд</th><th>Город</th><th>Публикация</th><th>Принял</th><th>Кол-во</th><th></th>
        </tr></thead>
        <tbody>
        <?php
        // Совмещаем rows с rep_full по индексу
        for ($i=0; $i<count($rows); $i++):
            $r = $rows[$i];
            $rf = $rep_full[$i] ?? null;
            $rid = $rf['rep_id'] ?? 0;
        ?>
        <tr class="clickable" onclick="openRepEdit(<?= $rid ?>,
            '<?= addslashes($r['Дата']) ?>',
            <?= $rf['employee_id']??0 ?>,
            <?= $rf['publication_id']??0 ?>,
            <?= $rf['fund_id']??0 ?>,
            <?= $r['Кол-во'] ?>)">
            <td><?= htmlspecialchars($r['Дата']) ?></td>
            <td><?= htmlspecialchars($r['Фонд']) ?></td>
            <td><?= htmlspecialchars($r['Город']) ?></td>
            <td><b><?= htmlspecialchars($r['Книга']) ?></b></td>
            <td><?= htmlspecialchars($r['Сотрудник']) ?></td>
            <td><b style="color:var(--teal)"><?= $r['Кол-во'] ?></b></td>
            <td style="width:32px;text-align:center;color:var(--muted);font-size:14px">✎</td>
        </tr>
        <?php endfor; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Модалка пополнения -->
<div class="modal-bg" id="rep-modal">
    <div class="modal">
        <h3>Редактировать запись</h3>
        <div id="rep-msg"></div>
        <input type="hidden" id="rm-id">
        <div class="form-row" style="margin-bottom:12px">
            <div class="form-group-v">
                <label>Дата</label>
                <input type="date" id="rm-date">
            </div>
            <div class="form-group-v">
                <label>Количество</label>
                <input type="number" id="rm-qty" min="1">
            </div>
        </div>
        <div class="form-full" style="margin-bottom:12px">
            <div class="form-group-v">
                <label>Публикация</label>
                <select id="rm-pub">
                    <?php foreach ($pubs_arr as $p): ?>
                    <option value="<?= $p['publication_id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row" style="margin-bottom:12px">
            <div class="form-group-v">
                <label>Сотрудник</label>
                <select id="rm-emp">
                    <?php foreach ($emps_arr as $e): ?>
                    <option value="<?= $e['employee_id'] ?>"><?= htmlspecialchars($e['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-v">
                <label>Фонд</label>
                <select id="rm-fund">
                    <?php foreach ($funds_arr as $f2): ?>
                    <option value="<?= $f2['fund_id'] ?>"><?= htmlspecialchars($f2['fund_name']) ?> · <?= htmlspecialchars($f2['lib']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-green" onclick="saveRep()">Сохранить</button>
            <button class="btn btn-red"   onclick="deleteRep()">Удалить</button>
            <button class="btn btn-ghost" onclick="closeRepModal()" style="margin-left:auto">Отмена</button>
        </div>
    </div>
</div>

<footer>Библиотечная система USM · База данных MySQL</footer>

<script>
function openRepEdit(rid, date, empId, pubId, fundId, qty) {
    document.getElementById('rm-id').value   = rid;
    document.getElementById('rm-date').value = date;
    document.getElementById('rm-qty').value  = qty;
    document.getElementById('rm-emp').value  = empId;
    document.getElementById('rm-pub').value  = pubId;
    document.getElementById('rm-fund').value = fundId;
    document.getElementById('rep-msg').innerHTML = '';
    document.getElementById('rep-modal').classList.add('open');
}
function closeRepModal() { document.getElementById('rep-modal').classList.remove('open'); }
document.getElementById('rep-modal').addEventListener('click', function(e){ if(e.target===this)closeRepModal(); });

async function saveRep() {
    const fd = new FormData();
    fd.append('action',   'edit_rep');
    fd.append('rep_id',   document.getElementById('rm-id').value);
    fd.append('rep_date', document.getElementById('rm-date').value);
    fd.append('quantity', document.getElementById('rm-qty').value);
    fd.append('emp_id',   document.getElementById('rm-emp').value);
    fd.append('pub_id',   document.getElementById('rm-pub').value);
    fd.append('fund_id',  document.getElementById('rm-fund').value);
    const resp = await fetch('replenishments.php', {method:'POST',body:fd});
    const data = await resp.json();
    if (data.ok) location.reload();
    else document.getElementById('rep-msg').innerHTML=`<div class="msg error" style="margin-bottom:12px">${data.msg}</div>`;
}

async function deleteRep() {
    if (!confirm('Удалить запись о пополнении? Счётчик фонда будет скорректирован.')) return;
    const fd = new FormData();
    fd.append('action', 'delete_rep');
    fd.append('rep_id', document.getElementById('rm-id').value);
    const resp = await fetch('replenishments.php', {method:'POST',body:fd});
    const data = await resp.json();
    if (data.ok) location.reload();
    else document.getElementById('rep-msg').innerHTML=`<div class="msg error" style="margin-bottom:12px">${data.msg}</div>`;
}
</script>
</body>
</html>

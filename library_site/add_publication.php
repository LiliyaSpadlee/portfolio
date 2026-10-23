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

// Добавить нового издателя
if (isset($_POST['action']) && $_POST['action'] === 'add_publisher') {
    $name = trim($_POST['new_publisher'] ?? '');
    if ($name) {
        try {
            $st = mysqli_prepare($conn,"INSERT INTO издатели (name) VALUES (?)");
            mysqli_stmt_bind_param($st,'s',$name);
            mysqli_stmt_execute($st);
            $new_pub_id = mysqli_insert_id($conn);
            echo json_encode(['ok'=>true,'id'=>$new_pub_id,'name'=>$name]);
        } catch (Exception $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
    } else { echo json_encode(['ok'=>false,'msg'=>'Введите название']); }
    exit;
}

// Добавить новый тип литературы
if (isset($_POST['action']) && $_POST['action'] === 'add_type') {
    $name = trim($_POST['new_type'] ?? '');
    if ($name) {
        try {
            $st = mysqli_prepare($conn,"INSERT INTO типы_литературы (type_name) VALUES (?)");
            mysqli_stmt_bind_param($st,'s',$name);
            mysqli_stmt_execute($st);
            $new_type_id = mysqli_insert_id($conn);
            echo json_encode(['ok'=>true,'id'=>$new_type_id,'name'=>$name]);
        } catch (Exception $e) { echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
    } else { echo json_encode(['ok'=>false,'msg'=>'Введите название']); }
    exit;
}

$types      = mysqli_query($conn,"SELECT type_id, type_name FROM типы_литературы ORDER BY type_name");
$publishers = mysqli_query($conn,"SELECT publisher_id, name FROM издатели ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $type_id  = (int)($_POST['type_id'] ?? 0);
    $pub_id   = (int)($_POST['pub_id']  ?? 0);
    $pub_date = trim($_POST['pub_date'] ?? '');

    if (!$title || !$type_id || !$pub_id || !$pub_date) {
        $message  = 'Заполните все обязательные поля: Название, Тип, Издатель, Дата.';
        $msg_type = 'error';
    } else {
        try {
            $st = mysqli_prepare($conn,
                "INSERT INTO публикации (title, literature_type_id, publisher_id, publication_date) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($st,'siis',$title,$type_id,$pub_id,$pub_date);
            mysqli_stmt_execute($st);
            $new_id  = mysqli_insert_id($conn);
            $message  = "Публикация «{$title}» добавлена.";
            $msg_type = 'success';
        } catch (Exception $e) {
            $message  = 'Ошибка: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

// Перечитываем после возможных добавлений
$types_arr = [];
mysqli_data_seek($types,0);
while ($t = mysqli_fetch_assoc($types)) $types_arr[] = $t;
$pubs_arr2 = [];
mysqli_data_seek($publishers,0);
while ($p = mysqli_fetch_assoc($publishers)) $pubs_arr2[] = $p;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Добавить публикацию · USM</title>
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
    <div class="section-title">Добавить публикацию</div>

    <?php if ($message): ?>
    <div class="msg <?= $msg_type ?>"><?= htmlspecialchars($message) ?>
        <?php if ($msg_type==='success'): ?>
        · <a href="catalog.php" style="color:inherit;font-weight:600">Открыть каталог</a>
        · <a href="add_replenishment.php" style="color:inherit;font-weight:600">Зарегистрировать поступление</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post">
            <div class="form-full">
                <div class="form-group-v">
                    <label>Название *</label>
                    <input type="text" name="title" placeholder="Полное название публикации" required
                           value="<?= htmlspecialchars($_POST['title']??'') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-v">
                    <label>Тип литературы *</label>
                    <div style="display:flex;gap:6px">
                        <select name="type_id" id="type-select" required style="flex:1">
                            <option value="">— выбрать —</option>
                            <?php foreach ($types_arr as $t): ?>
                            <option value="<?= $t['type_id'] ?>" <?= ($_POST['type_id']??0)==$t['type_id']?'selected':'' ?>>
                                <?= htmlspecialchars($t['type_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="showAddType()" title="Добавить тип">+</button>
                    </div>
                    <div id="add-type-wrap" style="display:none;margin-top:6px;display:none;gap:6px">
                        <input type="text" id="new-type-input" placeholder="Новый тип..." style="flex:1;padding:6px 10px;border:1px solid var(--border);border-radius:5px;font-size:13px">
                        <button type="button" class="btn btn-green btn-sm" onclick="submitNewType()">Добавить</button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="hideAddType()">Отмена</button>
                    </div>
                </div>
                <div class="form-group-v">
                    <label>Издатель *</label>
                    <div style="display:flex;gap:6px">
                        <select name="pub_id" id="pub-select" required style="flex:1">
                            <option value="">— выбрать —</option>
                            <?php foreach ($pubs_arr2 as $p): ?>
                            <option value="<?= $p['publisher_id'] ?>" <?= ($_POST['pub_id']??0)==$p['publisher_id']?'selected':'' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="showAddPub()" title="Добавить издателя">+</button>
                    </div>
                    <div id="add-pub-wrap" style="display:none;margin-top:6px;gap:6px">
                        <input type="text" id="new-pub-input" placeholder="Новый издатель..." style="flex:1;padding:6px 10px;border:1px solid var(--border);border-radius:5px;font-size:13px">
                        <button type="button" class="btn btn-green btn-sm" onclick="submitNewPub()">Добавить</button>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="hideAddPub()">Отмена</button>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-v">
                    <label>Дата издания *</label>
                    <input type="date" name="pub_date" id="pub-date" required
                           value="<?= htmlspecialchars($_POST['pub_date']??date('Y-m-d')) ?>">
                </div>
                <div class="form-group-v">
                    <label>Статус</label>
                    <div id="status-preview" style="padding:8px 11px;background:var(--warm);border-radius:5px;font-size:13px;margin-top:1px">—</div>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:6px">
                <button class="btn btn-green" type="submit">Добавить публикацию</button>
                <a class="btn btn-ghost" href="catalog.php">Отмена</a>
            </div>
        </form>
    </div>
</div>

<footer>Библиотечная система USM · База данных MySQL</footer>

<script>
// Статус по дате
const dateInput = document.getElementById('pub-date');
const preview   = document.getElementById('status-preview');
function updatePreview() {
    const d = new Date(dateInput.value);
    if (isNaN(d)) { preview.innerHTML='—'; return; }
    const yr = d.getFullYear(), now = new Date().getFullYear();
    let badge, label;
    if (yr >= now-2)       { badge='badge-new';  label='Новинка'; }
    else if (yr >= now-10) { badge='badge-ok';   label='Актуальное'; }
    else                   { badge='badge-arch'; label='Архив'; }
    preview.innerHTML = `<span class="badge ${badge}">${label}</span>`;
}
dateInput.addEventListener('change', updatePreview);
updatePreview();

// Добавить тип
function showAddType() { const w=document.getElementById('add-type-wrap'); w.style.display='flex'; }
function hideAddType() { document.getElementById('add-type-wrap').style.display='none'; }
async function submitNewType() {
    const val = document.getElementById('new-type-input').value.trim();
    if (!val) return;
    const fd = new FormData(); fd.append('action','add_type'); fd.append('new_type',val);
    const resp = await fetch('add_publication.php',{method:'POST',body:fd});
    const data = await resp.json();
    if (data.ok) {
        const sel = document.getElementById('type-select');
        const opt = new Option(data.name, data.id, true, true);
        sel.appendChild(opt);
        hideAddType();
        document.getElementById('new-type-input').value='';
    } else alert(data.msg);
}

// Добавить издателя
function showAddPub() { const w=document.getElementById('add-pub-wrap'); w.style.display='flex'; }
function hideAddPub() { document.getElementById('add-pub-wrap').style.display='none'; }
async function submitNewPub() {
    const val = document.getElementById('new-pub-input').value.trim();
    if (!val) return;
    const fd = new FormData(); fd.append('action','add_publisher'); fd.append('new_publisher',val);
    const resp = await fetch('add_publication.php',{method:'POST',body:fd});
    const data = await resp.json();
    if (data.ok) {
        const sel = document.getElementById('pub-select');
        const opt = new Option(data.name, data.id, true, true);
        sel.appendChild(opt);
        hideAddPub();
        document.getElementById('new-pub-input').value='';
    } else alert(data.msg);
}
</script>
</body>
</html>

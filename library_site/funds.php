<?php
session_start();
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$rows = [];
$r = mysqli_query($conn, "SELECT * FROM библиотека_статистика ORDER BY Всего_книг DESC");
while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;

$funds_r = mysqli_query($conn,
    "SELECT f.fund_id, f.fund_name, b.name AS lib, b.city,
            COALESCE(f.total_books,0) AS total_books,
            COALESCE(f.total_journals,0) AS total_journals,
            COALESCE(f.total_newspapers,0) AS total_newspapers
     FROM фонды f JOIN библиотеки b ON f.library_id = b.library_id ORDER BY f.total_books DESC");
$funds = [];
while ($f = mysqli_fetch_assoc($funds_r)) $funds[] = $f;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Фонды · USM</title>
<?php include 'head.php'; ?>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container" style="padding-top:28px">
    <div class="section-title">Фонды библиотек
        <a href="add_replenishment.php" class="btn btn-green btn-sm" style="margin-left:auto">+ Пополнить фонд</a>
    </div>

    <div class="table-wrap" style="margin-bottom:36px">
    <table>
        <thead><tr>
            <th>Библиотека</th><th>Город</th><th>Сотрудников</th><th>Книг</th><th>Журналов</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
            <td><b><?= htmlspecialchars($r['Библиотека']) ?></b></td>
            <td><?= htmlspecialchars($r['Город']) ?></td>
            <td><?= $r['Кол-во_сотрудников'] ?></td>
            <td><?= number_format($r['Всего_книг'],0,'','&nbsp;') ?></td>
            <td><?= number_format($r['Всего_журналов'],0,'','&nbsp;') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <div class="section-title">Детали по фондам</div>

    <div style="margin-bottom:10px">
        <input type="text" id="fund-filter" placeholder="Фильтр по названию фонда..."
               style="padding:7px 11px;border:1px solid var(--border);border-radius:5px;font-size:13px;width:260px">
    </div>

    <div class="table-wrap">
    <table id="funds-table">
        <thead><tr>
            <th>Фонд</th><th>Библиотека</th><th>Город</th>
            <th>Книги</th><th>Журналы</th><th>Газеты</th><th>Соотношение</th>
        </tr></thead>
        <tbody>
        <?php foreach ($funds as $f):
            $total = $f['total_books'] + $f['total_journals'] + $f['total_newspapers'];
            $b_pct = $total ? round($f['total_books']/$total*100) : 0;
            $j_pct = $total ? round($f['total_journals']/$total*100) : 0;
            $n_pct = $total ? 100 - $b_pct - $j_pct : 0;
        ?>
        <tr>
            <td><b><?= htmlspecialchars($f['fund_name']) ?></b></td>
            <td><?= htmlspecialchars($f['lib']) ?></td>
            <td><?= htmlspecialchars($f['city']) ?></td>
            <td><?= number_format($f['total_books'],0,'','&nbsp;') ?></td>
            <td><?= $f['total_journals'] ?: '—' ?></td>
            <td><?= $f['total_newspapers'] ?: '—' ?></td>
            <td style="min-width:130px">
                <?php if ($total > 0): ?>
                <div style="display:flex;height:10px;border-radius:5px;overflow:hidden;gap:1px;margin-bottom:4px">
                    <?php if ($b_pct): ?><div style="background:var(--gold);width:<?= $b_pct ?>%"></div><?php endif; ?>
                    <?php if ($j_pct): ?><div style="background:var(--teal);width:<?= $j_pct ?>%"></div><?php endif; ?>
                    <?php if ($n_pct): ?><div style="background:var(--muted);width:<?= $n_pct ?>%"></div><?php endif; ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Легенда без квадратиков-эмодзи, увеличенный шрифт -->
    <div style="margin-top:14px;font-size:13px;color:var(--muted);display:flex;gap:20px;flex-wrap:wrap">
        <span style="display:flex;align-items:center;gap:6px">
            <span style="display:inline-block;width:16px;height:10px;background:var(--gold);border-radius:3px"></span>
            Книги
        </span>
        <span style="display:flex;align-items:center;gap:6px">
            <span style="display:inline-block;width:16px;height:10px;background:var(--teal);border-radius:3px"></span>
            Журналы
        </span>
        <span style="display:flex;align-items:center;gap:6px">
            <span style="display:inline-block;width:16px;height:10px;background:var(--muted);border-radius:3px"></span>
            Газеты
        </span>
    </div>
</div>

<footer>Библиотечная система USM · База данных MySQL</footer>

<script>
document.getElementById('fund-filter').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#funds-table tbody tr').forEach(row => {
        const text = row.cells[0].textContent.toLowerCase() + row.cells[1].textContent.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});
</script>
</body>
</html>

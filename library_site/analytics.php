<?php
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'staff';

$data = [];
if ($tab === 'staff') {
    $r = mysqli_query($conn, "SELECT * FROM v_квалификация_по_филиалам ORDER BY `% Профессионализма` DESC");
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
} elseif ($tab === 'budget') {
    $r = mysqli_query($conn, "SELECT * FROM v_бюджетпогородам ORDER BY `Средняя зарплата` DESC");
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
} elseif ($tab === 'funds') {
    $r = mysqli_query($conn, "SELECT * FROM v_статистикапополнений ORDER BY `Всего книг добавлено` DESC");
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
} elseif ($tab === 'libstat') {
    $r = mysqli_query($conn, "SELECT * FROM библиотека_статистика ORDER BY Всего_книг DESC");
    while ($row = mysqli_fetch_assoc($r)) $data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Аналитика · USM</title>
<?php include 'head.php'; ?>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container" style="padding-top:28px">
    <div class="section-title">Аналитика</div>

    <div class="tabs">
        <a class="tab <?= $tab==='staff'  ?'active':'' ?>" href="?tab=staff">Квалификация</a>
        <a class="tab <?= $tab==='budget' ?'active':'' ?>" href="?tab=budget">Бюджет по городам</a>
        <a class="tab <?= $tab==='funds'  ?'active':'' ?>" href="?tab=funds">Статистика пополнений</a>
        <a class="tab <?= $tab==='libstat'?'active':'' ?>" href="?tab=libstat">Сводка библиотек</a>
    </div>

    <?php if (empty($data)): ?>
        <div class="empty">Нет данных</div>
    <?php else: ?>

    <?php if ($tab === 'budget' && isset($data[0]['Средняя зарплата'])):
        $max_s = max(array_column($data,'Средняя зарплата')); ?>
    <div class="stat-card" style="margin-bottom:20px;text-align:left;padding:18px 22px">
        <div style="font-size:13px;font-weight:600;margin-bottom:14px">Средняя зарплата по городам</div>
        <?php foreach ($data as $d): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;font-size:13px">
            <div style="width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($d['Город'] ?? array_values($d)[0]) ?>
            </div>
            <div style="flex:1;background:var(--warm);border-radius:3px;height:12px;overflow:hidden">
                <div style="height:100%;background:var(--gold);width:<?= round($d['Средняя зарплата']/$max_s*100) ?>%"></div>
            </div>
            <div style="font-weight:600;width:100px;text-align:right"><?= number_format($d['Средняя зарплата'],0,'.','&nbsp;') ?> ₽</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="table-wrap">
    <table>
        <thead><tr>
            <?php foreach (array_keys($data[0]) as $col): ?>
            <th><?= htmlspecialchars($col) ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php foreach ($data as $row): ?>
        <tr>
            <?php foreach ($row as $key => $val): ?>
            <td>
                <?php
                if (str_contains($key,'зарплата') || str_contains($key,'Зарплата')) {
                    echo number_format((float)$val,0,'.','&nbsp;') . ' ₽';
                } elseif (str_contains($key,'%')) {
                    $pct = (float)$val;
                    $color = $pct>=80 ? 'var(--teal)' : ($pct>=50 ? 'var(--gold)' : 'var(--red)');
                    echo "<b style=\"color:$color\">{$val}%</b>";
                } else {
                    echo htmlspecialchars($val ?? '—');
                }
                ?>
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<footer>Библиотечная система USM · База данных MySQL</footer>
</body>
</html>

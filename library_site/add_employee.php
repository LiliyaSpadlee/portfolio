<?php
session_start();
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$libs = mysqli_query($conn, "SELECT library_id, name, city FROM библиотеки ORDER BY city");
$message  = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name  = trim($_POST['last_name']  ?? '');
    $position   = trim($_POST['position']   ?? '');
    $birth_year = (int)($_POST['birth_year']  ?? 0);
    $emp_year   = (int)($_POST['emp_year']    ?? date('Y'));
    $education  = trim($_POST['education']  ?? '');
    $salary     = (float)($_POST['salary']  ?? 0);
    $lib_id     = (int)($_POST['lib_id']    ?? 0);

    if (!$last_name || !$lib_id || $salary <= 0) {
        $message  = 'Заполни обязательные поля: Фамилия, Библиотека, Зарплата.';
        $msg_type = 'error';
    } else {
        $stmt = mysqli_prepare($conn, "CALL пр_ДобавитьСотрудника(?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ssiisdi',
            $last_name, $position, $birth_year, $emp_year, $education, $salary, $lib_id);
        if (mysqli_stmt_execute($stmt)) {
            $message  = "✅ Сотрудник «{$last_name}» успешно добавлен!";
            $msg_type = 'success';
            mysqli_next_result($conn);
        } else {
            $err = mysqli_error($conn);
            $message  = "Ошибка: " . $err;
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Добавить сотрудника · USM</title>
<?php include 'head.php'; ?>
<style>
        /* Прицеливаемся строго в анкету, которая на строке 65 */
        .form-card {
            max-width: 600px !important; /* Не даем ей растягиваться на весь экран */
            margin: 30px auto !important; /* auto за шкирку двигает её ровно в центр */
            background: #fff;            /* Делаем белый фон анкеты */
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        /* Центрируем блок с сообщением об успехе (строка 57) */
        .msg {
            max-width: 600px !important;
            margin: 15px auto !important;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container" style="padding-top:32px">
    <div class="section-title"> Добавить сотрудника
    </div>

    <?php if ($message): ?>
    <div class="msg <?= $msg_type ?>"><?= htmlspecialchars($message) ?>
        <?php if ($msg_type === 'success'): ?>
        · <a href="employees.php" style="color:inherit;font-weight:600">Открыть список сотрудников →</a>
        · <a href="add_employee.php" style="color:inherit;font-weight:600">Добавить ещё →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post">
            <div class="form-row">
                <div class="form-group-v">
                    <label>Фамилия *</label>
                    <input type="text" name="last_name" placeholder="Иванов" required>
                </div>
                <div class="form-group-v">
                    <label>Должность</label>
                    <input type="text" name="position" placeholder="Библиотекарь" list="positions-list">
                    <datalist id="positions-list">
                        <option>Библиотекарь</option>
                        <option>Старший библиотекарь</option>
                        <option>Заведующий</option>
                        <option>Главный библиограф</option>
                        <option>Хранитель фондов</option>
                        <option>Методист</option>
                    </datalist>
                    
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-v">
                    <label>Год рождения</label>
                    <input type="number" name="birth_year" min="1950" max="2005" placeholder="1990">
                    <div class="hint" id="age-hint"></div>
                </div>
                <div class="form-group-v">
                    <label>Год найма</label>
                    <input type="number" name="emp_year" id="emp-year-input"
                           min="1990" max="<?= date('Y') ?>" value="<?= date('Y') ?>">
                    <div class="hint" id="exp-hint"></div>
                </div>
            </div>
            <div class="form-full">
                <div class="form-group-v">
                    <label>Образование</label>
                    <select name="education">
                        <option value="">— не указано —</option>
                        <option>Высшее образование</option>
                        <option>Среднее профессиональное</option>
                        <option>Среднее специальное</option>
                        <option>Среднее общее</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group-v">
                    <label>Зарплата (₽) *</label>
                    <input type="number" name="salary" id="salary-input"
                           min="35000" max="200000" placeholder="30000" required>
                </div>
                <div class="form-group-v">
                    <label>Библиотека *</label>
                    <select name="lib_id" required>
                        <option value="">— выбрать —</option>
                        <?php while ($l = mysqli_fetch_assoc($libs)): ?>
                        <option value="<?= $l['library_id'] ?>"><?= htmlspecialchars($l['name']) ?> (<?= $l['city'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px">
                <button class="btn btn-green" type="submit">Добавить сотрудника</button>
                <a class="btn btn-teal" href="employees.php">← К списку</a>
            </div>
        </form>
    </div>

</div>

<footer>Библиотечная система USM · База данных MySQL · XAMPP</footer>

</body>
</html>

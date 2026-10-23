<?php
session_start(); // Обязательно добавляем на первую строчку!
require_once 'db.php';

$is_admin = isset($_SESSION['auth']) && $_SESSION['auth'] === true;

// ЛОГИКА ИЗМЕНЕНИЯ СТАТУСА (только для админа)
// Обработка изменения статуса книги через AJAX
if (isset($_GET['action']) && isset($_GET['id'])) {
    $book_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'borrow') {
        mysqli_query($conn, "UPDATE `публикации` SET `status` = 'borrowed' WHERE `publication_id` = $book_id");
    } elseif ($action === 'return') {
        mysqli_query($conn, "UPDATE `публикации` SET `status` = 'available' WHERE `publication_id` = $book_id");
    }
    
    // Просто останавливаем выполнение скрипта, возвращая успешный статус для AJAX
    exit;
}

// Логика поиска
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Сложный SQL-запрос для вытягивания названий из связанных таблиц
$sql = "SELECT p.*, t.type_name, pub.name AS publisher_name
        FROM `публикации` p
        LEFT JOIN `типы_литературы` t ON p.literature_type_id = t.type_id
        LEFT JOIN `издатели` pub ON p.publisher_id = pub.publisher_id";

if ($search !== '') {
    $search_clean = mysqli_real_escape_string($conn, $search);
    $sql .= " WHERE p.title LIKE '%$search_clean%' 
              OR t.type_name LIKE '%$search_clean%' 
              OR pub.name LIKE '%$search_clean%'";
}

$sql .= " ORDER BY p.publication_id DESC";
$result = mysqli_query($conn, $sql);

// Если запрос упал из-за неверных имен таблиц JOIN, делаем резервный простой запрос
if (!$result) {
    $sql = "SELECT * FROM `публикации` ORDER BY p.publication_id DESC";
    $result = mysqli_query($conn, $sql);
    $backup_mode = true;
} else {
    $backup_mode = false;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Каталог книг · USM</title>
<?php include 'head.php'; ?>
<style>
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
    }
    .status-free {
    background-color: transparent;
    color: #2e7d32;
    border: none;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.status-free::before {
    content: "●";
    color: #4caf50;
    font-size: 12px;
}

.status-busy {
    background-color: transparent;
    color: #c62828;
    border: none;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.status-busy::before {
    content: "●";
    color: #ef5350;
    font-size: 12px;
}

.search-container {
    background: var(--card);
    padding: 20px;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
}

.search-input {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 15px;
}

.btn-status {
    padding: 6px 14px;
    font-size: 13px;
    text-decoration: none;
    border-radius: 6px;
    margin-left: 10px;
    display: inline-block;
    font-weight: 500;
    transition: all 0.2s;
    background-color: #e3f2fd;
    color: #0d47a1;
    border: 1px solid #bbdefb;
}
.btn-status:hover {
    background-color: #2196f3;
    color: white;
    border-color: #2196f3;
}
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="container" style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 24px; color: var(--ink);">Каталог изданий</h1>
        <?php if ($is_admin): ?>
            <a href="add_publication.php" class="btn btn-green">+ Добавить публикацию</a>
        <?php endif; ?>
    </div>

    <div class="search-container">
        <form method="GET" action="catalog.php">
            <label style="display: block; margin-bottom: 6px; font-size: 12px; color: var(--muted); text-transform: uppercase;">Быстрый поиск по каталогу</label>
            <input type="text" name="search" class="search-input" placeholder="Начните вводить название книги..." value="<?= htmlspecialchars($search) ?>" oninput="if(this.value=='') this.form.submit();">
        </form>
    </div>

    <div style="font-size: 13px; color: var(--muted); margin-bottom: 10px;">
        Найдено: <b><?= mysqli_num_rows($result) ?></b> публикаций
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Название</th>
                <th>Тип</th>
                <th>Издатель</th>
                <th>Дата издания</th>
                <th style="text-align: center; width: 220px;">Статус</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                    // Привязываемся к структуре полей
                    $title = $row['title'] ?? '—';
                    $date = $row['publication_date'] ?? '—';
                    
                    // Если сработал умный JOIN, выводим имена, иначе ID-шники
                    $type = !$backup_mode ? ($row['type_name'] ?? '—') : 'ID: ' . ($row['literature_type_id'] ?? '—');
                    $publisher = !$backup_mode ? ($row['publisher_name'] ?? '—') : 'ID: ' . ($row['publisher_id'] ?? '—');
                    
                    // Статус из базы данных (available / borrowed)
                    $is_available = ($row['status'] === 'available');
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--ink);"><?= htmlspecialchars($title) ?></td>
                        <td><?= htmlspecialchars($type) ?></td>
                        <td><?= htmlspecialchars($publisher) ?></td>
                        <td><?= htmlspecialchars($date) ?></td>
                        <td style="text-align: center;">
                            <?php if ($row['status'] === 'available'): ?>
                                <span class="status-free">Свободна</span>
                                <!-- Показываем кнопку ТОЛЬКО если это админ -->
                                <?php if ($is_admin): ?>
                                    <a href="javascript:void(0);" class="btn-status" onclick="toggleBookStatus(this, <?= $row['publication_id'] ?>, 'borrow')">Выдать</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="status-busy">Занята</span>
                                <!-- Показываем кнопку ТОЛЬКО если это админ -->
                                <?php if ($is_admin): ?>
                                    <a href="javascript:void(0);" class="btn-status" onclick="toggleBookStatus(this, <?= $row['publication_id'] ?>, 'return')">Вернуть</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--muted); padding: 30px;">Ничего не найдено.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<footer>Библиотечная сеть USM · 2026</footer>
<script>
function toggleBookStatus(element, bookId, action) {
    const url = `catalog.php?action=${action}&id=${bookId}`;
    fetch(url, { method: 'GET' })
    .then(response => {
        if (response.ok) {
            const container = element.parentElement;
            if (action === 'borrow') {
                container.innerHTML = `
                    <span class="status-busy">Занята</span>
                    <a href="javascript:void(0);" class="btn-status" onclick="toggleBookStatus(this, ${bookId}, 'return')">Вернуть</a>
                `;
            } else {
                container.innerHTML = `
                    <span class="status-free">Свободна</span>
                    <a href="javascript:void(0);" class="btn-status" onclick="toggleBookStatus(this, ${bookId}, 'borrow')">Выдать</a>
                `;
            }
        }
    })
    .catch(err => console.error(err));
}
</script>
</body>
</html>
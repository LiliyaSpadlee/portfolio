<?php
$is_admin = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$current_page = basename($_SERVER['PHP_SELF']);

function nav_active($page) {
    global $current_page;
    return $current_page === $page ? 'class="active"' : '';
}
?>
<nav>
    <div class="nav-logo" onclick="window.location='index.php'" style="cursor:pointer">📚 USM</div>
    <a href="index.php" <?= nav_active('index.php') ?>>Главная</a>
    <a href="catalog.php" <?= nav_active('catalog.php') ?>>Каталог</a>
    
    <?php if ($is_admin): ?>
        <a href="employees.php" <?= nav_active('employees.php') ?>>Сотрудники</a>
        <a href="funds.php" <?= nav_active('funds.php') ?>>Фонды и Аналитика</a>
        <a href="replenishments.php" <?= nav_active('replenishments.php') ?>>Пополнения</a>
    <?php endif; ?>

    <div class="nav-actions">
        <?php if ($is_admin): ?>
            <a href="add_publication.php" <?= nav_active('add_publication.php') ?> class="nav-btn-add">+ Публикация</a>
            <a href="add_replenishment.php" <?= nav_active('add_replenishment.php') ?> class="nav-btn-add">+ Пополнение</a>
            <a href="add_employee.php" <?= nav_active('add_employee.php') ?> class="nav-btn-add">+ Сотрудник</a>
            <a href="login.php?action=logout" class="btn btn-sm" style="background: var(--red); color: white; margin-left: 10px; text-decoration: none;">Выйти</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-sm" style="background: var(--gold); color: white; text-decoration: none; padding: 6px 16px; border-radius: 4px;">Войти как Админ</a>
        <?php endif; ?>
    </div>
</nav>
<?php
session_start();
require_once 'db.php';

// Получаем общую статистику для главного экрана
$stat = mysqli_query($conn, "SELECT 
    (SELECT COUNT(*) FROM библиотеки) AS libs,
    (SELECT COUNT(*) FROM сотрудники) AS emps,
    (SELECT COUNT(*) FROM публикации) AS pubs,
    (SELECT COALESCE(SUM(total_books),0) FROM фонды) AS books
");
$s = mysqli_fetch_assoc($stat);

$is_admin = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Библиотечная сеть USM — Главная</title>
<?php include 'head.php'; ?>
<style>
    /* ГАРАНТИРОВАННОЕ ЦЕНТРИРОВАНИЕ ОСНОВНОГО КОНТЕНТА */
    .container {
        max-width: 1200px !important;
        margin-left: auto !important;
        margin-right: auto !important;
        padding-left: 20px !important;
        padding-right: 20px !important;
        box-sizing: border-box;
    }

    /* Главный баннер (Hero-блок) */
    .welcome-hero {
        background: linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), 
                    url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?q=80&w=1600&auto=format&fit=crop') center/cover;
        color: white;
        padding: 80px 0;
        text-align: center !important; /* Центрируем текст внутри баннера */
        border-bottom: 4px solid var(--gold);
        width: 100%;
    }
    .welcome-hero .container {
        display: flex;
        flex-direction: column;
        align-items: center; /* Центрируем кнопку и текст в контейнере баннера */
        text-align: center;
    }
    .welcome-hero h1 {
        font-size: 36px;
        font-weight: 700;
        color: var(--gold2);
        margin-bottom: 12px;
        letter-spacing: -0.5px;
    }
    .welcome-hero p {
        color: #cbd5e1;
        font-size: 16px;
        max-width: 650px;
        margin: 0 auto 24px auto !important; /* Магический auto центрирует абзац */
        line-height: 1.6;
    }
    
    /* Кнопка быстрого перехода в каталог */
    .hero-btn {
        display: inline-block;
        background: var(--gold);
        color: var(--ink);
        padding: 12px 28px;
        border-radius: var(--radius);
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: background 0.2s, transform 0.2s;
        margin: 0 auto;
    }
    .hero-btn:hover {
        background: var(--gold2);
        transform: translateY(-1px);
    }

    /* Заголовки городов */
    .city-heading {
        font-size: 24px;
        font-weight: 600;
        color: var(--ink);
        margin: 50px 0 20px 0;
        padding-bottom: 8px;
        border-bottom: 2px solid var(--border);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Сетка библиотек внутри города */
    .libraries-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
        justify-content: center; /* Центрируем карточки, если их меньше, чем на всю строку */
        margin-bottom: 20px;
    }

    /* Карточка библиотеки */
    .lib-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, border-color 0.2s;
    }
    .lib-card:hover {
        transform: translateY(-2px);
        border-color: var(--gold);
    }
    
    /* Фотография */
    .lib-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        background: #e2e8f0;
    }

    /* Контент карточки */
    .lib-body {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .lib-name {
        font-size: 18px;
        font-weight: 600;
        color: var(--ink);
        margin-bottom: 8px;
        line-height: 1.4;
    }
    .lib-address {
        font-size: 13px;
        color: var(--muted);
        margin-bottom: 16px;
        display: flex;
        align-items: flex-start;
        gap: 4px;
    }

    /* Блок фондов внутри карточки */
    .lib-funds {
        margin-top: auto;
        background: var(--warm);
        padding: 12px;
        border-radius: 6px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        text-align: center;
    }
    .fund-item {
        display: flex;
        flex-direction: column;
    }
    .fund-val {
        font-size: 14px;
        font-weight: 600;
        color: var(--ink);
    }
    .fund-lbl {
        font-size: 10px;
        color: var(--muted);
        text-transform: uppercase;
        margin-top: 2px;
    }

    /* Блок админки (внизу) */
    .admin-footer-box {
        margin: 60px 0;
        background: #fffcf5;
        border: 1px dashed var(--gold);
        padding: 24px;
        border-radius: var(--radius);
    }
    .admin-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
        margin-top: 14px;
    }
    .admin-card {
        background: white;
        border: 1px solid var(--border);
        padding: 16px;
        border-radius: var(--radius);
        text-decoration: none;
        color: inherit;
        box-shadow: var(--shadow);
        transition: border-color 0.2s;
    }
    .admin-card:hover {
        border-color: var(--gold);
    }
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="welcome-hero">
    <div class="container">
        <h1>Единая библиотечная система USM</h1>
        <p>Добро пожаловать на общедоступный портал нашей сети. Мы объединяем крупнейшие научные, публичные и детские фонды литературы по всей стране. Ищите издания и бронируйте их онлайн.</p>
        <a href="catalog.php" class="hero-btn">🔍 Перейти в каталог книг</a>
    </div>
</div>

<div class="container">

    <div class="city-heading">Москва</div>
    <div class="libraries-grid">
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Центральная им. Пушкина</div>
                <div class="lib-address">📍 ул. Пушкина, 10</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">15 200</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">2 800</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">950</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1568667256549-094345857637?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Фонд периодики им. Пушкина</div>
                <div class="lib-address">📍 ул. Пушкина, 10</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">0</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">3 100</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">400</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1614849963640-9cc74b2a826f?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Искусств и медиатехнологий</div>
                <div class="lib-address">📍 Адрес уточняется (обратитесь на горячую линию)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">4 300</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">850</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">310</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1549675584-91f19337af3d?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Филиал №3 "Северный"</div>
                <div class="lib-address">📍 Адрес уточняется (обратитесь на горячую линию)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">2 100</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">2 400</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">820</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="city-heading">Санкт-Петербург</div>
    <div class="libraries-grid">
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Центральная им. Маяковского</div>
                <div class="lib-address">📍 Центральный округ (адрес уточняется)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">16 500</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">3 400</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">1 200</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Библиотека им. В.В. Маяковского (Филиал)</div>
                <div class="lib-address">📍 наб. Фонтанки, 44</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">4 100</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">1 300</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">400</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1607964896090-dfd190628298?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Районная им. Гоголя</div>
                <div class="lib-address">📍 Среднеохтинский пр., 8</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">4 800</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">1 500</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">500</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="city-heading">🔬 Новосибирск</div>
    <div class="libraries-grid">
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1512820790803-83ca734da794?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Новосибирская научная</div>
                <div class="lib-address">📍 Городской центр (адрес уточняется)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">14 100</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">2 900</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">1 100</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Филиал "Академгородок"</div>
                <div class="lib-address">📍 Советский р-н (адрес уточняется)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">3 900</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">1 400</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">450</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="city-heading">Омск</div>
    <div class="libraries-grid">
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Омская областная научная</div>
                <div class="lib-address">📍 Центральный округ (адрес уточняется)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">12 800</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">1 100</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">200</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1502086223501-7ea6ecd79368?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Детская "Книжный остров"</div>
                <div class="lib-address">📍 Адрес уточняется (обратитесь на горячую линию)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">3 100</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">600</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">350</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1516979187457-637abb4f9353?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Городской читальный зал №2</div>
                <div class="lib-address">📍 Адрес уточняется (обратитесь на горячую линию)</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">1 500</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">2 900</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">1 100</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="city-heading">Уфа</div>
    <div class="libraries-grid">
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1561089489-f13d5e730d72?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Национальная им. Ахмет-Заки Валиди</div>
                <div class="lib-address">📍 ул. Ленина, 4</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">18 200</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">3 800</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">1 400</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1580536213826-f47922bad99a?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Центральная городская</div>
                <div class="lib-address">📍 ул. Комсомольская, 138</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">5 400</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">1 600</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">550</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
        <div class="lib-card">
            <img class="lib-img" src="https://images.unsplash.com/photo-1457369804613-52c61a468e7d?q=80&w=500&auto=format&fit=crop" alt="Библиотека">
            <div class="lib-body">
                <div class="lib-name">Детская библиотека №17</div>
                <div class="lib-address">📍 ул. Проспект Октября, 114</div>
                <div class="lib-funds">
                    <div class="fund-item"><div class="fund-val">2 800</div><div class="fund-lbl">Книги</div></div>
                    <div class="fund-item"><div class="fund-val">950</div><div class="fund-lbl">Журналы</div></div>
                    <div class="fund-item"><div class="fund-val">180</div><div class="fund-lbl">Газеты</div></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_admin): ?>
    <div class="admin-footer-box">
        <h3 style="margin: 0; color: var(--ink); font-size: 16px; font-weight: 600;">🔐 Служебный доступ (Панель Администратора)</h3>
        <div class="admin-grid">
            <a class="admin-card" href="employees.php">
                <div style="font-weight:600; color:var(--ink);">Кадровый учёт сотрудников</div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">Всего сотрудников в штате: <?= $s['emps'] ?>.</div>
            </a>
            <a class="admin-card" href="funds.php">
                <div style="font-weight:600; color:var(--ink);">Финансовая аналитика</div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">Интерактивные графики, бюджеты филиалов и отчеты.</div>
            </a>
            <a class="admin-card" href="replenishments.php">
                <div style="font-weight:600; color:var(--ink);">Журнал пополнений фондов</div>
                <div style="font-size:12px; color:var(--muted); margin-top:4px;">Контроль поставок литературы и новых партий.</div>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

<footer style="margin-top: 60px; text-align: center;">Библиотечная сеть USM · Официальный информационный портал · 2026</footer>

</body>
</html>
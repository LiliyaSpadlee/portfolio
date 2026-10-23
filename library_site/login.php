<?php
require_once 'db.php';
session_start();

$error = '';

if (isset($_GET['demo']) && $_GET['demo'] === '1') {
    $_SESSION['auth'] = true;
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Жестко зашитые данные для входа
    if ($username === 'admin' && $password === 'secret123') {
        $_SESSION['auth'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль!';
    }
}

// Выход из системы
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['auth']);
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему · USM</title>
    <?php include 'head.php'; ?>
    <style>
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        .login-card {
            background: var(--card);
            padding: 36px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 400px;
        }
        .login-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group-login {
            margin-bottom: 16px;
        }
        .form-group-login label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--muted);
        }
        .form-group-login input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group-login input:focus {
            outline: 2px solid var(--gold);
            border-color: var(--gold);
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-title">Вход в панель управления</div>
        
        <?php if ($error): ?>
            <div class="msg error" style="margin-bottom: 16px; padding: 10px; background: rgba(139,38,53,0.1); color: var(--red); border-radius: 4px; font-size: 13px;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 20px;">
            <a href="login.php?demo=1" class="btn btn-teal" style="display: block; text-align: center; padding: 10px; text-decoration: none; font-weight: 500;">
                Войти в демо-режиме
            </a>
        </div>

        <div style="display: flex; align-items: center; text-align: center; color: var(--muted); margin-bottom: 16px; font-size: 12px;">
            <div style="flex: 1; border-bottom: 1px solid var(--border);"></div>
            <span style="padding: 0 10px;">или вручную</span>
            <div style="flex: 1; border-bottom: 1px solid var(--border);"></div>
        </div>

        <form action="login.php" method="POST">
            <div class="form-group-login">
                <label>Логин</label>
                <input type="text" name="username" value="admin" required autocomplete="off">
            </div>
            <div class="form-group-login">
                <label>Пароль</label>
                <input type="password" name="password" value="secret123" required>
            </div>
            <button type="submit" class="btn btn-green" style="width: 100%; margin-top: 8px; padding: 12px;">Войти</button>
        </form>

        <div style="text-align: center; margin-top: 16px;">
            <a href="index.php" style="color: var(--teal); text-decoration: none; font-size: 13px;">← На главную</a>
        </div>
    </div>
</div>

<footer>Библиотечная система USM · 2026</footer>
</body>
</html>
<?php
// ============================================================
//  Подключение к базе данных
// ============================================================

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'usm';

$conn = mysqli_connect($host, $user, $pass, $db);
mysqli_set_charset($conn, 'utf8');

if (!$conn) {
    die("<div style='color:red;padding:20px;font-family:sans-serif'>
        <b>Ошибка подключения к БД:</b> " . mysqli_connect_error() . "<br>
        Убедись что в XAMPP запущены Apache и MySQL, и база данных <b>usm</b> существует.
    </div>");
}

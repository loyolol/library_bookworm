<?php
// php/logout.php
require_once __DIR__ . '/../config.php';
session_start();

// Уничтожаем все переменные сессии
$_SESSION = array();

// Уничтожаем сессию
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

$_SESSION['success'] = "Вы успешно вышли из системы.";
// *** ИСПРАВЛЕНО: Перенаправляем на login.php (который теперь в корне) ***
header("Location: ../login.php"); 
exit;
?>
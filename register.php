<?php 
require_once __DIR__ . '/config.php';
session_start(); 

$message = ''; 
if (isset($_SESSION['error'])) {
    $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
} elseif (isset($_SESSION['success'])) {
    $message = '<div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;

$nav_links = [
    'Каталог' => 'index.php',
];

if ($is_logged_in) {
    $nav_links['Выход'] = 'php/logout.php';
} else {
    $nav_links['Войти'] = 'login.php';
}

// Генерация абсолютного URL для обработки POST-запроса
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_path = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\'); 
$action_url = $protocol . $host . $base_path . '/php/register.php'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="/css/style.css"> 
</head>
<body>
    <header>
        <h1>Книжный червь</h1>
        <nav>
            <ul>
                <?php if ($is_logged_in): ?>
                    <li style="color: #FFD700; margin-right: 20px;">Привет, <?php echo htmlspecialchars($user_name); ?>!</li>
                <?php endif; ?>
                <?php foreach ($nav_links as $text => $url): ?>
                    <?php if (!($is_logged_in && $text == 'Каталог') || !$is_logged_in): ?> 
                        <li><a href="<?php echo $url; ?>"><?php echo $text; ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </header>
    <div class="form-container"> 
        <div class="form-content-wrapper">
            <h2>Регистрация</h2>
            
            <?php echo $message; ?>
    
            <form action="<?php echo htmlspecialchars($action_url); ?>" method="POST">
                <div class="form-group">
                    <label for="name">Имя:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="form-submit">Зарегистрироваться</button>
            </form>
            <div class="link-footer">
                <p style="margin: 0;">Уже есть аккаунт? <a href="login.php">Войти</a></p>
            </div>
        </div>
    </div>
    <footer>
        &copy; Книжный червь 2026. Все права защищены.
    </footer>
</body>
</html>
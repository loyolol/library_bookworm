<?php 
require_once __DIR__ . '/config.php';
session_start(); 

$message = ''; 
if (isset($_SESSION['error'])) {
    $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
} elseif (isset($_SESSION['success'])) {
    $message = '<div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;

$nav_links = [
    'Каталог' => 'index.php'
];

if ($is_logged_in) {
    $nav_links['Выход'] = 'php/logout.php';
} else {
    $nav_links['Регистрация'] = 'register.php';
}

// Генерация абсолютного URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_path = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\'); 
$action_url = $protocol . $host . $base_path . '/php/login.php'; 
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="/css/style.css"> 
    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.9em;
            margin-top: 5px;
            display: none;
        }
        
        .form-group input.invalid {
            border-color: #dc3545;
        }
        
        .form-group input.valid {
            border-color: #28a745;
        }
    </style>
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
            <h2>Авторизация</h2>
            
            <?php echo $message; ?>
            
            <form action="<?php echo htmlspecialchars($action_url); ?>" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <div class="error-message" id="emailError"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                    <div class="error-message" id="passwordError"></div>
                </div>
                
                <button type="submit" class="form-submit" id="submitBtn">Войти</button>
            </form>
            
            <div class="link-footer">
                <p style="margin: 0;">Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
            </div>
        </div>
    </div>
    
    <footer>
        &copy; Книжный червь 2026. Все права защищены.
    </footer>

    <script>
        // Клиентская валидация
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');
        
        // Валидация email
        emailInput.addEventListener('input', function() {
            const value = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (value.length > 0 && !emailRegex.test(value)) {
                emailError.textContent = 'Введите корректный email';
                emailError.style.display = 'block';
                this.classList.add('invalid');
                this.classList.remove('valid');
            } else if (value.length === 0) {
                emailError.style.display = 'none';
                this.classList.remove('invalid', 'valid');
            } else {
                emailError.style.display = 'none';
                this.classList.remove('invalid');
                this.classList.add('valid');
            }
        });
        
        // Валидация пароля
        passwordInput.addEventListener('input', function() {
            if (this.value.length === 0) {
                passwordError.textContent = 'Пароль обязателен';
                passwordError.style.display = 'block';
                this.classList.add('invalid');
                this.classList.remove('valid');
            } else {
                passwordError.style.display = 'none';
                this.classList.remove('invalid');
                this.classList.add('valid');
            }
        });
    </script>
</body>
</html>

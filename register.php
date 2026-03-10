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

// Получаем сохраненные значения полей, если они есть
$old_name = isset($_SESSION['old_name']) ? htmlspecialchars($_SESSION['old_name']) : '';
$old_email = isset($_SESSION['old_email']) ? htmlspecialchars($_SESSION['old_email']) : '';

// Очищаем сохраненные значения после использования
unset($_SESSION['old_name']);
unset($_SESSION['old_email']);

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
        
        .password-requirements {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
            padding-left: 10px;
        }
        
        .password-requirements li {
            margin-bottom: 3px;
        }
        
        .password-requirements .valid {
            color: #28a745;
        }
        
        .password-requirements .invalid {
            color: #dc3545;
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
            <h2>Регистрация</h2>
            
            <?php echo $message; ?>
    
            <form action="<?php echo htmlspecialchars($action_url); ?>" method="POST" id="registerForm">
                <div class="form-group">
                    <label for="name">Имя:</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo $old_name; ?>"
                           pattern="[а-яА-ЯёЁa-zA-Z\s]+" 
                           title="Только буквы (русские или латинские)">
                    <div class="error-message" id="nameError"></div>
                    <small style="color: #666;">Только буквы, не более 10 символов</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo $old_email; ?>">
                    <div class="error-message" id="emailError"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required minlength="4">
                    <div class="error-message" id="passwordError"></div>
                    <ul class="password-requirements" id="passwordRequirements">
                        <li id="lengthReq">✗ Минимум 4 символа</li>
                    </ul>
                </div>
                
                <button type="submit" class="form-submit" id="submitBtn">Зарегистрироваться</button>
            </form>
            
            <div class="link-footer">
                <p style="margin: 0;">Уже есть аккаунт? <a href="login.php">Войти</a></p>
            </div>
        </div>
    </div>
    
    <footer>
        &copy; Книжный червь 2026. Все права защищены.
    </footer>

    <script>
        // Клиентская валидация
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const nameError = document.getElementById('nameError');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');
        const lengthReq = document.getElementById('lengthReq');
        
        // Валидация имени
        nameInput.addEventListener('input', function() {
            const value = this.value;
            const nameRegex = /^[а-яА-ЯёЁa-zA-Z\s]*$/;
            
            if (value.length > 10) {
                nameError.textContent = 'Имя не должно быть длиннее 10 символов. Сейчас: ' + value.length;
                nameError.style.display = 'block';
                this.classList.add('invalid');
                this.classList.remove('valid');
            } else if (value.length > 0 && !nameRegex.test(value)) {
                nameError.textContent = 'Имя должно содержать только буквы';
                nameError.style.display = 'block';
                this.classList.add('invalid');
                this.classList.remove('valid');
            } else if (value.length === 0) {
                nameError.style.display = 'none';
                this.classList.remove('invalid', 'valid');
            } else {
                nameError.style.display = 'none';
                this.classList.remove('invalid');
                this.classList.add('valid');
            }
        });
        
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
            const value = this.value;
            
            // Проверка длины
            if (value.length >= 4) {
                lengthReq.innerHTML = '✓ Минимум 4 символа';
                lengthReq.classList.add('valid');
                lengthReq.classList.remove('invalid');
            } else {
                lengthReq.innerHTML = '✗ Минимум 4 символа';
                lengthReq.classList.add('invalid');
                lengthReq.classList.remove('valid');
            }
            
            // Общая валидация
            if (value.length > 0 && value.length < 4) {
                passwordError.textContent = 'Пароль должен содержать минимум 4 символа';
                passwordError.style.display = 'block';
                this.classList.add('invalid');
                this.classList.remove('valid');
            } else if (value.length === 0) {
                passwordError.style.display = 'none';
                this.classList.remove('invalid', 'valid');
            } else {
                passwordError.style.display = 'none';
                this.classList.remove('invalid');
                this.classList.add('valid');
            }
        });
        
        // Запускаем проверку при загрузке страницы, если есть сохраненное значение
        if (nameInput.value) {
            nameInput.dispatchEvent(new Event('input'));
        }
        if (emailInput.value) {
            emailInput.dispatchEvent(new Event('input'));
        }
    </script>
</body>
</html>
<?php
require_once __DIR__ . '/../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];

    if (empty($name)) {
        $errors[] = "Имя обязательно для заполнения.";
    } else {
        if (strlen($name) > 20) {
            $errors[] = "Имя не должно быть длиннее 10 символов.";
        }
        elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $name)) {
            $errors[] = "Имя должно содержать только буквы.";
        }
    }

    // Проверка email
    if (empty($email)) {
        $errors[] = "Email обязателен для заполнения.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат Email.";
    }

    // Проверка пароля
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения.";
    } elseif (strlen($password) < 4) {
        $errors[] = "Пароль должен содержать не менее 4 символов.";
    }

    // Если есть ошибки, возвращаем пользователя с сообщениями
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        $_SESSION['old_name'] = $name;
        $_SESSION['old_email'] = $email;
        header("Location: ../register.php");
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, 'user')";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $password_hash
        ]);

        $_SESSION['success'] = "Регистрация прошла успешно! Теперь вы можете войти.";
        header("Location: ../login.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == '23505') {
            $_SESSION['error'] = "Пользователь с таким Email уже существует.";
        } else {
            $_SESSION['error'] = "Ошибка базы данных при регистрации: " . $e->getMessage();
        }
        $_SESSION['old_name'] = $name;
        $_SESSION['old_email'] = $email;
        header("Location: ../register.php");
        exit;
    }
} else {
    header("Location: ../register.php");
    exit;
}
?>
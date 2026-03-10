<?php
// php/login.php
require_once __DIR__ . '/../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];

    // Проверка email
    if (empty($email)) {
        $errors[] = "Email обязателен для заполнения.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат Email.";
    }

    // Проверка пароля
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения.";
    }

    // Если есть ошибки валидации, возвращаем пользователя
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: ../login.php");
        exit;
    }

    try {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Проверка существования пользователя
        if (!$user) {
            $_SESSION['error'] = "Пользователь с таким Email не зарегистрирован.";
            header("Location: ../login.php");
            exit;
        }

        // Проверка пароля
        if (!password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = "Неверный пароль.";
            header("Location: ../login.php");
            exit;
        }

        // Успешный вход
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name']; 
        
        header("Location: ../index.php"); 
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "Ошибка сервера: " . $e->getMessage();
        header("Location: ../login.php");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
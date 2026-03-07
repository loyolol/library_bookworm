<?php
// php/login.php
require_once __DIR__ . '/../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email и пароль обязательны.";
        header("Location: ../login.php");
        exit;
    }

    try {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Успешный вход
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_name'] = $user['name']; 
            
            // Успех не отображаем в виде сообщения, просто перенаправляем
            header("Location: ../index.php"); 
            exit;
        } else {
            $_SESSION['error'] = "Неверный Email или пароль.";
            header("Location: ../login.php");
            exit;
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Ошибка сервера.";
        header("Location: ../login.php");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
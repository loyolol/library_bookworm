<?php
// php/register.php
require_once __DIR__ . '/../config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Все поля должны быть заполнены.";
        header("Location: ../register.php");
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Некорректный формат Email.";
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
        header("Location: ../login.php"); // Перенаправляем на login.php
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == '23505') {
            $_SESSION['error'] = "Пользователь с таким Email уже существует.";
        } else {
            $_SESSION['error'] = "Ошибка базы данных при регистрации.";
        }
        header("Location: ../register.php");
        exit;
    }
} else {
    header("Location: ../register.php");
    exit;
}
?>
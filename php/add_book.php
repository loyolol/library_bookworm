<?php
// php/add_book.php
require_once __DIR__ . '/../config.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Несанкционированный доступ.";
    header("Location: ../pages/index.php");
    exit;
}


$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$year = $_POST['year'] ?? 0;
$publisher = $_POST['publisher'] ?? '';
$genre = $_POST['genre'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 0);
$description = $_POST['description'] ?? '';


$cover_path = null;
$upload_dir = '../uploads/'; 

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (isset($_FILES['cover']) && $_FILES['cover']['error'] == UPLOAD_ERR_OK) {
    $file_tmp_path = $_FILES['cover']['tmp_name'];
    $file_name = time() . "_" . basename($_FILES['cover']['name']);
    $destination = $upload_dir . $file_name;

    if (move_uploaded_file($file_tmp_path, $destination)) {
        $cover_path = $destination;
    } else {
        $_SESSION['error'] = "Не удалось загрузить файл обложки.";
        header("Location: ../pages/add_book.php");
        exit;
    }
} else if ($quantity > 0) {
    
}

try {
    $sql = "INSERT INTO books (title, author, publication_year, publisher, genre, total_quantity, quantity_available, cover_path, description) 
            VALUES (:title, :author, :year, :publisher, :genre, :quantity, :quantity, :cover_path, :description)";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':title' => $title,
        ':author' => $author,
        ':year' => $year,
        ':publisher' => $publisher,
        ':genre' => $genre,
        ':quantity' => $quantity,
        ':cover_path' => $cover_path,
        ':description' => $description
    ]);

    $_SESSION['success'] = "Книга '{$title}' успешно добавлена в каталог.";
    header("Location: ../pages/add_book.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "Ошибка БД при добавлении книги: " . $e->getMessage();
    header("Location: ../pages/add_book.php");
    exit;
}
?>
<?php

require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректный запрос или сессия недействительна.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$book_id = filter_var($_POST['book_id'] ?? 0, FILTER_VALIDATE_INT);

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID книги.']);
    exit;
}

try {
    
    $pdo->beginTransaction();

    
    $check_sql = "SELECT quantity_available FROM books WHERE book_id = :book_id FOR UPDATE"; 
    $stmt_check = $pdo->prepare($check_sql);
    $stmt_check->execute([':book_id' => $book_id]);
    $book = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Книга не найдена в каталоге.']);
        exit;
    }

    if ($book['quantity_available'] <= 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Книга закончилась.']);
        exit;
    }

    
    $check_booking_sql = "SELECT 1 FROM bookings WHERE book_id = :book_id AND user_id = :user_id AND status = 'active'";
    $stmt_booking_check = $pdo->prepare($check_booking_sql);
    $stmt_booking_check->execute([':book_id' => $book_id, ':user_id' => $user_id]);
    
    if ($stmt_booking_check->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Вы уже забронировали эту книгу.']);
        exit;
    }

    
    $booking_sql = "INSERT INTO bookings (book_id, user_id, booking_date, due_date, status) 
                    VALUES (:book_id, :user_id, CURRENT_DATE, CURRENT_DATE + INTERVAL '14 days', 'active')";
    $stmt_book = $pdo->prepare($booking_sql);
    $stmt_book->execute([
        ':book_id' => $book_id, 
        ':user_id' => $user_id
    ]);

    
    $update_sql = "UPDATE books SET quantity_available = quantity_available - 1 WHERE book_id = :book_id";
    $stmt_update = $pdo->prepare($update_sql);
    $stmt_update->execute([':book_id' => $book_id]);

    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Книга успешно забронирована!']);

} catch (Exception $e) {
    
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Booking Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при бронировании.']);
}
?>
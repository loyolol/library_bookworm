<?php


require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Некорректный запрос или сессия недействительна.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = filter_var($_POST['booking_id'] ?? 0, FILTER_VALIDATE_INT);

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID бронирования.']);
    exit;
}

try {
    $pdo->beginTransaction();


    $check_sql = "SELECT book_id, status FROM bookings WHERE booking_id = :booking_id AND user_id = :user_id FOR UPDATE";
    $stmt_check = $pdo->prepare($check_sql);
    $stmt_check->execute([':booking_id' => $booking_id, ':user_id' => $user_id]);
    $booking = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$booking || $booking['status'] !== 'active') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Бронирование не найдено или уже отменено/завершено.']);
        exit;
    }
    
    $book_id = $booking['book_id'];


    $cancel_sql = "UPDATE bookings SET status = 'cancelled' WHERE booking_id = :booking_id";
    $stmt_cancel = $pdo->prepare($cancel_sql);
    $stmt_cancel->execute([':booking_id' => $booking_id]);


    $update_sql = "UPDATE books SET quantity_available = quantity_available + 1 WHERE book_id = :book_id";
    $stmt_update = $pdo->prepare($update_sql); 
    $stmt_update->execute([':book_id' => $book_id]);

    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Бронирование успешно отменено. Книга возвращена в каталог.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Cancellation Error: " . $e->getMessage()); 
    echo json_encode(['success' => false, 'message' => 'Ошибка сервера при отмене бронирования.']);
}
?>
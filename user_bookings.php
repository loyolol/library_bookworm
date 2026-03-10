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

if (!$is_logged_in) {
     $_SESSION['error'] = "Доступ запрещен. Пожалуйста, войдите в систему.";
     header("Location: login.php");
     exit;
}


// --- Унифицированная Навигация ---
$nav_links = [
    'Каталог' => 'index.php',
];

if ($is_logged_in) {
    // 1. Пользователь видит "Мои Бронирования"
    if ($user_role == 'user') {
        $nav_links['Мои Бронирования'] = 'user_bookings.php';
    }
    
    // 2. Админ видит УПРАВЛЕНИЕ
    if ($user_role == 'admin') {
        $nav_links['Управление Книгами'] = 'manage_books.php';
        $nav_links['Добавить Книгу'] = 'add_book.php';
    }
    $nav_links['Выход'] = 'php/logout.php';
} else {
    $nav_links['Войти'] = 'login.php';
    $nav_links['Регистрация'] = 'register.php';
}


// --- РЕАЛЬНАЯ ЗАГРУЗКА БРОНИРОВАНИЙ ---
$bookings_data = [];
try {
    $sql = "
        SELECT 
            b.booking_id, b.booking_date, b.due_date, b.status,
            bk.title, bk.author
        FROM bookings b
        JOIN books bk ON b.book_id = bk.book_id
        WHERE b.user_id = :user_id
        ORDER BY b.booking_date DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $bookings_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $message .= '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Ошибка загрузки бронирований: ' . $e->getMessage() . '</div>';
}

// Функция для преобразования статуса в русский язык и цвет
function getStatusInRussian($english_status) {
    switch ($english_status) {
        case 'active':
            return '<span style="color: #28a745; font-weight: bold;">Активно</span>'; 
        case 'returned':
            return '<span style="color: blue;">Возвращено</span>';
        case 'overdue':
            return '<span style="color: red; font-weight: bold;">Просрочено</span>';
        case 'cancelled':
            return '<span style="color: gray;">Отменено</span>';
        default:
            return htmlspecialchars($english_status);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои Бронирования</title>
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
                    <li><a href="<?php echo $url; ?>"><?php echo $text; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h2>Мои Забронированные Книги</h2>
        <?php echo $message; ?>
        
        <table>
            <thead>
                <tr>
                    <th>Название Книги</th>
                    <th>Автор</th>
                    <th>Дата Бронирования</th>
                    <th>Срок Возврата</th>
                    <th>Статус</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings_data)): ?>
                    <tr><td colspan="6">У вас пока нет активных бронирований.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings_data as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['title']); ?></td>
                            <td><?php echo htmlspecialchars($b['author']); ?></td>
                            <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                            <td><?php echo htmlspecialchars($b['due_date']); ?></td>
                            <td><?php echo getStatusInRussian($b['status']); ?></td>
                            <td>
                                <?php if ($b['status'] == 'active'): ?>
                                    <button class="btn btn-secondary" onclick="cancelBooking(<?php echo $b['booking_id']; ?>, this)">Отменить</button>
                                <?php else: ?>
                                    --
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 20px;">Вернуться в <a href="index.php">каталог</a>.</p>
    </div>
    <footer>
        &copy; Книжный червь 2026. Все права защищены. 
    </footer>
    <script>
        function cancelBooking(bookingId, buttonElement) {
            // ... (JS остается прежним)
            buttonElement.disabled = true;
            buttonElement.textContent = 'Отмена...';
            
            const formData = new FormData();
            formData.append('booking_id', bookingId);

            fetch('php/cancel_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                
                if (data.success) {
                    setTimeout(() => {
                        window.location.reload(); 
                    }, 500);
                } else {
                    buttonElement.textContent = 'Повторить';
                    buttonElement.disabled = false;
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Произошла сетевая ошибка. Проверьте путь к php/cancel_booking.php.');
                buttonElement.textContent = 'Отменить';
                buttonElement.disabled = false;
            });
        }
    </script>
</body>
</html>
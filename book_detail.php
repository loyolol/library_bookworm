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

// --- Унифицированная Навигация ---
$nav_links = [
    'Каталог' => 'index.php',
];

if ($is_logged_in) {
    // 1. Пользователь видит "Мои Бронирования"
    if ($user_role == 'user') {
        $nav_links['Мои Бронирования'] = 'user_bookings.php';
    }
    
    // 2. Админ видит УПРАВЛЕНИЕ (Ссылки на manage_books.php и add_book.php в корне)
    if ($user_role == 'admin') {
        $nav_links['Управление Книгами'] = 'manage_books.php'; 
        $nav_links['Добавить Книгу'] = 'add_book.php';
    }
    $nav_links['Выход'] = 'php/logout.php';
} else {
    $nav_links['Войти'] = 'login.php';
    $nav_links['Регистрация'] = 'register.php';
}


// --- Логика Бронирования ---
$book_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$book = null;
$is_booked = false;

if ($book_id > 0) {
    try {
        // Загрузка данных книги
        $sql = "SELECT * FROM books WHERE book_id = :book_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':book_id' => $book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($book) {
            // Проверка, забронирована ли книга этим пользователем
            if ($is_logged_in && $user_role == 'user') {
                $booked_sql = "SELECT 1 FROM bookings WHERE user_id = :user_id AND book_id = :book_id AND status = 'active'";
                $stmt_booked = $pdo->prepare($booked_sql);
                $stmt_booked->execute([':user_id' => $_SESSION['user_id'], ':book_id' => $book_id]);
                $is_booked = (bool)$stmt_booked->fetch();
            }
        }
    } catch (PDOException $e) {
        $message .= '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Ошибка загрузки данных: ' . $e->getMessage() . '</div>';
    }
} else {
    $message .= '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Неверный ID книги.</div>';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $book ? htmlspecialchars($book['title']) : 'Детали Книги'; ?></title>
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
        <h2>Подробная Информация</h2>
        <?php echo $message; ?>
        
        <?php if ($book): ?>
            <div class="book-card" style="display: block;">
                <img src="" alt="Обложка книги" style="float: left; margin-right: 30px; width: 200px; height: auto;">
                
                <div>
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                    <p><strong>Год издания:</strong> <?php echo htmlspecialchars($book['publication_year']); ?></p>
                    <p><strong>Издательство:</strong> <?php echo htmlspecialchars($book['publisher']); ?></p>
                    <hr>
                    <p><strong>Описание:</strong><br><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                    <p><strong>Общее количество:</strong> <?php echo htmlspecialchars($book['total_quantity']); ?></p>
                    <p><strong>В наличии:</strong> <?php echo htmlspecialchars($book['quantity_available']); ?></p>
                    
                    <?php if ($is_logged_in && $user_role == 'user'): ?>
                        <?php if ($book['quantity_available'] > 0 && !$is_booked): ?>
                            <a href="#" onclick="bookBookDetail(<?php echo $book['book_id']; ?>); return false;" class="btn btn-primary" style="margin-top: 15px;">Забронировать</a>
                        <?php elseif ($is_booked): ?>
                            <span style="color: #28a745; font-weight: bold; display: block; margin-top: 10px;">Забронировано</span>
                        <?php elseif ($book['quantity_available'] == 0): ?>
                            <p style="color: red; font-weight: bold;">Книга временно отсутствует.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary" style="margin-top: 15px;">Назад к каталогу</a>
                </div>
            </div>
        <?php else: ?>
            <p>Книга с таким ID не найдена.</p>
        <?php endif; ?>
    </div>
    <footer>
        &copy; Книжный червь 2026. Все права защищены. 
    </footer>
    <script>
        function bookBookDetail(bookId) {
            const formData = new FormData();
            formData.append('action', 'book');
            formData.append('book_id', bookId);

            fetch('php/book_actions.php', {
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
                } 
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Произошла сетевая ошибка.');
            });
        }
    </script>
</body>
</html>
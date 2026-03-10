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
    if ($user_role == 'user') {
        $nav_links['Мои Бронирования'] = 'user_bookings.php';
    }
    if ($user_role == 'admin') {
        $nav_links['Управление Книгами'] = 'manage_books.php';
        $nav_links['Добавить Книгу'] = 'add_book.php';
    }
    $nav_links['Выход'] = 'php/logout.php';
} else {
    $nav_links['Войти'] = 'login.php';
    $nav_links['Регистрация'] = 'register.php';
}

// --- Проверка, какие книги уже забронированы пользователем ---
$currently_booked_ids = [];
if ($is_logged_in && $user_role == 'user') {
    try {
        $booked_sql = "SELECT book_id FROM bookings WHERE user_id = :user_id AND status = 'active'";
        $stmt_booked = $pdo->prepare($booked_sql);
        $stmt_booked->execute([':user_id' => $_SESSION['user_id']]);
        $currently_booked_ids = $stmt_booked->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $message .= '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Ошибка проверки бронирований: ' . $e->getMessage() . '</div>';
    }
}

// --- ЗАГРУЗКА КНИГ ИЗ БД ---
$books = [];
try {
    $sql = "SELECT book_id, title, author, genre, quantity_available, cover_path FROM books WHERE quantity_available > 0 ORDER BY title ASC";
    $stmt = $pdo->query($sql);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Ошибка загрузки каталога: ' . $e->getMessage() . '</div>';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - Библиотека</title>
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
        <h2>Каталог Книг</h2>
        <?php echo $message; ?>

        <?php if (empty($books)): ?>
            <p>К сожалению, в каталоге пока нет доступных книг.</p>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <?php 
                    // Определяем путь к обложке
                    $cover = !empty($book['cover_path']) 
                        ? htmlspecialchars($book['cover_path']) 
                        : 'https://via.placeholder.com/100x150?text=No+Cover'; 
                    ?>
                    <img src="<?php echo $cover; ?>" alt="Обложка книги" style="width: 100px; height: auto;">
                    <div class="book-info">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                        <p><strong>В наличии:</strong> <?php echo htmlspecialchars($book['quantity_available']); ?> шт.</p>
                        
                        <a href="book_detail.php?id=<?php echo $book['book_id']; ?>" class="btn btn-secondary">Подробнее</a>
                        
                        <?php if ($is_logged_in && $user_role == 'user'): ?>
                            <?php 
                                $is_booked = in_array($book['book_id'], $currently_booked_ids);
                            ?>
                            <?php if ($book['quantity_available'] > 0 && !$is_booked): ?>
                                <button class="btn btn-primary" onclick="bookBook(<?php echo $book['book_id']; ?>, this)">Забронировать</button> 
                            <?php elseif ($is_booked): ?>
                                <span style="color: #28a745; font-weight: bold; display: block; margin-top: 5px;">Забронировано</span>
                            <?php elseif ($book['quantity_available'] == 0): ?>
                                <span style="color: red;">Нет в наличии</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
    <script>
        function bookBook(bookId, buttonElement) {
            buttonElement.disabled = true;
            buttonElement.textContent = 'Бронирование...';
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
                    setTimeout(() => { window.location.reload(); }, 500);
                } else {
                    buttonElement.textContent = 'Повторить попытку';
                    buttonElement.disabled = false;
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Произошла сетевая ошибка.');
                buttonElement.textContent = 'Забронировать';
                buttonElement.disabled = false;
            });
        }
    </script>
    <footer>
        &copy; Книжный червь 2026. Все права защищены. 
    </footer>
</body>
</html>
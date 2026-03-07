<?php 
require_once __DIR__ . '/config.php'; 
session_start(); 

// --- Проверка Доступа ---
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;

if (!$is_logged_in || $user_role !== 'admin') {
     $_SESSION['error'] = "Доступ запрещен. Эта страница только для администраторов.";
     header("Location: login.php");
     exit;
}

$message = '';
if (isset($_SESSION['error'])) {
    $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
} elseif (isset($_SESSION['success'])) {
    $message = '<div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['success']) . '</div>';
    unset($_SESSION['success']);
}

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


// --- ЗАГРУЗКА ВСЕХ КНИГ ДЛЯ РЕДАКТИРОВАНИЯ ---
$books = [];
try {
    $sql = "SELECT book_id, title, author, quantity_available, total_quantity FROM books ORDER BY title ASC";
    $stmt = $pdo->query($sql);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message .= '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Ошибка загрузки книг для управления: ' . $e->getMessage() . '</div>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление Книгами (Админ)</title>
    <link rel="stylesheet" href="/css/style.css"> 
</head>
<body>
    <header>
        <h1>Книжный червь</h1>
        <nav>
            <ul>
                <li style="color: #FFD700; margin-right: 20px;">Привет, <?php echo htmlspecialchars($user_name); ?>!</li>
                <?php foreach ($nav_links as $text => $url): ?>
                    <li><a href="<?php echo $url; ?>"><?php echo $text; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h2>Управление Каталогом</h2>
        <?php echo $message; ?>

        <p><a href="add_book.php" class="btn btn-primary">Добавить Новую Книгу</a></p>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Всего</th>
                    <th>В наличии</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr><td colspan="6">Книги не найдены.</td></tr>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['book_id']); ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['total_quantity']); ?></td>
                            <td><?php echo htmlspecialchars($book['quantity_available']); ?></td>
                            <td>
                                <!-- КНОПКА РЕДАКТИРОВАТЬ -->
                                <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-secondary">Редактировать</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <footer>
        &copy; Книжный червь 2026. Все права защищены. 
    </footer>
</body>
</html>
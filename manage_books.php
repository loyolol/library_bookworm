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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book_id'])) {
    $delete_book_id = filter_var($_POST['delete_book_id'], FILTER_VALIDATE_INT);
    
    if ($delete_book_id > 0) {
        try {
            
            $pdo->beginTransaction();
            
            
            $check_sql = "SELECT COUNT(*) FROM bookings WHERE book_id = :book_id AND status = 'active'";
            $stmt_check = $pdo->prepare($check_sql);
            $stmt_check->execute([':book_id' => $delete_book_id]);
            $active_bookings = $stmt_check->fetchColumn();
            
            if ($active_bookings > 0) {
                $_SESSION['error'] = "Невозможно удалить книгу, так как есть активные бронирования.";
                $pdo->rollBack();
            } else {
                
                $cover_sql = "SELECT cover_path FROM books WHERE book_id = :book_id";
                $stmt_cover = $pdo->prepare($cover_sql);
                $stmt_cover->execute([':book_id' => $delete_book_id]);
                $cover_path = $stmt_cover->fetchColumn();
                
                
                $delete_bookings_sql = "DELETE FROM bookings WHERE book_id = :book_id";
                $stmt_delete_bookings = $pdo->prepare($delete_bookings_sql);
                $stmt_delete_bookings->execute([':book_id' => $delete_book_id]);
                
                
                $delete_book_sql = "DELETE FROM books WHERE book_id = :book_id";
                $stmt_delete_book = $pdo->prepare($delete_book_sql);
                $stmt_delete_book->execute([':book_id' => $delete_book_id]);
                
                
                if ($stmt_delete_book->rowCount() > 0) {
                    
                    if (!empty($cover_path) && file_exists($cover_path) && strpos($cover_path, 'uploads/') === 0) {
                        unlink($cover_path);
                    }
                    
                    $_SESSION['success'] = "Книга успешно удалена.";
                } else {
                    $_SESSION['error'] = "Книга не найдена.";
                }
                
                $pdo->commit();
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Ошибка при удалении книги: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Неверный ID книги.";
    }
    
    header("Location: manage_books.php");
    exit;
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
    <style>
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-left: 5px;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .actions-cell {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-secondary {
            margin-right: 5px;
        }
    </style>
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
        <h2>Управление Книгами</h2>
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
                            <td class="actions-cell">
                                <!-- КНОПКА РЕДАКТИРОВАТЬ -->
                                <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" class="btn btn-secondary">Редактировать</a>
                                
                                <!-- КНОПКА УДАЛИТЬ -->
                                <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $book['book_id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>')">Удалить</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Модальное окно подтверждения удаления -->
    <div id="deleteModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 15% auto; padding: 20px; border-radius: 8px; width: 400px; max-width: 80%; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
            <h3 style="margin-top: 0; color: #333;">Подтверждение удаления</h3>
            <p id="deleteModalMessage" style="margin-bottom: 20px;">Вы уверены, что хотите удалить эту книгу?</p>
            
            <form id="deleteForm" method="POST" style="display: inline;">
                <input type="hidden" name="delete_book_id" id="delete_book_id" value="">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary" style="margin-right: 10px;">Отмена</button>
                <button type="submit" class="btn btn-danger">Удалить</button>
            </form>
        </div>
    </div>
    
    <footer>
        &copy; Книжный червь 2026. Все права защищены. 
    </footer>
    
    <script>
        // Функция для открытия модального окна подтверждения удаления
        function confirmDelete(bookId, bookTitle) {
            document.getElementById('delete_book_id').value = bookId;
            document.getElementById('deleteModalMessage').innerHTML = `Вы уверены, что хотите удалить книгу "<strong>${bookTitle}</strong>"?<br><br>`;
            
            // Добавляем предупреждение, если есть
            document.getElementById('deleteModalMessage').innerHTML += '<span style="color: #dc3545; font-size: 0.9em;">Внимание: Это действие нельзя отменить. Все связанные бронирования (кроме активных) будут также удалены.</span>';
            
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Функция для закрытия модального окна
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
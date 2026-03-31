<?php 
require_once __DIR__ . '/config.php'; 
session_start(); 

$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;

if (!$is_logged_in || $user_role !== 'admin') {
     $_SESSION['error'] = "Доступ запрещен. Эта страница только для администраторов.";
     header("Location: login.php");
     exit;
}

$book_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$book = null;


if ($book_id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $sql = "SELECT * FROM books WHERE book_id = :book_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':book_id' => $book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            $_SESSION['error'] = "Книга с ID {$book_id} не найдена.";
            header("Location: manage_books.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Ошибка загрузки данных: " . $e->getMessage();
        header("Location: manage_books.php");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id_post = filter_var($_POST['book_id'] ?? 0, FILTER_VALIDATE_INT);
    
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $year = filter_var($_POST['publication_year'], FILTER_VALIDATE_INT);
    $publisher = trim($_POST['publisher']);
    $genre = trim($_POST['genre']);
    $total_quantity = filter_var($_POST['total_quantity'], FILTER_VALIDATE_INT);
    $available_quantity = filter_var($_POST['quantity_available'], FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);
    
    // Обработка обложки - сначала берем существующую
    $cover_path = $_POST['existing_cover_path'] ?? '';
    
    // Проверяем, загружен ли новый файл
    if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/covers/';
        
        // Создаем директорию, если её нет
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['cover_file']['name']);
        $extension = strtolower($file_info['extension']);
        
        // Проверяем расширение файла
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($extension, $allowed_extensions)) {
            $new_filename = time() . '_' . uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_path)) {
                $cover_path = $upload_path;
                
                // Удаляем старый файл, если он существует и не является URL
                if (!empty($_POST['existing_cover_path']) && 
                    file_exists($_POST['existing_cover_path']) && 
                    strpos($_POST['existing_cover_path'], 'uploads/') === 0) {
                    unlink($_POST['existing_cover_path']);
                }
            } else {
                $_SESSION['error'] = "Ошибка при загрузке файла.";
            }
        } else {
            $_SESSION['error'] = "Разрешены только JPG, PNG и GIF файлы.";
        }
    } 
    // Если указан новый URL, используем его
    elseif (!empty($_POST['cover_path']) && $_POST['cover_path'] !== $_POST['existing_cover_path']) {
        $cover_path = trim($_POST['cover_path']);
    }

    if ($book_id_post > 0 && !empty($title) && $total_quantity >= 0 && $available_quantity >= 0 && $available_quantity <= $total_quantity && !isset($_SESSION['error'])) {
        try {
            $sql = "UPDATE books SET 
                        title = :title, 
                        author = :author, 
                        publication_year = :year, 
                        publisher = :publisher, 
                        genre = :genre, 
                        total_quantity = :total_qty, 
                        quantity_available = :avail_qty, 
                        description = :description,
                        cover_path = :cover_path
                    WHERE book_id = :book_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':year' => $year,
                ':publisher' => $publisher,
                ':genre' => $genre,
                ':total_qty' => $total_quantity,
                ':avail_qty' => $available_quantity,
                ':description' => $description,
                ':cover_path' => $cover_path,
                ':book_id' => $book_id_post
            ]);
            
            $_SESSION['success'] = "Книга ID {$book_id_post} успешно обновлена.";
            header("Location: manage_books.php");
            exit;

        } catch (PDOException $e) {
            $_SESSION['error'] = "Ошибка при сохранении: " . $e->getMessage();
        }
    } elseif (!isset($_SESSION['error'])) {
        $_SESSION['error'] = "Ошибка валидации данных. Убедитесь, что количества положительны и доступно не больше, чем всего.";
    }
}

// --- Если после POST нам нужно перезагрузить данные книги ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['error']) && $book_id_post > 0) {
    try {
        $sql = "SELECT * FROM books WHERE book_id = :book_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':book_id' => $book_id_post]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Игнорируем, если не удалось
    }
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование книги</title>
    <link rel="stylesheet" href="/css/style.css"> 
    <style>
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-row input[type="text"], .form-row input[type="number"], .form-row textarea,
        .form-row input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;}
        .form-row textarea { resize: vertical; height: 100px;}
        .sidebar-preview { margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;}
        .preview-container { display: flex; gap: 20px; align-items: flex-start; }
        .preview-image { max-width: 150px; height: auto; border: 1px solid #ccc; }
        .or-divider { margin: 10px 0; text-align: center; font-weight: bold; color: #666; }
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
        <h2>Редактирование книги: <?php echo $book ? htmlspecialchars($book['title']) : 'Не найдено'; ?></h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if ($book): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <label for="title">Название:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                </div>
                <div class="form-row">
                    <label for="author">Автор:</label>
                    <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                </div>
                <div class="form-row">
                    <label for="year">Год издания:</label>
                    <input type="number" id="year" name="publication_year" value="<?php echo htmlspecialchars($book['publication_year']); ?>" required>
                </div>
                <div class="form-row">
                    <label for="publisher">Издательство:</label>
                    <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($book['publisher']); ?>">
                </div>
                <div class="form-row">
                    <label for="genre">Жанр:</label>
                    <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($book['genre']); ?>">
                </div>
                <div class="form-row">
                    <label for="total_quantity">Общее количество:</label>
                    <input type="number" id="total_quantity" name="total_quantity" value="<?php echo htmlspecialchars($book['total_quantity']); ?>" min="0" required>
                </div>
                <div class="form-row">
                    <label for="quantity_available">Количество в наличии:</label>
                    <input type="number" id="quantity_available" name="quantity_available" value="<?php echo htmlspecialchars($book['quantity_available']); ?>" min="0" required>
                </div>
                <div class="form-row">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($book['description']); ?></textarea>
                </div>
                
                <!-- ОБНОВЛЕННАЯ СЕКЦИЯ ДЛЯ ЗАГРУЗКИ ОБЛОЖКИ -->
                <div class="sidebar-preview">
                    <h3>Обложка книги</h3>
                    
                    <div class="form-row">
                        <label for="cover_file">Загрузить новый файл с компьютера:</label>
                        <input type="file" id="cover_file" name="cover_file" accept="image/jpeg,image/png,image/gif">
                    </div>
                    
                    <div class="or-divider">- ИЛИ -</div>
                    
                    <div class="form-row">
                        <label for="cover_path">Указать новую ссылку на обложку (URL):</label>
                        <input type="text" id="cover_path" name="cover_path" placeholder="https://example.com/cover.jpg" value="<?php echo htmlspecialchars($book['cover_path'] ?? ''); ?>">
                    </div>
                    
                    <p>Текущая обложка / Предварительный просмотр:</p>
                    <div class="preview-container">
                        <img id="coverPreview" src="<?php echo !empty($book['cover_path']) ? htmlspecialchars($book['cover_path']) : 'https://via.placeholder.com/150x200?text=No+Cover'; ?>" alt="Preview" class="preview-image">
                    </div>
                    <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Загрузите новый файл или укажите новый URL для замены обложки.</p>
                    
                    <!-- Скрытое поле для хранения текущего пути к обложке -->
                    <input type="hidden" name="existing_cover_path" value="<?php echo htmlspecialchars($book['cover_path'] ?? ''); ?>">
                </div>

                <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="manage_books.php" class="btn btn-secondary">Отмена</a>
            </form>
        <?php endif; ?>
    </div>
    <script>
        // JS для предпросмотра обложки
        const coverInput = document.getElementById('cover_path');
        const fileInput = document.getElementById('cover_file');
        const preview = document.getElementById('coverPreview');

        function updatePreviewFromFile() {
            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(fileInput.files[0]);
            }
        }

        function updatePreviewFromUrl() {
            if (coverInput.value) {
                preview.src = coverInput.value;
            } else if (!fileInput.files || !fileInput.files[0]) {
                // Если ничего не выбрано, показываем текущую обложку
                const existingPath = document.querySelector('input[name="existing_cover_path"]').value;
                preview.src = existingPath || 'https://via.placeholder.com/150x200?text=No+Cover';
            }
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                updatePreviewFromFile();
                if (fileInput.files && fileInput.files[0]) {
                    coverInput.value = ''; 
                }
            });
        }

        if (coverInput) {
            coverInput.addEventListener('input', function() {
                if (coverInput.value) {
                    fileInput.value = ''; 
                }
                updatePreviewFromUrl();
            });
        }

        // Начальный предпросмотр
        updatePreviewFromUrl();
    </script>
    <footer>
        &copy; Книжный червь 2026. Все права защищены. 
    </footer>
</body>
</html>

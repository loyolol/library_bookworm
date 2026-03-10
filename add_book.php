<?php 
require_once __DIR__ . '/config.php';
session_start(); 

$message = '';
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;

if (!$is_logged_in || $user_role !== 'admin') {
     $_SESSION['error'] = "Доступ запрещен. Эта страница только для администраторов.";
     header("Location: login.php");
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

// Обработка POST запроса с файлом
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $year = filter_var($_POST['publication_year'], FILTER_VALIDATE_INT);
    $publisher = trim($_POST['publisher']);
    $genre = trim($_POST['genre']);
    $total_quantity = filter_var($_POST['total_quantity'], FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);
    
    // Обработка загруженного файла обложки
    $cover_path = '';
    
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
            } else {
                $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Ошибка при загрузке файла.</div>';
            }
        } else {
            $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Разрешены только JPG, PNG и GIF файлы.</div>';
        }
    } elseif (!empty($_POST['cover_path'])) {
        $cover_path = trim($_POST['cover_path']);
    }
    
    if (!empty($title) && !empty($author) && $year && $total_quantity >= 0 && empty($message)) {
        try {
            $sql = "INSERT INTO books (title, author, publication_year, publisher, genre, total_quantity, quantity_available, description, cover_path) 
                    VALUES (:title, :author, :year, :publisher, :genre, :total_qty, :total_qty, :description, :cover_path)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':year' => $year,
                ':publisher' => $publisher,
                ':genre' => $genre,
                ':total_qty' => $total_quantity,
                ':description' => $description,
                ':cover_path' => $cover_path
            ]);
            
            $_SESSION['success'] = "Книга '{$title}' успешно добавлена.";
            header("Location: manage_books.php"); 
            exit;

        } catch (PDOException $e) {
            $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Ошибка базы данных при добавлении: ' . $e->getMessage() . '</div>';
        }
    } elseif (empty($message)) {
        $message = '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px;">Пожалуйста, заполните все обязательные поля корректно.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить Книгу</title>
    <link rel="stylesheet" href="/css/style.css"> 
    <style>
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-row input[type="text"], .form-row input[type="number"], .form-row textarea, 
        .form-row input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;}
        .form-row textarea { resize: vertical; }
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
        <h2>Добавить Новую Книгу</h2>
        <?php echo $message; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <label for="title">Название:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-row">
                <label for="author">Автор:</label>
                <input type="text" id="author" name="author" required>
            </div>
            <div class="form-row">
                <label for="year">Год издания:</label>
                <input type="number" id="year" name="publication_year" required>
            </div>
            <div class="form-row">
                <label for="publisher">Издательство:</label>
                <input type="text" id="publisher" name="publisher">
            </div>
            <div class="form-row">
                <label for="genre">Жанр:</label>
                <input type="text" id="genre" name="genre">
            </div>
            <div class="form-row">
                <label for="total_quantity">Общее количество:</label>
                <input type="number" id="total_quantity" name="total_quantity" min="0" required>
            </div>
            <div class="form-row">
                <label for="description">Описание:</label>
                <textarea id="description" name="description"></textarea>
            </div>
            
            
            <div class="sidebar-preview">
                <h3>Обложка книги</h3>
                
                <div class="form-row">
                    <label for="cover_file">Загрузить файл с компьютера:</label>
                    <input type="file" id="cover_file" name="cover_file" accept="image/jpeg,image/png,image/gif">
                </div>
                
                <div class="or-divider">- ИЛИ -</div>
                
                <div class="form-row">
                    <label for="cover_path">Указать ссылку на обложку (URL):</label>
                    <input type="text" id="cover_path" name="cover_path" placeholder="https://example.com/cover.jpg">
                </div>
                
                <p>Предварительный просмотр:</p>
                <div class="preview-container">
                    <img id="coverPreview" src="https://via.placeholder.com/150x200?text=No+Cover" alt="Preview" class="preview-image">
                </div>
                <p style="font-size: 0.9em; color: #666; margin-top: 5px;">Загрузите файл или укажите URL.</p>
            </div>
            
            <button type="submit" class="btn btn-primary">Добавить Книгу</button>
            <a href="manage_books.php" class="btn btn-secondary">Отмена</a>
        </form>
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
                preview.src = 'https://via.placeholder.com/150x200?text=No+Cover';
            }
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                updatePreviewFromFile();
                coverInput.value = ''; 
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
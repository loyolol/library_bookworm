<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'library_db'); 
define('DB_USER', 'postgres'); 
define('DB_PASS', '2006'); 
define('DB_PORT', '5432'); 


try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";user=" . DB_USER . ";password=" . DB_PASS;
    
    $pdo = new PDO($dsn);
    
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    $pdo->exec("SET NAMES 'utf8'");

} catch (PDOException $e) {
    
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}


?>
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'db_name');
define('DB_PASS', 'db_password');
define('DB_NAME', 'ai_chat_db');

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
} catch(PDOException $e) {
    error_log("数据库错误: " . $e->getMessage() . "\n" . 
              "错误代码: " . $e->getCode() . "\n" . 
              "文件: " . $e->getFile() . "\n" . 
              "行号: " . $e->getLine() . "\n" . 
              "堆栈跟踪: " . $e->getTraceAsString());
    // 数据库连接失败时，返回错误信息并终止程序
    header('Content-Type: text/event-stream; charset=UTF-8');
    echo "data: " . json_encode([
        'error' => '系统维护中，请稍后再试'
    ], JSON_UNESCAPED_UNICODE) . "\n\n";
    exit;
} 
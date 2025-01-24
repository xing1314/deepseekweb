<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h2>系统配置检测结果</h2>";

// 1. 检查必需的PHP扩展
$required_extensions = [
    'curl' => '用于API请求',
    'pdo' => '用于数据库连接',
    'pdo_mysql' => '用于MySQL数据库操作',
    'json' => '用于JSON处理',
    'mbstring' => '用于UTF-8字符处理'
];

echo "<h3>1. PHP扩展检查：</h3><ul>";
foreach ($required_extensions as $ext => $usage) {
    $loaded = extension_loaded($ext);
    echo "<li>{$ext}: " . 
         ($loaded ? 
         "<span style='color: green;'>✓ 已安装</span>" : 
         "<span style='color: red;'>❌ 未安装</span>") .
         " ({$usage})</li>";
}
echo "</ul>";

// 2. 数据库连接测试
echo "<h3>2. 数据库连接测试：</h3>";
try {
    require_once 'db_config.php';
    echo "<p style='color: green;'>✓ 数据库连接成功</p>";
    
    // 检查数据库版本
    $version = $db->query('SELECT VERSION() as version')->fetch();
    echo "<ul>";
    echo "<li>MySQL版本：" . $version['version'] . "</li>";
    
    // 检查数据库权限
    $permissions = [];
    try { $db->query('SELECT 1'); $permissions[] = 'SELECT'; } catch(PDOException $e) {}
    try { $db->query('INSERT INTO chat_history (id) VALUES (NULL)'); $permissions[] = 'INSERT'; } catch(PDOException $e) {}
    try { $db->query('UPDATE chat_history SET id=id LIMIT 0'); $permissions[] = 'UPDATE'; } catch(PDOException $e) {}
    try { $db->query('DELETE FROM chat_history LIMIT 0'); $permissions[] = 'DELETE'; } catch(PDOException $e) {}
    
    echo "<li>数据库权限：" . implode(', ', $permissions) . "</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ 数据库连接失败：" . $e->getMessage() . "</p>";
}

// 3. API配置检查
echo "<h3>3. API配置检查：</h3>";
require_once 'config.php';
echo "<ul>";
echo "<li>API密钥: " . 
     (defined('DEEPSEEK_API_KEY') && strlen(DEEPSEEK_API_KEY) > 20 ? 
     "<span style='color: green;'>✓ 已配置</span>" : 
     "<span style='color: red;'>❌ 未配置或无效</span>") . "</li>";

// 测试API连接
$ch = curl_init('https://api.deepseek.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . DEEPSEEK_API_KEY]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "<li>API连接测试: " . 
     ($http_code == 200 || $http_code == 401 ? 
     "<span style='color: green;'>✓ 可访问</span>" : 
     "<span style='color: red;'>❌ 无法访问 (HTTP {$http_code})</span>") . "</li>";
curl_close($ch);
echo "</ul>";

// 4. 文件权限检查
echo "<h3>4. 文件权限检查：</h3><ul>";
$files_to_check = [
    'config.php' => '配置文件',
    'db_config.php' => '数据库配置',
    'chat.php' => '聊天处理脚本',
    'index.php' => '主页面'
];

foreach ($files_to_check as $file => $desc) {
    $readable = is_readable($file);
    $writable = is_writable($file);
    echo "<li>{$file} ({$desc}): " .
         "读取" . ($readable ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>❌</span>") .
         " | 写入" . ($writable ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>❌</span>") .
         "</li>";
}
echo "</ul>";

// 5. 服务器环境检查
echo "<h3>5. 服务器环境检查：</h3><ul>";
echo "<li>PHP版本：" . PHP_VERSION . "</li>";
echo "<li>最大执行时间：" . ini_get('max_execution_time') . "秒</li>";
echo "<li>内存限制：" . ini_get('memory_limit') . "</li>";
echo "<li>POST大小限制：" . ini_get('post_max_size') . "</li>";
echo "<li>上传文件大小限制：" . ini_get('upload_max_filesize') . "</li>";
echo "<li>显示错误：" . (ini_get('display_errors') ? '开启' : '关闭') . "</li>";
echo "<li>错误报告级别：" . error_reporting() . "</li>";
echo "<li>时区设置：" . date_default_timezone_get() . "</li>";
echo "</ul>";

// 6. 网络环境检查
echo "<h3>6. 网络环境检查：</h3><ul>";
$api_host = 'api.deepseek.com';
$ip = gethostbyname($api_host);
echo "<li>{$api_host} DNS解析: " . ($ip != $api_host ? 
     "<span style='color: green;'>✓ {$ip}</span>" : 
     "<span style='color: red;'>❌ 解析失败</span>") . "</li>";

// 检查SSL/TLS支持
$ch = curl_init('https://api.deepseek.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
echo "<li>SSL/TLS支持: " . 
     (curl_errno($ch) == 0 ? 
     "<span style='color: green;'>✓ 支持</span>" : 
     "<span style='color: red;'>❌ 不支持</span>") . "</li>";
curl_close($ch);
echo "</ul>";

?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    line-height: 1.6;
}
h2, h3 {
    color: #333;
    margin-top: 20px;
}
ul {
    list-style-type: none;
    padding-left: 20px;
    margin-bottom: 20px;
}
li {
    margin: 8px 0;
}
pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
}
.error {
    color: red;
    font-weight: bold;
}
.success {
    color: green;
}
</style> 
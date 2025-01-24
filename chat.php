<?php
// 在文件开头添加错误日志记录
error_log("开始API调用: " . date('Y-m-d H:i:s'));

header('Content-Type: text/event-stream; charset=UTF-8');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once 'config.php';
require_once 'db_config.php';

// 检查数据库中是否存在相同的问题
function checkHistoryInDB($query) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT ai_response 
            FROM chat_history 
            WHERE user_query = :query 
            ORDER BY query_time DESC 
            LIMIT 1
        ");
        
        $stmt->execute([':query' => $query]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['ai_response'] : null;
    } catch(PDOException $e) {
        error_log("查询历史记录失败: " . $e->getMessage());
        return null;
    }
}

// 获取用户IP
function getUserIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $ip;
}

// 记录聊天历史
function recordChatHistory($userQuery, $aiResponse) {
    global $db;
    // 检查数据库连接是否可用
    if (!isset($db) || $db === null) {
        error_log("数据库连接不可用");
        echo "data: " . json_encode([
            'error' => '系统维护中，请稍后再试'
        ], JSON_UNESCAPED_UNICODE) . "\n\n";
        exit;
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO chat_history (user_ip, query_time, user_query, ai_response)
            VALUES (:user_ip, NOW(), :user_query, :ai_response)
        ");
        
        $stmt->execute([
            ':user_ip' => getUserIP(),
            ':user_query' => $userQuery,
            ':ai_response' => $aiResponse
        ]);
        
        return true;
    } catch(PDOException $e) {
        error_log("记录聊天历史失败: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        // 如果记录失败，终止聊天
        echo "data: " . json_encode([
            'error' => '系统维护中，请稍后再试'
        ], JSON_UNESCAPED_UNICODE) . "\n\n";
        exit;
    }
}

// 获取GET参数中的消息并进行URL解码和UTF-8转换
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';
$message = mb_convert_encoding($message, 'UTF-8', 'auto');

if (empty($message)) {
    echo "data: " . json_encode(['error' => '缺少消息内容'], JSON_UNESCAPED_UNICODE) . "\n\n";
    exit;
}

// 先检查数据库中是否有相同的问题
$historicalResponse = checkHistoryInDB($message);
if ($historicalResponse) {
    // 如果找到历史记录，直接返回
    echo "data: " . json_encode([
        'choices' => [
            [
                'delta' => ['content' => $historicalResponse],
                'finish_reason' => 'stop'
            ]
        ]
    ], JSON_UNESCAPED_UNICODE) . "\n\n";
    exit;
}

// 准备发送到Deepseek的数据
$postData = [
    'model' => 'deepseek-chat',
    'messages' => [
        ['role' => 'user', 'content' => $message]
    ],
    'stream' => true,
    'temperature' => 0.7,
    'max_tokens' => 2000
];

// 在curl_init之前添加
$ip = gethostbyname('api-inference.deepseek.com');
error_log("DNS解析结果: " . $ip);

// 开启错误信息收集
$ch = curl_init('https://api.deepseek.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=utf-8',
    'Authorization: Bearer ' . DEEPSEEK_API_KEY
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_RESOLVE, ['api-inference.deepseek.com:443:'.$ip]);

// 在回调函数中收集完整的AI响应
$fullAiResponse = '';

// 在回调函数中修改输出逻辑
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    global $fullAiResponse;
    static $buffer = '';
    static $contentBuffer = '';  // 新增内容缓冲区
    
    // 确保数据是UTF-8编码
    if (!mb_check_encoding($data, 'UTF-8')) {
        $data = mb_convert_encoding($data, 'UTF-8', 'auto');
    }
    
    // 将新数据添加到缓冲区
    $buffer .= $data;
    
    // 处理完整的数据行
    while (($pos = strpos($buffer, "\n")) !== false) {
        $line = substr($buffer, 0, $pos);
        $buffer = substr($buffer, $pos + 1);
        
        // 跳过空行
        if (trim($line) === '') {
            continue;
        }
        
        // 如果是 [DONE] 标记，输出剩余内容并跳过
        if (trim($line) === 'data: [DONE]') {
            if (!empty($contentBuffer)) {
                $jsonData = [
                    'choices' => [[
                        'delta' => ['content' => $contentBuffer]
                    ]]
                ];
                echo "data: " . json_encode($jsonData, JSON_UNESCAPED_UNICODE) . "\n\n";
                ob_flush();
                flush();
                $contentBuffer = '';
            }
            continue;
        }
        
        // 处理数据行
        if (strpos($line, 'data: ') === 0) {
            try {
                $jsonStr = substr($line, 6);
                $jsonData = json_decode($jsonStr, true);
                
                if ($jsonData && isset($jsonData['choices'][0]['delta']['content'])) {
                    // 获取内容
                    $content = $jsonData['choices'][0]['delta']['content'];
                    
                    // 仅删除 ** 标记，保留标记内的内容
                    $content = str_replace('**', '', $content);
                    
                    // 累积AI响应
                    $fullAiResponse .= $content;
                    $contentBuffer .= $content;
                    
                    // 检查是否需要输出
                    if (mb_strlen($contentBuffer) >= 3 || 
                        preg_match('/[，。！？、：；,.!?:;\n]$/u', $contentBuffer)) {
                        $jsonData['choices'][0]['delta']['content'] = $contentBuffer;
                        echo "data: " . json_encode($jsonData, JSON_UNESCAPED_UNICODE) . "\n\n";
                        ob_flush();
                        flush();
                        $contentBuffer = '';
                    }
                }
            } catch (Exception $e) {
                error_log("JSON处理错误: " . $e->getMessage());
            }
        }
    }
    
    return strlen($data);
});

$success = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (!$success || $httpCode !== 200) {
    $error = curl_error($ch);
    echo "data: " . json_encode([
        'error' => "调用API失败\nHTTP状态码: $httpCode\nCURL错误: $error"
    ], JSON_UNESCAPED_UNICODE) . "\n\n";
} else {
    // 记录聊天历史
    recordChatHistory($message, $fullAiResponse);
}

curl_close($ch); 
<?php
// 定义特殊回复
$keywordReplies = array(
    "你是" => "我是由D工作室开发的网页ai--Dai！",
    "你好" => "我不好！",
    "版本" => "Dai-v3.5",
    "乐子" => "不好意思，D工作室现在不招人 不需要你在这里自我介绍",        
    "D工作室" => " D工作室？看看官网 www.dduck.fun",    
);

// 处理聊天请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['model'])) {
    $message = $_POST['message'];
    $model = $_POST['model'];
    $context = json_decode($_POST['context'] ?? '[]', true);

    // 检查关键字回复
    foreach ($keywordReplies as $keyword => $reply) {
        if (strpos($message, $keyword) !== false) {
            echo $reply;
            exit;
        }
    }

    // 构建消息数组
    $messages = $context;
    $messages[] = array(
        "role" => "user",
        "content" => $message
    );

    // 调用Ollama API
    $actualModel = substr($model, 7); // 移除 "ollama/" 前缀
    $url = '你的ollama地址/api/chat';
    $data = array(
        "model" => $actualModel,
        "messages" => $messages,
        "stream" => false
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlErrno === 0 && $response) {
        $responseData = json_decode($response, true);
        if (isset($responseData['message']['content'])) {
            echo $responseData['message']['content'];
        } else {
            echo "Dai未响应，数据格式不符";
        }
    } else {
        echo "Dai未给予回复，错误信息：" . $curlError;
    }
} else {
    echo "此请求不合法";
    exit;
}
?>

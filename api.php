<?php
// 定义特殊回复
$keywordReplies = array(
    "你是" => "我是由D工作室开发的网页ai--Dai！",
    "你好" => "我不好！",
    "版本" => "Dai-v3.5",
    "乐子" => "不好意思，D工作室现在不招人 不需要你在这里自我介绍",        
    "D工作室" => " D工作室？看看官网 www.dduck.fun",        
    "D工作室" => " D工作室？看看官网 www.dduck.fun",    
);

// 处理搜索请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'search') {
    $query = $_POST['query'] ?? '';
    
    // 使用Google Custom Search API
    $apiKey = 'AIzaSyB3J0J0J0J0J0J0J0J0J0J0J0J0J0J0J0';
    $cx = '012345678901234567890:abcdefghijk';
    $url = "https://www.googleapis.com/customsearch/v1?q=".urlencode($query)."&key=$apiKey&cx=$cx";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $results = [];
    
    if (isset($data['items'])) {
        foreach ($data['items'] as $item) {
            $results[] = [
                'title' => $item['title'],
                'link' => $item['link'],
                'snippet' => $item['snippet']
            ];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['results' => $results]);
    exit;
}

// 处理聊天请求
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['model'])) {
    $message = $_POST['message'];
    $model = $_POST['model'];
    $context = json_decode($_POST['context'] ?? '[]', true);

    // 检查关键字回复
    foreach ($keywordReplies as $keyword => $reply) {
        if (strpos($message, $keyword) !== false) {
            if (filter_var($reply, FILTER_VALIDATE_URL)) { 
                header("Location: $reply"); 
                exit;
            } else {
                echo $reply;
                exit;
            }
        }
    }

    // 构建消息数组
    $messages = $context; // 添加上下文
    $messages[] = array(
        "role" => "user",
        "content" => $message
    );

    // 调用 API
    $url = 'api接口地址';
    $data = array(
        "model" => $model,
        "messages" => $messages,
        "temperature" => 0.5
    );
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Bearer api密钥'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

    $response = curl_exec($ch);
    $curlErrno = curl_errno($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlErrno === 0) {
        if ($response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['message']['content'])) {
                $content = $responseData['message']['content'];
                
                // 处理思考过程标签
                $pattern = '/<think>(.*?)<\/think>/s';
                preg_match_all($pattern, $content, $matches);
                
                if (!empty($matches[1])) {
                    foreach($matches[1] as $index => $think) {
                        $id = uniqid('think_');
                        $replacement = "<div class='think-toggle' data-target='$id'>显示思考过程</div><div id='$id' class='think-content'>$think</div>";
                        $content = str_replace($matches[0][$index], $replacement, $content);
                    }
                }
                
                echo $content;
            } else {
                echo "Dai未响应，数据格式不符";
            }
        } else {
            echo "Dai未作出有效回复内容";
        }
    } else {
        echo "Dai未给予回复，错误信息：" . $curlError;
    }
} else {
    echo "此请求不合法";
    exit;
}
?>

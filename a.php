<?php
// 定义特殊回复
$keywordReplies = array(
    "你是" => "我是由D工作室开发的网页ai--Dai！",
    "你好" => "我不好！",
    "版本" => "老子是Dai-接入DeepSeek-r1！",
    "清欢" => "你还提清欢云？ 他他妈太牛逼了 虚拟主机竟然卖出了0元的天价 这简直就是在扰乱市场秩序 你可以试着去用用 官网 qinghuany.cn 不过请你记住 跑路是他们的基本业务 笑是一种礼貌，也是圈光你的警告！",
    "eca" => "你也配提eca？ eca工作室是一个现代的网络技术工作室 多次对D工作室提供技术支持 官网 eyun.xyz",
    "ECA" => "你也配提eca？ eca工作室是一个现代的网络技术工作室 多次对D工作室提供技术支持 官网 eyun.xyz",    
   "eyun" => "你也配提eca？ eca工作室是一个现代的网络技术工作室 多次对D工作室提供技术支持 官网 eyun.xyz",
    "Eyun" => "你也配提eca？ eca工作室是一个现代的网络技术工作室 多次对D工作室提供技术支持 官网 eyun.xyz",        
    "乐子" => "不好意思，D工作室现在不招人 不需要你在这里自我介绍",        
    "dduck.fun" => "dduck.fun是D工作室的主域名 D工作室的官网是 www.dduck.fun 你他妈又想干嘛！",    
    "D工作室" => " D工作室？看看官网 www.dduck.fun",        
    "D工作室" => " D工作室？看看官网 www.dduck.fun",    
            
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['model'])) {
    $message = $_POST['message'];
    $model = $_POST['model'];
} else {
    echo "此请求不合法";
    exit;
}

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

$url = 'https://openrouter.ai/api/v1/chat/completions';
$data = array(
    "model" => $model,
    "messages" => array(
        array(
            "role" => "user",
            "content" => "" . $message // 定义ai信息
        )
    ),
    "temperature" => 0.5
);
$headers = array(
    'Content-Type: application/json',
    'Authorization: Bearer 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'
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
        if (isset($responseData['choices'][0]['message']['content'])) {
            echo $responseData['choices'][0]['message']['content'];
        } else {
            echo "Dai未响应，数据格式不符";
        }
    } else {
        echo "Dai未作出有效回复内容";
    }
} else {
    echo "Dai未给予回复，错误信息：" . $curlError;
}
?>
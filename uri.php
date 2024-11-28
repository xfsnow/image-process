<?php
// 参考 https://segmentfault.com/q/1010000043329246
# 引用 UriCode 类
require_once 'lib/UriCode.php';
$uri = 'w122h333split1h2000rotate180';
// $uri = 'w122h333s1r1';
// $uri = 'w122';
// $test = UriCoder::encode($uri);
// echo "echo UriCoder::encode('$uri');\n".$test;
// phpinfo();

// 示例用法
$input = '0abc123dfadrcvg';
$encoded = UriCode::encode($input);
$decoded = UriCode::decode($encoded);
echo "Input: $input Encoded: $encoded Decoded: $decoded". ($input === $decoded ? ' OK' : ' Error'). " ". (strlen($input) -strlen($encoded)) . " shortened\n";

// 用 gzip 压缩后的字符串进行编码和解码，对比来看压缩后的字符串反而更长了
// $compressed = gzencode($input, 9);
// echo 'input: '.strlen($input).' compressed:'. strlen($compressed)."\n";
// $encoded = base64_encode($compressed);
// $decoded = gzdecode(base64_decode($encoded));
// echo "Compressed: $compressed Encoded: $encoded Decoded: $decoded". ($compressed === $decoded ? ' OK' : ' Error'). " ". (strlen($compressed) -strlen($encoded)) . " shortened\n";

// 随机生成 20 个字符串，输入字符串长度10-50，验证编码和解码的正确性
$numTest = 20;
$inputChars = '0123456789abcdefghijklmnopqrstuvwxyz';
for ($i = 0; $i < $numTest; $i++) {
    $input = '';
    // 随机生成长度为 10-50 的字符串
    $inputLength = rand(10, 50);
    for ($j = 0; $j < $inputLength; $j++) {
        $input .= $inputChars[rand(0, strlen($inputChars) - 1)];
    }
    $encoded = UriCode::encode($input);
    $decoded = UriCode::decode($encoded);

    echo "Inputed: $input \nEncoded: $encoded \nDecoded: $decoded\n". ($input === $decoded ? ' Same' : ' Wrong'). "\nInput lenth: ". strlen($input).', '.(strlen($input) -strlen($encoded)) . " char shortened\n\n";
}
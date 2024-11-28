<?php
// 把实时生成图片缩略图的参数编码成 URI
// 如 /?filename=ms.jpg&width=200&height=200&crop_x=0&crop_y=0&crop_w=200&crop_h=200&flip=1&rotate=270
// 编码成 /ms_w200h200cx0cy0cw200ch200f1r270.jpg
require_once 'lib/ImgCode.php';
require_once 'lib/UriCode.php';
require_once 'vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;

// 创建 BlobRestProxy 对象
$connectionString = getenv("AZURE_BLOB_CONNECTION");
$blobContainerName = getenv("AZURE_BLOB_CONTAINER");
error_log("connectionString: " . $connectionString);
$blobClient = BlobRestProxy::createBlobService($connectionString);

// 测试访问 http://localhost:8080/ms_e8t8sssxWpLByP1F26VWdD0~Ru.jpg
// 获取URI中的文件名部分
$requestUri = $_SERVER['REQUEST_URI'];
$filename = substr($requestUri, strrpos($requestUri, '/') + 1);
$outputType = substr($filename, strrpos($filename, '.') + 1);
// echo $outputType . "\n";
// echo $filename . "\n";
// 文件名拆解，第一个 _ 之前的是文件本身的名字，最后一个 . 符后面是文件扩展名，之间的是 $compressd，把文件本身和扩展名合并成真实的文件名
$realFilename = substr($filename, 0, strrpos($filename, '_')). substr($filename, strrpos($filename, '.'));
// echo $realFilename . "\n";
// $compressd 是 _ 和 . 之间的部分
$compressd = substr($filename, strrpos($filename, '_') + 1, strrpos($filename, '.') - strrpos($filename, '_') - 1);
// echo $compressd . "\n";
// 解码 $compressd
$decompressd = UriCode::decompress($compressd);
// echo $decompressd . "\n";
// 解码后的字符串解析成数组
$decoded = ImgCode::decode($decompressd);
// print_r($decoded) . "\n";

$outputImageMethod='image' . $outputType;
$outputImageHeader='Content-Type: image/' . $outputType;
// 从 Azure Blob Storage 中读取图像文件内容，文件名是 $filename
try {
    $getBlobResult = $blobClient->getBlob($blobContainerName, $realFilename);
    $content = stream_get_contents($getBlobResult->getContentStream());

    $outputImage = imagecreatefromstring($content);
    $originial_width = imagesx($outputImage);
    $originial_height = imagesy($outputImage);
    $width = $decoded['w'];
    $height = $decoded['h'];
    // 使用 GD 库调整图像大小
    if ($width >0 && $height >0)  {
        $resizedImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($resizedImage, $outputImage, 0, 0, 0, 0, $width, $height, $originial_width, $originial_height);
        $outputImage = $resizedImage;
    }

    // 读取调整大小后的图像内容
    ob_start();
    // TODO 现在是写死的 imagejpeg() 方法，还得支持 imagepng() 和 imagegif()，以及扩展名是 jpg，但是输出类型是 image/jpeg 的情况
        imagejpeg($outputImage);
        $imageData = ob_get_contents();
    ob_end_clean();

    // 把读取到的内容输出到浏览器
    header($outputImageHeader);
    echo $imageData;
} catch (ServiceException $e) {
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code . ": " . $error_message . "\n";
}

<?php
require_once 'vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\GetBlobOptions;

// 创建 BlobRestProxy 对象
$connectionString = getenv("AZURE_BLOB_CONNECTION");
$blobContainerName = getenv("AZURE_BLOB_CONTAINER");
error_log("connectionString: " . $connectionString);
$blobClient = BlobRestProxy::createBlobService($connectionString);

// 处理请求
$filePath = $_SERVER['REQUEST_URI'];
$filename = $_REQUEST['filename'];
$width = isset($_REQUEST['width']) ? $_REQUEST['width'] :'';
$height = isset($_REQUEST['height']) ? $_REQUEST['height'] :'';
$flip = isset($_REQUEST['flip']) ? $_REQUEST['flip'] :'';
$crop = isset($_REQUEST['crop']) ? $_REQUEST['crop'] :'';
$rotate = isset($_REQUEST['rotate']) ? $_REQUEST['rotate'] :'';
$supportedOutputTypes = array('png', 'jpeg', 'gif', 'webp');
$outputType = isset($_REQUEST['outputtype']) && in_array($_REQUEST['outputtype'], $supportedOutputTypes) ? $_REQUEST['outputtype'] :'png'; 
$outputImageMethod='image' . $outputType;
$outputImageHeader='Content-Type: image/' . $outputType;
// 从 Azure Blob Storage 中读取图像文件内容，文件名是 $filename
try {
    $getBlobResult = $blobClient->getBlob($blobContainerName, $filename);
    $content = stream_get_contents($getBlobResult->getContentStream());
    
    $outputImage = imagecreatefromstring($content);
    $originial_width = imagesx($outputImage);
    $originial_height = imagesy($outputImage);
    // 使用 GD 库调整图像大小
    if ($width >0 && $height >0)  {
        $resizedImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($resizedImage, $outputImage, 0, 0, 0, 0, $width, $height, $originial_width, $originial_height);
        $outputImage = $resizedImage;
    }
    
    // 如果 flip 包含 | 或 -，则翻转图像
    if (false !== strpos($flip, '|') || false !== strpos($flip, '-'))
    {
        // 使用 GD 库翻转图像
        $flipedImage = imagecreatetruecolor($width, $height);
        // 如果 flip 包含 |，则水平翻转图像
        if (false !== strpos($flip, '|'))
        {
            for ($x = 0; $x < $width; $x++) {
                imagecopy($flipedImage, $resizedImage, $width - $x - 1, 0, $x, 0, 1, $height);
            }
        }
        // 如果 flip 包含 -，则垂直翻转图像
        if (false !== strpos($flip, '-'))
        {
            for ($y = 0; $y < $height; $y++) {
                imagecopy($flipedImage, $resizedImage, 0, $height - $y - 1, 0, $y, $width, 1);
            }
        }
        $outputImage = $flipedImage;
        // TODO 同时包含 | 和 - 的情况还得再确认一下
    }

    // crop 参数格式为 x,y,w,h，先按,分割到4个新变量
    // 用正则表达式判断 crop 参数值满足格式要求，x,y,w,h 是 4 个整数，否则抛出异常
    if (''!=$crop) {
        if (preg_match('/^\d+,\d+,\d+,\d+$/', $crop)) {
            $cropParams = explode(',', $crop);
            $crop_x = $cropParams[0];
            $crop_y = $cropParams[1];
            $crop_w = $cropParams[2];
            $crop_h = $cropParams[3];
        
            // 使用 GD 库裁剪图像
            $croppedImage = imagecreatetruecolor($crop_w, $crop_h);
            imagecopy($croppedImage, $outputImage, 0, 0, $crop_x, $crop_y, $crop_w, $crop_h);    
            $outputImage = $croppedImage;
        }
        else {
            throw new ServiceException('Invalid crop parameter', 400);
        }
     }
    //  TODO 翻转和裁剪先后顺序不同时效果不同，需要再细化一下。

    // 使用 GD 库旋转图像，旋转后背景补成白色
    if (''!=$rotate && is_numeric($rotate)) {
        // 创建一个新的图像资源，用于存储旋转后的图像
        $rotatedImage = imagerotate($outputImage, $rotate, 0);

        // // 获取旋转后的图像的宽度和高度
        // $rotated_width = imagesx($rotatedImage);
        // $rotated_height = imagesy($rotatedImage);

        // // 创建一个白色的图像资源，用于存储旋转后的图像
        // $whiteImage = imagecreatetruecolor($rotated_width, $rotated_height);
        // $white = imagecolorallocate($whiteImage, 255, 255, 255);
        // imagefill($whiteImage, 0, 0, $white);

        // // 将旋转后的图像复制到白色的图像中
        // imagecopy($whiteImage, $rotatedImage, 0, 0, 0, 0, $rotated_width, $rotated_height);
        // TODO 旋转后的图像背景补成白色的方法还有待研究，暂时先不补白色背景
        $outputImage = $rotatedImage;
    }

    // 读取调整大小后的图像内容 
    ob_start();
    $outputImageMethod($outputImage);
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
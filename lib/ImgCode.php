<?php
// 把实时生成图片缩略图的参数编码成 URI
// 如 /?filename=ms.jpg&width=200&height=200&crop_x=0&crop_y=0&crop_w=200&crop_h=200&flip=1&rotate=270
// 编码成 /ms_w200h200cx0cy0cw200ch200f1r270.jpg

class ImgCode {
    // 可用字符表
    private static $inputChars = '0123456789abcdefghijklmnopqrstuvwxyz';
    private static $outputChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_.~';

    // 编码方法
    // 输入参数 $param，参数可选，并且有默认值 ['w' => 100, 'h' => 100, 'cx' => 0, 'cy' => 0, 'cw' => 0, 'ch' => 0, 'f' => 0, 'r' => 0]
    // 各参数含义：
    // w: 缩略图宽度
    // h: 缩略图高度
    // cx: 裁剪区域左上角 x 坐标    cy: 裁剪区域左上角 y 坐标     cw: 裁剪区域宽度     ch: 裁剪区域高度，这4个参数必须同时存在
    // f: 是否翻转，0 不翻转，1 垂直翻转，2 水平翻转
    // r: 旋转角度，顺时针方向，0, 90, 180, 270
    // 返回值为字符串，如 w200h200cx0cy0cw200ch200f1r270
    public static function encode($input) {
        $output = '';
        $output .= 'w'.$input['w'];
        $output .= 'h'.$input['h'];
        if ($input['cw'] > 0 && $input['ch'] > 0) {
            $output .= 'cx'.$input['cx'];
            $output .= 'cy'.$input['cy'];
            $output .= 'cw'.$input['cw'];
            $output .= 'ch'.$input['ch'];
        }
        $output .= 'f'.$input['f'];
        $output .= 'r'.$input['r'];
        return $output;
    }

    // 解码方法
    // 输入参数 $input，形如 w200h200cx0cy0cw200ch200f1r270
    // 不用正则表达式一步到位解析，而是先用简单的方法解析，然后再用正则表达式解析
    // 再从 w200h200cx0cy0cw200ch200f1r270 中解析出各个参数，用简单的模式 字母+数字 来解析，然后再具体解析每组字母的含义
    // 返回值为数组，如 ['w' => 200, 'h' => 200, 'cx' => 0, 'cy' => 0, 'cw' => 200, 'ch' => 200, 'f' => 1, 'r' => 270]
    public static function decode($input) {
        $output = [];
        // $input 按数字和字母分割
        $split = preg_split('/(?<=[0-9])(?=[a-z])|(?<=[a-z])(?=[0-9])/i', $input);
        // print_r($split);
        // $split 中偶数位是key，奇数位是 value，组织成数组放到 $output 中
        for ($i = 0; $i < count($split); $i += 2) {
            $output[$split[$i]] = $split[$i + 1];
        }

        return $output;
    }
}

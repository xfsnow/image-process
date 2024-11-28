<?php
// 参考 https://segmentfault.com/q/1010000043329246

class UriCode {
    // 可用字符表
    private static $inputChars = '0123456789abcdefghijklmnopqrstuvwxyz';
    private static $outputChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_.~';

    // 编码方法
    public static function compress($input) {
        if (empty($input)) {
            return '';
        }

        // 将输入字符串转为十进制数
        $inputBase = strlen(self::$inputChars);
        $outputBase = strlen(self::$outputChars);
        $decimal = '0';
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $decimal = bcadd(bcmul($decimal, $inputBase), strpos(self::$inputChars, $input[$i]));
        }

        // 将十进制数转为输出字符串的进制
        $result = '';
        while (bccomp($decimal, '0') > 0) {
            $remainder = bcmod($decimal, $outputBase);
            $result = self::$outputChars[$remainder] . $result;
            $decimal = bcdiv($decimal, $outputBase, 0);
        }

        // 记录前导零的数量并添加到结果中
        $leadingZeros = 0;
        while ($leadingZeros < strlen($input) && $input[$leadingZeros] === '0') {
            $leadingZeros++;
        }
        return str_repeat(self::$outputChars[0], $leadingZeros) . $result;
    }

    // 解码方法
    public static function decompress($input) {
        if (empty($input)) {
            return '';
        }

        // 读取前导零的数量
        $leadingZeros = 0;
        while ($leadingZeros < strlen($input) && $input[$leadingZeros] === self::$outputChars[0]) {
            $leadingZeros++;
        }
        $input = substr($input, $leadingZeros);

        // 将输入字符串转为十进制数
        $inputBase = strlen(self::$outputChars);
        $outputBase = strlen(self::$inputChars);
        $decimal = '0';
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $decimal = bcadd(bcmul($decimal, $inputBase), strpos(self::$outputChars, $input[$i]));
        }

        // 将十进制数转为输出字符串的进制
        $result = '';
        while (bccomp($decimal, '0') > 0) {
            $remainder = bcmod($decimal, $outputBase);
            $result = self::$inputChars[$remainder] . $result;
            $decimal = bcdiv($decimal, $outputBase, 0);
        }

        // 在结果字符串的开头添加前导零
        return str_repeat('0', $leadingZeros) . $result;
    }
}

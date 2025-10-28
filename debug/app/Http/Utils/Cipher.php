<?php
namespace App\Http\Utils;

class Cipher
{
    public static $STATIC_KEY1 = 58251;
    public static $STATIC_KEY2 = 3262;

    public static function truemod($num, $mod): int {
        return ($mod + ($num % $mod)) % $mod;
    }

    public static function encrypt($str, $key1, $key2): string
    {
        $inv256 = [1, 171, 205, 183, 57, 163, 197, 239, 241, 27, 61, 167, 41, 19, 53, 223, 225, 139, 173, 151, 25, 131, 165, 207, 209, 251, 29, 135, 9, 243, 21, 191, 193, 107, 141, 119, 249, 99, 133, 175, 177, 219, 253, 103, 233, 211, 245, 159, 161, 75, 109, 87, 217, 67, 101, 143, 145, 187, 221, 71, 201, 179, 213, 127, 129, 43, 77, 55, 185, 35, 69, 111, 113, 155, 189, 39, 169, 147, 181, 95, 97, 11, 45, 23, 153, 3, 37, 79, 81, 123, 157, 7, 137, 115, 149, 63, 65, 235, 13, 247, 121, 227, 5, 47, 49, 91, 125, 231, 105, 83, 117, 31, 33, 203, 237, 215, 89, 195, 229, 15, 17, 59, 93, 199, 73, 51, 85, -1];

        $K = $key1; $F = 16384 + $key2;
        $newStr = '';
        for ($i = 0; $i < strlen($str); $i++){
            $m = $str[$i];
            $L = fmod($K, 274877906944);
            $H = ($K - $L) / 274877906944;
            $M = fmod($H, 128);
            $m = ord($m);
            $c = Cipher::truemod(($m * $inv256[$M] - ($H - $M) / 128), 256);
            $K = $L * $F + $H + $c + $m;
            $newStr .= sprintf("%02x", $c);
        }
        return $newStr;
    }


    public static function decrypt($str, $key1, $key2): string
    {
        $K = $key1; $F = 16384 + $key2;
        $newStr = '';
        preg_match_all('|[a-f0-9]{2}|', $str, $matches);
        $match = $matches[0];
        for ($i = 0; $i < sizeof($match); $i++){
            $m = $match[$i];
            $L = fmod($K, 274877906944);
            $H = ($K - $L) / 274877906944;
            $M = fmod($H, 128);

            $m = intval($m, 16);
            $c = Cipher::truemod(($m + ($H - $M) / 128) * (2 * $M + 1), 256);
            $K = $L * $F + $H + $c + $m;

            $newStr .= chr($c);
        }
        return $newStr;
    }
}
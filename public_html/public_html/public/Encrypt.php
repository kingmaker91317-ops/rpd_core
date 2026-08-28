<?php
class Encrypt {
    public function __construct() {
    }
    public static function HMGENC($input) {
        $base64Encoded = base64_encode($input);
        $length = strlen($base64Encoded);
        $reversed = '';
        for ($i = 0; $i < $length; $i += 2) {
            if ($i + 1 < $length) {
                $reversed .= $base64Encoded[$i + 1] . $base64Encoded[$i];
            } else {
                $reversed .= $base64Encoded[$i];
            }
        }
        return "HMGENC" . base64_encode($reversed);
    }

    public static function HMGDEC($input) {
        if (substr($input, 0, 6) === "HMGENC") {
            $input = substr($input, 6);
            $input = base64_decode($input);
            $length = strlen($input);
            $reversed = '';
            for ($i = 0; $i < $length; $i += 2) {
                if ($i + 1 < $length) {
                    $reversed .= $input[$i + 1] . $input[$i];
                } else {
                    $reversed .= $input[$i];
                }
            }
            $decodedString = base64_decode($reversed);
            return $decodedString;
        } else {
            return "";
        }
    }
    public static function encrypt($text) {
        $numbers = array();
        $length = strlen($text); // Thay thế mb_strlen
        for ($i = 0; $i < $length; $i++) {
            $char = substr($text, $i, 1); // Thay thế mb_substr
            $numbers[] = ord($char) * 50;
        }
        return json_encode($numbers);
    }
    
    public static function decrypt($encryptedNumbers) {
        $numbers = json_decode($encryptedNumbers);
        if ($numbers === null) {
            return "";
        }
        $text = "";
        foreach ($numbers as $number) {
            $text .= chr($number / 50);
        }
        return $text;
    }

}
?>
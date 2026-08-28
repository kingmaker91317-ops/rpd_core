<?php

function pkcs7_unpad($data) {
  $pad = ord(substr($data, -1));
  if ($pad < 1 || $pad > 16) {
    throw new Exception("Bad padding");
  }
  if (substr($data, -$pad) !== str_repeat(chr($pad), $pad)) {
    throw new Exception("Bad padding");
  }
  return substr($data, 0, -$pad);
}

function pkcs7_pad($data, $blockSize = 16) {
  $pad = $blockSize - (strlen($data) % $blockSize);
  return $data . str_repeat(chr($pad), $pad);
}

function aes_cbc_decrypt($keyStr, $ivHex, $payloadB64OrUrl) {
  $iv = hex2bin($ivHex);
  if ($iv === false || strlen($iv) !== 16) {
    throw new Exception("Bad iv hex");
  }

  $payloadB64 = rawurldecode($payloadB64OrUrl);
  if (strpos($payloadB64, " ") !== false && strpos($payloadB64, "+") === false) {
    $payloadB64 = str_replace(" ", "+", $payloadB64);
  }

  $ciphertext = base64_decode($payloadB64, true);
  if ($ciphertext === false) {
    throw new Exception("Bad base64");
  }

  $plaintext = openssl_decrypt(
    $ciphertext,
    "AES-128-CBC",
    $keyStr,
    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
    $iv
  );

  if ($plaintext === false) {
    throw new Exception("Decrypt failed");
  }

  return pkcs7_unpad($plaintext);
}

function aes_cbc_encrypt($keyStr, $ivHex, $plaintext) {
  $iv = hex2bin($ivHex);
  if ($iv === false || strlen($iv) !== 16) {
    throw new Exception("Bad iv hex");
  }

  $ciphertext = openssl_encrypt(
    pkcs7_pad($plaintext),
    "AES-128-CBC",
    $keyStr,
    OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
    $iv
  );

  if ($ciphertext === false) {
    throw new Exception("Encrypt failed");
  }

  return base64_encode($ciphertext);
}

header("Content-Type: application/json; charset=utf-8");

try {
  $keyStr = "pKveGj379TVcDuSH";

  $action = $_GET["action"] ?? $_POST["action"] ?? "";
  $ivHex = $_GET["iv"] ?? $_POST["iv"] ?? "";
  $payload = $_GET["payload"] ?? $_POST["payload"] ?? "";

  if ($ivHex === "" || $payload === "") {
    throw new Exception("Missing iv or payload");
  }

  $requestPlaintext = aes_cbc_decrypt($keyStr, $ivHex, $payload);
  $requestData = json_decode($requestPlaintext, true);
  if (!is_array($requestData)) {
    throw new Exception("Bad request json");
  }

  if ($action === "" && isset($requestData["action"])) {
    $action = (string)$requestData["action"];
  }

  $reqGetFileInfo = isset($requestData["get_file_info"]) && (string)$requestData["get_file_info"] === "1";
  $reqGetFile = isset($requestData["get_file"]) && (string)$requestData["get_file"] === "1";
  $reqHasLibname = isset($requestData["libname"]) && (string)$requestData["libname"] !== "";

  $isGetFileInfo =
    (isset($_GET["get_file_info"]) && (string)$_GET["get_file_info"] === "1") ||
    (isset($_POST["get_file_info"]) && (string)$_POST["get_file_info"] === "1") ||
    $reqGetFileInfo ||
    $action === "get_file_info";

  $isGetFile =
    (isset($_GET["get_file"]) && (string)$_GET["get_file"] === "1") ||
    (isset($_POST["get_file"]) && (string)$_POST["get_file"] === "1") ||
    $reqGetFile ||
    $action === "get_file";

  if (!$isGetFile && !$isGetFileInfo && $reqHasLibname && $action === "") {
    $isGetFile = true;
  }

  $isCheckStatus =
    (isset($_GET["check_status"]) && (string)$_GET["check_status"] === "1") ||
    (isset($_POST["check_status"]) && (string)$_POST["check_status"] === "1") ||
    $action === "check_status";
  $isCheckStatus = $isCheckStatus && !$isGetFile && !$isGetFileInfo;

  if ($isGetFileInfo) {
    $responseData = [
      "status" => "success",
      "file_size" => 5946904,
      "original_size" => 4460176,
      "file_size_mb" => 5.67,
      "original_size_mb" => 4.25,
      "file_hash" => "308ef5b287cc36d0efe8de7d21c31b8a",
      "version" => $requestData["app_identifier"] ?? "ESP-2402",
      "libname" => $requestData["libname"] ?? "libESP-2402.so",
      "encoding" => "base64"
    ];
  } else if ($isGetFile) {
    $libname = $requestData["libname"] ?? "libESP-2402.so";
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . basename($libname);

    if (!file_exists($filePath)) {
      $responseData = [
        "status" => "error",
        "message" => "File not found"
      ];
    } else {
      $rawFile = file_get_contents($filePath);
      if ($rawFile === false) {
        $responseData = [
          "status" => "error",
          "message" => "Read failed"
        ];
      } else {
        $responseData = [
          "status" => "success",
          "version" => $requestData["app_identifier"] ?? "ESP-2402",
          "total_size" => strlen($rawFile),
          "data" => base64_encode($rawFile),
          "safety" => "unsafe",
          "safety_message" => "Đây là phiên bản mới , vui lòng chơi acc phụ từ 24-48h để kiểm tra độ an toàn đã ."
        ];
      }
    }
  } else if ($isCheckStatus) {
    $checkStatusJson = <<<'JSON'
{"status":"success","data":{"libESP-2402.so":{"version":"ESP-2402","safety":"unsafe","safety_message":"\u0110\u00e2y l\u00e0 phi\u00ean b\u1ea3n m\u1edbi , vui l\u00f2ng ch\u01a1i acc ph\u1ee5 t\u1eeb 24-48h \u0111\u1ec3 ki\u1ec3m tra \u0111\u1ed9 an to\u00e0n \u0111\u00e3 .","description":"Full ch\u1ee9c n\u0103ng ESP + Camera ch\u1ec9nh - AIM Skill + Mod Full Skin kh\u00f4ng l\u1ed7i m\u1ea1ng","icon":"VIP NEW","title":"ESP VIP NEW","status":"ready","vpn_check_enabled":false}},"menu_colors":{"MENU_BG_COLOR":"#fff0f5","MENU_FEATURE_BG_COLOR":"#ffffff","BTN_COLOR":"#e91e63","TEXT_COLOR":"#880e4f","TEXT_COLOR_2":"#ad1457","ToggleON":"#e91e63","ToggleOFF":"#9e9e9e","SeekBarProgressColor":"#e91e63","SubtitleColor":"#f48fb1","MENU_CORNER":20,"CARD_CORNER":14,"MENU_BORDER_COLOR":"#f8bbd0","TITLE_COLOR":"#880e4f","CLOSE_BTN_COLOR":"#d32f2f","LOGOUT_BTN_COLOR":"#ff6f00","BUTTON_GRADIENT_START":"#e91e63","BUTTON_GRADIENT_END":"#c2185b","BTN_ON_GRADIENT_START":"#e91e63","BTN_ON_GRADIENT_END":"#f06292","BTN_OFF_GRADIENT_START":"#9e9e9e","BTN_OFF_GRADIENT_END":"#757575","DIALOG_POSITIVE_BTN_COLOR":"#e91e63","DIALOG_NEGATIVE_BTN_COLOR":"#bdbdbd","DIALOG_BG_COLOR":"#ffffff","DIALOG_BORDER_COLOR":"#f8bbd0","HINT_TEXT_COLOR":"#bdbdbd","DIVIDER_COLOR":"#fce4ec","CATEGORY_TEXT_COLOR":"#e91e63","CATEGORY_BORDER_COLOR":"#fce4ec","SPINNER_BG_COLOR":"#ffffff","SPINNER_BORDER_COLOR":"#e91e63","EDITTEXT_BG_COLOR":"#ffffff","EDITTEXT_BORDER_COLOR":"#f8bbd0"},"menu_icon":"iVBORw0KGgoAAAANSUhEUgAAAE4AAABOCAYAAACOqiAdAAAAAXNSR0IArs4c6QAAAARzQklUCAgICHwIZIgAABHPSURBVHic7Zx7dNXVlcc\/+\/e775ub94OEAOEZVEAEFBFbQpVpabUdncaOndFCa2PramfaVWc6dtohrJlaVzt9IJ3OSLV1ZmlXCz5AEChFyVi0IFBFLIIQHoGQkABJSHKfv\/vb88cNEDS59ya5yeiM37Wysu49j31+37vPPufsvc9PGCYotQY1y0zAiQ8XcZzEMXEhhInjIYoQ4TVi1ElcQIdrLMMByXSHWr3apLzahUUWTvKAfIQ8hACKG0UwiWBzHjgDtBCljX0EpU6sTI9nuJAx4pRag+plbirIIU4pBhXAeIRylCKEbBR3j8wI0I7QiHIY4QAhjiKcZaVE3w\/alxHitEad+AlgUoZQiTINmAqMQyjEIgsTFzYmBoIQB8JAG0oDyl5gJzZvcJIm1kj4vU7ekIhTEJaomwCFOJmMMAthFn6mPlX5zJjnStYFduXtcB4tOG7EHDFQoeLcGG5s\/pDefegLetOxBTGULoQGlF3Ai8AuIpySlRLJzCMODwZNnKJCDV78lGEwA4MbyGXuV+f87ZWPXvFYbtjoNlL0rvOabuSXW35uV7ZN7UZpQNiOspkwu3iTlveyzTMG00hR4X58+BiHwTycfOJ7N\/3g0567vTf8dNrD+WEzJWkA8ofS7TL7r643toz9bRYwFrgOmIebScwkS6kd1PhGAgPWOAXhLvVRwFgcXM+E+CenfmTaooNZB\/yD1V9P3KNbn62z55+c2w68hrIeZQtRjr5Xp+zAf9El6iaPMgyuPTp3453uW\/y3Hgy8izT1qOPQuJj7h+MjrjsKo1SZyhKE+r66DJthuW3xLUaL71wAmAjMRJmEi0AyrVMQrV5tao06tVpdif+rTR2GbdY7MSABWqNOshiFMHfDzT\/48idnfHOhopf1UWy5jz7UPmf50me3P00tQUHsi4UtjCLKHoSyvvr\/1NHbde3ap7uAfcBalOfZT71serfWafVqk0C1Dz85uMjCxomTKDG66OA8RwgNp410pFtRa9XgFDkYVP5+0UOfu3XaAwu5nHj7gfarn3xw4+vLeJFTbOpjP1ZMs9nIt+Pwi75krBv\/DC+MfcFzU8NNo4FKDPZSQZPCZX0palBINm7GY3IFBmMw8GITxOAUudQzjWNaoed4XCLDsbVJS+N62bVKPvybGmPunV9U9OIUcqgRix1e+i889eijnOaMrJFof33N2Iv\/jUKagay+yme3ztPdT75yHtgNrMbmt+ypa5S6hRe1R5eohwImoCzEZAHKBAQ3EEZpAt4EdhLmDbpoGg7y0tO42eogm0KmtX4oMHvJ5y4nzYzF3nzon9ly\/+OpSAN442q6zRNsjxt8rK\/yPUV\/YPvYlz03NswfhVIBFFJU1QJcmnYBXLgZzcSDNz\/h3Dk3FA\/lzjtzgzmtdboFjEMpQ8jFhYN8XqVGm1klsbSeNU2kJE5BmI2PHCbNWTDr612OsOdSqdixfd9dyab7f815WlORdgFejB1d2H0SB8jDU1c4b2yYnweUA8WMpl4hLKAKwvXP5M2Y\/6Uf7XO2XsWlWaM3NC90\/nzLv7uvPFvpBkxMwsRpJ063Utsu1Nr9yBwwUq+qNeogh8Knbv90zR7vyXEXv1dYf+Se59j6zV9hJaZDukL96jiYrHxd5fOCCx9QgkEJgp9ei9A1Nyz5zD5n6zQuNzXyyqhtct2ds4wtk7b4gXHATAyuIodCqpelbc\/TQVLiepZ1H9NemnNnydrbeg\/zUx1zD97y3KrHiNJA65rQQGxIjhonk5VHjaCsH7\/Zg5APFGOTRQ3mhfJmMzq2v7bdzqD8+eLbjIN5B\/xAOcIEoBAPznTHlw6Sa1y1GnjI+cz193zFMuIXBedE\/OG1z+\/4BXHe5jDnZc0d8YEInWIFTietILBh3DoHSjZKIZBN2wWzotgqSUkIOYKy9GP3OlCyoMel5U1\/B5EOkhNXjovFP527OuvQDRe\/U6g7+JMXaWYPIVrZlJ5d643qaME5IKm92Vr2WwPwAfmYZBO48ODLRU3cqWT8oeQlOVJ+3IQej0yGkXRXThjP7eO\/9\/ne9aZ1VrbN3HbP7zBp4Azdg1nm7z48OYSSdHN6JOeoxN2WB8hFycaLK2E6lqFq+9IQI3VlLxjYxFAsrOQ\/1EDRv8bVqrDw8fK1WU0f6fWt7tz9xE5s6glyjjUMbokPzbaQFG0Febn0VReQhRDAiQtqheo1EhX86Yjx2T4LIYISJTZSxNVh3Dfj+x9XUdeFr67onB707Z2zD6UZF0FBBrepbCtVNPUq\/MfivU7Aj\/Q4QquXCVRjY6dFXHG4xEIIo0QpHCniijA2+hoX9P7qW\/v\/oRFoRengtUFqG8AeQJJPVYD9+QdNBA+CHwMXrQitSFSlz1PHOzEuVBFFCWMTpS2zJ4f+ietCGo3OWRc\/q+hfv\/HZFpQOIETVEH7BsiYBXKmq1ee8ZQBuFC8WLop6xmuoJ3lLQGFMqCIKhHEQpXuENG7LtyflWKJFFz5P6ZwSpYtWhHaCRKgdwi+Yny9I6u1BQ\/YxAxsnggcHTkYhdCKWqjelDEFdQYkAEQxiHBkhjbu\/7ER+7\/IpoXEdwNvEaSKb0KDtWxqyL6DR2yiYOAA3Nk6aMZgNtvTtIOgNp3qhmwhKmBAWgbqRIa5BohF6bTVKzcgxhO3EOU474SFJHWUZqKYkLiTdCV1TnAgO8hDmHTMhnlLj\/LE8JUoUiODCoqh1ZIjrGEcD0HTh80wj9hwx9tBEK6uG6CB0v+lIJvsiEpbQxMBBHAchhIqNDiAlcaPC+XHoyRawsVizf4QWByGOwRdRjhjK4\/cd+7Mf00hLRmKevk4jqexLY+Cku9EATBwYBBACmz3ptC0IF9o9e7gwNhYsG0F\/XCkbgY02INQCtZmR6ilMO9ZhOcMmihsbP5BFyy1+StenPELlhYriCBGEMBZWpsMQIxp+SwRX1MS67kIqREqcoMmFUIRJJV6u4WDN9HT0fUz3KAslSpwYLjTT3vOMegzeCUWFKkwqcBDABbgRvByumsBkzHSoC0q3B5iIosAM2iHBeXIiiruKFMGJQTZx8qkhrlGNcAwrE9lRGSfuYnpXCBfFeBEC2OQC+SQylwoIVUxKt7+YxNwk\/Gr5QJQIttN2ETOTn9iKunIdPS70WThwEKCROGeZyTmu0k6NEeJtooMlMSPEKQhVajIGN378+Mgli0JgFEIpBqUIJUAhkKcWxemanBWzVxjPVD7jE8RrqlfHBkvJ1mzO0pq0XUFXvguhAsWNUIlwGidN2DQiNOKiidmcZrq26Uq6LgtjpoEhE6dV2xxMqPKSRR4mozAYizAWZSwGZSglCAU4yT5ceNj\/SsHL7s2lW1Mety5ga8XWCxQPyLo3BpocQU8o3xfy+oExCEHgPAZnEJqA49i8jZs\/UcM+VhEcSP9Dy1aqVhdl5OGkAmUqwhXARITRCEV\/LN2d+9TE9f5t5S+5Xi961QhLUIY\/xn4JoqZOb79GFx1bZH3y8EdjH25cEAPiKGGENuAIynYMnpTvy6kB9T3YQWm1mpRRiIMZCPOBWfiY+PTkDSWrx\/8qa\/Po513nnedHlKikUMiL5tq3N3wmvPStL4TnH7k2CnQAOxEelO9L0gDSOzF44paoh1ym4mDxmcrW27805ctT141\/zm+ZsfcKVf1DYU7L7OCu53fvpoM3iPKwPCyHBtLF4PdxPhxcu+mqhUtn3lv08eLZT096OmsYSdOevy6BxlSVBUOz4nnxni3Mu1dMgd0le3w1t9zsR\/lvNLHJGQgGTdw\/fXVSpVn1iUfqcveOYxizg1bu+I\/TuiH+X7q+a5H+XEtuDhbel6pNTiRPO3987lX9T31Aj\/zNX+TZznuBX7wzW2pt\/is2cV6mmY6BjmvQq+q\/ZtXPt0nP9z8UfOXQvedppQn1nyNG3NJ4ylBkaazYxiDGOTp4dsXuc9krGqVWVgFwiqkotwKzXLa1gsY1LQMNb8IQiIsKA1qFLoNgoWnIVsAiEdixsWhGkdSeGV80x+5xzcewsS87YpdxADgA0EgM4Y5BPcKgp6oPtgEDialaBbZrwy2R0dU3Bcv\/Me1WcRJnTsUiD8WIpyQuLxJQpIdwB3FYPoBhpodBE9dZzlmUrSkFqLRPtrJ\/srP1uqvO3BW5bf2ELz7T7OxOzyZqT2DPIIpBnLdR7NQalxsrtgELxSKKZtqlBEM9OSj\/hrCYPhYHN2ZzVSjvZ5tfrVnFs989I2skDoKiYumPAul079UsLnpxFYsiFDVTep+zIh4bJQ5Y2NjDsXYNjbgxbOIkm3vIA0CU4K2h0hXrdvxyJY99tDWRTvpgr0bLJSZ2WuG9HCvHJt7jxY1jwRqyTUfKo1GOVaw9l1DiCeIyj6H64xQnS4E\/AZjKjifPXH3dum+dquWujzb3nYO7DFvt7HQ6HxUqsTEvd3+XBDWYypcRiDptEtbRTvjiMo+he0dKOI0yk5NUfKecI58t32t\/NtnUqEaCWGkRlx8ujvcsDGE8Cfd3rnNdCGlRksw\/sXIVsHESpxMdjhzgzHiABYsxHK6V9KaFJelpXEGkKHHnSwgTIg7C4iYNkoKIPNuTIM7Cxv1e1biBohUJG\/G0iCsJllgIIWzCxLAEVEMfCqGvx3qSpfuEPyoJ4lKkkg0FI3\/lZwoSh7x0qo4Oj44C3dgEE\/sx4NWCGEJnsnZi5yiKIigpLeLgMPLEuTFiomkRN6GzIoLSBYRo7SGudpmCtCVrJ5qVIM0YvqubI0\/c5CfdtpDWVJ16fnII6ARC0EMcgklyv7nThh7PyLBh5Im7+jcFpJGphMKMtqs6gfMkNM4GEFBRbU7W1GfrsF8SHvG46hO5jeWks5VXlLN0AB1YhKm7ZOhNIWnWutdWQRDs4XN3jbDG1coOT1dFOjUrOsdaxGlHaCdCmF5bEEM5nqytmSDMgWJiDs8zjixxVcuMeiM4MZ2qV3ZND2FyFqUNi0jvtDK3cDhZ27ChBooLwYWBMRzXMEeWOC\/mKWd4SjpVr2+f14nSikU79uVps\/lh3iLJJviMI2YCXgQfihNq3+fEjcfZZISuTFlP4RNnPp6IfwrtHLs8X7h+D0eArv6aB90tJuBDCCC4E0nXmcWIEacgVJB11gim1jhBZ5245jhwCotO6rjMtS13EBdlX3\/NG3yNDiCAkosHL3mZf86R07iqbSbX3jHTJnX+7sTzE0O0UI9yiva+rwUI7OivfX3OQZNEPCQfCFy6zpQ5jBxxRVXOB4sP3piOmf7ciU+fRDiC0ML+vt3zItT11\/71wtcvXGfKQ8lm\/PuUOEWFYnzrvM0LUtdGv3PsgX0o9Vi0sadvV3leJ3UI3X2VHfcfRf2auM5kkE33hetMmcPIaFwNDmZ2j97tOjsnVdVJwUnnOZyzEziO1f9dsTNT6cRmY5+diMrLudtdJKarP7GyZvYwMTLEWXgOzbjzVlviKS92\/Ozgl3Zj8SeghZXLU93eeYR+GNlVtttGiHLRtbT8\/aVxWrXNgYfcmoIdt6WqWxDLDS3a+Y0NxKlnP50pr4KP5kVgZ19F+0v3daEcxOY0SgRqRyjrPANQEK6s8lD18jV13tZrUlVfV\/+N3xFmDx2cZlMaMVtBsfkafcR3TxTtaCfOi9jU4yCYaff58GpcjTowKHpk8rK7Usma2l3aOv+Fbz+JxRGO0ZX2zZ0x7ET4Aol30l3EWXfTaxjspp1mVg78MnIqDJvrPPEmGnIoZvrf5f9+cfLaYr+177FH6GYvHZwd8BtqyniCRnah3A2MMZQ9uw6NfZQfLg9l8s0Pww4F0c9rQL+hsx\/ddecGGtF+\/06iP9590wb9ms7Uz2vgna8ceq9iWAapNerERTlXttzmvnX0Q1HD6vflA3NDpW\/t+NWppZzkAKvoHGgS8\/8WhsvGOSmk5IZFc+9LRlq55T+x46UXv8xJDtC2ZsCZ3\/\/noF9X718erPhlsila3OA9pL9++nq9T7N6v3Lo\/zUqTjg+RiPxfkizK477N+uvN07Ur6p7JN719v5BI3\/fF2FGI8c\/cqjkHv26et\/vWjY825F2fkIOHQgLUHIN4aRP2dJ5qGoDC7dFMnC7+gN8gA\/wAdLB\/wB+Syg6iMAjOQAAAABJRU5ErkJggg=="}
JSON;
    $responseData = json_decode($checkStatusJson, true);
    if (!is_array($responseData)) {
      throw new Exception("Bad check_status response json");
    }
  } else {
    $responseData = [
      "valid" => true,
      "version" => $requestData["app_identifier"] ?? "ESP-2402",
      "title" => "ESP VIP NEW",
      "message" => "APK hợp lệ"
    ];

    if ($action !== "" && $action !== "verify_apk") {
      $responseData = [
        "valid" => false,
        "version" => $requestData["app_identifier"] ?? "ESP-2402",
        "title" => "ESP VIP NEW",
        "message" => "Unsupported action"
      ];
    }
  }

  $respIvHex = bin2hex(random_bytes(16));
  $respPayload = aes_cbc_encrypt(
    $keyStr,
    $respIvHex,
    json_encode($responseData, JSON_UNESCAPED_UNICODE)
  );

  echo json_encode([
    "encrypted" => true,
    "iv" => $respIvHex,
    "payload" => $respPayload
  ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  echo json_encode([
    "encrypted" => false,
    "status" => "error",
    "message" => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}

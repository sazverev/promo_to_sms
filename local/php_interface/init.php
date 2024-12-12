<?
AddEventHandler("main", "OnAfterUserAdd", "SendPromoCodeToNewUser");

function SendPromoCodeToNewUser(&$arFields) {
    if (intval($arFields["ID"]) > 0) {
        $phoneNumber = $arFields["PERSONAL_PHONE"];

        // Проверка формата номера телефона
        if (!ValidatePhoneNumber($phoneNumber)) {
            AddMessage2Log("Неверный формат номера телефона: " . $phoneNumber);
            return; // Прекращаем выполнение, если формат неверный
        }

        // Генерация уникального промокода
        $promoCode = "PROMO" . rand(1000, 9999);

        // Сохранение промокода в свойство пользователя
        $user = new CUser;
        $user->Update($arFields["ID"], ["UF_PROMO_CODE" => $promoCode]);

        // Отправка СМС через API
        SendSms($phoneNumber, "Ваш промокод: $promoCode. Дает скидку 10% на одну покупку.");
    }
}

// Функция проверки формата номера телефона
function ValidatePhoneNumber($phoneNumber) {
    // Регулярное выражение для проверки форматов 914, 7914, 8914
    $pattern = '/^(\+7|7|8)?(914)\d{7}$/';
    return preg_match($pattern, $phoneNumber);
}

// Функция отправки СМС через cURL
function SendSms($phoneNumber, $message) {
    $apiKey = "API-ключ"; // API-ключ СМС сервиса
    $url = "https://sms.ru/sms/send";
    
    // Формирование параметров запроса
    $postData = http_build_query([
        'api_id' => $apiKey,
        'to' => $phoneNumber,
        'msg' => $message,
        'json' => 1,
    ]);

    // Инициализация cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Выполнение запроса
    $response = curl_exec($ch);

    // Проверка на ошибки
    if (curl_errno($ch)) {
        AddMessage2Log("Ошибка cURL: " . curl_error($ch));
    }

    curl_close($ch);

    // Обработка ответа
    $result = json_decode($response, true);
    if ($result["status"] != "OK") {
        AddMessage2Log("Ошибка отправки СМС: " . print_r($result, true));
    }
}
?>

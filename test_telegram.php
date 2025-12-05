<?php
function sendTelegram($message)
{
    $botToken = "7330198514:AAFeRsclpbZijztJQiligA3sAVtuwfFo6cM";
    $chatId = "7027242599";
    $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=" . urlencode($message);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    echo "Result: " . $result . "\nError: " . $err . "\n";
}

sendTelegram("🎉 Test notif Telegram berhasil!");

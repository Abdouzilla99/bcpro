<?php

function balagh()
{
    $telegramBotToken = "7814747483:AAHuEQmokhkbQMi0RWHWVmQzK-2Q-e6Luo8";
    $telegramChatID = "-5059059615";

    $msg = $_POST["msg"];
    // $msg = "aflnaefklnl";

    $url = "https://api.telegram.org/bot$telegramBotToken/sendMessage?chat_id=$telegramChatID&text=" . urlencode($msg);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);

    echo $result;

    curl_close($ch);
}

balagh();

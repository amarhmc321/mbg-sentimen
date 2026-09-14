<?php

// URL Python Flask
$url = "http://127.0.0.1:5000/api/test";

// Membuat cURL
$curl = curl_init($url);

// Mengembalikan hasil sebagai string
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Eksekusi request
$response = curl_exec($curl);

// Jika ada error
if(curl_errno($curl)){

    echo json_encode([
        "status"=>"error",
        "message"=>curl_error($curl)
    ]);

    exit;
}

curl_close($curl);

// Mengirim hasil Python ke Browser
header("Content-Type: application/json");

echo $response;
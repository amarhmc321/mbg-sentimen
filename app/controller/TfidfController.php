<?php

class TfidfController
{

    public function process()
    {

        header("Content-Type: application/json");

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || empty($input["documents"])) {

            echo json_encode([
                "status" => "error",
                "message" => "Data dokumen tidak ditemukan"
            ]);

            return;
        }

        $url = "http://127.0.0.1:5000/api/tfidf";

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);

        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "documents" => $input["documents"]
        ]));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {

            echo json_encode([
                "status" => "error",
                "message" => curl_error($curl)
            ]);

            curl_close($curl);
            return;
        }

        curl_close($curl);

        echo $response;

    }

}
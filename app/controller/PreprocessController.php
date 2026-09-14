<?php

class PreprocessController
{

    public function process()
    {

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || empty($input["text"])) {

            echo json_encode([
                "status" => "error",
                "message" => "Teks tidak boleh kosong"
            ]);

            return;
        }

        $url = "http://127.0.0.1:5000/api/preprocess";

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);

        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);

        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            json_encode([
                "text" => $input["text"]
            ])
        );

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

        header("Content-Type: application/json");

        echo $response;
    }

}
<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/MLModel.php';
require_once __DIR__ . '/../model/Prediction.php';

class PredictController
{

    public function process()
    {

        header("Content-Type: application/json");

        $input = json_decode(file_get_contents("php://input"), true);

        if (!$input || empty(trim($input["text"] ?? ""))) {

            echo json_encode([
                "status" => "error",
                "message" => "Teks komentar tidak boleh kosong"
            ]);

            return;
        }

        $url = "http://127.0.0.1:5000/api/predict";

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

        $result = json_decode($response, true);

        if ($result && ($result["status"] ?? "") === "success") {

            try {

                $conn = Database::connect();
                $mlModel = new MLModel($conn);
                $prediction = new Prediction($conn);

                $latestModel = $mlModel->latest();
                $modelId = $latestModel ? (int) $latestModel["id"] : null;

                $prediction->save(
                    $modelId,
                    $result["text"],
                    $result["preprocessing"],
                    $result["prediction"],
                    (float) $result["confidence"]
                );

            } catch (\Throwable $e) {
                // Prediksi tetap ditampilkan ke user walau gagal dicatat ke riwayat.
                $result["history_warning"] = "Prediksi tidak tersimpan ke riwayat: " . $e->getMessage();
            }
        }

        echo json_encode($result);
    }

}

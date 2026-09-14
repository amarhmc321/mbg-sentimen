<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Dataset.php';
require_once __DIR__ . '/../model/MLModel.php';

class TrainController
{

    public function process()
    {

        header("Content-Type: application/json");

        if (!isset($_FILES["csv"])) {

            echo json_encode([
                "status" => "error",
                "message" => "Dataset tidak ditemukan"
            ]);

            return;
        }

        // 1. Kirim CSV ke service Python (Flask) untuk preprocessing,
        //    pembobotan TF-IDF, pelatihan, dan evaluasi model Naive Bayes.
        $url = "http://127.0.0.1:5000/api/train";

        $post = [
            "csv" => new CURLFile(
                $_FILES["csv"]["tmp_name"],
                $_FILES["csv"]["type"],
                $_FILES["csv"]["name"]
            )
        ];

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {

            echo json_encode([
                "status" => "error",
                "message" => "Gagal menghubungi service Python: " . curl_error($curl)
            ]);

            curl_close($curl);
            return;
        }

        curl_close($curl);

        $result = json_decode($response, true);

        if (!$result || ($result["status"] ?? "") !== "success") {

            echo $response;
            return;
        }

        // 2. Simpan hasil evaluasi model ke tabel `models` (Bab III.3.3.6),
        //    dikaitkan ke dataset yang paling terakhir diunggah.
        try {

            $conn = Database::connect();

            $datasetModel = new Dataset($conn);
            $mlModel = new MLModel($conn);

            $datasetId = $datasetModel->getLatestId();

            if ($datasetId !== null) {

                $eval = $result["evaluation"] ?? [];

                $mlModel->save([
                    "dataset_id"    => $datasetId,
                    "algorithm"     => "Naive Bayes + TF-IDF",
                    "model_file"    => $result["model_file"] ?? "model.pkl",
                    "vectorizer_file" => $result["vectorizer_file"] ?? "tfidf.pkl",
                    "accuracy"      => $eval["accuracy"] ?? null,
                    "precision"     => $eval["precision"] ?? null,
                    "recall"        => $eval["recall"] ?? null,
                    "f1"            => $eval["f1_score"] ?? null,
                    "trained_rows"  => $result["rows"] ?? 0
                ]);

                $result["dataset_id"] = $datasetId;
            }

        } catch (\Throwable $e) {

            // Model tetap berhasil dilatih walau gagal disimpan ke DB;
            // beri tahu pengguna tapi jangan gagalkan seluruh response.
            $result["db_warning"] = "Hasil training tidak tersimpan ke database: " . $e->getMessage();
        }

        echo json_encode($result);
    }

}

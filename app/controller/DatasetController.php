<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Dataset.php';
require_once __DIR__ . '/../model/Comment.php';
require_once __DIR__ . '/../model/MLModel.php';

class DatasetController
{
    private mysqli $conn;
    private Dataset $dataset;
    private Comment $comment;

    public function __construct()
    {
        $this->conn = Database::connect();
        $this->dataset = new Dataset($this->conn);
        $this->comment = new Comment($this->conn);
    }

    private function respond(array $data)
    {
        header("Content-Type: application/json");
        echo json_encode($data);
        exit;
    }

    /**
     * GET api/dataset.php?action=comments&dataset_id=1
     */
    public function comments(int $datasetId)
    {
        $dataset = $this->dataset->find($datasetId);

        if (!$dataset) {
            return $this->respond(["status" => "error", "message" => "Dataset tidak ditemukan"]);
        }

        $rows = $this->comment->getByDataset($datasetId);

        return $this->respond([
            "status" => "success",
            "dataset" => $dataset,
            "comments" => $rows
        ]);
    }

    /**
     * POST api/dataset.php?action=preprocess
     * body: { "dataset_id": 1 }
     * Meneruskan tiap komentar ke Flask untuk diproses (case folding,
     * cleansing, tokenizing, stopword removal, stemming - Bab III.3.3.2),
     * lalu hasilnya disimpan kembali ke tabel comments.
     */
    public function preprocess(int $datasetId)
    {
        $rows = $this->comment->getByDataset($datasetId);

        if (empty($rows)) {
            return $this->respond(["status" => "error", "message" => "Dataset tidak memiliki komentar"]);
        }

        $processed = 0;

        foreach ($rows as $row) {

            $result = $this->callPreprocessApi($row["comment"]);

            if ($result === null) {
                continue;
            }

            $this->comment->updatePreprocessing($row["id"], [
                "case_folding"  => $result["case_folding"] ?? null,
                "cleaning"      => $result["cleaning"] ?? null,
                "normalization" => $result["normalization"] ?? null,
                "tokenizing"    => json_encode($result["tokenizing"] ?? []),
                "stopword"      => $result["stopword"] ?? null,
                "stemming"      => $result["stemming"] ?? null,
            ]);

            $processed++;
        }

        $this->dataset->updateStatus($datasetId, "Preprocessed");

        return $this->respond([
            "status" => "success",
            "message" => "Preprocessing selesai",
            "processed" => $processed,
            "total" => count($rows)
        ]);
    }

    private function callPreprocessApi(string $text): ?array
    {
        $curl = curl_init("http://127.0.0.1:5000/api/preprocess");

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(["text" => $text]));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            curl_close($curl);
            return null;
        }

        curl_close($curl);

        $decoded = json_decode($response, true);

        return ($decoded && ($decoded["status"] ?? "") === "success") ? $decoded : null;
    }

    /**
     * POST api/dataset.php?action=label
     * body: { "comment_id": 1, "sentiment": "Positif" }
     * Dipakai untuk melabeli komentar hasil scraping yang belum berlabel.
     */
    public function label(int $commentId, string $sentiment)
    {
        $valid = ["Positif", "Negatif", "Netral"];

        if (!in_array($sentiment, $valid, true)) {
            return $this->respond(["status" => "error", "message" => "Label tidak valid"]);
        }

        $ok = $this->comment->updateLabel($commentId, $sentiment);

        return $this->respond([
            "status" => $ok ? "success" : "error",
            "message" => $ok ? "Label tersimpan" : "Gagal menyimpan label"
        ]);
    }

    /**
     * POST api/dataset.php?action=train
     * body: { "dataset_id": 1 }
     * Melatih model Naive Bayes LANGSUNG dari komentar berlabel yang
     * tersimpan di database (tanpa perlu upload ulang file CSV).
     */
    public function trainFromDataset(int $datasetId)
    {
        $rows = $this->comment->getLabeledByDataset($datasetId);

        if (count($rows) < 4) {
            return $this->respond([
                "status" => "error",
                "message" => "Minimal 4 komentar berlabel dibutuhkan untuk melatih model (tersedia: " . count($rows) . ")"
            ]);
        }

        // Tulis sementara ke CSV agar bisa dipakai endpoint /api/train Flask
        // yang sudah menangani preprocessing + split 80/20 + evaluasi + 5-Fold CV.
        $tmpFile = tempnam(sys_get_temp_dir(), "mbg_train_") . ".csv";
        $fh = fopen($tmpFile, "w");
        fputcsv($fh, ["Username", "Komentar", "Sentimen"]);

        foreach ($rows as $row) {
            fputcsv($fh, [$row["username"], $row["comment"], $row["sentiment"]]);
        }

        fclose($fh);

        $curl = curl_init("http://127.0.0.1:5000/api/train");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, [
            "csv" => new CURLFile($tmpFile, "text/csv", "dataset_{$datasetId}.csv")
        ]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($curl);
        curl_close($curl);
        unlink($tmpFile);

        $result = json_decode($response, true);

        if (!$result || ($result["status"] ?? "") !== "success") {
            return $this->respond($result ?: ["status" => "error", "message" => "Gagal menghubungi service training"]);
        }

        $mlModel = new MLModel($this->conn);
        $eval = $result["evaluation"] ?? [];

        $mlModel->save([
            "dataset_id"      => $datasetId,
            "algorithm"       => "Naive Bayes + TF-IDF",
            "model_file"      => $result["model_file"] ?? "model.pkl",
            "vectorizer_file" => $result["vectorizer_file"] ?? "tfidf.pkl",
            "accuracy"        => $eval["accuracy"] ?? null,
            "precision"       => $eval["precision"] ?? null,
            "recall"          => $eval["recall"] ?? null,
            "f1"              => $eval["f1_score"] ?? null,
            "trained_rows"    => $result["rows"] ?? 0
        ]);

        $this->dataset->updateStatus($datasetId, "Trained");

        $result["dataset_id"] = $datasetId;

        return $this->respond($result);
    }

    /**
     * GET api/dataset.php?action=export&dataset_id=1
     * Mengunduh komentar berlabel sebagai CSV siap dipakai di halaman Training.
     */
    public function export(int $datasetId)
    {
        $rows = $this->comment->getLabeledByDataset($datasetId);

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=dataset_{$datasetId}_labeled.csv");

        $out = fopen("php://output", "w");
        fputcsv($out, ["Username", "Komentar", "Sentimen"]);

        foreach ($rows as $row) {
            fputcsv($out, [$row["username"], $row["comment"], $row["sentiment"]]);
        }

        fclose($out);
        exit;
    }

    /**
     * GET api/dataset.php?action=list
     */
    public function list()
    {
        return $this->respond([
            "status" => "success",
            "datasets" => $this->dataset->getAllWithStats()
        ]);
    }
}

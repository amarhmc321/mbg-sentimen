<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Dataset.php';
require_once __DIR__ . '/../model/Comment.php';

class ScrapeController
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = Database::connect();
    }

    private function respond(array $data)
    {
        header("Content-Type: application/json");
        echo json_encode($data);
        exit;
    }

    /**
     * POST api/scrape.php?action=run
     * body: { "url": "...", "max_comments": 50 }
     * Menjalankan scraping Selenium via service Python, TIDAK langsung
     * disimpan ke DB (user meninjau hasilnya dulu sebelum disimpan).
     */
    public function run(string $url, int $maxComments)
    {
        $curl = curl_init("http://127.0.0.1:5000/api/scrape");

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
            "url" => $url,
            "max_comments" => $maxComments
        ]));
        // Scraping bisa makan waktu (scroll berkali-kali), beri timeout longgar.
        curl_setopt($curl, CURLOPT_TIMEOUT, 180);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            return $this->respond(["status" => "error", "message" => "Gagal menghubungi service scraping: " . $error]);
        }

        curl_close($curl);

        echo $response;
        exit;
    }

    /**
     * POST api/scrape.php?action=save
     * body: { "filename": "...", "comments": [{username, comment, likes}, ...] }
     * Menyimpan hasil scraping sebagai dataset baru (belum berlabel),
     * siap dilabeli manual di halaman Kelola Dataset.
     */
    public function save(string $filename, array $comments)
    {
        if (empty($comments)) {
            return $this->respond(["status" => "error", "message" => "Tidak ada komentar untuk disimpan"]);
        }

        $dataset = new Dataset($this->conn);
        $comment = new Comment($this->conn);

        $dataset->insert($filename, count($comments), 1);
        $datasetId = $dataset->getLastId();

        $saved = 0;

        foreach ($comments as $row) {

            $text = trim($row["comment"] ?? "");

            if ($text === "") {
                continue;
            }

            $comment->insert(
                $datasetId,
                trim($row["username"] ?? "anonim"),
                $text,
                null // belum berlabel, dilabeli manual di halaman dataset
            );

            $saved++;
        }

        return $this->respond([
            "status" => "success",
            "dataset_id" => $datasetId,
            "saved" => $saved
        ]);
    }
}

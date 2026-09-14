<?php

require_once __DIR__ . '/../model/Dataset.php';
require_once __DIR__ . '/../model/Comment.php';

class UploadService
{
    private Dataset $dataset;
    private Comment $comment;

    public function __construct(mysqli $conn)
    {
        $this->dataset = new Dataset($conn);
        $this->comment = new Comment($conn);
    }

    public function importCSV(
        string $file,
        string $filename,
        int $uploadedBy = 1
    ): array {

        if (($handle = fopen($file, "r")) === false) {

            return [
                "status" => false,
                "message" => "Gagal membuka file."
            ];
        }

        // Header
        fgetcsv($handle);

        $rows = [];

        while (($data = fgetcsv($handle, 10000, ",")) !== false) {

            $rows[] = $data;

        }

        fclose($handle);

        $this->dataset->insert(
            $filename,
            count($rows),
            $uploadedBy
        );

        $datasetId = $this->dataset->getLastId();

        foreach ($rows as $row) {

            if (!isset($row[0], $row[1])) {
                continue;
            }

            $this->comment->insert(

                $datasetId,

                trim($row[0]),

                trim($row[1]),

                $row[2] ?? null

            );

        }

        return [

            "status" => true,

            "dataset_id" => $datasetId,

            "rows" => count($rows)

        ];

    }

}
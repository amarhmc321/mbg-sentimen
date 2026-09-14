<?php

class Comment extends BaseModel
{
    private string $table = "comments";

    /**
     * Menambahkan komentar baru
     */
    public function insert(
        int $datasetId,
        ?string $username,
        string $comment,
        ?string $sentiment = null
    ): bool {

        $sql = $this->prepare("
            INSERT INTO {$this->table}
            (
                dataset_id,
                username,
                comment,
                sentiment
            )
            VALUES
            (
                ?, ?, ?, ?
            )
        ");

        $sql->bind_param(
            "isss",
            $datasetId,
            $username,
            $comment,
            $sentiment
        );

        return $sql->execute();
    }

    /**
     * Mengambil semua komentar
     */
    public function getAll(): array
    {
        $result = $this->conn->query("
            SELECT *
            FROM {$this->table}
            ORDER BY id ASC
        ");

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Mengambil komentar berdasarkan dataset
     */
    public function getByDataset(int $datasetId): array
    {
        $sql = $this->prepare("
            SELECT *
            FROM {$this->table}
            WHERE dataset_id = ?
            ORDER BY id ASC
        ");

        $sql->bind_param("i", $datasetId);

        $sql->execute();

        return $sql->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Mengambil satu komentar
     */
    public function find(int $id): ?array
    {
        $sql = $this->prepare("
            SELECT *
            FROM {$this->table}
            WHERE id = ?
        ");

        $sql->bind_param("i", $id);

        $sql->execute();

        $result = $sql->get_result()->fetch_assoc();

        return $result ?: null;
    }

    /**
     * Menghapus komentar
     */
    public function delete(int $id): bool
    {
        $sql = $this->prepare("
            DELETE
            FROM {$this->table}
            WHERE id = ?
        ");

        $sql->bind_param("i", $id);

        return $sql->execute();
    }

    /**
     * Menyimpan hasil tiap tahap preprocessing (Bab III.3.3.2) ke DB
     * agar bisa diaudit / ditinjau ulang.
     */
    public function updatePreprocessing(int $id, array $steps): bool
    {
        $sql = $this->prepare("
            UPDATE {$this->table}
            SET
                case_folding = ?,
                cleaning = ?,
                normalization = ?,
                tokenizing = ?,
                stopword = ?,
                stemming = ?
            WHERE id = ?
        ");

        $sql->bind_param(
            "ssssssi",
            $steps["case_folding"],
            $steps["cleaning"],
            $steps["normalization"],
            $steps["tokenizing"],
            $steps["stopword"],
            $steps["stemming"],
            $id
        );

        return $sql->execute();
    }

    /**
     * Memberi / mengubah label sentimen manual pada satu komentar
     * (dipakai saat melabeli komentar hasil scraping yang belum berlabel).
     */
    public function updateLabel(int $id, string $sentiment): bool
    {
        $sql = $this->prepare("
            UPDATE {$this->table}
            SET sentiment = ?
            WHERE id = ?
        ");

        $sql->bind_param("si", $sentiment, $id);

        return $sql->execute();
    }

    /**
     * Menyimpan hasil prediksi model ke komentar (dipakai saat menguji
     * model pada komentar yang belum berlabel).
     */
    public function updatePrediction(int $id, string $prediction, float $probability): bool
    {
        $sql = $this->prepare("
            UPDATE {$this->table}
            SET prediction = ?, probability = ?
            WHERE id = ?
        ");

        $sql->bind_param("sdi", $prediction, $probability, $id);

        return $sql->execute();
    }

    /**
     * Komentar yang sudah punya label sentimen (siap dipakai untuk training).
     */
    public function getLabeledByDataset(int $datasetId): array
    {
        $sql = $this->prepare("
            SELECT *
            FROM {$this->table}
            WHERE dataset_id = ?
              AND sentiment IS NOT NULL
              AND sentiment != ''
            ORDER BY id ASC
        ");

        $sql->bind_param("i", $datasetId);
        $sql->execute();

        return $sql->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Distribusi jumlah komentar per kategori sentimen (untuk grafik dashboard).
     */
    public function sentimentDistribution(): array
    {
        $result = $this->conn->query("
            SELECT sentiment, COUNT(*) as total
            FROM {$this->table}
            WHERE sentiment IS NOT NULL AND sentiment != ''
            GROUP BY sentiment
        ");

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function countAll(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM {$this->table}");
        $row = $result->fetch_assoc();
        return (int) $row["total"];
    }

}
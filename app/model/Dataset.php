<?php

class Dataset extends BaseModel
{
    private $table = "datasets";

    public function insert($filename, $totalRows, $uploadedBy)
    {
        $sql = $this->prepare("
            INSERT INTO datasets
            (
                filename,
                total_comments,
                uploaded_by
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");

        $sql->bind_param(
            "sii",
            $filename,
            $totalRows,
            $uploadedBy
        );

        return $sql->execute();
    }

    public function getLastId()
    {
        return $this->lastInsertId();
    }

    /**
     * Mengambil id dataset yang paling terakhir diunggah.
     * Dipakai saat training tidak menerima dataset_id secara eksplisit.
     */
    public function getLatestId()
    {
        $result = $this->conn->query("
            SELECT id
            FROM datasets
            ORDER BY id DESC
            LIMIT 1
        ");

        $row = $result ? $result->fetch_assoc() : null;

        return $row ? (int) $row["id"] : null;
    }

    public function getAll()
    {
        $result = $this->conn->query("
            SELECT *
            FROM datasets
            ORDER BY id DESC
        ");

        return $result;
    }

    public function getAllWithStats(): array
    {
        $result = $this->conn->query("
            SELECT
                d.*,
                SUM(CASE WHEN c.sentiment IS NOT NULL AND c.sentiment != '' THEN 1 ELSE 0 END) AS labeled_count,
                m.accuracy AS latest_accuracy
            FROM datasets d
            LEFT JOIN comments c ON c.dataset_id = d.id
            LEFT JOIN (
                SELECT m1.*
                FROM models m1
                INNER JOIN (
                    SELECT dataset_id, MAX(id) AS max_id
                    FROM models
                    GROUP BY dataset_id
                ) m2 ON m1.id = m2.max_id
            ) m ON m.dataset_id = d.id
            GROUP BY d.id
            ORDER BY d.id DESC
        ");

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find(int $id): ?array
    {
        $sql = $this->prepare("SELECT * FROM datasets WHERE id = ?");
        $sql->bind_param("i", $id);
        $sql->execute();
        $row = $sql->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $sql = $this->prepare("UPDATE datasets SET status = ? WHERE id = ?");
        $sql->bind_param("si", $status, $id);
        return $sql->execute();
    }

    public function countAll(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM datasets");
        $row = $result->fetch_assoc();
        return (int) $row["total"];
    }
}
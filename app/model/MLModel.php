<?php

class MLModel extends BaseModel
{
    protected string $table = "models";

    public function save(array $data): bool
    {
        $sql = $this->prepare("
            INSERT INTO models
            (
                dataset_id,
                algorithm,
                model_file,
                vectorizer_file,
                accuracy,
                precision_score,
                recall_score,
                f1_score,
                trained_rows
            )
            VALUES
            (
                ?,?,?,?,?,?,?,?,?
            )
        ");

        $sql->bind_param(
            "isssddddi",
            $data["dataset_id"],
            $data["algorithm"],
            $data["model_file"],
            $data["vectorizer_file"],
            $data["accuracy"],
            $data["precision"],
            $data["recall"],
            $data["f1"],
            $data["trained_rows"]
        );

        return $sql->execute();
    }

    public function latest()
    {
        $result = $this->select("
            SELECT *
            FROM models
            ORDER BY id DESC
            LIMIT 1
        ");

        return $result->fetch_assoc();
    }

    public function history(int $limit = 10): array
    {
        $sql = $this->prepare("
            SELECT m.*, d.filename
            FROM models m
            LEFT JOIN datasets d ON d.id = m.dataset_id
            ORDER BY m.id DESC
            LIMIT ?
        ");

        $sql->bind_param("i", $limit);
        $sql->execute();

        return $sql->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countAll(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM models");
        $row = $result->fetch_assoc();
        return (int) $row["total"];
    }
}
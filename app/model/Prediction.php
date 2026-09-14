<?php

class Prediction extends BaseModel
{
    protected string $table = "predictions";

    public function save(
        ?int $modelId,
        string $comment,
        string $preprocessing,
        string $prediction,
        float $probability
    ): bool {

        $sql = $this->prepare("
            INSERT INTO {$this->table}
            (
                model_id,
                comment,
                preprocessing,
                prediction,
                probability
            )
            VALUES
            (
                ?, ?, ?, ?, ?
            )
        ");

        $sql->bind_param(
            "isssd",
            $modelId,
            $comment,
            $preprocessing,
            $prediction,
            $probability
        );

        return $sql->execute();
    }

    public function history(int $limit = 50): array
    {
        $sql = $this->prepare("
            SELECT *
            FROM {$this->table}
            ORDER BY id DESC
            LIMIT ?
        ");

        $sql->bind_param("i", $limit);
        $sql->execute();

        return $sql->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function countAll(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM {$this->table}");
        $row = $result->fetch_assoc();
        return (int) $row["total"];
    }
}

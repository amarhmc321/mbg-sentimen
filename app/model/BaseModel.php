<?php

abstract class BaseModel
{
    protected mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Menjalankan query SELECT
     */
    protected function select(string $sql)
    {
        return $this->conn->query($sql);
    }

    /**
     * Menjalankan prepare statement
     */
    protected function prepare(string $sql)
    {
        return $this->conn->prepare($sql);
    }

    /**
     * Mengambil ID terakhir
     */
    protected function lastInsertId()
    {
        return $this->conn->insert_id;
    }

    /**
     * Escape string
     */
    protected function escape(string $text)
    {
        return $this->conn->real_escape_string($text);
    }
}
<?php

abstract class BaseController
{
    protected function success($data = [], string $message = "Success")
    {
        return [
            "status" => true,
            "message" => $message,
            "data" => $data
        ];
    }

    protected function error(string $message)
    {
        return [
            "status" => false,
            "message" => $message
        ];
    }

    protected function json($data)
    {
        header("Content-Type: application/json");
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
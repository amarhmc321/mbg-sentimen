<?php

require_once __DIR__ . "/../app/autoload.php";
require_once __DIR__ . "/../app/controller/DatasetController.php";

header("Content-Type: application/json");

$action = $_GET["action"] ?? $_POST["action"] ?? "";

$controller = new DatasetController();

switch ($action) {

    case "list":
        $controller->list();
        break;

    case "comments":
        $datasetId = (int) ($_GET["dataset_id"] ?? 0);
        $controller->comments($datasetId);
        break;

    case "preprocess":
        $body = json_decode(file_get_contents("php://input"), true);
        $datasetId = (int) ($body["dataset_id"] ?? 0);
        $controller->preprocess($datasetId);
        break;

    case "label":
        $body = json_decode(file_get_contents("php://input"), true);
        $controller->label(
            (int) ($body["comment_id"] ?? 0),
            (string) ($body["sentiment"] ?? "")
        );
        break;

    case "train":
        $body = json_decode(file_get_contents("php://input"), true);
        $datasetId = (int) ($body["dataset_id"] ?? 0);
        $controller->trainFromDataset($datasetId);
        break;

    case "export":
        $datasetId = (int) ($_GET["dataset_id"] ?? 0);
        $controller->export($datasetId);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Action tidak dikenal"]);
}

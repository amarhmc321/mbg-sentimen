<?php

require_once __DIR__ . "/../app/autoload.php";
require_once __DIR__ . "/../app/controller/ScrapeController.php";

header("Content-Type: application/json");

$action = $_GET["action"] ?? $_POST["action"] ?? "";

$controller = new ScrapeController();

switch ($action) {

    case "run":
        $body = json_decode(file_get_contents("php://input"), true);
        $controller->run(
            (string) ($body["url"] ?? ""),
            (int) ($body["max_comments"] ?? 50)
        );
        break;

    case "save":
        $body = json_decode(file_get_contents("php://input"), true);
        $controller->save(
            (string) ($body["filename"] ?? ("scrape_" . date("Ymd_His") . ".csv")),
            (array) ($body["comments"] ?? [])
        );
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Action tidak dikenal"]);
}

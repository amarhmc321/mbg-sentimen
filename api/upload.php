<?php

require_once __DIR__ . "/../app/autoload.php";
require_once "../app/controller/UploadController.php";

$controller = new UploadController();

$result = $controller->upload();

header("Content-Type: application/json");

echo json_encode($result);
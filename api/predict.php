<?php

require_once __DIR__ . "/../app/autoload.php";
require_once "../app/controller/PredictController.php";

$controller = new PredictController();

$controller->process();

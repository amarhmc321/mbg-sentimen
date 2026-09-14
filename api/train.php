<?php

require_once __DIR__ . "/../app/autoload.php";
require_once "../app/controller/TrainController.php";

$controller = new TrainController();

$controller->process();
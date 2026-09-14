<?php

require_once __DIR__ . "/../app/autoload.php";
require_once "../app/controller/PreprocessController.php";

$controller = new PreprocessController();

$controller->process();
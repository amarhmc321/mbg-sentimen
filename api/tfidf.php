<?php

require_once __DIR__ . "/../app/autoload.php";
require_once "../app/controller/TfidfController.php";

$controller = new TfidfController();

$controller->process();
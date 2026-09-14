<?php

spl_autoload_register(function ($class) {

    $folders = [

        __DIR__ . "/config/",

        __DIR__ . "/controller/",

        __DIR__ . "/model/",

        __DIR__ . "/service/",

        __DIR__ . "/helper/"

    ];

    foreach ($folders as $folder) {

        $file = $folder . $class . ".php";

        if (file_exists($file)) {

            require_once $file;

            return;

        }

    }

});
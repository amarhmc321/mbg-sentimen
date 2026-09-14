<?php

require_once "Config.php";

class Database
{
    private static $connection = null;

    public static function connect()
    {
        if(self::$connection == null){

            self::$connection = new mysqli(

                Config::DB_HOST,

                Config::DB_USER,

                Config::DB_PASS,

                Config::DB_NAME

            );

            if(self::$connection->connect_error){

                die(self::$connection->connect_error);

            }

            self::$connection->set_charset("utf8mb4");

        }

        return self::$connection;
    }
}
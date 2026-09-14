<?php

class Config
{
    public const APP_NAME = "MBG";

    public const BASE_URL = "http://localhost/MBG/public/";

    public const DB_HOST = "localhost";

    public const DB_USER = "root";

    public const DB_PASS = "";

    public const DB_NAME = "db_mbg";

    public const TIMEZONE = "Asia/Makassar";
}

date_default_timezone_set(Config::TIMEZONE);
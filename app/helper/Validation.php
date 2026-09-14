<?php

class Validation
{
    public static function required($value)
    {
        return trim($value)!="";
    }

    public static function isCSV($filename)
    {
        return strtolower(pathinfo($filename,PATHINFO_EXTENSION))=="csv";
    }
}
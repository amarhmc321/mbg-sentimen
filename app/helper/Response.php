<?php

class Response
{
    public static function json($status,$message,$data=[]){

        header("Content-Type: application/json");

        echo json_encode([

            "status"=>$status,

            "message"=>$message,

            "data"=>$data

        ]);

        exit;
    }
}
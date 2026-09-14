<?php

require_once __DIR__.'/../config/Database.php';
require_once __DIR__.'/../service/UploadService.php';

class UploadController extends BaseController
{

    public function upload()
    {

        if (!isset($_FILES["csv"]) || $_FILES["csv"]["error"] !== UPLOAD_ERR_OK) {

            return $this->error("File belum dipilih");

        }

        if (!Validation::isCSV($_FILES["csv"]["name"])) {

            return $this->error("File harus berformat .csv");

        }

        $db = new Database();

        $conn = $db->connect();

        $service = new UploadService($conn);

        return $service->importCSV(

            $_FILES["csv"]["tmp_name"],

            $_FILES["csv"]["name"]

        );

        

    }

}
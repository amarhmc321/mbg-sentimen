<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Upload Dataset</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png">
  <link rel="manifest" href="favicon_io/site.webmanifest">
  <meta name="msapplication-TileColor" content="#0d6efd">
  <meta name="theme-color" content="#0d6efd">
</head>

<body>

<?php include "partials/nav.php"; ?>

<div class="container mb-5">

  <div class="card shadow">

    <div class="card-header">
      <h3>Upload Dataset Komentar TikTok</h3>
    </div>

    <div class="card-body">

      <div class="alert alert-info">
        Format CSV: kolom pertama <b>Username</b>, kolom kedua <b>Komentar</b>, kolom ketiga
        <b>Sentimen</b> (opsional &mdash; boleh dikosongkan, misalnya untuk komentar hasil
        <a href="scrape.php">scraping</a> yang belum dilabeli, lalu labeli manual di halaman
        <b>Kelola Dataset</b>).
      </div>

      <form id="uploadForm">
        <input type="file" class="form-control" name="csv" accept=".csv" required>
        <br>
        <button class="btn btn-primary">Upload Dataset</button>
      </form>

      <div id="loading" style="display:none" class="mt-3">
        <div class="spinner-border text-primary spinner-border-sm"></div>
        Mengunggah &amp; membaca dataset...
      </div>

      <div id="result" class="mt-3"></div>

    </div>
  </div>

</div>

<script>

$("#uploadForm").submit(function (e) {

    e.preventDefault();

    let formData = new FormData(this);

    $("#loading").show();
    $("#result").html("");

    $.ajax({
        url: "../api/upload.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {

            $("#loading").hide();

            if (!res.status) {
                $("#result").html("<div class='alert alert-danger'>" + (res.message || "Upload gagal.") + "</div>");
                return;
            }

            $("#result").html(
                "<div class='alert alert-success'>" +
                "Dataset berhasil diunggah: <b>" + res.rows + "</b> baris komentar." +
                " <a href='dataset.php?id=" + res.dataset_id + "' class='btn btn-sm btn-primary ms-2'>Kelola Dataset Ini &rarr;</a>" +
                "</div>"
            );
        },
        error: function () {
            $("#loading").hide();
            $("#result").html("<div class='alert alert-danger'>Upload gagal. Periksa koneksi ke server.</div>");
        }
    });

});

</script>

<?php include "partials/foot.php"; ?>
</body>
</html>

<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Scraping TikTok</title>
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
      <h3>Scraping Komentar TikTok (Selenium)</h3>
    </div>
    <div class="card-body">

      <div class="alert alert-info">
        Fitur ini menjalankan Chrome otomatis (Selenium) di server untuk membuka video TikTok
        dan mengambil komentar publik yang tampil di layar. TikTok bisa saja menampilkan
        captcha atau meminta login untuk video tertentu &mdash; kalau itu terjadi, coba video lain
        atau kurangi jumlah komentar yang diminta. Pastikan Chrome + chromedriver sudah
        terpasang di server Flask.
      </div>

      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">URL Video TikTok</label>
          <input type="text" class="form-control" id="url" placeholder="https://www.tiktok.com/@akun/video/xxxxxxxxxxxxx">
        </div>
        <div class="col-md-4">
          <label class="form-label">Jumlah komentar</label>
          <input type="number" class="form-control" id="maxComments" value="" min="250" max="500">
        </div>
      </div>

      <button class="btn btn-primary mt-3" id="btnScrape">Mulai Scraping</button>

      <div id="loading" class="mt-3" style="display:none">
        <div class="spinner-border text-primary spinner-border-sm"></div>
        Sedang membuka & men-scroll halaman TikTok, mohon tunggu...
      </div>

      <div id="resultArea" class="mt-4" style="display:none">

        <div class="d-flex justify-content-between align-items-center">
          <h5>Hasil Scraping (<span id="totalCount">0</span> komentar)</h5>
          <div>
            <input type="text" class="form-control form-control-sm d-inline-block w-auto" id="datasetName" placeholder="Nama dataset (mis. dapursilmisalim_video1.csv)">
            <button class="btn btn-success btn-sm" id="btnSave">Simpan sebagai Dataset Baru</button>
          </div>
        </div>

        <table class="table table-bordered table-sm mt-2">
          <thead>
            <tr><th style="width:150px">Username</th><th>Komentar</th><th style="width:80px">Like</th></tr>
          </thead>
          <tbody id="commentTableBody"></tbody>
        </table>

      </div>

    </div>
  </div>

</div>

<script>

let scrapedComments = [];

$("#btnScrape").click(function () {

    let url = $("#url").val().trim();
    let maxComments = parseInt($("#maxComments").val()) || 50;

    if (!url) {
        alert("Masukkan URL video TikTok terlebih dahulu.");
        return;
    }

    $("#resultArea").hide();
    $("#loading").show();
    $("#btnScrape").prop("disabled", true);

    $.ajax({
        url: "../api/scrape.php?action=run",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ url: url, max_comments: maxComments }),
        success: function (res) {

            $("#loading").hide();
            $("#btnScrape").prop("disabled", false);

            if (res.status !== "success") {
                alert(res.message || "Scraping gagal.");
                return;
            }

            scrapedComments = res.comments;

            $("#totalCount").text(res.total);

            let rows = "";
            res.comments.forEach(function (c) {
                rows += "<tr><td>" + $("<div>").text(c.username).html() + "</td>";
                rows += "<td>" + $("<div>").text(c.comment).html() + "</td>";
                rows += "<td>" + $("<div>").text(c.likes).html() + "</td></tr>";
            });

            $("#commentTableBody").html(rows);
            $("#resultArea").show();
        },
        error: function () {
            $("#loading").hide();
            $("#btnScrape").prop("disabled", false);
            alert("Gagal menghubungi server. Pastikan Flask & Selenium berjalan.");
        }
    });

});

$("#btnSave").click(function () {

    if (scrapedComments.length === 0) {
        alert("Belum ada hasil scraping untuk disimpan.");
        return;
    }

    let filename = $("#datasetName").val().trim() || ("scrape_" + Date.now() + ".csv");

    $.ajax({
        url: "../api/scrape.php?action=save",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ filename: filename, comments: scrapedComments }),
        success: function (res) {

            if (res.status !== "success") {
                alert(res.message || "Gagal menyimpan dataset.");
                return;
            }

            alert("Tersimpan " + res.saved + " komentar sebagai dataset baru. Silakan beri label sentimen di halaman Kelola Dataset.");
            window.location.href = "dataset.php?id=" + res.dataset_id;
        },
        error: function () {
            alert("Gagal menyimpan dataset.");
        }
    });

});

</script>

<?php include "partials/foot.php"; ?>
</body>
</html>

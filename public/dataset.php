<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Kelola Dataset - SentiGuard MBG</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-database-fill text-primary me-2"></i>Kelola Dataset &amp; Pelabelan</h4>
        <small class="text-muted">Preprocessing, penentuan label Cyberbullying vs Negatif Biasa (Kritik Wajar), dan ekspor data</small>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" id="btnPreprocess">
          <i class="bi bi-gear-fill me-1"></i> Jalankan Preprocessing
        </button>
        <button class="btn btn-outline-success btn-sm" id="btnTrain">
          <i class="bi bi-cpu-fill me-1"></i> Latih Model dari Dataset Ini
        </button>
        <a class="btn btn-outline-secondary btn-sm" id="btnExport" href="#" data-no-spa="true">
          <i class="bi bi-download me-1"></i> Export CSV Berlabel
        </a>
      </div>
    </div>

    <div class="card-body p-4">

      <div id="datasetInfo" class="mb-3 p-3 bg-light rounded border"></div>

      <!-- Ringkasan Kategori Cyberbullying vs Negatif Biasa vs Positif vs Netral -->
      <div class="row g-2 mb-3">
        <div class="col-md-3">
          <div class="p-2 border rounded bg-danger-subtle border-danger d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-danger">🚨 CYBERBULLYING</span>
              <div class="small text-muted">Makian / Hinaan</div>
            </div>
            <div class="fs-4 fw-bold text-danger" id="countCyberbullying">-</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-2 border rounded bg-warning-subtle border-warning d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-warning text-dark">💬 NEGATIF BIASA</span>
              <div class="small text-muted">Kritik / Keluhan Wajar</div>
            </div>
            <div class="fs-4 fw-bold text-warning-emphasis" id="countOrdNegative">-</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-2 border rounded bg-success-subtle border-success d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-success">🛡️ POSITIF</span>
              <div class="small text-muted">Apresiasi / Dukungan</div>
            </div>
            <div class="fs-4 fw-bold text-success" id="countPositive">-</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-2 border rounded bg-light border d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-secondary">ℹ️ NETRAL</span>
              <div class="small text-muted">Informasi / Pertanyaan</div>
            </div>
            <div class="fs-4 fw-bold text-secondary" id="countNeutral">-</div>
          </div>
        </div>
      </div>

      <div id="actionMessage"></div>

      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:130px">Username</th>
              <th>Komentar TikTok</th>
              <th style="width:200px">Hasil Preprocessing (Stemming)</th>
              <th style="width:250px">Label Sentimen &amp; Deteksi Cyberbullying</th>
            </tr>
          </thead>
          <tbody id="commentTableBody">
            <tr><td colspan="4" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat data komentar...</td></tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

</div>

<script>
const params = new URLSearchParams(window.location.search);
const datasetId = params.get("id");

if (!datasetId) {
    $("#datasetInfo").html("<div class='alert alert-warning mb-0'>Dataset ID tidak ditemukan di URL. Silakan pilih dataset dari halaman <a href='upload.php' data-spa='true'>Upload Dataset</a> atau <a href='index.php' data-spa='true'>Dashboard</a>.</div>");
    $("#commentTableBody").html("<tr><td colspan='4' class='text-center text-muted py-3'>Tidak ada dataset yang dipilih.</td></tr>");
    return;
}

$("#btnExport").attr("href", "../api/dataset.php?action=export&dataset_id=" + datasetId);

// Leksikon pendeteksi kata kasar/makian cyberbullying
const cbLexicon = [
    "anjing", "anjir", "anjay", "anjrit", "asu", "babi", "bangsat", "bajingan", 
    "kampret", "tai", "taek", "kontol", "memek", "ngentot", "peler", "pantek", "puki",
    "kntl", "mmk", "tolol", "goblok", "bego", "idiot", "bodoh", "dungu", "autis", 
    "cacat", "bloon", "pekok", "sinting", "gila", "sarap", "miring", "gembel", 
    "sampah", "najis", "busuk", "bangkai", "racun", "beracun", "mampus", "mati", 
    "laknat", "celaka", "dajjal", "iblis", "setan", "jahanam", "sialan", "biadab", 
    "haram", "maling", "rampok", "koruptor", "korupsi", "pencitraan", "monyet", 
    "cebong", "bencong", "banci", "jelek bet"
];

function checkCyberbullying(text) {
    let lower = (text || "").toLowerCase();
    let words = lower.replace(/[^a-z0-9]/g, " ").split(/\s+/);
    let detected = [];
    words.forEach(function(w) {
        if (cbLexicon.includes(w) && !detected.includes(w)) {
            detected.push(w);
        }
    });
    return detected;
}

function loadComments() {
    $.get("../api/dataset.php?action=comments&dataset_id=" + datasetId, function (res) {
        if (res.status !== "success") {
            $("#commentTableBody").html("<tr><td colspan='4' class='text-danger text-center'>" + res.message + "</td></tr>");
            return;
        }

        let d = res.dataset;
        $("#datasetInfo").html(
            "<b>Nama File:</b> " + d.filename +
            " &nbsp;|&nbsp; <b>Total Komentar:</b> " + d.total_comments +
            " &nbsp;|&nbsp; <b>Status Pipeline:</b> <span class='badge bg-secondary'>" + d.status + "</span>"
        );

        let cbCount = 0;
        let ordNegCount = 0;
        let posCount = 0;
        let neuCount = 0;

        if (res.comments.length === 0) {
            $("#commentTableBody").html("<tr><td colspan='4' class='text-muted text-center py-3'>Belum ada komentar dalam dataset ini.</td></tr>");
            $("#countCyberbullying").text("0");
            $("#countOrdNegative").text("0");
            $("#countPositive").text("0");
            $("#countNeutral").text("0");
            return;
        }

        let rows = "";

        res.comments.forEach(function (c) {
            let sent = c.sentiment || "";
            let cbTerms = checkCyberbullying(c.comment);
            let isCb = (sent === "Negatif" && cbTerms.length > 0);
            let isOrdNeg = (sent === "Negatif" && cbTerms.length === 0);

            if (isCb) {
                cbCount++;
            } else if (isOrdNeg) {
                ordNegCount++;
            } else if (sent === "Positif") {
                posCount++;
            } else if (sent === "Netral") {
                neuCount++;
            }

            let options = [
                { val: "", text: "- Pilih Label Sentimen -" },
                { val: "Negatif", text: "Negatif (Kritik / Cyberbullying)" },
                { val: "Positif", text: "Positif (🛡️ Apresiasi/Dukungan)" },
                { val: "Netral", text: "Netral (ℹ️ Faktual/Pertanyaan)" }
            ];

            let labelSelect = "<select class='form-select form-select-sm label-select' data-id='" + c.id + "'>";
            options.forEach(function (opt) {
                let selected = (sent === opt.val) ? "selected" : "";
                labelSelect += "<option value='" + opt.val + "' " + selected + ">" + opt.text + "</option>";
            });
            labelSelect += "</select>";

            let statusBadge = "";
            if (isCb) {
                statusBadge = "<div class='mt-1'><span class='badge bg-danger'>🚨 Cyberbullying</span> <small class='text-danger'>(" + cbTerms.join(", ") + ")</small></div>";
            } else if (isOrdNeg) {
                statusBadge = "<div class='mt-1'><span class='badge bg-warning text-dark'>💬 Negatif Biasa (Kritik Wajar)</span></div>";
            } else if (sent === "Positif") {
                statusBadge = "<div class='mt-1'><span class='badge bg-success'>🛡️ Positif (Non-CB)</span></div>";
            } else if (sent === "Netral") {
                statusBadge = "<div class='mt-1'><span class='badge bg-secondary'>ℹ️ Netral (Non-CB)</span></div>";
            }

            rows += "<tr>";
            rows += "<td><b>@" + $("<div>").text(c.username || "-").html() + "</b></td>";
            rows += "<td>" + $("<div>").text(c.comment).html() + "</td>";
            rows += "<td class='text-muted small'><code>" + $("<div>").text(c.stemming || "-").html() + "</code></td>";
            rows += "<td>" + labelSelect + statusBadge + "</td>";
            rows += "</tr>";
        });

        $("#countCyberbullying").text(cbCount);
        $("#countOrdNegative").text(ordNegCount);
        $("#countPositive").text(posCount);
        $("#countNeutral").text(neuCount);
        $("#commentTableBody").html(rows);
    });
}

loadComments();

$(document).off("change.mbgLabel", ".label-select").on("change.mbgLabel", ".label-select", function () {
    let commentId = $(this).data("id");
    let sentiment = $(this).val();

    $.ajax({
        url: "../api/dataset.php?action=label",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ comment_id: commentId, sentiment: sentiment }),
        success: function () {
            loadComments();
        }
    });
});

$("#btnPreprocess").off("click").on("click", function () {
    let $btn = $(this);
    $btn.prop("disabled", true).html("<i class='bi bi-hourglass-split'></i> Memproses Preprocessing...");
    $("#actionMessage").html("");

    $.ajax({
        url: "../api/dataset.php?action=preprocess",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ dataset_id: datasetId }),
        success: function (res) {
            $btn.prop("disabled", false).html("<i class='bi bi-gear-fill me-1'></i> Jalankan Preprocessing");
            if (res.status !== "success") {
                $("#actionMessage").html("<div class='alert alert-danger'>" + res.message + "</div>");
                return;
            }
            $("#actionMessage").html(
                "<div class='alert alert-success'>Preprocessing selesai: <b>" + res.processed + " / " + res.total + "</b> komentar berhasil diproses.</div>"
            );
            loadComments();
        },
        error: function () {
            $btn.prop("disabled", false).html("<i class='bi bi-gear-fill me-1'></i> Jalankan Preprocessing");
            $("#actionMessage").html("<div class='alert alert-danger'>Gagal menghubungi server.</div>");
        }
    });
});

$("#btnTrain").off("click").on("click", function () {
    let $btn = $(this);
    $btn.prop("disabled", true).html("<i class='bi bi-hourglass-split'></i> Melatih...");
    $("#actionMessage").html("");

    $.ajax({
        url: "../api/dataset.php?action=train",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ dataset_id: datasetId }),
        success: function (res) {
            $btn.prop("disabled", false).html("<i class='bi bi-cpu-fill me-1'></i> Latih Model dari Dataset Ini");
            if (res.status !== "success") {
                $("#actionMessage").html("<div class='alert alert-danger'>" + (res.message || "Training gagal.") + "</div>");
                return;
            }
            let ev = res.evaluation || {};
            $("#actionMessage").html(
                "<div class='alert alert-success'>" +
                "Training berhasil! Akurasi: <b>" + (ev.accuracy * 100).toFixed(2) + "%</b>, " +
                "Precision: <b>" + ev.precision + "</b>, Recall: <b>" + ev.recall + "</b>, F1: <b>" + ev.f1_score + "</b>. " +
                "<a href='history.php' data-spa='true' class='btn btn-sm btn-outline-success ms-2'>Lihat Riwayat &rarr;</a>" +
                "</div>"
            );
            loadComments();
        },
        error: function () {
            $btn.prop("disabled", false).html("<i class='bi bi-cpu-fill me-1'></i> Latih Model dari Dataset Ini");
            $("#actionMessage").html("<div class='alert alert-danger'>Gagal menghubungi server.</div>");
        }
    });
});
</script>

<?php include "partials/foot.php"; ?>
</body>
</html>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Prediksi Komentar - SentiGuard MBG</title>
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

  <!-- Penjelasan Konsep Klasifikasi: Cyberbullying vs Negatif Biasa -->
  <div class="alert alert-light border shadow-sm mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
      <i class="bi bi-info-circle-fill text-primary fs-5"></i>
      <h6 class="mb-0 fw-bold">Pedoman Klasifikasi: Membedakan Cyberbullying vs Komentar Negatif Biasa</h6>
    </div>
    <div class="row g-2 small text-muted">
      <div class="col-md-6">
        <div class="p-2 border rounded bg-danger-subtle border-danger h-100">
          <span class="badge bg-danger me-1">🚨 CYBERBULLYING</span> (Sentimen Negatif Agresif)
          <p class="mb-0 mt-1 text-dark">Komentar negatif yang memuat <b>makian kasar, cemoohan, pelecehan personal/kelompok, penghinaan martabat, atau ujaran kebencian</b> terhadap pihak/program MBG.</p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="p-2 border rounded bg-warning-subtle border-warning h-100">
          <span class="badge bg-warning text-dark me-1">💬 NEGATIF BIASA</span> (Kritik / Keluhan Wajar - Aman)
          <p class="mb-0 mt-1 text-dark">Komentar negatif berupa <b>komplain rasa, porsi, antrean, atau saran perbaikan</b> TANPA kata makian, hinaan martabat, atau serangan personal (Bukan Cyberbullying).</p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="p-2 border rounded bg-white border h-100">
          <span class="badge bg-success me-1">🛡️ POSITIF</span> (Non-Cyberbullying)
          <p class="mb-0 mt-1">Komentar berupa dukungan, apresiasi, kepuasan, atau antusiasme baik terhadap program MBG.</p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="p-2 border rounded bg-white border h-100">
          <span class="badge bg-secondary me-1">ℹ️ NETRAL</span> (Non-Cyberbullying)
          <p class="mb-0 mt-1">Komentar berupa pertanyaan faktual, informasi objektif, atau pernyataan netral tanpa muatan emosi negatif/positif.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
      <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-dots-fill text-primary me-2"></i>Prediksi Komentar &amp; Deteksi Cyberbullying</h4>
      <small class="text-muted">Uji satu komentar baru menggunakan model Naive Bayes, TF-IDF, dan deteksi leksikon cyberbullying</small>
    </div>

    <div class="card-body p-4">
      <div class="mb-3">
        <label for="text" class="form-label fw-semibold">Masukkan Komentar TikTok:</label>
        <textarea class="form-control" id="text" rows="4" placeholder="Tulis komentar..."></textarea>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-2">
        <button class="btn btn-primary px-4 fw-semibold" id="btnPredict">
          <i class="bi bi-search me-1"></i> Analisis &amp; Prediksi
        </button>
        <button class="btn btn-outline-danger btn-sm" id="btnSampleCB">
          <i class="bi bi-exclamation-octagon me-1"></i> Contoh Cyberbullying (Makian)
        </button>
        <button class="btn btn-outline-warning btn-sm text-dark" id="btnSampleNegBiasa">
          <i class="bi bi-chat-square-text me-1"></i> Contoh Negatif Biasa (Kritik Wajar)
        </button>
        <button class="btn btn-outline-success btn-sm" id="btnSamplePos">
          <i class="bi bi-hand-thumbs-up me-1"></i> Contoh Positif
        </button>
        <button class="btn btn-outline-secondary btn-sm" id="btnSampleNeu">
          <i class="bi bi-question-circle me-1"></i> Contoh Netral
        </button>
      </div>

      <div id="loading" class="mt-4" style="display:none">
        <div class="d-flex align-items-center gap-2 text-primary">
          <div class="spinner-border spinner-border-sm"></div>
          <span>Sedang melakukan preprocessing teks dan menganalisis klasifikasi sentimen &amp; cyberbullying...</span>
        </div>
      </div>

      <div id="alertBox" class="alert alert-danger mt-3" style="display:none"></div>

      <!-- Hasil Prediksi -->
      <div id="resultBox" class="mt-4" style="display:none">
        <hr>
        <h5 class="fw-bold mb-3 text-secondary">HASIL ANALISIS KLASIFIKASI &amp; DETEKSI</h5>

        <!-- Banner Status Cyberbullying Utama -->
        <div id="cyberbullyingBanner" class="p-4 rounded-3 border mb-4">
          <div class="d-flex align-items-start gap-3">
            <div id="bannerIcon" class="fs-1"></div>
            <div class="flex-grow-1">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="fs-4 fw-bold" id="cyberbullyingTitle"></span>
                <span id="badgeSentimentClass" class="badge fs-6"></span>
              </div>
              <p class="mb-2 fs-6" id="cyberbullyingExplanation"></p>
              
              <!-- Indikator Kata Kunci Cyberbullying (Jika Ada) -->
              <div id="detectedTermsBox" class="mb-2" style="display:none">
                <span class="small fw-bold text-danger">Kata makian/hinaan yang terdeteksi: </span>
                <span id="detectedTermsList"></span>
              </div>

              <div class="d-flex flex-wrap gap-3 small text-muted pt-2 border-top">
                <div>Keyakinan Model (Confidence): <b class="text-dark fs-6" id="confidenceText"></b></div>
                <div>Status Akhir: <b class="text-dark" id="statusSummaryText"></b></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Preprocessing Pipeline -->
        <div class="card mb-4 bg-light border-0">
          <div class="card-body">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-gear-wide-connected me-1"></i>Hasil Preprocessing Teks</h6>
            <div class="p-2 bg-white rounded border font-monospace text-primary" id="preprocessedText"></div>
            <small class="text-muted mt-1 d-block">
              Pipeline: Case Folding &rarr; Cleansing &rarr; Normalisasi Huruf &rarr; Stopword Removal &rarr; Stemming Sastrawi
            </small>
          </div>
        </div>

        <!-- Tabel Probabilitas -->
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-bar-chart-fill me-1"></i>Distribusi Probabilitas Kelas Sentimen (Multinomial Naive Bayes)</h6>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Kelas Sentimen</th>
                <th>Kategori Deteksi</th>
                <th>Probabilitas</th>
                <th style="width: 40%">Visualisasi Distribusi</th>
              </tr>
            </thead>
            <tbody id="probabilityBody"></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  <p class="text-muted mt-3 small">
    Riwayat pengujian disimpan secara otomatis di database. Kunjungi halaman <a href="history.php" data-spa="true">Riwayat</a> untuk melihat log sebelumnya.
  </p>

</div>

<script>
function renderPredictionResult(res) {
    let isCb = res.is_cyberbullying === true;
    let isOrdNeg = res.is_ordinary_negative === true || (res.prediction === "Negatif" && !isCb);
    let sentiment = res.prediction || "Netral";

    let banner = $("#cyberbullyingBanner");
    let icon = $("#bannerIcon");
    let title = $("#cyberbullyingTitle");
    let explanation = $("#cyberbullyingExplanation");
    let badgeSent = $("#badgeSentimentClass");
    let statusSummary = $("#statusSummaryText");
    let detectedBox = $("#detectedTermsBox");
    let detectedList = $("#detectedTermsList");

    // Reset kelas banner
    banner.removeClass("alert-danger alert-warning alert-success alert-secondary border-danger border-warning border-success border-secondary bg-danger-subtle bg-warning-subtle bg-success-subtle bg-light");

    if (isCb) {
        banner.addClass("alert-danger border-danger");
        icon.html('<i class="bi bi-exclamation-octagon-fill text-danger"></i>');
        title.html('<span class="text-danger">🚨 TERDETEKSI CYBERBULLYING</span>');
        badgeSent.removeClass().addClass("badge bg-danger").text("Sentimen: NEGATIF (Cyberbullying)");
        explanation.text(res.explanation);
        statusSummary.html('<span class="text-danger fw-bold">🚨 Cyberbullying (Perlu Moderasi / Tindakan)</span>');

        if (res.detected_terms && res.detected_terms.length > 0) {
            let termsBadges = res.detected_terms.map(t => "<span class='badge bg-danger me-1'>" + t + "</span>").join(" ");
            detectedList.html(termsBadges);
            detectedBox.show();
        } else {
            detectedBox.hide();
        }
    } else if (isOrdNeg) {
        banner.addClass("alert-warning border-warning");
        icon.html('<i class="bi bi-chat-square-text-fill text-warning"></i>');
        title.html('<span class="text-dark">💬 KOMENTAR NEGATIF BIASA (BUKAN CYBERBULLYING)</span>');
        badgeSent.removeClass().addClass("badge bg-warning text-dark").text("Sentimen: NEGATIF (Kritik Wajar)");
        explanation.text(res.explanation);
        statusSummary.html('<span class="text-warning-emphasis fw-bold">💬 Negatif Biasa (Kritik / Keluhan Wajar - Aman)</span>');
        detectedBox.hide();
    } else if (sentiment === "Positif") {
        banner.addClass("alert-success border-success");
        icon.html('<i class="bi bi-shield-check text-success"></i>');
        title.html('<span class="text-success">🛡️ NON-CYBERBULLYING (Sentimen Positif)</span>');
        badgeSent.removeClass().addClass("badge bg-success").text("Sentimen: POSITIF");
        explanation.text(res.explanation);
        statusSummary.html('<span class="text-success fw-bold">🛡️ Non-Cyberbullying (Apresiasi / Positif)</span>');
        detectedBox.hide();
    } else {
        banner.addClass("alert-secondary border-secondary");
        icon.html('<i class="bi bi-info-circle-fill text-secondary"></i>');
        title.html('<span class="text-secondary">ℹ️ NON-CYBERBULLYING (Sentimen Netral)</span>');
        badgeSent.removeClass().addClass("badge bg-secondary").text("Sentimen: NETRAL");
        explanation.text(res.explanation);
        statusSummary.html('<span class="text-secondary fw-bold">ℹ️ Non-Cyberbullying (Informasi / Netral)</span>');
        detectedBox.hide();
    }

    $("#preprocessedText").text(res.preprocessing || "-");
    $("#confidenceText").text(((res.confidence || 0) * 100).toFixed(2) + "%");

    let rows = "";
    let probs = res.probabilities || {};

    Object.keys(probs).forEach(function (label) {
        let prob = probs[label];
        let pct = (prob * 100).toFixed(2);
        let cbDesc = "";
        let barColor = "bg-secondary";

        if (label === "Negatif") {
            cbDesc = "<span class='badge bg-danger me-1'>Cyberbullying</span> / <span class='badge bg-warning text-dark'>Negatif Biasa</span>";
            barColor = isCb ? "bg-danger" : "bg-warning";
        } else if (label === "Positif") {
            cbDesc = "<span class='badge bg-success'>Non-Cyberbullying</span>";
            barColor = "bg-success";
        } else {
            cbDesc = "<span class='badge bg-secondary'>Non-Cyberbullying</span>";
            barColor = "bg-secondary";
        }

        rows += "<tr>";
        rows += "<td><b>" + label + "</b></td>";
        rows += "<td>" + cbDesc + "</td>";
        rows += "<td><span class='fw-semibold'>" + pct + "%</span> <small class='text-muted'>(" + prob + ")</small></td>";
        rows += "<td>";
        rows += "  <div class='progress' style='height: 18px;'>";
        rows += "    <div class='progress-bar " + barColor + "' role='progressbar' style='width: " + pct + "%;' aria-valuenow='" + pct + "' aria-valuemin='0' aria-valuemax='100'>" + pct + "%</div>";
        rows += "  </div>";
        rows += "</td>";
        rows += "</tr>";
    });

    $("#probabilityBody").html(rows);
    $("#resultBox").show();
}

$("#btnPredict").off("click").on("click", function () {
    let text = $("#text").val().trim();
    $("#alertBox").hide();
    $("#resultBox").hide();

    if (text === "") {
        $("#alertBox").text("Teks komentar tidak boleh kosong. Silakan tuliskan komentar yang ingin dianalisis.").show();
        return;
    }

    $("#loading").show();

    $.ajax({
        url: "../api/predict.php",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({ text: text }),
        success: function (res) {
            $("#loading").hide();
            if (res.status !== "success") {
                $("#alertBox").text(res.message || "Prediksi gagal dijalankan.").show();
                return;
            }
            renderPredictionResult(res);
        },
        error: function () {
            $("#loading").hide();
            $("#alertBox").text("Gagal terhubung ke service API. Pastikan service Python Flask sedang aktif di port 5000.").show();
        }
    });
});

// Tombol Contoh Uji Cepat
$("#btnSampleCB").off("click").on("click", function () {
    $("#text").val("Dasar program MBG tolol busuk beracun korupsi, menu kayak sampah najis mampus lu!");
});

$("#btnSampleNegBiasa").off("click").on("click", function () {
    $("#text").val("Makanannya kurang asin dan sayurnya dingin banget, tolong porsi nasi dan ayamnya ditambah ya.");
});

$("#btnSamplePos").off("click").on("click", function () {
    $("#text").val("Alhamdulillah menu MBG hari ini enak banget dan sangat bergizi, terima kasih banyak atas kerja keras tim dapur!");
});

$("#btnSampleNeu").off("click").on("click", function () {
    $("#text").val("Apakah program MBG ini sudah berlaku serentak di seluruh sekolah dasar di Jawa Barat?");
});
</script>

<?php include "partials/foot.php"; ?>
</body>
</html>

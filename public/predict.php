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

  <!-- Penjelasan Konsep Klasifikasi -->
  <div class="alert alert-light border shadow-sm mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
      <i class="bi bi-info-circle-fill text-primary fs-5"></i>
      <h6 class="mb-0 fw-bold">Pedoman Klasifikasi Cyberbullying vs Non-Cyberbullying</h6>
    </div>
    <div class="row g-2 small text-muted">
      <div class="col-md-6">
        <div class="p-2 border rounded bg-white">
          <span class="badge bg-danger me-1">🚨 CYBERBULLYING</span> = Sentimen <b>Negatif</b>
          <p class="mb-0 mt-1">Komentar berupa makian, cemoohan, pelecehan verbal, tuduhan tak berdasar, atau ujaran kebencian terhadap program MBG.</p>
        </div>
      </div>
      <div class="col-md-6">
        <div class="p-2 border rounded bg-white">
          <span class="badge bg-success me-1">🛡️ NON-CYBERBULLYING</span> = Sentimen <b>Positif</b> &amp; <b>Netral</b>
          <p class="mb-0 mt-1">Komentar berupa dukungan, apresiasi, kepuasan, maupun pertanyaan informatif dan diskusi objektif yang aman.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
      <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-dots-fill text-primary me-2"></i>Prediksi Komentar &amp; Deteksi Cyberbullying</h4>
      <small class="text-muted">Uji satu komentar baru menggunakan model Naive Bayes dan pembobotan kata TF-IDF</small>
    </div>

    <div class="card-body p-4">
      <div class="mb-3">
        <label for="text" class="form-label fw-semibold">Masukkan Komentar TikTok:</label>
        <textarea class="form-control" id="text" rows="4" placeholder="Contoh: Menunya buruk banget, beracun dan bikin muntah! atau Makanannya enak dan bergizi sekali, terima kasih!"></textarea>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary px-4 fw-semibold" id="btnPredict">
          <i class="bi bi-search me-1"></i> Analisis &amp; Prediksi
        </button>
        <button class="btn btn-outline-secondary" id="btnSamplePos">Contoh Positif</button>
        <button class="btn btn-outline-secondary" id="btnSampleNeg">Contoh Negatif (Cyberbullying)</button>
        <button class="btn btn-outline-secondary" id="btnSampleNeu">Contoh Netral</button>
      </div>

      <div id="loading" class="mt-4" style="display:none">
        <div class="d-flex align-items-center gap-2 text-primary">
          <div class="spinner-border spinner-border-sm"></div>
          <span>Sedang melakukan preprocessing teks dan menghitung probabilitas Naive Bayes...</span>
        </div>
      </div>

      <div id="alertBox" class="alert alert-danger mt-3" style="display:none"></div>

      <!-- Hasil Prediksi -->
      <div id="resultBox" class="mt-4" style="display:none">
        <hr>
        <h5 class="fw-bold mb-3 text-secondary">HASIL ANALISIS MODEL</h5>

        <!-- Banner Status Cyberbullying Utama -->
        <div id="cyberbullyingBanner" class="p-4 rounded-3 border mb-4">
          <div class="d-flex align-items-start gap-3">
            <div id="bannerIcon" class="fs-1"></div>
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fs-4 fw-bold" id="cyberbullyingTitle"></span>
                <span id="badgeSentimentClass" class="badge fs-6"></span>
              </div>
              <p class="mb-2 fs-6" id="cyberbullyingExplanation"></p>
              <div class="d-flex flex-wrap gap-3 small text-muted pt-2 border-top">
                <div>Tingkat Keyakinan (Confidence): <b class="text-dark fs-6" id="confidenceText"></b></div>
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
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-bar-chart-fill me-1"></i>Distribusi Probabilitas Kelas (Multinomial Naive Bayes)</h6>
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
    let isCb = (res.is_cyberbullying || res.prediction === "Negatif" || (res.cyberbullying_status || "").toLowerCase().includes("cyberbullying") && !(res.cyberbullying_status || "").toLowerCase().includes("non"));
    let sentiment = res.prediction || res.sentiment || "Netral";

    let banner = $("#cyberbullyingBanner");
    let icon = $("#bannerIcon");
    let title = $("#cyberbullyingTitle");
    let explanation = $("#cyberbullyingExplanation");
    let badgeSent = $("#badgeSentimentClass");
    let statusSummary = $("#statusSummaryText");

    // Reset kelas banner
    banner.removeClass("alert-danger alert-success alert-secondary border-danger border-success border-secondary bg-danger-subtle bg-success-subtle bg-light");

    if (isCb) {
        banner.addClass("alert-danger border-danger");
        icon.html('<i class="bi bi-exclamation-octagon-fill text-danger"></i>');
        title.html('<span class="text-danger">🚨 TERDETEKSI CYBERBULLYING</span>');
        badgeSent.removeClass().addClass("badge bg-danger").text("Sentimen: NEGATIF");
        explanation.text(res.explanation || "Komentar ini teridentifikasi sebagai Sentimen Negatif yang dikategorikan sebagai CYBERBULLYING (memuat ujaran kebencian, cemoohan, atau serangan verbal terhadap program MBG).");
        statusSummary.html('<span class="text-danger fw-bold">CYBERBULLYING (Perlu Moderasi)</span>');
    } else if (sentiment === "Positif") {
        banner.addClass("alert-success border-success");
        icon.html('<i class="bi bi-shield-check text-success"></i>');
        title.html('<span class="text-success">🛡️ NON-CYBERBULLYING (Aman)</span>');
        badgeSent.removeClass().addClass("badge bg-success").text("Sentimen: POSITIF");
        explanation.text(res.explanation || "Komentar ini teridentifikasi sebagai Sentimen Positif yang dikategorikan sebagai NON-CYBERBULLYING (memuat apresiasi atau dukungan positif).");
        statusSummary.html('<span class="text-success fw-bold">NON-CYBERBULLYING (Aman / Positif)</span>');
    } else {
        banner.addClass("alert-secondary border-secondary");
        icon.html('<i class="bi bi-shield-check text-secondary"></i>');
        title.html('<span class="text-secondary">🛡️ NON-CYBERBULLYING (Netral)</span>');
        badgeSent.removeClass().addClass("badge bg-secondary").text("Sentimen: NETRAL");
        explanation.text(res.explanation || "Komentar ini teridentifikasi sebagai Sentimen Netral yang dikategorikan sebagai NON-CYBERBULLYING (memuat pertanyaan objektif atau fakta tanpa unsur perundungan).");
        statusSummary.html('<span class="text-secondary fw-bold">NON-CYBERBULLYING (Aman / Netral)</span>');
    }

    $("#preprocessedText").text(res.preprocessing || "-");
    $("#confidenceText").text(((res.confidence || 0) * 100).toFixed(2) + "%");

    let rows = "";
    let probs = res.probabilities || {};

    Object.keys(probs).forEach(function (label) {
        let prob = probs[label];
        let pct = (prob * 100).toFixed(2);
        let cbStatus = (label === "Negatif") ? "<span class='badge bg-danger'>Cyberbullying</span>" : "<span class='badge bg-success'>Non-Cyberbullying</span>";
        let barColor = (label === "Negatif") ? "bg-danger" : (label === "Positif" ? "bg-success" : "bg-warning");

        rows += "<tr>";
        rows += "<td><b>" + label + "</b></td>";
        rows += "<td>" + cbStatus + "</td>";
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

$("#btnPredict").click(function () {
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
$("#btnSamplePos").click(function () {
    $("#text").val("Alhamdulillah menu MBG di sekolah anakku sangat bergizi dan enak sekali, anak jadi semangat sekolah!");
});
$("#btnSampleNeg").click(function () {
    $("#text").val("Dasar program MBG tolol busuk beracun, menu kayak sampah gini dikorupsi semua duitnya!");
});
$("#btnSampleNeu").click(function () {
    $("#text").val("Apakah program MBG ini sudah berlaku di semua sekolah dasar di Jawa Barat?");
});
</script>

<?php include "partials/foot.php"; ?>
</body>
</html>

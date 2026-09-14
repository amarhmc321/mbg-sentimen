<?php

require_once __DIR__ . "/../app/autoload.php";
require_once __DIR__ . "/../app/config/Database.php";
require_once __DIR__ . "/../app/model/MLModel.php";
require_once __DIR__ . "/../app/model/Prediction.php";

$conn = Database::connect();

$mlModel = new MLModel($conn);
$prediction = new Prediction($conn);

$models = $mlModel->history(20);
$predictions = $prediction->history(50);

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Riwayat - SentiGuard MBG</title>
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

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Sistem</h3>
      <p class="text-muted mb-0">Log pelatihan model Naive Bayes dan riwayat pengujian prediksi komentar</p>
    </div>
    <div>
      <a href="train.php" data-spa="true" class="btn btn-sm btn-outline-success me-2"><i class="bi bi-cpu-fill me-1"></i> Training Baru</a>
      <a href="predict.php" data-spa="true" class="btn btn-sm btn-outline-primary"><i class="bi bi-search me-1"></i> Prediksi Baru</a>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold"><i class="bi bi-hdd-stack-fill text-success me-2"></i>Riwayat Pelatihan Model (ML Models)</h5>
      <span class="badge bg-secondary"><?= count($models) ?> model tercatat</span>
    </div>
    <div class="card-body p-0">

      <?php if (empty($models)): ?>
        <p class="text-muted p-4 mb-0">Belum ada model yang dilatih. Kunjungi halaman <a href="train.php" data-spa="true">Training Model</a> untuk mulai melatih.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-bordered table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width: 50px;">ID</th>
              <th>Dataset Sumber</th>
              <th>Algoritma &amp; Fitur</th>
              <th class="text-center">Jumlah Data</th>
              <th class="text-center">Akurasi</th>
              <th class="text-center">Precision</th>
              <th class="text-center">Recall</th>
              <th class="text-center">F1-Score</th>
              <th class="text-center">Waktu Pelatihan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($models as $m): ?>
            <tr>
              <td class="text-center fw-bold"><?= (int)$m["id"] ?></td>
              <td><b><?= htmlspecialchars($m["filename"] ?? "Upload Manual") ?></b></td>
              <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($m["algorithm"]) ?></span></td>
              <td class="text-center"><?= (int)$m["trained_rows"] ?> data</td>
              <td class="text-center fw-bold text-primary"><?= $m["accuracy"] !== null ? round($m["accuracy"] * 100, 2) . "%" : "-" ?></td>
              <td class="text-center"><?= $m["precision_score"] !== null ? $m["precision_score"] : "-" ?></td>
              <td class="text-center"><?= $m["recall_score"] !== null ? $m["recall_score"] : "-" ?></td>
              <td class="text-center"><?= $m["f1_score"] !== null ? $m["f1_score"] : "-" ?></td>
              <td class="text-center small text-muted"><?= htmlspecialchars($m["created_at"]) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold"><i class="bi bi-chat-left-quote-fill text-primary me-2"></i>Riwayat Pengujian Prediksi Komentar</h5>
      <span class="badge bg-secondary"><?= count($predictions) ?> prediksi terakhir</span>
    </div>
    <div class="card-body p-0">

      <?php if (empty($predictions)): ?>
        <p class="text-muted p-4 mb-0">Belum ada riwayat prediksi komentar. Coba uji komentar pada halaman <a href="predict.php" data-spa="true">Prediksi</a>.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-bordered table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="width: 35%;">Komentar TikTok</th>
              <th style="width: 25%;">Hasil Preprocessing</th>
              <th class="text-center" style="width: 20%;">Klasifikasi &amp; Status Cyberbullying</th>
              <th class="text-center">Confidence</th>
              <th class="text-center">Waktu Uji</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($predictions as $p): ?>
            <?php
              $pred = $p["prediction"] ?? "";
              $isCb = ($pred === "Negatif");
              $badgeClass = $isCb ? "bg-danger" : ($pred === "Positif" ? "bg-success" : "bg-secondary");
              $statusTitle = $isCb ? "🚨 Cyberbullying" : ($pred === "Positif" ? "🛡️ Non-Cyberbullying" : "🛡️ Non-Cyberbullying");
            ?>
            <tr>
              <td><?= htmlspecialchars($p["comment"]) ?></td>
              <td><code class="small"><?= htmlspecialchars($p["preprocessing"] ?? "-") ?></code></td>
              <td class="text-center">
                <span class="badge <?= $badgeClass ?> fs-6"><?= htmlspecialchars($pred) ?></span><br>
                <small class="fw-semibold <?= $isCb ? 'text-danger' : 'text-success' ?>"><?= $statusTitle ?></small>
              </td>
              <td class="text-center fw-bold">
                <?= $p["probability"] !== null ? round($p["probability"] * 100, 2) . "%" : "-" ?>
              </td>
              <td class="text-center small text-muted"><?= htmlspecialchars($p["created_at"]) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </div>

</div>

<?php include "partials/foot.php"; ?>
</body>
</html>

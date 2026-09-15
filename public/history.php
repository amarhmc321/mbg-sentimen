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

// Leksikon pendeteksi kata makian/hinaan cyberbullying sejati vs Negatif Biasa (Kritik Wajar)
$cyberbullyingLexicon = [
    "anjing", "anjir", "anjay", "anjrit", "asu", "babi", "bangsat", "bajingan",
    "kampret", "tai", "taek", "kontol", "memek", "ngentot", "peler", "pantek", "puki",
    "kntl", "mmk", "bgst", "anj", "asw",
    "tolol", "goblok", "bego", "idiot", "bodoh", "dungu", "autis", "cacat", "bloon",
    "pekok", "sinting", "gila", "sarap", "miring", "gembel", "udik", "kampungan",
    "sampah", "najis", "busuk", "bangkai", "racun", "beracun", "mampus", "mati",
    "laknat", "celaka", "dajjal", "iblis", "setan", "jahanam", "sialan", "biadab",
    "haram", "maling", "rampok", "koruptor", "korupsi", "pencitraan", "ngapusi",
    "monyet", "cebong", "bencong", "banci", "jelek bet", "muka lu", "babu"
];

function checkCyberbullyingText(string $text, array $lexicon): array {
    $lower = strtolower($text);
    $detected = [];
    foreach ($lexicon as $term) {
        if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $lower)) {
            $detected[] = $term;
        }
    }
    return $detected;
}

$predCbCount = 0;
$predOrdNegCount = 0;
$predPosCount = 0;
$predNeuCount = 0;

$processedPredictions = [];
foreach ($predictions as $p) {
    $pred = $p["prediction"] ?? "";
    $detectedTerms = checkCyberbullyingText($p["comment"] ?? "", $cyberbullyingLexicon);

    if ($pred === "Negatif" && !empty($detectedTerms)) {
        $category = "Cyberbullying";
        $badgeClass = "bg-danger";
        $labelTitle = "🚨 Cyberbullying";
        $labelDesc = "Makian: " . implode(", ", $detectedTerms);
        $isCyberbullying = true;
        $predCbCount++;
    } elseif ($pred === "Negatif") {
        $category = "Negatif Biasa";
        $badgeClass = "bg-warning text-dark";
        $labelTitle = "💬 Negatif Biasa";
        $labelDesc = "Kritik / Keluhan Wajar (Non-CB)";
        $isCyberbullying = false;
        $predOrdNegCount++;
    } elseif ($pred === "Positif") {
        $category = "Positif";
        $badgeClass = "bg-success";
        $labelTitle = "🛡️ Positif";
        $labelDesc = "Apresiasi / Non-CB";
        $isCyberbullying = false;
        $predPosCount++;
    } else {
        $category = "Netral";
        $badgeClass = "bg-secondary";
        $labelTitle = "ℹ️ Netral";
        $labelDesc = "Informasi / Non-CB";
        $isCyberbullying = false;
        $predNeuCount++;
    }

    $p["detected_terms"] = $detectedTerms;
    $p["category"] = $category;
    $p["badge_class"] = $badgeClass;
    $p["label_title"] = $labelTitle;
    $p["label_desc"] = $labelDesc;
    $p["is_cb"] = $isCyberbullying;
    $processedPredictions[] = $p;
}
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

      <!-- 4-Kategori Counter Ringkasan -->
      <div class="row g-2 p-3 bg-light border-bottom m-0">
        <div class="col-md-3">
          <div class="p-2 border rounded bg-danger-subtle border-danger d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-danger">🚨 CYBERBULLYING</span>
              <div class="small text-muted">Makian / Hinaan</div>
            </div>
            <div class="fs-5 fw-bold text-danger"><?= $predCbCount ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-2 border rounded bg-warning-subtle border-warning d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-warning text-dark">💬 NEGATIF BIASA</span>
              <div class="small text-muted">Kritik / Keluhan Wajar</div>
            </div>
            <div class="fs-5 fw-bold text-warning-emphasis"><?= $predOrdNegCount ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-2 border rounded bg-success-subtle border-success d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-success">🛡️ POSITIF</span>
              <div class="small text-muted">Apresiasi / Non-CB</div>
            </div>
            <div class="fs-5 fw-bold text-success"><?= $predPosCount ?></div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="p-2 border rounded bg-white border d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-secondary">ℹ️ NETRAL</span>
              <div class="small text-muted">Informasi / Non-CB</div>
            </div>
            <div class="fs-5 fw-bold text-secondary"><?= $predNeuCount ?></div>
          </div>
        </div>
      </div>

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
            <?php foreach ($processedPredictions as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p["comment"]) ?></td>
              <td><code class="small"><?= htmlspecialchars($p["preprocessing"] ?? "-") ?></code></td>
              <td class="text-center">
                <span class="badge <?= $p["badge_class"] ?> fs-6"><?= $p["label_title"] ?></span><br>
                <small class="<?= $p["is_cb"] ? 'text-danger fw-bold' : ($p['category'] === 'Negatif Biasa' ? 'text-warning-emphasis fw-semibold' : 'text-muted') ?>">
                  <?= htmlspecialchars($p["label_desc"]) ?>
                </small>
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

<?php

require_once __DIR__ . "/../app/autoload.php";
require_once __DIR__ . "/../app/config/Database.php";
require_once __DIR__ . "/../app/model/Dataset.php";
require_once __DIR__ . "/../app/model/Comment.php";
require_once __DIR__ . "/../app/model/MLModel.php";

$conn = Database::connect();

$datasetModel = new Dataset($conn);
$commentModel = new Comment($conn);
$mlModel = new MLModel($conn);

$totalDatasets = $datasetModel->countAll();
$totalComments = $commentModel->countAll();
$totalModels = $mlModel->countAll();
$latestModel = $mlModel->latest();
$distribution = $commentModel->sentimentDistribution();
$datasets = $datasetModel->getAllWithStats();

$positiveCount = 0;
$neutralCount = 0;

foreach ($distribution as $row) {
    if ($row["sentiment"] === "Positif") {
        $positiveCount = (int) $row["total"];
    } elseif ($row["sentiment"] === "Netral") {
        $neutralCount = (int) $row["total"];
    }
}

// Leksikon pendeteksi Cyberbullying sejati vs Negatif Biasa
$cyberbullyingLexicon = [
    "anjing", "anjir", "anjay", "anjrit", "asu", "babi", "bangsat", "bajingan",
    "kampret", "tai", "taek", "kontol", "memek", "ngentot", "peler", "pantek", "puki",
    "kntl", "mmk", "tolol", "goblok", "bego", "idiot", "bodoh", "dungu", "autis",
    "cacat", "bloon", "pekok", "sinting", "gila", "sarap", "miring", "gembel",
    "sampah", "najis", "busuk", "bangkai", "racun", "beracun", "mampus", "mati",
    "laknat", "celaka", "dajjal", "iblis", "setan", "jahanam", "sialan", "biadab",
    "haram", "maling", "rampok", "koruptor", "korupsi", "pencitraan", "monyet",
    "cebong", "bencong", "banci", "jelek bet"
];

$cyberbullyingCount = 0;
$ordinaryNegativeCount = 0;

$negResult = $conn->query("SELECT comment FROM comments WHERE sentiment = 'Negatif'");
if ($negResult) {
    while ($row = $negResult->fetch_assoc()) {
        $textLower = strtolower($row["comment"] ?? "");
        $isCb = false;
        foreach ($cyberbullyingLexicon as $term) {
            if (preg_match('/\b' . preg_quote($term, '/') . '\b/i', $textLower)) {
                $isCb = true;
                break;
            }
        }
        if ($isCb) {
            $cyberbullyingCount++;
        } else {
            $ordinaryNegativeCount++;
        }
    }
}

$chartLabels = ["Cyberbullying (Makian)", "Negatif Biasa (Kritik)", "Positif", "Netral"];
$chartValues = [$cyberbullyingCount, $ordinaryNegativeCount, $positiveCount, $neutralCount];
$chartColorList = ["#dc3545", "#fd7e14", "#198754", "#6c757d"];

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Dashboard - SentiGuard MBG</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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

  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold mb-1">Dashboard Analisis Sentimen &amp; Deteksi Cyberbullying MBG</h3>
      <p class="text-muted mb-0">Klasifikasi komentar TikTok program Makan Bergizi Gratis: Membedakan Cyberbullying vs Komentar Negatif Biasa</p>
    </div>
    <div>
      <a href="predict.php" data-spa="true" class="btn btn-primary me-2"><i class="bi bi-search me-1"></i> Prediksi Komentar</a>
      <a href="train.php" data-spa="true" class="btn btn-success"><i class="bi bi-cpu-fill me-1"></i> Training Model</a>
    </div>
  </div>

  <!-- Indikator Definisi: Cyberbullying vs Negatif Biasa -->
  <div class="alert alert-light border shadow-sm mb-4">
    <div class="row align-items-center g-3">
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-2 p-2 border rounded bg-danger-subtle">
          <span class="badge bg-danger fs-6 mt-1">🚨 CYBERBULLYING</span>
          <span class="small text-dark">Komentar negatif yang memuat <b>makian kasar, cemoohan, penghinaan martabat, atau ujaran kebencian</b> terhadap pihak/program MBG.</span>
        </div>
      </div>
      <div class="col-md-6">
        <div class="d-flex align-items-start gap-2 p-2 border rounded bg-warning-subtle">
          <span class="badge bg-warning text-dark fs-6 mt-1">💬 NEGATIF BIASA</span>
          <span class="small text-dark">Komentar negatif berupa <b>kritik konstruktif, keluhan porsi/rasa, atau komplain wajar</b> TANPA unsur makian/hinaan (Bukan Cyberbullying).</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistik Ringkasan 4 Kategori -->
  <div class="row g-3 mb-4">

    <div class="col-md-3">
      <div class="card shadow-sm border-0 text-center p-2">
        <div class="card-body">
          <div class="text-muted small fw-semibold">TOTAL KOMENTAR</div>
          <div class="fs-2 fw-bold text-dark"><?= $totalComments ?></div>
          <small class="text-muted"><?= $totalDatasets ?> file dataset</small>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card shadow-sm border-0 text-center p-2 border-start border-danger border-4">
        <div class="card-body">
          <div class="text-muted small fw-semibold">🚨 CYBERBULLYING</div>
          <div class="fs-2 fw-bold text-danger"><?= $cyberbullyingCount ?></div>
          <small class="text-muted">Makian / Hinaan Kasar</small>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card shadow-sm border-0 text-center p-2 border-start border-warning border-4">
        <div class="card-body">
          <div class="text-muted small fw-semibold">💬 NEGATIF BIASA</div>
          <div class="fs-2 fw-bold text-warning-emphasis"><?= $ordinaryNegativeCount ?></div>
          <small class="text-muted">Kritik / Keluhan Wajar (Aman)</small>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card shadow-sm border-0 text-center p-2 border-start border-success border-4">
        <div class="card-body">
          <div class="text-muted small fw-semibold">🛡️ POSITIF &amp; NETRAL</div>
          <div class="fs-2 fw-bold text-success"><?= $positiveCount + $neutralCount ?></div>
          <small class="text-muted">Apresiasi &amp; Informasi (Aman)</small>
        </div>
      </div>
    </div>

  </div>

  <div class="row g-3 mb-4">

    <div class="col-md-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white py-3 border-bottom">
          <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Distribusi Cyberbullying &amp; Sentimen</h6>
        </div>
        <div class="card-body d-flex flex-column justify-content-center">
          <?php if (empty($chartValues) || array_sum($chartValues) === 0): ?>
            <p class="text-muted text-center my-4">Belum ada komentar berlabel. Upload dataset atau jalankan scraping terlebih dahulu.</p>
          <?php else: ?>
            <div style="position: relative; height: 240px;">
              <canvas id="sentimentChart"></canvas>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h6 class="mb-0 fw-bold"><i class="bi bi-folder-fill me-2 text-primary"></i>Daftar Dataset Terkelola</h6>
          <a href="upload.php" data-spa="true" class="btn btn-sm btn-outline-primary">+ Upload CSV Baru</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($datasets)): ?>
            <p class="text-muted p-3 mb-0">Belum ada dataset. <a href="upload.php" data-spa="true">Upload dataset CSV</a> atau <a href="scrape.php" data-spa="true">scraping komentar TikTok</a> untuk memulai.</p>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th class="ps-3">Nama File</th>
                  <th>Total</th>
                  <th>Berlabel</th>
                  <th>Status</th>
                  <th>Akurasi</th>
                  <th class="text-end pe-3">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($datasets as $d): ?>
                <tr>
                  <td class="ps-3 fw-semibold"><?= htmlspecialchars($d["filename"]) ?></td>
                  <td><?= (int) $d["total_comments"] ?></td>
                  <td><?= (int) $d["labeled_count"] ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($d["status"]) ?></span></td>
                  <td><?= $d["latest_accuracy"] !== null ? round($d["latest_accuracy"] * 100, 2) . "%" : "-" ?></td>
                  <td class="text-end pe-3">
                    <a href="dataset.php?id=<?= (int) $d["id"] ?>" data-spa="true" class="btn btn-sm btn-outline-primary py-0">Kelola</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <div class="row g-3">
    <div class="col-md-4">
      <a href="scrape.php" data-spa="true" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 p-2">
          <div class="card-body">
            <h5 class="fw-bold text-dark"><i class="bi bi-tiktok text-danger me-2"></i>Scraping TikTok</h5>
            <p class="text-muted small mb-0">Pengambilan komentar publik video TikTok otomatis menggunakan Selenium sesuai Bab III.</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="train.php" data-spa="true" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 p-2">
          <div class="card-body">
            <h5 class="fw-bold text-dark"><i class="bi bi-cpu-fill text-success me-2"></i>Training &amp; Evaluasi</h5>
            <p class="text-muted small mb-0">Latih Naive Bayes + TF-IDF, uraian matematis Confusion Matrix &amp; analisis kesalahan.</p>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="predict.php" data-spa="true" class="text-decoration-none">
        <div class="card shadow-sm border-0 h-100 p-2">
          <div class="card-body">
            <h5 class="fw-bold text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Prediksi Komentar</h5>
            <p class="text-muted small mb-0">Uji komentar baru: Membedakan Cyberbullying vs Komentar Negatif Biasa.</p>
          </div>
        </div>
      </a>
    </div>
  </div>

</div>

<?php if (!empty($chartValues) && array_sum($chartValues) > 0): ?>
<script>
(function() {
    let canvas = document.getElementById("sentimentChart");
    if (!canvas) return;
    
    if (window.mySentimentChart && typeof window.mySentimentChart.destroy === "function") {
        window.mySentimentChart.destroy();
    }
    
    window.mySentimentChart = new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                data: <?= json_encode($chartValues) ?>,
                backgroundColor: <?= json_encode($chartColorList) ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom" }
            }
        }
    });
})();
</script>
<?php endif; ?>

<?php include "partials/foot.php"; ?>
</body>
</html>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Training Model &amp; Evaluasi - SentiGuard MBG</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png">
  <link rel="manifest" href="favicon_io/site.webmanifest">
  <meta name="msapplication-TileColor" content="#0d6efd">
  <meta name="theme-color" content="#0d6efd">
  <style>
    .metric-card {
      border-radius: 10px;
      border: 1px solid rgba(0,0,0,0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .cm-table th, .cm-table td {
      vertical-align: middle;
      text-align: center;
    }
    .cm-diagonal {
      background-color: #d1e7dd !important;
      font-weight: bold;
      color: #0f5132;
    }
    .cm-off-diagonal {
      background-color: #f8d7da !important;
      color: #842029;
    }
    .formula-box {
      background-color: #f8f9fa;
      border-left: 4px solid #0d6efd;
      padding: 12px 16px;
      font-family: Consolas, "Courier New", monospace;
      font-size: 0.95rem;
      border-radius: 0 8px 8px 0;
    }
  </style>
</head>

<body>

<?php include "partials/nav.php"; ?>

<div class="container mb-5">

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
      <div>
        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-cpu-fill text-primary me-2"></i>Pelatihan Model &amp; Evaluasi Mendalam</h4>
        <small class="text-muted">Multinomial Naive Bayes + Pembobotan TF-IDF untuk Deteksi Cyberbullying MBG</small>
      </div>
    </div>

    <div class="card-body p-4">
      <div class="mb-3">
        <label class="form-label fw-semibold">
          Pilih File Dataset CSV (Kolom wajib: <code>Username</code>, <code>Komentar</code>, <code>Sentimen</code>):
        </label>
        <div class="input-group">
          <input type="file" class="form-control" id="csv" accept=".csv">
          <button class="btn btn-success px-4 fw-semibold" id="btnTrain">
            <i class="bi bi-play-circle-fill me-1"></i> Mulai Latih Model
          </button>
        </div>
        <div class="form-text mt-1 text-muted">
          Dataset akan diproses melalui pipeline text preprocessing (Case Folding, Cleansing, Stemming Sastrawi), split 80% data latih dan 20% data uji, dihitung Confusion Matrix, proses evaluasi matematis, dan analisis data misklasifikasi.
        </div>
      </div>

      <div id="loading" class="alert alert-info mt-3" style="display:none">
        <div class="d-flex align-items-center gap-2">
          <div class="spinner-border spinner-border-sm text-primary"></div>
          <div>
            <b>Sedang melatih model...</b><br>
            <span class="small">Menjalankan preprocessing (stemming Sastrawi membutuhkan beberapa detik), pembobotan TF-IDF, pengujian holdout 20%, 5-Fold Cross Validation, dan diagnosa kesalahan klasifikasi...</span>
          </div>
        </div>
      </div>

      <div id="result" class="mt-4"></div>
    </div>
  </div>

</div>

<script>
function renderConfusionMatrix(cm) {
    if (!cm || !cm.labels) return "";

    let html = "<div class='table-responsive'><table class='table table-bordered cm-table shadow-sm'>";
    html += "<thead class='table-dark'><tr><th class='text-start'>Aktual (Ground Truth) \\ Prediksi Model</th>";
    
    cm.labels.forEach(function (label) {
        let cbText = (label === "Negatif") ? "<br><small class='badge bg-danger'>Cyberbullying</small>" : "<br><small class='badge bg-success'>Non-CB</small>";
        html += "<th>" + label + cbText + "</th>";
    });
    html += "<th class='bg-secondary text-white'>Total Aktual</th>";
    html += "</tr></thead><tbody>";

    let colSums = new Array(cm.labels.length).fill(0);

    cm.matrix.forEach(function (row, i) {
        let rowSum = row.reduce((a, b) => a + b, 0);
        let cbText = (cm.labels[i] === "Negatif") ? " <span class='badge bg-danger'>Cyberbullying</span>" : " <span class='badge bg-success'>Non-CB</span>";
        html += "<tr><th class='table-light text-start'>" + cm.labels[i] + cbText + "</th>";

        row.forEach(function (val, j) {
            colSums[j] += val;
            let cellClass = (i === j) ? "cm-diagonal" : (val > 0 ? "cm-off-diagonal" : "");
            let checkIcon = (i === j) ? " <i class='bi bi-check-circle-fill small'></i>" : "";
            html += "<td class='" + cellClass + "'>" + val + checkIcon + "</td>";
        });

        html += "<td class='fw-bold table-light'>" + rowSum + "</td>";
        html += "</tr>";
    });

    html += "<tr class='table-light fw-bold'><th class='text-start'>Total Prediksi</th>";
    colSums.forEach(function (sum) {
        html += "<td>" + sum + "</td>";
    });
    let totalAll = colSums.reduce((a, b) => a + b, 0);
    html += "<td class='table-primary'>" + totalAll + "</td></tr>";

    html += "</tbody></table></div>";
    html += "<div class='d-flex gap-3 small text-muted mb-2'>";
    html += "<div><span class='badge bg-success-subtle text-success border border-success me-1'>■</span> <b>Diagonal Hijau:</b> Prediksi Tepat (True)</div>";
    html += "<div><span class='badge bg-danger-subtle text-danger border border-danger me-1'>■</span> <b>Kotak Merah:</b> Salah Klasifikasi (Misclassification)</div>";
    html += "</div>";

    return html;
}

$("#btnTrain").click(function () {
    let file = $("#csv")[0].files[0];

    if (file == null) {
        alert("Silakan pilih file dataset CSV terlebih dahulu.");
        return;
    }

    let formData = new FormData();
    formData.append("csv", file);

    let $btn = $(this);
    $btn.prop("disabled", true).html("<i class='bi bi-hourglass-split me-1'></i> Memproses Training...");
    $("#loading").show();
    $("#result").html("");

    $.ajax({
        url: "../api/train.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function (res) {
            $btn.prop("disabled", false).html("<i class='bi bi-play-circle-fill me-1'></i> Mulai Latih Model");
            $("#loading").hide();

            if (res.status !== "success") {
                $("#result").html("<div class='alert alert-danger'>" + (res.message || "Proses training gagal.") + "</div>");
                return;
            }

            let ev = res.evaluation || {};
            let step = res.step_by_step_evaluation || {};
            let err = res.error_analysis || {};
            let cv = res.cross_validation || {};
            let accCalc = step.accuracy_calc || {};
            let classEvals = step.class_evaluations || [];

            let html = "";

            // ==========================================
            // 1. RINGKASAN DATASET & STATUS CYBERBULLYING
            // ==========================================
            html += "<div class='alert alert-success border-success shadow-sm mb-4'>";
            html += "<div class='d-flex justify-content-between align-items-center mb-2'>";
            html += "<h5 class='alert-heading fw-bold mb-0'><i class='bi bi-check-circle-fill me-2'></i>Model Naive Bayes Berhasil Dilatih!</h5>";
            html += "<span class='badge bg-success fs-6'>Holdout Split 80:20</span>";
            html += "</div>";
            html += "<hr class='my-2'>";
            html += "<div class='row g-2 small'>";
            html += "<div class='col-md-3'>Total Data Bersih: <b>" + res.rows + " komentar</b></div>";
            html += "<div class='col-md-3'>Data Latih (80%): <b>" + res.train_rows + " data</b></div>";
            html += "<div class='col-md-3'>Data Uji (20%): <b>" + res.test_rows + " data</b></div>";
            html += "<div class='col-md-3'>Jumlah Kelas: <b>" + res.classes.length + " (" + res.classes.join(", ") + ")</b></div>";
            html += "</div>";
            html += "<div class='mt-2 pt-2 border-top small text-muted'>";
            html += "<b>Kategori Deteksi:</b> Sentimen <span class='badge bg-danger'>Negatif</span> = <b>Cyberbullying</b> | Sentimen <span class='badge bg-success'>Positif</span> &amp; <span class='badge bg-secondary'>Netral</span> = <b>Non-Cyberbullying</b>.";
            html += "</div>";
            html += "</div>";

            // ==========================================
            // 2. KARTU METRIK UTAMA
            // ==========================================
            html += "<div class='row g-3 mb-4'>";
            html += "  <div class='col-md-3'>";
            html += "    <div class='card metric-card bg-light text-center p-3'>";
            html += "      <div class='text-muted small fw-semibold'>AKURASI MODEL</div>";
            html += "      <div class='fs-2 fw-bold text-primary'>" + (ev.accuracy * 100).toFixed(2) + "%</div>";
            html += "      <small class='text-muted'>(" + step.total_correct + " dari " + step.total_test + " benar)</small>";
            html += "    </div>";
            html += "  </div>";
            html += "  <div class='col-md-3'>";
            html += "    <div class='card metric-card bg-light text-center p-3'>";
            html += "      <div class='text-muted small fw-semibold'>PRECISION (Weighted)</div>";
            html += "      <div class='fs-2 fw-bold text-success'>" + (ev.precision * 100).toFixed(2) + "%</div>";
            html += "      <small class='text-muted'>Skor desimal: " + ev.precision + "</small>";
            html += "    </div>";
            html += "  </div>";
            html += "  <div class='col-md-3'>";
            html += "    <div class='card metric-card bg-light text-center p-3'>";
            html += "      <div class='text-muted small fw-semibold'>RECALL (Weighted)</div>";
            html += "      <div class='fs-2 fw-bold text-info'>" + (ev.recall * 100).toFixed(2) + "%</div>";
            html += "      <small class='text-muted'>Skor desimal: " + ev.recall + "</small>";
            html += "    </div>";
            html += "  </div>";
            html += "  <div class='col-md-3'>";
            html += "    <div class='card metric-card bg-light text-center p-3'>";
            html += "      <div class='text-muted small fw-semibold'>F1-SCORE (Weighted)</div>";
            html += "      <div class='fs-2 fw-bold text-warning'>" + (ev.f1_score * 100).toFixed(2) + "%</div>";
            html += "      <small class='text-muted'>Skor desimal: " + ev.f1_score + "</small>";
            html += "    </div>";
            html += "  </div>";
            html += "</div>";

            // Nav Tabs untuk Uraian Evaluasi vs Analisis Kesalahan
            html += "<ul class='nav nav-tabs mb-3' id='evalTabs' role='tablist'>";
            html += "  <li class='nav-item' role='presentation'>";
            html += "    <button class='nav-link active fw-bold' id='tab-eval' data-bs-toggle='tab' data-bs-target='#content-eval' type='button' role='tab'>";
            html += "      <i class='bi bi-calculator-fill me-1'></i> 1. Uraian Proses Evaluasi (Rumus &amp; Perhitungan)";
            html += "    </button>";
            html += "  </li>";
            html += "  <li class='nav-item' role='presentation'>";
            html += "    <button class='nav-link fw-bold text-danger' id='tab-error' data-bs-toggle='tab' data-bs-target='#content-error' type='button' role='tab'>";
            html += "      <i class='bi bi-exclamation-triangle-fill me-1'></i> 2. Analisis Data yang Salah Klasifikasi (" + (err.total_misclassified || 0) + " Data)";
            html += "    </button>";
            html += "  </li>";
            html += "</ul>";

            html += "<div class='tab-content' id='evalTabsContent'>";

            // ==========================================
            // TAB 1: URAIAN PROSES EVALUASI LENGKAP
            // ==========================================
            html += "<div class='tab-pane fade show active' id='content-eval' role='tabpanel'>";

            // Langkah 1: Confusion Matrix
            html += "<div class='card shadow-sm mb-4'>";
            html += "  <div class='card-header bg-white py-3'>";
            html += "    <h5 class='mb-0 fw-bold'><i class='bi bi-table me-2 text-primary'></i>Langkah 1: Tabulasi Confusion Matrix</h5>";
            html += "    <small class='text-muted'>Menghitung frekuensi prediksi benar (diagonal) vs salah pada data uji (" + step.total_test + " baris holdout 20%)</small>";
            html += "  </div>";
            html += "  <div class='card-body'>";
            html += renderConfusionMatrix(ev.confusion_matrix);
            html += "  </div>";
            html += "</div>";

            // Langkah 2: Uraian Perhitungan Akurasi
            html += "<div class='card shadow-sm mb-4'>";
            html += "  <div class='card-header bg-white py-3'>";
            html += "    <h5 class='mb-0 fw-bold'><i class='bi bi-bullseye me-2 text-primary'></i>Langkah 2: Uraian Rumus &amp; Perhitungan Akurasi</h5>";
            html += "  </div>";
            html += "  <div class='card-body'>";
            html += "    <p class='text-muted mb-2'>Akurasi mengukur rasio seluruh prediksi yang tepat terhadap keseluruhan sampel data uji:</p>";
            html += "    <div class='formula-box mb-3'>";
            html += "      <b>Rumus Umum:</b><br>";
            html += "      Akurasi = (Total Prediksi Benar) / (Total Data Uji)<br>";
            html += "      Akurasi = (TP + TN) / (TP + TN + FP + FN) = &Sigma;(Diagonal Utama Confusion Matrix) / N_uji<br><br>";
            html += "      <b>Substitusi Angka Riil:</b><br>";
            html += "      Akurasi = (" + step.total_correct + ") / (" + step.total_test + ")<br>";
            html += "      Akurasi = <b>" + (accCalc.result_decimal || 0) + "</b> = <b>" + (accCalc.result_pct || 0) + "%</b>";
            html += "    </div>";
            html += "    <div class='alert alert-info py-2 small mb-0'>";
            html += "      <i class='bi bi-info-circle-fill me-1'></i> <b>Penjelasan:</b> " + (accCalc.explanation || "") + "";
            html += "    </div>";
            html += "  </div>";
            html += "</div>";

            // Langkah 3: Rincian TP, FP, FN, TN, Precision, Recall, F1 Per-Kelas
            html += "<div class='card shadow-sm mb-4'>";
            html += "  <div class='card-header bg-white py-3'>";
            html += "    <h5 class='mb-0 fw-bold'><i class='bi bi-card-checklist me-2 text-primary'></i>Langkah 3: Rincian Perhitungan Per-Kelas (TP, FP, FN, TN)</h5>";
            html += "    <small class='text-muted'>Menjabarkan rumus Precision, Recall, dan F1-Score beserta substitusi nilai dari Confusion Matrix</small>";
            html += "  </div>";
            html += "  <div class='card-body'>";

            classEvals.forEach(function (ce) {
                let isCbClass = ce.is_cyberbullying;
                let badgeClass = isCbClass ? "bg-danger" : "bg-success";
                let cbStatusTitle = isCbClass ? "🚨 KELAS CYBERBULLYING (Sentimen Negatif)" : "🛡️ KELAS NON-CYBERBULLYING (Sentimen " + ce.label + ")";

                html += "<div class='card border mb-3'>";
                html += "  <div class='card-header d-flex justify-content-between align-items-center bg-light'>";
                html += "    <div><span class='badge " + badgeClass + " fs-6 me-2'>" + ce.label + "</span> <b>" + cbStatusTitle + "</b></div>";
                html += "    <div class='small text-muted'>Total Data Aktual (Support): <b>" + ce.support + "</b> komentar</div>";
                html += "  </div>";
                html += "  <div class='card-body'>";

                // 4 Kotak Parameter
                html += "    <div class='row g-2 text-center mb-3'>";
                html += "      <div class='col-3'><div class='p-2 border rounded bg-success-subtle'><b>TP (True Pos): " + ce.tp + "</b><br><small class='text-muted'>Prediksi Benar " + ce.label + "</small></div></div>";
                html += "      <div class='col-3'><div class='p-2 border rounded bg-danger-subtle'><b>FP (False Pos): " + ce.fp + "</b><br><small class='text-muted'>Bukan " + ce.label + " tapi dituduh " + ce.label + "</small></div></div>";
                html += "      <div class='col-3'><div class='p-2 border rounded bg-warning-subtle'><b>FN (False Neg): " + ce.fn + "</b><br><small class='text-muted'>" + ce.label + " tapi lolos/diprediksi lain</small></div></div>";
                html += "      <div class='col-3'><div class='p-2 border rounded bg-light'><b>TN (True Neg): " + ce.tn + "</b><br><small class='text-muted'>Bukan " + ce.label + " tepat ditebak bukan</small></div></div>";
                html += "    </div>";

                // Uraian Rumus Per Metrik
                html += "    <div class='row g-2 small'>";
                html += "      <div class='col-md-4'>";
                html += "        <div class='p-2 border rounded bg-white h-100'>";
                html += "          <b>Precision:</b><br>";
                html += "          <code>" + ce.formulas.precision_text + "</code><br>";
                html += "          <span class='text-muted'>Ketepatan saat model memprediksi kelas " + ce.label + ".</span>";
                html += "        </div>";
                html += "      </div>";
                html += "      <div class='col-md-4'>";
                html += "        <div class='p-2 border rounded bg-white h-100'>";
                html += "          <b>Recall:</b><br>";
                html += "          <code>" + ce.formulas.recall_text + "</code><br>";
                html += "          <span class='text-muted'>Kemampuan model menjaring seluruh data " + ce.label + " yang ada.</span>";
                html += "        </div>";
                html += "      </div>";
                html += "      <div class='col-md-4'>";
                html += "        <div class='p-2 border rounded bg-white h-100'>";
                html += "          <b>F1-Score:</b><br>";
                html += "          <code>" + ce.formulas.f1_text + "</code><br>";
                html += "          <span class='text-muted'>Rata-rata harmonik Precision &amp; Recall.</span>";
                html += "        </div>";
                html += "      </div>";
                html += "    </div>";

                html += "  </div>";
                html += "</div>";
            });

            // Weighted Averages
            let wa = step.weighted_averages || {};
            html += "<div class='p-3 bg-light rounded border'>";
            html += "  <h6 class='fw-bold mb-2'>Uraian Rata-Rata Berbobot (Weighted Average) Keseluruhan:</h6>";
            html += "  <div class='small font-monospace text-muted'>";
            html += "    " + (wa.precision_formula || "") + "<br>";
            html += "    " + (wa.recall_formula || "") + "<br>";
            html += "    " + (wa.f1_formula || "") + "<br>";
            html += "  </div>";
            html += "</div>";

            html += "  </div>";
            html += "</div>";

            // Langkah 4: Cross Validation
            html += "<div class='card shadow-sm mb-4'>";
            html += "  <div class='card-header bg-white py-3'>";
            html += "    <h5 class='mb-0 fw-bold'><i class='bi bi-arrow-repeat me-2 text-primary'></i>Langkah 4: Evaluasi 5-Fold Stratified Cross Validation</h5>";
            html += "  </div>";
            html += "  <div class='card-body'>";
            if (cv.scores && cv.scores.length > 0) {
                html += "<p class='text-muted small mb-2'>Validasi silang membagi dataset menjadi " + cv.n_splits + " fold berimbang untuk menguji kestabilan model:</p>";
                html += "<div class='table-responsive mb-3'><table class='table table-bordered table-sm text-center'>";
                html += "<thead class='table-light'><tr>";
                cv.scores.forEach(function (s, idx) {
                    html += "<th>Fold " + (idx + 1) + "</th>";
                });
                html += "<th class='table-primary'>Rata-Rata (Mean CV)</th></tr></thead><tbody><tr>";
                cv.scores.forEach(function (s) {
                    html += "<td>" + (s * 100).toFixed(2) + "%</td>";
                });
                html += "<td class='fw-bold text-primary'>" + (cv.mean_accuracy * 100).toFixed(2) + "%</td>";
                html += "</tr></tbody></table></div>";

                if (cv.formula_text) {
                    html += "<div class='formula-box small'><b>Perhitungan Rata-Rata:</b><br>" + cv.formula_text + "</div>";
                }
            } else {
                html += "<span class='text-muted'>" + (cv.note || "5-Fold CV tidak dapat dijalankan.") + "</span>";
            }
            html += "  </div>";
            html += "</div>";

            html += "</div>"; // End Tab 1

            // ==========================================
            // TAB 2: ANALISIS KESALAHAN KLASIFIKASI (ERROR ANALYSIS)
            // ==========================================
            html += "<div class='tab-pane fade' id='content-error' role='tabpanel'>";

            html += "<div class='card shadow-sm mb-4'>";
            html += "  <div class='card-header bg-white py-3 d-flex justify-content-between align-items-center'>";
            html += "    <div>";
            html += "      <h5 class='mb-0 fw-bold text-danger'><i class='bi bi-search me-2'></i>Analisis Data yang Mengalami Kesalahan Klasifikasi</h5>";
            html += "      <small class='text-muted'>Investigasi sampel data uji di mana label aktual &ne; hasil prediksi model Naive Bayes</small>";
            html += "    </div>";
            html += "    <div>";
            html += "      <span class='badge bg-danger fs-6'>" + (err.total_misclassified || 0) + " Kesalahan</span>";
            html += "      <span class='badge bg-secondary fs-6'>Error Rate: " + (err.error_rate_pct || 0) + "%</span>";
            html += "    </div>";
            html += "  </div>";
            html += "  <div class='card-body'>";

            // Rekap Error Cyberbullying Khusus
            let cbImpact = err.cyberbullying_impact || {};
            html += "    <div class='row g-3 mb-4'>";
            html += "      <div class='col-md-6'>";
            html += "        <div class='p-3 border rounded border-warning bg-warning-subtle'>";
            html += "          <h6 class='fw-bold text-dark mb-1'><i class='bi bi-shield-slash-fill me-1'></i>Cyberbullying Lolos Deteksi (False Negative)</h6>";
            html += "          <div class='fs-3 fw-bold text-warning-emphasis'>" + (cbImpact.missed_cyberbullying_count || 0) + " Komentar</div>";
            html += "          <small class='text-muted'>" + (cbImpact.missed_cyberbullying_desc || "") + "</small>";
            html += "        </div>";
            html += "      </div>";
            html += "      <div class='col-md-6'>";
            html += "        <div class='p-3 border rounded border-danger bg-danger-subtle'>";
            html += "          <h6 class='fw-bold text-dark mb-1'><i class='bi bi-exclamation-triangle-fill me-1'></i>Keliru Dituduh Cyberbullying (False Positive)</h6>";
            html += "          <div class='fs-3 fw-bold text-danger'>" + (cbImpact.false_cyberbullying_count || 0) + " Komentar</div>";
            html += "          <small class='text-muted'>" + (cbImpact.false_cyberbullying_desc || "") + "</small>";
            html += "        </div>";
            html += "      </div>";
            html += "    </div>";

            // Distribusi Tipe Kesalahan
            let errTypes = err.error_type_counts || {};
            html += "    <div class='mb-3'>";
            html += "      <span class='fw-semibold small text-muted me-2'>Rincian Pasangan Kesalahan:</span>";
            Object.keys(errTypes).forEach(function (k) {
                html += "<span class='badge bg-light text-dark border me-2 py-1 px-2 mb-1'>" + k + ": <b>" + errTypes[k] + " kasus</b></span>";
            });
            html += "    </div>";

            // Tabel Rincian Misklasifikasi
            let samples = err.misclassified_samples || [];
            if (samples.length === 0) {
                html += "    <div class='alert alert-success'><i class='bi bi-check-circle-fill me-1'></i> Luar biasa! Tidak ada kesalahan klasifikasi pada data uji (Akurasi 100%).</div>";
            } else {
                html += "    <div class='table-responsive'>";
                html += "      <table class='table table-bordered table-hover align-middle'>";
                html += "        <thead class='table-light'>";
                html += "          <tr>";
                html += "            <th style='width: 40px;'>#</th>";
                html += "            <th style='width: 30%;'>Teks Komentar Asli &amp; Preprocessing</th>";
                html += "            <th style='width: 15%;'>Label Aktual (Ground Truth)</th>";
                html += "            <th style='width: 15%;'>Prediksi Model (Salah)</th>";
                html += "            <th style='width: 12%;'>Keyakinan</th>";
                html += "            <th>Faktor / Analisis Penyebab Kesalahan</th>";
                html += "          </tr>";
                html += "        </thead>";
                html += "        <tbody>";

                samples.forEach(function (s) {
                    let actBadge = (s.actual_sentiment === "Negatif") ? "bg-danger" : (s.actual_sentiment === "Positif" ? "bg-success" : "bg-secondary");
                    let prdBadge = (s.predicted_sentiment === "Negatif") ? "bg-danger" : (s.predicted_sentiment === "Positif" ? "bg-success" : "bg-secondary");

                    html += "<tr>";
                    html += "  <td class='text-center fw-bold'>" + s.index + "</td>";
                    html += "  <td>";
                    html += "    <div class='fw-semibold text-dark mb-1'>" + s.raw_comment + "</div>";
                    html += "    <small class='text-muted d-block'><b>Preprocessing:</b> <code>" + (s.clean_comment || "-") + "</code></small>";
                    html += "    <small class='text-muted'>User: @" + s.username + "</small>";
                    html += "  </td>";
                    html += "  <td>";
                    html += "    <span class='badge " + actBadge + " fs-6'>" + s.actual_sentiment + "</span><br>";
                    html += "    <small class='fw-semibold text-muted'>" + s.actual_cyberbullying + "</small>";
                    html += "  </td>";
                    html += "  <td>";
                    html += "    <span class='badge " + prdBadge + " fs-6'>" + s.predicted_sentiment + "</span><br>";
                    html += "    <small class='fw-semibold text-danger'>" + s.predicted_cyberbullying + "</small>";
                    html += "  </td>";
                    html += "  <td>";
                    html += "    <span class='fw-bold text-dark'>" + s.confidence + "%</span>";
                    html += "  </td>";
                    html += "  <td>";
                    html += "    <div class='small text-dark p-2 bg-light rounded border'>" + s.reason_analysis + "</div>";
                    html += "  </td>";
                    html += "</tr>";
                });

                html += "        </tbody>";
                html += "      </table>";
                html += "    </div>";
            }

            // Kesimpulan & Rekomendasi
            html += "    <div class='card bg-light border-0 mt-3'>";
            html += "      <div class='card-body'>";
            html += "        <h6 class='fw-bold text-dark mb-2'><i class='bi bi-lightbulb-fill text-warning me-1'></i>Insight &amp; Rekomendasi Akademis Error Analysis:</h6>";
            html += "        <ul class='small text-muted mb-0 ps-3'>";
            html += "          <li><b>Sarkasme dan Sindiran Halus:</b> Kesalahan klasifikasi cyberbullying sering terjadi ketika warganet menggunakan ungkapan ironi atau perbandingan tanpa kata-kata kasar eksplisit.</li>";
            html += "          <li><b>Keterbatasan Kamus TF-IDF:</b> Kata-kata slang/singkatan TikTok yang tidak muncul pada data latih (OOV) menerima bobot 0, menyebabkan model mengandalkan kata netral yang tersisa.</li>";
            html += "          <li><b>Saran Pengembangan:</b> Menambahkan leksikon kata slang khusus TikTok program MBG pada tahap normalisasi, atau memperluas data latih pada kelas yang sering tertukar.</li>";
            html += "        </ul>";
            html += "      </div>";
            html += "    </div>";

            html += "  </div>";
            html += "</div>";

            html += "</div>"; // End Tab 2
            html += "</div>"; // End tab-content

            if (res.db_warning) {
                html += "<div class='alert alert-warning mt-3'><i class='bi bi-exclamation-circle-fill me-1'></i> " + res.db_warning + "</div>";
            }

            $("#result").html(html);

            // Scroll ke hasil
            $('html, body').animate({
                scrollTop: $("#result").offset().top - 80
            }, 400);

        },
        error: function () {
            $btn.prop("disabled", false).html("<i class='bi bi-play-circle-fill me-1'></i> Mulai Latih Model");
            $("#loading").hide();
            $("#result").html("<div class='alert alert-danger'>Terjadi kesalahan saat menghubungi server. Pastikan service Python Flask sedang berjalan.</div>");
        }
    });
});
</script>

<?php include "partials/foot.php"; ?>
</body>
</html>

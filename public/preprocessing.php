<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<title>Preprocessing</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<script
src="https://code.jquery.com/jquery-3.7.1.min.js">
</script>
    
    <link rel="apple-touch-icon" sizes="180x180" href="favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="favicon_io/favicon-16x16.png">
  <link rel="manifest" href="favicon_io/site.webmanifest">
  <meta name="msapplication-TileColor" content="#0d6efd">
  <meta name="theme-color" content="#0d6efd">

</head>

<body>

<?php include "partials/nav.php"; ?>

<div class="container mt-5">

<div class="card shadow">

<div class="card-header">

<h3>MBG - Text Preprocessing</h3>

</div>

<div class="card-body">

<div class="mb-3">

<label class="form-label">

Masukkan Komentar

</label>

<textarea

class="form-control"

id="text"

rows="4"

placeholder="Masukkan komentar..."></textarea>

</div>

<button

class="btn btn-primary"

id="btnProcess">

Proses

</button>

<hr>

<table class="table table-bordered">

<thead>

<tr>

<th width="200">Tahap</th>

<th>Hasil</th>

</tr>

</thead>

<tbody>

<tr>

<td>Original</td>

<td id="original"></td>

</tr>

<tr>

<td>Case Folding</td>

<td id="case"></td>

</tr>

<tr>

<td>Cleaning</td>

<td id="clean"></td>

</tr>

<tr>

<td>Normalisasi</td>

<td id="normalization"></td>

</tr>

<tr>

<td>Tokenizing</td>

<td id="token"></td>

</tr>

<tr>

<td>Stopword Removal</td>

<td id="stop"></td>

</tr>


<tr>

<td>Stemming</td>
<td id="stem"></td>

</tr>


</tbody>

</table>

</div>

</div>

</div>

<script>

$("#btnProcess").click(function(){

    $.ajax({

       url:"../api/preprocess.php",

        type:"POST",

        contentType:"application/json",

        data:JSON.stringify({

            text:$("#text").val()

        }),

        success:function(res){

            $("#original").text(res.original);

            $("#case").text(res.case_folding);

            $("#clean").text(res.cleaning);

            $("#token").text(

                JSON.stringify(res.tokenizing)

            );
            $("#normalization").text(res.normalization);

            $("#stop").text(res.stopword);

            $("#stem").text(res.stemming);

        }

    });

});

</script>

<?php include "partials/foot.php"; ?>
</body>

</html>
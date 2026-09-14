<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>TF-IDF MBG</title>

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

<div class="container mt-5">

<div class="card shadow">

<div class="card-header">

<h3>TF-IDF Calculator</h3>

</div>

<div class="card-body">

<label class="form-label">

Masukkan Dokumen (1 komentar setiap baris)

</label>

<textarea
class="form-control"
id="documents"
rows="10"></textarea>

<br>

<button
class="btn btn-primary"
id="btnProcess">

Hitung TF-IDF

</button>

<hr>

<h5>Terms</h5>

<div id="terms"></div>

<hr>

<h5>Matriks TF-IDF</h5>

<div id="matrix"></div>

</div>

</div>

</div>

<script>

$("#btnProcess").click(function(){

    let docs=$("#documents").val().trim().split("\n");

    $.ajax({

        url:"../api/tfidf.php",

        type:"POST",

        contentType:"application/json",

        data:JSON.stringify({

            documents:docs

        }),

        success:function(res){

            if(res.status!="success"){

                alert(res.message);

                return;

            }

            $("#terms").html(res.terms.join(" | "));

            let html="";

            html+="<table class='table table-bordered'>";

            html+="<thead>";

            html+="<tr>";

            html+="<th>Dokumen</th>";

            res.terms.forEach(function(term){

                html+="<th>"+term+"</th>";

            });

            html+="</tr>";

            html+="</thead>";

            html+="<tbody>";

            res.matrix.forEach(function(row,index){

                html+="<tr>";

                html+="<td>D"+(index+1)+"</td>";

                row.forEach(function(value){

                    html+="<td>"+parseFloat(value).toFixed(3)+"</td>";

                });

                html+="</tr>";

            });

            html+="</tbody>";

            html+="</table>";

            $("#matrix").html(html);

        }

    });

});

</script>

<?php include "partials/foot.php"; ?>
</body>

</html>
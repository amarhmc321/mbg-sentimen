from flask import Flask, jsonify, request
from flask_cors import CORS
import pandas as pd
from services.vectorizer import generate_tfidf
from services.trainer import train_model
from services.predictor import predict_sentiment

# Import fungsi preprocessing
from services.preprocessing import (
    case_folding,
    cleaning,
    normalize_repeated_characters,
    tokenizing,
    stopword_removal,
    stemming
)

app = Flask(__name__)
CORS(app)

# CORS(app, resources={
#     r"/api/*": {
#         "origins": "*"
#     }
# })

# ==========================================
# HOME
# ==========================================
@app.route("/")
def home():
    return "Python Flask MBG Berhasil"


# ==========================================
# TEST API
# ==========================================
@app.route("/api/test")
def test():

    return jsonify({
        "status": "success",
        "message": "Halo dari Python",
        "language": "Python",
        "version": "3.14"
    })


# ==========================================
# UPLOAD CSV
# ==========================================
@app.route("/api/upload", methods=["POST"])
def upload():

    if "csv" not in request.files:
        return jsonify({
            "status": "error",
            "message": "File tidak ditemukan"
        }), 400

    file = request.files["csv"]

    df = pd.read_csv(file)

    return jsonify({
        "status": "success",
        "rows": len(df),
        "columns": list(df.columns),
        "data": df.to_dict(orient="records")
    })


# ==========================================
# PREPROCESSING
# ==========================================
@app.route("/api/preprocess", methods=["POST"])
def preprocess():

    data = request.get_json()

    if not data:
        return jsonify({
            "status": "error",
            "message": "Data JSON tidak ditemukan"
        }), 400

    text = data.get("text", "")

    # Tahap 1
    step1 = case_folding(text)

    # Tahap 2
    step2 = cleaning(step1)

    # Tahap 3
    step3 = normalize_repeated_characters(step2)

    # Tahap 4
    tokens = tokenizing(step3)

    # Tahap 5
    step4 = stopword_removal(step3)

    # Tahap 6
    step5 = stemming(step4)

    return jsonify({
        "status": "success",
        "original": text,
        "case_folding": step1,
        "cleaning": step2,
        "normalization": step3,
        "tokenizing": tokens,
        "stopword": step4,
        "stemming": step5
    })


@app.route("/api/tfidf", methods=["POST"])
def tfidf():

    data = request.get_json()

    if not data or "documents" not in data:

        return jsonify({
            "status": "error",
            "message": "Data dokumen tidak ditemukan"
        }), 400

    result = generate_tfidf(data["documents"])

    return jsonify({
        "status": "success",
        "terms": result["terms"],
        "matrix": result["matrix"]
    })

@app.route("/api/train", methods=["POST"])
def train():

    if "csv" not in request.files:

        return jsonify({
            "status": "error",
            "message": "Dataset tidak ditemukan"
        }),400

    file=request.files["csv"]

    temp_path="dataset.csv"

    file.save(temp_path)

    try:
        result = train_model(temp_path)
    except Exception as e:
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 400

    return jsonify({
        "status": "success",
        "message": "Training selesai",
        **result
    })


# ==========================================
# SCRAPING KOMENTAR TIKTOK (Selenium)
# ==========================================
@app.route("/api/scrape", methods=["POST"])
def scrape():

    data = request.get_json()

    if not data or not str(data.get("url", "")).strip():
        return jsonify({
            "status": "error",
            "message": "URL video TikTok tidak boleh kosong"
        }), 400

    url = data["url"].strip()
    max_comments = int(data.get("max_comments", 50))
    max_comments = max(1, min(max_comments, 500))

    # Import di sini supaya Flask tetap bisa jalan walau paket selenium
    # / chromedriver belum terpasang, selama fitur scraping belum dipakai.
    try:
        from services.scraper import scrape_tiktok_comments
    except ImportError as e:
        return jsonify({
            "status": "error",
            "message": f"Selenium belum terpasang: {e}"
        }), 500

    try:
        comments = scrape_tiktok_comments(url, max_comments=max_comments, headless=False)
    except Exception as e:
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 500

    return jsonify({
        "status": "success",
        "url": url,
        "total": len(comments),
        "comments": comments
    })


# ==========================================
# PREDIKSI KOMENTAR BARU
# ==========================================
@app.route("/api/predict", methods=["POST"])
def predict():

    data = request.get_json()

    if not data or not str(data.get("text", "")).strip():
        return jsonify({
            "status": "error",
            "message": "Teks komentar tidak boleh kosong"
        }), 400

    try:
        result = predict_sentiment(data["text"])

    except FileNotFoundError as e:
        return jsonify({
            "status": "error",
            "message": str(e)
        }), 400

    return jsonify({
        "status": "success",
        **result
    })


# ==========================================
# RUN FLASK
# ==========================================
if __name__ == "__main__":
    app.run(debug=True, port=5000)
import os
import joblib

from services.trainer import (
    preprocess_text,
    clean_sentiment_label,
    get_cyberbullying_category
)

MODEL_DIR = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    "models"
)

_model = None
_vectorizer = None
_loaded_mtime = None


def _load():
    """
    Lazy-load model & vectorizer, di-cache di memori untuk performa.
    Cache otomatis di-refresh kalau file model.pkl berubah.
    """
    global _model, _vectorizer, _loaded_mtime

    model_path = os.path.join(MODEL_DIR, "model.pkl")
    vectorizer_path = os.path.join(MODEL_DIR, "tfidf.pkl")

    if not os.path.exists(model_path) or not os.path.exists(vectorizer_path):
        raise FileNotFoundError(
            "Model belum dilatih. Silakan latih model terlebih dahulu di halaman Training Model."
        )

    current_mtime = os.path.getmtime(model_path)

    if _model is None or _vectorizer is None or current_mtime != _loaded_mtime:
        _model = joblib.load(model_path)
        _vectorizer = joblib.load(vectorizer_path)
        _loaded_mtime = current_mtime

    return _model, _vectorizer


def predict_sentiment(text):
    model, vectorizer = _load()

    clean_text = preprocess_text(text)

    X = vectorizer.transform([clean_text])

    raw_prediction = model.predict(X)[0]
    prediction = clean_sentiment_label(raw_prediction)

    probabilities = model.predict_proba(X)[0]

    probability_map = {
        clean_sentiment_label(label): round(float(prob), 5)
        for label, prob in zip(model.classes_, probabilities)
    }

    cb_category = get_cyberbullying_category(prediction)

    # Penjelasan naratif yang sangat jelas untuk user dan penguji sidang
    if cb_category["is_cyberbullying"]:
        explanation = (
            "🚨 PERINGATAN: Komentar ini teridentifikasi sebagai Sentimen NEGATIF dan dikategorikan "
            "sebagai CYBERBULLYING. Teks memuat indikasi cemoohan, perundungan, ujaran kebencian, "
            "atau serangan destruktif terhadap program MBG atau pihak terkait."
        )
    elif prediction == "Positif":
        explanation = (
            "🛡️ AMAN: Komentar ini teridentifikasi sebagai Sentimen POSITIF dan dikategorikan "
            "sebagai NON-CYBERBULLYING. Teks memuat opini apresiasi, kepuasan, dukungan, "
            "atau antusiasme baik terhadap program MBG."
        )
    else:
        explanation = (
            "🛡️ AMAN: Komentar ini teridentifikasi sebagai Sentimen NETRAL dan dikategorikan "
            "sebagai NON-CYBERBULLYING. Teks memuat pertanyaan, diskusi wajar, fakta objektif, "
            "atau pernyataan netral tanpa unsur perundungan."
        )

    confidence = round(float(max(probabilities)), 5)

    return {
        "text": text,
        "preprocessing": clean_text,
        "prediction": prediction,
        "sentiment": prediction,
        "cyberbullying_status": cb_category["status"],
        "is_cyberbullying": cb_category["is_cyberbullying"],
        "badge_color": cb_category["badge"],
        "icon": cb_category["icon"],
        "explanation": explanation,
        "confidence": confidence,
        "probabilities": probability_map
    }

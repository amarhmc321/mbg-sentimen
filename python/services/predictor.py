import os
import joblib

from services.trainer import (
    preprocess_text,
    clean_sentiment_label,
    get_cyberbullying_category,
    detect_cyberbullying_terms
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

    # Pembedaan tegas: Apakah Negatif ini Cyberbullying atau Negatif Biasa (Kritik Wajar)?
    cb_terms = detect_cyberbullying_terms(text)
    
    # Jika teks memuat leksikon makian/hinaan/serangan verbal nyata:
    # Teks dipastikan sebagai CYBERBULLYING (Sentimen Negatif Agresif)
    if cb_terms:
        prediction = "Negatif"
        cb_category = {
            "status": "Cyberbullying",
            "sub_category": "Negatif (🚨 Cyberbullying)",
            "is_cyberbullying": True,
            "is_ordinary_negative": False,
            "badge": "danger",
            "icon": "bi-exclamation-octagon-fill",
            "detected_terms": cb_terms,
            "desc": f"Terdeteksi CYBERBULLYING: Memuat indikasi makian, cemoohan, atau ujaran kebencian ('{', '.join(cb_terms)}')."
        }
    else:
        cb_category = get_cyberbullying_category(prediction, text)

    detected_terms = cb_category.get("detected_terms", [])
    terms_str = f" ('{', '.join(detected_terms)}')" if detected_terms else ""

    if cb_category["is_cyberbullying"]:
        status_title = "🚨 TERDETEKSI CYBERBULLYING"
        explanation = (
            f"Komentar ini berstatus Sentimen NEGATIF dan teridentifikasi sebagai CYBERBULLYING{terms_str}. "
            f"Teks memuat indikasi makian, cemoohan, pelecehan martabat, atau ujaran kebencian agresif terhadap pihak/program MBG."
        )
    elif cb_category["is_ordinary_negative"] or (prediction == "Negatif" and not cb_terms):
        cb_category["is_ordinary_negative"] = True
        cb_category["status"] = "Negatif Biasa"
        status_title = "💬 KOMENTAR NEGATIF BIASA (BUKAN CYBERBULLYING)"
        explanation = (
            "Komentar ini berstatus Sentimen NEGATIF namun HANYA berupa kritik konstruktif atau keluhan wajar "
            "(seperti ketidakpuasan porsi, rasa hambar/dingin, variasi menu, atau saran perbaikan). "
            "Teks TIDAK memuat kata makian kasar, hinaan personal, atau unsur perundungan siber (Aman)."
        )
    elif prediction == "Positif":
        status_title = "🛡️ NON-CYBERBULLYING (Sentimen Positif)"
        explanation = (
            "Komentar ini teridentifikasi sebagai Sentimen POSITIF dan tergolong NON-CYBERBULLYING. "
            "Teks memuat opini apresiasi, kepuasan, rasa syukur, atau dukungan positif terhadap program MBG."
        )
    else:
        status_title = "ℹ️ NON-CYBERBULLYING (Sentimen Netral)"
        explanation = (
            "Komentar ini teridentifikasi sebagai Sentimen NETRAL dan tergolong NON-CYBERBULLYING. "
            "Teks memuat pertanyaan informasi, diskusi umum, atau pernyataan faktual objektif tanpa muatan perundungan."
        )

    confidence = round(float(max(probabilities)), 5)

    return {
        "text": text,
        "preprocessing": clean_text,
        "prediction": prediction,
        "sentiment": prediction,
        "cyberbullying_status": cb_category["status"],       # "Cyberbullying", "Negatif Biasa", "Positif", "Netral"
        "sub_category": cb_category["sub_category"],         # Label lengkap
        "is_cyberbullying": cb_category["is_cyberbullying"], # True HANYA jika benar-benar cyberbullying
        "is_ordinary_negative": cb_category["is_ordinary_negative"], # True jika negatif biasa / kritik
        "detected_terms": detected_terms,
        "status_title": status_title,
        "badge_color": cb_category["badge"],
        "icon": cb_category["icon"],
        "explanation": explanation,
        "confidence": confidence,
        "probabilities": probability_map
    }

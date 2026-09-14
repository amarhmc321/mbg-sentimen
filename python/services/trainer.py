import os
import re
import joblib
import numpy as np
import pandas as pd

from sklearn.naive_bayes import MultinomialNB
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.model_selection import train_test_split, StratifiedKFold, cross_val_score
from sklearn.metrics import (
    accuracy_score,
    precision_score,
    recall_score,
    f1_score,
    confusion_matrix,
)

from services.preprocessing import (
    case_folding,
    cleaning,
    normalize_repeated_characters,
    stopword_removal,
    stemming,
)

MODEL_DIR = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    "models"
)


def clean_sentiment_label(raw_label):
    """
    Normalisasi label sentimen dari berbagai format (misal ada emoji '😡 Negatif', '😊 Positif', '😐 Netral', dll.)
    menjadi label standar: 'Negatif', 'Positif', 'Netral'.
    """
    if pd.isna(raw_label):
        return "Netral"
    
    text = str(raw_label).strip()
    lower = text.lower()

    if "negatif" in lower or ("cyberbullying" in lower and "non" not in lower):
        return "Negatif"
    elif "positif" in lower:
        return "Positif"
    elif "netral" in lower or "neutral" in lower:
        return "Netral"
    elif "non" in lower:
        return "Positif"
    
    # Bersihkan karakter non-alfanumerik jika masih ada format lain
    cleaned = re.sub(r"[^\w\s]", "", text).strip()
    return cleaned if cleaned else text


def get_cyberbullying_category(sentiment_label):
    """
    Pemetaan tegas Kategori Cyberbullying:
    - Negatif           -> Cyberbullying (Ujaran kebencian, makian, cemoohan, perundungan terhadap MBG)
    - Positif / Netral  -> Non-Cyberbullying (Apresiasi, dukungan, informasi objektif, pertanyaan)
    """
    norm = clean_sentiment_label(sentiment_label)
    if norm == "Negatif":
        return {
            "status": "Cyberbullying",
            "is_cyberbullying": True,
            "badge": "danger",
            "icon": "bi-exclamation-triangle-fill",
            "desc": "Terindikasi ujaran perundungan/cemoohan negatif (Cyberbullying)."
        }
    else:
        return {
            "status": "Non-Cyberbullying",
            "is_cyberbullying": False,
            "badge": "success" if norm == "Positif" else "secondary",
            "icon": "bi-shield-check",
            "desc": "Bukan cyberbullying (komentar positif/netral aman)."
        }


def preprocess_text(text):
    """
    Pipeline preprocessing sesuai Bab III proposal:
    Case Folding -> Cleansing -> Normalisasi Huruf Berulang ->
    Stopword Removal -> Stemming.
    """
    text = case_folding(str(text))
    text = cleaning(text)
    text = normalize_repeated_characters(text)
    text = stopword_removal(text)
    text = stemming(text)
    return text


def diagnose_misclassification(actual, pred, raw_text, clean_text, vocab, probabilities):
    """
    Analisis heuristik penyebab kesalahan klasifikasi (Error Analysis).
    """
    tokens = clean_text.split() if clean_text else []
    oov_tokens = [t for t in tokens if t not in vocab]
    
    reasons = []
    
    # 1. Teks sangat pendek
    if len(tokens) <= 2:
        reasons.append("Teks sangat singkat (kurang dari 3 kata bersih), informasi fitur TF-IDF minim.")
        
    # 2. Out-of-vocabulary (banyak kata tidak ada di data latih)
    if oov_tokens:
        reasons.append(f"Terdapat kata yang tidak ada dalam vocabulary data latih: '{', '.join(oov_tokens[:4])}'.")
        
    # 3. Analisis False Negative Cyberbullying (Aktual Negatif/Cyberbullying diprediksi Netral/Positif)
    if actual == "Negatif" and pred in ["Netral", "Positif"]:
        reasons.append("Pola sarkasme/sindiran halus atau penggunaan kata umum tanpa kata makian eksplisit, sehingga model Naive Bayes condong ke kelas non-cyberbullying.")
        
    # 4. Analisis False Positive Cyberbullying (Aktual Positif/Netral diprediksi Negatif/Cyberbullying)
    elif actual in ["Positif", "Netral"] and pred == "Negatif":
        reasons.append("Komentar mengandung kata bernada evaluatif atau kata yang sering diasosiasikan dengan keluhan pada data latih, padahal konteksnya adalah masukan wajar/netral.")
        
    # 5. Margin probabilitas tipis
    probs = list(probabilities.values())
    if len(probs) >= 2:
        sorted_probs = sorted(probs, reverse=True)
        if (sorted_probs[0] - sorted_probs[1]) < 0.15:
            reasons.append(f"Selisih probabilitas antar-kelas sangat tipis ({round((sorted_probs[0] - sorted_probs[1])*100, 1)}%), menunjukkan tingkat ambiguitas fitur yang tinggi.")

    if not reasons:
        reasons.append("Distribusi bobot kata pada teks lebih dominan mengarah ke pola kata kelas prediksi pada data latih.")
        
    return " | ".join(reasons)


def train_model(csv_path):
    # 1. Membaca dataset
    df = pd.read_csv(csv_path)

    # Validasi kolom
    if "Komentar" not in df.columns:
        raise Exception("Kolom 'Komentar' tidak ditemukan.")

    if "Sentimen" not in df.columns:
        raise Exception("Kolom 'Sentimen' tidak ditemukan.")

    df = df.dropna(subset=["Komentar", "Sentimen"]).reset_index(drop=True)

    if len(df) < 4:
        raise Exception(
            "Dataset terlalu sedikit untuk dilatih (minimal 4 baris dengan label)."
        )

    # Normalisasi kolom label sentimen
    df["Sentimen"] = df["Sentimen"].apply(clean_sentiment_label)

    # 2. Preprocessing teks
    df["komentar_bersih"] = df["Komentar"].apply(preprocess_text)

    # Buang baris yang jadi kosong setelah preprocessing
    df = df[df["komentar_bersih"].str.strip() != ""].reset_index(drop=True)

    if len(df) < 4:
        raise Exception("Dataset bersih setelah preprocessing kurang dari 4 baris.")

    # 3. Split data latih (80%) dan data uji (20%)
    y = df["Sentimen"]
    class_counts = y.value_counts()
    min_class_count = int(class_counts.min())

    stratify = y if min_class_count >= 2 else None
    
    # Split index untuk mempertahankan referensi baris utuh (untuk error analysis)
    train_indices, test_indices = train_test_split(
        df.index,
        test_size=0.2,
        random_state=42,
        stratify=stratify
    )

    df_train = df.loc[train_indices].reset_index(drop=True)
    df_test = df.loc[test_indices].reset_index(drop=True)

    X_train_text = df_train["komentar_bersih"]
    y_train = df_train["Sentimen"]
    X_test_text = df_test["komentar_bersih"]
    y_test = df_test["Sentimen"]

    # 4. Pembobotan TF-IDF data latih
    vectorizer = TfidfVectorizer()
    X_train = vectorizer.fit_transform(X_train_text)
    X_test = vectorizer.transform(X_test_text)

    # 5. Melatih model Naive Bayes pada data latih
    model = MultinomialNB()
    model.fit(X_train, y_train)

    # 6. Menguji model pada data uji (holdout 20%)
    y_pred = model.predict(X_test)
    y_prob = model.predict_proba(X_test)

    labels = [str(c) for c in sorted(list(model.classes_))]
    label_indices = {lbl: idx for idx, lbl in enumerate(labels)}

    # Metrik Dasar
    accuracy = float(accuracy_score(y_test, y_pred))
    precision_w = float(precision_score(y_test, y_pred, average="weighted", zero_division=0))
    recall_w = float(recall_score(y_test, y_pred, average="weighted", zero_division=0))
    f1_w = float(f1_score(y_test, y_pred, average="weighted", zero_division=0))
    
    cm = confusion_matrix(y_test, y_pred, labels=labels)
    total_test = int(len(y_test))
    total_correct = int(np.trace(cm))
    total_incorrect = total_test - total_correct

    # =========================================================================
    # PERHITUNGAN DETAIL PER KELAS & URAIAN MATEMATIS (STEP-BY-STEP EVALUATION)
    # =========================================================================
    class_evaluations = []
    class_breakdown_formulas = []
    
    support_total = 0
    
    for i, label in enumerate(labels):
        tp = int(cm[i, i])
        fp = int(np.sum(cm[:, i]) - tp)
        fn = int(np.sum(cm[i, :]) - tp)
        tn = int(total_test - (tp + fp + fn))
        support = tp + fn
        support_total += support

        prec = tp / (tp + fp) if (tp + fp) > 0 else 0.0
        rec = tp / (tp + fn) if (tp + fn) > 0 else 0.0
        f1_c = (2 * prec * rec) / (prec + rec) if (prec + rec) > 0 else 0.0

        cb_info = get_cyberbullying_category(label)

        class_info = {
            "label": label,
            "cyberbullying_category": cb_info["status"],
            "is_cyberbullying": cb_info["is_cyberbullying"],
            "badge": cb_info["badge"],
            "support": support,
            "tp": tp,
            "fp": fp,
            "fn": fn,
            "tn": tn,
            "precision": round(prec, 4),
            "recall": round(rec, 4),
            "f1_score": round(f1_c, 4),
            "formulas": {
                "precision_text": f"Precision = TP / (TP + FP) = {tp} / ({tp} + {fp}) = {tp}/{tp+fp} = {prec:.4f} ({prec*100:.2f}%)",
                "recall_text": f"Recall = TP / (TP + FN) = {tp} / ({tp} + {fn}) = {tp}/{tp+fn} = {rec:.4f} ({rec*100:.2f}%)",
                "f1_text": f"F1-Score = 2 × (P × R) / (P + R) = 2 × ({prec:.4f} × {rec:.4f}) / ({prec:.4f} + {rec:.4f}) = {f1_c:.4f} ({f1_c*100:.2f}%)",
                "tp_desc": f"{tp} komentar {label} berhasil diprediksi tepat sebagai {label}.",
                "fp_desc": f"{fp} komentar kelas lain keliru diprediksi sebagai {label}.",
                "fn_desc": f"{fn} komentar {label} keliru diprediksi sebagai kelas lain.",
                "tn_desc": f"{tn} komentar bukan {label} berhasil diidentifikasi bukan {label}."
            }
        }
        class_evaluations.append(class_info)

    # Uraian Matematis Akurasi
    diagonal_terms = " + ".join([f"{cm[i, i]} ({labels[i]})" for i in range(len(labels))])
    accuracy_formula_text = (
        f"Akurasi = Jumlah Prediksi Benar / Total Data Uji\n"
        f"Akurasi = (Diagonal Utama CM) / N_test\n"
        f"Akurasi = ({diagonal_terms}) / {total_test}\n"
        f"Akurasi = {total_correct} / {total_test} = {accuracy:.4f} = {accuracy * 100:.2f}%"
    )

    # Uraian Weighted Average
    prec_calc_parts = " + ".join([f"({c['support']} × {c['precision']:.4f})" for c in class_evaluations])
    rec_calc_parts = " + ".join([f"({c['support']} × {c['recall']:.4f})" for c in class_evaluations])
    f1_calc_parts = " + ".join([f"({c['support']} × {c['f1_score']:.4f})" for c in class_evaluations])

    # 7. Evaluasi 5-Fold Cross Validation
    cv_accuracy_mean = None
    cv_accuracy_scores = None
    cv_formula_text = None
    n_splits = min(5, min_class_count)

    if n_splits >= 2:
        X_full = vectorizer.transform(df["komentar_bersih"])
        cv = StratifiedKFold(n_splits=n_splits, shuffle=True, random_state=42)
        cv_model = MultinomialNB()
        cv_scores = cross_val_score(cv_model, X_full, y, cv=cv, scoring="accuracy")
        cv_accuracy_scores = [round(float(s), 4) for s in cv_scores]
        cv_accuracy_mean = round(float(np.mean(cv_scores)), 4)
        
        scores_sum_str = " + ".join([f"{s:.4f}" for s in cv_accuracy_scores])
        cv_formula_text = f"Mean CV = ({scores_sum_str}) / {n_splits} = {cv_accuracy_mean:.4f} = {cv_accuracy_mean*100:.2f}%"

    # =========================================================================
    # 8. ANALISIS DATA YANG MENGALAMI KESALAHAN KLASIFIKASI (ERROR ANALYSIS)
    # =========================================================================
    misclassified_items = []
    error_type_counts = {}

    training_vocab = vectorizer.vocabulary_

    for i in range(total_test):
        act = str(y_test.iloc[i])
        prd = str(y_pred[i])

        prob_map = {
            str(lbl): round(float(y_prob[i][idx]), 4)
            for lbl, idx in label_indices.items()
        }
        conf = float(np.max(y_prob[i]))

        if act != prd:
            key_err = f"{act} → {prd}"
            error_type_counts[key_err] = error_type_counts.get(key_err, 0) + 1

            raw_comment = str(df_test.iloc[i].get("Komentar", ""))
            clean_cmt = str(df_test.iloc[i].get("komentar_bersih", ""))
            user_name = str(df_test.iloc[i].get("Username", "Pengguna"))

            reason = diagnose_misclassification(
                act, prd, raw_comment, clean_cmt, training_vocab, prob_map
            )

            act_cb = get_cyberbullying_category(act)
            prd_cb = get_cyberbullying_category(prd)

            misclassified_items.append({
                "index": int(i + 1),
                "username": user_name,
                "raw_comment": raw_comment,
                "clean_comment": clean_cmt,
                "actual_sentiment": act,
                "actual_cyberbullying": act_cb["status"],
                "actual_badge": act_cb["badge"],
                "predicted_sentiment": prd,
                "predicted_cyberbullying": prd_cb["status"],
                "predicted_badge": prd_cb["badge"],
                "confidence": round(conf * 100, 2),
                "probabilities": prob_map,
                "reason_analysis": reason,
                "is_false_cyberbullying": (not act_cb["is_cyberbullying"] and prd_cb["is_cyberbullying"]),
                "is_missed_cyberbullying": (act_cb["is_cyberbullying"] and not prd_cb["is_cyberbullying"])
            })

    # Rekap Error Cyberbullying
    false_pos_cb = sum(1 for item in misclassified_items if item["is_false_cyberbullying"])
    missed_cb = sum(1 for item in misclassified_items if item["is_missed_cyberbullying"])
    error_rate = (total_incorrect / total_test) * 100 if total_test > 0 else 0

    # 9. Latih Model Final pada Seluruh Data (untuk disimpan)
    final_vectorizer = TfidfVectorizer()
    X_all = final_vectorizer.fit_transform(df["komentar_bersih"])
    final_model = MultinomialNB()
    final_model.fit(X_all, y)

    os.makedirs(MODEL_DIR, exist_ok=True)
    joblib.dump(final_model, os.path.join(MODEL_DIR, "model.pkl"))
    joblib.dump(final_vectorizer, os.path.join(MODEL_DIR, "tfidf.pkl"))

    return {
        "rows": len(df),
        "train_rows": len(df_train),
        "test_rows": len(df_test),
        "classes": labels,
        "classes_cyberbullying_mapping": {
            lbl: get_cyberbullying_category(lbl) for lbl in labels
        },
        "evaluation": {
            "accuracy": round(accuracy, 4),
            "precision": round(precision_w, 4),
            "recall": round(recall_w, 4),
            "f1_score": round(f1_w, 4),
            "confusion_matrix": {
                "labels": labels,
                "matrix": cm.tolist()
            }
        },
        "step_by_step_evaluation": {
            "total_test": total_test,
            "total_correct": total_correct,
            "total_incorrect": total_incorrect,
            "accuracy_calc": {
                "formula": "Akurasi = Jumlah Prediksi Benar / Total Data Uji",
                "substitution": f"{total_correct} / {total_test}",
                "result_decimal": round(accuracy, 4),
                "result_pct": round(accuracy * 100, 2),
                "explanation": f"Dari {total_test} data uji (holdout 20%), model Naive Bayes berhasil mengklasifikasikan {total_correct} komentar dengan benar, dan salah pada {total_incorrect} komentar.",
                "formula_text": accuracy_formula_text
            },
            "class_evaluations": class_evaluations,
            "weighted_averages": {
                "precision": round(precision_w, 4),
                "precision_formula": f"Weighted Precision = ({prec_calc_parts}) / {support_total} = {precision_w:.4f}",
                "recall": round(recall_w, 4),
                "recall_formula": f"Weighted Recall = ({rec_calc_parts}) / {support_total} = {recall_w:.4f}",
                "f1_score": round(f1_w, 4),
                "f1_formula": f"Weighted F1 = ({f1_calc_parts}) / {support_total} = {f1_w:.4f}"
            }
        },
        "cross_validation": {
            "n_splits": n_splits if n_splits >= 2 else None,
            "scores": cv_accuracy_scores,
            "mean_accuracy": cv_accuracy_mean,
            "formula_text": cv_formula_text,
            "note": None if n_splits >= 2 else "Data per kelas terlalu sedikit untuk 5-Fold CV."
        },
        "error_analysis": {
            "total_misclassified": total_incorrect,
            "error_rate_pct": round(error_rate, 2),
            "error_type_counts": error_type_counts,
            "cyberbullying_impact": {
                "false_cyberbullying_count": false_pos_cb,
                "false_cyberbullying_desc": f"{false_pos_cb} komentar non-cyberbullying keliru terdeteksi sebagai cyberbullying (False Positive).",
                "missed_cyberbullying_count": missed_cb,
                "missed_cyberbullying_desc": f"{missed_cb} komentar cyberbullying lolos dari deteksi (False Negative)."
            },
            "misclassified_samples": misclassified_items
        },
        "model_file": "model.pkl",
        "vectorizer_file": "tfidf.pkl"
    }

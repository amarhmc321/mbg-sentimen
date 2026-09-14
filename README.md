# SentiGuard - Analisis Sentimen Cyberbullying MBG

Aplikasi analisis sentimen & deteksi cyberbullying pada komentar TikTok
program Makan Bergizi Gratis (MBG), menggunakan TF-IDF + Naive Bayes.
Dibangun mengikuti metodologi pada proposal penelitian Bab III.

## Fitur

1. **Upload Dataset** - unggah CSV (Username, Komentar, Sentimen).
2. **Scraping TikTok (Selenium)** - ambil komentar publik langsung dari video
   TikTok, lalu simpan sebagai dataset baru (belum berlabel).
3. **Kelola Dataset** - lihat komentar per dataset, jalankan preprocessing
   (case folding, cleansing, tokenizing, stopword removal, stemming Sastrawi),
   beri/ubah label sentimen manual, export CSV berlabel, atau langsung latih
   model dari dataset yang tersimpan di database.
4. **Preprocessing Demo** - lihat hasil tiap tahap preprocessing untuk satu
   komentar contoh.
5. **TF-IDF Calculator** - hitung matriks TF-IDF dari beberapa dokumen contoh.
6. **Training Model** - latih Naive Bayes + TF-IDF dari file CSV, dengan
   split data latih 80% / uji 20%, evaluasi (akurasi, precision, recall,
   F1-score, confusion matrix), dan 5-Fold Cross Validation.
7. **Prediksi Komentar** - uji satu komentar baru dan lihat probabilitas
   tiap kelas sentimen.
8. **Dashboard** - ringkasan jumlah dataset/komentar/model, distribusi
   sentimen (grafik), dan daftar dataset.
9. **Riwayat** - riwayat semua model yang pernah dilatih dan semua prediksi
   yang pernah diuji (tersimpan di database).

## Teknologi

- PHP Native (MVC) + MySQLi
- Python Flask (service ML terpisah, dipanggil PHP lewat cURL)
- MySQL / MariaDB
- Bootstrap 5, jQuery, Chart.js
- Scikit-learn (TF-IDF, Multinomial Naive Bayes, evaluasi)
- Sastrawi (stemming & stopword Bahasa Indonesia)
- Selenium (web scraping komentar TikTok)

## Requirement

- PHP >= 8.2 (dengan ekstensi mysqli, curl)
- Python >= 3.10 (proposal menyebut 3.14, tapi semua paket di
  `requirements.txt` sudah teruji baik di Python 3.10-3.12 juga)
- MySQL 8 / MariaDB (mis. lewat XAMPP)
- Google Chrome + chromedriver versi yang cocok (hanya dibutuhkan kalau mau
  memakai fitur Scraping TikTok)

## Struktur

```
app/        MVC PHP: controller, model, service, config, helper
api/        Endpoint yang dipanggil frontend (proxy ke Flask untuk tugas ML)
database/   Skema database (db_mbg.sql)
public/     Halaman-halaman aplikasi (frontend)
python/     Service Flask: preprocessing, TF-IDF, training, prediksi, scraping
uploads/    Folder upload sementara
logs/       Log aplikasi
```

## Cara Menjalankan

1. Buat database lalu import skema:
   ```
   mysql -u root -p < database/db_mbg.sql
   ```
   (atau import lewat phpMyAdmin: buat database `db_mbg`, lalu import file
   `database/db_mbg.sql`)

2. Sesuaikan kredensial database di `app/config/Config.php` bila perlu.

3. Install dependency Python & jalankan Flask:
   ```
   cd python
   pip install -r requirements.txt --break-system-packages
   python app.py
   ```
   Flask berjalan di `http://127.0.0.1:5000` (dipanggil oleh PHP lewat cURL).

4. Jalankan Apache & MySQL (mis. lewat XAMPP), letakkan folder proyek ini
   di `htdocs/MBG` (atau sesuaikan `BASE_URL` di `Config.php`).

5. Buka `http://localhost/MBG/public/index.php` di browser.

### Alur penggunaan yang disarankan

```
Upload Dataset / Scraping TikTok
        |
        v
Kelola Dataset  --(preprocessing)-->  Beri Label (kalau belum berlabel)
        |
        v
Latih Model dari Dataset Ini  --(atau)-->  halaman Training (upload CSV manual)
        |
        v
Lihat evaluasi di halaman Riwayat / Dashboard
        |
        v
Prediksi komentar baru
```

## Catatan tentang Scraping TikTok

TikTok kerap mengubah struktur halaman & bisa menampilkan captcha atau
mewajibkan login untuk video/akun tertentu. Fitur scraping di sini bersifat
best-effort untuk kebutuhan akademik (mengikuti metodologi Bab III.3.3.1),
bukan jaminan bisa mengambil komentar dari sembarang video. Kalau selector
CSS `data-e2e="..."` yang dipakai di `python/services/scraper.py` berhenti
berfungsi karena TikTok berubah, sesuaikan kembali selector-nya.

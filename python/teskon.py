"""
Web scraping komentar TikTok menggunakan Selenium (Bab II.2.9 & II.2.11,
Bab III.3.3.1 pada proposal).

CATATAN PENTING:
- TikTok kerap mengubah struktur DOM / nama class-nya, dan bisa menampilkan
  captcha atau memaksa login untuk video/akun tertentu. Selector CSS di bawah
  ini ('data-e2e' attributes) relatif stabil dibanding class name acak, tapi
  tetap perlu dicek ulang berkala jika TikTok mengubah tampilannya.
- Gunakan scraping ini secara wajar (jumlah video/komentar terbatas, beri jeda
  antar request) dan hanya untuk komentar publik, sesuai kebutuhan akademik
  pada proposal ini.
- Membutuhkan Google Chrome + chromedriver yang versinya cocok dengan Chrome
  yang terpasang di komputer kamu.
"""

import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


def _build_driver(headless: bool = True):
    options = Options()

    if headless:
        options.add_argument("--headless=new")

    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--window-size=1366,768")
    # options.add_argument(
    #     "user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    #     "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"
    # )

    return webdriver.Chrome(options=options)


def scrape_tiktok_comments(video_url: str, max_comments: int = 100, headless: bool = False):
    """
    Membuka halaman video TikTok, men-scroll panel komentar, lalu
    mengekstraksi username + isi komentar + jumlah like.

    Return: list[dict] -> [{"username": ..., "comment": ..., "likes": ...}, ...]
    """

    driver = _build_driver(headless=headless)
    results = []

    try:
        driver.get(video_url)

        # Tunggu komentar pertama muncul
        try:
            WebDriverWait(driver, 20).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, '[data-e2e="comment-level-1"]'))
            )
        except TimeoutException:
            driver.quit()
            raise RuntimeError(
                "Komentar tidak termuat dalam 20 detik. Kemungkinan video butuh "
                "login, dilindungi, atau selector TikTok sudah berubah."
            )

        seen_keys = set()
        stagnant_rounds = 0
        max_stagnant_rounds = 6

        while len(results) < max_comments and stagnant_rounds < max_stagnant_rounds:

            comment_blocks = driver.find_elements(By.CSS_SELECTOR, '[data-e2e="comment-level-1"]')

            before_count = len(results)

            for block in comment_blocks:

                if len(results) >= max_comments:
                    break

                try:
                    username = block.find_element(
                        By.CSS_SELECTOR, '[data-e2e="comment-username-1"]'
                    ).text.strip()
                except NoSuchElementException:
                    username = ""

                try:
                    comment_text = block.find_element(
                        By.CSS_SELECTOR, '[data-e2e="comment-level-1"] p'
                    ).text.strip()
                except NoSuchElementException:
                    comment_text = ""

                try:
                    likes = block.find_element(
                        By.CSS_SELECTOR, '[data-e2e="comment-like-count"]'
                    ).text.strip()
                except NoSuchElementException:
                    likes = "0"

                if not comment_text:
                    continue

                key = (username, comment_text)

                if key in seen_keys:
                    continue

                seen_keys.add(key)
                results.append({
                    "username": username or "anonim",
                    "comment": comment_text,
                    "likes": likes
                })

            # Scroll ke bawah panel komentar supaya komentar baru dimuat (lazy load)
            driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
            time.sleep(1.5)

            if len(results) == before_count:
                stagnant_rounds += 1
            else:
                stagnant_rounds = 0

    finally:
        driver.quit()

    return results

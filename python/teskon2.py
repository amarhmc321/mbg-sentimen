import time
import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


def _build_driver(headless: bool = False):
    options = uc.ChromeOptions()
    if headless:
        options.add_argument('--headless')
    options.add_argument("--disable-gpu")
    options.add_argument("--window-size=1366,768")
    driver = uc.Chrome(options=options)
    return driver


def scrape_tiktok_comments(video_url: str, max_comments: int = 100, headless: bool = False):
    driver = _build_driver(headless=headless)
    results = []
    seen_keys = set()

    try:
        driver.get(video_url)
        print("Membuka halaman video...")
        time.sleep(3)

        # 1. HAPUS OVERLAY TUTORIAL "GOT IT" (JIKA ADA)
        try:
            got_it_button = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, "//button[text()='Got it']"))
            )
            got_it_button.click()
            print("Overlay tutorial dibersihkan.")
            time.sleep(2)
        except TimeoutException:
            pass

        # 2. BERALIH KE TAB "COMMENTS"
        try:
            comments_tab = WebDriverWait(driver, 10).until(
                EC.element_to_be_clickable((By.XPATH, "//*[text()='Comments' or contains(text(), 'Komentar')]"))
            )
            comments_tab.click()
            print("Berhasil beralih ke tab Comments.")
            time.sleep(3)
        except Exception as e:
            print(f"Catatan tab Comments: {e}")

        # 3. MENGAMBIL KOMENTAR DENGAN MULTI-SELECTOR (FALLBACK)
        print("Mulai memindai elemen komentar...")
        
        stagnant_rounds = 0
        max_stagnant_rounds = 6

        while len(results) < max_comments and stagnant_rounds < max_stagnant_rounds:
            # Mengambil semua blok komentar yang ada di layar
            comment_blocks = driver.find_elements(By.CSS_SELECTOR, '[data-e2e="comment-level-1"]')
            
            # Jika selector 'comment-level-1' tidak ditemukan, coba selector cadangan TikTok
            if not comment_blocks:
                comment_blocks = driver.find_elements(By.CSS_SELECTOR, 'div[class*="DivCommentItemContainer"]')

            before_count = len(results)
            print(f"Ditemukan {len(comment_blocks)} blok komentar di DOM...")

            for block in comment_blocks:
                if len(results) >= max_comments:
                    break

                # --- A. Ekstraksi Username ---
                username = ""
                for u_selector in ['[data-e2e="comment-username-1"]', 'a[href*="/@"]', 'span[class*="User"]']:
                    try:
                        username = block.find_element(By.CSS_SELECTOR, u_selector).text.strip()
                        if username:
                            break
                    except NoSuchElementException:
                        pass

                # --- B. Ekstraksi Teks Komentar (Multi-Fallback) ---
                comment_text = ""
                # Urutan pencarian: data-e2e khusus -> tag p -> tag span
                for c_selector in ['[data-e2e="comment-level-1-text"]', 'p', 'span[class*="SpanText"]', 'span']:
                    try:
                        elements = block.find_elements(By.CSS_SELECTOR, c_selector)
                        for elem in elements:
                            txt = elem.text.strip()
                            # Validasi agar teks bukan username atau string kosong
                            if txt and txt != username and len(txt) > 0:
                                comment_text = txt
                                break
                        if comment_text:
                            break
                    except NoSuchElementException:
                        pass

                # --- C. Ekstraksi Likes ---
                likes = "0"
                try:
                    likes = block.find_element(By.CSS_SELECTOR, '[data-e2e="comment-like-count"]').text.strip()
                except NoSuchElementException:
                    likes = "0"

                # Skip jika isi komentar memang tidak berhasil terbaca
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

            print(f"Progres ekstraksi: {len(results)}/{max_comments} komentar terkumpul.")

            # --- D. Scroll Panel Komentar ---
            try:
                # Scroll spesifik di dalam wadah komentar
                container = driver.find_element(By.CSS_SELECTOR, '[data-e2e="comment-list-container"]')
                driver.execute_script("arguments[0].scrollBy(0, 500);", container)
            except NoSuchElementException:
                # Fallback scroll halaman
                driver.execute_script("window.scrollBy(0, 500);")

            time.sleep(2.5)

            if len(results) == before_count:
                stagnant_rounds += 1
            else:
                stagnant_rounds = 0

    finally:
        driver.quit()

    return results
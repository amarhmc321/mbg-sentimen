import time
import random
import platform
import undetected_chromedriver as uc
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.common.exceptions import TimeoutException, NoSuchElementException

# Import winreg khusus sistem operasi Windows
if platform.system() == "Windows":
    import winreg


def get_installed_chrome_version():
    """Mendeteksi major version Google Chrome yang terpasang di Windows."""
    if platform.system() != "Windows":
        return None

    # Coba cek via HKCU (Current User)
    try:
        key = winreg.OpenKey(winreg.HKEY_CURRENT_USER, r"Software\Google\Chrome\BLBeacon")
        version, _ = winreg.QueryValueEx(key, "version")
        return int(version.split('.')[0])
    except Exception:
        pass

    # Coba cek via HKLM (Local Machine / All Users)
    try:
        key = winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, r"SOFTWARE\WOW6432Node\Google\Update\Clients\{8A69D345-D564-463c-AFF1-A69D9E530F96}")
        version, _ = winreg.QueryValueEx(key, "pv")
        return int(version.split('.')[0])
    except Exception:
        pass

    return None


def _build_driver(headless: bool = False):
    options = uc.ChromeOptions()
    if headless:
        # Headless mode terbaru + anti-deteksi
        options.add_argument('--headless=new')
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--disable-blink-features=AutomationControlled')

    options.add_argument("--window-size=1366,768")

    # Deteksi versi utama Google Chrome
    chrome_version = get_installed_chrome_version()

    if chrome_version:
        print(f"[INFO] Terdeteksi Google Chrome versi: {chrome_version}")
        driver = uc.Chrome(
            options=options,
            version_main=chrome_version,  # Kunci versi sesuai hasil deteksi sistem
            use_subprocess=True
        )
    else:
        print("[INFO] Versi Chrome tidak dapat dibaca dari Registry, menggunakan konfigurasi default...")
        driver = uc.Chrome(options=options, use_subprocess=True)

    # Hapus flag webdriver
    driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
    return driver


def scrape_tiktok_comments(video_url: str, max_comments: int = 100, headless: bool = False):
    driver = _build_driver(headless=headless)
    results = []
    seen_keys = set()

    try:
        driver.get(video_url)
        print("Membuka halaman video...")
        time.sleep(8 if headless else 5)   # Beri waktu ekstra pemuatan awal

        # Simulasi gerakan mouse kecil untuk menunjukkan aktivitas
        try:
            body = driver.find_element(By.TAG_NAME, 'body')
            ActionChains(driver).move_to_element_with_offset(body, 200, 200).pause(0.5).move_by_offset(50, 0).perform()
        except Exception:
            pass

        # 1. Hapus overlay "Got it"
        try:
            got_it_button = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, "//button[text()='Got it']"))
            )
            got_it_button.click()
            print("Overlay tutorial dibersihkan.")
            time.sleep(2)
        except TimeoutException:
            pass

        # 2. Klik tab Comments
        try:
            comments_tab = WebDriverWait(driver, 15).until(
                EC.element_to_be_clickable((By.XPATH, "//*[text()='Comments' or contains(text(), 'Komentar')]"))
            )
            comments_tab.click()
            print("Berhasil beralih ke tab Comments.")
            time.sleep(5)   # Tunggu panel komentar terbuka
        except Exception as e:
            print(f"Gagal klik tab Comments: {e}")
            return results

        # 3. Paksa loading komentar dengan beberapa scroll kecil + gerakan mouse
        print("Memaksa pemuatan komentar...")
        comment_container = None
        try:
            comment_container = WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, 'div[class*="DivCommentListContainer"]'))
            )
        except TimeoutException:
            pass

        # Scroll bertahap di dalam kontainer (atau halaman) untuk memicu lazy load
        for _ in range(4):
            try:
                if comment_container:
                    driver.execute_script("arguments[0].scrollBy(0, 300);", comment_container)
                else:
                    driver.execute_script("window.scrollBy(0, 300);")
                
                # Gerakan mouse acak di dalam kontainer
                ActionChains(driver).move_to_element_with_offset(
                    comment_container or driver.find_element(By.TAG_NAME, 'body'),
                    random.randint(50, 200), random.randint(50, 200)
                ).pause(0.3).perform()
            except Exception:
                pass
            time.sleep(1.5)

        # Tambahan: tunggu hingga elemen komentar benar-benar terlihat
        try:
            WebDriverWait(driver, 15).until(
                EC.visibility_of_element_located((By.CSS_SELECTOR, 'div[class*="DivCommentItemWrapper"]'))
            )
        except TimeoutException:
            print("Komentar tidak muncul setelah scroll. Coba simpan screenshot...")
            if headless:
                driver.save_screenshot("headless_no_comments.png")
            return results

        print("Mulai memindai komentar...")
        stagnant_rounds = 0
        max_stagnant = 3

        while len(results) < max_comments and stagnant_rounds < max_stagnant:
            comment_blocks = driver.find_elements(By.CSS_SELECTOR, 'div[class*="DivCommentItemWrapper"]')
            before_count = len(results)
            print(f"Ditemukan {len(comment_blocks)} blok komentar.")

            for block in comment_blocks:
                if len(results) >= max_comments:
                    break

                # ----- A. USERNAME (dari href /@) -----
                username = ""
                try:
                    user_link = block.find_element(By.CSS_SELECTOR, 'a[href*="/@"]')
                    href = user_link.get_attribute('href')
                    username_raw = href.split('/@')[-1].split('?')[0]
                    username = '@' + username_raw.strip()
                except NoSuchElementException:
                    pass

                # ----- B. TEKS KOMENTAR -----
                comment_text = ""
                try:
                    comment_span = block.find_element(By.CSS_SELECTOR, 'span[data-e2e="comment-level-1"] span')
                    comment_text = comment_span.text.strip()
                except NoSuchElementException:
                    for span in block.find_elements(By.CSS_SELECTOR, 'span.TUXText'):
                        txt = span.text.strip()
                        if txt and len(txt) > 2 and not txt.startswith('@'):
                            comment_text = txt
                            break

                if not comment_text:
                    continue

                # ----- C. LIKES -----
                likes = "0"
                try:
                    like_container = block.find_element(By.CSS_SELECTOR, 'div[aria-label*="Like video"]')
                    like_span = like_container.find_element(By.TAG_NAME, 'span')
                    likes = like_span.text.strip()
                except NoSuchElementException:
                    try:
                        like_div = block.find_element(By.CSS_SELECTOR, 'div[class*="DivLikeContainer"]')
                        likes = like_div.find_element(By.TAG_NAME, 'span').text.strip()
                    except Exception:
                        pass

                # Hindari duplikasi
                key = (username, comment_text)
                if key in seen_keys:
                    continue

                seen_keys.add(key)
                results.append({
                    "username": username if username else "anonim",
                    "comment": comment_text,
                    "likes": likes
                })

            print(f"Terkumpul {len(results)} komentar.")

            # ----- D. SCROLL + interaksi rutin -----
            try:
                if comment_container:
                    driver.execute_script("arguments[0].scrollBy(0, 800);", comment_container)
                else:
                    driver.execute_script("window.scrollBy(0, 800);")
                ActionChains(driver).move_by_offset(random.randint(-20, 20), random.randint(-20, 20)).pause(0.2).perform()
            except Exception:
                pass
            time.sleep(3 if headless else 2.5)

            if len(results) == before_count:
                stagnant_rounds += 1
            else:
                stagnant_rounds = 0

    finally:
        driver.quit()

    return results


if __name__ == "__main__":
    url = input("Masukkan URL video TikTok: ")
    data = scrape_tiktok_comments(url, max_comments=20, headless=False)
    print("\n===== HASIL AKHIR =====")
    for i, d in enumerate(data, 1):
        print(f"{i}. {d['username']} : {d['comment']} (❤️ {d['likes']})")
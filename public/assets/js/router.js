/**
 * SentiGuard MBG - SPA Client-side Router (router.js)
 * Mengelola navigasi halaman dinamis tanpa reload penuh (Single Page Application)
 * Mendukung History API (pushState & popstate), sinkronisasi URL search params,
 * indikator loading, dan eksekusi skrip dinamis terisolasi (IIFE).
 */

(function () {
    'use strict';

    // Buat elemen progress bar di bagian paling atas halaman
    const style = document.createElement('style');
    style.textContent = `
        #spa-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd, #0dcaf0);
            z-index: 99999;
            transition: width 0.25s ease, opacity 0.3s ease;
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.7);
            pointer-events: none;
        }
        .spa-fade-enter {
            opacity: 0.15;
            transition: opacity 0.2s ease-in-out;
        }
        .spa-fade-active {
            opacity: 1;
            transition: opacity 0.2s ease-in-out;
        }
    `;
    document.head.appendChild(style);

    const progressBar = document.createElement('div');
    progressBar.id = 'spa-progress-bar';
    document.body.appendChild(progressBar);

    function setProgress(percent) {
        if (!progressBar) return;
        progressBar.style.opacity = '1';
        progressBar.style.width = percent + '%';
        if (percent >= 100) {
            setTimeout(() => {
                progressBar.style.opacity = '0';
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 350);
            }, 200);
        }
    }

    /**
     * Dapatkan kontainer utama halaman (#app-main)
     */
    function getMainContainer(doc = document) {
        return doc.querySelector('#app-main') ||
               doc.querySelector('.spa-container') ||
               doc.querySelector('.container.mb-5') ||
               doc.querySelector('.container.mt-5') ||
               doc.querySelector('.container');
    }

    /**
     * Perbarui status menu aktif pada navbar
     */
    function updateActiveNav(targetUrl) {
        const cleanPath = targetUrl.split('?')[0].split('#')[0].split('/').pop() || 'index.php';
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            const href = link.getAttribute('href') || '';
            const linkPath = href.split('?')[0].split('#')[0].split('/').pop() || 'index.php';
            
            if (cleanPath === linkPath || (cleanPath === '' && linkPath === 'index.php')) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            } else {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });
    }

    /**
     * Eksekusi seluruh skrip spesifik halaman yang baru dimuat
     */
    function executePageScripts(doc) {
        // 1. Bersihkan instance chart lama bila ada (mencegah error canvas is already in use)
        if (window.mySentimentChart && typeof window.mySentimentChart.destroy === 'function') {
            try {
                window.mySentimentChart.destroy();
                window.mySentimentChart = null;
            } catch (e) {
                console.warn('Error destroying chart instance:', e);
            }
        }

        // 2. Kumpulkan seluruh tag script dari dokumen yang baru di-fetch
        // Periksa baik yang berada di dalam #app-main maupun di dalam body/head
        const incomingScripts = Array.from(doc.querySelectorAll('script'));

        incomingScripts.forEach(script => {
            const src = script.getAttribute('src');

            // Lewati library vendor umum yang sudah terpasang
            if (src) {
                if (src.includes('router.js') || 
                    src.includes('bootstrap') || 
                    src.includes('jquery')) {
                    return;
                }

                // Jika ada library eksternal (seperti Chart.js) yang belum ada di dokumen saat ini, pasang
                if (!document.querySelector(`script[src="${src}"]`)) {
                    const extScript = document.createElement('script');
                    Array.from(script.attributes).forEach(attr => extScript.setAttribute(attr.name, attr.value));
                    document.head.appendChild(extScript);
                }
                return;
            }

            // Script Inline
            const code = script.textContent.trim();
            if (!code) return;

            // Jangan jalankan kembali inisialisasi router di inline
            if (code.includes('SPA Client-side Router') || code.includes('spa-progress-bar')) {
                return;
            }

            // Eksekusi skrip inline dalam scope terisolasi (IIFE)
            // Menggunakan DOM script injection agar terhubung penuh dengan window & document
            try {
                const s = document.createElement('script');
                s.type = 'text/javascript';
                s.className = 'spa-page-script';
                // Bungkus dalam fungsi anonim agar const/let/function tidak bentrok saat navigasi bolak-balik
                s.textContent = `(function() {\n${code}\n})();`;
                
                document.body.appendChild(s);
                // Bersihkan elemen tag dari DOM setelah eksekusi berjalan
                s.remove();
            } catch (err) {
                console.error('Error saat mengeksekusi skrip halaman SPA:', err);
            }
        });
    }

    /**
     * Navigasi halaman SPA via AJAX/Fetch
     */
    async function navigateTo(url, push = true) {
        try {
            setProgress(25);

            const currentMain = getMainContainer(document);
            if (currentMain) {
                currentMain.classList.add('spa-fade-enter');
            }

            setProgress(55);

            // Fetch konten HTML halaman target
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'SPA-Router'
                }
            });

            if (!response.ok) {
                throw new Error(`Gagal memuat URL (${response.status}): ${url}`);
            }

            const html = await response.text();
            setProgress(80);

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. PENTING: Update URL di History SEBELUM skrip dijalankan!
            // Agar window.location.href & window.location.search sinkron dengan query parameter baru (misal ?id=5)
            if (push) {
                window.history.pushState({ spa: true, url: url }, '', url);
            }

            // 2. Perbarui judul halaman (<title>)
            if (doc.title) {
                document.title = doc.title;
            }

            // 3. Ganti konten kontainer utama
            const newMain = getMainContainer(doc);
            if (newMain && currentMain) {
                currentMain.innerHTML = newMain.innerHTML;

                if (newMain.className && currentMain.className !== newMain.className) {
                    currentMain.className = newMain.className;
                }

                currentMain.classList.remove('spa-fade-enter');
                currentMain.classList.add('spa-fade-active');
                setTimeout(() => {
                    currentMain.classList.remove('spa-fade-active');
                }, 200);
            } else {
                // Fallback jika struktur berbeda: lakukan navigasi normal
                window.location.href = url;
                return;
            }

            // 4. Perbarui status aktif navigasi navbar
            updateActiveNav(url);

            // 5. Scroll halaman ke atas
            window.scrollTo({ top: 0, behavior: 'instant' });

            // 6. Eksekusi seluruh skrip halaman yang baru
            executePageScripts(doc);

            setProgress(100);

        } catch (err) {
            console.error('SPA navigation failed, beralih ke reload browser standar:', err);
            setProgress(100);
            window.location.href = url;
        }
    }

    /**
     * Memeriksa apakah tautan memenuhi syarat untuk navigasi SPA
     */
    function isEligibleForSpa(link) {
        if (!link || !link.getAttribute) return false;

        const href = link.getAttribute('href');
        if (!href) return false;

        // Abaikan link anchor, protokol khusus, download, atau target baru
        if (href.startsWith('#') || 
            href.startsWith('javascript:') || 
            href.startsWith('mailto:') || 
            href.startsWith('tel:') ||
            link.hasAttribute('download') ||
            link.getAttribute('target') === '_blank' ||
            link.hasAttribute('data-no-spa') ||
            href.includes('export') ||
            href.includes('../api/') ||
            href.startsWith('api/')) {
            return false;
        }

        try {
            const targetUrl = new URL(href, window.location.href);
            // Harus satu domain (same-origin)
            if (targetUrl.origin !== window.location.origin) return false;

            // Abaikan file dokumen statis
            if (/\.(css|js|png|jpg|jpeg|gif|svg|ico|pdf|csv|xlsx|zip)$/i.test(targetUrl.pathname)) {
                return false;
            }

            return true;
        } catch (e) {
            return false;
        }
    }

    // Tangkap klik pada seluruh link yang memenuhi syarat
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;

        if (isEligibleForSpa(link)) {
            e.preventDefault();
            const href = link.getAttribute('href');
            navigateTo(href, true);
        }
    });

    // Tangani tombol browser Back & Forward
    window.addEventListener('popstate', function (e) {
        navigateTo(window.location.href, false);
    });

    // Inisialisasi navbar aktif saat pertama kali dibuka
    document.addEventListener('DOMContentLoaded', function () {
        updateActiveNav(window.location.href);
    });

    // Ekspos fungsi navigasi global
    window.spaNavigate = navigateTo;

    console.log('SentiGuard MBG SPA Router initialized and ready.');
})();

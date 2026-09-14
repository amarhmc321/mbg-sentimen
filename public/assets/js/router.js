/**
 * SentiGuard MBG - SPA Client-side Router (router.js)
 * Mengelola navigasi halaman dinamis tanpa reload penuh (Single Page Application)
 * Mendukung History API (pushState & popstate), indikator loading, dan eksekusi skrip dinamis.
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
            transition: width 0.3s ease, opacity 0.4s ease;
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.7);
        }
        .spa-fade-enter {
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }
        .spa-fade-active {
            opacity: 1;
            transform: translateY(0);
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
                }, 400);
            }, 250);
        }
    }

    /**
     * Cari kontainer konten utama pada halaman (elemen dengan class container atau main)
     */
    function getMainContainer(doc = document) {
        return doc.querySelector('#app-main') ||
               doc.querySelector('.spa-container') ||
               doc.querySelector('.container.mb-5') ||
               doc.querySelector('.container.mt-5') ||
               doc.querySelector('.container');
    }

    /**
     * Perbarui status aktif pada navigasi navbar
     */
    function updateActiveNav(targetPath) {
        const currentPath = targetPath.split('?')[0].split('#')[0].split('/').pop() || 'index.php';
        document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
            const href = link.getAttribute('href') || '';
            const linkPath = href.split('?')[0].split('#')[0].split('/').pop() || 'index.php';
            
            if (currentPath === linkPath || (currentPath === '' && linkPath === 'index.php')) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            } else {
                link.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });
    }

    /**
     * Jalankan kembali script inline yang ada pada halaman yang baru dimuat
     */
    function executeScripts(container) {
        // Bersihkan chart.js canvas bila ada instance aktif
        if (window.mySentimentChart && typeof window.mySentimentChart.destroy === 'function') {
            try {
                window.mySentimentChart.destroy();
                window.mySentimentChart = null;
            } catch (e) {
                console.warn('Error destroying chart instance:', e);
            }
        }

        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            // Salin atribut
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            // Salin inline script
            newScript.textContent = oldScript.textContent;
            
            // Pasang ke DOM agar dieksekusi browser
            document.body.appendChild(newScript);
            // Bersihkan setelah eksekusi agar tidak menumpuk di body
            newScript.remove();
        });
    }

    /**
     * Muat dan ganti konten halaman via AJAX (SPA Navigation)
     */
    async function navigateTo(url, push = true) {
        try {
            setProgress(30);

            const currentContainer = getMainContainer(document);
            if (currentContainer) {
                currentContainer.classList.add('spa-fade-enter');
            }

            setProgress(60);
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'SPA-Router'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status} saat memuat ${url}`);
            }

            const html = await response.text();
            setProgress(80);

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Update Title
            if (doc.title) {
                document.title = doc.title;
            }

            // 2. Ganti konten utama
            const newContainer = getMainContainer(doc);
            if (newContainer && currentContainer) {
                currentContainer.innerHTML = newContainer.innerHTML;
                
                // Salin atribut class jika berbeda
                if (newContainer.className && currentContainer.className !== newContainer.className) {
                    currentContainer.className = newContainer.className;
                }

                // Efek transisi halus
                currentContainer.classList.remove('spa-fade-enter');
                currentContainer.classList.add('spa-fade-active');
                setTimeout(() => {
                    currentContainer.classList.remove('spa-fade-active');
                }, 300);

                // 3. Jalankan script pada halaman baru
                executeScripts(newContainer);
            } else {
                // Fallback jika struktur berbeda: reload halaman biasa
                window.location.href = url;
                return;
            }

            // 4. Update URL History
            if (push) {
                window.history.pushState({ spa: true, url: url }, '', url);
            }

            // 5. Update status navbar aktif
            updateActiveNav(url);

            // 6. Scroll ke atas
            window.scrollTo({ top: 0, behavior: 'smooth' });

            setProgress(100);

        } catch (err) {
            console.error('SPA Router navigation failed, fallback to standard reload:', err);
            setProgress(100);
            window.location.href = url;
        }
    }

    /**
     * Cek apakah URL valid untuk ditangani oleh SPA Router
     */
    function isEligibleForSpa(link) {
        if (!link || !link.getAttribute) return false;
        
        const href = link.getAttribute('href');
        if (!href) return false;

        // Abaikan link khusus
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

        // Periksa origin (harus same-origin)
        try {
            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) return false;
            
            // Cek apakah menuju file aset fisik
            if (/\.(css|js|png|jpg|jpeg|gif|svg|ico|pdf|csv|xlsx|zip)$/i.test(url.pathname)) {
                return false;
            }

            return true;
        } catch (e) {
            return false;
        }
    }

    /**
     * Tangkap semua event click pada tautan yang memenuhi syarat
     */
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;

        if (isEligibleForSpa(link)) {
            e.preventDefault();
            const href = link.getAttribute('href');
            navigateTo(href, true);
        }
    });

    /**
     * Tangani tombol Back / Forward browser
     */
    window.addEventListener('popstate', function (e) {
        navigateTo(window.location.href, false);
    });

    // Inisialisasi navbar aktif saat pertama kali dimuat
    document.addEventListener('DOMContentLoaded', function () {
        updateActiveNav(window.location.href);
    });

    // Ekspos fungsi global untuk kemudahan akses dari script lain
    window.spaNavigate = navigateTo;

    console.log('SentiGuard SPA Router active.');
})();

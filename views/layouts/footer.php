
	</main><!-- /main-content -->

</div><!-- /app-layout -->

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

<script>
    // ── Sidebar hamburger mobile ──────────────────────
    function openSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }

    // Fermer avec la touche Échap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });

    // ── PWA : enregistrement du service worker ───────────
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function (err) {
                console.warn('Service worker non enregistré :', err);
            });
        });
    }

    // ── Bannière de connectivité + file d'attente ─────────
    (function () {
        var banner = document.getElementById('connectivity-banner');
        var text   = document.getElementById('connectivity-banner-text');
        if (!banner) return;

        var wasOffline = false;
        var pendingCount = 0;

        function render() {
            if (pendingCount > 0) {
                banner.classList.remove('back-online');
                banner.classList.add('show');
                text.textContent = pendingCount +
                    ' élément(s) en attente de synchronisation' +
                    (navigator.onLine ? ' — synchronisation en cours...' : ' — hors ligne.');
                return;
            }
            if (!navigator.onLine) {
                banner.classList.remove('back-online');
                banner.classList.add('show');
                text.textContent = 'Hors ligne — certaines actions nécessitent une connexion.';
                return;
            }
            if (wasOffline) {
                banner.classList.add('show', 'back-online');
                text.textContent = 'Connexion rétablie.';
                setTimeout(function () {
                    banner.classList.remove('show', 'back-online');
                }, 3000);
            } else {
                banner.classList.remove('show', 'back-online');
            }
            wasOffline = false;
        }

        async function refreshPendingCount() {
            try {
                pendingCount = (typeof OfflineOutbox !== 'undefined')
                    ? await OfflineOutbox.count()
                    : 0;
            } catch (err) {
                pendingCount = 0;
            }
            render();
        }

        window.addEventListener('online', function () { render(); });
        window.addEventListener('offline', function () { wasOffline = true; render(); });
        window.addEventListener('outbox:updated', refreshPendingCount);

        refreshPendingCount();
    })();
</script>

</body>
</html>
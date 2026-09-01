
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
        var banner   = document.getElementById('connectivity-banner');
        var text     = document.getElementById('connectivity-banner-text');
        var actions  = document.getElementById('connectivity-banner-actions');
        var btnSync  = document.getElementById('conn-btn-sync');
        var btnClear = document.getElementById('conn-btn-clear');
        if (!banner) return;

        var wasOffline = false;
        var pendingCount = 0;

        function render() {
            if (actions) actions.style.display = 'none';

            if (pendingCount > 0) {
                banner.classList.remove('back-online');
                banner.classList.add('show');
                text.textContent = pendingCount +
                    ' élément(s) en attente de synchronisation' +
                    (navigator.onLine ? ' — clique Réessayer ou Vider' : ' — hors ligne.');
                if (actions) actions.style.display = 'inline-flex';
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
                if (typeof LocalDB !== 'undefined' && LocalDB.count) {
                    pendingCount = await LocalDB.count();
                } else if (typeof OfflineOutbox !== 'undefined') {
                    pendingCount = await OfflineOutbox.count();
                } else {
                    pendingCount = 0;
                }
            } catch (err) {
                pendingCount = 0;
            }
            render();
        }

        if (btnSync) {
            btnSync.addEventListener('click', async function () {
                if (typeof LocalDB === 'undefined') return;
                btnSync.disabled = true;
                btnSync.textContent = '...';
                try {
                    var result = await LocalDB.syncAll(true);
                    if (result.synced === 0 && result.failed > 0) {
                        alert('Synchronisation échouée. Vérifie que tu es bien connecté, puis utilise « Vider » si l\'action a déjà été faite à la main.');
                    }
                } finally {
                    btnSync.disabled = false;
                    btnSync.textContent = 'Réessayer';
                    refreshPendingCount();
                }
            });
        }

        if (btnClear) {
            btnClear.addEventListener('click', async function () {
                if (!confirm('Supprimer toutes les actions en attente ? Elles ne seront pas envoyées au serveur.')) return;
                if (typeof LocalDB !== 'undefined' && LocalDB.clearOutbox) {
                    await LocalDB.clearOutbox();
                }
                refreshPendingCount();
            });
        }

        window.addEventListener('online', function () {
            render();
            if (typeof LocalDB !== 'undefined') {
                setTimeout(function () { LocalDB.syncAll(false); }, 500);
            }
        });
        window.addEventListener('offline', function () { wasOffline = true; render(); });
        window.addEventListener('outbox:updated', refreshPendingCount);

        refreshPendingCount();

        // Au chargement, si en ligne et file non vide → tenter une sync
        if (navigator.onLine && typeof LocalDB !== 'undefined') {
            setTimeout(function () { LocalDB.syncAll(false); }, 1200);
        }
    })();
</script>

</body>
</html>
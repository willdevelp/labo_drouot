/**
 * PwaInstallPrompt — Laboratoire Drouot
 *
 * Affiche un popup personnalisé proposant d'installer l'application,
 * plutôt que de compter uniquement sur le mini-bandeau natif du
 * navigateur (que beaucoup de gens ignorent ou ne voient jamais).
 *
 * - Chrome / Edge / Android : écoute l'événement `beforeinstallprompt`,
 *   l'intercepte, et propose notre propre bouton "Installer".
 * - iOS Safari : ne supporte pas `beforeinstallprompt`. On affiche à la
 *   place une courte instruction ("Partager → Sur l'écran d'accueil"),
 *   uniquement si l'app n'est pas déjà installée.
 * - Ne réaffiche pas le popup pendant 7 jours si l'utilisateur l'a
 *   fermé, et jamais si l'app est déjà installée (mode standalone).
 */
(function () {
    const DISMISS_KEY = 'pwaInstallDismissedAt';
    const DISMISS_DAYS = 7;

    // Injecte les animations CSS et les styles nécessaires
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pwaBannerIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes pwaFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true; // iOS
    }

    function wasRecentlyDismissed() {
        const raw = localStorage.getItem(DISMISS_KEY);
        if (!raw) return false;
        const elapsedDays = (Date.now() - Number(raw)) / (1000 * 60 * 60 * 24);
        return elapsedDays < DISMISS_DAYS;
    }

    function markDismissed() {
        try {
            localStorage.setItem(DISMISS_KEY, String(Date.now()));
        } catch (err) { /* stockage indisponible */ }
    }

    function isIOS() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }

    // Modal d'instructions spécifique à iOS au clic sur le bouton
    function showIosInstructionsModal() {
        const modal = document.createElement('div');
        modal.id = 'pwa-ios-modal';
        modal.style.cssText =
            'position:fixed;top:0;left:0;width:100%;height:100%;' +
            'background:rgba(0,0,0,0.5);z-index:6000;display:flex;' +
            'align-items:center;justify-content:center;padding:20px;' +
            'box-sizing:border-box;animation:pwaFadeIn .2s ease;';

        modal.innerHTML = `
            <div style="background:#fff;border-radius:16px;max-width:360px;width:100%;
                        padding:20px;font-family:'DM Sans',-apple-system,sans-serif;
                        box-shadow:0 10px 30px rgba(0,0,0,0.2);position:relative;">
                <div style="font-weight:700;font-size:1rem;color:#1a2340;margin-bottom:12px;text-align:center;">
                    Installer sur iOS Safari
                </div>
                <ol style="font-size:.85rem;color:#4a5568;line-height:1.6;padding-left:20px;margin:0 0 16px 0;">
                    <li style="margin-bottom:8px;">Appuyez sur le bouton <strong>Partager</strong> <i class="fas fa-share-from-square" style="color:#1E88E5;"></i> dans la barre Safari.</li>
                    <li style="margin-bottom:8px;">Faites défiler le menu vers le bas.</li>
                    <li>Sélectionnez <strong>« Sur l'écran d'accueil »</strong> <i class="fas fa-plus-square" style="color:#1E88E5;"></i>.</li>
                </ol>
                <button id="pwa-ios-modal-close" style="width:100%;background:#1E88E5;color:#fff;
                        border:none;border-radius:8px;padding:10px;font-size:.85rem;
                        font-weight:600;cursor:pointer;">
                    J'ai compris
                </button>
            </div>
        `;

        document.body.appendChild(modal);

        document.getElementById('pwa-ios-modal-close').addEventListener('click', () => {
            modal.remove();
        });
    }

    function buildBanner({ message, primaryLabel, onPrimary }) {
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.style.cssText =
            'position:fixed;left:16px;right:16px;bottom:16px;z-index:5000;' +
            'max-width:420px;margin:0 auto;background:#fff;' +
            'border:1px solid #dde3f0;border-radius:14px;' +
            'box-shadow:0 8px 28px rgba(13,71,161,.16);' +
            'padding:16px;display:flex;gap:12px;align-items:flex-start;' +
            'font-family:"DM Sans",-apple-system,sans-serif;' +
            'animation:pwaBannerIn .25s ease;';

        banner.innerHTML = `
            <div style="width:40px;height:40px;flex-shrink:0;border-radius:10px;
                        background:#1E88E5;display:flex;align-items:center;
                        justify-content:center;">
                <i class="fas fa-flask" style="color:#fff;font-size:16px;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:600;font-size:.9rem;color:#1a2340;margin-bottom:2px;">
                    Installer Labo Drouot
                </div>
                <div style="font-size:.8rem;color:#6b7a99;line-height:1.4;">
                    ${message}
                </div>
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <button id="pwa-install-btn-yes" style="background:#1E88E5;color:#fff;
                            border:none;border-radius:8px;padding:7px 14px;font-size:.8rem;
                            font-weight:600;cursor:pointer;font-family:inherit;">
                        ${primaryLabel}
                    </button>
                    <button id="pwa-install-btn-no" style="background:transparent;color:#6b7a99;
                            border:none;padding:7px 10px;font-size:.8rem;cursor:pointer;
                            font-family:inherit;">
                        Plus tard
                    </button>
                </div>
            </div>
            <button id="pwa-install-btn-close" aria-label="Fermer"
                    style="background:none;border:none;color:#9aa5bd;cursor:pointer;
                           font-size:.9rem;padding:2px;line-height:1;">
                <i class="fas fa-xmark"></i>
            </button>
        `;

        document.body.appendChild(banner);

        const close = () => {
            markDismissed();
            banner.remove();
        };

        document.getElementById('pwa-install-btn-yes').addEventListener('click', () => {
            onPrimary(close);
        });
        document.getElementById('pwa-install-btn-no').addEventListener('click', close);
        document.getElementById('pwa-install-btn-close').addEventListener('click', close);
    }

    if (isStandalone() || wasRecentlyDismissed()) return;

    let deferredPrompt = null;

    // Chrome / Android / Windows / Mac
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        buildBanner({
            message: 'Ajoute l\'application à ton écran d\'accueil pour y accéder plus vite, même hors ligne.',
            primaryLabel: 'Installer',
            onPrimary: async (closeBanner) => {
                if (!deferredPrompt) return;
                deferredPrompt.prompt();
                const choice = await deferredPrompt.userChoice;
                if (choice.outcome === 'accepted') {
                    closeBanner();
                }
                deferredPrompt = null;
            },
        });
    });

    window.addEventListener('appinstalled', () => {
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.remove();
        markDismissed();
    });

    // iOS Safari
    if (isIOS() && !window.navigator.standalone) {
        setTimeout(() => {
            if (document.getElementById('pwa-install-banner')) return;
            buildBanner({
                message: 'Ajoute l\'application sur ton écran d\'accueil pour une expérience optimale.',
                primaryLabel: 'Voir comment faire',
                onPrimary: (closeBanner) => {
                    closeBanner();
                    showIosInstructionsModal();
                },
            });
        }, 4000);
    }
})();
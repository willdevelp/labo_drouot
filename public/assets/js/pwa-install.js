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

    // Injecte l'animation d'apparition (le script n'a pas de fichier CSS dédié)
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pwaBannerIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
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
        } catch (err) { /* stockage indisponible, tant pis */ }
    }

    function isIOS() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    }

    function buildBanner({ message, primaryLabel, onPrimary, showPrimary }) {
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
                    ${showPrimary ? `
                        <button id="pwa-install-btn-yes" style="background:#1E88E5;color:#fff;
                                border:none;border-radius:8px;padding:7px 14px;font-size:.8rem;
                                font-weight:600;cursor:pointer;font-family:inherit;">
                            ${primaryLabel}
                        </button>
                    ` : ''}
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

        if (showPrimary) {
            document.getElementById('pwa-install-btn-yes').addEventListener('click', () => {
                banner.remove();
                onPrimary();
            });
        }
        document.getElementById('pwa-install-btn-no').addEventListener('click', close);
        document.getElementById('pwa-install-btn-close').addEventListener('click', close);
    }

    if (isStandalone() || wasRecentlyDismissed()) return;

    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        buildBanner({
            message: 'Ajoute l\'application à ton écran d\'accueil pour y accéder plus vite, même hors ligne.',
            primaryLabel: 'Installer',
            showPrimary: true,
            onPrimary: async () => {
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
            },
        });
    });

    window.addEventListener('appinstalled', () => {
        const banner = document.getElementById('pwa-install-banner');
        if (banner) banner.remove();
        markDismissed();
    });

    // iOS Safari : pas d'événement beforeinstallprompt, on affiche une
    // instruction manuelle à la place (après un court délai, pour ne pas
    // asséner ça dès la première seconde de visite).
    if (isIOS() && !window.navigator.standalone) {
        setTimeout(() => {
            if (document.getElementById('pwa-install-banner')) return;
            buildBanner({
                message: 'Appuie sur le bouton Partager de Safari, puis "Sur l\'écran d\'accueil".',
                showPrimary: false,
            });
        }, 4000);
    }
})();

<!-- PWA Install Prompt Banner -->
<div id="pwaInstallBanner" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:9999; background:linear-gradient(135deg, #1a365d, #2c5282); color:#fff; padding:0; box-shadow:0 -4px 20px rgba(0,0,0,0.3); font-family:'Inter',sans-serif;">
    <div style="max-width:900px; margin:0 auto; padding:1rem 1.5rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
        <div style="width:48px; height:48px; border-radius:12px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <img src="/holy-trinity/assets/icons/icon-96x96.png" alt="HTP" style="width:36px; height:36px; border-radius:8px;">
        </div>
        <div style="flex:1; min-width:200px;">
            <strong style="font-size:0.95rem; display:block;">Install Holy Trinity Parish App</strong>
            <span style="font-size:0.8rem; opacity:0.8;">Quick access from your home screen — works offline!</span>
        </div>
        <div style="display:flex; gap:0.5rem; flex-shrink:0;">
            <button id="pwaInstallBtn" onclick="installPWA()" style="padding:0.5rem 1.25rem; background:#d4a843; color:#0f172a; border:none; border-radius:6px; font-weight:700; font-size:0.85rem; cursor:pointer; transition:all 0.2s;">
                <i class="fas fa-download"></i> Install
            </button>
            <button onclick="dismissInstall()" style="padding:0.5rem 0.75rem; background:rgba(255,255,255,0.15); color:#fff; border:none; border-radius:6px; font-size:0.85rem; cursor:pointer; transition:all 0.2s;">
                Later
            </button>
        </div>
    </div>
</div>

<!-- iOS Install Instructions Modal -->
<div id="iosInstallModal" style="display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; border-radius:16px; max-width:380px; width:100%; padding:2rem; text-align:center; position:relative;">
        <button onclick="document.getElementById('iosInstallModal').style.display='none'" style="position:absolute; top:0.75rem; right:0.75rem; background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;">&times;</button>
        <img src="/holy-trinity/assets/icons/icon-96x96.png" alt="HTP" style="width:64px; height:64px; border-radius:16px; margin-bottom:1rem;">
        <h3 style="font-size:1.1rem; color:#1a365d; margin-bottom:0.5rem;">Install HTP Kabwe</h3>
        <p style="color:#64748b; font-size:0.85rem; margin-bottom:1.5rem;">Add this app to your home screen for quick access</p>
        <div style="text-align:left; font-size:0.9rem; color:#334155;">
            <div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; background:#f8fafc; border-radius:8px; margin-bottom:0.5rem;">
                <span style="background:#1a365d; color:#fff; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">1</span>
                <span>Tap the <strong>Share</strong> button <i class="fas fa-share-from-square" style="color:#007aff;"></i> at the bottom of Safari</span>
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; background:#f8fafc; border-radius:8px; margin-bottom:0.5rem;">
                <span style="background:#1a365d; color:#fff; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">2</span>
                <span>Scroll down and tap <strong>"Add to Home Screen"</strong> <i class="fas fa-plus-square" style="color:#007aff;"></i></span>
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem; background:#f8fafc; border-radius:8px;">
                <span style="background:#1a365d; color:#fff; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem; flex-shrink:0;">3</span>
                <span>Tap <strong>"Add"</strong> to install the app</span>
            </div>
        </div>
    </div>
</div>

<script>
// Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/holy-trinity/sw.js', { scope: '/holy-trinity/' })
            .then(reg => {
                console.log('[PWA] Service Worker registered, scope:', reg.scope);
                // Check for updates periodically
                setInterval(() => reg.update(), 60 * 60 * 1000); // every hour
            })
            .catch(err => console.log('[PWA] SW registration failed:', err));
    });
}

// Install Prompt
let deferredPrompt = null;
const installBanner = document.getElementById('pwaInstallBanner');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // Show install banner if not dismissed recently
    if (!localStorage.getItem('pwa-install-dismissed') || 
        Date.now() - parseInt(localStorage.getItem('pwa-install-dismissed')) > 7 * 24 * 60 * 60 * 1000) {
        installBanner.style.display = 'block';
    }
});

function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(result => {
            if (result.outcome === 'accepted') {
                console.log('[PWA] App installed');
            }
            deferredPrompt = null;
            installBanner.style.display = 'none';
        });
    }
}

function dismissInstall() {
    installBanner.style.display = 'none';
    localStorage.setItem('pwa-install-dismissed', Date.now().toString());
}

// iOS detection - show manual instructions
window.addEventListener('load', () => {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.navigator.standalone === true;
    
    if (isIOS && !isStandalone) {
        if (!localStorage.getItem('ios-install-shown') ||
            Date.now() - parseInt(localStorage.getItem('ios-install-shown')) > 14 * 24 * 60 * 60 * 1000) {
            setTimeout(() => {
                document.getElementById('iosInstallModal').style.display = 'flex';
                localStorage.setItem('ios-install-shown', Date.now().toString());
            }, 3000);
        }
    }
});

// App installed event
window.addEventListener('appinstalled', () => {
    console.log('[PWA] App installed successfully');
    installBanner.style.display = 'none';
    deferredPrompt = null;
});
</script>

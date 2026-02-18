<!-- PWA Service Worker Registration & Install Prompt -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/holy-trinity/sw.js', { scope: '/holy-trinity/' })
            .then(reg => console.log('[PWA] SW registered'))
            .catch(err => console.log('[PWA] SW failed:', err));
    });
}
</script>

// =====================================================
// BookEase Lab — Client-side JavaScript
// VULN-06: Price recalculation happens client-side only
// =====================================================

document.addEventListener('DOMContentLoaded', () => {

    // Auto-dismiss alerts after 6 seconds
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 6000);
    });

    // Highlight active nav link
    const path = window.location.pathname;
    document.querySelectorAll('.nav-links a').forEach(a => {
        if (a.getAttribute('href') && path.startsWith(a.getAttribute('href').split('?')[0])) {
            a.style.color = 'var(--brand-light)';
        }
    });

    // ---- VULN-06: CLIENT-SIDE PRICE CALCULATION ----
    // This is the "authoritative" price calculation in the browser.
    // The server trusts the total_price hidden field — never recalculates.
    // Attacker can open DevTools → Elements or intercept with Burp Suite
    // and change total_price to 0.01 before submitting.

    // Copy-to-clipboard for code snippets
    document.querySelectorAll('code').forEach(el => {
        el.style.cursor = 'pointer';
        el.title = 'Click to copy';
        el.addEventListener('click', () => {
            navigator.clipboard.writeText(el.textContent).then(() => {
                const orig = el.style.background;
                el.style.background = 'rgba(34,197,94,.3)';
                setTimeout(() => el.style.background = orig, 800);
            });
        });
    });

    // Confirm on all danger buttons that are links (without inline onclick)
    document.querySelectorAll('a.btn-danger:not([onclick])').forEach(a => {
        a.addEventListener('click', e => {
            if (!confirm('Are you sure? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Table row click-through to invoice (where applicable)
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => window.location.href = row.dataset.href);
    });

});

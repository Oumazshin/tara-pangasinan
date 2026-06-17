/**
 * components.js
 * Dynamically loads the shared Navbar and Footer HTML components.
 */
document.addEventListener('DOMContentLoaded', async () => {
    const navPlaceholder = document.getElementById('navbar-placeholder');
    const footPlaceholder = document.getElementById('footer-placeholder');

    try {
        const [navHtml, footHtml] = await Promise.all([
            navPlaceholder ? fetch('components/navbar.html').then(r => r.text()) : Promise.resolve(''),
            footPlaceholder ? fetch('components/footer.html').then(r => r.text()) : Promise.resolve('')
        ]);

        if (navPlaceholder) navPlaceholder.innerHTML = navHtml;
        if (footPlaceholder) footPlaceholder.innerHTML = footHtml;

        // Re-initialize navbar logic now that the HTML is injected
        if (typeof window.initNavbar === 'function') {
            window.initNavbar();
        }
    } catch (err) {
        console.error('Failed to load components. If you are viewing this via file://, CORS will block the fetch.', err);
    }
});

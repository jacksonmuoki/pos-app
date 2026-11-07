// Intercepts any element with data-route="..." and sends that route
// internally via POST, so the real path never appears in the link,
// status bar, or address bar.

document.addEventListener('click', function (e) {
    const el = e.target.closest('[data-route]');
    if (!el) return;
    e.preventDefault();
    goToRoute(el.getAttribute('data-route'));
});

document.addEventListener('submit', function (e) {
    const form = e.target.closest('form[data-route]');
    if (!form) return;
    e.preventDefault();
    const formData = new URLSearchParams(new FormData(form));
    goToRoute(form.getAttribute('data-route'), formData);
});

function goToRoute(route, extraParams) {
    const body = new URLSearchParams();
    body.set('route', route);
    if (extraParams) {
        for (const [key, value] of extraParams.entries()) {
            body.set(key, value);
        }
    }

    fetch('/intensify/public/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
        .then(res => res.text())
        .then(html => {
            const container = document.getElementById('app-content');
            if (container) {
                container.innerHTML = html;
            } else {
                document.body.innerHTML = html;
            }
        })
        .catch(err => console.error('Route error:', err));
}

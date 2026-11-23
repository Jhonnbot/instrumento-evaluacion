// Mejora visual: el servidor sigue controlando autenticación, validaciones y cambios.
(() => {
    const menu = document.querySelector('[data-coco-menu]');
    const side = document.querySelector('[data-coco-sidebar]');
    const backdrop = document.querySelector('[data-coco-backdrop]');
    const close = () => { side?.classList.remove('is-open'); backdrop?.classList.remove('is-open'); menu?.setAttribute('aria-expanded','false'); };
    menu?.addEventListener('click', () => {
        const open = !side.classList.contains('is-open');
        side.classList.toggle('is-open', open);
        backdrop?.classList.toggle('is-open', open);
        menu.setAttribute('aria-expanded', String(open));
    });
    backdrop?.addEventListener('click', close);
    side?.querySelectorAll('a').forEach(link => link.addEventListener('click', close));
    document.addEventListener('keydown', e => { if(e.key === 'Escape') close(); });
})();

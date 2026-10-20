// Anti-robot ALTCHA (preuve de travail, sans cookie), en français.
import 'altcha';
import 'altcha/i18n/fr-fr';

/*
 * Raccourci Q : ouvre l'ajout rapide depuis n'importe quelle page,
 * sauf pendant la saisie dans un champ.
 */
document.addEventListener('keydown', (event) => {
    if (event.key !== 'q' && event.key !== 'Q') {
        return;
    }

    if (event.metaKey || event.ctrlKey || event.altKey || event.isComposing) {
        return;
    }

    const target = event.target;
    if (target instanceof HTMLElement && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))) {
        return;
    }

    const url = document.body.dataset.proposeUrl ?? '/proposer';
    if (window.location.pathname === new URL(url, window.location.origin).pathname) {
        return;
    }

    event.preventDefault();
    window.Livewire ? window.Livewire.navigate(url) : (window.location.href = url);
});

(() => {
    'use strict';

    let hideTimer = null;
    let showFrame = null;

    function createLoader() {
        if (!document.body) return null;

        const loader = document.createElement('div');
        const dots = Array.from({ length: 12 }, (_, index) => {
            const angle = index * 30;
            const delay = (index * -0.08).toFixed(2);
            return `<span class="pqr-orb-dot" style="--orb-angle:${angle}deg;--orb-delay:${delay}s"></span>`;
        }).join('');

        loader.id = 'pqrPageLoader';
        loader.className = 'pqr-page-loader';
        loader.hidden = true;
        loader.setAttribute('aria-hidden', 'true');
        loader.innerHTML = `
            <div class="pqr-loader-card" role="status" aria-live="polite">
                <div class="pqr-thinking-orb" aria-hidden="true">${dots}</div>
                <div class="pqr-loader-message">Cargando...</div>
                <div class="pqr-loader-detail"></div>
                <div class="pqr-loader-progress" aria-hidden="true">
                    <div class="pqr-loader-progress-track">
                        <div class="pqr-loader-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                    </div>
                    <div class="pqr-loader-progress-label">0%</div>
                </div>
            </div>
        `;
        document.body.appendChild(loader);
        return loader;
    }

    function getLoader() {
        return document.getElementById('pqrPageLoader') || createLoader();
    }

    function show(message = 'Cargando...') {
        const loader = getLoader();
        if (!loader) return null;

        clearTimeout(hideTimer);
        if (showFrame !== null) {
            cancelAnimationFrame(showFrame);
        }

        loader.querySelector('.pqr-loader-message').textContent = message;
        loader.querySelector('.pqr-loader-detail').textContent = '';
        loader.hidden = false;
        loader.setAttribute('aria-hidden', 'false');
        document.body.setAttribute('aria-busy', 'true');
        showFrame = requestAnimationFrame(() => loader.classList.add('is-visible'));
        return loader;
    }

    function hide() {
        const loader = getLoader();
        if (!loader) return;

        if (showFrame !== null) {
            cancelAnimationFrame(showFrame);
            showFrame = null;
        }

        loader.classList.remove('is-visible', 'has-progress');
        loader.setAttribute('aria-hidden', 'true');
        document.body.removeAttribute('aria-busy');

        const progress = loader.querySelector('.pqr-loader-progress');
        const progressBar = loader.querySelector('.pqr-loader-progress-bar');
        const progressLabel = loader.querySelector('.pqr-loader-progress-label');
        progress.setAttribute('aria-hidden', 'true');
        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', '0');
        progressLabel.textContent = '0%';

        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            loader.hidden = true;
        }, 220);
    }

    function setProgress(value, message = 'Subiendo archivos...') {
        const loader = show(message);
        if (!loader) return;

        const percentage = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
        const progress = loader.querySelector('.pqr-loader-progress');
        const progressBar = loader.querySelector('.pqr-loader-progress-bar');
        const progressLabel = loader.querySelector('.pqr-loader-progress-label');

        loader.classList.add('has-progress');
        progress.setAttribute('aria-hidden', 'false');
        progressBar.style.width = `${percentage}%`;
        progressBar.setAttribute('aria-valuenow', String(percentage));
        progressLabel.textContent = `${percentage}%`;
    }

    function setButtonLoading(button, label = 'Procesando...') {
        if (!button || button.dataset.pqrLoading === 'true') return;

        button.dataset.pqrLoading = 'true';
        button.dataset.pqrOriginalHtml = button.innerHTML;
        button.dataset.pqrOriginalValue = button.value || '';
        button.dataset.pqrWasDisabled = button.disabled ? 'true' : 'false';
        button.disabled = true;
        button.classList.add('pqr-loading-button');

        if (button.tagName === 'INPUT') {
            button.value = label;
            return;
        }

        button.replaceChildren();
        const spinner = document.createElement('span');
        spinner.className = 'pqr-button-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        const text = document.createElement('span');
        text.textContent = label;
        button.append(spinner, text);
    }

    function restoreButton(button) {
        if (!button || button.dataset.pqrLoading !== 'true') return;

        if (button.tagName === 'INPUT') {
            button.value = button.dataset.pqrOriginalValue || '';
        } else {
            button.innerHTML = button.dataset.pqrOriginalHtml || '';
        }
        button.disabled = button.dataset.pqrWasDisabled === 'true';
        button.classList.remove('pqr-loading-button');
        delete button.dataset.pqrLoading;
    }

    function isInternalNavigation(event, link) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return false;
        }

        if (link.hasAttribute('download') || link.target === '_blank' || link.target === '_parent' || link.target === '_top') {
            return false;
        }

        if (link.dataset.loadingSkip === 'true' || link.getAttribute('aria-disabled') === 'true') {
            return false;
        }

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#')) return false;

        let url;
        try {
            url = new URL(href, window.location.href);
        } catch (error) {
            return false;
        }

        if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) {
            return false;
        }

        if (url.pathname.endsWith('/download_file.php')) return false;
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return false;

        return true;
    }

    document.addEventListener('click', event => {
        if (!(event.target instanceof Element)) return;

        const link = event.target.closest('a[href]');
        if (link && isInternalNavigation(event, link)) {
            show(link.dataset.loadingMessage || 'Cargando página...');
        }
    });

    document.addEventListener('submit', event => {
        if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;

        const form = event.target;
        if (form.dataset.loadingSkip === 'true' || form.dataset.loadingHandled === 'true') return;

        const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitter) {
            setButtonLoading(submitter, form.querySelector('input[type="file"]') ? 'Subiendo...' : 'Procesando...');
        }

        show(form.querySelector('input[type="file"]') ? 'Subiendo archivos...' : 'Procesando solicitud...');
    });

    window.addEventListener('pageshow', hide);
    document.addEventListener('DOMContentLoaded', getLoader);

    window.PqrLoading = {
        show,
        hide,
        setProgress,
        setButtonLoading,
        restoreButton
    };
})();

(function () {
    'use strict';

    if (window.AustraliaConfirmModalLoaded) {
        return;
    }

    window.AustraliaConfirmModalLoaded = true;

    const STYLE_ID = 'ag-confirm-modal-style';
    const OVERLAY_ID = 'ag-confirm-modal-overlay';

    function injectStyles() {
        if (document.getElementById(STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = STYLE_ID;

        style.textContent = `
            .ag-confirm-overlay {
                position: fixed;
                inset: 0;
                z-index: 2147483000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 22px;
                box-sizing: border-box;
                background: rgba(0, 0, 0, .72);
                backdrop-filter: blur(5px);
            }

            .ag-confirm-overlay.open {
                display: flex;
            }

            .ag-confirm-modal {
                width: min(560px, 100%);
                overflow: hidden;
                border: 1px solid rgba(244,179,35,.36);
                border-radius: 16px;
                background:
                    linear-gradient(180deg, rgba(18,31,38,.98), rgba(7,12,15,.99));
                box-shadow:
                    0 28px 90px rgba(0,0,0,.62),
                    0 0 0 1px rgba(255,255,255,.025) inset;
                color: #f5f7f8;
                transform: translateY(8px) scale(.985);
                opacity: 0;
                transition: transform .14s ease, opacity .14s ease;
            }

            .ag-confirm-overlay.open .ag-confirm-modal {
                transform: translateY(0) scale(1);
                opacity: 1;
            }

            .ag-confirm-head {
                display: flex;
                gap: 14px;
                align-items: center;
                padding: 20px 22px 13px;
            }

            .ag-confirm-symbol {
                flex: 0 0 46px;
                width: 46px;
                height: 46px;
                display: grid;
                place-items: center;
                border-radius: 13px;
                border: 1px solid rgba(244,179,35,.34);
                background: rgba(244,179,35,.11);
                color: #f4b323;
                font-size: 26px;
                font-weight: 1000;
                line-height: 1;
            }

            .ag-confirm-kicker {
                color: #f4b323;
                font-size: 11px;
                font-weight: 1000;
                letter-spacing: .13em;
                text-transform: uppercase;
            }

            .ag-confirm-title {
                margin-top: 2px;
                color: #ffffff;
                font-size: 23px;
                font-weight: 1000;
                line-height: 1.1;
            }

            .ag-confirm-message {
                padding: 3px 22px 20px;
                color: #cbd8de;
                font-size: 16px;
                line-height: 1.52;
                white-space: pre-line;
            }

            .ag-confirm-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                padding: 15px 22px 20px;
                border-top: 1px solid rgba(255,255,255,.08);
                background: rgba(0,0,0,.12);
            }

            .ag-confirm-button {
                min-height: 44px;
                padding: 0 19px;
                border-radius: 9px;
                border: 1px solid rgba(255,255,255,.14);
                cursor: pointer;
                font: inherit;
                font-weight: 1000;
            }

            .ag-confirm-cancel {
                color: #edf3f5;
                background: #17232a;
            }

            .ag-confirm-cancel:hover {
                background: #203039;
            }

            .ag-confirm-ok {
                border-color: #f4b323;
                color: #091015;
                background: #f4b323;
            }

            .ag-confirm-ok:hover {
                filter: brightness(1.06);
            }

            body.ag-confirm-no-scroll {
                overflow: hidden !important;
            }

            @media (max-width: 520px) {
                .ag-confirm-actions {
                    flex-direction: column-reverse;
                }

                .ag-confirm-button {
                    width: 100%;
                }

                .ag-confirm-head,
                .ag-confirm-message,
                .ag-confirm-actions {
                    padding-left: 17px;
                    padding-right: 17px;
                }
            }
        `;

        document.head.appendChild(style);
    }

    function buildModal() {
        let overlay = document.getElementById(OVERLAY_ID);

        if (overlay) {
            return overlay;
        }

        overlay = document.createElement('div');
        overlay.id = OVERLAY_ID;
        overlay.className = 'ag-confirm-overlay';
        overlay.setAttribute('role', 'presentation');

        overlay.innerHTML = `
            <div
                class="ag-confirm-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ag-confirm-title"
                aria-describedby="ag-confirm-message"
            >
                <div class="ag-confirm-head">
                    <div class="ag-confirm-symbol" aria-hidden="true">!</div>

                    <div>
                        <div class="ag-confirm-kicker">Grid</div>
                        <div class="ag-confirm-title" id="ag-confirm-title">
                            Confirm Action
                        </div>
                    </div>
                </div>

                <div
                    class="ag-confirm-message"
                    id="ag-confirm-message"
                ></div>

                <div class="ag-confirm-actions">
                    <button
                        type="button"
                        class="ag-confirm-button ag-confirm-cancel"
                        data-ag-confirm-cancel
                    >
                        CANCEL
                    </button>

                    <button
                        type="button"
                        class="ag-confirm-button ag-confirm-ok"
                        data-ag-confirm-ok
                    >
                        CONTINUE
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        return overlay;
    }

    let activeResolver = null;
    let lastFocused = null;

    function closeModal(result) {
        const overlay = document.getElementById(OVERLAY_ID);

        if (!overlay) {
            return;
        }

        overlay.classList.remove('open');
        document.body.classList.remove('ag-confirm-no-scroll');

        const resolver = activeResolver;
        activeResolver = null;

        if (
            lastFocused &&
            typeof lastFocused.focus === 'function'
        ) {
            try {
                lastFocused.focus();
            } catch (_) {
            }
        }

        lastFocused = null;

        if (resolver) {
            resolver(Boolean(result));
        }
    }

    function showModal(message, options) {
        injectStyles();

        const overlay = buildModal();
        const title = overlay.querySelector('#ag-confirm-title');
        const messageBox = overlay.querySelector('#ag-confirm-message');
        const ok = overlay.querySelector('[data-ag-confirm-ok]');
        const cancel = overlay.querySelector('[data-ag-confirm-cancel]');

        const opts = options || {};

        title.textContent =
            opts.title || 'Confirm Action';

        messageBox.textContent =
            String(message || 'Are you sure you want to continue?');

        ok.textContent =
            opts.confirmText || 'CONTINUE';

        cancel.textContent =
            opts.cancelText || 'CANCEL';

        lastFocused =
            document.activeElement;

        overlay.classList.add('open');
        document.body.classList.add('ag-confirm-no-scroll');

        window.setTimeout(function () {
            cancel.focus();
        }, 0);

        return new Promise(function (resolve) {
            activeResolver = resolve;
        });
    }

    function decodeHtmlAttributeMessage(handlerText) {
        /*
         * Supported native handlers are deliberately narrow:
         *   return confirm('message');
         *   return confirm("message");
         */
        let match =
            handlerText.match(
                /^\s*return\s+confirm\(\s*'([^']*)'\s*\)\s*;?\s*$/i
            );

        if (match) {
            return match[1]
                .replace(/\\n/g, '\n')
                .replace(/\\'/g, "'")
                .replace(/\\\\/g, '\\');
        }

        match =
            handlerText.match(
                /^\s*return\s+confirm\(\s*"([^"]*)"\s*\)\s*;?\s*$/i
            );

        if (match) {
            return match[1]
                .replace(/\\n/g, '\n')
                .replace(/\\"/g, '"')
                .replace(/\\\\/g, '\\');
        }

        return null;
    }

    function inferTitle(element, message) {
        const text =
            (
                element.textContent ||
                element.value ||
                ''
            )
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();

        const combined =
            (text + ' ' + String(message || '')).toUpperCase();

        if (combined.includes('DELETE')) {
            return 'Confirm Deletion';
        }

        if (combined.includes('SAVE')) {
            return 'Confirm Changes';
        }

        if (combined.includes('CREATE')) {
            return 'Confirm Creation';
        }

        if (combined.includes('RESET')) {
            return 'Confirm Reset';
        }

        if (combined.includes('DISABLE')) {
            return 'Confirm Account Change';
        }

        if (combined.includes('REMOVE')) {
            return 'Confirm Removal';
        }

        return 'Confirm Action';
    }

    function inferConfirmText(element) {
        const text =
            (
                element.textContent ||
                element.value ||
                ''
            )
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();

        if (text.includes('DELETE')) {
            return 'DELETE';
        }

        if (text.includes('SAVE')) {
            return 'SAVE CHANGES';
        }

        if (text.includes('CREATE')) {
            return 'CREATE';
        }

        if (text.includes('RESET')) {
            return 'RESET';
        }

        if (text.includes('REMOVE')) {
            return 'REMOVE';
        }

        if (text.includes('DISABLE')) {
            return 'DISABLE';
        }

        return 'CONTINUE';
    }

    function activateOriginalElement(element) {
        element.dataset.agConfirmBypass = '1';

        const original =
            element.dataset.agOriginalOnclick || '';

        element.removeAttribute('onclick');

        try {
            element.click();
        } finally {
            if (original) {
                element.setAttribute('onclick', original);
            }

            window.setTimeout(function () {
                delete element.dataset.agConfirmBypass;
            }, 0);
        }
    }

    function prepareNativeConfirms() {
        const elements =
            document.querySelectorAll('[onclick*="confirm("]');

        elements.forEach(function (element) {
            if (element.dataset.agConfirmPrepared === '1') {
                return;
            }

            const original =
                element.getAttribute('onclick') || '';

            const message =
                decodeHtmlAttributeMessage(original);

            if (message === null) {
                /*
                 * Installer should have rejected unsupported patterns.
                 * Leave it untouched rather than risk changing behaviour.
                 */
                return;
            }

            element.dataset.agConfirmPrepared = '1';
            element.dataset.agOriginalOnclick = original;
            element.dataset.agConfirmMessage = message;

            element.removeAttribute('onclick');

            element.addEventListener('click', function (event) {
                if (element.dataset.agConfirmBypass === '1') {
                    delete element.dataset.agConfirmBypass;
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                showModal(
                    element.dataset.agConfirmMessage,
                    {
                        title: inferTitle(
                            element,
                            element.dataset.agConfirmMessage
                        ),
                        confirmText: inferConfirmText(element),
                        cancelText: 'CANCEL'
                    }
                ).then(function (confirmed) {
                    if (!confirmed) {
                        return;
                    }

                    activateOriginalElement(element);
                });
            }, true);
        });
    }

    function bindModalEvents() {
        const overlay = buildModal();

        overlay.addEventListener('click', function (event) {
            if (
                event.target === overlay ||
                event.target.closest('[data-ag-confirm-cancel]')
            ) {
                closeModal(false);
                return;
            }

            if (event.target.closest('[data-ag-confirm-ok]')) {
                closeModal(true);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (!overlay.classList.contains('open')) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal(false);
                return;
            }

            if (event.key === 'Enter') {
                const active = document.activeElement;

                if (
                    active &&
                    active.matches('[data-ag-confirm-ok]')
                ) {
                    return;
                }
            }
        });
    }

    window.AustraliaGridConfirm = showModal;

    function start() {
        injectStyles();
        buildModal();
        bindModalEvents();
        prepareNativeConfirms();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
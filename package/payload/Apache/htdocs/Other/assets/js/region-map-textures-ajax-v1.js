/*
 * AUSTRALIA REGION MAP TEXTURES AJAX V1
 */

(function () {
    'use strict';

    window.rmtAjaxReady = true;

    var pendingRestoreForm = null;
    var security = createSecurityDialog();


    function actionOf(form) {

        var field =
            form.querySelector(
                'input[name="action"]'
            );

        return field
            ? String(field.value || '')
            : '';
    }


    function regionName(card) {

        return (
            card.dataset.regionName ||
            'Region'
        );
    }


    function customSummaryValue() {

        var cards =
            document.querySelectorAll(
                '.rmt-summary-card'
            );

        for (
            var i = 0;
            i < cards.length;
            i++
        ) {

            var label =
                cards[i].querySelector(
                    '.rmt-summary-label'
                );

            if (
                label &&
                label.textContent.trim() ===
                    'CUSTOM TEXTURES'
            ) {

                return cards[i].querySelector(
                    '.rmt-summary-value'
                );
            }
        }

        return null;
    }


    function metaValue(
        card,
        wantedLabel
    ) {

        var items =
            card.querySelectorAll(
                '.rmt-meta-item'
            );

        for (
            var i = 0;
            i < items.length;
            i++
        ) {

            var label =
                items[i].querySelector(
                    '.rmt-meta-label'
                );

            if (
                label &&
                label.textContent.trim() ===
                    wantedLabel
            ) {

                return items[i].querySelector(
                    '.rmt-meta-value'
                );
            }
        }

        return null;
    }


    function setMeta(
        card,
        label,
        value
    ) {

        var target =
            metaValue(
                card,
                label
            );

        if (target) {
            target.textContent = value;
        }
    }


    function makeStatus(state) {

        var status =
            document.createElement(
                'span'
            );

        status.className =
            'rmt-status ' +
            (
                state === 'CUSTOM'
                    ? 'custom'
                    : 'default'
            );

        status.textContent =
            state;

        return status;
    }


    function renderCustom(
        card,
        payload
    ) {

        var preview =
            card.querySelector(
                '.rmt-preview'
            );

        if (!preview) {
            return;
        }

        var image =
            document.createElement(
                'img'
            );

        image.alt =
            regionName(card) +
            ' custom map texture';

        image.src =
            payload.custom.url +
            (
                payload.custom.url.indexOf('?') >= 0
                    ? '&'
                    : '?'
            ) +
            'ajax=' +
            Date.now();

        preview.replaceChildren(
            image,
            makeStatus('CUSTOM')
        );
    }


    function renderDefault(card) {

        var preview =
            card.querySelector(
                '.rmt-preview'
            );

        if (!preview) {
            return;
        }

        var baseX =
            parseInt(
                card.dataset.regionX,
                10
            );

        var baseY =
            parseInt(
                card.dataset.regionY,
                10
            );

        var cellsX =
            parseInt(
                card.dataset.cellsX,
                10
            );

        var cellsY =
            parseInt(
                card.dataset.cellsY,
                10
            );

        var grid =
            document.createElement(
                'div'
            );

        grid.className =
            'rmt-native-grid';

        grid.style.gridTemplateColumns =
            'repeat(' +
            cellsX +
            ',1fr)';

        grid.style.gridTemplateRows =
            'repeat(' +
            cellsY +
            ',1fr)';

        grid.style.aspectRatio =
            cellsX +
            ' / ' +
            cellsY;

        for (
            var dy = cellsY - 1;
            dy >= 0;
            dy--
        ) {

            for (
                var dx = 0;
                dx < cellsX;
                dx++
            ) {

                var image =
                    document.createElement(
                        'img'
                    );

                image.alt = '';
                image.loading = 'lazy';

                image.src =
                    '/Other/region-map-native-tile.php?x=' +
                    (baseX + dx) +
                    '&y=' +
                    (baseY + dy) +
                    '&ajax=' +
                    Date.now();

                grid.appendChild(
                    image
                );
            }
        }

        preview.replaceChildren(
            grid,
            makeStatus('DEFAULT')
        );
    }


    function findRestoreForm(card) {

        var forms =
            card.querySelectorAll(
                'form'
            );

        for (
            var i = 0;
            i < forms.length;
            i++
        ) {

            if (
                actionOf(forms[i]) ===
                'restore'
            ) {

                return forms[i];
            }
        }

        return null;
    }


    function ensureRestoreForm(card) {

        var existing =
            findRestoreForm(card);

        if (existing) {
            return existing;
        }

        var actions =
            card.querySelector(
                '.rmt-actions'
            );

        var uploadForm =
            card.querySelector(
                '.rmt-upload-form'
            );

        if (
            !actions ||
            !uploadForm
        ) {
            return null;
        }

        var csrf =
            uploadForm.querySelector(
                'input[name="csrf"]'
            );

        var uuid =
            uploadForm.querySelector(
                'input[name="region_uuid"]'
            );

        if (
            !csrf ||
            !uuid
        ) {
            return null;
        }

        var form =
            document.createElement(
                'form'
            );

        form.method = 'post';

        form.innerHTML =
            '<input type="hidden" name="csrf">' +
            '<input type="hidden" name="action" value="restore">' +
            '<input type="hidden" name="region_uuid">' +
            '<button class="rmt-button restore" type="submit">RESTORE DEFAULT</button>';

        form.querySelector(
            'input[name="csrf"]'
        ).value =
            csrf.value;

        form.querySelector(
            'input[name="region_uuid"]'
        ).value =
            uuid.value;

        actions.appendChild(
            form
        );

        return form;
    }


    function syncButtons(card) {

        var isCustom =
            !!card.querySelector(
                '.rmt-status.custom'
            );

        var uploadButton =
            card.querySelector(
                '.rmt-upload-form .rmt-button'
            );

        if (uploadButton) {

            uploadButton.disabled =
                false;

            uploadButton.textContent =
                isCustom
                    ? 'REPLACE CUSTOM'
                    : 'UPLOAD CUSTOM';
        }

        var restoreForm =
            findRestoreForm(card);

        if (isCustom) {

            restoreForm =
                ensureRestoreForm(card);

            if (restoreForm) {
                restoreForm.hidden = false;
            }
        }
        else if (restoreForm) {

            restoreForm.hidden = true;
        }

        if (restoreForm) {

            var restoreButton =
                restoreForm.querySelector(
                    '.rmt-button.restore'
                );

            if (restoreButton) {

                restoreButton.disabled =
                    false;

                restoreButton.textContent =
                    'RESTORE DEFAULT';
            }
        }

        var file =
            card.querySelector(
                '.rmt-file'
            );

        if (file) {
            file.disabled = false;
        }

        card.classList.remove(
            'rmt-ajax-busy'
        );
    }


    function setBusy(
        card,
        action
    ) {

        card.classList.add(
            'rmt-ajax-busy'
        );

        var file =
            card.querySelector(
                '.rmt-file'
            );

        if (file) {
            file.disabled = true;
        }

        var uploadButton =
            card.querySelector(
                '.rmt-upload-form .rmt-button'
            );

        if (uploadButton) {

            uploadButton.disabled = true;

            if (action === 'upload') {
                uploadButton.textContent =
                    'UPLOADING...';
            }
        }

        var restoreForm =
            findRestoreForm(card);

        if (restoreForm) {

            var restoreButton =
                restoreForm.querySelector(
                    '.rmt-button.restore'
                );

            if (restoreButton) {

                restoreButton.disabled =
                    true;

                if (action === 'restore') {
                    restoreButton.textContent =
                        'RESTORING...';
                }
            }
        }
    }


    function showMessage(
        card,
        message,
        type
    ) {

        var body =
            card.querySelector(
                '.rmt-body'
            );

        if (!body) {
            return;
        }

        var old =
            body.querySelector(
                '.rmt-card-message'
            );

        if (old) {
            old.remove();
        }

        var box =
            document.createElement(
                'div'
            );

        box.className =
            'rmt-card-message ' +
            (
                type === 'success'
                    ? 'success'
                    : 'error'
            );

        box.textContent =
            message;

        body.insertBefore(
            box,
            body.firstChild
        );

        window.setTimeout(
            function () {

                if (box.parentNode) {
                    box.remove();
                }
            },
            5000
        );
    }


    function applyState(
        card,
        payload
    ) {

        if (
            payload.state ===
            'CUSTOM'
        ) {

            renderCustom(
                card,
                payload
            );
        }
        else {

            renderDefault(
                card
            );
        }

        setMeta(
            card,
            'IMAGE',
            payload.display.image
        );

        setMeta(
            card,
            'DIMENSIONS',
            payload.display.dimensions
        );

        setMeta(
            card,
            'FILE SIZE',
            payload.display.file_size
        );

        setMeta(
            card,
            'LAST CHANGED',
            payload.display.last_changed
        );

        var count =
            customSummaryValue();

        if (count) {

            count.textContent =
                String(
                    payload.custom_count
                );
        }

        var file =
            card.querySelector(
                '.rmt-file'
            );

        if (file) {
            file.value = '';
        }

        syncButtons(card);

        /*
         * AUSTRALIA RMT 2D MAP CHANGE SIGNAL V1
         *
         * Tell any currently-open Admin 2D Map that this
         * region's texture changed.
         *
         * BroadcastChannel gives immediate same-origin
         * tab/window/iframe delivery.
         *
         * localStorage is a second same-origin fallback.
         */
        signalMapTextureChange(
            card,
            payload
        );
    }


    function signalMapTextureChange(
        card,
        payload
    ) {

        var message = {
            source:
                'region-map-textures',

            region_name:
                regionName(
                    card
                ),

            region_uuid:
                (
                    card.dataset.regionUuid ||
                    payload.uuid ||
                    ''
                ),

            state:
                (
                    payload.state ||
                    ''
                ),

            changed_at:
                Date.now()
        };


        try {

            if (
                'BroadcastChannel'
                in window
            ) {

                var channel =
                    new BroadcastChannel(
                        'australia-rmt-map-change-v1'
                    );


                channel.postMessage(
                    message
                );


                channel.close();
            }
        }
        catch (error) {
        }


        try {

            window.localStorage.setItem(
                'australia-rmt-map-change-v1',
                JSON.stringify(
                    message
                )
            );
        }
        catch (error) {
        }
    }


    async function submitAjax(form) {

        var card =
            form.closest(
                '.rmt-card'
            );

        if (!card) {
            return;
        }

        var action =
            actionOf(form);

        var data =
            new FormData(form);

        data.set(
            'ajax',
            '1'
        );

        setBusy(
            card,
            action
        );

        try {

            var response =
                await fetch(
                    '/Other/admin-region-map-textures.php',
                    {
                        method:
                            'POST',

                        body:
                            data,

                        cache:
                            'no-store',

                        credentials:
                            'same-origin',

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',

                            'Accept':
                                'application/json'
                        }
                    }
                );

            var text =
                await response.text();

            var payload =
                JSON.parse(text);

            if (
                !payload ||
                payload.ok !== true
            ) {

                showMessage(
                    card,
                    payload.message ||
                        'The texture change could not be completed.',
                    'error'
                );

                return;
            }

            applyState(
                card,
                payload
            );

            showMessage(
                card,
                payload.message ||
                    'Region map texture updated.',
                'success'
            );
        }
        catch (error) {

            showMessage(
                card,
                error.message ||
                    'The texture change could not be completed.',
                'error'
            );
        }
        finally {

            syncButtons(card);
        }
    }


    function createSecurityDialog() {

        var overlay =
            document.createElement(
                'div'
            );

        overlay.className =
            'rmt-security-overlay';

        overlay.hidden = true;

        overlay.innerHTML =
            '<div class="rmt-security-dialog" role="dialog" aria-modal="true">' +
                '<div class="rmt-security-head">' +
                    '<div class="rmt-security-kicker">SECURITY WARNING</div>' +
                    '<h2 class="rmt-security-title">RESTORE DEFAULT MAP TEXTURE</h2>' +
                '</div>' +
                '<div class="rmt-security-body">' +
                    '<p class="rmt-security-region">Region: <strong data-rmt-security-region>Region</strong></p>' +
                    '<p class="rmt-security-text">This will remove the custom map texture from this region and return it to its native OpenSim map image.</p>' +
                    '<p class="rmt-security-note">The current custom texture will be backed up before it is removed.</p>' +
                '</div>' +
                '<div class="rmt-security-actions">' +
                    '<button type="button" class="rmt-security-button rmt-security-cancel">CANCEL</button>' +
                    '<button type="button" class="rmt-security-button rmt-security-confirm">RESTORE DEFAULT</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(
            overlay
        );

        overlay
            .querySelector(
                '.rmt-security-cancel'
            )
            .addEventListener(
                'click',
                closeSecurityDialog
            );

        overlay
            .querySelector(
                '.rmt-security-confirm'
            )
            .addEventListener(
                'click',
                function () {

                    var form =
                        pendingRestoreForm;

                    closeSecurityDialog();

                    if (form) {
                        submitAjax(form);
                    }
                }
            );

        overlay.addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    overlay
                ) {
                    closeSecurityDialog();
                }
            }
        );

        return overlay;
    }


    function openSecurityDialog(form) {

        var card =
            form.closest(
                '.rmt-card'
            );

        pendingRestoreForm =
            form;

        var name =
            security.querySelector(
                '[data-rmt-security-region]'
            );

        name.textContent =
            card
                ? regionName(card)
                : 'Region';

        security.hidden =
            false;
    }


    function closeSecurityDialog() {

        security.hidden =
            true;

        pendingRestoreForm =
            null;
    }


    document.addEventListener(
        'click',
        function (event) {

            var button =
                event.target.closest(
                    '.rmt-button.restore'
                );

            if (!button) {
                return;
            }

            var form =
                button.closest(
                    'form'
                );

            if (
                !form ||
                actionOf(form) !==
                    'restore'
            ) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            openSecurityDialog(
                form
            );
        },
        true
    );


    document.addEventListener(
        'submit',
        function (event) {

            var form =
                event.target;

            if (
                !(form instanceof HTMLFormElement)
            ) {
                return;
            }

            var action =
                actionOf(form);

            if (
                action !== 'upload' &&
                action !== 'restore'
            ) {
                return;
            }

            if (
                !form.closest(
                    '.rmt-card'
                )
            ) {
                return;
            }

            event.preventDefault();

            if (
                action === 'restore'
            ) {

                openSecurityDialog(
                    form
                );

                return;
            }

            submitAjax(
                form
            );
        },
        true
    );

})();
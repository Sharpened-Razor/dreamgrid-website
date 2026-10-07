(function(){

    "use strict";

    if (
        window.__dgRegionActionModalV1
    ) {
        return;
    }


    window.__dgRegionActionModalV1 =
        true;


    let backdrop =
        null;

    let dialog =
        null;

    let titleElement =
        null;

    let messageElement =
        null;

    let inputWrap =
        null;

    let inputLabel =
        null;

    let inputElement =
        null;

    let cancelButton =
        null;

    let confirmButton =
        null;

    let activeResolve =
        null;

    let activeTypedMode =
        false;

    let activeExpected =
        "";

    let bypassButton =
        null;

    let bypassSubmit =
        false;


    function regionOriginalName(){

        const hidden =
            document.querySelector(
                'input[name="region_original"]'
            );


        if (
            hidden &&
            String(hidden.value || "").trim()
        ) {

            return String(
                hidden.value
            ).trim();
        }


        const nameField =
            document.getElementById(
                "regionNameField"
            );


        if (
            nameField &&
            String(nameField.value || "").trim()
        ) {

            return String(
                nameField.value
            ).trim();
        }


        return "this region";
    }


    function buildModal(){

        if (dialog) {
            return;
        }


        const style =
            document.createElement(
                "style"
            );


        style.id =
            "dgRegionActionModalStyleV1";


        style.textContent =
`
#dgRegionActionBackdropV1{
    position:fixed;
    inset:0;
    z-index:2147483600;

    display:none;

    align-items:center;
    justify-content:center;

    padding:24px;

    background:
        rgba(0,0,0,.78);

    backdrop-filter:
        blur(2px);
}

#dgRegionActionBackdropV1.open{
    display:flex;
}

#dgRegionActionModalV1{
    width:
        min(
            560px,
            calc(100vw - 40px)
        );

    overflow:hidden;

    border:
        1px solid #9a7724;

    border-radius:
        10px;

    background:
        #0b0e0d;

    color:
        #e9e9e4;

    box-shadow:
        0 28px 90px rgba(0,0,0,.92),
        0 0 0 1px rgba(226,182,79,.08),
        inset 0 1px 0 rgba(255,255,255,.035);
}

#dgRegionActionHeadV1{
    display:flex;
    align-items:center;
    min-height:47px;

    padding:
        0 18px;

    border-bottom:
        1px solid #6c541c;

    background:
        linear-gradient(
            180deg,
            #20231e 0%,
            #111512 100%
        );
}

#dgRegionActionTitleV1{
    color:
        #efbd3e;

    font-size:
        14px;

    font-weight:
        900;

    letter-spacing:
        .45px;

    text-transform:
        uppercase;
}

#dgRegionActionBodyV1{
    padding:
        22px 22px 18px;
}

#dgRegionActionMessageV1{
    display:grid;
    gap:12px;

    color:
        #ddddda;

    font-size:
        13px;

    line-height:
        1.55;
}

#dgRegionActionMessageV1 p{
    margin:0;
}

#dgRegionActionMessageV1 .dg-action-warning-v1{
    color:
        #f1c452;

    font-weight:
        800;
}

#dgRegionActionInputWrapV1{
    display:none;

    margin-top:
        20px;
}

#dgRegionActionInputWrapV1.open{
    display:block;
}

#dgRegionActionInputLabelV1{
    display:block;

    margin-bottom:
        7px;

    color:
        #cba54d;

    font-size:
        11px;

    font-weight:
        900;

    letter-spacing:
        .35px;

    text-transform:
        uppercase;
}

#dgRegionActionInputV1{
    width:100%;
    height:42px;

    box-sizing:border-box;

    padding:
        0 11px;

    border:
        1px solid #5d512e;

    border-radius:
        6px;

    outline:none;

    background:
        #050706;

    color:
        #f2f2ee;

    font:
        13px Arial,Helvetica,sans-serif;

    box-shadow:
        inset 0 2px 5px rgba(0,0,0,.72);
}

#dgRegionActionInputV1:focus{
    border-color:
        #e2b64f;

    box-shadow:
        inset 0 2px 5px rgba(0,0,0,.72),
        0 0 0 1px rgba(226,182,79,.22);
}

#dgRegionActionButtonsV1{
    display:flex;
    justify-content:flex-end;
    gap:10px;

    padding:
        0 22px 21px;
}

.dg-region-action-button-v1{
    min-width:105px;
    height:36px;

    padding:
        0 17px;

    border:
        1px solid #625329;

    border-radius:
        6px;

    background:
        linear-gradient(
            180deg,
            #292c27,
            #131613
        );

    color:
        #deded8;

    font:
        800 11px Arial,Helvetica,sans-serif;

    letter-spacing:
        .3px;

    cursor:pointer;
}

.dg-region-action-button-v1:hover{
    border-color:
        #e2b64f;

    color:
        #fff2bd;
}

#dgRegionActionConfirmV1{
    border-color:
        #9a7724;

    background:
        linear-gradient(
            180deg,
            #4a3b17,
            #241d0e
        );

    color:
        #f5cd67;
}

#dgRegionActionConfirmV1:hover{
    border-color:
        #f0c457;

    color:
        #fff0b0;
}

#dgRegionActionConfirmV1:disabled{
    opacity:.36;
    cursor:not-allowed;
}

#dgRegionActionConfirmV1:disabled:hover{
    border-color:#9a7724;
    color:#f5cd67;
}

body.dg-region-action-modal-open-v1{
    overflow:hidden !important;
}
`;


        document.head.appendChild(
            style
        );


        backdrop =
            document.createElement(
                "div"
            );


        backdrop.id =
            "dgRegionActionBackdropV1";


        dialog =
            document.createElement(
                "div"
            );


        dialog.id =
            "dgRegionActionModalV1";

        dialog.setAttribute(
            "role",
            "dialog"
        );

        dialog.setAttribute(
            "aria-modal",
            "true"
        );


        const head =
            document.createElement(
                "div"
            );


        head.id =
            "dgRegionActionHeadV1";


        titleElement =
            document.createElement(
                "div"
            );


        titleElement.id =
            "dgRegionActionTitleV1";


        const body =
            document.createElement(
                "div"
            );


        body.id =
            "dgRegionActionBodyV1";


        messageElement =
            document.createElement(
                "div"
            );


        messageElement.id =
            "dgRegionActionMessageV1";


        inputWrap =
            document.createElement(
                "div"
            );


        inputWrap.id =
            "dgRegionActionInputWrapV1";


        inputLabel =
            document.createElement(
                "label"
            );


        inputLabel.id =
            "dgRegionActionInputLabelV1";

        inputLabel.htmlFor =
            "dgRegionActionInputV1";


        inputElement =
            document.createElement(
                "input"
            );


        inputElement.id =
            "dgRegionActionInputV1";

        inputElement.type =
            "text";

        inputElement.autocomplete =
            "off";

        inputElement.spellcheck =
            false;


        const buttons =
            document.createElement(
                "div"
            );


        buttons.id =
            "dgRegionActionButtonsV1";


        cancelButton =
            document.createElement(
                "button"
            );


        cancelButton.type =
            "button";

        cancelButton.id =
            "dgRegionActionCancelV1";

        cancelButton.className =
            "dg-region-action-button-v1";

        cancelButton.textContent =
            "CANCEL";


        confirmButton =
            document.createElement(
                "button"
            );


        confirmButton.type =
            "button";

        confirmButton.id =
            "dgRegionActionConfirmV1";

        confirmButton.className =
            "dg-region-action-button-v1";


        head.appendChild(
            titleElement
        );


        inputWrap.appendChild(
            inputLabel
        );

        inputWrap.appendChild(
            inputElement
        );


        body.appendChild(
            messageElement
        );

        body.appendChild(
            inputWrap
        );


        buttons.appendChild(
            cancelButton
        );

        buttons.appendChild(
            confirmButton
        );


        dialog.appendChild(
            head
        );

        dialog.appendChild(
            body
        );

        dialog.appendChild(
            buttons
        );


        backdrop.appendChild(
            dialog
        );


        document.body.appendChild(
            backdrop
        );


        cancelButton.addEventListener(
            "click",
            function(){

                finishModal(
                    activeTypedMode
                        ? null
                        : false
                );
            }
        );


        confirmButton.addEventListener(
            "click",
            function(){

                if (
                    confirmButton.disabled
                ) {
                    return;
                }


                if (
                    activeTypedMode
                ) {

                    finishModal(
                        inputElement.value
                    );

                    return;
                }


                finishModal(
                    true
                );
            }
        );


        inputElement.addEventListener(
            "input",
            function(){

                if (
                    !activeTypedMode
                ) {
                    return;
                }


                confirmButton.disabled =
                    inputElement.value !==
                    activeExpected;
            }
        );


        inputElement.addEventListener(
            "keydown",
            function(event){

                if (
                    event.key ===
                        "Enter" &&
                    activeTypedMode &&
                    !confirmButton.disabled
                ) {

                    event.preventDefault();

                    confirmButton.click();
                }
            }
        );


        backdrop.addEventListener(
            "click",
            function(event){

                if (
                    event.target ===
                    backdrop
                ) {

                    finishModal(
                        activeTypedMode
                            ? null
                            : false
                    );
                }
            }
        );


        document.addEventListener(
            "keydown",
            function(event){

                if (
                    event.key ===
                        "Escape" &&
                    backdrop.classList.contains(
                        "open"
                    )
                ) {

                    event.preventDefault();

                    finishModal(
                        activeTypedMode
                            ? null
                            : false
                    );
                }
            },
            true
        );
    }


    function finishModal(
        result
    ){

        if (
            !activeResolve
        ) {
            return;
        }


        const resolve =
            activeResolve;


        activeResolve =
            null;


        backdrop.classList.remove(
            "open"
        );


        document.body.classList.remove(
            "dg-region-action-modal-open-v1"
        );


        inputWrap.classList.remove(
            "open"
        );


        inputElement.value =
            "";


        activeExpected =
            "";

        activeTypedMode =
            false;


        resolve(
            result
        );
    }


    function renderMessages(
        lines
    ){

        messageElement.replaceChildren();


        (
            Array.isArray(lines)
                ? lines
                : [String(lines || "")]
        )
        .forEach(
            function(line){

                const paragraph =
                    document.createElement(
                        "p"
                    );


                paragraph.textContent =
                    String(
                        line.text !== undefined
                            ? line.text
                            : line
                    );


                if (
                    line &&
                    typeof line ===
                        "object" &&
                    line.warning ===
                        true
                ) {

                    paragraph.className =
                        "dg-action-warning-v1";
                }


                messageElement.appendChild(
                    paragraph
                );
            }
        );
    }


    function openConfirm(
        options
    ){

        buildModal();


        if (
            activeResolve
        ) {

            finishModal(
                activeTypedMode
                    ? null
                    : false
            );
        }


        activeTypedMode =
            false;

        activeExpected =
            "";


        titleElement.textContent =
            String(
                options.title ||
                "CONFIRM"
            );


        renderMessages(
            options.lines || []
        );


        inputWrap.classList.remove(
            "open"
        );


        confirmButton.disabled =
            false;


        confirmButton.textContent =
            String(
                options.confirmLabel ||
                "OK"
            );


        backdrop.classList.add(
            "open"
        );


        document.body.classList.add(
            "dg-region-action-modal-open-v1"
        );


        window.setTimeout(
            function(){

                confirmButton.focus();

            },
            30
        );


        return new Promise(
            function(resolve){

                activeResolve =
                    resolve;
            }
        );
    }


    function openTypedConfirm(
        options
    ){

        buildModal();


        if (
            activeResolve
        ) {

            finishModal(
                activeTypedMode
                    ? null
                    : false
            );
        }


        activeTypedMode =
            true;

        activeExpected =
            String(
                options.expected ||
                ""
            );


        titleElement.textContent =
            String(
                options.title ||
                "CONFIRM"
            );


        renderMessages(
            options.lines || []
        );


        inputLabel.textContent =
            String(
                options.inputLabel ||
                "TYPE THE REGION NAME TO CONFIRM"
            );


        inputElement.value =
            "";


        inputElement.placeholder =
            activeExpected;


        inputWrap.classList.add(
            "open"
        );


        confirmButton.textContent =
            String(
                options.confirmLabel ||
                "DELETE"
            );


        confirmButton.disabled =
            true;


        backdrop.classList.add(
            "open"
        );


        document.body.classList.add(
            "dg-region-action-modal-open-v1"
        );


        window.setTimeout(
            function(){

                inputElement.focus();

            },
            30
        );


        return new Promise(
            function(resolve){

                activeResolve =
                    resolve;
            }
        );
    }


    function invokeOriginalButton(
        button,
        promptValue
    ){

        const oldConfirm =
            window.confirm;

        const oldPrompt =
            window.prompt;


        bypassButton =
            button;


        /*
         * The original bridge handler stays intact.
         *
         * Its old browser confirmation receives approval only
         * AFTER our charcoal/gold confirmation has completed.
         */
        window.confirm =
            function(){
                return true;
            };


        if (
            typeof promptValue ===
                "string"
        ) {

            window.prompt =
                function(){
                    return promptValue;
                };
        }


        try {

            button.click();

        }
        finally {

            window.confirm =
                oldConfirm;

            window.prompt =
                oldPrompt;

            bypassButton =
                null;
        }
    }


    /*
     * ========================================================
     * DEREGISTER / DELETE
     * ========================================================
     */

    document.addEventListener(
        "click",
        async function(event){

            const target =
                event.target;


            if (
                !target ||
                typeof target.closest !==
                    "function"
            ) {
                return;
            }


            const button =
                target.closest(
                    "#dreDeregisterButton,#dreDeleteButton"
                );


            if (
                !button ||
                button.disabled
            ) {
                return;
            }


            if (
                bypassButton ===
                button
            ) {
                return;
            }


            event.preventDefault();
            event.stopImmediatePropagation();


            const regionName =
                regionOriginalName();


            if (
                button.id ===
                    "dreDeregisterButton"
            ) {

                const approved =
                    await openConfirm(
                        {
                            title:
                                "DEREGISTER REGION",

                            confirmLabel:
                                "DEREGISTER",

                            lines:
                                [
                                    {
                                        text:
                                            "Deregister " +
                                            regionName +
                                            "?",
                                        warning:
                                            true
                                    },
                                    "DreamGrid will stop THIS REGION ONLY and remove its Robust registration.",
                                    "It will also disappear from the web Global Map.",
                                    "The Region files will be kept."
                                ]
                        }
                    );


                if (
                    approved !==
                    true
                ) {
                    return;
                }


                invokeOriginalButton(
                    button
                );


                return;
            }


            if (
                button.id ===
                    "dreDeleteButton"
            ) {

                const typedName =
                    await openTypedConfirm(
                        {
                            title:
                                "DELETE REGION",

                            expected:
                                regionName,

                            confirmLabel:
                                "DELETE",

                            inputLabel:
                                "TYPE " +
                                regionName +
                                " TO CONFIRM",

                            lines:
                                [
                                    {
                                        text:
                                            "Permanently delete " +
                                            regionName +
                                            "?",
                                        warning:
                                            true
                                    },
                                    "This uses DreamGrid's native permanent region delete path.",
                                    "Region data, map data, registration and DreamGrid region/port records may be removed.",
                                    "This action cannot be undone from this screen."
                                ]
                        }
                    );


                if (
                    typedName ===
                    null
                ) {
                    return;
                }


                if (
                    typedName !==
                    regionName
                ) {
                    return;
                }


                invokeOriginalButton(
                    button,
                    typedName
                );
            }

        },
        true
    );


    /*
     * ========================================================
     * SAVE
     * ========================================================
     *
     * Region Edit already performs its own save validation,
     * backup and verification.
     *
     * We only replace the browser confirmation UI.
     * ========================================================
     */

    document.addEventListener(
        "submit",
        async function(event){

            const form =
                event.target;


            if (
                !form ||
                form.id !==
                    "regionEditForm"
            ) {
                return;
            }


            if (
                bypassSubmit
            ) {
                return;
            }


            event.preventDefault();
            event.stopImmediatePropagation();


            const approved =
                await openConfirm(
                    {
                        title:
                            "SAVE REGION SETTINGS",

                        confirmLabel:
                            "SAVE",

                        lines:
                            [
                                {
                                    text:
                                        "Save changes to " +
                                        regionOriginalName() +
                                        "?",
                                    warning:
                                        true
                                },
                                "A Region INI backup will be created before writing.",
                                "The written Region INI will then be verified."
                            ]
                    }
                );


            if (
                approved !==
                true
            ) {
                return;
            }


            const oldConfirm =
                window.confirm;


            window.confirm =
                function(){
                    return true;
                };


            bypassSubmit =
                true;


            try {

                if (
                    typeof form.requestSubmit ===
                        "function"
                ) {

                    if (
                        event.submitter
                    ) {

                        form.requestSubmit(
                            event.submitter
                        );
                    }
                    else {

                        form.requestSubmit();
                    }
                }
                else {

                    /*
                     * Current Edge supports requestSubmit().
                     * This fallback is retained only for safety.
                     */
                    form.submit();
                }

            }
            finally {

                bypassSubmit =
                    false;

                window.confirm =
                    oldConfirm;
            }

        },
        true
    );




    /*
     * ========================================================
     * dreamgrid-region-message-modal-v2
     * ========================================================
     *
     * Region Edit must never display browser-owned
     * "localhost says" alert boxes.
     *
     * Existing action code may continue calling alert().
     * Route those messages into this charcoal/gold modal.
     */

    window.dgRegionActionMessageV2 =
        function(
            message,
            title
        ){

            return openConfirm(
                {
                    title:
                        String(
                            title ||
                            "REGION ACTION"
                        ),

                    confirmLabel:
                        "OK",

                    lines:
                        [
                            String(
                                message ||
                                ""
                            )
                        ]
                }
            );
        };


    window.alert =
        function(
            message
        ){

            window.dgRegionActionMessageV2(
                message,
                "REGION ACTION"
            );
        };

})();
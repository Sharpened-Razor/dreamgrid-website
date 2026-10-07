/*
 * ============================================================
 * Grid CUSTOM MODAL SYSTEM V1
 * ============================================================
 */


(function(){


let activeResolve =
    null;


function ensureModal(){

    let overlay =
        document.getElementById(
            "auGlobalModal"
        );


    if(overlay){
        return overlay;
    }


    overlay =
        document.createElement(
            "div"
        );


    overlay.id =
        "auGlobalModal";


    overlay.className =
        "au-modal-overlay";


    overlay.hidden =
        true;


    overlay.innerHTML =

        '<div class="au-modal-card" role="dialog" aria-modal="true">' +

            '<div class="au-modal-header">' +

                '<div class="au-modal-kicker">' +
                    'Grid' +
                '</div>' +

                '<h2 id="auModalTitle" class="au-modal-title"></h2>' +

            '</div>' +

            '<div class="au-modal-body">' +

                '<div id="auModalMessage"></div>' +

                '<div id="auModalHighlight" class="au-modal-highlight" hidden></div>' +

                '<div id="auModalWarning" class="au-modal-warning" hidden></div>' +

                '<input id="auModalInput" class="au-modal-input" type="text" hidden>' +

            '</div>' +

            '<div class="au-modal-actions">' +

                '<button id="auModalCancel" class="au-modal-button au-modal-cancel" type="button">' +
                    'CANCEL' +
                '</button>' +

                '<button id="auModalConfirm" class="au-modal-button au-modal-confirm" type="button">' +
                    'OK' +
                '</button>' +

            '</div>' +

        '</div>';


    document.body.appendChild(
        overlay
    );


    const cancel =
        overlay.querySelector(
            "#auModalCancel"
        );


    const confirm =
        overlay.querySelector(
            "#auModalConfirm"
        );


    cancel.addEventListener(
        "click",
        function(){

            closeModal(
                false
            );
        }
    );


    confirm.addEventListener(
        "click",
        function(){

            const input =
                overlay.querySelector(
                    "#auModalInput"
                );


            if(
                !input.hidden
            ){

                closeModal(
                    input.value
                );


                return;
            }


            closeModal(
                true
            );
        }
    );


    overlay.addEventListener(
        "click",
        function(event){

            if(
                event.target ===
                overlay
            ){

                closeModal(
                    false
                );
            }
        }
    );


    document.addEventListener(
        "keydown",
        function(event){

            if(
                overlay.hidden
            ){
                return;
            }


            if(
                event.key ===
                "Escape"
            ){

                closeModal(
                    false
                );
            }


            if(
                event.key ===
                "Enter"
            ){

                const input =
                    overlay.querySelector(
                        "#auModalInput"
                    );


                if(
                    !input.hidden
                    &&
                    document.activeElement === input
                ){

                    event.preventDefault();


                    closeModal(
                        input.value
                    );
                }
            }
        }
    );


    return overlay;
}


function closeModal(
    result
){

    const overlay =
        ensureModal();


    overlay.hidden =
        true;


    const resolver =
        activeResolve;


    activeResolve =
        null;


    if(resolver){

        resolver(
            result
        );
    }
}


function showModal(
    options
){

    const overlay =
        ensureModal();


    const title =
        overlay.querySelector(
            "#auModalTitle"
        );


    const message =
        overlay.querySelector(
            "#auModalMessage"
        );


    const highlight =
        overlay.querySelector(
            "#auModalHighlight"
        );


    const warning =
        overlay.querySelector(
            "#auModalWarning"
        );


    const input =
        overlay.querySelector(
            "#auModalInput"
        );


    const confirm =
        overlay.querySelector(
            "#auModalConfirm"
        );


    const cancel =
        overlay.querySelector(
            "#auModalCancel"
        );


    title.textContent =
        options.title ||
        "Grid";


    message.textContent =
        options.message ||
        "";


    if(
        options.highlight
    ){

        highlight.hidden =
            false;


        highlight.textContent =
            options.highlight;
    }
    else{

        highlight.hidden =
            true;


        highlight.textContent =
            "";
    }


    if(
        options.warning
    ){

        warning.hidden =
            false;


        warning.textContent =
            options.warning;
    }
    else{

        warning.hidden =
            true;


        warning.textContent =
            "";
    }


    if(
        options.prompt
    ){

        input.hidden =
            false;


        input.value =
            options.defaultValue ||
            "";


        input.placeholder =
            options.placeholder ||
            "";
    }
    else{

        input.hidden =
            true;


        input.value =
            "";
    }


    confirm.textContent =
        options.confirmText ||
        "OK";


    cancel.textContent =
        options.cancelText ||
        "CANCEL";


    confirm.className =
        "au-modal-button " +
        (
            options.danger
            ?
            "au-modal-danger"
            :
            "au-modal-confirm"
        );


    overlay.hidden =
        false;


    setTimeout(
        function(){

            if(
                options.prompt
            ){

                input.focus();


                input.select();
            }
            else{

                confirm.focus();
            }

        },
        40
    );


    return new Promise(
        function(resolve){

            activeResolve =
                resolve;
        }
    );
}


window.auConfirm =
    function(options){

        if(
            typeof options ===
            "string"
        ){

            options = {
                message:
                    options
            };
        }


        return showModal(
            Object.assign(
                {
                    title:
                        "PLEASE CONFIRM",

                    confirmText:
                        "CONFIRM",

                    cancelText:
                        "CANCEL"
                },
                options ||
                {}
            )
        );
    };


window.auPrompt =
    function(options){

        if(
            typeof options ===
            "string"
        ){

            options = {
                message:
                    options
            };
        }


        return showModal(
            Object.assign(
                {
                    title:
                        "ENTER DETAILS",

                    prompt:
                        true,

                    confirmText:
                        "SAVE",

                    cancelText:
                        "CANCEL"
                },
                options ||
                {}
            )
        );
    };


window.auAlert =
    function(options){

        if(
            typeof options ===
            "string"
        ){

            options = {
                message:
                    options
            };
        }


        const settings =
            Object.assign(
                {
                    title:
                        "Grid",

                    confirmText:
                        "OK",

                    cancelText:
                        ""
                },
                options ||
                {}
            );


        return showModal(
            settings
        );
    };


})();
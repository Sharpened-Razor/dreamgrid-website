(function () {
    "use strict";

    function setButtonMessage(button, text, delay) {

        var original =
            button.getAttribute("data-original-text");

        if (!original) {

            original =
                button.textContent.trim();

            button.setAttribute(
                "data-original-text",
                original
            );
        }

        button.textContent =
            text;

        window.setTimeout(
            function () {

                button.textContent =
                    original;
            },
            delay
        );
    }


    function fallbackCopy(text) {

        var textarea =
            document.createElement("textarea");

        textarea.value =
            text;

        textarea.setAttribute(
            "readonly",
            ""
        );

        textarea.style.position =
            "fixed";

        textarea.style.left =
            "-9999px";

        document.body.appendChild(
            textarea
        );

        textarea.select();

        var copied = false;

        try {

            copied =
                document.execCommand(
                    "copy"
                );
        }
        catch (error) {

            copied =
                false;
        }

        document.body.removeChild(
            textarea
        );

        return copied;
    }


    document.addEventListener(
        "click",
        function (event) {

            var button =
                event.target.closest(
                    "[data-copy-grid-url]"
                );

            if (!button) {
                return;
            }

            var row =
                button.closest(
                    ".hcp-grid-copy-row"
                );

            if (!row) {
                return;
            }

            var address =
                row.querySelector(
                    ".ag-grid-login-url"
                );

            if (!address) {
                return;
            }

            var value =
                address.textContent.trim();

            if (
                !value ||
                value ===
                    "Loading grid login address..."
            ) {

                setButtonMessage(
                    button,
                    "ADDRESS NOT READY",
                    1500
                );

                return;
            }


            function success() {

                setButtonMessage(
                    button,
                    "COPIED",
                    1500
                );
            }


            function failed() {

                if (fallbackCopy(value)) {

                    success();
                    return;
                }

                setButtonMessage(
                    button,
                    "SELECT ADDRESS AND CTRL+C",
                    2500
                );

                address.focus();

                var range =
                    document.createRange();

                range.selectNodeContents(
                    address
                );

                var selection =
                    window.getSelection();

                selection.removeAllRanges();
                selection.addRange(
                    range
                );
            }


            if (
                navigator.clipboard &&
                window.isSecureContext
            ) {

                navigator.clipboard
                    .writeText(value)
                    .then(success)
                    .catch(failed);

                return;
            }

            if (fallbackCopy(value)) {

                success();
            }
            else {

                failed();
            }
        }
    );

})();

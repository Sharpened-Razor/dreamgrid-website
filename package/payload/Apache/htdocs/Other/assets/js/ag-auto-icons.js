(function () {
    "use strict";

    /*
     * AUSTRALIA GRID
     *
     * Legacy automatic button icons are disabled.
     *
     * IMPORTANT:
     * This file must NOT resize, move or restyle any
     * Back/Return button.
     *
     * The uniform site controller owns that layout.
     */

    function removeAutomaticIcons() {

        document
            .querySelectorAll(
                ".ag-auto-icon," +
                "[data-ag-auto-icon]"
            )
            .forEach(function (icon) {

                icon.remove();
            });
    }

    if (document.readyState === "loading") {

        document.addEventListener(
            "DOMContentLoaded",
            removeAutomaticIcons
        );

    } else {

        removeAutomaticIcons();
    }
})();
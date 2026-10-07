/*
 * ==========================================================
 * AUSTRALIA CONTROL CENTER
 * TELEPORTING CLEAN REBUILD
 * PICTURE TUTORIAL TOGGLE V1
 * ==========================================================
 */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const button =
            document.getElementById(
                "tpPictureTutorialToggle"
            );

        const text =
            document.getElementById(
                "tpPictureTutorialToggleText"
            );

        const tutorial =
            document.getElementById(
                "tp-picture-tutorial"
            );


        if (
            !button ||
            !text ||
            !tutorial
        ) {
            return;
        }


        button.addEventListener(
            "click",
            function () {

                const opening =
                    tutorial.hidden;


                tutorial.hidden =
                    !opening;


                button.setAttribute(
                    "aria-expanded",
                    opening
                        ? "true"
                        : "false"
                );


                text.textContent =
                    opening
                        ? "CLOSE PICTURE TUTORIAL"
                        : "VIEW PICTURE TUTORIAL";


                if (opening) {

                    tutorial.scrollIntoView({
                        behavior: "smooth",
                        block: "nearest"
                    });

                }

            }
        );

    }
);
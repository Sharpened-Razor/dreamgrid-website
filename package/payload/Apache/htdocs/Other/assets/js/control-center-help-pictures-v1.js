(function () {
    "use strict";


    function closePictureScreens() {

        document
            .querySelectorAll(
                ".hcp-picture-screen"
            )
            .forEach(
                function (screen) {

                    screen.hidden =
                        true;
                }
            );


        document
            .querySelectorAll(
                "[data-firestorm-guide-main]"
            )
            .forEach(
                function (main) {

                    main.hidden =
                        false;
                }
            );
    }


    document.addEventListener(
        "click",
        function (event) {

            var openButton =
                event.target.closest(
                    "[data-open-picture-screen]"
                );

            if (openButton) {

                event.preventDefault();

                var panel =
                    openButton.closest(
                        ".hcp-topic-panel"
                    );

                if (!panel) {
                    return;
                }

                var targetId =
                    openButton.getAttribute(
                        "data-open-picture-screen"
                    );

                var screen =
                    document.getElementById(
                        targetId
                    );

                var main =
                    panel.querySelector(
                        "[data-firestorm-guide-main]"
                    );

                if (!screen || !main) {
                    return;
                }

                main.hidden =
                    true;

                screen.hidden =
                    false;

                window.scrollTo(
                    {
                        top: 0,
                        behavior: "smooth"
                    }
                );

                return;
            }


            var closeButton =
                event.target.closest(
                    "[data-close-picture-screen]"
                );

            if (closeButton) {

                event.preventDefault();

                closePictureScreens();

                window.scrollTo(
                    {
                        top: 0,
                        behavior: "smooth"
                    }
                );

                return;
            }


            /*
             * Whenever normal Help Centre navigation is used,
             * reset the picture screen back to the normal guide.
             */

            var helpNavigation =
                event.target.closest(
                    "[data-help-topic], [data-open-topic]"
                );

            if (helpNavigation) {

                closePictureScreens();
            }
        }
    );


})();

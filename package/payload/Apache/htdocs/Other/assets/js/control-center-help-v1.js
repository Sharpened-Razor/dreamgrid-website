(function () {

    "use strict";


    const buttons =
        Array.from(
            document.querySelectorAll(
                ".hcp-topic[data-help-topic]"
            )
        );


    const panels =
        Array.from(
            document.querySelectorAll(
                ".hcp-topic-panel[data-help-panel]"
            )
        );


    const title =
        document.querySelector(
            ".hcp-content-title"
        );


    function topicExists(name) {

        return panels.some(
            function (panel) {

                return (
                    panel.dataset.helpPanel ===
                    name
                );
            }
        );
    }


    function topicLabel(name) {

        const button =
            buttons.find(
                function (item) {

                    return (
                        item.dataset.helpTopic ===
                        name
                    );
                }
            );

        if (!button) {
            return "Help Centre";
        }

        const label =
            button.querySelector(
                ".hcp-topic-label"
            );

        return label
            ? label.textContent.trim()
            : "Help Centre";
    }


    function openHelpTopic(
        name,
        updateUrl
    ) {

        if (!topicExists(name)) {
            name = "home";
        }


        panels.forEach(
            function (panel) {

                panel.classList.toggle(
                    "active",
                    panel.dataset.helpPanel ===
                    name
                );
            }
        );


        buttons.forEach(
            function (button) {

                button.classList.toggle(
                    "active",
                    button.dataset.helpTopic ===
                    name
                );
            }
        );


        if (title) {

            title.textContent =
                name === "home"
                    ? "What do you need help with?"
                    : topicLabel(name);
        }


        if (
            updateUrl !== false &&
            window.history &&
            window.history.replaceState
        ) {

            const url =
                new URL(
                    window.location.href
                );

            if (name === "home") {
                url.searchParams.delete(
                    "topic"
                );
            }
            else {
                url.searchParams.set(
                    "topic",
                    name
                );
            }

            window.history.replaceState(
                null,
                "",
                url.pathname +
                url.search +
                url.hash
            );
        }


        window.scrollTo(
            0,
            0
        );

        document.documentElement.scrollTop = 0;

        if (document.body) {
            document.body.scrollTop = 0;
        }
    }


    buttons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    openHelpTopic(
                        button.dataset.helpTopic,
                        true
                    );
                }
            );
        }
    );


    /*
     * Buttons inside tutorials can open another Help topic.
     */
    document.addEventListener(
        "click",
        function (event) {

            const opener =
                event.target.closest(
                    "[data-open-topic]"
                );

            if (!opener) {
                return;
            }

            event.preventDefault();

            openHelpTopic(
                opener.dataset.openTopic,
                true
            );
        }
    );


    /*
     * Picture tutorial open / close controls.
     */
    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    "[data-picture-toggle]"
                );

            if (!button) {
                return;
            }

            const id =
                button.dataset.pictureToggle;

            const picture =
                document.getElementById(
                    id
                );

            if (!picture) {
                return;
            }

            const opening =
                !picture.classList.contains(
                    "open"
                );

            picture.classList.toggle(
                "open",
                opening
            );

            button.textContent =
                opening
                    ? "HIDE PICTURE TUTORIAL"
                    : "VIEW PICTURE TUTORIAL";
        }
    );


    /*
     * Portable DreamGrid login address.
     */
    function setLoginUrl(value) {

        document
            .querySelectorAll(
                ".ag-grid-login-url"
            )
            .forEach(
                function (node) {

                    node.textContent =
                        value;
                }
            );


        document
            .querySelectorAll(
                ".ag-grid-login-url-link"
            )
            .forEach(
                function (link) {

                    if (
                        /^https?:\/\//i.test(
                            value
                        )
                    ) {

                        link.href =
                            value;
                    }
                    else {

                        link.removeAttribute(
                            "href"
                        );
                    }
                }
            );
    }


    function loadLoginUrl() {

        if (
            !document.querySelector(
                ".ag-grid-login-url"
            )
        ) {
            return;
        }

        fetch(
            "/Other/grid-login-url.php?ts=" +
            Date.now(),
            {
                cache: "no-store",
                credentials: "same-origin"
            }
        )
        .then(
            function (response) {

                if (!response.ok) {
                    throw new Error(
                        "Login URL request failed"
                    );
                }

                return response.json();
            }
        )
        .then(
            function (data) {

                if (
                    !data ||
                    data.ok !== true ||
                    !data.login_url
                ) {
                    throw new Error(
                        "Login URL unavailable"
                    );
                }

                setLoginUrl(
                    data.login_url
                );
            }
        )
        .catch(
            function () {

                setLoginUrl(
                    "Grid login address unavailable"
                );
            }
        );
    }


    /*
     * Direct Help links:
     *
     * panel-help-centre.php?topic=inventory-browser
     */
    const requested =
        new URLSearchParams(
            window.location.search
        ).get(
            "topic"
        );


    if (
        requested &&
        topicExists(requested)
    ) {

        openHelpTopic(
            requested,
            false
        );
    }
    else {

        openHelpTopic(
            "home",
            false
        );
    }


    loadLoginUrl();

})();



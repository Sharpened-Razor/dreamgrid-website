(function(){

    "use strict";

    const buttons =
        Array.from(
            document.querySelectorAll(
                ".cc-nav-button[data-src]"
            )
        );

    const dashboardButton =
        document.getElementById(
            "udx-dashboard-button"
        );

    const frame =
        document.getElementById(
            "cc-frame"
        );

    const home =
        document.getElementById(
            "cc-home"
        );

    const title =
        document.getElementById(
            "cc-workspace-title"
        );

    const status =
        document.getElementById(
            "cc-status-text"
        );

    const clock =
        document.getElementById(
            "cc-clock"
        );


    function clearActive(){

        document
            .querySelectorAll(
                ".cc-nav-button"
            )
            .forEach(
                function(button){

                    button.classList.remove(
                        "active"
                    );
                }
            );
    }


    function showHome(){

        clearActive();

        dashboardButton.classList.add(
            "active"
        );

        frame.classList.remove(
            "visible"
        );

        frame.src =
            "about:blank";

        home.classList.remove(
            "hidden"
        );

        title.textContent =
            "User Control Center";

        status.textContent =
            "User Control Center Ready";

        history.replaceState(
            null,
            "",
            location.pathname +
            location.search
        );
    }


    function openPanel(button){

        const src =
            button.dataset.src || "";

        const pageTitle =
            button.dataset.title ||
            "User Service";

        if(!src){
            return;
        }

        clearActive();

        button.classList.add(
            "active"
        );

        home.classList.add(
            "hidden"
        );

        title.textContent =
            pageTitle;

        status.textContent =
            pageTitle +
            " Open";

        frame.classList.add(
            "visible"
        );

        frame.src =
            src;

        const view =
            button.dataset.view || "";

        if(view){

            history.replaceState(
                null,
                "",
                location.pathname +
                location.search +
                "#" +
                view
            );
        }
    }


    dashboardButton.addEventListener(
        "click",
        showHome
    );


    buttons.forEach(
        function(button){

            button.addEventListener(
                "click",
                function(){

                    openPanel(
                        button
                    );
                }
            );
        }
    );


    frame.addEventListener(
        "load",
        function(){

            if(
                !frame.src ||
                frame.src ===
                "about:blank"
            ){
                return;
            }

            try{

                const doc =
                    frame.contentDocument ||
                    frame.contentWindow.document;

                if(
                    doc &&
                    doc.head &&
                    !doc.getElementById(
                        "cc-embedded-theme"
                    )
                ){

                    const link =
                        doc.createElement(
                            "link"
                        );

                    link.id =
                        "cc-embedded-theme";

                    link.rel =
                        "stylesheet";

                    link.href =
                        "/Other/assets/css/control-center-embedded-v1.css";

                    doc.head.appendChild(
                        link
                    );
                }

            }catch(error){

                // Same-origin pages are expected.
            }
        }
    );


    function updateClock(){

        if(!clock){
            return;
        }

        clock.textContent =
            new Date()
                .toLocaleTimeString(
                    [],
                    {
                        hour:
                            "2-digit",

                        minute:
                            "2-digit"
                    }
                );
    }


    updateClock();

    setInterval(
        updateClock,
        30000
    );


    const requested =
        location.hash
            .replace("#","")
            .trim();

    if(requested){

        const target =
            buttons.find(
                function(button){

                    return (
                        button.dataset.view ===
                        requested
                    );
                }
            );

        if(target){

            openPanel(
                target
            );
        }
    }

})();
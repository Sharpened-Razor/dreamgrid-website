(function () {
    "use strict";


    var ICON_ROOT =
        "/Other/assets/icons/sentinel/";


    function esc(value) {

        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }


    function forceAncestorOpacity(element) {

        var node =
            element ?
            element.parentElement :
            null;


        while (
            node &&
            node !== document.body
        ) {

            var style =
                window.getComputedStyle(
                    node
                );


            if (
                parseFloat(
                    style.opacity || "1"
                ) < 1
            ) {

                node.style.setProperty(
                    "opacity",
                    "1",
                    "important"
                );
            }


            node =
                node.parentElement;
        }
    }


    function statCard(
        icon,
        label,
        subtitle,
        valueId
    ) {

        return (
            '<div class="cp-stat-card">' +

                '<img ' +
                    'class="cp-stat-icon" ' +
                    'src="' +
                    ICON_ROOT +
                    icon +
                    '" alt="">' +

                '<div class="cp-stat-copy">' +

                    '<div class="cp-stat-label">' +
                        label +
                    '</div>' +

                    '<div ' +
                        'class="cp-stat-value" ' +
                        'id="' +
                        valueId +
                        '">' +
                        '...' +
                    '</div>' +

                    '<div class="cp-stat-sub">' +
                        subtitle +
                    '</div>' +

                '</div>' +

            '</div>'
        );
    }


    function buildStats() {

        var grid =
            document.getElementById(
                "grid-stats-cards"
            );


        if (!grid) {
            return;
        }


        grid.removeAttribute(
            "style"
        );


        grid.className =
            "grid-stats-cards cp-stats";


        grid.innerHTML =
            statCard(
                "users.png",
                "USERS ONLINE",
                "Active now",
                "cp-users-online"
            ) +

            statCard(
                "region.png",
                "REGIONS ONLINE",
                "Regions",
                "cp-regions-online"
            ) +

            statCard(
                "account.png",
                "TOTAL USERS",
                "Registered",
                "cp-total-users"
            ) +

            statCard(
                "statistics.png",
                "ACTIVE USERS",
                "Last 30 days",
                "cp-active-users"
            );


        forceAncestorOpacity(
            grid
        );
    }


    function setValue(
        id,
        value
    ) {

        var target =
            document.getElementById(
                id
            );


        if (!target) {
            return;
        }


        if (
            value === undefined ||
            value === null ||
            value === ""
        ) {

            target.textContent =
                "0";
        }
        else {

            target.textContent =
                String(value);
        }
    }


    function updateStats() {

        fetch(
            "front-stats.php?t=" +
            Date.now(),
            {
                cache:
                    "no-store",

                credentials:
                    "same-origin"
            }
        )
        .then(
            function (response) {

                if (!response.ok) {
                    throw new Error(
                        "Stats request failed"
                    );
                }


                return response.json();
            }
        )
        .then(
            function (data) {

                if (!data) {
                    return;
                }


                setValue(
                    "cp-users-online",
                    data.users_online
                );


                setValue(
                    "cp-regions-online",
                    data.regions_online
                );


                setValue(
                    "cp-total-users",
                    data.total_users
                );


                setValue(
                    "cp-active-users",
                    data.active_users
                );
            }
        )
        .catch(
            function () {
            }
        );
    }


    function buttonMarkup(
        icon,
        text
    ) {

        return (
            '<img ' +
                'class="cp-login-button-icon" ' +
                'src="' +
                ICON_ROOT +
                icon +
                '" alt="">' +

            '<span class="cp-login-button-text">' +
                text +
            '</span>'
        );
    }


    function rebuildLogin() {

        var card =
            document.querySelector(
                ".australia-login-card"
            );


        if (!card) {
            return;
        }


        /*
         * Preserve whatever working actions
         * the current page already has.
         */

        var oldForms =
            card.querySelectorAll(
                "form"
            );


        var loginAction =
            oldForms[0] ?
            oldForms[0].getAttribute(
                "action"
            ) :
            "login-action.php";


        var forgotAction =
            oldForms[1] ?
            oldForms[1].getAttribute(
                "action"
            ) :
            "forgot-password.php";


        var termsAction =
            oldForms[2] ?
            oldForms[2].getAttribute(
                "action"
            ) :
            "terms.php";


        card.className =
            "australia-login-card cp-login";


        card.innerHTML =
            '<div class="cp-login-title">' +
                'Member Login' +
            '</div>' +

            '<div class="cp-login-body">' +

                '<form ' +
                    'class="cp-login-form" ' +
                    'action="' +
                    esc(loginAction) +
                    '" ' +
                    'method="post">' +

                    '<input ' +
                        'type="hidden" ' +
                        'name="METHOD" ' +
                        'value="login">' +

                    '<label class="cp-field-label">' +
                        'First Name' +
                    '</label>' +

                    '<input ' +
                        'class="cp-login-input" ' +
                        'name="firstname" ' +
                        'type="text" ' +
                        'placeholder="First name" ' +
                        'autocomplete="username">' +

                    '<label class="cp-field-label">' +
                        'Last Name' +
                    '</label>' +

                    '<input ' +
                        'class="cp-login-input" ' +
                        'name="lastname" ' +
                        'type="text" ' +
                        'placeholder="Last name" ' +
                        'autocomplete="username">' +

                    '<label class="cp-field-label">' +
                        'Password' +
                    '</label>' +

                    '<input ' +
                        'class="cp-login-input" ' +
                        'id="cp_login_password" ' +
                        'name="password" ' +
                        'type="password" ' +
                        'placeholder="Password" ' +
                        'autocomplete="current-password">' +

                    '<label class="cp-remember cp-show-password">' +

                        '<input ' +
                            'id="cp_login_show_password" ' +
                            'type="checkbox" ' +
                            'value="1">' +

                        '<span>Show password</span>' +

                    '</label>' +

                    '<label class="cp-remember">' +

                        '<input ' +
                            'name="remember" ' +
                            'type="checkbox" ' +
                            'value="1">' +

                        '<span>Remember me</span>' +

                    '</label>' +

                    '<button ' +
                        'type="submit" ' +
                        'class="' +
                            'cp-login-button ' +
                            'cp-login-primary' +
                        '">' +

                        buttonMarkup(
                            "key.png",
                            "LOG IN"
                        ) +

                    '</button>' +

                '</form>' +


                '<form ' +
                    'class="cp-secondary-form" ' +
                    'action="' +
                    esc(forgotAction) +
                    '" ' +
                    'method="get">' +

                    '<input ' +
                        'type="hidden" ' +
                        'name="panel" ' +
                        'value="forgot-password">' +

                    '<button ' +
                        'type="submit" ' +
                        'class="' +
                            'cp-login-button ' +
                            'cp-login-secondary' +
                        '">' +

                        buttonMarkup(
                            "email.png",
                            "FORGOT PASSWORD"
                        ) +

                    '</button>' +

                '</form>' +


                '<form ' +
                    'class="cp-secondary-form" ' +
                    'action="' +
                    esc(termsAction) +
                    '" ' +
                    'method="get">' +

                    '<input ' +
                        'type="hidden" ' +
                        'name="panel" ' +
                        'value="terms">' +

                    '<button ' +
                        'type="submit" ' +
                        'class="' +
                            'cp-login-button ' +
                            'cp-login-secondary' +
                        '">' +

                        buttonMarkup(
                            "report.png",
                            "TERMS OF SERVICE"
                        ) +

                    '</button>' +

                '</form>' +

            '</div>';


        forceAncestorOpacity(
            card
        );
    }


    function rebuildMenuButton(
        selector,
        icon,
        text
    ) {

        var button =
            document.querySelector(
                selector
            );


        if (!button) {
            return;
        }


        button.classList.add(
            "cp-menu-button"
        );


        button.innerHTML =
            '<img ' +
                'class="cp-menu-icon" ' +
                'src="' +
                ICON_ROOT +
                icon +
                '" alt="">' +

            '<span class="cp-menu-text">' +
                text +
            '</span>';


        forceAncestorOpacity(
            button
        );
    }


    function start() {

        buildStats();

        rebuildMenuButton(
            ".australia-public-button.create-account",
            "account.png",
            "CREATE ACCOUNT"
        );

        rebuildMenuButton(
            ".australia-public-button.help-centre",
            "help.png",
            "HELP CENTRE"
        );

        rebuildLogin();

        var loginPassword =
            document.getElementById(
                "cp_login_password"
            );

        var showPassword =
            document.getElementById(
                "cp_login_show_password"
            );

        if (
            loginPassword &&
            showPassword
        ) {

            showPassword.addEventListener(
                "change",
                function () {

                    loginPassword.type =
                        showPassword.checked ?
                        "text" :
                        "password";
                }
            );
        }

        updateStats();


        window.setInterval(
            updateStats,
            30000
        );
    }


    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            start
        );
    }
    else {

        start();
    }

})();
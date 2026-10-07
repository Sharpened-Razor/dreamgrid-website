(function () {
    "use strict";
    if (window.__agUniformSiteLoaded) return;
    window.__agUniformSiteLoaded = true;

    // Header identity and role use the same document-startup snapshot.
    // This is display data only; endpoint authorization remains server-side.
    var sessionInfoPromise;
    function loadSessionInfo() {
        if (!sessionInfoPromise) {
            sessionInfoPromise = fetch("/Other/session-info.php", {
                credentials: "same-origin", cache: "no-store"
            }).then(function (response) {
                if (!response.ok) throw new Error("Not signed in");
                return response.json();
            }).catch(function (error) {
                sessionInfoPromise = null;
                throw error;
            });
        }
        return sessionInfoPromise;
    }

(function () {
    "use strict";

    function text(el) {

        return String(
            el.textContent || ""
        )
        .replace(/\s+/g, " ")
        .trim();
    }

    function upper(el) {

        return text(el).toUpperCase();
    }

    function inNavigation(el) {

        return !!el.closest(
            "nav," +
            '[role="navigation"],' +
            ".navbar," +
            ".top-nav," +
            ".main-nav," +
            ".sidebar," +
            ".menu," +
            ".admin-menu," +
            ".dashboard-nav"
        );
    }

    function findTitle() {

        return document.querySelector(
            "main h1," +
            ".account-header h1," +
            ".profile-header h1," +
            ".regions-header h1," +
            ".messages-header h1," +
            ".page-header h1," +
            ".admin-header h1," +
            ".dashboard-header h1," +
            ".control-header h1," +
            ".hero h1," +
            "header h1," +
            "h1"
        );
    }

    function findHeader(title) {

        if (!title) {
            return null;
        }

        var header =
            title.closest(
                "header," +
                ".account-header," +
                ".profile-header," +
                ".inventory-header," +
                ".regions-header," +
                ".messages-header," +
                ".map-header," +
                ".page-header," +
                ".admin-header," +
                ".dashboard-header," +
                ".control-header," +
                ".hero," +
                ".header"
            );

        if (header) {
            return header;
        }

        var parent =
            title.parentElement;

        while (
            parent &&
            parent !== document.body
        ) {

            var rect =
                parent.getBoundingClientRect();

            if (
                rect.width >=
                window.innerWidth * 0.65
            ) {
                return parent;
            }

            parent =
                parent.parentElement;
        }

        return title.parentElement;
    }

    function hideOldBranding() {

        document
            .querySelectorAll(
                ".eyebrow," +
                ".kicker," +
                ".account-kicker," +
                ".brand-label," +
                ".member-area," +
                '[class*="eyebrow"],' +
                '[class*="kicker"]'
            )
            .forEach(function (el) {

                var t =
                    upper(el);

                if (
                    t === "GRID" ||
                    t === "GRID MEMBER AREA" ||
                    t === "MEMBER AREA"
                ) {

                    el.classList.add(
                        "ag-v12-hide-brand"
                    );
                }
            });
    }

    function hideExistingIdentity() {

        document
            .querySelectorAll(
                "p,span,small,div"
            )
            .forEach(function (el) {

                if (
                    el.hasAttribute(
                        "data-ag-v12-identity"
                    )
                ) {
                    return;
                }

                var t =
                    upper(el);

                if (
                    t.indexOf("SIGNED IN AS") !== 0
                ) {
                    return;
                }

                if (
                    t.length > 180
                ) {
                    return;
                }

                if (
                    el.querySelector(
                        "h1,h2,a,button"
                    )
                ) {
                    return;
                }

                el.style.setProperty(
                    "display",
                    "none",
                    "important"
                );
            });
    }

    function createIdentity(
        title,
        avatar
    ) {

        if (
            !title ||
            !avatar
        ) {
            return;
        }

        var existing =
            document.querySelector(
                '[data-ag-v12-identity="1"]'
            );

        if (existing) {
            existing.remove();
        }

        var identity =
            document.createElement(
                "div"
            );

        identity.className =
            "ag-v12-identity";

        identity.setAttribute(
            "data-ag-v12-identity",
            "1"
        );

        identity.appendChild(
            document.createTextNode(
                "SIGNED IN AS: "
            )
        );

        var strong =
            document.createElement(
                "strong"
            );

        strong.textContent =
            avatar;

        identity.appendChild(
            strong
        );

        title.insertAdjacentElement(
            "afterend",
            identity
        );
    }

    function loadIdentity(title) {

        hideExistingIdentity();

        loadSessionInfo()
        .then(function (data) {

            if (
                data &&
                data.ok &&
                data.avatar
            ) {

                createIdentity(
                    title,
                    String(data.avatar)
                );
            }
        })
        .catch(function () {
        });
    }

    function isReturn(el) {

        if (inNavigation(el)) {
            return false;
        }

        var t =
            upper(el);

        if (
            /^BACK(?:\s|$)/.test(t)
        ) {
            return true;
        }

        if (
            /^RETURN(?:\s|$)/.test(t)
        ) {
            return true;
        }

        var href =
            String(
                el.getAttribute(
                    "href"
                ) || ""
            ).toLowerCase();

        if (
            t === "HOME" &&
            (
                href.indexOf("home") !== -1 ||
                href.indexOf("dashboard") !== -1 ||
                href.indexOf("javascript:history.back") === 0
            )
        ) {
            return true;
        }

        if (
            t === "ADMIN DASHBOARD" &&
            href.indexOf(
                "admin-dashboard.php"
            ) !== -1
        ) {
            return true;
        }

        return false;
    }

    function cleanReturnButton(el) {

        el.removeAttribute(
            "style"
        );

        el.querySelectorAll(
            "svg.ag-icon," +
            ".ag-icon," +
            ".ag-auto-icon," +
            "[data-ag-auto-icon]"
        )
        .forEach(function (icon) {

            icon.remove();
        });

        el.classList.add(
            "ag-v12-return"
        );

        el.setAttribute(
            "data-ag-v12-return",
            "1"
        );
    }

    function createReturnSlot(header) {

        var existing =
            header.querySelector(
                ":scope > .ag-v12-return-slot"
            );

        if (existing) {
            return existing;
        }

        var slot =
            document.createElement(
                "div"
            );

        slot.className =
            "ag-v12-return-slot";

        header.appendChild(
            slot
        );

        return slot;
    }

    function standardiseReturn(
        header
    ) {

        if (!header) {
            return;
        }

        var buttons =
            Array.from(
                document.querySelectorAll(
                    "a,button"
                )
            )
            .filter(
                isReturn
            );

        if (!buttons.length) {
            return;
        }

        /*
         * One return control per page header.
         *
         * Use the first real return button.
         */

        var button =
            buttons[0];

        cleanReturnButton(
            button
        );

        header.classList.add(
            "ag-v12-header"
        );

        var slot =
            createReturnSlot(
                header
            );

        slot.appendChild(
            button
        );

        /*
         * If duplicate return buttons exist elsewhere,
         * remove the duplicates from the layout.
         */

        buttons
            .slice(1)
            .forEach(function (extra) {

                extra.style.setProperty(
                    "display",
                    "none",
                    "important"
                );
            });
    }

    function run() {

        hideOldBranding();

        var title =
            findTitle();

        if (!title) {
            return;
        }

        var header =
            findHeader(
                title
            );

        if (header) {

            header.classList.add(
                "ag-v12-header"
            );
        }

        standardiseReturn(
            header
        );

        loadIdentity(
            title
        );
    }

    /*
     * Script is injected at the end of BODY,
     * so page-specific scripts have already built the page.
     */

    run();

    /*
     * Re-run after page-specific scripts.
     *
     * This makes the shared standard authoritative even
     * on older pages that modify their buttons later.
     */

    window.setTimeout(run,150);
    window.setTimeout(run,500);
    window.setTimeout(run,1200);

    window.addEventListener(
        "load",
        run
    );
})();

/* AG DASHBOARD ROLE BADGE V14 START */

(function () {
    "use strict";

    function isDashboardPage() {

        return /\/(?:admin-home|admin-dashboard|user-dashboard)\.php$/i
            .test(
                window.location.pathname
            );
    }

    function roleForLevel(level) {

        level =
            Number(level) || 0;

        if (level >= 250) {
            return "GRID OWNER";
        }

        if (level >= 200) {
            return "ADMINISTRATOR";
        }

        return "USER";
    }

    function setBadgeContents(
        badge,
        role,
        level
    ) {

        badge.textContent =
            "";

        var strong =
            document.createElement(
                "strong"
            );

        strong.textContent =
            role;

        badge.appendChild(
            strong
        );

        badge.appendChild(
            document.createTextNode(
                "USER LEVEL " + level
            )
        );

        badge.setAttribute(
            "data-ag-role-badge",
            "1"
        );
    }

    function findDashboardHeader() {

        var title =
            document.querySelector(
                "main h1," +
                ".dashboard-header h1," +
                ".admin-header h1," +
                ".page-header h1," +
                "header h1," +
                "h1"
            );

        if (!title) {
            return null;
        }

        return (
            title.closest(
                ".dashboard-header," +
                ".admin-header," +
                ".page-header," +
                "header," +
                ".header"
            ) ||
            title.parentElement
        );
    }

    function installBadge(
        level
    ) {

        var header =
            findDashboardHeader();

        if (!header) {
            return;
        }

        /*
         * Admin Home already has the original .php-level
         * badge. Reuse it rather than creating a duplicate.
         */

        var badge =
            header.querySelector(
                ".php-level"
            );

        if (badge) {

            setBadgeContents(
                badge,
                roleForLevel(level),
                level
            );

            return;
        }

        /*
         * Admin Dashboard / User Dashboard receive the exact
         * shared clone of the Admin Home badge.
         */

        badge =
            header.querySelector(
                ".ag-role-level-badge"
            );

        if (!badge) {

            badge =
                document.createElement(
                    "div"
                );

            badge.className =
                "ag-role-level-badge";

            header.classList.add(
                "ag-role-badge-host"
            );

            header.appendChild(
                badge
            );
        }

        setBadgeContents(
            badge,
            roleForLevel(level),
            level
        );
    }

    function loadBadge() {

        if (!isDashboardPage()) {
            return;
        }

        loadSessionInfo()
        .then(function (data) {

            if (
                !data ||
                !data.ok
            ) {
                return;
            }

            installBadge(
                Number(data.level) || 0
            );
        })
        .catch(function () {
        });
    }

    if (
        document.readyState === "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            loadBadge
        );

    } else {

        loadBadge();
    }

    window.addEventListener(
        "load",
        loadBadge
    );
})();

/* AG DASHBOARD ROLE BADGE V14 END */


/* AG MASTER COMMON HEADER V18 START */

(function () {
    "use strict";

    function cleanText(value) {

        return String(value || "")
            .replace(/\s+/g, " ")
            .trim();
    }

    function directText(element) {

        var text = "";

        Array.prototype.forEach.call(
            element.childNodes,
            function (node) {

                if (
                    node.nodeType ===
                    Node.TEXT_NODE
                ) {

                    text +=
                        " " +
                        node.nodeValue;
                }
            }
        );

        return cleanText(text);
    }

    function visible(element) {

        if (!element) {
            return false;
        }

        var style =
            window.getComputedStyle(
                element
            );

        return (
            style.display !== "none" &&
            style.visibility !== "hidden"
        );
    }

    function findTitle() {

        var selectors = [
            "[data-ag-page-title]",
            ".offline-title",
            ".regions-title",
            ".account-title",
            ".profile-title",
            ".dashboard-title",
            ".admin-title",
            ".page-title",
            "header h1",
            "main h1",
            "h1"
        ];

        for (
            var i = 0;
            i < selectors.length;
            i++
        ) {

            var nodes =
                document.querySelectorAll(
                    selectors[i]
                );

            for (
                var j = 0;
                j < nodes.length;
                j++
            ) {

                if (
                    visible(nodes[j]) &&
                    cleanText(
                        nodes[j].textContent
                    ) !== ""
                ) {

                    return nodes[j];
                }
            }
        }

        return null;
    }

    function findHeader(title) {

        if (!title) {
            return null;
        }

        return (
            title.closest("header") ||
            title.closest(
                ".page-header," +
                ".admin-header," +
                ".dashboard-header," +
                ".offline-header," +
                ".regions-header," +
                ".profile-header," +
                ".account-header"
            ) ||
            title.parentElement
        );
    }

    function returnLabel(element) {

        var text =
            cleanText(
                element.textContent
            )
            .replace(
                /^[^A-Za-z0-9]+/,
                ""
            )
            .trim();

        return text;
    }

    function isReturnButton(element) {

        var text =
            returnLabel(
                element
            ).toUpperCase();

        if (
            text === "BACK" ||
            text.indexOf("BACK TO ") === 0 ||
            text.indexOf("RETURN TO ") === 0
        ) {

            return true;
        }

        if (
            text === "HOME" &&
            (
                element.classList.contains(
                    "ag-return-button"
                ) ||
                element.classList.contains(
                    "ag-back-dashboard"
                )
            )
        ) {

            return true;
        }

        if (
            text === "ADMIN DASHBOARD" &&
            (
                element.classList.contains(
                    "ag-return-button"
                ) ||
                element.classList.contains(
                    "ag-back-dashboard"
                )
            )
        ) {

            return true;
        }

        return false;
    }

    function removeReturnIcons(button) {

        button
            .querySelectorAll(
                "svg," +
                "i," +
                ".icon," +
                ".ag-icon," +
                ".ag-auto-icon," +
                "[data-ag-auto-icon]," +
                "[class*='back-icon']," +
                "[class*='button-icon']"
            )
            .forEach(function (node) {

                node.remove();
            });

        var label =
            returnLabel(
                button
            );

        if (label) {

            button.textContent =
                label;
        }
    }

    function installReturnButton(
        header
    ) {

        if (!header) {
            return;
        }

        var candidates =
            Array.prototype.slice.call(
                header.querySelectorAll(
                    "a,button"
                )
            )
            .filter(
                isReturnButton
            );

        if (
            candidates.length === 0
        ) {

            candidates =
                Array.prototype.slice.call(
                    document.querySelectorAll(
                        ".ag-return-button," +
                        ".ag-back-dashboard," +
                        "[data-ag-v12-return='1']"
                    )
                )
                .filter(
                    isReturnButton
                );
        }

        if (
            candidates.length === 0
        ) {
            return;
        }

        var button =
            candidates[0];

        removeReturnIcons(
            button
        );

        button.removeAttribute(
            "style"
        );

        button.setAttribute(
            "data-ag-master-return",
            "1"
        );

        button.setAttribute(
            "data-ag-v12-return",
            "1"
        );

        var slot =
            header.querySelector(
                ".ag-master-return-slot"
            );

        if (!slot) {

            slot =
                document.createElement(
                    "div"
                );

            slot.className =
                "ag-master-return-slot";

            header.appendChild(
                slot
            );
        }

        if (
            button.parentElement !== slot
        ) {

            slot.appendChild(
                button
            );
        }

        for (
            var i = 1;
            i < candidates.length;
            i++
        ) {

            candidates[i].style.display =
                "none";

            candidates[i].setAttribute(
                "aria-hidden",
                "true"
            );
        }
    }

    function removeMainHeaderIcons(
        header
    ) {

        if (!header) {
            return;
        }

        header
            .querySelectorAll(
                ".offline-icon," +
                ".header-icon," +
                ".title-icon," +
                ".page-title-icon," +
                ".ag-header-icon"
            )
            .forEach(function (node) {

                node.remove();
            });
    }

    function findOldIdentity(
        header
    ) {

        if (!header) {
            return null;
        }

        var nodes =
            header.querySelectorAll(
                "div,p,span,small"
            );

        for (
            var i = 0;
            i < nodes.length;
            i++
        ) {

            if (
                nodes[i].classList.contains(
                    "ag-v12-identity"
                ) ||
                nodes[i].classList.contains(
                    "ag-master-identity"
                )
            ) {
                continue;
            }

            var text =
                directText(
                    nodes[i]
                ).toUpperCase();

            if (
                text.indexOf(
                    "SIGNED IN AS"
                ) === 0
            ) {

                return nodes[i];
            }
        }

        return null;
    }

    function hideOldIdentities(
        header
    ) {

        if (!header) {
            return;
        }

        header
            .querySelectorAll(
                "div,p,span,small"
            )
            .forEach(function (node) {

                if (
                    node.classList.contains(
                        "ag-v12-identity"
                    ) ||
                    node.classList.contains(
                        "ag-master-identity"
                    )
                ) {
                    return;
                }

                var text =
                    directText(
                        node
                    ).toUpperCase();

                if (
                    text.indexOf(
                        "SIGNED IN AS"
                    ) === 0
                ) {

                    node.classList.add(
                        "ag-master-old-identity"
                    );
                }
            });
    }

    function moveMasterIdentity(
        header,
        title
    ) {

        if (
            !header ||
            !title
        ) {
            return;
        }

        var identity =
            header.querySelector(
                ".ag-v12-identity," +
                ".ag-master-identity"
            );

        if (!identity) {
            return;
        }

        identity.classList.add(
            "ag-master-identity"
        );

        if (
            title.nextElementSibling !==
            identity
        ) {

            title.insertAdjacentElement(
                "afterend",
                identity
            );
        }

        hideOldIdentities(
            header
        );
    }

    function normalize() {

        var title =
            findTitle();

        if (!title) {
            return;
        }

        var header =
            findHeader(
                title
            );

        if (!header) {
            return;
        }

        header.classList.add(
            "ag-master-header"
        );

        title.classList.add(
            "ag-master-title"
        );

        removeMainHeaderIcons(
            header
        );

        installReturnButton(
            header
        );

        moveMasterIdentity(
            header,
            title
        );
    }

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            normalize
        );

    } else {

        normalize();
    }

    window.addEventListener(
        "load",
        normalize
    );

    window.setTimeout(
        normalize,
        150
    );

    window.setTimeout(
        normalize,
        500
    );

    window.setTimeout(
        normalize,
        1200
    );
})();

/* AG MASTER COMMON HEADER V18 END */


/* ============================================================
   SHARED UI PHASE 3C
   GLOBAL LEGACY UI NEUTRALIZER
   ============================================================ */

(function () {
    "use strict";

    function cleanText(value) {

        return String(value || "")
            .replace(/\s+/g, " ")
            .trim();
    }

    function neutralizeOldSignedIn() {

        document
            .querySelectorAll(
                "div,p,span,small"
            )
            .forEach(function (node) {

                if (
                    node.classList.contains(
                        "ag-master-identity"
                    ) ||
                    node.classList.contains(
                        "ag-v12-identity"
                    )
                ) {
                    return;
                }

                var text =
                    cleanText(
                        node.textContent
                    ).toUpperCase();

                if (
                    text.indexOf(
                        "SIGNED IN AS"
                    ) === 0
                ) {

                    node.classList.add(
                        "ag-master-old-identity"
                    );

                    node.setAttribute(
                        "aria-hidden",
                        "true"
                    );
                }
            });
    }

    function neutralizeOldRoles() {

        document
            .querySelectorAll(
                "div,span,strong,p,small"
            )
            .forEach(function (node) {

                if (
                    node.closest(
                        ".ag-role-level-badge"
                    ) ||
                    node.closest(
                        ".php-level[data-ag-role-badge='1']"
                    )
                ) {
                    return;
                }

                var text =
                    cleanText(
                        node.textContent
                    ).toUpperCase();

                if (
                    text === "GRID OWNER" ||
                    text === "ADMINISTRATOR"
                ) {

                    node.classList.add(
                        "ag-master-old-role"
                    );

                    node.setAttribute(
                        "aria-hidden",
                        "true"
                    );
                }
            });
    }

    function runLegacyNeutralizer() {

        neutralizeOldSignedIn();
        neutralizeOldRoles();
    }

    if (
        document.readyState === "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            runLegacyNeutralizer
        );

    } else {

        runLegacyNeutralizer();
    }

    window.addEventListener(
        "load",
        runLegacyNeutralizer
    );

    window.setTimeout(
        runLegacyNeutralizer,
        150
    );

    window.setTimeout(
        runLegacyNeutralizer,
        500
    );

    window.setTimeout(
        runLegacyNeutralizer,
        1200
    );
})();

/* ============================================================
   END SHARED UI PHASE 3C
   ============================================================ */









/* ============================================================
   AUSTRALIA SENTINEL GLOBAL AUTO ICONS V1

   Shared Admin + User page icon system.
   Existing Sentinel PNG icons are preserved.
   Legacy/empty card icon slots are upgraded automatically.
   ============================================================ */

(function () {
    "use strict";

    if (window.__agSentinelGlobalIconsV1) {
        return;
    }

    window.__agSentinelGlobalIconsV1 = true;

    var BASE =
        "/Other/assets/icons/sentinel/";


    /* --------------------------------------------------------
       TEXT -> SENTINEL ICON MAP
       -------------------------------------------------------- */

    var ICON_MAP = [

        [
            /BACK\s+TO\s+ADMIN\s+HOME|ADMIN\s+HOME/i,
            "admin-home.png"
        ],

        [
            /BACK\s+TO\s+USER\s+HOME|USER\s+HOME/i,
            "home.png"
        ],

        [
            /CONTROL\s+PANEL|GRID\s+CONTROL/i,
            "control-panel.png"
        ],

        [
            /ADMIN\s+DASHBOARD|USER\s+DASHBOARD|DASHBOARD/i,
            "dashboard.png"
        ],

        [
            /BACKUP\s+SCHEDULER|SCHEDULE|CALENDAR/i,
            "calendar.png"
        ],

        [
            /BACKUP\s+HEALTH|HEALTH\s+DIAGNOSTICS|HEALTH|CHECKLIST/i,
            "checklist.png"
        ],

        [
            /BACKUP\s+HISTORY|RESTORE\s+HISTORY|HISTORY|REPORT/i,
            "report.png"
        ],

        [
            /BACKUP\s+STORAGE|STORAGE|DATABASE/i,
            "database.png"
        ],

        [
            /BACKUP\s+FILES|FILES|FOLDERS/i,
            "files.png"
        ],

        [
            /USER\s+IAR\s+BACKUP|SAVE\s+IAR|IAR\s+BACKUP/i,
            "backup.png"
        ],

        [
            /GRID\s+BACKUP|BACKUP/i,
            "backup.png"
        ],

        [
            /RESTORE/i,
            "refresh.png"
        ],

        [
            /REGION\s+MAP|GRID\s+MAP|MAP/i,
            "map.png"
        ],

        [
            /CREATE\s+REGION|ADD\s+REGION|NEW\s+REGION/i,
            "add-region.png"
        ],

        [
            /REGION/i,
            "region.png"
        ],

        [
            /USER\s+ACCOUNT|MY\s+ACCOUNT|ACCOUNT/i,
            "account.png"
        ],

        [
            /AVATAR|PROFILE/i,
            "avatar.png"
        ],

        [
            /CREATE\s+USER|ADD\s+USER|USERS|USER\s+MANAGEMENT|ACCOUNTS/i,
            "users.png"
        ],

        [
            /FRIEND/i,
            "users.png"
        ],

        [
            /GROUP/i,
            "groups.png"
        ],

        [
            /INVENTORY/i,
            "inventory.png"
        ],

        [
            /MESSENGER|MESSAGE|EMAIL|MAIL/i,
            "email.png"
        ],

        [
            /CHAT/i,
            "chat.png"
        ],

        [
            /NOTIFICATION|ALERT/i,
            "notification.png"
        ],

        [
            /STATISTICS|PERFORMANCE|TRAFFIC/i,
            "statistics.png"
        ],

        [
            /UPTIME|LOG/i,
            "report.png"
        ],

        [
            /CONSOLE/i,
            "console.png"
        ],

        [
            /RESTART|REFRESH|RELOAD/i,
            "refresh.png"
        ],

        [
            /START|POWER\s+ON/i,
            "control-panel.png"
        ],

        [
            /STOP|SHUTDOWN|LOGOUT|SIGN\s+OUT/i,
            "logout.png"
        ],

        [
            /FREEZE|LOCK|SECURITY/i,
            "security.png"
        ],

        [
            /THAW|UNLOCK|PASSWORD|KEY/i,
            "key.png"
        ],

        [
            /SAVE/i,
            "save.png"
        ],

        [
            /DELETE|REMOVE|CLEAR/i,
            "delete.png"
        ],

        [
            /EDIT|MODIFY/i,
            "edit.png"
        ],

        [
            /SEARCH|FIND/i,
            "search.png"
        ],

        [
            /FILTER/i,
            "filter.png"
        ],

        [
            /IMPORT|UPLOAD/i,
            "import.png"
        ],

        [
            /EXPORT|DOWNLOAD/i,
            "export.png"
        ],

        [
            /TELEPORT/i,
            "map.png"
        ],

        [
            /LINK/i,
            "link.png"
        ],

        [
            /IMAGE|TEXTURE/i,
            "image.png"
        ],

        [
            /VIEW|OPEN|SHOW/i,
            "view.png"
        ],

        [
            /SETTINGS|PREFERENCES/i,
            "settings.png"
        ],

        [
            /TOOLS|MAINTENANCE/i,
            "tools.png"
        ],

        [
            /HELP|SUPPORT|DETAILS|INFO/i,
            "help.png"
        ],

        [
            /BOOKMARK/i,
            "bookmark.png"
        ],

        [
            /BACK|RETURN|HOME/i,
            "home.png"
        ]
    ];


    function clean(value) {

        return String(value || "")
            .replace(/\s+/g, " ")
            .trim();
    }


    function iconFor(label) {

        var value =
            clean(label);

        var i;

        if (
            !value ||
            value.length > 140
        ) {
            return "";
        }

        for (
            i = 0;
            i < ICON_MAP.length;
            i++
        ) {

            if (
                ICON_MAP[i][0].test(
                    value
                )
            ) {

                return ICON_MAP[i][1];
            }
        }

        return "";
    }


    function hasSentinel(root) {

        if (
            !root ||
            !root.querySelector
        ) {
            return false;
        }

        return !!root.querySelector(
            'img[src*="/assets/icons/sentinel/"],' +
            '.ag-sentinel-icon'
        );
    }


    function makeIcon(
        fileName,
        className
    ) {

        var img =
            document.createElement(
                "img"
            );

        img.src =
            BASE + fileName;

        img.alt = "";

        img.setAttribute(
            "aria-hidden",
            "true"
        );

        img.setAttribute(
            "draggable",
            "false"
        );

        img.decoding =
            "async";

        img.className =
            "ag-sentinel-direct-icon " +
            (
                className ||
                "ag-sentinel-sm"
            );

        return img;
    }


    /* --------------------------------------------------------
       LOAD THE SENTINEL PICTURE CSS ON EVERY PAGE
       -------------------------------------------------------- */

    function ensureSentinelCss() {

        var found =
            document.querySelector(
                'link[href*="ag-sentinel-icons-v1.css"]'
            );

        if (found) {
            return;
        }

        var link =
            document.createElement(
                "link"
            );

        link.rel =
            "stylesheet";

        link.href =
            "/Other/assets/css/" +
            "ag-sentinel-icons-v1.css" +
            "?v=20260906-global-v1";

        document.head.appendChild(
            link
        );
    }


    /* --------------------------------------------------------
       GLOBAL AUTO ICON SIZE RULES
       -------------------------------------------------------- */

    function ensureAutoCss() {

        if (
            document.getElementById(
                "ag-sentinel-global-auto-style-v1"
            )
        ) {
            return;
        }

        var style =
            document.createElement(
                "style"
            );

        style.id =
            "ag-sentinel-global-auto-style-v1";

        style.textContent = [

            ".ag-sentinel-auto-control-icon{" +
                "width:22px!important;" +
                "height:22px!important;" +
                "min-width:22px!important;" +
                "margin-right:7px!important;" +
                "vertical-align:middle!important;" +
                "object-fit:contain!important;" +
            "}",

            ".ag-sentinel-auto-title-icon{" +
                "width:34px!important;" +
                "height:34px!important;" +
                "min-width:34px!important;" +
                "margin-right:10px!important;" +
                "vertical-align:-7px!important;" +
                "object-fit:contain!important;" +
            "}",

            ".ag-sentinel-auto-slot-icon{" +
                "width:50px!important;" +
                "height:50px!important;" +
                "min-width:50px!important;" +
                "object-fit:contain!important;" +
            "}",

            ".ag-sentinel-auto-compact-icon{" +
                "width:28px!important;" +
                "height:28px!important;" +
                "min-width:28px!important;" +
                "object-fit:contain!important;" +
            "}",

            "button>.ag-sentinel-auto-control-icon," +
            "a>.ag-sentinel-auto-control-icon{" +
                "pointer-events:none!important;" +
            "}"

        ].join("\n");

        document.head.appendChild(
            style
        );
    }


    /* --------------------------------------------------------
       BUTTON / LINK ICONS
       -------------------------------------------------------- */

    function controlLabel(el) {

        return clean(

            el.getAttribute(
                "aria-label"
            ) ||

            el.getAttribute(
                "title"
            ) ||

            el.textContent

        );
    }


    function removeLegacyControlIcon(el) {

        var legacy =
            el.querySelectorAll(

                ":scope > .ag-auto-icon," +
                ":scope > .ag-glow-icon," +
                ":scope > svg"

            );

        legacy.forEach(
            function (node) {

                node.remove();
            }
        );
    }


    function iconizeControl(el) {

        var label;
        var fileName;

        /* FRIEND LIST ICON EXCLUSION V1 */
        if (
            el &&
            el.matches &&
            el.matches(".ag-v2-new-friend")
        ) {

            Array.prototype.slice.call(el.children).forEach(
                function (node) {

                    if (
                        node.classList &&
                        (
                            node.classList.contains("ag-sentinel-auto-control-icon") ||
                            node.classList.contains("ag-sentinel-direct-icon")
                        )
                    ) {
                        node.remove();
                    }
                }
            );

            el.dataset.agSentinelAutoIcon = "1";
            return;
        }


        if (
            !el ||
            el.dataset.agSentinelAutoIcon === "1"
        ) {
            return;
        }

        if (
            hasSentinel(el)
        ) {

            el.dataset.agSentinelAutoIcon =
                "1";

            return;
        }

        /*
         * Do not replace real image controls.
         */
        if (
            el.querySelector("img")
        ) {
            return;
        }

        label =
            controlLabel(el);

        fileName =
            iconFor(label);

        if (!fileName) {
            return;
        }

        removeLegacyControlIcon(
            el
        );

        el.insertBefore(

            makeIcon(
                fileName,
                "ag-sentinel-auto-control-icon"
            ),

            el.firstChild
        );

        el.dataset.agSentinelAutoIcon =
            "1";
    }


    /* --------------------------------------------------------
       PAGE TITLE ICONS
       -------------------------------------------------------- */

    function iconizeTitle(el) {

        var fileName;

        if (
            !el ||
            el.dataset.agSentinelAutoTitle === "1"
        ) {
            return;
        }

        if (
            hasSentinel(el)
        ) {

            el.dataset.agSentinelAutoTitle =
                "1";

            return;
        }

        fileName =
            iconFor(
                clean(
                    el.textContent
                )
            );

        if (!fileName) {
            return;
        }

        el.insertBefore(

            makeIcon(
                fileName,
                "ag-sentinel-auto-title-icon"
            ),

            el.firstChild
        );

        el.dataset.agSentinelAutoTitle =
            "1";
    }


    /* --------------------------------------------------------
       CARD / MENU ICON SLOTS
       -------------------------------------------------------- */

    function cardLabel(slot) {

        var card =
            slot.closest(

                ".control-hub-card," +
                ".dashboard-card," +
                ".php-tool-card," +
                ".php-menu-item," +
                ".menu-card," +
                ".card"

            );

        var title;

        if (!card) {
            return "";
        }

        title =
            card.querySelector(

                ".control-hub-title," +
                ".card-title," +
                ".php-tool-title," +
                ".php-menu-title," +
                "h2,h3"

            );

        return title
            ? clean(
                title.textContent
            )
            : clean(
                card.textContent
            );
    }


    function iconizeSlot(slot) {

        var fileName;
        var compact;

        if (
            !slot ||
            slot.dataset.agSentinelAutoSlot === "1"
        ) {
            return;
        }

        if (
            hasSentinel(slot)
        ) {

            slot.dataset.agSentinelAutoSlot =
                "1";

            return;
        }

        fileName =
            iconFor(
                cardLabel(slot)
            );

        if (!fileName) {
            return;
        }

        compact =

            slot.classList.contains(
                "php-menu-icon"
            ) ||

            slot.classList.contains(
                "menu-icon"
            ) ||

            slot.classList.contains(
                "user-home-icon"
            );

        slot.replaceChildren(

            makeIcon(

                fileName,

                compact
                    ? "ag-sentinel-auto-compact-icon"
                    : "ag-sentinel-auto-slot-icon"

            )
        );

        slot.dataset.agSentinelAutoSlot =
            "1";
    }


    /* --------------------------------------------------------
       APPLY ACROSS THE CURRENT PAGE
       -------------------------------------------------------- */

    function run(root) {

        var scope =
            root &&
            root.querySelectorAll
                ? root
                : document;

        ensureSentinelCss();
        ensureAutoCss();


        /* Existing card/menu icon positions */

        scope.querySelectorAll(

            ".card-icon," +
            ".control-hub-icon," +
            ".php-tool-icon," +
            ".php-menu-icon," +
            ".menu-icon," +
            ".user-home-icon," +
            ".stat-icon"

        ).forEach(
            iconizeSlot
        );


        /* Main Admin/User page headers */

        scope.querySelectorAll(

            ".dashboard-header h1," +
            ".admin-header h1," +
            ".account-header h1," +
            ".profile-header h1," +
            ".regions-header h1," +
            ".messages-header h1," +
            ".page-header h1," +
            ".control-header h1," +
            "main > h1"

        ).forEach(
            iconizeTitle
        );


        /* Main control buttons / navigation controls */

        scope.querySelectorAll(

            "button," +
            "a.control-hub-button," +
            "a.top-button," +
            "a.small-button," +
            "a.dg-button," +
            "a.dg-button-link," +
            "a[class*='button']," +
            "a[class*='btn']," +
            "a[class*='menu']," +
            "a[class*='tool']," +
            "a[class*='nav']"

        ).forEach(
            iconizeControl
        );
    }


    /* --------------------------------------------------------
       DYNAMIC CONTENT SUPPORT
       -------------------------------------------------------- */

    var timer = 0;


    function scheduleRun() {

        window.clearTimeout(
            timer
        );

        timer =
            window.setTimeout(
                function () {

                    run(document);

                },
                80
            );
    }


    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            function () {

                run(document);
            }
        );

    }
    else {

        run(document);
    }


    window.addEventListener(
        "load",
        function () {

            run(document);
        }
    );

    /* Dynamic MutationObserver disabled to prevent page reflow/pulsing. */

})();

/* ============================================================
   END AUSTRALIA SENTINEL GLOBAL AUTO ICONS V1
   ============================================================ */




/* ============================================================
   STANDARD ROLE BADGE STYLE V3
   Same visual role badge on Admin and User pages.
   ============================================================ */

(function(){
    if(document.getElementById("ag-standard-role-style-v3")) return;

    var s=document.createElement("style");
    s.id="ag-standard-role-style-v3";

    s.textContent=
        ".php-level,.ag-account-role-box{" +
        "display:inline-flex!important;" +
        "flex-direction:column!important;" +
        "align-items:center!important;" +
        "justify-content:center!important;" +
        "min-width:145px!important;" +
        "min-height:54px!important;" +
        "padding:7px 12px!important;" +
        "box-sizing:border-box!important;" +
        "border:1px solid rgba(55,150,215,.65)!important;" +
        "border-radius:8px!important;" +
        "background:rgba(5,28,43,.82)!important;" +
        "box-shadow:none!important;" +
        "line-height:1.15!important;" +
        "text-align:center!important;" +
        "}" +

        ".php-level strong,.ag-account-role-box strong{" +
        "display:block!important;" +
        "margin:0 0 3px 0!important;" +
        "padding:0!important;" +
        "color:#76c7ff!important;" +
        "font-size:12px!important;" +
        "font-weight:900!important;" +
        "letter-spacing:.03em!important;" +
        "line-height:1.1!important;" +
        "}" +

        ".php-level span,.ag-account-role-box span{" +
        "display:block!important;" +
        "margin:0!important;" +
        "padding:0!important;" +
        "color:#ffffff!important;" +
        "font-size:11px!important;" +
        "font-weight:800!important;" +
        "white-space:nowrap!important;" +
        "line-height:1.1!important;" +
        "}";

    document.head.appendChild(s);
})();

/* END STANDARD ROLE BADGE STYLE V3 */
})();

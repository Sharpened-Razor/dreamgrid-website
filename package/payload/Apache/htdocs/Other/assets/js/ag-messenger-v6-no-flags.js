(function () {
    "use strict";

    let area = null;

    let friendsWindow = null;
    let chatWindow = null;

    let friendsList = null;
    let chatTitle = null;
    let chatHistory = null;
    let messageBox = null;

    let legacyFriends = null;
    let legacyChat = null;

    let currentTrigger = null;
    let currentFriendName = "";

    let friendsMinimised = false;
    let chatMinimised = false;

    let zCounter = 50;


    function front(win) {

        if(!win){
            return;
        }

        zCounter++;

        win.style.zIndex =
            String(
                zCounter
            );
    }


    function makeButton(
        text,
        className
    ) {

        const button =
            document.createElement(
                "button"
            );

        button.type =
            "button";

        button.className =
            "ag-v2-small-button " +
            className;

        button.textContent =
            text;

        return button;
    }


    function updateAreaHeight() {

        if(!area){
            return;
        }

        let height =
            650;

        [
            friendsWindow,
            chatWindow
        ]
        .forEach(function (win) {

            if(
                !win ||
                getComputedStyle(win).display ===
                    "none"
            ){
                return;
            }

            const top =
                parseFloat(
                    win.style.top
                ) || 0;

            height =
                Math.max(
                    height,
                    top +
                    win.offsetHeight
                );
        });

        area.style.minHeight =
            Math.ceil(
                height
            ) +
            "px";
    }


    function restoreFriends() {

        if(!friendsWindow){
            return;
        }

        friendsMinimised =
            false;

        friendsWindow.classList.remove(
            "ag-v2-minimised"
        );

        friendsWindow.style.height =
            friendsWindow.dataset.normalHeight ||
            "650px";

        const button =
            friendsWindow.querySelector(
                ".ag-v2-friends-minimise"
            );

        if(button){

            button.textContent =
                "MINIMISE";
        }

        front(
            friendsWindow
        );

        updateAreaHeight();
    }


    function minimiseFriends() {

        if(!friendsWindow){
            return;
        }

        if(friendsMinimised){

            restoreFriends();

            return;
        }

        friendsWindow.dataset.normalHeight =
            friendsWindow.style.height ||
            friendsWindow.offsetHeight +
            "px";

        friendsMinimised =
            true;

        friendsWindow.classList.add(
            "ag-v2-minimised"
        );

        const button =
            friendsWindow.querySelector(
                ".ag-v2-friends-minimise"
            );

        if(button){

            button.textContent =
                "RESTORE";
        }

        updateAreaHeight();
    }


    function closeChat() {

        if(!chatWindow){
            return;
        }

        chatWindow.classList.remove(
            "ag-v2-chat-open",
            "ag-v2-minimised"
        );

        chatMinimised =
            false;

        currentTrigger =
            null;

        currentFriendName =
            "";

        front(
            friendsWindow
        );

        updateAreaHeight();
    }


    function minimiseChat() {

        if(!chatWindow){
            return;
        }

        if(chatMinimised){

            chatMinimised =
                false;

            chatWindow.classList.remove(
                "ag-v2-minimised"
            );

            chatWindow.style.height =
                chatWindow.dataset.normalHeight ||
                "650px";

            const button =
                chatWindow.querySelector(
                    ".ag-v2-chat-minimise"
                );

            if(button){

                button.textContent =
                    "MINIMISE";
            }

            front(
                chatWindow
            );

            updateAreaHeight();

            return;
        }


        chatWindow.dataset.normalHeight =
            chatWindow.style.height ||
            chatWindow.offsetHeight +
            "px";

        chatMinimised =
            true;

        chatWindow.classList.add(
            "ag-v2-minimised"
        );

        const button =
            chatWindow.querySelector(
                ".ag-v2-chat-minimise"
            );

        if(button){

            button.textContent =
                "RESTORE";
        }

        updateAreaHeight();
    }


    function positionChatBesideFriends() {

        if(
            !friendsWindow ||
            !chatWindow ||
            !area
        ){
            return;
        }

        const gap =
            20;

        const left =
            friendsWindow.offsetLeft +
            friendsWindow.offsetWidth +
            gap;

        const top =
            friendsWindow.offsetTop;

        chatWindow.style.left =
            Math.round(
                left
            ) +
            "px";

        chatWindow.style.top =
            Math.round(
                top
            ) +
            "px";


        const available =
            area.clientWidth -
            left;

        if(
            available >=
            450
        ){
            chatWindow.style.width =
                Math.floor(
                    available
                ) +
                "px";
        }
        else{
            chatWindow.style.width =
                "450px";
        }
    }


    function prepareLegacyFriend(
        trigger
    ) {

        if(
            !trigger ||
            typeof window
                .australiaOpenFriendMessage !==
                "function"
        ){
            return;
        }

        try {

            /*
             * This only prepares the original hidden OpenSim
             * form fields.
             *
             * Its old visual overlay is permanently hidden
             * by V2 CSS.
             */

            window
                .australiaOpenFriendMessage(
                    trigger
                );

        } catch(error) {
        }
    }


    function openChat(
        trigger
    ) {

        if(
            !trigger
        ){
            return;
        }

        restoreFriends();

        currentTrigger =
            trigger;

        currentFriendName =
            String(
                trigger.dataset.friendName ||
                "Friend"
            ).trim();


        prepareLegacyFriend(
            trigger
        );


        positionChatBesideFriends();


        chatTitle.textContent =
            currentFriendName;


        chatHistory.innerHTML =
            "";


        const empty =
            document.createElement(
                "div"
            );

        empty.id =
            "agV2ChatEmpty";

        empty.textContent =
            "Chat with " +
            currentFriendName;


        chatHistory.appendChild(
            empty
        );


        messageBox.value =
            "";


        chatWindow.classList.add(
            "ag-v2-chat-open"
        );

        chatWindow.classList.remove(
            "ag-v2-minimised"
        );

        chatMinimised =
            false;


        front(
            chatWindow
        );

        updateAreaHeight();


        window.setTimeout(
            function () {

                messageBox.focus();
            },
            20
        );
    }


    function sendMessage() {

        if(
            !currentTrigger ||
            !legacyChat
        ){
            return;
        }


        const text =
            messageBox.value.trim();


        if(
            text ===
            ""
        ){
            messageBox.focus();
            return;
        }


        /*
         * Prepare the original hidden recipient fields again.
         */

        prepareLegacyFriend(
            currentTrigger
        );


        const legacyForm =
            legacyChat.querySelector(
                "form"
            );


        const legacyTextarea =
            legacyChat.querySelector(
                "textarea"
            );


        const legacySend =
            legacyChat.querySelector(
                ".aus-friend-send"
            );


        if(
            !legacyForm ||
            !legacyTextarea ||
            !legacySend
        ){
            alert(
                "OpenSim message form could not be found."
            );

            return;
        }


        legacyTextarea.value =
            text;


        /*
         * Send asynchronously.
         * Do not reload the Offline Messages page.
         */

        const formData =
            new FormData(
                legacyForm
            );

        formData.set(
            "friend_message",
            text
        );

        const v2Send =
            document.getElementById(
                "agV2Send"
            );

        const oldLabel =
            v2Send
                ? v2Send.textContent
                : "";

        if(
            v2Send
        ){
            v2Send.disabled =
                true;

            v2Send.textContent =
                "SENDING...";
        }

        messageBox.value =
            "";

        fetch(
            "/Other/messenger-send.php",
            {
                method: "POST",
                headers: {
                    "X-Australia-Messenger": "1"
                },
                body: formData,
                credentials: "same-origin",
                redirect: "follow"
            }
        )
        .then(
            function(response){

                if(
                    !response.ok
                ){
                    throw new Error(
                        "Message send failed."
                    );
                }
            }
        )
        .catch(
            function(error){

                if(
                    messageBox.value ===
                    ""
                ){
                    messageBox.value =
                        text;
                }

                alert(
                    error.message
                );
            }
        )
        .finally(
            function(){

                if(
                    v2Send
                ){
                    v2Send.disabled =
                        false;

                    v2Send.textContent =
                        oldLabel ||
                        "SEND";
                }

                messageBox.focus();
            }
        );
    }


    function buildFriendList() {

        friendsList.innerHTML =
            "";


        const triggers =
            Array.from(
                legacyFriends.querySelectorAll(
                    "[data-friend-key][data-friend-name]"
                )
            );


        triggers.forEach(
            function (trigger) {

                const name =
                    String(
                        trigger.dataset.friendName ||
                        "Friend"
                    ).trim();


                const grid =
                    String(
                        trigger.dataset.friendGrid ||
                        ""
                    )
                    .trim()
                    .replace(
                        /^@/,
                        ""
                    );


                const row =
                    document.createElement(
                        "button"
                    );


                row.type =
                    "button";

                row.className =
                    "ag-v2-new-friend";


                row.dataset.search =
                    (
                        name + " " + grid
                    ).toLowerCase();


                const title =
                    document.createElement(
                        "span"
                    );

                title.className =
                    "ag-v2-new-friend-name";

                title.textContent =
                    name;


                const sub =
                    document.createElement(
                        "span"
                    );

                sub.className =
                    "ag-v2-new-friend-sub";

                sub.textContent =
                    grid !== ""
                        ? "Grid: " + grid
                        : "Click to open chat";


                row.appendChild(
                    title
                );

                row.appendChild(
                    sub
                );


                row.addEventListener(
                    "click",
                    function () {

                        openChat(
                            trigger
                        );
                    }
                );


                friendsList.appendChild(
                    row
                );
            }
        );


        if(
            triggers.length ===
            0
        ){
            const none =
                document.createElement(
                    "div"
                );

            none.style.padding =
                "20px";

            none.style.color =
                "rgba(255,255,255,.5)";

            none.textContent =
                "No friends were returned by OpenSim.";

            friendsList.appendChild(
                none
            );
        }
    }


    function installSearch() {

        const input =
            document.getElementById(
                "agV2FriendsSearch"
            );


        input.addEventListener(
            "input",
            function () {

                const value =
                    input.value
                        .trim()
                        .toLowerCase();


                friendsList
                    .querySelectorAll(
                        ".ag-v2-new-friend"
                    )
                    .forEach(
                        function (row) {

                            row.style.display =
                                (
                                    value ===
                                        "" ||
                                    row.dataset.search
                                        .includes(value)
                                )
                                ? ""
                                : "none";
                        }
                    );
            }
        );
    }


    function saveWindowGeometry(win) {
        if(!win){
            return;
        }

        let scope = "";

        if(win.id === "agV2FriendsWindow"){
            scope = "friends";
        }

        if(win.id === "agV2ChatWindow"){
            scope = "chat";
        }

        if(!scope){
            return;
        }

        const data = new FormData();

        data.append("action","save_window");
        data.append("window",scope);
        data.append("left",String(Math.round(win.offsetLeft)));
        data.append("top",String(Math.round(win.offsetTop)));
        data.append("width",String(Math.round(win.offsetWidth)));
        data.append("height",String(Math.round(win.offsetHeight)));

        postWindowSettings(data).catch(function () {
            /* Silent geometry save failure. */
        });
    }

    function makeDraggable(
        win,
        handle
    ) {

        if(
            !win ||
            !handle
        ){
            return;
        }


        handle.addEventListener(
            "pointerdown",
            function (event) {

                if(
                    event.target.closest(
                        "button,input,textarea,a,select"
                    )
                ){
                    return;
                }


                if(
                    window.matchMedia(
                        "(max-width:1000px)"
                    ).matches
                ){
                    return;
                }


                event.preventDefault();


                front(
                    win
                );


                const startX =
                    event.clientX;

                const startY =
                    event.clientY;

                const startLeft =
                    win.offsetLeft;

                const startTop =
                    win.offsetTop;


                function move(moveEvent) {

                    const left =
                        Math.max(
                            0,
                            startLeft +
                            moveEvent.clientX -
                            startX
                        );


                    const top =
                        Math.max(
                            0,
                            startTop +
                            moveEvent.clientY -
                            startY
                        );


                    win.style.setProperty(
                        "left",
                        Math.round(left) + "px",
                        "important"
                    );

                    win.style.setProperty(
                        "top",
                        Math.round(top) + "px",
                        "important"
                    );


                    updateAreaHeight();
                }


                function finish() {

                    document.removeEventListener(
                        "pointermove",
                        move
                    );

                    document.removeEventListener(
                        "pointerup",
                        finish
                    );

                    document.removeEventListener(
                        "pointercancel",
                        finish
                    );


                    updateAreaHeight();

                    saveWindowGeometry(win);
                }


                document.addEventListener(
                    "pointermove",
                    move
                );

                document.addEventListener(
                    "pointerup",
                    finish
                );

                document.addEventListener(
                    "pointercancel",
                    finish
                );
            }
        );


        win.addEventListener(
            "pointerdown",
            function () {

                front(
                    win
                );
            }
        );
    }


    /* MESSENGER WINDOW SETTINGS UI V2 START */

    const windowPreferences = {
        friends: null,
        chat: null
    };


    const localPreviewUrls = {
        friends: null,
        chat: null
    };


    function getWindowElement(
        scope
    ) {

        if(
            scope ===
            "friends"
        ){
            return friendsWindow;
        }

        if(
            scope ===
            "chat"
        ){
            return chatWindow;
        }

        return null;
    }


    function getSettingsDrawer(
        scope
    ) {

        return document.getElementById(
                "agV2Settings-" +
                scope
            );
    }


    function setSettingsStatus(
        scope,
        message,
        error
    ) {

        const drawer =
            getSettingsDrawer(
                scope
            );

        if(!drawer){
            return;
        }

        const status =
            drawer.querySelector(
                ".ag-v2-settings-status"
            );

        if(!status){
            return;
        }

        status.textContent =
            message || "";

        status.classList.toggle(
            "error",
            !!error
        );
    }


    function applyWindowPreferences(
        scope,
        preferences
    ) {

        const win =
            getWindowElement(
                scope
            );

        if(
            !win ||
            !preferences
        ){
            return;
        }


        windowPreferences[scope] =
            Object.assign(
                {},
                windowPreferences[scope] || {},
                preferences
            );


        const prefs =
            windowPreferences[scope];


        /*
         * Restore this avatar's saved Messenger geometry.
         * Desktop only so responsive/mobile layout stays untouched.
         */
        if(
            !window.matchMedia(
                "(max-width:1000px)"
            ).matches
        ){
            if(Number.isFinite(Number(prefs.left))){
                win.style.setProperty(
                    "left",
                    Math.round(Number(prefs.left)) + "px",
                    "important"
                );
            }

            if(Number.isFinite(Number(prefs.top))){
                win.style.setProperty(
                    "top",
                    Math.round(Number(prefs.top)) + "px",
                    "important"
                );
            }

            if(Number.isFinite(Number(prefs.width))){
                win.style.setProperty(
                    "width",
                    Math.round(Number(prefs.width)) + "px",
                    "important"
                );
            }

            if(Number.isFinite(Number(prefs.height))){
                win.style.setProperty(
                    "height",
                    Math.round(Number(prefs.height)) + "px",
                    "important"
                );
            }
        }


        const wallpaper =
            win.querySelector(
                ".ag-v2-window-wallpaper"
            );


        const background =
            localPreviewUrls[scope] ||
            prefs.backgroundUrl ||
            "";


        if(wallpaper){

            wallpaper.style.backgroundImage =
                background
                ? 'url("' +
                  String(background)
                      .replace(
                          /"/g,
                          '\\"'
                      ) +
                  '")'
                : "none";


            const mode =
                prefs.backgroundMode ||
                "cover";


            if(
                mode ===
                "stretch"
            ){
                wallpaper.style.backgroundSize =
                    "100% 100%";
            }
            else{
                wallpaper.style.backgroundSize =
                    mode;
            }


            wallpaper.style.backgroundPosition =
                prefs.backgroundPosition ||
                "center center";


            wallpaper.style.filter =
                "blur(" +
                Number(
                    prefs.blur || 0
                ) +
                "px)";
        }


        win.style.setProperty(
            "--ag-v2-darkness",
            String(
                Number(
                    prefs.darkness ?? .50
                )
            )
        );


        win.style.setProperty(
            "--ag-v2-surface-alpha",
            String(
                Number(
                    prefs.bubbleOpacity ?? .88
                )
            )
        );


        win.style.setProperty(
            "--ag-v2-font-size",
            String(
                Number(
                    prefs.fontSize || 15
                )
            ) +
            "px"
        );


        syncSettingsForm(
            scope
        );
    }


    function syncSettingsForm(
        scope
    ) {

        const drawer =
            getSettingsDrawer(
                scope
            );

        const prefs =
            windowPreferences[scope];


        if(
            !drawer ||
            !prefs
        ){
            return;
        }


        const topClose =
            drawer.querySelector(
                '[data-action="top-close"]'
            );

        if(topClose){

            topClose.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    drawer.classList.remove(
                        "show"
                    );

                    drawer.setAttribute(
                        "aria-hidden",
                        "true"
                    );

                    drawer.style.removeProperty(
                        "display"
                    );

                    drawer.style.removeProperty(
                        "visibility"
                    );

                    drawer.style.removeProperty(
                        "opacity"
                    );

                    drawer.style.removeProperty(
                        "pointer-events"
                    );
                }
            );
        }


        const form =
            drawer.querySelector(
                "form"
            );


        if(!form){
            return;
        }


        form.elements.backgroundMode.value =
            prefs.backgroundMode ||
            "cover";


        form.elements.backgroundPosition.value =
            prefs.backgroundPosition ||
            "center center";


        form.elements.darkness.value =
            Number(
                prefs.darkness ?? .50
            );


        form.elements.blur.value =
            Number(
                prefs.blur || 0
            );


        form.elements.bubbleOpacity.value =
            Number(
                prefs.bubbleOpacity ?? .88
            );


        form.elements.fontSize.value =
            Number(
                prefs.fontSize || 15
            );


        updateRangeValues(
            drawer
        );
    }


    function collectSettingsForm(
        scope
    ) {

        const drawer =
            getSettingsDrawer(
                scope
            );


        const topClose =
            drawer.querySelector(
                '[data-action="top-close"]'
            );

        if(topClose){

            topClose.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    drawer.classList.remove(
                        "show"
                    );

                    drawer.setAttribute(
                        "aria-hidden",
                        "true"
                    );

                    drawer.style.removeProperty(
                        "display"
                    );

                    drawer.style.removeProperty(
                        "visibility"
                    );

                    drawer.style.removeProperty(
                        "opacity"
                    );

                    drawer.style.removeProperty(
                        "pointer-events"
                    );
                }
            );
        }


        const form =
            drawer.querySelector(
                "form"
            );


        return {
            backgroundMode:
                form.elements
                    .backgroundMode
                    .value,

            backgroundPosition:
                form.elements
                    .backgroundPosition
                    .value,

            darkness:
                Number(
                    form.elements
                        .darkness
                        .value
                ),

            blur:
                Number(
                    form.elements
                        .blur
                        .value
                ),

            bubbleOpacity:
                Number(
                    form.elements
                        .bubbleOpacity
                        .value
                ),

            fontSize:
                Number(
                    form.elements
                        .fontSize
                        .value
                )
        };
    }


    function updateRangeValues(
        drawer
    ) {

        if(!drawer){
            return;
        }


        drawer
            .querySelectorAll(
                "[data-ag-range]"
            )
            .forEach(
                function (input) {

                    const output =
                        drawer.querySelector(
                            '[data-ag-range-value="' +
                            input.name +
                            '"]'
                        );


                    if(!output){
                        return;
                    }


                    if(
                        input.name ===
                        "darkness" ||
                        input.name ===
                        "bubbleOpacity"
                    ){
                        output.textContent =
                            Math.round(
                                Number(input.value) *
                                100
                            ) +
                            "%";
                    }
                    else if(
                        input.name ===
                        "blur"
                    ){
                        output.textContent =
                            input.value +
                            "px";
                    }
                    else{
                        output.textContent =
                            input.value +
                            "px";
                    }
                }
            );
    }


    async function postWindowSettings(
        formData
    ) {

        const response =
            await fetch(
                "/Other/messenger-settings.php",
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    body:
                        formData
                }
            );


        let result =
            null;


        try{

            result =
                await response.json();

        }
        catch(error){

            throw new Error(
                "Invalid response from Messenger settings."
            );
        }


        if(
            !response.ok ||
            !result ||
            result.ok !==
                true
        ){
            throw new Error(
                (
                    result &&
                    result.error
                ) ||
                "Messenger settings request failed."
            );
        }


        return result;
    }


    async function loadWindowPreferences(
        scope
    ) {

        try{

            const data =
                new FormData();


            data.append(
                "action",
                "get_window"
            );


            data.append(
                "window",
                scope
            );


            const result =
                await postWindowSettings(
                    data
                );


            applyWindowPreferences(
                scope,
                result.preferences
            );

        }
        catch(error){

            setSettingsStatus(
                scope,
                error.message,
                true
            );
        }
    }


    async function saveWindowSettings(
        scope
    ) {

        const values =
            collectSettingsForm(
                scope
            );


        applyWindowPreferences(
            scope,
            values
        );


        const drawer =
            getSettingsDrawer(
                scope
            );


        const saveButton =
            drawer
                ? drawer.querySelector(
                    '[data-action="save"]'
                )
                : null;


        if(saveButton){

            saveButton.disabled =
                true;

            saveButton.textContent =
                "SAVING...";
        }


        setSettingsStatus(
            scope,
            "Saving...",
            false
        );


        try{

            const data =
                new FormData();


            data.append(
                "action",
                "save_window"
            );


            data.append(
                "window",
                scope
            );


            Object.keys(
                values
            )
            .forEach(
                function (key) {

                    data.append(
                        key,
                        String(
                            values[key]
                        )
                    );
                }
            );


            const result =
                await postWindowSettings(
                    data
                );


            applyWindowPreferences(
                scope,
                result.preferences
            );


            setSettingsStatus(
                scope,
                "Settings saved successfully.",
                false
            );


            if(saveButton){

                saveButton.textContent =
                    "SAVED \u2713";


                window.setTimeout(
                    function () {

                        saveButton.textContent =
                            "SAVE";

                        saveButton.disabled =
                            false;
                    },
                    1500
                );
            }

        }
        catch(error){

            setSettingsStatus(
                scope,
                error.message,
                true
            );


            if(saveButton){

                saveButton.textContent =
                    "SAVE FAILED";

                saveButton.disabled =
                    false;


                window.setTimeout(
                    function () {

                        saveButton.textContent =
                            "SAVE";
                    },
                    2000
                );
            }
        }
    }


    async function uploadWindowBackground(
        scope
    ) {

        const drawer =
            getSettingsDrawer(
                scope
            );


        const input =
            drawer.querySelector(
                'input[name="background"]'
            );


        if(
            !input ||
            !input.files ||
            !input.files[0]
        ){
            setSettingsStatus(
                scope,
                "Choose an image first.",
                true
            );

            return;
        }


        setSettingsStatus(
            scope,
            "Uploading background...",
            false
        );


        try{

            const data =
                new FormData();


            data.append(
                "action",
                "upload_window_background"
            );


            data.append(
                "window",
                scope
            );


            data.append(
                "background",
                input.files[0]
            );


            const result =
                await postWindowSettings(
                    data
                );


            if(
                localPreviewUrls[scope]
            ){
                URL.revokeObjectURL(
                    localPreviewUrls[scope]
                );

                localPreviewUrls[scope] =
                    null;
            }


            input.value =
                "";


            applyWindowPreferences(
                scope,
                result.preferences
            );


            setSettingsStatus(
                scope,
                "Background uploaded.",
                false
            );

        }
        catch(error){

            setSettingsStatus(
                scope,
                error.message,
                true
            );
        }
    }


    async function resetWindowSettings(
        scope
    ) {

        setSettingsStatus(
            scope,
            "Resetting...",
            false
        );


        try{

            const data =
                new FormData();


            data.append(
                "action",
                "reset_window"
            );


            data.append(
                "window",
                scope
            );


            const result =
                await postWindowSettings(
                    data
                );


            if(
                localPreviewUrls[scope]
            ){
                URL.revokeObjectURL(
                    localPreviewUrls[scope]
                );

                localPreviewUrls[scope] =
                    null;
            }


            applyWindowPreferences(
                scope,
                result.preferences
            );


            setSettingsStatus(
                scope,
                "Window settings reset.",
                false
            );

        }
        catch(error){

            setSettingsStatus(
                scope,
                error.message,
                true
            );
        }
    }


    function toggleSettingsDrawer(
        scope
    ) {

        const drawer =
            getSettingsDrawer(
                scope
            );


        if(!drawer){
            return;
        }


        drawer.classList.toggle(
            "show"
        );


        if(
            drawer.classList.contains(
                "show"
            )
        ){
            syncSettingsForm(
                scope
            );

            front(
                getWindowElement(
                    scope
                )
            );
        }
    }


    function buildSettingsDrawer(
        scope,
        label
    ) {

        const win =
            getWindowElement(
                scope
            );


        if(!win){
            return;
        }


        const drawer =
            document.createElement(
                "section"
            );


        drawer.id =
            "agV2Settings-" +
            scope;


        drawer.className =
            "ag-v2-settings-drawer";


        drawer.innerHTML = `
            <div class="ag-v2-settings-top">

                <h3 class="ag-v2-settings-heading">
                    ${label} SETTINGS
                </h3>

                <button
                    type="button"
                    class="ag-v2-settings-top-close"
                    data-action="top-close"
                >
                    CLOSE
                </button>

            </div>

            <div class="ag-v2-settings-subtitle">
                These settings affect only this Messenger window.
            </div>

            <form autocomplete="off">

                <div class="ag-v2-settings-grid">

                    <div class="ag-v2-setting-field ag-v2-setting-wide">
                        <label>Background Image</label>

                        <input
                            type="file"
                            name="background"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >
                    </div>

                    <div class="ag-v2-setting-field">
                        <label>Background Fit</label>

                        <select name="backgroundMode">
                            <option value="cover">Fill</option>
                            <option value="contain">Fit</option>
                            <option value="stretch">Stretch</option>
                        </select>
                    </div>

                    <div class="ag-v2-setting-field">
                        <label>Background Position</label>

                        <select name="backgroundPosition">
                            <option value="center center">Centre</option>
                            <option value="center top">Top</option>
                            <option value="center bottom">Bottom</option>
                            <option value="left center">Left</option>
                            <option value="right center">Right</option>
                        </select>
                    </div>

                    <div class="ag-v2-setting-field ag-v2-setting-wide">
                        <label>Darkness</label>

                        <div class="ag-v2-range-row">

                            <input
                                type="range"
                                name="darkness"
                                min="0"
                                max=".90"
                                step=".05"
                                data-ag-range
                            >

                            <span
                                class="ag-v2-range-value"
                                data-ag-range-value="darkness"
                            ></span>

                        </div>
                    </div>

                    <div class="ag-v2-setting-field ag-v2-setting-wide">
                        <label>Background Blur</label>

                        <div class="ag-v2-range-row">

                            <input
                                type="range"
                                name="blur"
                                min="0"
                                max="20"
                                step="1"
                                data-ag-range
                            >

                            <span
                                class="ag-v2-range-value"
                                data-ag-range-value="blur"
                            ></span>

                        </div>
                    </div>

                    <div class="ag-v2-setting-field ag-v2-setting-wide">
                        <label>Window Transparency</label>

                        <div class="ag-v2-range-row">

                            <input
                                type="range"
                                name="bubbleOpacity"
                                min=".40"
                                max="1"
                                step=".05"
                                data-ag-range
                            >

                            <span
                                class="ag-v2-range-value"
                                data-ag-range-value="bubbleOpacity"
                            ></span>

                        </div>
                    </div>

                    <div class="ag-v2-setting-field ag-v2-setting-wide">
                        <label>Font Size</label>

                        <div class="ag-v2-range-row">

                            <input
                                type="range"
                                name="fontSize"
                                min="12"
                                max="22"
                                step="1"
                                data-ag-range
                            >

                            <span
                                class="ag-v2-range-value"
                                data-ag-range-value="fontSize"
                            ></span>

                        </div>
                    </div>

                </div>

                <div class="ag-v2-settings-actions">

                    <button
                        type="button"
                        class="ag-v2-settings-action"
                        data-action="upload"
                    >
                        UPLOAD BACKGROUND
                    </button>

                    <button
                        type="button"
                        class="ag-v2-settings-action"
                        data-action="save"
                    >
                        SAVE
                    </button>

                    <button
                        type="button"
                        class="ag-v2-settings-action"
                        data-action="reset"
                    >
                        RESET
                    </button>

                    <button
                        type="button"
                        class="ag-v2-settings-action"
                        data-action="close"
                    >
                        CLOSE
                    </button>

                </div>

                <div class="ag-v2-settings-status"></div>

            </form>
        `;


        win.appendChild(
            drawer
        );


        const topClose =
            drawer.querySelector(
                '[data-action="top-close"]'
            );

        if(topClose){

            topClose.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    drawer.classList.remove(
                        "show"
                    );

                    drawer.setAttribute(
                        "aria-hidden",
                        "true"
                    );

                    drawer.style.removeProperty(
                        "display"
                    );

                    drawer.style.removeProperty(
                        "visibility"
                    );

                    drawer.style.removeProperty(
                        "opacity"
                    );

                    drawer.style.removeProperty(
                        "pointer-events"
                    );
                }
            );
        }


        const form =
            drawer.querySelector(
                "form"
            );


        form
            .querySelectorAll(
                "select,input[type='range']"
            )
            .forEach(
                function (control) {

                    control.addEventListener(
                        "input",
                        function () {

                            updateRangeValues(
                                drawer
                            );


                            applyWindowPreferences(
                                scope,
                                collectSettingsForm(
                                    scope
                                )
                            );
                        }
                    );


                    control.addEventListener(
                        "change",
                        function () {

                            updateRangeValues(
                                drawer
                            );


                            applyWindowPreferences(
                                scope,
                                collectSettingsForm(
                                    scope
                                )
                            );
                        }
                    );
                }
            );


        const fileInput =
            form.elements.background;


        fileInput.addEventListener(
            "change",
            function () {

                if(
                    localPreviewUrls[scope]
                ){
                    URL.revokeObjectURL(
                        localPreviewUrls[scope]
                    );

                    localPreviewUrls[scope] =
                        null;
                }


                if(
                    fileInput.files &&
                    fileInput.files[0]
                ){
                    localPreviewUrls[scope] =
                        URL.createObjectURL(
                            fileInput.files[0]
                        );


                    applyWindowPreferences(
                        scope,
                        collectSettingsForm(
                            scope
                        )
                    );


                    setSettingsStatus(
                        scope,
                        "Previewing selected image. Click UPLOAD BACKGROUND to keep it.",
                        false
                    );
                }
            }
        );


        drawer
            .querySelector(
                '[data-action="upload"]'
            )
            .addEventListener(
                "click",
                function () {

                    uploadWindowBackground(
                        scope
                    );
                }
            );


        drawer
            .querySelector(
                '[data-action="save"]'
            )
            .addEventListener(
                "click",
                function () {

                    saveWindowSettings(
                        scope
                    );
                }
            );


        drawer
            .querySelector(
                '[data-action="reset"]'
            )
            .addEventListener(
                "click",
                function () {

                    resetWindowSettings(
                        scope
                    );
                }
            );


        drawer
            .querySelector(
                '[data-action="close"]'
            )
            .addEventListener(
                "click",
                function () {

                    drawer.classList.remove(
                        "show"
                    );
                }
            );
    }


    function installWindowAppearanceLayer(
        scope
    ) {

        const win =
            getWindowElement(
                scope
            );


        if(!win){
            return;
        }


        if(
            !win.querySelector(
                ".ag-v2-window-wallpaper"
            )
        ){
            const wallpaper =
                document.createElement(
                    "div"
                );


            wallpaper.className =
                "ag-v2-window-wallpaper";


            const shade =
                document.createElement(
                    "div"
                );


            shade.className =
                "ag-v2-window-shade";


            win.insertBefore(
                shade,
                win.firstChild
            );


            win.insertBefore(
                wallpaper,
                win.firstChild
            );
        }
    }


    /* MESSENGER LAZY SETTINGS OPENER START */

    function openWindowSettings(
        scope,
        label
    ) {

        const win =
            getWindowElement(
                scope
            );

        if(!win){
            return;
        }


        /*
         * Ensure the wallpaper/shading layer exists.
         */

        installWindowAppearanceLayer(
            scope
        );


        /*
         * If the settings drawer was not created during
         * startup, create it now.
         */

        let drawer =
            getSettingsDrawer(
                scope
            );


        if(!drawer){

            buildSettingsDrawer(
                scope,
                label
            );


            drawer =
                getSettingsDrawer(
                    scope
                );
        }


        if(!drawer){
            return;
        }


        /*
         * Supply safe defaults immediately so the controls
         * are usable even before the saved preferences finish
         * loading from the server.
         */

        if(
            !windowPreferences[scope]
        ){

            applyWindowPreferences(
                scope,
                {
                    backgroundUrl: "",
                    backgroundMode: "cover",
                    backgroundPosition: "center center",
                    darkness: 0.50,
                    blur: 0,
                    bubbleOpacity: 0.88,
                    fontSize: 15
                }
            );
        }


        const opening =
            !drawer.classList.contains(
                "show"
            );


        if(opening){

            drawer.classList.add(
                "show"
            );


            drawer.setAttribute(
                "aria-hidden",
                "false"
            );


            syncSettingsForm(
                scope
            );


            front(
                win
            );


            /*
             * Refresh this avatar's saved settings.
             */

            loadWindowPreferences(
                scope
            );
        }
        else{

            drawer.classList.remove(
                "show"
            );


            drawer.setAttribute(
                "aria-hidden",
                "true"
            );
        }
    }

    /* MESSENGER LAZY SETTINGS OPENER END */

    /* MESSENGER SETTINGS SAFE OPENER START */

    function forceSettingsDrawerOpen(drawer) {
        if(!drawer){ return; }

        const scope = drawer.id.indexOf("friends") !== -1 ? "friends" : "chat";
        const win = getWindowElement(scope);
        const host = document.getElementById("agV2MessengerArea");

        if(host && drawer.parentNode !== host){ host.appendChild(drawer); }

        drawer.dataset.agScope = scope;
        drawer.classList.add("show");
        drawer.setAttribute("aria-hidden","false");

        const r = win ? win.getBoundingClientRect() : null;
        const x = scope === "friends"
            ? (r ? r.right + 12 : 20)
            : (r ? Math.max(12,r.left - 372) : 20);

        const y = r ? Math.max(12,r.top) : 90;

        drawer.style.setProperty("position","fixed","important");
        drawer.style.setProperty("left",x + "px","important");
        drawer.style.setProperty("right","auto","important");
        drawer.style.setProperty("top",y + "px","important");
        drawer.style.setProperty("bottom","auto","important");
        drawer.style.setProperty("width","360px","important");
        drawer.style.setProperty("max-width","calc(100vw - 24px)","important");
        drawer.style.setProperty("max-height","calc(100vh - 24px)","important");
        drawer.style.setProperty("overflow-y","auto","important");
        drawer.style.setProperty("z-index","999999","important");
        drawer.style.setProperty("border-radius","10px","important");
        drawer.style.setProperty("box-shadow","0 15px 45px rgba(0,0,0,.7)","important");
    }

    function showSettingsDiagnostic(
        scope,
        label,
        message
    ) {

        const win =
            getWindowElement(
                scope
            );


        if(!win){
            return;
        }


        let panel =
            document.getElementById(
                "agV2SettingsDiagnostic-" +
                scope
            );


        if(!panel){

            panel =
                document.createElement(
                    "div"
                );


            panel.id =
                "agV2SettingsDiagnostic-" +
                scope;


            panel.style.position =
                "absolute";

            panel.style.left =
                "0";

            panel.style.right =
                "0";

            panel.style.top =
                "58px";

            panel.style.bottom =
                "0";

            panel.style.zIndex =
                "10000";

            panel.style.boxSizing =
                "border-box";

            panel.style.padding =
                "20px";

            panel.style.overflow =
                "auto";

            panel.style.background =
                "rgba(5,11,16,.98)";

            panel.style.color =
                "#ffffff";


            win.appendChild(
                panel
            );
        }


        panel.innerHTML =
            "";


        const heading =
            document.createElement(
                "div"
            );


        heading.textContent =
            label +
            " SETTINGS ERROR";


        heading.style.color =
            "#ffc84c";

        heading.style.fontWeight =
            "900";

        heading.style.fontSize =
            "18px";

        heading.style.marginBottom =
            "14px";


        const text =
            document.createElement(
                "div"
            );


        text.textContent =
            message;


        text.style.color =
            "#ffaaaa";

        text.style.fontSize =
            "13px";

        text.style.whiteSpace =
            "pre-wrap";


        const close =
            document.createElement(
                "button"
            );


        close.type =
            "button";

        close.textContent =
            "CLOSE";


        close.style.marginTop =
            "18px";

        close.style.padding =
            "9px 18px";

        close.style.cursor =
            "pointer";


        close.addEventListener(
            "click",
            function () {

                panel.remove();
            }
        );


        panel.appendChild(
            heading
        );

        panel.appendChild(
            text
        );

        panel.appendChild(
            close
        );
    }


    function openWindowSettingsSafe(
        scope,
        label
    ) {

        const win =
            getWindowElement(
                scope
            );


        if(!win){
            return;
        }


        try{

            openWindowSettings(
                scope,
                label
            );


            window.setTimeout(
                function () {

                    const drawer =
                        getSettingsDrawer(
                            scope
                        );


                    if(drawer){

                        forceSettingsDrawerOpen(
                            drawer
                        );

                        front(
                            win
                        );

                        return;
                    }


                    showSettingsDiagnostic(
                        scope,
                        label,
                        "The SETTINGS button worked, but the settings drawer was not created."
                    );
                },
                0
            );

        }
        catch(error){

            const message =
                error &&
                error.message
                ? error.message
                : String(error);


            const drawer =
                getSettingsDrawer(
                    scope
                );


            if(drawer){

                forceSettingsDrawerOpen(
                    drawer
                );


                setSettingsStatus(
                    scope,
                    "SETTINGS ERROR: " +
                    message,
                    true
                );


                front(
                    win
                );

                return;
            }


            showSettingsDiagnostic(
                scope,
                label,
                message
            );
        }
    }

    /* MESSENGER SETTINGS SAFE OPENER END */

    function installWindowSettingsButton(
        scope,
        label
    ) {

        const win =
            getWindowElement(
                scope
            );


        if(!win){
            return;
        }


        const actions =
            win.querySelector(
                ".ag-v2-window-actions"
            );


        if(!actions){
            return;
        }


        const button =
            makeButton(
                "SETTINGS",
                "ag-v2-" +
                scope +
                "-settings"
            );


        button.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                toggleSettingsDrawer(
                    scope
                );
            }
        );


        actions.insertBefore(
            button,
            actions.firstChild
        );


        buildSettingsDrawer(
            scope,
            label
        );
    }


    function installWindowCustomisation() {

        installWindowAppearanceLayer(
            "friends"
        );


        installWindowAppearanceLayer(
            "chat"
        );


        buildSettingsDrawer(
            "friends",
            "FRIENDS"
        );


        buildSettingsDrawer(
            "chat",
            "CHAT"
        );
    }

    /* MESSENGER WINDOW SETTINGS UI V2 END */

    function buildUI() {

        const stats =
            document.querySelector(
                ".stats-grid"
            );


        if(!stats){
            return false;
        }


        area =
            document.createElement(
                "section"
            );

        area.id =
            "agV2MessengerArea";


        /*
         * FRIENDS WINDOW
         */

        friendsWindow =
            document.createElement(
                "section"
            );

        friendsWindow.id =
            "agV2FriendsWindow";

        friendsWindow.className =
            "ag-v2-real-window";


        const friendsTitlebar =
            document.createElement(
                "div"
            );

        friendsTitlebar.className =
            "ag-v2-real-titlebar";


        const friendsTitle =
            document.createElement(
                "div"
            );

        friendsTitle.className =
            "ag-v2-real-title";

        friendsTitle.textContent =
            "FRIENDS";


        const friendsActions =
            document.createElement(
                "div"
            );

        friendsActions.className =
            "ag-v2-window-actions";


        const friendsSettings =
            makeButton(
                "SETTINGS",
                "ag-v2-friends-settings"
            );


        const friendsMin =
            makeButton(
                "MINIMISE",
                "ag-v2-friends-minimise"
            );


        friendsSettings.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                openWindowSettingsSafe(
                    "friends",
                    "FRIENDS"
                );
            }
        );


        friendsMin.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                minimiseFriends();
            }
        );


        friendsActions.appendChild(
            friendsSettings
        );

        friendsActions.appendChild(
            friendsMin
        );


        friendsTitlebar.appendChild(
            friendsTitle
        );

        friendsTitlebar.appendChild(
            friendsActions
        );


        const friendsBody =
            document.createElement(
                "div"
            );

        friendsBody.className =
            "ag-v2-friends-body";


        const searchWrap =
            document.createElement(
                "div"
            );

        searchWrap.className =
            "ag-v2-friends-search-wrap";


        const search =
            document.createElement(
                "input"
            );

        search.id =
            "agV2FriendsSearch";

        search.type =
            "search";

        search.placeholder =
            "Search Friends...";

        search.autocomplete =
            "off";


        searchWrap.appendChild(
            search
        );


        friendsList =
            document.createElement(
                "div"
            );

        friendsList.id =
            "agV2FriendsList";


        friendsBody.appendChild(
            searchWrap
        );

        friendsBody.appendChild(
            friendsList
        );


        friendsWindow.appendChild(
            friendsTitlebar
        );

        friendsWindow.appendChild(
            friendsBody
        );


        /*
         * CHAT WINDOW
         */

        chatWindow =
            document.createElement(
                "section"
            );

        chatWindow.id =
            "agV2ChatWindow";

        chatWindow.className =
            "ag-v2-real-window";


        const chatTitlebar =
            document.createElement(
                "div"
            );

        chatTitlebar.className =
            "ag-v2-real-titlebar";


        chatTitle =
            document.createElement(
                "div"
            );

        chatTitle.className =
            "ag-v2-real-title";

        chatTitle.textContent =
            "CHAT";


        const chatActions =
            document.createElement(
                "div"
            );

        chatActions.className =
            "ag-v2-window-actions";


        const chatSettings =
            makeButton(
                "SETTINGS",
                "ag-v2-chat-settings"
            );


        const chatMin =
            makeButton(
                "MINIMISE",
                "ag-v2-chat-minimise"
            );


        const chatClose =
            makeButton(
                "CLOSE",
                "ag-v2-chat-close"
            );


        chatSettings.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                openWindowSettingsSafe(
                    "chat",
                    "CHAT"
                );
            }
        );


        chatMin.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                minimiseChat();
            }
        );


        chatClose.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                closeChat();
            }
        );


        chatActions.appendChild(
            chatSettings
        );

        chatActions.appendChild(
            chatMin
        );

        chatActions.appendChild(
            chatClose
        );


        chatTitlebar.appendChild(
            chatTitle
        );

        chatTitlebar.appendChild(
            chatActions
        );


        const chatBody =
            document.createElement(
                "div"
            );

        chatBody.className =
            "ag-v2-chat-body";


        chatHistory =
            document.createElement(
                "div"
            );

        chatHistory.id =
            "agV2ChatHistory";


        const composer =
            document.createElement(
                "div"
            );

        composer.id =
            "agV2Composer";


        messageBox =
            document.createElement(
                "textarea"
            );

        messageBox.id =
            "agV2Message";

        messageBox.placeholder =
            "Type your message...";

        messageBox.maxLength =
            1000;


        const send =
            document.createElement(
                "button"
            );

        send.id =
            "agV2Send";

        send.type =
            "button";

        send.textContent =
            "SEND";


        send.addEventListener(
            "click",
            sendMessage
        );


        messageBox.addEventListener(
            "keydown",
            function (event) {

                if(
                    event.key ===
                        "Enter" &&
                    !event.shiftKey
                ){
                    event.preventDefault();

                    sendMessage();
                }
            }
        );


        composer.appendChild(
            messageBox
        );

        composer.appendChild(
            send
        );


        chatBody.appendChild(
            chatHistory
        );

        chatBody.appendChild(
            composer
        );


        chatWindow.appendChild(
            chatTitlebar
        );

        chatWindow.appendChild(
            chatBody
        );


        area.appendChild(
            friendsWindow
        );

        area.appendChild(
            chatWindow
        );


        stats.insertAdjacentElement(
            "afterend",
            area
        );


        makeDraggable(
            friendsWindow,
            friendsTitlebar
        );


        makeDraggable(
            chatWindow,
            chatTitlebar
        );


        if(
            typeof ResizeObserver !==
            "undefined"
        ){
            const observer =
                new ResizeObserver(
                    updateAreaHeight
                );

            observer.observe(
                friendsWindow
            );

            observer.observe(
                chatWindow
            );
        }


        return true;
    }


    function replaceTopFriendsButton() {

        /*
         * Existing header FRIENDS button now restores our
         * actual new Friends window.
         */

        window.australiaOpenFriendsList =
            function () {

                restoreFriends();
            };


        window.australiaCloseFriendsList =
            function () {

                minimiseFriends();
            };
    }


    function install() {

        if(
            document.body.dataset
                .agV2Isolated ===
            "1"
        ){
            return;
        }


        legacyFriends =
            document.getElementById(
                "ausFriendsPanel"
            );


        legacyChat =
            document.getElementById(
                "ausFriendOverlay"
            );


        if(
            !legacyFriends ||
            !legacyChat
        ){
            return;
        }


        if(
            !buildUI()
        ){
            return;
        }


        document.body.dataset
            .agV2Isolated =
            "1";


        installWindowCustomisation();


        loadWindowPreferences(
            "friends"
        );


        loadWindowPreferences(
            "chat"
        );


        buildFriendList();

        installSearch();

        replaceTopFriendsButton();

        front(
            friendsWindow
        );

        updateAreaHeight();
    }


    if(
        document.readyState ===
        "loading"
    ){
        document.addEventListener(
            "DOMContentLoaded",
            install
        );
    }
    else{
        install();
    }

})();




/* ==========================================================
   AUSTRALIA CHAT EMOTES V2 START
   FULL UNICODE CATALOG
   ========================================================== */

(function () {

    "use strict";

    const categories = {"SMILEYS":[{"e":"😀","n":"grinning face","s":"face-smiling"},{"e":"😃","n":"grinning face with big eyes","s":"face-smiling"},{"e":"😄","n":"grinning face with smiling eyes","s":"face-smiling"},{"e":"😁","n":"beaming face with smiling eyes","s":"face-smiling"},{"e":"😆","n":"grinning squinting face","s":"face-smiling"},{"e":"😅","n":"grinning face with sweat","s":"face-smiling"},{"e":"🤣","n":"rolling on the floor laughing","s":"face-smiling"},{"e":"😂","n":"face with tears of joy","s":"face-smiling"},{"e":"🙂","n":"slightly smiling face","s":"face-smiling"},{"e":"🙃","n":"upside-down face","s":"face-smiling"},{"e":"🫠","n":"melting face","s":"face-smiling"},{"e":"🫫","n":"cracking face","s":"face-smiling"},{"e":"😉","n":"winking face","s":"face-smiling"},{"e":"😊","n":"smiling face with smiling eyes","s":"face-smiling"},{"e":"😇","n":"smiling face with halo","s":"face-smiling"},{"e":"🥰","n":"smiling face with hearts","s":"face-affection"},{"e":"😍","n":"smiling face with heart-eyes","s":"face-affection"},{"e":"🤩","n":"star-struck","s":"face-affection"},{"e":"😘","n":"face blowing a kiss","s":"face-affection"},{"e":"😗","n":"kissing face","s":"face-affection"},{"e":"☺️","n":"smiling face","s":"face-affection"},{"e":"😚","n":"kissing face with closed eyes","s":"face-affection"},{"e":"😙","n":"kissing face with smiling eyes","s":"face-affection"},{"e":"🥲","n":"smiling face with tear","s":"face-affection"},{"e":"😋","n":"face savoring food","s":"face-tongue"},{"e":"😛","n":"face with tongue","s":"face-tongue"},{"e":"😜","n":"winking face with tongue","s":"face-tongue"},{"e":"🤪","n":"zany face","s":"face-tongue"},{"e":"😝","n":"squinting face with tongue","s":"face-tongue"},{"e":"🤑","n":"money-mouth face","s":"face-tongue"},{"e":"🤗","n":"smiling face with open hands","s":"face-hand"},{"e":"🤭","n":"face with hand over mouth","s":"face-hand"},{"e":"🫢","n":"face with open eyes and hand over mouth","s":"face-hand"},{"e":"🫣","n":"face with peeking eye","s":"face-hand"},{"e":"🤫","n":"shushing face","s":"face-hand"},{"e":"🤔","n":"thinking face","s":"face-hand"},{"e":"🫡","n":"saluting face","s":"face-hand"},{"e":"🤐","n":"zipper-mouth face","s":"face-neutral-skeptical"},{"e":"🤨","n":"face with raised eyebrow","s":"face-neutral-skeptical"},{"e":"😐","n":"neutral face","s":"face-neutral-skeptical"},{"e":"😑","n":"expressionless face","s":"face-neutral-skeptical"},{"e":"😶","n":"face without mouth","s":"face-neutral-skeptical"},{"e":"🫥","n":"dotted line face","s":"face-neutral-skeptical"},{"e":"😶‍🌫️","n":"face in clouds","s":"face-neutral-skeptical"},{"e":"😏","n":"smirking face","s":"face-neutral-skeptical"},{"e":"😒","n":"unamused face","s":"face-neutral-skeptical"},{"e":"🙄","n":"face with rolling eyes","s":"face-neutral-skeptical"},{"e":"😬","n":"grimacing face","s":"face-neutral-skeptical"},{"e":"😮‍💨","n":"face exhaling","s":"face-neutral-skeptical"},{"e":"🤥","n":"lying face","s":"face-neutral-skeptical"},{"e":"🫨","n":"shaking face","s":"face-neutral-skeptical"},{"e":"🙂‍↔️","n":"head shaking horizontally","s":"face-neutral-skeptical"},{"e":"🙂‍↕️","n":"head shaking vertically","s":"face-neutral-skeptical"},{"e":"😌","n":"relieved face","s":"face-sleepy"},{"e":"😔","n":"pensive face","s":"face-sleepy"},{"e":"😪","n":"sleepy face","s":"face-sleepy"},{"e":"🤤","n":"drooling face","s":"face-sleepy"},{"e":"😴","n":"sleeping face","s":"face-sleepy"},{"e":"🫩","n":"face with bags under eyes","s":"face-sleepy"},{"e":"😷","n":"face with medical mask","s":"face-unwell"},{"e":"🤒","n":"face with thermometer","s":"face-unwell"},{"e":"🤕","n":"face with head-bandage","s":"face-unwell"},{"e":"🤢","n":"nauseated face","s":"face-unwell"},{"e":"🤮","n":"face vomiting","s":"face-unwell"},{"e":"🤧","n":"sneezing face","s":"face-unwell"},{"e":"🥵","n":"hot face","s":"face-unwell"},{"e":"🥶","n":"cold face","s":"face-unwell"},{"e":"🥴","n":"woozy face","s":"face-unwell"},{"e":"😵","n":"face with crossed-out eyes","s":"face-unwell"},{"e":"😵‍💫","n":"face with spiral eyes","s":"face-unwell"},{"e":"🤯","n":"exploding head","s":"face-unwell"},{"e":"🤠","n":"cowboy hat face","s":"face-hat"},{"e":"🥳","n":"partying face","s":"face-hat"},{"e":"🥸","n":"disguised face","s":"face-hat"},{"e":"😎","n":"smiling face with sunglasses","s":"face-glasses"},{"e":"🤓","n":"nerd face","s":"face-glasses"},{"e":"🧐","n":"face with monocle","s":"face-glasses"},{"e":"😕","n":"confused face","s":"face-concerned"},{"e":"🫤","n":"face with diagonal mouth","s":"face-concerned"},{"e":"😟","n":"worried face","s":"face-concerned"},{"e":"🙁","n":"slightly frowning face","s":"face-concerned"},{"e":"☹️","n":"frowning face","s":"face-concerned"},{"e":"😮","n":"face with open mouth","s":"face-concerned"},{"e":"😯","n":"hushed face","s":"face-concerned"},{"e":"😲","n":"astonished face","s":"face-concerned"},{"e":"😳","n":"flushed face","s":"face-concerned"},{"e":"🫪","n":"distorted face","s":"face-concerned"},{"e":"🥺","n":"pleading face","s":"face-concerned"},{"e":"🥹","n":"face holding back tears","s":"face-concerned"},{"e":"😦","n":"frowning face with open mouth","s":"face-concerned"},{"e":"😧","n":"anguished face","s":"face-concerned"},{"e":"😨","n":"fearful face","s":"face-concerned"},{"e":"😰","n":"anxious face with sweat","s":"face-concerned"},{"e":"😥","n":"sad but relieved face","s":"face-concerned"},{"e":"😢","n":"crying face","s":"face-concerned"},{"e":"😭","n":"loudly crying face","s":"face-concerned"},{"e":"😱","n":"face screaming in fear","s":"face-concerned"},{"e":"😖","n":"confounded face","s":"face-concerned"},{"e":"😣","n":"persevering face","s":"face-concerned"},{"e":"😞","n":"disappointed face","s":"face-concerned"},{"e":"😓","n":"downcast face with sweat","s":"face-concerned"},{"e":"😩","n":"weary face","s":"face-concerned"},{"e":"😫","n":"tired face","s":"face-concerned"},{"e":"🥱","n":"yawning face","s":"face-concerned"},{"e":"😤","n":"face with steam from nose","s":"face-negative"},{"e":"😡","n":"enraged face","s":"face-negative"},{"e":"😠","n":"angry face","s":"face-negative"},{"e":"🤬","n":"face with symbols on mouth","s":"face-negative"},{"e":"😈","n":"smiling face with horns","s":"face-negative"},{"e":"👿","n":"angry face with horns","s":"face-negative"},{"e":"💀","n":"skull","s":"face-negative"},{"e":"☠️","n":"skull and crossbones","s":"face-negative"},{"e":"💩","n":"pile of poo","s":"face-costume"},{"e":"🤡","n":"clown face","s":"face-costume"},{"e":"👹","n":"ogre","s":"face-costume"},{"e":"👺","n":"goblin","s":"face-costume"},{"e":"👻","n":"ghost","s":"face-costume"},{"e":"👽","n":"alien","s":"face-costume"},{"e":"👾","n":"alien monster","s":"face-costume"},{"e":"🤖","n":"robot","s":"face-costume"},{"e":"😺","n":"grinning cat","s":"cat-face"},{"e":"😸","n":"grinning cat with smiling eyes","s":"cat-face"},{"e":"😹","n":"cat with tears of joy","s":"cat-face"},{"e":"😻","n":"smiling cat with heart-eyes","s":"cat-face"},{"e":"😼","n":"cat with wry smile","s":"cat-face"},{"e":"😽","n":"kissing cat","s":"cat-face"},{"e":"🙀","n":"weary cat","s":"cat-face"},{"e":"😿","n":"crying cat","s":"cat-face"},{"e":"😾","n":"pouting cat","s":"cat-face"},{"e":"🙈","n":"see-no-evil monkey","s":"monkey-face"},{"e":"🙉","n":"hear-no-evil monkey","s":"monkey-face"},{"e":"🙊","n":"speak-no-evil monkey","s":"monkey-face"},{"e":"💌","n":"love letter","s":"heart"},{"e":"💘","n":"heart with arrow","s":"heart"},{"e":"💝","n":"heart with ribbon","s":"heart"},{"e":"💖","n":"sparkling heart","s":"heart"},{"e":"💗","n":"growing heart","s":"heart"},{"e":"💓","n":"beating heart","s":"heart"},{"e":"💞","n":"revolving hearts","s":"heart"},{"e":"💕","n":"two hearts","s":"heart"},{"e":"💟","n":"heart decoration","s":"heart"},{"e":"❣️","n":"heart exclamation","s":"heart"},{"e":"💔","n":"broken heart","s":"heart"},{"e":"❤️‍🔥","n":"heart on fire","s":"heart"},{"e":"❤️‍🩹","n":"mending heart","s":"heart"},{"e":"❤️","n":"red heart","s":"heart"},{"e":"🩷","n":"pink heart","s":"heart"},{"e":"🧡","n":"orange heart","s":"heart"},{"e":"💛","n":"yellow heart","s":"heart"},{"e":"💚","n":"green heart","s":"heart"},{"e":"💙","n":"blue heart","s":"heart"},{"e":"🩵","n":"light blue heart","s":"heart"},{"e":"💜","n":"purple heart","s":"heart"},{"e":"🤎","n":"brown heart","s":"heart"},{"e":"🖤","n":"black heart","s":"heart"},{"e":"🩶","n":"grey heart","s":"heart"},{"e":"🤍","n":"white heart","s":"heart"},{"e":"💋","n":"kiss mark","s":"emotion"},{"e":"💯","n":"hundred points","s":"emotion"},{"e":"💢","n":"anger symbol","s":"emotion"},{"e":"🫯","n":"fight cloud","s":"emotion"},{"e":"💥","n":"collision","s":"emotion"},{"e":"💫","n":"dizzy","s":"emotion"},{"e":"💦","n":"sweat droplets","s":"emotion"},{"e":"💨","n":"dashing away","s":"emotion"},{"e":"🕳️","n":"hole","s":"emotion"},{"e":"💬","n":"speech balloon","s":"emotion"},{"e":"👁️‍🗨️","n":"eye in speech bubble","s":"emotion"},{"e":"🗨️","n":"left speech bubble","s":"emotion"},{"e":"🗯️","n":"right anger bubble","s":"emotion"},{"e":"💭","n":"thought balloon","s":"emotion"},{"e":"💤","n":"ZZZ","s":"emotion"}],"PEOPLE":[{"e":"👋","n":"waving hand","s":"hand-fingers-open"},{"e":"👋🏻","n":"waving hand: light skin tone","s":"hand-fingers-open"},{"e":"👋🏼","n":"waving hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"👋🏽","n":"waving hand: medium skin tone","s":"hand-fingers-open"},{"e":"👋🏾","n":"waving hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"👋🏿","n":"waving hand: dark skin tone","s":"hand-fingers-open"},{"e":"🤚","n":"raised back of hand","s":"hand-fingers-open"},{"e":"🤚🏻","n":"raised back of hand: light skin tone","s":"hand-fingers-open"},{"e":"🤚🏼","n":"raised back of hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"🤚🏽","n":"raised back of hand: medium skin tone","s":"hand-fingers-open"},{"e":"🤚🏾","n":"raised back of hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🤚🏿","n":"raised back of hand: dark skin tone","s":"hand-fingers-open"},{"e":"🖐️","n":"hand with fingers splayed","s":"hand-fingers-open"},{"e":"🖐🏻","n":"hand with fingers splayed: light skin tone","s":"hand-fingers-open"},{"e":"🖐🏼","n":"hand with fingers splayed: medium-light skin tone","s":"hand-fingers-open"},{"e":"🖐🏽","n":"hand with fingers splayed: medium skin tone","s":"hand-fingers-open"},{"e":"🖐🏾","n":"hand with fingers splayed: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🖐🏿","n":"hand with fingers splayed: dark skin tone","s":"hand-fingers-open"},{"e":"✋","n":"raised hand","s":"hand-fingers-open"},{"e":"✋🏻","n":"raised hand: light skin tone","s":"hand-fingers-open"},{"e":"✋🏼","n":"raised hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"✋🏽","n":"raised hand: medium skin tone","s":"hand-fingers-open"},{"e":"✋🏾","n":"raised hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"✋🏿","n":"raised hand: dark skin tone","s":"hand-fingers-open"},{"e":"🖖","n":"vulcan salute","s":"hand-fingers-open"},{"e":"🖖🏻","n":"vulcan salute: light skin tone","s":"hand-fingers-open"},{"e":"🖖🏼","n":"vulcan salute: medium-light skin tone","s":"hand-fingers-open"},{"e":"🖖🏽","n":"vulcan salute: medium skin tone","s":"hand-fingers-open"},{"e":"🖖🏾","n":"vulcan salute: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🖖🏿","n":"vulcan salute: dark skin tone","s":"hand-fingers-open"},{"e":"🫱","n":"rightwards hand","s":"hand-fingers-open"},{"e":"🫱🏻","n":"rightwards hand: light skin tone","s":"hand-fingers-open"},{"e":"🫱🏼","n":"rightwards hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"🫱🏽","n":"rightwards hand: medium skin tone","s":"hand-fingers-open"},{"e":"🫱🏾","n":"rightwards hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🫱🏿","n":"rightwards hand: dark skin tone","s":"hand-fingers-open"},{"e":"🫲","n":"leftwards hand","s":"hand-fingers-open"},{"e":"🫲🏻","n":"leftwards hand: light skin tone","s":"hand-fingers-open"},{"e":"🫲🏼","n":"leftwards hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"🫲🏽","n":"leftwards hand: medium skin tone","s":"hand-fingers-open"},{"e":"🫲🏾","n":"leftwards hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🫲🏿","n":"leftwards hand: dark skin tone","s":"hand-fingers-open"},{"e":"🫳","n":"palm down hand","s":"hand-fingers-open"},{"e":"🫳🏻","n":"palm down hand: light skin tone","s":"hand-fingers-open"},{"e":"🫳🏼","n":"palm down hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"🫳🏽","n":"palm down hand: medium skin tone","s":"hand-fingers-open"},{"e":"🫳🏾","n":"palm down hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🫳🏿","n":"palm down hand: dark skin tone","s":"hand-fingers-open"},{"e":"🫴","n":"palm up hand","s":"hand-fingers-open"},{"e":"🫴🏻","n":"palm up hand: light skin tone","s":"hand-fingers-open"},{"e":"🫴🏼","n":"palm up hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"🫴🏽","n":"palm up hand: medium skin tone","s":"hand-fingers-open"},{"e":"🫴🏾","n":"palm up hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🫴🏿","n":"palm up hand: dark skin tone","s":"hand-fingers-open"},{"e":"🫷","n":"leftwards pushing hand","s":"hand-fingers-open"},{"e":"🫷🏻","n":"leftwards pushing hand: light skin tone","s":"hand-fingers-open"},{"e":"🫷🏼","n":"leftwards pushing hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"🫷🏽","n":"leftwards pushing hand: medium skin tone","s":"hand-fingers-open"},{"e":"🫷🏾","n":"leftwards pushing hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🫷🏿","n":"leftwards pushing hand: dark skin tone","s":"hand-fingers-open"},{"e":"🫸","n":"rightwards pushing hand","s":"hand-fingers-open"},{"e":"🫸🏻","n":"rightwards pushing hand: light skin tone","s":"hand-fingers-open"},{"e":"🫸🏼","n":"rightwards pushing hand: medium-light skin tone","s":"hand-fingers-open"},{"e":"🫸🏽","n":"rightwards pushing hand: medium skin tone","s":"hand-fingers-open"},{"e":"🫸🏾","n":"rightwards pushing hand: medium-dark skin tone","s":"hand-fingers-open"},{"e":"🫸🏿","n":"rightwards pushing hand: dark skin tone","s":"hand-fingers-open"},{"e":"👌","n":"OK hand","s":"hand-fingers-partial"},{"e":"👌🏻","n":"OK hand: light skin tone","s":"hand-fingers-partial"},{"e":"👌🏼","n":"OK hand: medium-light skin tone","s":"hand-fingers-partial"},{"e":"👌🏽","n":"OK hand: medium skin tone","s":"hand-fingers-partial"},{"e":"👌🏾","n":"OK hand: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"👌🏿","n":"OK hand: dark skin tone","s":"hand-fingers-partial"},{"e":"🤌","n":"pinched fingers","s":"hand-fingers-partial"},{"e":"🤌🏻","n":"pinched fingers: light skin tone","s":"hand-fingers-partial"},{"e":"🤌🏼","n":"pinched fingers: medium-light skin tone","s":"hand-fingers-partial"},{"e":"🤌🏽","n":"pinched fingers: medium skin tone","s":"hand-fingers-partial"},{"e":"🤌🏾","n":"pinched fingers: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"🤌🏿","n":"pinched fingers: dark skin tone","s":"hand-fingers-partial"},{"e":"🤏","n":"pinching hand","s":"hand-fingers-partial"},{"e":"🤏🏻","n":"pinching hand: light skin tone","s":"hand-fingers-partial"},{"e":"🤏🏼","n":"pinching hand: medium-light skin tone","s":"hand-fingers-partial"},{"e":"🤏🏽","n":"pinching hand: medium skin tone","s":"hand-fingers-partial"},{"e":"🤏🏾","n":"pinching hand: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"🤏🏿","n":"pinching hand: dark skin tone","s":"hand-fingers-partial"},{"e":"✌️","n":"victory hand","s":"hand-fingers-partial"},{"e":"✌🏻","n":"victory hand: light skin tone","s":"hand-fingers-partial"},{"e":"✌🏼","n":"victory hand: medium-light skin tone","s":"hand-fingers-partial"},{"e":"✌🏽","n":"victory hand: medium skin tone","s":"hand-fingers-partial"},{"e":"✌🏾","n":"victory hand: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"✌🏿","n":"victory hand: dark skin tone","s":"hand-fingers-partial"},{"e":"🤞","n":"crossed fingers","s":"hand-fingers-partial"},{"e":"🤞🏻","n":"crossed fingers: light skin tone","s":"hand-fingers-partial"},{"e":"🤞🏼","n":"crossed fingers: medium-light skin tone","s":"hand-fingers-partial"},{"e":"🤞🏽","n":"crossed fingers: medium skin tone","s":"hand-fingers-partial"},{"e":"🤞🏾","n":"crossed fingers: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"🤞🏿","n":"crossed fingers: dark skin tone","s":"hand-fingers-partial"},{"e":"🫰","n":"hand with index finger and thumb crossed","s":"hand-fingers-partial"},{"e":"🫰🏻","n":"hand with index finger and thumb crossed: light skin tone","s":"hand-fingers-partial"},{"e":"🫰🏼","n":"hand with index finger and thumb crossed: medium-light skin tone","s":"hand-fingers-partial"},{"e":"🫰🏽","n":"hand with index finger and thumb crossed: medium skin tone","s":"hand-fingers-partial"},{"e":"🫰🏾","n":"hand with index finger and thumb crossed: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"🫰🏿","n":"hand with index finger and thumb crossed: dark skin tone","s":"hand-fingers-partial"},{"e":"🤟","n":"love-you gesture","s":"hand-fingers-partial"},{"e":"🤟🏻","n":"love-you gesture: light skin tone","s":"hand-fingers-partial"},{"e":"🤟🏼","n":"love-you gesture: medium-light skin tone","s":"hand-fingers-partial"},{"e":"🤟🏽","n":"love-you gesture: medium skin tone","s":"hand-fingers-partial"},{"e":"🤟🏾","n":"love-you gesture: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"🤟🏿","n":"love-you gesture: dark skin tone","s":"hand-fingers-partial"},{"e":"🤘","n":"sign of the horns","s":"hand-fingers-partial"},{"e":"🤘🏻","n":"sign of the horns: light skin tone","s":"hand-fingers-partial"},{"e":"🤘🏼","n":"sign of the horns: medium-light skin tone","s":"hand-fingers-partial"},{"e":"🤘🏽","n":"sign of the horns: medium skin tone","s":"hand-fingers-partial"},{"e":"🤘🏾","n":"sign of the horns: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"🤘🏿","n":"sign of the horns: dark skin tone","s":"hand-fingers-partial"},{"e":"🤙","n":"call me hand","s":"hand-fingers-partial"},{"e":"🤙🏻","n":"call me hand: light skin tone","s":"hand-fingers-partial"},{"e":"🤙🏼","n":"call me hand: medium-light skin tone","s":"hand-fingers-partial"},{"e":"🤙🏽","n":"call me hand: medium skin tone","s":"hand-fingers-partial"},{"e":"🤙🏾","n":"call me hand: medium-dark skin tone","s":"hand-fingers-partial"},{"e":"🤙🏿","n":"call me hand: dark skin tone","s":"hand-fingers-partial"},{"e":"👈","n":"backhand index pointing left","s":"hand-single-finger"},{"e":"👈🏻","n":"backhand index pointing left: light skin tone","s":"hand-single-finger"},{"e":"👈🏼","n":"backhand index pointing left: medium-light skin tone","s":"hand-single-finger"},{"e":"👈🏽","n":"backhand index pointing left: medium skin tone","s":"hand-single-finger"},{"e":"👈🏾","n":"backhand index pointing left: medium-dark skin tone","s":"hand-single-finger"},{"e":"👈🏿","n":"backhand index pointing left: dark skin tone","s":"hand-single-finger"},{"e":"👉","n":"backhand index pointing right","s":"hand-single-finger"},{"e":"👉🏻","n":"backhand index pointing right: light skin tone","s":"hand-single-finger"},{"e":"👉🏼","n":"backhand index pointing right: medium-light skin tone","s":"hand-single-finger"},{"e":"👉🏽","n":"backhand index pointing right: medium skin tone","s":"hand-single-finger"},{"e":"👉🏾","n":"backhand index pointing right: medium-dark skin tone","s":"hand-single-finger"},{"e":"👉🏿","n":"backhand index pointing right: dark skin tone","s":"hand-single-finger"},{"e":"👆","n":"backhand index pointing up","s":"hand-single-finger"},{"e":"👆🏻","n":"backhand index pointing up: light skin tone","s":"hand-single-finger"},{"e":"👆🏼","n":"backhand index pointing up: medium-light skin tone","s":"hand-single-finger"},{"e":"👆🏽","n":"backhand index pointing up: medium skin tone","s":"hand-single-finger"},{"e":"👆🏾","n":"backhand index pointing up: medium-dark skin tone","s":"hand-single-finger"},{"e":"👆🏿","n":"backhand index pointing up: dark skin tone","s":"hand-single-finger"},{"e":"🖕","n":"middle finger","s":"hand-single-finger"},{"e":"🖕🏻","n":"middle finger: light skin tone","s":"hand-single-finger"},{"e":"🖕🏼","n":"middle finger: medium-light skin tone","s":"hand-single-finger"},{"e":"🖕🏽","n":"middle finger: medium skin tone","s":"hand-single-finger"},{"e":"🖕🏾","n":"middle finger: medium-dark skin tone","s":"hand-single-finger"},{"e":"🖕🏿","n":"middle finger: dark skin tone","s":"hand-single-finger"},{"e":"👇","n":"backhand index pointing down","s":"hand-single-finger"},{"e":"👇🏻","n":"backhand index pointing down: light skin tone","s":"hand-single-finger"},{"e":"👇🏼","n":"backhand index pointing down: medium-light skin tone","s":"hand-single-finger"},{"e":"👇🏽","n":"backhand index pointing down: medium skin tone","s":"hand-single-finger"},{"e":"👇🏾","n":"backhand index pointing down: medium-dark skin tone","s":"hand-single-finger"},{"e":"👇🏿","n":"backhand index pointing down: dark skin tone","s":"hand-single-finger"},{"e":"☝️","n":"index pointing up","s":"hand-single-finger"},{"e":"☝🏻","n":"index pointing up: light skin tone","s":"hand-single-finger"},{"e":"☝🏼","n":"index pointing up: medium-light skin tone","s":"hand-single-finger"},{"e":"☝🏽","n":"index pointing up: medium skin tone","s":"hand-single-finger"},{"e":"☝🏾","n":"index pointing up: medium-dark skin tone","s":"hand-single-finger"},{"e":"☝🏿","n":"index pointing up: dark skin tone","s":"hand-single-finger"},{"e":"🫵","n":"index pointing at the viewer","s":"hand-single-finger"},{"e":"🫵🏻","n":"index pointing at the viewer: light skin tone","s":"hand-single-finger"},{"e":"🫵🏼","n":"index pointing at the viewer: medium-light skin tone","s":"hand-single-finger"},{"e":"🫵🏽","n":"index pointing at the viewer: medium skin tone","s":"hand-single-finger"},{"e":"🫵🏾","n":"index pointing at the viewer: medium-dark skin tone","s":"hand-single-finger"},{"e":"🫵🏿","n":"index pointing at the viewer: dark skin tone","s":"hand-single-finger"},{"e":"👍","n":"thumbs up","s":"hand-fingers-closed"},{"e":"👍🏻","n":"thumbs up: light skin tone","s":"hand-fingers-closed"},{"e":"👍🏼","n":"thumbs up: medium-light skin tone","s":"hand-fingers-closed"},{"e":"👍🏽","n":"thumbs up: medium skin tone","s":"hand-fingers-closed"},{"e":"👍🏾","n":"thumbs up: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"👍🏿","n":"thumbs up: dark skin tone","s":"hand-fingers-closed"},{"e":"👎","n":"thumbs down","s":"hand-fingers-closed"},{"e":"👎🏻","n":"thumbs down: light skin tone","s":"hand-fingers-closed"},{"e":"👎🏼","n":"thumbs down: medium-light skin tone","s":"hand-fingers-closed"},{"e":"👎🏽","n":"thumbs down: medium skin tone","s":"hand-fingers-closed"},{"e":"👎🏾","n":"thumbs down: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"👎🏿","n":"thumbs down: dark skin tone","s":"hand-fingers-closed"},{"e":"🫹","n":"leftwards thumb sign","s":"hand-fingers-closed"},{"e":"🫹🏻","n":"leftwards thumb sign: light skin tone","s":"hand-fingers-closed"},{"e":"🫹🏼","n":"leftwards thumb sign: medium-light skin tone","s":"hand-fingers-closed"},{"e":"🫹🏽","n":"leftwards thumb sign: medium skin tone","s":"hand-fingers-closed"},{"e":"🫹🏾","n":"leftwards thumb sign: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"🫹🏿","n":"leftwards thumb sign: dark skin tone","s":"hand-fingers-closed"},{"e":"🫺","n":"rightwards thumb sign","s":"hand-fingers-closed"},{"e":"🫺🏻","n":"rightwards thumb sign: light skin tone","s":"hand-fingers-closed"},{"e":"🫺🏼","n":"rightwards thumb sign: medium-light skin tone","s":"hand-fingers-closed"},{"e":"🫺🏽","n":"rightwards thumb sign: medium skin tone","s":"hand-fingers-closed"},{"e":"🫺🏾","n":"rightwards thumb sign: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"🫺🏿","n":"rightwards thumb sign: dark skin tone","s":"hand-fingers-closed"},{"e":"✊","n":"raised fist","s":"hand-fingers-closed"},{"e":"✊🏻","n":"raised fist: light skin tone","s":"hand-fingers-closed"},{"e":"✊🏼","n":"raised fist: medium-light skin tone","s":"hand-fingers-closed"},{"e":"✊🏽","n":"raised fist: medium skin tone","s":"hand-fingers-closed"},{"e":"✊🏾","n":"raised fist: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"✊🏿","n":"raised fist: dark skin tone","s":"hand-fingers-closed"},{"e":"👊","n":"oncoming fist","s":"hand-fingers-closed"},{"e":"👊🏻","n":"oncoming fist: light skin tone","s":"hand-fingers-closed"},{"e":"👊🏼","n":"oncoming fist: medium-light skin tone","s":"hand-fingers-closed"},{"e":"👊🏽","n":"oncoming fist: medium skin tone","s":"hand-fingers-closed"},{"e":"👊🏾","n":"oncoming fist: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"👊🏿","n":"oncoming fist: dark skin tone","s":"hand-fingers-closed"},{"e":"🤛","n":"left-facing fist","s":"hand-fingers-closed"},{"e":"🤛🏻","n":"left-facing fist: light skin tone","s":"hand-fingers-closed"},{"e":"🤛🏼","n":"left-facing fist: medium-light skin tone","s":"hand-fingers-closed"},{"e":"🤛🏽","n":"left-facing fist: medium skin tone","s":"hand-fingers-closed"},{"e":"🤛🏾","n":"left-facing fist: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"🤛🏿","n":"left-facing fist: dark skin tone","s":"hand-fingers-closed"},{"e":"🤜","n":"right-facing fist","s":"hand-fingers-closed"},{"e":"🤜🏻","n":"right-facing fist: light skin tone","s":"hand-fingers-closed"},{"e":"🤜🏼","n":"right-facing fist: medium-light skin tone","s":"hand-fingers-closed"},{"e":"🤜🏽","n":"right-facing fist: medium skin tone","s":"hand-fingers-closed"},{"e":"🤜🏾","n":"right-facing fist: medium-dark skin tone","s":"hand-fingers-closed"},{"e":"🤜🏿","n":"right-facing fist: dark skin tone","s":"hand-fingers-closed"},{"e":"👏","n":"clapping hands","s":"hands"},{"e":"👏🏻","n":"clapping hands: light skin tone","s":"hands"},{"e":"👏🏼","n":"clapping hands: medium-light skin tone","s":"hands"},{"e":"👏🏽","n":"clapping hands: medium skin tone","s":"hands"},{"e":"👏🏾","n":"clapping hands: medium-dark skin tone","s":"hands"},{"e":"👏🏿","n":"clapping hands: dark skin tone","s":"hands"},{"e":"🙌","n":"raising hands","s":"hands"},{"e":"🙌🏻","n":"raising hands: light skin tone","s":"hands"},{"e":"🙌🏼","n":"raising hands: medium-light skin tone","s":"hands"},{"e":"🙌🏽","n":"raising hands: medium skin tone","s":"hands"},{"e":"🙌🏾","n":"raising hands: medium-dark skin tone","s":"hands"},{"e":"🙌🏿","n":"raising hands: dark skin tone","s":"hands"},{"e":"🫶","n":"heart hands","s":"hands"},{"e":"🫶🏻","n":"heart hands: light skin tone","s":"hands"},{"e":"🫶🏼","n":"heart hands: medium-light skin tone","s":"hands"},{"e":"🫶🏽","n":"heart hands: medium skin tone","s":"hands"},{"e":"🫶🏾","n":"heart hands: medium-dark skin tone","s":"hands"},{"e":"🫶🏿","n":"heart hands: dark skin tone","s":"hands"},{"e":"👐","n":"open hands","s":"hands"},{"e":"👐🏻","n":"open hands: light skin tone","s":"hands"},{"e":"👐🏼","n":"open hands: medium-light skin tone","s":"hands"},{"e":"👐🏽","n":"open hands: medium skin tone","s":"hands"},{"e":"👐🏾","n":"open hands: medium-dark skin tone","s":"hands"},{"e":"👐🏿","n":"open hands: dark skin tone","s":"hands"},{"e":"🤲","n":"palms up together","s":"hands"},{"e":"🤲🏻","n":"palms up together: light skin tone","s":"hands"},{"e":"🤲🏼","n":"palms up together: medium-light skin tone","s":"hands"},{"e":"🤲🏽","n":"palms up together: medium skin tone","s":"hands"},{"e":"🤲🏾","n":"palms up together: medium-dark skin tone","s":"hands"},{"e":"🤲🏿","n":"palms up together: dark skin tone","s":"hands"},{"e":"🤝","n":"handshake","s":"hands"},{"e":"🤝🏻","n":"handshake: light skin tone","s":"hands"},{"e":"🤝🏼","n":"handshake: medium-light skin tone","s":"hands"},{"e":"🤝🏽","n":"handshake: medium skin tone","s":"hands"},{"e":"🤝🏾","n":"handshake: medium-dark skin tone","s":"hands"},{"e":"🤝🏿","n":"handshake: dark skin tone","s":"hands"},{"e":"🫱🏻‍🫲🏼","n":"handshake: light skin tone, medium-light skin tone","s":"hands"},{"e":"🫱🏻‍🫲🏽","n":"handshake: light skin tone, medium skin tone","s":"hands"},{"e":"🫱🏻‍🫲🏾","n":"handshake: light skin tone, medium-dark skin tone","s":"hands"},{"e":"🫱🏻‍🫲🏿","n":"handshake: light skin tone, dark skin tone","s":"hands"},{"e":"🫱🏼‍🫲🏻","n":"handshake: medium-light skin tone, light skin tone","s":"hands"},{"e":"🫱🏼‍🫲🏽","n":"handshake: medium-light skin tone, medium skin tone","s":"hands"},{"e":"🫱🏼‍🫲🏾","n":"handshake: medium-light skin tone, medium-dark skin tone","s":"hands"},{"e":"🫱🏼‍🫲🏿","n":"handshake: medium-light skin tone, dark skin tone","s":"hands"},{"e":"🫱🏽‍🫲🏻","n":"handshake: medium skin tone, light skin tone","s":"hands"},{"e":"🫱🏽‍🫲🏼","n":"handshake: medium skin tone, medium-light skin tone","s":"hands"},{"e":"🫱🏽‍🫲🏾","n":"handshake: medium skin tone, medium-dark skin tone","s":"hands"},{"e":"🫱🏽‍🫲🏿","n":"handshake: medium skin tone, dark skin tone","s":"hands"},{"e":"🫱🏾‍🫲🏻","n":"handshake: medium-dark skin tone, light skin tone","s":"hands"},{"e":"🫱🏾‍🫲🏼","n":"handshake: medium-dark skin tone, medium-light skin tone","s":"hands"},{"e":"🫱🏾‍🫲🏽","n":"handshake: medium-dark skin tone, medium skin tone","s":"hands"},{"e":"🫱🏾‍🫲🏿","n":"handshake: medium-dark skin tone, dark skin tone","s":"hands"},{"e":"🫱🏿‍🫲🏻","n":"handshake: dark skin tone, light skin tone","s":"hands"},{"e":"🫱🏿‍🫲🏼","n":"handshake: dark skin tone, medium-light skin tone","s":"hands"},{"e":"🫱🏿‍🫲🏽","n":"handshake: dark skin tone, medium skin tone","s":"hands"},{"e":"🫱🏿‍🫲🏾","n":"handshake: dark skin tone, medium-dark skin tone","s":"hands"},{"e":"🙏","n":"folded hands","s":"hands"},{"e":"🙏🏻","n":"folded hands: light skin tone","s":"hands"},{"e":"🙏🏼","n":"folded hands: medium-light skin tone","s":"hands"},{"e":"🙏🏽","n":"folded hands: medium skin tone","s":"hands"},{"e":"🙏🏾","n":"folded hands: medium-dark skin tone","s":"hands"},{"e":"🙏🏿","n":"folded hands: dark skin tone","s":"hands"},{"e":"✍️","n":"writing hand","s":"hand-prop"},{"e":"✍🏻","n":"writing hand: light skin tone","s":"hand-prop"},{"e":"✍🏼","n":"writing hand: medium-light skin tone","s":"hand-prop"},{"e":"✍🏽","n":"writing hand: medium skin tone","s":"hand-prop"},{"e":"✍🏾","n":"writing hand: medium-dark skin tone","s":"hand-prop"},{"e":"✍🏿","n":"writing hand: dark skin tone","s":"hand-prop"},{"e":"💅","n":"nail polish","s":"hand-prop"},{"e":"💅🏻","n":"nail polish: light skin tone","s":"hand-prop"},{"e":"💅🏼","n":"nail polish: medium-light skin tone","s":"hand-prop"},{"e":"💅🏽","n":"nail polish: medium skin tone","s":"hand-prop"},{"e":"💅🏾","n":"nail polish: medium-dark skin tone","s":"hand-prop"},{"e":"💅🏿","n":"nail polish: dark skin tone","s":"hand-prop"},{"e":"🤳","n":"selfie","s":"hand-prop"},{"e":"🤳🏻","n":"selfie: light skin tone","s":"hand-prop"},{"e":"🤳🏼","n":"selfie: medium-light skin tone","s":"hand-prop"},{"e":"🤳🏽","n":"selfie: medium skin tone","s":"hand-prop"},{"e":"🤳🏾","n":"selfie: medium-dark skin tone","s":"hand-prop"},{"e":"🤳🏿","n":"selfie: dark skin tone","s":"hand-prop"},{"e":"💪","n":"flexed biceps","s":"body-parts"},{"e":"💪🏻","n":"flexed biceps: light skin tone","s":"body-parts"},{"e":"💪🏼","n":"flexed biceps: medium-light skin tone","s":"body-parts"},{"e":"💪🏽","n":"flexed biceps: medium skin tone","s":"body-parts"},{"e":"💪🏾","n":"flexed biceps: medium-dark skin tone","s":"body-parts"},{"e":"💪🏿","n":"flexed biceps: dark skin tone","s":"body-parts"},{"e":"🦾","n":"mechanical arm","s":"body-parts"},{"e":"🦿","n":"mechanical leg","s":"body-parts"},{"e":"🦵","n":"leg","s":"body-parts"},{"e":"🦵🏻","n":"leg: light skin tone","s":"body-parts"},{"e":"🦵🏼","n":"leg: medium-light skin tone","s":"body-parts"},{"e":"🦵🏽","n":"leg: medium skin tone","s":"body-parts"},{"e":"🦵🏾","n":"leg: medium-dark skin tone","s":"body-parts"},{"e":"🦵🏿","n":"leg: dark skin tone","s":"body-parts"},{"e":"🦶","n":"foot","s":"body-parts"},{"e":"🦶🏻","n":"foot: light skin tone","s":"body-parts"},{"e":"🦶🏼","n":"foot: medium-light skin tone","s":"body-parts"},{"e":"🦶🏽","n":"foot: medium skin tone","s":"body-parts"},{"e":"🦶🏾","n":"foot: medium-dark skin tone","s":"body-parts"},{"e":"🦶🏿","n":"foot: dark skin tone","s":"body-parts"},{"e":"👂","n":"ear","s":"body-parts"},{"e":"👂🏻","n":"ear: light skin tone","s":"body-parts"},{"e":"👂🏼","n":"ear: medium-light skin tone","s":"body-parts"},{"e":"👂🏽","n":"ear: medium skin tone","s":"body-parts"},{"e":"👂🏾","n":"ear: medium-dark skin tone","s":"body-parts"},{"e":"👂🏿","n":"ear: dark skin tone","s":"body-parts"},{"e":"🦻","n":"ear with hearing aid","s":"body-parts"},{"e":"🦻🏻","n":"ear with hearing aid: light skin tone","s":"body-parts"},{"e":"🦻🏼","n":"ear with hearing aid: medium-light skin tone","s":"body-parts"},{"e":"🦻🏽","n":"ear with hearing aid: medium skin tone","s":"body-parts"},{"e":"🦻🏾","n":"ear with hearing aid: medium-dark skin tone","s":"body-parts"},{"e":"🦻🏿","n":"ear with hearing aid: dark skin tone","s":"body-parts"},{"e":"👃","n":"nose","s":"body-parts"},{"e":"👃🏻","n":"nose: light skin tone","s":"body-parts"},{"e":"👃🏼","n":"nose: medium-light skin tone","s":"body-parts"},{"e":"👃🏽","n":"nose: medium skin tone","s":"body-parts"},{"e":"👃🏾","n":"nose: medium-dark skin tone","s":"body-parts"},{"e":"👃🏿","n":"nose: dark skin tone","s":"body-parts"},{"e":"🧠","n":"brain","s":"body-parts"},{"e":"🫀","n":"anatomical heart","s":"body-parts"},{"e":"🫁","n":"lungs","s":"body-parts"},{"e":"🦷","n":"tooth","s":"body-parts"},{"e":"🦴","n":"bone","s":"body-parts"},{"e":"👀","n":"eyes","s":"body-parts"},{"e":"👁️","n":"eye","s":"body-parts"},{"e":"👅","n":"tongue","s":"body-parts"},{"e":"👄","n":"mouth","s":"body-parts"},{"e":"🫦","n":"biting lip","s":"body-parts"},{"e":"👶","n":"baby","s":"person"},{"e":"👶🏻","n":"baby: light skin tone","s":"person"},{"e":"👶🏼","n":"baby: medium-light skin tone","s":"person"},{"e":"👶🏽","n":"baby: medium skin tone","s":"person"},{"e":"👶🏾","n":"baby: medium-dark skin tone","s":"person"},{"e":"👶🏿","n":"baby: dark skin tone","s":"person"},{"e":"🧒","n":"child","s":"person"},{"e":"🧒🏻","n":"child: light skin tone","s":"person"},{"e":"🧒🏼","n":"child: medium-light skin tone","s":"person"},{"e":"🧒🏽","n":"child: medium skin tone","s":"person"},{"e":"🧒🏾","n":"child: medium-dark skin tone","s":"person"},{"e":"🧒🏿","n":"child: dark skin tone","s":"person"},{"e":"👦","n":"boy","s":"person"},{"e":"👦🏻","n":"boy: light skin tone","s":"person"},{"e":"👦🏼","n":"boy: medium-light skin tone","s":"person"},{"e":"👦🏽","n":"boy: medium skin tone","s":"person"},{"e":"👦🏾","n":"boy: medium-dark skin tone","s":"person"},{"e":"👦🏿","n":"boy: dark skin tone","s":"person"},{"e":"👧","n":"girl","s":"person"},{"e":"👧🏻","n":"girl: light skin tone","s":"person"},{"e":"👧🏼","n":"girl: medium-light skin tone","s":"person"},{"e":"👧🏽","n":"girl: medium skin tone","s":"person"},{"e":"👧🏾","n":"girl: medium-dark skin tone","s":"person"},{"e":"👧🏿","n":"girl: dark skin tone","s":"person"},{"e":"🧑","n":"person","s":"person"},{"e":"🧑🏻","n":"person: light skin tone","s":"person"},{"e":"🧑🏼","n":"person: medium-light skin tone","s":"person"},{"e":"🧑🏽","n":"person: medium skin tone","s":"person"},{"e":"🧑🏾","n":"person: medium-dark skin tone","s":"person"},{"e":"🧑🏿","n":"person: dark skin tone","s":"person"},{"e":"👱","n":"person: blond hair","s":"person"},{"e":"👱🏻","n":"person: light skin tone, blond hair","s":"person"},{"e":"👱🏼","n":"person: medium-light skin tone, blond hair","s":"person"},{"e":"👱🏽","n":"person: medium skin tone, blond hair","s":"person"},{"e":"👱🏾","n":"person: medium-dark skin tone, blond hair","s":"person"},{"e":"👱🏿","n":"person: dark skin tone, blond hair","s":"person"},{"e":"👨","n":"man","s":"person"},{"e":"👨🏻","n":"man: light skin tone","s":"person"},{"e":"👨🏼","n":"man: medium-light skin tone","s":"person"},{"e":"👨🏽","n":"man: medium skin tone","s":"person"},{"e":"👨🏾","n":"man: medium-dark skin tone","s":"person"},{"e":"👨🏿","n":"man: dark skin tone","s":"person"},{"e":"🧔","n":"person: beard","s":"person"},{"e":"🧔🏻","n":"person: light skin tone, beard","s":"person"},{"e":"🧔🏼","n":"person: medium-light skin tone, beard","s":"person"},{"e":"🧔🏽","n":"person: medium skin tone, beard","s":"person"},{"e":"🧔🏾","n":"person: medium-dark skin tone, beard","s":"person"},{"e":"🧔🏿","n":"person: dark skin tone, beard","s":"person"},{"e":"🧔‍♂️","n":"man: beard","s":"person"},{"e":"🧔🏻‍♂️","n":"man: light skin tone, beard","s":"person"},{"e":"🧔🏼‍♂️","n":"man: medium-light skin tone, beard","s":"person"},{"e":"🧔🏽‍♂️","n":"man: medium skin tone, beard","s":"person"},{"e":"🧔🏾‍♂️","n":"man: medium-dark skin tone, beard","s":"person"},{"e":"🧔🏿‍♂️","n":"man: dark skin tone, beard","s":"person"},{"e":"🧔‍♀️","n":"woman: beard","s":"person"},{"e":"🧔🏻‍♀️","n":"woman: light skin tone, beard","s":"person"},{"e":"🧔🏼‍♀️","n":"woman: medium-light skin tone, beard","s":"person"},{"e":"🧔🏽‍♀️","n":"woman: medium skin tone, beard","s":"person"},{"e":"🧔🏾‍♀️","n":"woman: medium-dark skin tone, beard","s":"person"},{"e":"🧔🏿‍♀️","n":"woman: dark skin tone, beard","s":"person"},{"e":"👨‍🦰","n":"man: red hair","s":"person"},{"e":"👨🏻‍🦰","n":"man: light skin tone, red hair","s":"person"},{"e":"👨🏼‍🦰","n":"man: medium-light skin tone, red hair","s":"person"},{"e":"👨🏽‍🦰","n":"man: medium skin tone, red hair","s":"person"},{"e":"👨🏾‍🦰","n":"man: medium-dark skin tone, red hair","s":"person"},{"e":"👨🏿‍🦰","n":"man: dark skin tone, red hair","s":"person"},{"e":"👨‍🦱","n":"man: curly hair","s":"person"},{"e":"👨🏻‍🦱","n":"man: light skin tone, curly hair","s":"person"},{"e":"👨🏼‍🦱","n":"man: medium-light skin tone, curly hair","s":"person"},{"e":"👨🏽‍🦱","n":"man: medium skin tone, curly hair","s":"person"},{"e":"👨🏾‍🦱","n":"man: medium-dark skin tone, curly hair","s":"person"},{"e":"👨🏿‍🦱","n":"man: dark skin tone, curly hair","s":"person"},{"e":"👨‍🦳","n":"man: white hair","s":"person"},{"e":"👨🏻‍🦳","n":"man: light skin tone, white hair","s":"person"},{"e":"👨🏼‍🦳","n":"man: medium-light skin tone, white hair","s":"person"},{"e":"👨🏽‍🦳","n":"man: medium skin tone, white hair","s":"person"},{"e":"👨🏾‍🦳","n":"man: medium-dark skin tone, white hair","s":"person"},{"e":"👨🏿‍🦳","n":"man: dark skin tone, white hair","s":"person"},{"e":"👨‍🦲","n":"man: bald","s":"person"},{"e":"👨🏻‍🦲","n":"man: light skin tone, bald","s":"person"},{"e":"👨🏼‍🦲","n":"man: medium-light skin tone, bald","s":"person"},{"e":"👨🏽‍🦲","n":"man: medium skin tone, bald","s":"person"},{"e":"👨🏾‍🦲","n":"man: medium-dark skin tone, bald","s":"person"},{"e":"👨🏿‍🦲","n":"man: dark skin tone, bald","s":"person"},{"e":"👩","n":"woman","s":"person"},{"e":"👩🏻","n":"woman: light skin tone","s":"person"},{"e":"👩🏼","n":"woman: medium-light skin tone","s":"person"},{"e":"👩🏽","n":"woman: medium skin tone","s":"person"},{"e":"👩🏾","n":"woman: medium-dark skin tone","s":"person"},{"e":"👩🏿","n":"woman: dark skin tone","s":"person"},{"e":"👩‍🦰","n":"woman: red hair","s":"person"},{"e":"👩🏻‍🦰","n":"woman: light skin tone, red hair","s":"person"},{"e":"👩🏼‍🦰","n":"woman: medium-light skin tone, red hair","s":"person"},{"e":"👩🏽‍🦰","n":"woman: medium skin tone, red hair","s":"person"},{"e":"👩🏾‍🦰","n":"woman: medium-dark skin tone, red hair","s":"person"},{"e":"👩🏿‍🦰","n":"woman: dark skin tone, red hair","s":"person"},{"e":"🧑‍🦰","n":"person: red hair","s":"person"},{"e":"🧑🏻‍🦰","n":"person: light skin tone, red hair","s":"person"},{"e":"🧑🏼‍🦰","n":"person: medium-light skin tone, red hair","s":"person"},{"e":"🧑🏽‍🦰","n":"person: medium skin tone, red hair","s":"person"},{"e":"🧑🏾‍🦰","n":"person: medium-dark skin tone, red hair","s":"person"},{"e":"🧑🏿‍🦰","n":"person: dark skin tone, red hair","s":"person"},{"e":"👩‍🦱","n":"woman: curly hair","s":"person"},{"e":"👩🏻‍🦱","n":"woman: light skin tone, curly hair","s":"person"},{"e":"👩🏼‍🦱","n":"woman: medium-light skin tone, curly hair","s":"person"},{"e":"👩🏽‍🦱","n":"woman: medium skin tone, curly hair","s":"person"},{"e":"👩🏾‍🦱","n":"woman: medium-dark skin tone, curly hair","s":"person"},{"e":"👩🏿‍🦱","n":"woman: dark skin tone, curly hair","s":"person"},{"e":"🧑‍🦱","n":"person: curly hair","s":"person"},{"e":"🧑🏻‍🦱","n":"person: light skin tone, curly hair","s":"person"},{"e":"🧑🏼‍🦱","n":"person: medium-light skin tone, curly hair","s":"person"},{"e":"🧑🏽‍🦱","n":"person: medium skin tone, curly hair","s":"person"},{"e":"🧑🏾‍🦱","n":"person: medium-dark skin tone, curly hair","s":"person"},{"e":"🧑🏿‍🦱","n":"person: dark skin tone, curly hair","s":"person"},{"e":"👩‍🦳","n":"woman: white hair","s":"person"},{"e":"👩🏻‍🦳","n":"woman: light skin tone, white hair","s":"person"},{"e":"👩🏼‍🦳","n":"woman: medium-light skin tone, white hair","s":"person"},{"e":"👩🏽‍🦳","n":"woman: medium skin tone, white hair","s":"person"},{"e":"👩🏾‍🦳","n":"woman: medium-dark skin tone, white hair","s":"person"},{"e":"👩🏿‍🦳","n":"woman: dark skin tone, white hair","s":"person"},{"e":"🧑‍🦳","n":"person: white hair","s":"person"},{"e":"🧑🏻‍🦳","n":"person: light skin tone, white hair","s":"person"},{"e":"🧑🏼‍🦳","n":"person: medium-light skin tone, white hair","s":"person"},{"e":"🧑🏽‍🦳","n":"person: medium skin tone, white hair","s":"person"},{"e":"🧑🏾‍🦳","n":"person: medium-dark skin tone, white hair","s":"person"},{"e":"🧑🏿‍🦳","n":"person: dark skin tone, white hair","s":"person"},{"e":"👩‍🦲","n":"woman: bald","s":"person"},{"e":"👩🏻‍🦲","n":"woman: light skin tone, bald","s":"person"},{"e":"👩🏼‍🦲","n":"woman: medium-light skin tone, bald","s":"person"},{"e":"👩🏽‍🦲","n":"woman: medium skin tone, bald","s":"person"},{"e":"👩🏾‍🦲","n":"woman: medium-dark skin tone, bald","s":"person"},{"e":"👩🏿‍🦲","n":"woman: dark skin tone, bald","s":"person"},{"e":"🧑‍🦲","n":"person: bald","s":"person"},{"e":"🧑🏻‍🦲","n":"person: light skin tone, bald","s":"person"},{"e":"🧑🏼‍🦲","n":"person: medium-light skin tone, bald","s":"person"},{"e":"🧑🏽‍🦲","n":"person: medium skin tone, bald","s":"person"},{"e":"🧑🏾‍🦲","n":"person: medium-dark skin tone, bald","s":"person"},{"e":"🧑🏿‍🦲","n":"person: dark skin tone, bald","s":"person"},{"e":"👱‍♀️","n":"woman: blond hair","s":"person"},{"e":"👱🏻‍♀️","n":"woman: light skin tone, blond hair","s":"person"},{"e":"👱🏼‍♀️","n":"woman: medium-light skin tone, blond hair","s":"person"},{"e":"👱🏽‍♀️","n":"woman: medium skin tone, blond hair","s":"person"},{"e":"👱🏾‍♀️","n":"woman: medium-dark skin tone, blond hair","s":"person"},{"e":"👱🏿‍♀️","n":"woman: dark skin tone, blond hair","s":"person"},{"e":"👱‍♂️","n":"man: blond hair","s":"person"},{"e":"👱🏻‍♂️","n":"man: light skin tone, blond hair","s":"person"},{"e":"👱🏼‍♂️","n":"man: medium-light skin tone, blond hair","s":"person"},{"e":"👱🏽‍♂️","n":"man: medium skin tone, blond hair","s":"person"},{"e":"👱🏾‍♂️","n":"man: medium-dark skin tone, blond hair","s":"person"},{"e":"👱🏿‍♂️","n":"man: dark skin tone, blond hair","s":"person"},{"e":"🧓","n":"older person","s":"person"},{"e":"🧓🏻","n":"older person: light skin tone","s":"person"},{"e":"🧓🏼","n":"older person: medium-light skin tone","s":"person"},{"e":"🧓🏽","n":"older person: medium skin tone","s":"person"},{"e":"🧓🏾","n":"older person: medium-dark skin tone","s":"person"},{"e":"🧓🏿","n":"older person: dark skin tone","s":"person"},{"e":"👴","n":"old man","s":"person"},{"e":"👴🏻","n":"old man: light skin tone","s":"person"},{"e":"👴🏼","n":"old man: medium-light skin tone","s":"person"},{"e":"👴🏽","n":"old man: medium skin tone","s":"person"},{"e":"👴🏾","n":"old man: medium-dark skin tone","s":"person"},{"e":"👴🏿","n":"old man: dark skin tone","s":"person"},{"e":"👵","n":"old woman","s":"person"},{"e":"👵🏻","n":"old woman: light skin tone","s":"person"},{"e":"👵🏼","n":"old woman: medium-light skin tone","s":"person"},{"e":"👵🏽","n":"old woman: medium skin tone","s":"person"},{"e":"👵🏾","n":"old woman: medium-dark skin tone","s":"person"},{"e":"👵🏿","n":"old woman: dark skin tone","s":"person"},{"e":"🙍","n":"person frowning","s":"person-gesture"},{"e":"🙍🏻","n":"person frowning: light skin tone","s":"person-gesture"},{"e":"🙍🏼","n":"person frowning: medium-light skin tone","s":"person-gesture"},{"e":"🙍🏽","n":"person frowning: medium skin tone","s":"person-gesture"},{"e":"🙍🏾","n":"person frowning: medium-dark skin tone","s":"person-gesture"},{"e":"🙍🏿","n":"person frowning: dark skin tone","s":"person-gesture"},{"e":"🙍‍♂️","n":"man frowning","s":"person-gesture"},{"e":"🙍🏻‍♂️","n":"man frowning: light skin tone","s":"person-gesture"},{"e":"🙍🏼‍♂️","n":"man frowning: medium-light skin tone","s":"person-gesture"},{"e":"🙍🏽‍♂️","n":"man frowning: medium skin tone","s":"person-gesture"},{"e":"🙍🏾‍♂️","n":"man frowning: medium-dark skin tone","s":"person-gesture"},{"e":"🙍🏿‍♂️","n":"man frowning: dark skin tone","s":"person-gesture"},{"e":"🙍‍♀️","n":"woman frowning","s":"person-gesture"},{"e":"🙍🏻‍♀️","n":"woman frowning: light skin tone","s":"person-gesture"},{"e":"🙍🏼‍♀️","n":"woman frowning: medium-light skin tone","s":"person-gesture"},{"e":"🙍🏽‍♀️","n":"woman frowning: medium skin tone","s":"person-gesture"},{"e":"🙍🏾‍♀️","n":"woman frowning: medium-dark skin tone","s":"person-gesture"},{"e":"🙍🏿‍♀️","n":"woman frowning: dark skin tone","s":"person-gesture"},{"e":"🙎","n":"person pouting","s":"person-gesture"},{"e":"🙎🏻","n":"person pouting: light skin tone","s":"person-gesture"},{"e":"🙎🏼","n":"person pouting: medium-light skin tone","s":"person-gesture"},{"e":"🙎🏽","n":"person pouting: medium skin tone","s":"person-gesture"},{"e":"🙎🏾","n":"person pouting: medium-dark skin tone","s":"person-gesture"},{"e":"🙎🏿","n":"person pouting: dark skin tone","s":"person-gesture"},{"e":"🙎‍♂️","n":"man pouting","s":"person-gesture"},{"e":"🙎🏻‍♂️","n":"man pouting: light skin tone","s":"person-gesture"},{"e":"🙎🏼‍♂️","n":"man pouting: medium-light skin tone","s":"person-gesture"},{"e":"🙎🏽‍♂️","n":"man pouting: medium skin tone","s":"person-gesture"},{"e":"🙎🏾‍♂️","n":"man pouting: medium-dark skin tone","s":"person-gesture"},{"e":"🙎🏿‍♂️","n":"man pouting: dark skin tone","s":"person-gesture"},{"e":"🙎‍♀️","n":"woman pouting","s":"person-gesture"},{"e":"🙎🏻‍♀️","n":"woman pouting: light skin tone","s":"person-gesture"},{"e":"🙎🏼‍♀️","n":"woman pouting: medium-light skin tone","s":"person-gesture"},{"e":"🙎🏽‍♀️","n":"woman pouting: medium skin tone","s":"person-gesture"},{"e":"🙎🏾‍♀️","n":"woman pouting: medium-dark skin tone","s":"person-gesture"},{"e":"🙎🏿‍♀️","n":"woman pouting: dark skin tone","s":"person-gesture"},{"e":"🙅","n":"person gesturing NO","s":"person-gesture"},{"e":"🙅🏻","n":"person gesturing NO: light skin tone","s":"person-gesture"},{"e":"🙅🏼","n":"person gesturing NO: medium-light skin tone","s":"person-gesture"},{"e":"🙅🏽","n":"person gesturing NO: medium skin tone","s":"person-gesture"},{"e":"🙅🏾","n":"person gesturing NO: medium-dark skin tone","s":"person-gesture"},{"e":"🙅🏿","n":"person gesturing NO: dark skin tone","s":"person-gesture"},{"e":"🙅‍♂️","n":"man gesturing NO","s":"person-gesture"},{"e":"🙅🏻‍♂️","n":"man gesturing NO: light skin tone","s":"person-gesture"},{"e":"🙅🏼‍♂️","n":"man gesturing NO: medium-light skin tone","s":"person-gesture"},{"e":"🙅🏽‍♂️","n":"man gesturing NO: medium skin tone","s":"person-gesture"},{"e":"🙅🏾‍♂️","n":"man gesturing NO: medium-dark skin tone","s":"person-gesture"},{"e":"🙅🏿‍♂️","n":"man gesturing NO: dark skin tone","s":"person-gesture"},{"e":"🙅‍♀️","n":"woman gesturing NO","s":"person-gesture"},{"e":"🙅🏻‍♀️","n":"woman gesturing NO: light skin tone","s":"person-gesture"},{"e":"🙅🏼‍♀️","n":"woman gesturing NO: medium-light skin tone","s":"person-gesture"},{"e":"🙅🏽‍♀️","n":"woman gesturing NO: medium skin tone","s":"person-gesture"},{"e":"🙅🏾‍♀️","n":"woman gesturing NO: medium-dark skin tone","s":"person-gesture"},{"e":"🙅🏿‍♀️","n":"woman gesturing NO: dark skin tone","s":"person-gesture"},{"e":"🙆","n":"person gesturing OK","s":"person-gesture"},{"e":"🙆🏻","n":"person gesturing OK: light skin tone","s":"person-gesture"},{"e":"🙆🏼","n":"person gesturing OK: medium-light skin tone","s":"person-gesture"},{"e":"🙆🏽","n":"person gesturing OK: medium skin tone","s":"person-gesture"},{"e":"🙆🏾","n":"person gesturing OK: medium-dark skin tone","s":"person-gesture"},{"e":"🙆🏿","n":"person gesturing OK: dark skin tone","s":"person-gesture"},{"e":"🙆‍♂️","n":"man gesturing OK","s":"person-gesture"},{"e":"🙆🏻‍♂️","n":"man gesturing OK: light skin tone","s":"person-gesture"},{"e":"🙆🏼‍♂️","n":"man gesturing OK: medium-light skin tone","s":"person-gesture"},{"e":"🙆🏽‍♂️","n":"man gesturing OK: medium skin tone","s":"person-gesture"},{"e":"🙆🏾‍♂️","n":"man gesturing OK: medium-dark skin tone","s":"person-gesture"},{"e":"🙆🏿‍♂️","n":"man gesturing OK: dark skin tone","s":"person-gesture"},{"e":"🙆‍♀️","n":"woman gesturing OK","s":"person-gesture"},{"e":"🙆🏻‍♀️","n":"woman gesturing OK: light skin tone","s":"person-gesture"},{"e":"🙆🏼‍♀️","n":"woman gesturing OK: medium-light skin tone","s":"person-gesture"},{"e":"🙆🏽‍♀️","n":"woman gesturing OK: medium skin tone","s":"person-gesture"},{"e":"🙆🏾‍♀️","n":"woman gesturing OK: medium-dark skin tone","s":"person-gesture"},{"e":"🙆🏿‍♀️","n":"woman gesturing OK: dark skin tone","s":"person-gesture"},{"e":"💁","n":"person tipping hand","s":"person-gesture"},{"e":"💁🏻","n":"person tipping hand: light skin tone","s":"person-gesture"},{"e":"💁🏼","n":"person tipping hand: medium-light skin tone","s":"person-gesture"},{"e":"💁🏽","n":"person tipping hand: medium skin tone","s":"person-gesture"},{"e":"💁🏾","n":"person tipping hand: medium-dark skin tone","s":"person-gesture"},{"e":"💁🏿","n":"person tipping hand: dark skin tone","s":"person-gesture"},{"e":"💁‍♂️","n":"man tipping hand","s":"person-gesture"},{"e":"💁🏻‍♂️","n":"man tipping hand: light skin tone","s":"person-gesture"},{"e":"💁🏼‍♂️","n":"man tipping hand: medium-light skin tone","s":"person-gesture"},{"e":"💁🏽‍♂️","n":"man tipping hand: medium skin tone","s":"person-gesture"},{"e":"💁🏾‍♂️","n":"man tipping hand: medium-dark skin tone","s":"person-gesture"},{"e":"💁🏿‍♂️","n":"man tipping hand: dark skin tone","s":"person-gesture"},{"e":"💁‍♀️","n":"woman tipping hand","s":"person-gesture"},{"e":"💁🏻‍♀️","n":"woman tipping hand: light skin tone","s":"person-gesture"},{"e":"💁🏼‍♀️","n":"woman tipping hand: medium-light skin tone","s":"person-gesture"},{"e":"💁🏽‍♀️","n":"woman tipping hand: medium skin tone","s":"person-gesture"},{"e":"💁🏾‍♀️","n":"woman tipping hand: medium-dark skin tone","s":"person-gesture"},{"e":"💁🏿‍♀️","n":"woman tipping hand: dark skin tone","s":"person-gesture"},{"e":"🙋","n":"person raising hand","s":"person-gesture"},{"e":"🙋🏻","n":"person raising hand: light skin tone","s":"person-gesture"},{"e":"🙋🏼","n":"person raising hand: medium-light skin tone","s":"person-gesture"},{"e":"🙋🏽","n":"person raising hand: medium skin tone","s":"person-gesture"},{"e":"🙋🏾","n":"person raising hand: medium-dark skin tone","s":"person-gesture"},{"e":"🙋🏿","n":"person raising hand: dark skin tone","s":"person-gesture"},{"e":"🙋‍♂️","n":"man raising hand","s":"person-gesture"},{"e":"🙋🏻‍♂️","n":"man raising hand: light skin tone","s":"person-gesture"},{"e":"🙋🏼‍♂️","n":"man raising hand: medium-light skin tone","s":"person-gesture"},{"e":"🙋🏽‍♂️","n":"man raising hand: medium skin tone","s":"person-gesture"},{"e":"🙋🏾‍♂️","n":"man raising hand: medium-dark skin tone","s":"person-gesture"},{"e":"🙋🏿‍♂️","n":"man raising hand: dark skin tone","s":"person-gesture"},{"e":"🙋‍♀️","n":"woman raising hand","s":"person-gesture"},{"e":"🙋🏻‍♀️","n":"woman raising hand: light skin tone","s":"person-gesture"},{"e":"🙋🏼‍♀️","n":"woman raising hand: medium-light skin tone","s":"person-gesture"},{"e":"🙋🏽‍♀️","n":"woman raising hand: medium skin tone","s":"person-gesture"},{"e":"🙋🏾‍♀️","n":"woman raising hand: medium-dark skin tone","s":"person-gesture"},{"e":"🙋🏿‍♀️","n":"woman raising hand: dark skin tone","s":"person-gesture"},{"e":"🧏","n":"deaf person","s":"person-gesture"},{"e":"🧏🏻","n":"deaf person: light skin tone","s":"person-gesture"},{"e":"🧏🏼","n":"deaf person: medium-light skin tone","s":"person-gesture"},{"e":"🧏🏽","n":"deaf person: medium skin tone","s":"person-gesture"},{"e":"🧏🏾","n":"deaf person: medium-dark skin tone","s":"person-gesture"},{"e":"🧏🏿","n":"deaf person: dark skin tone","s":"person-gesture"},{"e":"🧏‍♂️","n":"deaf man","s":"person-gesture"},{"e":"🧏🏻‍♂️","n":"deaf man: light skin tone","s":"person-gesture"},{"e":"🧏🏼‍♂️","n":"deaf man: medium-light skin tone","s":"person-gesture"},{"e":"🧏🏽‍♂️","n":"deaf man: medium skin tone","s":"person-gesture"},{"e":"🧏🏾‍♂️","n":"deaf man: medium-dark skin tone","s":"person-gesture"},{"e":"🧏🏿‍♂️","n":"deaf man: dark skin tone","s":"person-gesture"},{"e":"🧏‍♀️","n":"deaf woman","s":"person-gesture"},{"e":"🧏🏻‍♀️","n":"deaf woman: light skin tone","s":"person-gesture"},{"e":"🧏🏼‍♀️","n":"deaf woman: medium-light skin tone","s":"person-gesture"},{"e":"🧏🏽‍♀️","n":"deaf woman: medium skin tone","s":"person-gesture"},{"e":"🧏🏾‍♀️","n":"deaf woman: medium-dark skin tone","s":"person-gesture"},{"e":"🧏🏿‍♀️","n":"deaf woman: dark skin tone","s":"person-gesture"},{"e":"🙇","n":"person bowing","s":"person-gesture"},{"e":"🙇🏻","n":"person bowing: light skin tone","s":"person-gesture"},{"e":"🙇🏼","n":"person bowing: medium-light skin tone","s":"person-gesture"},{"e":"🙇🏽","n":"person bowing: medium skin tone","s":"person-gesture"},{"e":"🙇🏾","n":"person bowing: medium-dark skin tone","s":"person-gesture"},{"e":"🙇🏿","n":"person bowing: dark skin tone","s":"person-gesture"},{"e":"🙇‍♂️","n":"man bowing","s":"person-gesture"},{"e":"🙇🏻‍♂️","n":"man bowing: light skin tone","s":"person-gesture"},{"e":"🙇🏼‍♂️","n":"man bowing: medium-light skin tone","s":"person-gesture"},{"e":"🙇🏽‍♂️","n":"man bowing: medium skin tone","s":"person-gesture"},{"e":"🙇🏾‍♂️","n":"man bowing: medium-dark skin tone","s":"person-gesture"},{"e":"🙇🏿‍♂️","n":"man bowing: dark skin tone","s":"person-gesture"},{"e":"🙇‍♀️","n":"woman bowing","s":"person-gesture"},{"e":"🙇🏻‍♀️","n":"woman bowing: light skin tone","s":"person-gesture"},{"e":"🙇🏼‍♀️","n":"woman bowing: medium-light skin tone","s":"person-gesture"},{"e":"🙇🏽‍♀️","n":"woman bowing: medium skin tone","s":"person-gesture"},{"e":"🙇🏾‍♀️","n":"woman bowing: medium-dark skin tone","s":"person-gesture"},{"e":"🙇🏿‍♀️","n":"woman bowing: dark skin tone","s":"person-gesture"},{"e":"🤦","n":"person facepalming","s":"person-gesture"},{"e":"🤦🏻","n":"person facepalming: light skin tone","s":"person-gesture"},{"e":"🤦🏼","n":"person facepalming: medium-light skin tone","s":"person-gesture"},{"e":"🤦🏽","n":"person facepalming: medium skin tone","s":"person-gesture"},{"e":"🤦🏾","n":"person facepalming: medium-dark skin tone","s":"person-gesture"},{"e":"🤦🏿","n":"person facepalming: dark skin tone","s":"person-gesture"},{"e":"🤦‍♂️","n":"man facepalming","s":"person-gesture"},{"e":"🤦🏻‍♂️","n":"man facepalming: light skin tone","s":"person-gesture"},{"e":"🤦🏼‍♂️","n":"man facepalming: medium-light skin tone","s":"person-gesture"},{"e":"🤦🏽‍♂️","n":"man facepalming: medium skin tone","s":"person-gesture"},{"e":"🤦🏾‍♂️","n":"man facepalming: medium-dark skin tone","s":"person-gesture"},{"e":"🤦🏿‍♂️","n":"man facepalming: dark skin tone","s":"person-gesture"},{"e":"🤦‍♀️","n":"woman facepalming","s":"person-gesture"},{"e":"🤦🏻‍♀️","n":"woman facepalming: light skin tone","s":"person-gesture"},{"e":"🤦🏼‍♀️","n":"woman facepalming: medium-light skin tone","s":"person-gesture"},{"e":"🤦🏽‍♀️","n":"woman facepalming: medium skin tone","s":"person-gesture"},{"e":"🤦🏾‍♀️","n":"woman facepalming: medium-dark skin tone","s":"person-gesture"},{"e":"🤦🏿‍♀️","n":"woman facepalming: dark skin tone","s":"person-gesture"},{"e":"🤷","n":"person shrugging","s":"person-gesture"},{"e":"🤷🏻","n":"person shrugging: light skin tone","s":"person-gesture"},{"e":"🤷🏼","n":"person shrugging: medium-light skin tone","s":"person-gesture"},{"e":"🤷🏽","n":"person shrugging: medium skin tone","s":"person-gesture"},{"e":"🤷🏾","n":"person shrugging: medium-dark skin tone","s":"person-gesture"},{"e":"🤷🏿","n":"person shrugging: dark skin tone","s":"person-gesture"},{"e":"🤷‍♂️","n":"man shrugging","s":"person-gesture"},{"e":"🤷🏻‍♂️","n":"man shrugging: light skin tone","s":"person-gesture"},{"e":"🤷🏼‍♂️","n":"man shrugging: medium-light skin tone","s":"person-gesture"},{"e":"🤷🏽‍♂️","n":"man shrugging: medium skin tone","s":"person-gesture"},{"e":"🤷🏾‍♂️","n":"man shrugging: medium-dark skin tone","s":"person-gesture"},{"e":"🤷🏿‍♂️","n":"man shrugging: dark skin tone","s":"person-gesture"},{"e":"🤷‍♀️","n":"woman shrugging","s":"person-gesture"},{"e":"🤷🏻‍♀️","n":"woman shrugging: light skin tone","s":"person-gesture"},{"e":"🤷🏼‍♀️","n":"woman shrugging: medium-light skin tone","s":"person-gesture"},{"e":"🤷🏽‍♀️","n":"woman shrugging: medium skin tone","s":"person-gesture"},{"e":"🤷🏾‍♀️","n":"woman shrugging: medium-dark skin tone","s":"person-gesture"},{"e":"🤷🏿‍♀️","n":"woman shrugging: dark skin tone","s":"person-gesture"},{"e":"🧑‍⚕️","n":"health worker","s":"person-role"},{"e":"🧑🏻‍⚕️","n":"health worker: light skin tone","s":"person-role"},{"e":"🧑🏼‍⚕️","n":"health worker: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍⚕️","n":"health worker: medium skin tone","s":"person-role"},{"e":"🧑🏾‍⚕️","n":"health worker: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍⚕️","n":"health worker: dark skin tone","s":"person-role"},{"e":"👨‍⚕️","n":"man health worker","s":"person-role"},{"e":"👨🏻‍⚕️","n":"man health worker: light skin tone","s":"person-role"},{"e":"👨🏼‍⚕️","n":"man health worker: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍⚕️","n":"man health worker: medium skin tone","s":"person-role"},{"e":"👨🏾‍⚕️","n":"man health worker: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍⚕️","n":"man health worker: dark skin tone","s":"person-role"},{"e":"👩‍⚕️","n":"woman health worker","s":"person-role"},{"e":"👩🏻‍⚕️","n":"woman health worker: light skin tone","s":"person-role"},{"e":"👩🏼‍⚕️","n":"woman health worker: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍⚕️","n":"woman health worker: medium skin tone","s":"person-role"},{"e":"👩🏾‍⚕️","n":"woman health worker: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍⚕️","n":"woman health worker: dark skin tone","s":"person-role"},{"e":"🧑‍🎓","n":"student","s":"person-role"},{"e":"🧑🏻‍🎓","n":"student: light skin tone","s":"person-role"},{"e":"🧑🏼‍🎓","n":"student: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🎓","n":"student: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🎓","n":"student: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🎓","n":"student: dark skin tone","s":"person-role"},{"e":"👨‍🎓","n":"man student","s":"person-role"},{"e":"👨🏻‍🎓","n":"man student: light skin tone","s":"person-role"},{"e":"👨🏼‍🎓","n":"man student: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🎓","n":"man student: medium skin tone","s":"person-role"},{"e":"👨🏾‍🎓","n":"man student: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🎓","n":"man student: dark skin tone","s":"person-role"},{"e":"👩‍🎓","n":"woman student","s":"person-role"},{"e":"👩🏻‍🎓","n":"woman student: light skin tone","s":"person-role"},{"e":"👩🏼‍🎓","n":"woman student: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🎓","n":"woman student: medium skin tone","s":"person-role"},{"e":"👩🏾‍🎓","n":"woman student: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🎓","n":"woman student: dark skin tone","s":"person-role"},{"e":"🧑‍🏫","n":"teacher","s":"person-role"},{"e":"🧑🏻‍🏫","n":"teacher: light skin tone","s":"person-role"},{"e":"🧑🏼‍🏫","n":"teacher: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🏫","n":"teacher: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🏫","n":"teacher: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🏫","n":"teacher: dark skin tone","s":"person-role"},{"e":"👨‍🏫","n":"man teacher","s":"person-role"},{"e":"👨🏻‍🏫","n":"man teacher: light skin tone","s":"person-role"},{"e":"👨🏼‍🏫","n":"man teacher: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🏫","n":"man teacher: medium skin tone","s":"person-role"},{"e":"👨🏾‍🏫","n":"man teacher: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🏫","n":"man teacher: dark skin tone","s":"person-role"},{"e":"👩‍🏫","n":"woman teacher","s":"person-role"},{"e":"👩🏻‍🏫","n":"woman teacher: light skin tone","s":"person-role"},{"e":"👩🏼‍🏫","n":"woman teacher: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🏫","n":"woman teacher: medium skin tone","s":"person-role"},{"e":"👩🏾‍🏫","n":"woman teacher: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🏫","n":"woman teacher: dark skin tone","s":"person-role"},{"e":"🧑‍⚖️","n":"judge","s":"person-role"},{"e":"🧑🏻‍⚖️","n":"judge: light skin tone","s":"person-role"},{"e":"🧑🏼‍⚖️","n":"judge: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍⚖️","n":"judge: medium skin tone","s":"person-role"},{"e":"🧑🏾‍⚖️","n":"judge: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍⚖️","n":"judge: dark skin tone","s":"person-role"},{"e":"👨‍⚖️","n":"man judge","s":"person-role"},{"e":"👨🏻‍⚖️","n":"man judge: light skin tone","s":"person-role"},{"e":"👨🏼‍⚖️","n":"man judge: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍⚖️","n":"man judge: medium skin tone","s":"person-role"},{"e":"👨🏾‍⚖️","n":"man judge: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍⚖️","n":"man judge: dark skin tone","s":"person-role"},{"e":"👩‍⚖️","n":"woman judge","s":"person-role"},{"e":"👩🏻‍⚖️","n":"woman judge: light skin tone","s":"person-role"},{"e":"👩🏼‍⚖️","n":"woman judge: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍⚖️","n":"woman judge: medium skin tone","s":"person-role"},{"e":"👩🏾‍⚖️","n":"woman judge: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍⚖️","n":"woman judge: dark skin tone","s":"person-role"},{"e":"🧑‍🌾","n":"farmer","s":"person-role"},{"e":"🧑🏻‍🌾","n":"farmer: light skin tone","s":"person-role"},{"e":"🧑🏼‍🌾","n":"farmer: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🌾","n":"farmer: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🌾","n":"farmer: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🌾","n":"farmer: dark skin tone","s":"person-role"},{"e":"👨‍🌾","n":"man farmer","s":"person-role"},{"e":"👨🏻‍🌾","n":"man farmer: light skin tone","s":"person-role"},{"e":"👨🏼‍🌾","n":"man farmer: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🌾","n":"man farmer: medium skin tone","s":"person-role"},{"e":"👨🏾‍🌾","n":"man farmer: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🌾","n":"man farmer: dark skin tone","s":"person-role"},{"e":"👩‍🌾","n":"woman farmer","s":"person-role"},{"e":"👩🏻‍🌾","n":"woman farmer: light skin tone","s":"person-role"},{"e":"👩🏼‍🌾","n":"woman farmer: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🌾","n":"woman farmer: medium skin tone","s":"person-role"},{"e":"👩🏾‍🌾","n":"woman farmer: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🌾","n":"woman farmer: dark skin tone","s":"person-role"},{"e":"🧑‍🍳","n":"cook","s":"person-role"},{"e":"🧑🏻‍🍳","n":"cook: light skin tone","s":"person-role"},{"e":"🧑🏼‍🍳","n":"cook: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🍳","n":"cook: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🍳","n":"cook: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🍳","n":"cook: dark skin tone","s":"person-role"},{"e":"👨‍🍳","n":"man cook","s":"person-role"},{"e":"👨🏻‍🍳","n":"man cook: light skin tone","s":"person-role"},{"e":"👨🏼‍🍳","n":"man cook: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🍳","n":"man cook: medium skin tone","s":"person-role"},{"e":"👨🏾‍🍳","n":"man cook: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🍳","n":"man cook: dark skin tone","s":"person-role"},{"e":"👩‍🍳","n":"woman cook","s":"person-role"},{"e":"👩🏻‍🍳","n":"woman cook: light skin tone","s":"person-role"},{"e":"👩🏼‍🍳","n":"woman cook: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🍳","n":"woman cook: medium skin tone","s":"person-role"},{"e":"👩🏾‍🍳","n":"woman cook: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🍳","n":"woman cook: dark skin tone","s":"person-role"},{"e":"🧑‍🔧","n":"mechanic","s":"person-role"},{"e":"🧑🏻‍🔧","n":"mechanic: light skin tone","s":"person-role"},{"e":"🧑🏼‍🔧","n":"mechanic: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🔧","n":"mechanic: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🔧","n":"mechanic: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🔧","n":"mechanic: dark skin tone","s":"person-role"},{"e":"👨‍🔧","n":"man mechanic","s":"person-role"},{"e":"👨🏻‍🔧","n":"man mechanic: light skin tone","s":"person-role"},{"e":"👨🏼‍🔧","n":"man mechanic: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🔧","n":"man mechanic: medium skin tone","s":"person-role"},{"e":"👨🏾‍🔧","n":"man mechanic: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🔧","n":"man mechanic: dark skin tone","s":"person-role"},{"e":"👩‍🔧","n":"woman mechanic","s":"person-role"},{"e":"👩🏻‍🔧","n":"woman mechanic: light skin tone","s":"person-role"},{"e":"👩🏼‍🔧","n":"woman mechanic: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🔧","n":"woman mechanic: medium skin tone","s":"person-role"},{"e":"👩🏾‍🔧","n":"woman mechanic: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🔧","n":"woman mechanic: dark skin tone","s":"person-role"},{"e":"🧑‍🏭","n":"factory worker","s":"person-role"},{"e":"🧑🏻‍🏭","n":"factory worker: light skin tone","s":"person-role"},{"e":"🧑🏼‍🏭","n":"factory worker: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🏭","n":"factory worker: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🏭","n":"factory worker: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🏭","n":"factory worker: dark skin tone","s":"person-role"},{"e":"👨‍🏭","n":"man factory worker","s":"person-role"},{"e":"👨🏻‍🏭","n":"man factory worker: light skin tone","s":"person-role"},{"e":"👨🏼‍🏭","n":"man factory worker: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🏭","n":"man factory worker: medium skin tone","s":"person-role"},{"e":"👨🏾‍🏭","n":"man factory worker: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🏭","n":"man factory worker: dark skin tone","s":"person-role"},{"e":"👩‍🏭","n":"woman factory worker","s":"person-role"},{"e":"👩🏻‍🏭","n":"woman factory worker: light skin tone","s":"person-role"},{"e":"👩🏼‍🏭","n":"woman factory worker: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🏭","n":"woman factory worker: medium skin tone","s":"person-role"},{"e":"👩🏾‍🏭","n":"woman factory worker: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🏭","n":"woman factory worker: dark skin tone","s":"person-role"},{"e":"🧑‍💼","n":"office worker","s":"person-role"},{"e":"🧑🏻‍💼","n":"office worker: light skin tone","s":"person-role"},{"e":"🧑🏼‍💼","n":"office worker: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍💼","n":"office worker: medium skin tone","s":"person-role"},{"e":"🧑🏾‍💼","n":"office worker: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍💼","n":"office worker: dark skin tone","s":"person-role"},{"e":"👨‍💼","n":"man office worker","s":"person-role"},{"e":"👨🏻‍💼","n":"man office worker: light skin tone","s":"person-role"},{"e":"👨🏼‍💼","n":"man office worker: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍💼","n":"man office worker: medium skin tone","s":"person-role"},{"e":"👨🏾‍💼","n":"man office worker: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍💼","n":"man office worker: dark skin tone","s":"person-role"},{"e":"👩‍💼","n":"woman office worker","s":"person-role"},{"e":"👩🏻‍💼","n":"woman office worker: light skin tone","s":"person-role"},{"e":"👩🏼‍💼","n":"woman office worker: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍💼","n":"woman office worker: medium skin tone","s":"person-role"},{"e":"👩🏾‍💼","n":"woman office worker: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍💼","n":"woman office worker: dark skin tone","s":"person-role"},{"e":"🧑‍🔬","n":"scientist","s":"person-role"},{"e":"🧑🏻‍🔬","n":"scientist: light skin tone","s":"person-role"},{"e":"🧑🏼‍🔬","n":"scientist: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🔬","n":"scientist: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🔬","n":"scientist: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🔬","n":"scientist: dark skin tone","s":"person-role"},{"e":"👨‍🔬","n":"man scientist","s":"person-role"},{"e":"👨🏻‍🔬","n":"man scientist: light skin tone","s":"person-role"},{"e":"👨🏼‍🔬","n":"man scientist: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🔬","n":"man scientist: medium skin tone","s":"person-role"},{"e":"👨🏾‍🔬","n":"man scientist: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🔬","n":"man scientist: dark skin tone","s":"person-role"},{"e":"👩‍🔬","n":"woman scientist","s":"person-role"},{"e":"👩🏻‍🔬","n":"woman scientist: light skin tone","s":"person-role"},{"e":"👩🏼‍🔬","n":"woman scientist: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🔬","n":"woman scientist: medium skin tone","s":"person-role"},{"e":"👩🏾‍🔬","n":"woman scientist: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🔬","n":"woman scientist: dark skin tone","s":"person-role"},{"e":"🧑‍💻","n":"technologist","s":"person-role"},{"e":"🧑🏻‍💻","n":"technologist: light skin tone","s":"person-role"},{"e":"🧑🏼‍💻","n":"technologist: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍💻","n":"technologist: medium skin tone","s":"person-role"},{"e":"🧑🏾‍💻","n":"technologist: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍💻","n":"technologist: dark skin tone","s":"person-role"},{"e":"👨‍💻","n":"man technologist","s":"person-role"},{"e":"👨🏻‍💻","n":"man technologist: light skin tone","s":"person-role"},{"e":"👨🏼‍💻","n":"man technologist: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍💻","n":"man technologist: medium skin tone","s":"person-role"},{"e":"👨🏾‍💻","n":"man technologist: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍💻","n":"man technologist: dark skin tone","s":"person-role"},{"e":"👩‍💻","n":"woman technologist","s":"person-role"},{"e":"👩🏻‍💻","n":"woman technologist: light skin tone","s":"person-role"},{"e":"👩🏼‍💻","n":"woman technologist: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍💻","n":"woman technologist: medium skin tone","s":"person-role"},{"e":"👩🏾‍💻","n":"woman technologist: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍💻","n":"woman technologist: dark skin tone","s":"person-role"},{"e":"🧑‍🎤","n":"singer","s":"person-role"},{"e":"🧑🏻‍🎤","n":"singer: light skin tone","s":"person-role"},{"e":"🧑🏼‍🎤","n":"singer: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🎤","n":"singer: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🎤","n":"singer: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🎤","n":"singer: dark skin tone","s":"person-role"},{"e":"👨‍🎤","n":"man singer","s":"person-role"},{"e":"👨🏻‍🎤","n":"man singer: light skin tone","s":"person-role"},{"e":"👨🏼‍🎤","n":"man singer: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🎤","n":"man singer: medium skin tone","s":"person-role"},{"e":"👨🏾‍🎤","n":"man singer: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🎤","n":"man singer: dark skin tone","s":"person-role"},{"e":"👩‍🎤","n":"woman singer","s":"person-role"},{"e":"👩🏻‍🎤","n":"woman singer: light skin tone","s":"person-role"},{"e":"👩🏼‍🎤","n":"woman singer: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🎤","n":"woman singer: medium skin tone","s":"person-role"},{"e":"👩🏾‍🎤","n":"woman singer: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🎤","n":"woman singer: dark skin tone","s":"person-role"},{"e":"🧑‍🎨","n":"artist","s":"person-role"},{"e":"🧑🏻‍🎨","n":"artist: light skin tone","s":"person-role"},{"e":"🧑🏼‍🎨","n":"artist: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🎨","n":"artist: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🎨","n":"artist: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🎨","n":"artist: dark skin tone","s":"person-role"},{"e":"👨‍🎨","n":"man artist","s":"person-role"},{"e":"👨🏻‍🎨","n":"man artist: light skin tone","s":"person-role"},{"e":"👨🏼‍🎨","n":"man artist: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🎨","n":"man artist: medium skin tone","s":"person-role"},{"e":"👨🏾‍🎨","n":"man artist: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🎨","n":"man artist: dark skin tone","s":"person-role"},{"e":"👩‍🎨","n":"woman artist","s":"person-role"},{"e":"👩🏻‍🎨","n":"woman artist: light skin tone","s":"person-role"},{"e":"👩🏼‍🎨","n":"woman artist: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🎨","n":"woman artist: medium skin tone","s":"person-role"},{"e":"👩🏾‍🎨","n":"woman artist: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🎨","n":"woman artist: dark skin tone","s":"person-role"},{"e":"🧑‍✈️","n":"pilot","s":"person-role"},{"e":"🧑🏻‍✈️","n":"pilot: light skin tone","s":"person-role"},{"e":"🧑🏼‍✈️","n":"pilot: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍✈️","n":"pilot: medium skin tone","s":"person-role"},{"e":"🧑🏾‍✈️","n":"pilot: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍✈️","n":"pilot: dark skin tone","s":"person-role"},{"e":"👨‍✈️","n":"man pilot","s":"person-role"},{"e":"👨🏻‍✈️","n":"man pilot: light skin tone","s":"person-role"},{"e":"👨🏼‍✈️","n":"man pilot: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍✈️","n":"man pilot: medium skin tone","s":"person-role"},{"e":"👨🏾‍✈️","n":"man pilot: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍✈️","n":"man pilot: dark skin tone","s":"person-role"},{"e":"👩‍✈️","n":"woman pilot","s":"person-role"},{"e":"👩🏻‍✈️","n":"woman pilot: light skin tone","s":"person-role"},{"e":"👩🏼‍✈️","n":"woman pilot: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍✈️","n":"woman pilot: medium skin tone","s":"person-role"},{"e":"👩🏾‍✈️","n":"woman pilot: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍✈️","n":"woman pilot: dark skin tone","s":"person-role"},{"e":"🧑‍🚀","n":"astronaut","s":"person-role"},{"e":"🧑🏻‍🚀","n":"astronaut: light skin tone","s":"person-role"},{"e":"🧑🏼‍🚀","n":"astronaut: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🚀","n":"astronaut: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🚀","n":"astronaut: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🚀","n":"astronaut: dark skin tone","s":"person-role"},{"e":"👨‍🚀","n":"man astronaut","s":"person-role"},{"e":"👨🏻‍🚀","n":"man astronaut: light skin tone","s":"person-role"},{"e":"👨🏼‍🚀","n":"man astronaut: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🚀","n":"man astronaut: medium skin tone","s":"person-role"},{"e":"👨🏾‍🚀","n":"man astronaut: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🚀","n":"man astronaut: dark skin tone","s":"person-role"},{"e":"👩‍🚀","n":"woman astronaut","s":"person-role"},{"e":"👩🏻‍🚀","n":"woman astronaut: light skin tone","s":"person-role"},{"e":"👩🏼‍🚀","n":"woman astronaut: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🚀","n":"woman astronaut: medium skin tone","s":"person-role"},{"e":"👩🏾‍🚀","n":"woman astronaut: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🚀","n":"woman astronaut: dark skin tone","s":"person-role"},{"e":"🧑‍🚒","n":"firefighter","s":"person-role"},{"e":"🧑🏻‍🚒","n":"firefighter: light skin tone","s":"person-role"},{"e":"🧑🏼‍🚒","n":"firefighter: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🚒","n":"firefighter: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🚒","n":"firefighter: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🚒","n":"firefighter: dark skin tone","s":"person-role"},{"e":"👨‍🚒","n":"man firefighter","s":"person-role"},{"e":"👨🏻‍🚒","n":"man firefighter: light skin tone","s":"person-role"},{"e":"👨🏼‍🚒","n":"man firefighter: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🚒","n":"man firefighter: medium skin tone","s":"person-role"},{"e":"👨🏾‍🚒","n":"man firefighter: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🚒","n":"man firefighter: dark skin tone","s":"person-role"},{"e":"👩‍🚒","n":"woman firefighter","s":"person-role"},{"e":"👩🏻‍🚒","n":"woman firefighter: light skin tone","s":"person-role"},{"e":"👩🏼‍🚒","n":"woman firefighter: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🚒","n":"woman firefighter: medium skin tone","s":"person-role"},{"e":"👩🏾‍🚒","n":"woman firefighter: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🚒","n":"woman firefighter: dark skin tone","s":"person-role"},{"e":"👮","n":"police officer","s":"person-role"},{"e":"👮🏻","n":"police officer: light skin tone","s":"person-role"},{"e":"👮🏼","n":"police officer: medium-light skin tone","s":"person-role"},{"e":"👮🏽","n":"police officer: medium skin tone","s":"person-role"},{"e":"👮🏾","n":"police officer: medium-dark skin tone","s":"person-role"},{"e":"👮🏿","n":"police officer: dark skin tone","s":"person-role"},{"e":"👮‍♂️","n":"man police officer","s":"person-role"},{"e":"👮🏻‍♂️","n":"man police officer: light skin tone","s":"person-role"},{"e":"👮🏼‍♂️","n":"man police officer: medium-light skin tone","s":"person-role"},{"e":"👮🏽‍♂️","n":"man police officer: medium skin tone","s":"person-role"},{"e":"👮🏾‍♂️","n":"man police officer: medium-dark skin tone","s":"person-role"},{"e":"👮🏿‍♂️","n":"man police officer: dark skin tone","s":"person-role"},{"e":"👮‍♀️","n":"woman police officer","s":"person-role"},{"e":"👮🏻‍♀️","n":"woman police officer: light skin tone","s":"person-role"},{"e":"👮🏼‍♀️","n":"woman police officer: medium-light skin tone","s":"person-role"},{"e":"👮🏽‍♀️","n":"woman police officer: medium skin tone","s":"person-role"},{"e":"👮🏾‍♀️","n":"woman police officer: medium-dark skin tone","s":"person-role"},{"e":"👮🏿‍♀️","n":"woman police officer: dark skin tone","s":"person-role"},{"e":"🕵️","n":"detective","s":"person-role"},{"e":"🕵🏻","n":"detective: light skin tone","s":"person-role"},{"e":"🕵🏼","n":"detective: medium-light skin tone","s":"person-role"},{"e":"🕵🏽","n":"detective: medium skin tone","s":"person-role"},{"e":"🕵🏾","n":"detective: medium-dark skin tone","s":"person-role"},{"e":"🕵🏿","n":"detective: dark skin tone","s":"person-role"},{"e":"🕵️‍♂️","n":"man detective","s":"person-role"},{"e":"🕵🏻‍♂️","n":"man detective: light skin tone","s":"person-role"},{"e":"🕵🏼‍♂️","n":"man detective: medium-light skin tone","s":"person-role"},{"e":"🕵🏽‍♂️","n":"man detective: medium skin tone","s":"person-role"},{"e":"🕵🏾‍♂️","n":"man detective: medium-dark skin tone","s":"person-role"},{"e":"🕵🏿‍♂️","n":"man detective: dark skin tone","s":"person-role"},{"e":"🕵️‍♀️","n":"woman detective","s":"person-role"},{"e":"🕵🏻‍♀️","n":"woman detective: light skin tone","s":"person-role"},{"e":"🕵🏼‍♀️","n":"woman detective: medium-light skin tone","s":"person-role"},{"e":"🕵🏽‍♀️","n":"woman detective: medium skin tone","s":"person-role"},{"e":"🕵🏾‍♀️","n":"woman detective: medium-dark skin tone","s":"person-role"},{"e":"🕵🏿‍♀️","n":"woman detective: dark skin tone","s":"person-role"},{"e":"💂","n":"guard","s":"person-role"},{"e":"💂🏻","n":"guard: light skin tone","s":"person-role"},{"e":"💂🏼","n":"guard: medium-light skin tone","s":"person-role"},{"e":"💂🏽","n":"guard: medium skin tone","s":"person-role"},{"e":"💂🏾","n":"guard: medium-dark skin tone","s":"person-role"},{"e":"💂🏿","n":"guard: dark skin tone","s":"person-role"},{"e":"💂‍♂️","n":"man guard","s":"person-role"},{"e":"💂🏻‍♂️","n":"man guard: light skin tone","s":"person-role"},{"e":"💂🏼‍♂️","n":"man guard: medium-light skin tone","s":"person-role"},{"e":"💂🏽‍♂️","n":"man guard: medium skin tone","s":"person-role"},{"e":"💂🏾‍♂️","n":"man guard: medium-dark skin tone","s":"person-role"},{"e":"💂🏿‍♂️","n":"man guard: dark skin tone","s":"person-role"},{"e":"💂‍♀️","n":"woman guard","s":"person-role"},{"e":"💂🏻‍♀️","n":"woman guard: light skin tone","s":"person-role"},{"e":"💂🏼‍♀️","n":"woman guard: medium-light skin tone","s":"person-role"},{"e":"💂🏽‍♀️","n":"woman guard: medium skin tone","s":"person-role"},{"e":"💂🏾‍♀️","n":"woman guard: medium-dark skin tone","s":"person-role"},{"e":"💂🏿‍♀️","n":"woman guard: dark skin tone","s":"person-role"},{"e":"🥷","n":"ninja","s":"person-role"},{"e":"🥷🏻","n":"ninja: light skin tone","s":"person-role"},{"e":"🥷🏼","n":"ninja: medium-light skin tone","s":"person-role"},{"e":"🥷🏽","n":"ninja: medium skin tone","s":"person-role"},{"e":"🥷🏾","n":"ninja: medium-dark skin tone","s":"person-role"},{"e":"🥷🏿","n":"ninja: dark skin tone","s":"person-role"},{"e":"👷","n":"construction worker","s":"person-role"},{"e":"👷🏻","n":"construction worker: light skin tone","s":"person-role"},{"e":"👷🏼","n":"construction worker: medium-light skin tone","s":"person-role"},{"e":"👷🏽","n":"construction worker: medium skin tone","s":"person-role"},{"e":"👷🏾","n":"construction worker: medium-dark skin tone","s":"person-role"},{"e":"👷🏿","n":"construction worker: dark skin tone","s":"person-role"},{"e":"👷‍♂️","n":"man construction worker","s":"person-role"},{"e":"👷🏻‍♂️","n":"man construction worker: light skin tone","s":"person-role"},{"e":"👷🏼‍♂️","n":"man construction worker: medium-light skin tone","s":"person-role"},{"e":"👷🏽‍♂️","n":"man construction worker: medium skin tone","s":"person-role"},{"e":"👷🏾‍♂️","n":"man construction worker: medium-dark skin tone","s":"person-role"},{"e":"👷🏿‍♂️","n":"man construction worker: dark skin tone","s":"person-role"},{"e":"👷‍♀️","n":"woman construction worker","s":"person-role"},{"e":"👷🏻‍♀️","n":"woman construction worker: light skin tone","s":"person-role"},{"e":"👷🏼‍♀️","n":"woman construction worker: medium-light skin tone","s":"person-role"},{"e":"👷🏽‍♀️","n":"woman construction worker: medium skin tone","s":"person-role"},{"e":"👷🏾‍♀️","n":"woman construction worker: medium-dark skin tone","s":"person-role"},{"e":"👷🏿‍♀️","n":"woman construction worker: dark skin tone","s":"person-role"},{"e":"🫅","n":"person with crown","s":"person-role"},{"e":"🫅🏻","n":"person with crown: light skin tone","s":"person-role"},{"e":"🫅🏼","n":"person with crown: medium-light skin tone","s":"person-role"},{"e":"🫅🏽","n":"person with crown: medium skin tone","s":"person-role"},{"e":"🫅🏾","n":"person with crown: medium-dark skin tone","s":"person-role"},{"e":"🫅🏿","n":"person with crown: dark skin tone","s":"person-role"},{"e":"🤴","n":"prince","s":"person-role"},{"e":"🤴🏻","n":"prince: light skin tone","s":"person-role"},{"e":"🤴🏼","n":"prince: medium-light skin tone","s":"person-role"},{"e":"🤴🏽","n":"prince: medium skin tone","s":"person-role"},{"e":"🤴🏾","n":"prince: medium-dark skin tone","s":"person-role"},{"e":"🤴🏿","n":"prince: dark skin tone","s":"person-role"},{"e":"👸","n":"princess","s":"person-role"},{"e":"👸🏻","n":"princess: light skin tone","s":"person-role"},{"e":"👸🏼","n":"princess: medium-light skin tone","s":"person-role"},{"e":"👸🏽","n":"princess: medium skin tone","s":"person-role"},{"e":"👸🏾","n":"princess: medium-dark skin tone","s":"person-role"},{"e":"👸🏿","n":"princess: dark skin tone","s":"person-role"},{"e":"👳","n":"person wearing turban","s":"person-role"},{"e":"👳🏻","n":"person wearing turban: light skin tone","s":"person-role"},{"e":"👳🏼","n":"person wearing turban: medium-light skin tone","s":"person-role"},{"e":"👳🏽","n":"person wearing turban: medium skin tone","s":"person-role"},{"e":"👳🏾","n":"person wearing turban: medium-dark skin tone","s":"person-role"},{"e":"👳🏿","n":"person wearing turban: dark skin tone","s":"person-role"},{"e":"👳‍♂️","n":"man wearing turban","s":"person-role"},{"e":"👳🏻‍♂️","n":"man wearing turban: light skin tone","s":"person-role"},{"e":"👳🏼‍♂️","n":"man wearing turban: medium-light skin tone","s":"person-role"},{"e":"👳🏽‍♂️","n":"man wearing turban: medium skin tone","s":"person-role"},{"e":"👳🏾‍♂️","n":"man wearing turban: medium-dark skin tone","s":"person-role"},{"e":"👳🏿‍♂️","n":"man wearing turban: dark skin tone","s":"person-role"},{"e":"👳‍♀️","n":"woman wearing turban","s":"person-role"},{"e":"👳🏻‍♀️","n":"woman wearing turban: light skin tone","s":"person-role"},{"e":"👳🏼‍♀️","n":"woman wearing turban: medium-light skin tone","s":"person-role"},{"e":"👳🏽‍♀️","n":"woman wearing turban: medium skin tone","s":"person-role"},{"e":"👳🏾‍♀️","n":"woman wearing turban: medium-dark skin tone","s":"person-role"},{"e":"👳🏿‍♀️","n":"woman wearing turban: dark skin tone","s":"person-role"},{"e":"👲","n":"person with skullcap","s":"person-role"},{"e":"👲🏻","n":"person with skullcap: light skin tone","s":"person-role"},{"e":"👲🏼","n":"person with skullcap: medium-light skin tone","s":"person-role"},{"e":"👲🏽","n":"person with skullcap: medium skin tone","s":"person-role"},{"e":"👲🏾","n":"person with skullcap: medium-dark skin tone","s":"person-role"},{"e":"👲🏿","n":"person with skullcap: dark skin tone","s":"person-role"},{"e":"🧕","n":"woman with headscarf","s":"person-role"},{"e":"🧕🏻","n":"woman with headscarf: light skin tone","s":"person-role"},{"e":"🧕🏼","n":"woman with headscarf: medium-light skin tone","s":"person-role"},{"e":"🧕🏽","n":"woman with headscarf: medium skin tone","s":"person-role"},{"e":"🧕🏾","n":"woman with headscarf: medium-dark skin tone","s":"person-role"},{"e":"🧕🏿","n":"woman with headscarf: dark skin tone","s":"person-role"},{"e":"🤵","n":"person in tuxedo","s":"person-role"},{"e":"🤵🏻","n":"person in tuxedo: light skin tone","s":"person-role"},{"e":"🤵🏼","n":"person in tuxedo: medium-light skin tone","s":"person-role"},{"e":"🤵🏽","n":"person in tuxedo: medium skin tone","s":"person-role"},{"e":"🤵🏾","n":"person in tuxedo: medium-dark skin tone","s":"person-role"},{"e":"🤵🏿","n":"person in tuxedo: dark skin tone","s":"person-role"},{"e":"🤵‍♂️","n":"man in tuxedo","s":"person-role"},{"e":"🤵🏻‍♂️","n":"man in tuxedo: light skin tone","s":"person-role"},{"e":"🤵🏼‍♂️","n":"man in tuxedo: medium-light skin tone","s":"person-role"},{"e":"🤵🏽‍♂️","n":"man in tuxedo: medium skin tone","s":"person-role"},{"e":"🤵🏾‍♂️","n":"man in tuxedo: medium-dark skin tone","s":"person-role"},{"e":"🤵🏿‍♂️","n":"man in tuxedo: dark skin tone","s":"person-role"},{"e":"🤵‍♀️","n":"woman in tuxedo","s":"person-role"},{"e":"🤵🏻‍♀️","n":"woman in tuxedo: light skin tone","s":"person-role"},{"e":"🤵🏼‍♀️","n":"woman in tuxedo: medium-light skin tone","s":"person-role"},{"e":"🤵🏽‍♀️","n":"woman in tuxedo: medium skin tone","s":"person-role"},{"e":"🤵🏾‍♀️","n":"woman in tuxedo: medium-dark skin tone","s":"person-role"},{"e":"🤵🏿‍♀️","n":"woman in tuxedo: dark skin tone","s":"person-role"},{"e":"👰","n":"person with veil","s":"person-role"},{"e":"👰🏻","n":"person with veil: light skin tone","s":"person-role"},{"e":"👰🏼","n":"person with veil: medium-light skin tone","s":"person-role"},{"e":"👰🏽","n":"person with veil: medium skin tone","s":"person-role"},{"e":"👰🏾","n":"person with veil: medium-dark skin tone","s":"person-role"},{"e":"👰🏿","n":"person with veil: dark skin tone","s":"person-role"},{"e":"👰‍♂️","n":"man with veil","s":"person-role"},{"e":"👰🏻‍♂️","n":"man with veil: light skin tone","s":"person-role"},{"e":"👰🏼‍♂️","n":"man with veil: medium-light skin tone","s":"person-role"},{"e":"👰🏽‍♂️","n":"man with veil: medium skin tone","s":"person-role"},{"e":"👰🏾‍♂️","n":"man with veil: medium-dark skin tone","s":"person-role"},{"e":"👰🏿‍♂️","n":"man with veil: dark skin tone","s":"person-role"},{"e":"👰‍♀️","n":"woman with veil","s":"person-role"},{"e":"👰🏻‍♀️","n":"woman with veil: light skin tone","s":"person-role"},{"e":"👰🏼‍♀️","n":"woman with veil: medium-light skin tone","s":"person-role"},{"e":"👰🏽‍♀️","n":"woman with veil: medium skin tone","s":"person-role"},{"e":"👰🏾‍♀️","n":"woman with veil: medium-dark skin tone","s":"person-role"},{"e":"👰🏿‍♀️","n":"woman with veil: dark skin tone","s":"person-role"},{"e":"🤰","n":"pregnant woman","s":"person-role"},{"e":"🤰🏻","n":"pregnant woman: light skin tone","s":"person-role"},{"e":"🤰🏼","n":"pregnant woman: medium-light skin tone","s":"person-role"},{"e":"🤰🏽","n":"pregnant woman: medium skin tone","s":"person-role"},{"e":"🤰🏾","n":"pregnant woman: medium-dark skin tone","s":"person-role"},{"e":"🤰🏿","n":"pregnant woman: dark skin tone","s":"person-role"},{"e":"🫃","n":"pregnant man","s":"person-role"},{"e":"🫃🏻","n":"pregnant man: light skin tone","s":"person-role"},{"e":"🫃🏼","n":"pregnant man: medium-light skin tone","s":"person-role"},{"e":"🫃🏽","n":"pregnant man: medium skin tone","s":"person-role"},{"e":"🫃🏾","n":"pregnant man: medium-dark skin tone","s":"person-role"},{"e":"🫃🏿","n":"pregnant man: dark skin tone","s":"person-role"},{"e":"🫄","n":"pregnant person","s":"person-role"},{"e":"🫄🏻","n":"pregnant person: light skin tone","s":"person-role"},{"e":"🫄🏼","n":"pregnant person: medium-light skin tone","s":"person-role"},{"e":"🫄🏽","n":"pregnant person: medium skin tone","s":"person-role"},{"e":"🫄🏾","n":"pregnant person: medium-dark skin tone","s":"person-role"},{"e":"🫄🏿","n":"pregnant person: dark skin tone","s":"person-role"},{"e":"🤱","n":"breast-feeding","s":"person-role"},{"e":"🤱🏻","n":"breast-feeding: light skin tone","s":"person-role"},{"e":"🤱🏼","n":"breast-feeding: medium-light skin tone","s":"person-role"},{"e":"🤱🏽","n":"breast-feeding: medium skin tone","s":"person-role"},{"e":"🤱🏾","n":"breast-feeding: medium-dark skin tone","s":"person-role"},{"e":"🤱🏿","n":"breast-feeding: dark skin tone","s":"person-role"},{"e":"👩‍🍼","n":"woman feeding baby","s":"person-role"},{"e":"👩🏻‍🍼","n":"woman feeding baby: light skin tone","s":"person-role"},{"e":"👩🏼‍🍼","n":"woman feeding baby: medium-light skin tone","s":"person-role"},{"e":"👩🏽‍🍼","n":"woman feeding baby: medium skin tone","s":"person-role"},{"e":"👩🏾‍🍼","n":"woman feeding baby: medium-dark skin tone","s":"person-role"},{"e":"👩🏿‍🍼","n":"woman feeding baby: dark skin tone","s":"person-role"},{"e":"👨‍🍼","n":"man feeding baby","s":"person-role"},{"e":"👨🏻‍🍼","n":"man feeding baby: light skin tone","s":"person-role"},{"e":"👨🏼‍🍼","n":"man feeding baby: medium-light skin tone","s":"person-role"},{"e":"👨🏽‍🍼","n":"man feeding baby: medium skin tone","s":"person-role"},{"e":"👨🏾‍🍼","n":"man feeding baby: medium-dark skin tone","s":"person-role"},{"e":"👨🏿‍🍼","n":"man feeding baby: dark skin tone","s":"person-role"},{"e":"🧑‍🍼","n":"person feeding baby","s":"person-role"},{"e":"🧑🏻‍🍼","n":"person feeding baby: light skin tone","s":"person-role"},{"e":"🧑🏼‍🍼","n":"person feeding baby: medium-light skin tone","s":"person-role"},{"e":"🧑🏽‍🍼","n":"person feeding baby: medium skin tone","s":"person-role"},{"e":"🧑🏾‍🍼","n":"person feeding baby: medium-dark skin tone","s":"person-role"},{"e":"🧑🏿‍🍼","n":"person feeding baby: dark skin tone","s":"person-role"},{"e":"👼","n":"baby angel","s":"person-fantasy"},{"e":"👼🏻","n":"baby angel: light skin tone","s":"person-fantasy"},{"e":"👼🏼","n":"baby angel: medium-light skin tone","s":"person-fantasy"},{"e":"👼🏽","n":"baby angel: medium skin tone","s":"person-fantasy"},{"e":"👼🏾","n":"baby angel: medium-dark skin tone","s":"person-fantasy"},{"e":"👼🏿","n":"baby angel: dark skin tone","s":"person-fantasy"},{"e":"🎅","n":"Santa Claus","s":"person-fantasy"},{"e":"🎅🏻","n":"Santa Claus: light skin tone","s":"person-fantasy"},{"e":"🎅🏼","n":"Santa Claus: medium-light skin tone","s":"person-fantasy"},{"e":"🎅🏽","n":"Santa Claus: medium skin tone","s":"person-fantasy"},{"e":"🎅🏾","n":"Santa Claus: medium-dark skin tone","s":"person-fantasy"},{"e":"🎅🏿","n":"Santa Claus: dark skin tone","s":"person-fantasy"},{"e":"🤶","n":"Mrs. Claus","s":"person-fantasy"},{"e":"🤶🏻","n":"Mrs. Claus: light skin tone","s":"person-fantasy"},{"e":"🤶🏼","n":"Mrs. Claus: medium-light skin tone","s":"person-fantasy"},{"e":"🤶🏽","n":"Mrs. Claus: medium skin tone","s":"person-fantasy"},{"e":"🤶🏾","n":"Mrs. Claus: medium-dark skin tone","s":"person-fantasy"},{"e":"🤶🏿","n":"Mrs. Claus: dark skin tone","s":"person-fantasy"},{"e":"🧑‍🎄","n":"Mx Claus","s":"person-fantasy"},{"e":"🧑🏻‍🎄","n":"Mx Claus: light skin tone","s":"person-fantasy"},{"e":"🧑🏼‍🎄","n":"Mx Claus: medium-light skin tone","s":"person-fantasy"},{"e":"🧑🏽‍🎄","n":"Mx Claus: medium skin tone","s":"person-fantasy"},{"e":"🧑🏾‍🎄","n":"Mx Claus: medium-dark skin tone","s":"person-fantasy"},{"e":"🧑🏿‍🎄","n":"Mx Claus: dark skin tone","s":"person-fantasy"},{"e":"🦸","n":"superhero","s":"person-fantasy"},{"e":"🦸🏻","n":"superhero: light skin tone","s":"person-fantasy"},{"e":"🦸🏼","n":"superhero: medium-light skin tone","s":"person-fantasy"},{"e":"🦸🏽","n":"superhero: medium skin tone","s":"person-fantasy"},{"e":"🦸🏾","n":"superhero: medium-dark skin tone","s":"person-fantasy"},{"e":"🦸🏿","n":"superhero: dark skin tone","s":"person-fantasy"},{"e":"🦸‍♂️","n":"man superhero","s":"person-fantasy"},{"e":"🦸🏻‍♂️","n":"man superhero: light skin tone","s":"person-fantasy"},{"e":"🦸🏼‍♂️","n":"man superhero: medium-light skin tone","s":"person-fantasy"},{"e":"🦸🏽‍♂️","n":"man superhero: medium skin tone","s":"person-fantasy"},{"e":"🦸🏾‍♂️","n":"man superhero: medium-dark skin tone","s":"person-fantasy"},{"e":"🦸🏿‍♂️","n":"man superhero: dark skin tone","s":"person-fantasy"},{"e":"🦸‍♀️","n":"woman superhero","s":"person-fantasy"},{"e":"🦸🏻‍♀️","n":"woman superhero: light skin tone","s":"person-fantasy"},{"e":"🦸🏼‍♀️","n":"woman superhero: medium-light skin tone","s":"person-fantasy"},{"e":"🦸🏽‍♀️","n":"woman superhero: medium skin tone","s":"person-fantasy"},{"e":"🦸🏾‍♀️","n":"woman superhero: medium-dark skin tone","s":"person-fantasy"},{"e":"🦸🏿‍♀️","n":"woman superhero: dark skin tone","s":"person-fantasy"},{"e":"🦹","n":"supervillain","s":"person-fantasy"},{"e":"🦹🏻","n":"supervillain: light skin tone","s":"person-fantasy"},{"e":"🦹🏼","n":"supervillain: medium-light skin tone","s":"person-fantasy"},{"e":"🦹🏽","n":"supervillain: medium skin tone","s":"person-fantasy"},{"e":"🦹🏾","n":"supervillain: medium-dark skin tone","s":"person-fantasy"},{"e":"🦹🏿","n":"supervillain: dark skin tone","s":"person-fantasy"},{"e":"🦹‍♂️","n":"man supervillain","s":"person-fantasy"},{"e":"🦹🏻‍♂️","n":"man supervillain: light skin tone","s":"person-fantasy"},{"e":"🦹🏼‍♂️","n":"man supervillain: medium-light skin tone","s":"person-fantasy"},{"e":"🦹🏽‍♂️","n":"man supervillain: medium skin tone","s":"person-fantasy"},{"e":"🦹🏾‍♂️","n":"man supervillain: medium-dark skin tone","s":"person-fantasy"},{"e":"🦹🏿‍♂️","n":"man supervillain: dark skin tone","s":"person-fantasy"},{"e":"🦹‍♀️","n":"woman supervillain","s":"person-fantasy"},{"e":"🦹🏻‍♀️","n":"woman supervillain: light skin tone","s":"person-fantasy"},{"e":"🦹🏼‍♀️","n":"woman supervillain: medium-light skin tone","s":"person-fantasy"},{"e":"🦹🏽‍♀️","n":"woman supervillain: medium skin tone","s":"person-fantasy"},{"e":"🦹🏾‍♀️","n":"woman supervillain: medium-dark skin tone","s":"person-fantasy"},{"e":"🦹🏿‍♀️","n":"woman supervillain: dark skin tone","s":"person-fantasy"},{"e":"🧙","n":"mage","s":"person-fantasy"},{"e":"🧙🏻","n":"mage: light skin tone","s":"person-fantasy"},{"e":"🧙🏼","n":"mage: medium-light skin tone","s":"person-fantasy"},{"e":"🧙🏽","n":"mage: medium skin tone","s":"person-fantasy"},{"e":"🧙🏾","n":"mage: medium-dark skin tone","s":"person-fantasy"},{"e":"🧙🏿","n":"mage: dark skin tone","s":"person-fantasy"},{"e":"🧙‍♂️","n":"man mage","s":"person-fantasy"},{"e":"🧙🏻‍♂️","n":"man mage: light skin tone","s":"person-fantasy"},{"e":"🧙🏼‍♂️","n":"man mage: medium-light skin tone","s":"person-fantasy"},{"e":"🧙🏽‍♂️","n":"man mage: medium skin tone","s":"person-fantasy"},{"e":"🧙🏾‍♂️","n":"man mage: medium-dark skin tone","s":"person-fantasy"},{"e":"🧙🏿‍♂️","n":"man mage: dark skin tone","s":"person-fantasy"},{"e":"🧙‍♀️","n":"woman mage","s":"person-fantasy"},{"e":"🧙🏻‍♀️","n":"woman mage: light skin tone","s":"person-fantasy"},{"e":"🧙🏼‍♀️","n":"woman mage: medium-light skin tone","s":"person-fantasy"},{"e":"🧙🏽‍♀️","n":"woman mage: medium skin tone","s":"person-fantasy"},{"e":"🧙🏾‍♀️","n":"woman mage: medium-dark skin tone","s":"person-fantasy"},{"e":"🧙🏿‍♀️","n":"woman mage: dark skin tone","s":"person-fantasy"},{"e":"🧚","n":"fairy","s":"person-fantasy"},{"e":"🧚🏻","n":"fairy: light skin tone","s":"person-fantasy"},{"e":"🧚🏼","n":"fairy: medium-light skin tone","s":"person-fantasy"},{"e":"🧚🏽","n":"fairy: medium skin tone","s":"person-fantasy"},{"e":"🧚🏾","n":"fairy: medium-dark skin tone","s":"person-fantasy"},{"e":"🧚🏿","n":"fairy: dark skin tone","s":"person-fantasy"},{"e":"🧚‍♂️","n":"man fairy","s":"person-fantasy"},{"e":"🧚🏻‍♂️","n":"man fairy: light skin tone","s":"person-fantasy"},{"e":"🧚🏼‍♂️","n":"man fairy: medium-light skin tone","s":"person-fantasy"},{"e":"🧚🏽‍♂️","n":"man fairy: medium skin tone","s":"person-fantasy"},{"e":"🧚🏾‍♂️","n":"man fairy: medium-dark skin tone","s":"person-fantasy"},{"e":"🧚🏿‍♂️","n":"man fairy: dark skin tone","s":"person-fantasy"},{"e":"🧚‍♀️","n":"woman fairy","s":"person-fantasy"},{"e":"🧚🏻‍♀️","n":"woman fairy: light skin tone","s":"person-fantasy"},{"e":"🧚🏼‍♀️","n":"woman fairy: medium-light skin tone","s":"person-fantasy"},{"e":"🧚🏽‍♀️","n":"woman fairy: medium skin tone","s":"person-fantasy"},{"e":"🧚🏾‍♀️","n":"woman fairy: medium-dark skin tone","s":"person-fantasy"},{"e":"🧚🏿‍♀️","n":"woman fairy: dark skin tone","s":"person-fantasy"},{"e":"🧛","n":"vampire","s":"person-fantasy"},{"e":"🧛🏻","n":"vampire: light skin tone","s":"person-fantasy"},{"e":"🧛🏼","n":"vampire: medium-light skin tone","s":"person-fantasy"},{"e":"🧛🏽","n":"vampire: medium skin tone","s":"person-fantasy"},{"e":"🧛🏾","n":"vampire: medium-dark skin tone","s":"person-fantasy"},{"e":"🧛🏿","n":"vampire: dark skin tone","s":"person-fantasy"},{"e":"🧛‍♂️","n":"man vampire","s":"person-fantasy"},{"e":"🧛🏻‍♂️","n":"man vampire: light skin tone","s":"person-fantasy"},{"e":"🧛🏼‍♂️","n":"man vampire: medium-light skin tone","s":"person-fantasy"},{"e":"🧛🏽‍♂️","n":"man vampire: medium skin tone","s":"person-fantasy"},{"e":"🧛🏾‍♂️","n":"man vampire: medium-dark skin tone","s":"person-fantasy"},{"e":"🧛🏿‍♂️","n":"man vampire: dark skin tone","s":"person-fantasy"},{"e":"🧛‍♀️","n":"woman vampire","s":"person-fantasy"},{"e":"🧛🏻‍♀️","n":"woman vampire: light skin tone","s":"person-fantasy"},{"e":"🧛🏼‍♀️","n":"woman vampire: medium-light skin tone","s":"person-fantasy"},{"e":"🧛🏽‍♀️","n":"woman vampire: medium skin tone","s":"person-fantasy"},{"e":"🧛🏾‍♀️","n":"woman vampire: medium-dark skin tone","s":"person-fantasy"},{"e":"🧛🏿‍♀️","n":"woman vampire: dark skin tone","s":"person-fantasy"},{"e":"🧜","n":"merperson","s":"person-fantasy"},{"e":"🧜🏻","n":"merperson: light skin tone","s":"person-fantasy"},{"e":"🧜🏼","n":"merperson: medium-light skin tone","s":"person-fantasy"},{"e":"🧜🏽","n":"merperson: medium skin tone","s":"person-fantasy"},{"e":"🧜🏾","n":"merperson: medium-dark skin tone","s":"person-fantasy"},{"e":"🧜🏿","n":"merperson: dark skin tone","s":"person-fantasy"},{"e":"🧜‍♂️","n":"merman","s":"person-fantasy"},{"e":"🧜🏻‍♂️","n":"merman: light skin tone","s":"person-fantasy"},{"e":"🧜🏼‍♂️","n":"merman: medium-light skin tone","s":"person-fantasy"},{"e":"🧜🏽‍♂️","n":"merman: medium skin tone","s":"person-fantasy"},{"e":"🧜🏾‍♂️","n":"merman: medium-dark skin tone","s":"person-fantasy"},{"e":"🧜🏿‍♂️","n":"merman: dark skin tone","s":"person-fantasy"},{"e":"🧜‍♀️","n":"mermaid","s":"person-fantasy"},{"e":"🧜🏻‍♀️","n":"mermaid: light skin tone","s":"person-fantasy"},{"e":"🧜🏼‍♀️","n":"mermaid: medium-light skin tone","s":"person-fantasy"},{"e":"🧜🏽‍♀️","n":"mermaid: medium skin tone","s":"person-fantasy"},{"e":"🧜🏾‍♀️","n":"mermaid: medium-dark skin tone","s":"person-fantasy"},{"e":"🧜🏿‍♀️","n":"mermaid: dark skin tone","s":"person-fantasy"},{"e":"🧝","n":"elf","s":"person-fantasy"},{"e":"🧝🏻","n":"elf: light skin tone","s":"person-fantasy"},{"e":"🧝🏼","n":"elf: medium-light skin tone","s":"person-fantasy"},{"e":"🧝🏽","n":"elf: medium skin tone","s":"person-fantasy"},{"e":"🧝🏾","n":"elf: medium-dark skin tone","s":"person-fantasy"},{"e":"🧝🏿","n":"elf: dark skin tone","s":"person-fantasy"},{"e":"🧝‍♂️","n":"man elf","s":"person-fantasy"},{"e":"🧝🏻‍♂️","n":"man elf: light skin tone","s":"person-fantasy"},{"e":"🧝🏼‍♂️","n":"man elf: medium-light skin tone","s":"person-fantasy"},{"e":"🧝🏽‍♂️","n":"man elf: medium skin tone","s":"person-fantasy"},{"e":"🧝🏾‍♂️","n":"man elf: medium-dark skin tone","s":"person-fantasy"},{"e":"🧝🏿‍♂️","n":"man elf: dark skin tone","s":"person-fantasy"},{"e":"🧝‍♀️","n":"woman elf","s":"person-fantasy"},{"e":"🧝🏻‍♀️","n":"woman elf: light skin tone","s":"person-fantasy"},{"e":"🧝🏼‍♀️","n":"woman elf: medium-light skin tone","s":"person-fantasy"},{"e":"🧝🏽‍♀️","n":"woman elf: medium skin tone","s":"person-fantasy"},{"e":"🧝🏾‍♀️","n":"woman elf: medium-dark skin tone","s":"person-fantasy"},{"e":"🧝🏿‍♀️","n":"woman elf: dark skin tone","s":"person-fantasy"},{"e":"🧞","n":"genie","s":"person-fantasy"},{"e":"🧞‍♂️","n":"man genie","s":"person-fantasy"},{"e":"🧞‍♀️","n":"woman genie","s":"person-fantasy"},{"e":"🧟","n":"zombie","s":"person-fantasy"},{"e":"🧟‍♂️","n":"man zombie","s":"person-fantasy"},{"e":"🧟‍♀️","n":"woman zombie","s":"person-fantasy"},{"e":"🧌","n":"troll","s":"person-fantasy"},{"e":"🫈","n":"hairy creature","s":"person-fantasy"},{"e":"💆","n":"person getting massage","s":"person-activity"},{"e":"💆🏻","n":"person getting massage: light skin tone","s":"person-activity"},{"e":"💆🏼","n":"person getting massage: medium-light skin tone","s":"person-activity"},{"e":"💆🏽","n":"person getting massage: medium skin tone","s":"person-activity"},{"e":"💆🏾","n":"person getting massage: medium-dark skin tone","s":"person-activity"},{"e":"💆🏿","n":"person getting massage: dark skin tone","s":"person-activity"},{"e":"💆‍♂️","n":"man getting massage","s":"person-activity"},{"e":"💆🏻‍♂️","n":"man getting massage: light skin tone","s":"person-activity"},{"e":"💆🏼‍♂️","n":"man getting massage: medium-light skin tone","s":"person-activity"},{"e":"💆🏽‍♂️","n":"man getting massage: medium skin tone","s":"person-activity"},{"e":"💆🏾‍♂️","n":"man getting massage: medium-dark skin tone","s":"person-activity"},{"e":"💆🏿‍♂️","n":"man getting massage: dark skin tone","s":"person-activity"},{"e":"💆‍♀️","n":"woman getting massage","s":"person-activity"},{"e":"💆🏻‍♀️","n":"woman getting massage: light skin tone","s":"person-activity"},{"e":"💆🏼‍♀️","n":"woman getting massage: medium-light skin tone","s":"person-activity"},{"e":"💆🏽‍♀️","n":"woman getting massage: medium skin tone","s":"person-activity"},{"e":"💆🏾‍♀️","n":"woman getting massage: medium-dark skin tone","s":"person-activity"},{"e":"💆🏿‍♀️","n":"woman getting massage: dark skin tone","s":"person-activity"},{"e":"💇","n":"person getting haircut","s":"person-activity"},{"e":"💇🏻","n":"person getting haircut: light skin tone","s":"person-activity"},{"e":"💇🏼","n":"person getting haircut: medium-light skin tone","s":"person-activity"},{"e":"💇🏽","n":"person getting haircut: medium skin tone","s":"person-activity"},{"e":"💇🏾","n":"person getting haircut: medium-dark skin tone","s":"person-activity"},{"e":"💇🏿","n":"person getting haircut: dark skin tone","s":"person-activity"},{"e":"💇‍♂️","n":"man getting haircut","s":"person-activity"},{"e":"💇🏻‍♂️","n":"man getting haircut: light skin tone","s":"person-activity"},{"e":"💇🏼‍♂️","n":"man getting haircut: medium-light skin tone","s":"person-activity"},{"e":"💇🏽‍♂️","n":"man getting haircut: medium skin tone","s":"person-activity"},{"e":"💇🏾‍♂️","n":"man getting haircut: medium-dark skin tone","s":"person-activity"},{"e":"💇🏿‍♂️","n":"man getting haircut: dark skin tone","s":"person-activity"},{"e":"💇‍♀️","n":"woman getting haircut","s":"person-activity"},{"e":"💇🏻‍♀️","n":"woman getting haircut: light skin tone","s":"person-activity"},{"e":"💇🏼‍♀️","n":"woman getting haircut: medium-light skin tone","s":"person-activity"},{"e":"💇🏽‍♀️","n":"woman getting haircut: medium skin tone","s":"person-activity"},{"e":"💇🏾‍♀️","n":"woman getting haircut: medium-dark skin tone","s":"person-activity"},{"e":"💇🏿‍♀️","n":"woman getting haircut: dark skin tone","s":"person-activity"},{"e":"🚶","n":"person walking","s":"person-activity"},{"e":"🚶🏻","n":"person walking: light skin tone","s":"person-activity"},{"e":"🚶🏼","n":"person walking: medium-light skin tone","s":"person-activity"},{"e":"🚶🏽","n":"person walking: medium skin tone","s":"person-activity"},{"e":"🚶🏾","n":"person walking: medium-dark skin tone","s":"person-activity"},{"e":"🚶🏿","n":"person walking: dark skin tone","s":"person-activity"},{"e":"🚶‍♂️","n":"man walking","s":"person-activity"},{"e":"🚶🏻‍♂️","n":"man walking: light skin tone","s":"person-activity"},{"e":"🚶🏼‍♂️","n":"man walking: medium-light skin tone","s":"person-activity"},{"e":"🚶🏽‍♂️","n":"man walking: medium skin tone","s":"person-activity"},{"e":"🚶🏾‍♂️","n":"man walking: medium-dark skin tone","s":"person-activity"},{"e":"🚶🏿‍♂️","n":"man walking: dark skin tone","s":"person-activity"},{"e":"🚶‍♀️","n":"woman walking","s":"person-activity"},{"e":"🚶🏻‍♀️","n":"woman walking: light skin tone","s":"person-activity"},{"e":"🚶🏼‍♀️","n":"woman walking: medium-light skin tone","s":"person-activity"},{"e":"🚶🏽‍♀️","n":"woman walking: medium skin tone","s":"person-activity"},{"e":"🚶🏾‍♀️","n":"woman walking: medium-dark skin tone","s":"person-activity"},{"e":"🚶🏿‍♀️","n":"woman walking: dark skin tone","s":"person-activity"},{"e":"🚶‍➡️","n":"person walking facing right","s":"person-activity"},{"e":"🚶🏻‍➡️","n":"person walking facing right: light skin tone","s":"person-activity"},{"e":"🚶🏼‍➡️","n":"person walking facing right: medium-light skin tone","s":"person-activity"},{"e":"🚶🏽‍➡️","n":"person walking facing right: medium skin tone","s":"person-activity"},{"e":"🚶🏾‍➡️","n":"person walking facing right: medium-dark skin tone","s":"person-activity"},{"e":"🚶🏿‍➡️","n":"person walking facing right: dark skin tone","s":"person-activity"},{"e":"🚶‍♀️‍➡️","n":"woman walking facing right","s":"person-activity"},{"e":"🚶🏻‍♀️‍➡️","n":"woman walking facing right: light skin tone","s":"person-activity"},{"e":"🚶🏼‍♀️‍➡️","n":"woman walking facing right: medium-light skin tone","s":"person-activity"},{"e":"🚶🏽‍♀️‍➡️","n":"woman walking facing right: medium skin tone","s":"person-activity"},{"e":"🚶🏾‍♀️‍➡️","n":"woman walking facing right: medium-dark skin tone","s":"person-activity"},{"e":"🚶🏿‍♀️‍➡️","n":"woman walking facing right: dark skin tone","s":"person-activity"},{"e":"🚶‍♂️‍➡️","n":"man walking facing right","s":"person-activity"},{"e":"🚶🏻‍♂️‍➡️","n":"man walking facing right: light skin tone","s":"person-activity"},{"e":"🚶🏼‍♂️‍➡️","n":"man walking facing right: medium-light skin tone","s":"person-activity"},{"e":"🚶🏽‍♂️‍➡️","n":"man walking facing right: medium skin tone","s":"person-activity"},{"e":"🚶🏾‍♂️‍➡️","n":"man walking facing right: medium-dark skin tone","s":"person-activity"},{"e":"🚶🏿‍♂️‍➡️","n":"man walking facing right: dark skin tone","s":"person-activity"},{"e":"🧍","n":"person standing","s":"person-activity"},{"e":"🧍🏻","n":"person standing: light skin tone","s":"person-activity"},{"e":"🧍🏼","n":"person standing: medium-light skin tone","s":"person-activity"},{"e":"🧍🏽","n":"person standing: medium skin tone","s":"person-activity"},{"e":"🧍🏾","n":"person standing: medium-dark skin tone","s":"person-activity"},{"e":"🧍🏿","n":"person standing: dark skin tone","s":"person-activity"},{"e":"🧍‍♂️","n":"man standing","s":"person-activity"},{"e":"🧍🏻‍♂️","n":"man standing: light skin tone","s":"person-activity"},{"e":"🧍🏼‍♂️","n":"man standing: medium-light skin tone","s":"person-activity"},{"e":"🧍🏽‍♂️","n":"man standing: medium skin tone","s":"person-activity"},{"e":"🧍🏾‍♂️","n":"man standing: medium-dark skin tone","s":"person-activity"},{"e":"🧍🏿‍♂️","n":"man standing: dark skin tone","s":"person-activity"},{"e":"🧍‍♀️","n":"woman standing","s":"person-activity"},{"e":"🧍🏻‍♀️","n":"woman standing: light skin tone","s":"person-activity"},{"e":"🧍🏼‍♀️","n":"woman standing: medium-light skin tone","s":"person-activity"},{"e":"🧍🏽‍♀️","n":"woman standing: medium skin tone","s":"person-activity"},{"e":"🧍🏾‍♀️","n":"woman standing: medium-dark skin tone","s":"person-activity"},{"e":"🧍🏿‍♀️","n":"woman standing: dark skin tone","s":"person-activity"},{"e":"🧎","n":"person kneeling","s":"person-activity"},{"e":"🧎🏻","n":"person kneeling: light skin tone","s":"person-activity"},{"e":"🧎🏼","n":"person kneeling: medium-light skin tone","s":"person-activity"},{"e":"🧎🏽","n":"person kneeling: medium skin tone","s":"person-activity"},{"e":"🧎🏾","n":"person kneeling: medium-dark skin tone","s":"person-activity"},{"e":"🧎🏿","n":"person kneeling: dark skin tone","s":"person-activity"},{"e":"🧎‍♂️","n":"man kneeling","s":"person-activity"},{"e":"🧎🏻‍♂️","n":"man kneeling: light skin tone","s":"person-activity"},{"e":"🧎🏼‍♂️","n":"man kneeling: medium-light skin tone","s":"person-activity"},{"e":"🧎🏽‍♂️","n":"man kneeling: medium skin tone","s":"person-activity"},{"e":"🧎🏾‍♂️","n":"man kneeling: medium-dark skin tone","s":"person-activity"},{"e":"🧎🏿‍♂️","n":"man kneeling: dark skin tone","s":"person-activity"},{"e":"🧎‍♀️","n":"woman kneeling","s":"person-activity"},{"e":"🧎🏻‍♀️","n":"woman kneeling: light skin tone","s":"person-activity"},{"e":"🧎🏼‍♀️","n":"woman kneeling: medium-light skin tone","s":"person-activity"},{"e":"🧎🏽‍♀️","n":"woman kneeling: medium skin tone","s":"person-activity"},{"e":"🧎🏾‍♀️","n":"woman kneeling: medium-dark skin tone","s":"person-activity"},{"e":"🧎🏿‍♀️","n":"woman kneeling: dark skin tone","s":"person-activity"},{"e":"🧎‍➡️","n":"person kneeling facing right","s":"person-activity"},{"e":"🧎🏻‍➡️","n":"person kneeling facing right: light skin tone","s":"person-activity"},{"e":"🧎🏼‍➡️","n":"person kneeling facing right: medium-light skin tone","s":"person-activity"},{"e":"🧎🏽‍➡️","n":"person kneeling facing right: medium skin tone","s":"person-activity"},{"e":"🧎🏾‍➡️","n":"person kneeling facing right: medium-dark skin tone","s":"person-activity"},{"e":"🧎🏿‍➡️","n":"person kneeling facing right: dark skin tone","s":"person-activity"},{"e":"🧎‍♀️‍➡️","n":"woman kneeling facing right","s":"person-activity"},{"e":"🧎🏻‍♀️‍➡️","n":"woman kneeling facing right: light skin tone","s":"person-activity"},{"e":"🧎🏼‍♀️‍➡️","n":"woman kneeling facing right: medium-light skin tone","s":"person-activity"},{"e":"🧎🏽‍♀️‍➡️","n":"woman kneeling facing right: medium skin tone","s":"person-activity"},{"e":"🧎🏾‍♀️‍➡️","n":"woman kneeling facing right: medium-dark skin tone","s":"person-activity"},{"e":"🧎🏿‍♀️‍➡️","n":"woman kneeling facing right: dark skin tone","s":"person-activity"},{"e":"🧎‍♂️‍➡️","n":"man kneeling facing right","s":"person-activity"},{"e":"🧎🏻‍♂️‍➡️","n":"man kneeling facing right: light skin tone","s":"person-activity"},{"e":"🧎🏼‍♂️‍➡️","n":"man kneeling facing right: medium-light skin tone","s":"person-activity"},{"e":"🧎🏽‍♂️‍➡️","n":"man kneeling facing right: medium skin tone","s":"person-activity"},{"e":"🧎🏾‍♂️‍➡️","n":"man kneeling facing right: medium-dark skin tone","s":"person-activity"},{"e":"🧎🏿‍♂️‍➡️","n":"man kneeling facing right: dark skin tone","s":"person-activity"},{"e":"🧑‍🦯","n":"person with white cane","s":"person-activity"},{"e":"🧑🏻‍🦯","n":"person with white cane: light skin tone","s":"person-activity"},{"e":"🧑🏼‍🦯","n":"person with white cane: medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🦯","n":"person with white cane: medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🦯","n":"person with white cane: medium-dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🦯","n":"person with white cane: dark skin tone","s":"person-activity"},{"e":"🧑‍🦯‍➡️","n":"person with white cane facing right","s":"person-activity"},{"e":"🧑🏻‍🦯‍➡️","n":"person with white cane facing right: light skin tone","s":"person-activity"},{"e":"🧑🏼‍🦯‍➡️","n":"person with white cane facing right: medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🦯‍➡️","n":"person with white cane facing right: medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🦯‍➡️","n":"person with white cane facing right: medium-dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🦯‍➡️","n":"person with white cane facing right: dark skin tone","s":"person-activity"},{"e":"👨‍🦯","n":"man with white cane","s":"person-activity"},{"e":"👨🏻‍🦯","n":"man with white cane: light skin tone","s":"person-activity"},{"e":"👨🏼‍🦯","n":"man with white cane: medium-light skin tone","s":"person-activity"},{"e":"👨🏽‍🦯","n":"man with white cane: medium skin tone","s":"person-activity"},{"e":"👨🏾‍🦯","n":"man with white cane: medium-dark skin tone","s":"person-activity"},{"e":"👨🏿‍🦯","n":"man with white cane: dark skin tone","s":"person-activity"},{"e":"👨‍🦯‍➡️","n":"man with white cane facing right","s":"person-activity"},{"e":"👨🏻‍🦯‍➡️","n":"man with white cane facing right: light skin tone","s":"person-activity"},{"e":"👨🏼‍🦯‍➡️","n":"man with white cane facing right: medium-light skin tone","s":"person-activity"},{"e":"👨🏽‍🦯‍➡️","n":"man with white cane facing right: medium skin tone","s":"person-activity"},{"e":"👨🏾‍🦯‍➡️","n":"man with white cane facing right: medium-dark skin tone","s":"person-activity"},{"e":"👨🏿‍🦯‍➡️","n":"man with white cane facing right: dark skin tone","s":"person-activity"},{"e":"👩‍🦯","n":"woman with white cane","s":"person-activity"},{"e":"👩🏻‍🦯","n":"woman with white cane: light skin tone","s":"person-activity"},{"e":"👩🏼‍🦯","n":"woman with white cane: medium-light skin tone","s":"person-activity"},{"e":"👩🏽‍🦯","n":"woman with white cane: medium skin tone","s":"person-activity"},{"e":"👩🏾‍🦯","n":"woman with white cane: medium-dark skin tone","s":"person-activity"},{"e":"👩🏿‍🦯","n":"woman with white cane: dark skin tone","s":"person-activity"},{"e":"👩‍🦯‍➡️","n":"woman with white cane facing right","s":"person-activity"},{"e":"👩🏻‍🦯‍➡️","n":"woman with white cane facing right: light skin tone","s":"person-activity"},{"e":"👩🏼‍🦯‍➡️","n":"woman with white cane facing right: medium-light skin tone","s":"person-activity"},{"e":"👩🏽‍🦯‍➡️","n":"woman with white cane facing right: medium skin tone","s":"person-activity"},{"e":"👩🏾‍🦯‍➡️","n":"woman with white cane facing right: medium-dark skin tone","s":"person-activity"},{"e":"👩🏿‍🦯‍➡️","n":"woman with white cane facing right: dark skin tone","s":"person-activity"},{"e":"🧑‍🦼","n":"person in motorized wheelchair","s":"person-activity"},{"e":"🧑🏻‍🦼","n":"person in motorized wheelchair: light skin tone","s":"person-activity"},{"e":"🧑🏼‍🦼","n":"person in motorized wheelchair: medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🦼","n":"person in motorized wheelchair: medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🦼","n":"person in motorized wheelchair: medium-dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🦼","n":"person in motorized wheelchair: dark skin tone","s":"person-activity"},{"e":"🧑‍🦼‍➡️","n":"person in motorized wheelchair facing right","s":"person-activity"},{"e":"🧑🏻‍🦼‍➡️","n":"person in motorized wheelchair facing right: light skin tone","s":"person-activity"},{"e":"🧑🏼‍🦼‍➡️","n":"person in motorized wheelchair facing right: medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🦼‍➡️","n":"person in motorized wheelchair facing right: medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🦼‍➡️","n":"person in motorized wheelchair facing right: medium-dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🦼‍➡️","n":"person in motorized wheelchair facing right: dark skin tone","s":"person-activity"},{"e":"👨‍🦼","n":"man in motorized wheelchair","s":"person-activity"},{"e":"👨🏻‍🦼","n":"man in motorized wheelchair: light skin tone","s":"person-activity"},{"e":"👨🏼‍🦼","n":"man in motorized wheelchair: medium-light skin tone","s":"person-activity"},{"e":"👨🏽‍🦼","n":"man in motorized wheelchair: medium skin tone","s":"person-activity"},{"e":"👨🏾‍🦼","n":"man in motorized wheelchair: medium-dark skin tone","s":"person-activity"},{"e":"👨🏿‍🦼","n":"man in motorized wheelchair: dark skin tone","s":"person-activity"},{"e":"👨‍🦼‍➡️","n":"man in motorized wheelchair facing right","s":"person-activity"},{"e":"👨🏻‍🦼‍➡️","n":"man in motorized wheelchair facing right: light skin tone","s":"person-activity"},{"e":"👨🏼‍🦼‍➡️","n":"man in motorized wheelchair facing right: medium-light skin tone","s":"person-activity"},{"e":"👨🏽‍🦼‍➡️","n":"man in motorized wheelchair facing right: medium skin tone","s":"person-activity"},{"e":"👨🏾‍🦼‍➡️","n":"man in motorized wheelchair facing right: medium-dark skin tone","s":"person-activity"},{"e":"👨🏿‍🦼‍➡️","n":"man in motorized wheelchair facing right: dark skin tone","s":"person-activity"},{"e":"👩‍🦼","n":"woman in motorized wheelchair","s":"person-activity"},{"e":"👩🏻‍🦼","n":"woman in motorized wheelchair: light skin tone","s":"person-activity"},{"e":"👩🏼‍🦼","n":"woman in motorized wheelchair: medium-light skin tone","s":"person-activity"},{"e":"👩🏽‍🦼","n":"woman in motorized wheelchair: medium skin tone","s":"person-activity"},{"e":"👩🏾‍🦼","n":"woman in motorized wheelchair: medium-dark skin tone","s":"person-activity"},{"e":"👩🏿‍🦼","n":"woman in motorized wheelchair: dark skin tone","s":"person-activity"},{"e":"👩‍🦼‍➡️","n":"woman in motorized wheelchair facing right","s":"person-activity"},{"e":"👩🏻‍🦼‍➡️","n":"woman in motorized wheelchair facing right: light skin tone","s":"person-activity"},{"e":"👩🏼‍🦼‍➡️","n":"woman in motorized wheelchair facing right: medium-light skin tone","s":"person-activity"},{"e":"👩🏽‍🦼‍➡️","n":"woman in motorized wheelchair facing right: medium skin tone","s":"person-activity"},{"e":"👩🏾‍🦼‍➡️","n":"woman in motorized wheelchair facing right: medium-dark skin tone","s":"person-activity"},{"e":"👩🏿‍🦼‍➡️","n":"woman in motorized wheelchair facing right: dark skin tone","s":"person-activity"},{"e":"🧑‍🦽","n":"person in manual wheelchair","s":"person-activity"},{"e":"🧑🏻‍🦽","n":"person in manual wheelchair: light skin tone","s":"person-activity"},{"e":"🧑🏼‍🦽","n":"person in manual wheelchair: medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🦽","n":"person in manual wheelchair: medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🦽","n":"person in manual wheelchair: medium-dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🦽","n":"person in manual wheelchair: dark skin tone","s":"person-activity"},{"e":"🧑‍🦽‍➡️","n":"person in manual wheelchair facing right","s":"person-activity"},{"e":"🧑🏻‍🦽‍➡️","n":"person in manual wheelchair facing right: light skin tone","s":"person-activity"},{"e":"🧑🏼‍🦽‍➡️","n":"person in manual wheelchair facing right: medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🦽‍➡️","n":"person in manual wheelchair facing right: medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🦽‍➡️","n":"person in manual wheelchair facing right: medium-dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🦽‍➡️","n":"person in manual wheelchair facing right: dark skin tone","s":"person-activity"},{"e":"👨‍🦽","n":"man in manual wheelchair","s":"person-activity"},{"e":"👨🏻‍🦽","n":"man in manual wheelchair: light skin tone","s":"person-activity"},{"e":"👨🏼‍🦽","n":"man in manual wheelchair: medium-light skin tone","s":"person-activity"},{"e":"👨🏽‍🦽","n":"man in manual wheelchair: medium skin tone","s":"person-activity"},{"e":"👨🏾‍🦽","n":"man in manual wheelchair: medium-dark skin tone","s":"person-activity"},{"e":"👨🏿‍🦽","n":"man in manual wheelchair: dark skin tone","s":"person-activity"},{"e":"👨‍🦽‍➡️","n":"man in manual wheelchair facing right","s":"person-activity"},{"e":"👨🏻‍🦽‍➡️","n":"man in manual wheelchair facing right: light skin tone","s":"person-activity"},{"e":"👨🏼‍🦽‍➡️","n":"man in manual wheelchair facing right: medium-light skin tone","s":"person-activity"},{"e":"👨🏽‍🦽‍➡️","n":"man in manual wheelchair facing right: medium skin tone","s":"person-activity"},{"e":"👨🏾‍🦽‍➡️","n":"man in manual wheelchair facing right: medium-dark skin tone","s":"person-activity"},{"e":"👨🏿‍🦽‍➡️","n":"man in manual wheelchair facing right: dark skin tone","s":"person-activity"},{"e":"👩‍🦽","n":"woman in manual wheelchair","s":"person-activity"},{"e":"👩🏻‍🦽","n":"woman in manual wheelchair: light skin tone","s":"person-activity"},{"e":"👩🏼‍🦽","n":"woman in manual wheelchair: medium-light skin tone","s":"person-activity"},{"e":"👩🏽‍🦽","n":"woman in manual wheelchair: medium skin tone","s":"person-activity"},{"e":"👩🏾‍🦽","n":"woman in manual wheelchair: medium-dark skin tone","s":"person-activity"},{"e":"👩🏿‍🦽","n":"woman in manual wheelchair: dark skin tone","s":"person-activity"},{"e":"👩‍🦽‍➡️","n":"woman in manual wheelchair facing right","s":"person-activity"},{"e":"👩🏻‍🦽‍➡️","n":"woman in manual wheelchair facing right: light skin tone","s":"person-activity"},{"e":"👩🏼‍🦽‍➡️","n":"woman in manual wheelchair facing right: medium-light skin tone","s":"person-activity"},{"e":"👩🏽‍🦽‍➡️","n":"woman in manual wheelchair facing right: medium skin tone","s":"person-activity"},{"e":"👩🏾‍🦽‍➡️","n":"woman in manual wheelchair facing right: medium-dark skin tone","s":"person-activity"},{"e":"👩🏿‍🦽‍➡️","n":"woman in manual wheelchair facing right: dark skin tone","s":"person-activity"},{"e":"🏃","n":"person running","s":"person-activity"},{"e":"🏃🏻","n":"person running: light skin tone","s":"person-activity"},{"e":"🏃🏼","n":"person running: medium-light skin tone","s":"person-activity"},{"e":"🏃🏽","n":"person running: medium skin tone","s":"person-activity"},{"e":"🏃🏾","n":"person running: medium-dark skin tone","s":"person-activity"},{"e":"🏃🏿","n":"person running: dark skin tone","s":"person-activity"},{"e":"🏃‍♂️","n":"man running","s":"person-activity"},{"e":"🏃🏻‍♂️","n":"man running: light skin tone","s":"person-activity"},{"e":"🏃🏼‍♂️","n":"man running: medium-light skin tone","s":"person-activity"},{"e":"🏃🏽‍♂️","n":"man running: medium skin tone","s":"person-activity"},{"e":"🏃🏾‍♂️","n":"man running: medium-dark skin tone","s":"person-activity"},{"e":"🏃🏿‍♂️","n":"man running: dark skin tone","s":"person-activity"},{"e":"🏃‍♀️","n":"woman running","s":"person-activity"},{"e":"🏃🏻‍♀️","n":"woman running: light skin tone","s":"person-activity"},{"e":"🏃🏼‍♀️","n":"woman running: medium-light skin tone","s":"person-activity"},{"e":"🏃🏽‍♀️","n":"woman running: medium skin tone","s":"person-activity"},{"e":"🏃🏾‍♀️","n":"woman running: medium-dark skin tone","s":"person-activity"},{"e":"🏃🏿‍♀️","n":"woman running: dark skin tone","s":"person-activity"},{"e":"🏃‍➡️","n":"person running facing right","s":"person-activity"},{"e":"🏃🏻‍➡️","n":"person running facing right: light skin tone","s":"person-activity"},{"e":"🏃🏼‍➡️","n":"person running facing right: medium-light skin tone","s":"person-activity"},{"e":"🏃🏽‍➡️","n":"person running facing right: medium skin tone","s":"person-activity"},{"e":"🏃🏾‍➡️","n":"person running facing right: medium-dark skin tone","s":"person-activity"},{"e":"🏃🏿‍➡️","n":"person running facing right: dark skin tone","s":"person-activity"},{"e":"🏃‍♀️‍➡️","n":"woman running facing right","s":"person-activity"},{"e":"🏃🏻‍♀️‍➡️","n":"woman running facing right: light skin tone","s":"person-activity"},{"e":"🏃🏼‍♀️‍➡️","n":"woman running facing right: medium-light skin tone","s":"person-activity"},{"e":"🏃🏽‍♀️‍➡️","n":"woman running facing right: medium skin tone","s":"person-activity"},{"e":"🏃🏾‍♀️‍➡️","n":"woman running facing right: medium-dark skin tone","s":"person-activity"},{"e":"🏃🏿‍♀️‍➡️","n":"woman running facing right: dark skin tone","s":"person-activity"},{"e":"🏃‍♂️‍➡️","n":"man running facing right","s":"person-activity"},{"e":"🏃🏻‍♂️‍➡️","n":"man running facing right: light skin tone","s":"person-activity"},{"e":"🏃🏼‍♂️‍➡️","n":"man running facing right: medium-light skin tone","s":"person-activity"},{"e":"🏃🏽‍♂️‍➡️","n":"man running facing right: medium skin tone","s":"person-activity"},{"e":"🏃🏾‍♂️‍➡️","n":"man running facing right: medium-dark skin tone","s":"person-activity"},{"e":"🏃🏿‍♂️‍➡️","n":"man running facing right: dark skin tone","s":"person-activity"},{"e":"🧑‍🩰","n":"ballet dancer","s":"person-activity"},{"e":"🧑🏻‍🩰","n":"ballet dancer: light skin tone","s":"person-activity"},{"e":"🧑🏼‍🩰","n":"ballet dancer: medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🩰","n":"ballet dancer: medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🩰","n":"ballet dancer: medium-dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🩰","n":"ballet dancer: dark skin tone","s":"person-activity"},{"e":"💃","n":"woman dancing","s":"person-activity"},{"e":"💃🏻","n":"woman dancing: light skin tone","s":"person-activity"},{"e":"💃🏼","n":"woman dancing: medium-light skin tone","s":"person-activity"},{"e":"💃🏽","n":"woman dancing: medium skin tone","s":"person-activity"},{"e":"💃🏾","n":"woman dancing: medium-dark skin tone","s":"person-activity"},{"e":"💃🏿","n":"woman dancing: dark skin tone","s":"person-activity"},{"e":"🕺","n":"man dancing","s":"person-activity"},{"e":"🕺🏻","n":"man dancing: light skin tone","s":"person-activity"},{"e":"🕺🏼","n":"man dancing: medium-light skin tone","s":"person-activity"},{"e":"🕺🏽","n":"man dancing: medium skin tone","s":"person-activity"},{"e":"🕺🏾","n":"man dancing: medium-dark skin tone","s":"person-activity"},{"e":"🕺🏿","n":"man dancing: dark skin tone","s":"person-activity"},{"e":"🕴️","n":"person in suit levitating","s":"person-activity"},{"e":"🕴🏻","n":"person in suit levitating: light skin tone","s":"person-activity"},{"e":"🕴🏼","n":"person in suit levitating: medium-light skin tone","s":"person-activity"},{"e":"🕴🏽","n":"person in suit levitating: medium skin tone","s":"person-activity"},{"e":"🕴🏾","n":"person in suit levitating: medium-dark skin tone","s":"person-activity"},{"e":"🕴🏿","n":"person in suit levitating: dark skin tone","s":"person-activity"},{"e":"👯","n":"people with bunny ears","s":"person-activity"},{"e":"👯🏻","n":"people with bunny ears: light skin tone","s":"person-activity"},{"e":"👯🏼","n":"people with bunny ears: medium-light skin tone","s":"person-activity"},{"e":"👯🏽","n":"people with bunny ears: medium skin tone","s":"person-activity"},{"e":"👯🏾","n":"people with bunny ears: medium-dark skin tone","s":"person-activity"},{"e":"👯🏿","n":"people with bunny ears: dark skin tone","s":"person-activity"},{"e":"👯‍♂️","n":"men with bunny ears","s":"person-activity"},{"e":"👯🏻‍♂️","n":"men with bunny ears: light skin tone","s":"person-activity"},{"e":"👯🏼‍♂️","n":"men with bunny ears: medium-light skin tone","s":"person-activity"},{"e":"👯🏽‍♂️","n":"men with bunny ears: medium skin tone","s":"person-activity"},{"e":"👯🏾‍♂️","n":"men with bunny ears: medium-dark skin tone","s":"person-activity"},{"e":"👯🏿‍♂️","n":"men with bunny ears: dark skin tone","s":"person-activity"},{"e":"👯‍♀️","n":"women with bunny ears","s":"person-activity"},{"e":"👯🏻‍♀️","n":"women with bunny ears: light skin tone","s":"person-activity"},{"e":"👯🏼‍♀️","n":"women with bunny ears: medium-light skin tone","s":"person-activity"},{"e":"👯🏽‍♀️","n":"women with bunny ears: medium skin tone","s":"person-activity"},{"e":"👯🏾‍♀️","n":"women with bunny ears: medium-dark skin tone","s":"person-activity"},{"e":"👯🏿‍♀️","n":"women with bunny ears: dark skin tone","s":"person-activity"},{"e":"🧑🏻‍🐰‍🧑🏼","n":"people with bunny ears: light skin tone, medium-light skin tone","s":"person-activity"},{"e":"🧑🏻‍🐰‍🧑🏽","n":"people with bunny ears: light skin tone, medium skin tone","s":"person-activity"},{"e":"🧑🏻‍🐰‍🧑🏾","n":"people with bunny ears: light skin tone, medium-dark skin tone","s":"person-activity"},{"e":"🧑🏻‍🐰‍🧑🏿","n":"people with bunny ears: light skin tone, dark skin tone","s":"person-activity"},{"e":"🧑🏼‍🐰‍🧑🏻","n":"people with bunny ears: medium-light skin tone, light skin tone","s":"person-activity"},{"e":"🧑🏼‍🐰‍🧑🏽","n":"people with bunny ears: medium-light skin tone, medium skin tone","s":"person-activity"},{"e":"🧑🏼‍🐰‍🧑🏾","n":"people with bunny ears: medium-light skin tone, medium-dark skin tone","s":"person-activity"},{"e":"🧑🏼‍🐰‍🧑🏿","n":"people with bunny ears: medium-light skin tone, dark skin tone","s":"person-activity"},{"e":"🧑🏽‍🐰‍🧑🏻","n":"people with bunny ears: medium skin tone, light skin tone","s":"person-activity"},{"e":"🧑🏽‍🐰‍🧑🏼","n":"people with bunny ears: medium skin tone, medium-light skin tone","s":"person-activity"},{"e":"🧑🏽‍🐰‍🧑🏾","n":"people with bunny ears: medium skin tone, medium-dark skin tone","s":"person-activity"},{"e":"🧑🏽‍🐰‍🧑🏿","n":"people with bunny ears: medium skin tone, dark skin tone","s":"person-activity"},{"e":"🧑🏾‍🐰‍🧑🏻","n":"people with bunny ears: medium-dark skin tone, light skin tone","s":"person-activity"},{"e":"🧑🏾‍🐰‍🧑🏼","n":"people with bunny ears: medium-dark skin tone, medium-light skin tone","s":"person-activity"},{"e":"🧑🏾‍🐰‍🧑🏽","n":"people with bunny ears: medium-dark skin tone, medium skin tone","s":"person-activity"},{"e":"🧑🏾‍🐰‍🧑🏿","n":"people with bunny ears: medium-dark skin tone, dark skin tone","s":"person-activity"},{"e":"🧑🏿‍🐰‍🧑🏻","n":"people with bunny ears: dark skin tone, light skin tone","s":"person-activity"},{"e":"🧑🏿‍🐰‍🧑🏼","n":"people with bunny ears: dark skin tone, medium-light skin tone","s":"person-activity"},{"e":"🧑🏿‍🐰‍🧑🏽","n":"people with bunny ears: dark skin tone, medium skin tone","s":"person-activity"},{"e":"🧑🏿‍🐰‍🧑🏾","n":"people with bunny ears: dark skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👨🏻‍🐰‍👨🏼","n":"men with bunny ears: light skin tone, medium-light skin tone","s":"person-activity"},{"e":"👨🏻‍🐰‍👨🏽","n":"men with bunny ears: light skin tone, medium skin tone","s":"person-activity"},{"e":"👨🏻‍🐰‍👨🏾","n":"men with bunny ears: light skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👨🏻‍🐰‍👨🏿","n":"men with bunny ears: light skin tone, dark skin tone","s":"person-activity"},{"e":"👨🏼‍🐰‍👨🏻","n":"men with bunny ears: medium-light skin tone, light skin tone","s":"person-activity"},{"e":"👨🏼‍🐰‍👨🏽","n":"men with bunny ears: medium-light skin tone, medium skin tone","s":"person-activity"},{"e":"👨🏼‍🐰‍👨🏾","n":"men with bunny ears: medium-light skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👨🏼‍🐰‍👨🏿","n":"men with bunny ears: medium-light skin tone, dark skin tone","s":"person-activity"},{"e":"👨🏽‍🐰‍👨🏻","n":"men with bunny ears: medium skin tone, light skin tone","s":"person-activity"},{"e":"👨🏽‍🐰‍👨🏼","n":"men with bunny ears: medium skin tone, medium-light skin tone","s":"person-activity"},{"e":"👨🏽‍🐰‍👨🏾","n":"men with bunny ears: medium skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👨🏽‍🐰‍👨🏿","n":"men with bunny ears: medium skin tone, dark skin tone","s":"person-activity"},{"e":"👨🏾‍🐰‍👨🏻","n":"men with bunny ears: medium-dark skin tone, light skin tone","s":"person-activity"},{"e":"👨🏾‍🐰‍👨🏼","n":"men with bunny ears: medium-dark skin tone, medium-light skin tone","s":"person-activity"},{"e":"👨🏾‍🐰‍👨🏽","n":"men with bunny ears: medium-dark skin tone, medium skin tone","s":"person-activity"},{"e":"👨🏾‍🐰‍👨🏿","n":"men with bunny ears: medium-dark skin tone, dark skin tone","s":"person-activity"},{"e":"👨🏿‍🐰‍👨🏻","n":"men with bunny ears: dark skin tone, light skin tone","s":"person-activity"},{"e":"👨🏿‍🐰‍👨🏼","n":"men with bunny ears: dark skin tone, medium-light skin tone","s":"person-activity"},{"e":"👨🏿‍🐰‍👨🏽","n":"men with bunny ears: dark skin tone, medium skin tone","s":"person-activity"},{"e":"👨🏿‍🐰‍👨🏾","n":"men with bunny ears: dark skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👩🏻‍🐰‍👩🏼","n":"women with bunny ears: light skin tone, medium-light skin tone","s":"person-activity"},{"e":"👩🏻‍🐰‍👩🏽","n":"women with bunny ears: light skin tone, medium skin tone","s":"person-activity"},{"e":"👩🏻‍🐰‍👩🏾","n":"women with bunny ears: light skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👩🏻‍🐰‍👩🏿","n":"women with bunny ears: light skin tone, dark skin tone","s":"person-activity"},{"e":"👩🏼‍🐰‍👩🏻","n":"women with bunny ears: medium-light skin tone, light skin tone","s":"person-activity"},{"e":"👩🏼‍🐰‍👩🏽","n":"women with bunny ears: medium-light skin tone, medium skin tone","s":"person-activity"},{"e":"👩🏼‍🐰‍👩🏾","n":"women with bunny ears: medium-light skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👩🏼‍🐰‍👩🏿","n":"women with bunny ears: medium-light skin tone, dark skin tone","s":"person-activity"},{"e":"👩🏽‍🐰‍👩🏻","n":"women with bunny ears: medium skin tone, light skin tone","s":"person-activity"},{"e":"👩🏽‍🐰‍👩🏼","n":"women with bunny ears: medium skin tone, medium-light skin tone","s":"person-activity"},{"e":"👩🏽‍🐰‍👩🏾","n":"women with bunny ears: medium skin tone, medium-dark skin tone","s":"person-activity"},{"e":"👩🏽‍🐰‍👩🏿","n":"women with bunny ears: medium skin tone, dark skin tone","s":"person-activity"},{"e":"👩🏾‍🐰‍👩🏻","n":"women with bunny ears: medium-dark skin tone, light skin tone","s":"person-activity"},{"e":"👩🏾‍🐰‍👩🏼","n":"women with bunny ears: medium-dark skin tone, medium-light skin tone","s":"person-activity"},{"e":"👩🏾‍🐰‍👩🏽","n":"women with bunny ears: medium-dark skin tone, medium skin tone","s":"person-activity"},{"e":"👩🏾‍🐰‍👩🏿","n":"women with bunny ears: medium-dark skin tone, dark skin tone","s":"person-activity"},{"e":"👩🏿‍🐰‍👩🏻","n":"women with bunny ears: dark skin tone, light skin tone","s":"person-activity"},{"e":"👩🏿‍🐰‍👩🏼","n":"women with bunny ears: dark skin tone, medium-light skin tone","s":"person-activity"},{"e":"👩🏿‍🐰‍👩🏽","n":"women with bunny ears: dark skin tone, medium skin tone","s":"person-activity"},{"e":"👩🏿‍🐰‍👩🏾","n":"women with bunny ears: dark skin tone, medium-dark skin tone","s":"person-activity"},{"e":"🧖","n":"person in steamy room","s":"person-activity"},{"e":"🧖🏻","n":"person in steamy room: light skin tone","s":"person-activity"},{"e":"🧖🏼","n":"person in steamy room: medium-light skin tone","s":"person-activity"},{"e":"🧖🏽","n":"person in steamy room: medium skin tone","s":"person-activity"},{"e":"🧖🏾","n":"person in steamy room: medium-dark skin tone","s":"person-activity"},{"e":"🧖🏿","n":"person in steamy room: dark skin tone","s":"person-activity"},{"e":"🧖‍♂️","n":"man in steamy room","s":"person-activity"},{"e":"🧖🏻‍♂️","n":"man in steamy room: light skin tone","s":"person-activity"},{"e":"🧖🏼‍♂️","n":"man in steamy room: medium-light skin tone","s":"person-activity"},{"e":"🧖🏽‍♂️","n":"man in steamy room: medium skin tone","s":"person-activity"},{"e":"🧖🏾‍♂️","n":"man in steamy room: medium-dark skin tone","s":"person-activity"},{"e":"🧖🏿‍♂️","n":"man in steamy room: dark skin tone","s":"person-activity"},{"e":"🧖‍♀️","n":"woman in steamy room","s":"person-activity"},{"e":"🧖🏻‍♀️","n":"woman in steamy room: light skin tone","s":"person-activity"},{"e":"🧖🏼‍♀️","n":"woman in steamy room: medium-light skin tone","s":"person-activity"},{"e":"🧖🏽‍♀️","n":"woman in steamy room: medium skin tone","s":"person-activity"},{"e":"🧖🏾‍♀️","n":"woman in steamy room: medium-dark skin tone","s":"person-activity"},{"e":"🧖🏿‍♀️","n":"woman in steamy room: dark skin tone","s":"person-activity"},{"e":"🧗","n":"person climbing","s":"person-activity"},{"e":"🧗🏻","n":"person climbing: light skin tone","s":"person-activity"},{"e":"🧗🏼","n":"person climbing: medium-light skin tone","s":"person-activity"},{"e":"🧗🏽","n":"person climbing: medium skin tone","s":"person-activity"},{"e":"🧗🏾","n":"person climbing: medium-dark skin tone","s":"person-activity"},{"e":"🧗🏿","n":"person climbing: dark skin tone","s":"person-activity"},{"e":"🧗‍♂️","n":"man climbing","s":"person-activity"},{"e":"🧗🏻‍♂️","n":"man climbing: light skin tone","s":"person-activity"},{"e":"🧗🏼‍♂️","n":"man climbing: medium-light skin tone","s":"person-activity"},{"e":"🧗🏽‍♂️","n":"man climbing: medium skin tone","s":"person-activity"},{"e":"🧗🏾‍♂️","n":"man climbing: medium-dark skin tone","s":"person-activity"},{"e":"🧗🏿‍♂️","n":"man climbing: dark skin tone","s":"person-activity"},{"e":"🧗‍♀️","n":"woman climbing","s":"person-activity"},{"e":"🧗🏻‍♀️","n":"woman climbing: light skin tone","s":"person-activity"},{"e":"🧗🏼‍♀️","n":"woman climbing: medium-light skin tone","s":"person-activity"},{"e":"🧗🏽‍♀️","n":"woman climbing: medium skin tone","s":"person-activity"},{"e":"🧗🏾‍♀️","n":"woman climbing: medium-dark skin tone","s":"person-activity"},{"e":"🧗🏿‍♀️","n":"woman climbing: dark skin tone","s":"person-activity"},{"e":"🤺","n":"person fencing","s":"person-sport"},{"e":"🏇","n":"horse racing","s":"person-sport"},{"e":"🏇🏻","n":"horse racing: light skin tone","s":"person-sport"},{"e":"🏇🏼","n":"horse racing: medium-light skin tone","s":"person-sport"},{"e":"🏇🏽","n":"horse racing: medium skin tone","s":"person-sport"},{"e":"🏇🏾","n":"horse racing: medium-dark skin tone","s":"person-sport"},{"e":"🏇🏿","n":"horse racing: dark skin tone","s":"person-sport"},{"e":"⛷️","n":"skier","s":"person-sport"},{"e":"🏂","n":"snowboarder","s":"person-sport"},{"e":"🏂🏻","n":"snowboarder: light skin tone","s":"person-sport"},{"e":"🏂🏼","n":"snowboarder: medium-light skin tone","s":"person-sport"},{"e":"🏂🏽","n":"snowboarder: medium skin tone","s":"person-sport"},{"e":"🏂🏾","n":"snowboarder: medium-dark skin tone","s":"person-sport"},{"e":"🏂🏿","n":"snowboarder: dark skin tone","s":"person-sport"},{"e":"🏌️","n":"person golfing","s":"person-sport"},{"e":"🏌🏻","n":"person golfing: light skin tone","s":"person-sport"},{"e":"🏌🏼","n":"person golfing: medium-light skin tone","s":"person-sport"},{"e":"🏌🏽","n":"person golfing: medium skin tone","s":"person-sport"},{"e":"🏌🏾","n":"person golfing: medium-dark skin tone","s":"person-sport"},{"e":"🏌🏿","n":"person golfing: dark skin tone","s":"person-sport"},{"e":"🏌️‍♂️","n":"man golfing","s":"person-sport"},{"e":"🏌🏻‍♂️","n":"man golfing: light skin tone","s":"person-sport"},{"e":"🏌🏼‍♂️","n":"man golfing: medium-light skin tone","s":"person-sport"},{"e":"🏌🏽‍♂️","n":"man golfing: medium skin tone","s":"person-sport"},{"e":"🏌🏾‍♂️","n":"man golfing: medium-dark skin tone","s":"person-sport"},{"e":"🏌🏿‍♂️","n":"man golfing: dark skin tone","s":"person-sport"},{"e":"🏌️‍♀️","n":"woman golfing","s":"person-sport"},{"e":"🏌🏻‍♀️","n":"woman golfing: light skin tone","s":"person-sport"},{"e":"🏌🏼‍♀️","n":"woman golfing: medium-light skin tone","s":"person-sport"},{"e":"🏌🏽‍♀️","n":"woman golfing: medium skin tone","s":"person-sport"},{"e":"🏌🏾‍♀️","n":"woman golfing: medium-dark skin tone","s":"person-sport"},{"e":"🏌🏿‍♀️","n":"woman golfing: dark skin tone","s":"person-sport"},{"e":"🏄","n":"person surfing","s":"person-sport"},{"e":"🏄🏻","n":"person surfing: light skin tone","s":"person-sport"},{"e":"🏄🏼","n":"person surfing: medium-light skin tone","s":"person-sport"},{"e":"🏄🏽","n":"person surfing: medium skin tone","s":"person-sport"},{"e":"🏄🏾","n":"person surfing: medium-dark skin tone","s":"person-sport"},{"e":"🏄🏿","n":"person surfing: dark skin tone","s":"person-sport"},{"e":"🏄‍♂️","n":"man surfing","s":"person-sport"},{"e":"🏄🏻‍♂️","n":"man surfing: light skin tone","s":"person-sport"},{"e":"🏄🏼‍♂️","n":"man surfing: medium-light skin tone","s":"person-sport"},{"e":"🏄🏽‍♂️","n":"man surfing: medium skin tone","s":"person-sport"},{"e":"🏄🏾‍♂️","n":"man surfing: medium-dark skin tone","s":"person-sport"},{"e":"🏄🏿‍♂️","n":"man surfing: dark skin tone","s":"person-sport"},{"e":"🏄‍♀️","n":"woman surfing","s":"person-sport"},{"e":"🏄🏻‍♀️","n":"woman surfing: light skin tone","s":"person-sport"},{"e":"🏄🏼‍♀️","n":"woman surfing: medium-light skin tone","s":"person-sport"},{"e":"🏄🏽‍♀️","n":"woman surfing: medium skin tone","s":"person-sport"},{"e":"🏄🏾‍♀️","n":"woman surfing: medium-dark skin tone","s":"person-sport"},{"e":"🏄🏿‍♀️","n":"woman surfing: dark skin tone","s":"person-sport"},{"e":"🚣","n":"person rowing boat","s":"person-sport"},{"e":"🚣🏻","n":"person rowing boat: light skin tone","s":"person-sport"},{"e":"🚣🏼","n":"person rowing boat: medium-light skin tone","s":"person-sport"},{"e":"🚣🏽","n":"person rowing boat: medium skin tone","s":"person-sport"},{"e":"🚣🏾","n":"person rowing boat: medium-dark skin tone","s":"person-sport"},{"e":"🚣🏿","n":"person rowing boat: dark skin tone","s":"person-sport"},{"e":"🚣‍♂️","n":"man rowing boat","s":"person-sport"},{"e":"🚣🏻‍♂️","n":"man rowing boat: light skin tone","s":"person-sport"},{"e":"🚣🏼‍♂️","n":"man rowing boat: medium-light skin tone","s":"person-sport"},{"e":"🚣🏽‍♂️","n":"man rowing boat: medium skin tone","s":"person-sport"},{"e":"🚣🏾‍♂️","n":"man rowing boat: medium-dark skin tone","s":"person-sport"},{"e":"🚣🏿‍♂️","n":"man rowing boat: dark skin tone","s":"person-sport"},{"e":"🚣‍♀️","n":"woman rowing boat","s":"person-sport"},{"e":"🚣🏻‍♀️","n":"woman rowing boat: light skin tone","s":"person-sport"},{"e":"🚣🏼‍♀️","n":"woman rowing boat: medium-light skin tone","s":"person-sport"},{"e":"🚣🏽‍♀️","n":"woman rowing boat: medium skin tone","s":"person-sport"},{"e":"🚣🏾‍♀️","n":"woman rowing boat: medium-dark skin tone","s":"person-sport"},{"e":"🚣🏿‍♀️","n":"woman rowing boat: dark skin tone","s":"person-sport"},{"e":"🏊","n":"person swimming","s":"person-sport"},{"e":"🏊🏻","n":"person swimming: light skin tone","s":"person-sport"},{"e":"🏊🏼","n":"person swimming: medium-light skin tone","s":"person-sport"},{"e":"🏊🏽","n":"person swimming: medium skin tone","s":"person-sport"},{"e":"🏊🏾","n":"person swimming: medium-dark skin tone","s":"person-sport"},{"e":"🏊🏿","n":"person swimming: dark skin tone","s":"person-sport"},{"e":"🏊‍♂️","n":"man swimming","s":"person-sport"},{"e":"🏊🏻‍♂️","n":"man swimming: light skin tone","s":"person-sport"},{"e":"🏊🏼‍♂️","n":"man swimming: medium-light skin tone","s":"person-sport"},{"e":"🏊🏽‍♂️","n":"man swimming: medium skin tone","s":"person-sport"},{"e":"🏊🏾‍♂️","n":"man swimming: medium-dark skin tone","s":"person-sport"},{"e":"🏊🏿‍♂️","n":"man swimming: dark skin tone","s":"person-sport"},{"e":"🏊‍♀️","n":"woman swimming","s":"person-sport"},{"e":"🏊🏻‍♀️","n":"woman swimming: light skin tone","s":"person-sport"},{"e":"🏊🏼‍♀️","n":"woman swimming: medium-light skin tone","s":"person-sport"},{"e":"🏊🏽‍♀️","n":"woman swimming: medium skin tone","s":"person-sport"},{"e":"🏊🏾‍♀️","n":"woman swimming: medium-dark skin tone","s":"person-sport"},{"e":"🏊🏿‍♀️","n":"woman swimming: dark skin tone","s":"person-sport"},{"e":"⛹️","n":"person bouncing ball","s":"person-sport"},{"e":"⛹🏻","n":"person bouncing ball: light skin tone","s":"person-sport"},{"e":"⛹🏼","n":"person bouncing ball: medium-light skin tone","s":"person-sport"},{"e":"⛹🏽","n":"person bouncing ball: medium skin tone","s":"person-sport"},{"e":"⛹🏾","n":"person bouncing ball: medium-dark skin tone","s":"person-sport"},{"e":"⛹🏿","n":"person bouncing ball: dark skin tone","s":"person-sport"},{"e":"⛹️‍♂️","n":"man bouncing ball","s":"person-sport"},{"e":"⛹🏻‍♂️","n":"man bouncing ball: light skin tone","s":"person-sport"},{"e":"⛹🏼‍♂️","n":"man bouncing ball: medium-light skin tone","s":"person-sport"},{"e":"⛹🏽‍♂️","n":"man bouncing ball: medium skin tone","s":"person-sport"},{"e":"⛹🏾‍♂️","n":"man bouncing ball: medium-dark skin tone","s":"person-sport"},{"e":"⛹🏿‍♂️","n":"man bouncing ball: dark skin tone","s":"person-sport"},{"e":"⛹️‍♀️","n":"woman bouncing ball","s":"person-sport"},{"e":"⛹🏻‍♀️","n":"woman bouncing ball: light skin tone","s":"person-sport"},{"e":"⛹🏼‍♀️","n":"woman bouncing ball: medium-light skin tone","s":"person-sport"},{"e":"⛹🏽‍♀️","n":"woman bouncing ball: medium skin tone","s":"person-sport"},{"e":"⛹🏾‍♀️","n":"woman bouncing ball: medium-dark skin tone","s":"person-sport"},{"e":"⛹🏿‍♀️","n":"woman bouncing ball: dark skin tone","s":"person-sport"},{"e":"🏋️","n":"person lifting weights","s":"person-sport"},{"e":"🏋🏻","n":"person lifting weights: light skin tone","s":"person-sport"},{"e":"🏋🏼","n":"person lifting weights: medium-light skin tone","s":"person-sport"},{"e":"🏋🏽","n":"person lifting weights: medium skin tone","s":"person-sport"},{"e":"🏋🏾","n":"person lifting weights: medium-dark skin tone","s":"person-sport"},{"e":"🏋🏿","n":"person lifting weights: dark skin tone","s":"person-sport"},{"e":"🏋️‍♂️","n":"man lifting weights","s":"person-sport"},{"e":"🏋🏻‍♂️","n":"man lifting weights: light skin tone","s":"person-sport"},{"e":"🏋🏼‍♂️","n":"man lifting weights: medium-light skin tone","s":"person-sport"},{"e":"🏋🏽‍♂️","n":"man lifting weights: medium skin tone","s":"person-sport"},{"e":"🏋🏾‍♂️","n":"man lifting weights: medium-dark skin tone","s":"person-sport"},{"e":"🏋🏿‍♂️","n":"man lifting weights: dark skin tone","s":"person-sport"},{"e":"🏋️‍♀️","n":"woman lifting weights","s":"person-sport"},{"e":"🏋🏻‍♀️","n":"woman lifting weights: light skin tone","s":"person-sport"},{"e":"🏋🏼‍♀️","n":"woman lifting weights: medium-light skin tone","s":"person-sport"},{"e":"🏋🏽‍♀️","n":"woman lifting weights: medium skin tone","s":"person-sport"},{"e":"🏋🏾‍♀️","n":"woman lifting weights: medium-dark skin tone","s":"person-sport"},{"e":"🏋🏿‍♀️","n":"woman lifting weights: dark skin tone","s":"person-sport"},{"e":"🚴","n":"person biking","s":"person-sport"},{"e":"🚴🏻","n":"person biking: light skin tone","s":"person-sport"},{"e":"🚴🏼","n":"person biking: medium-light skin tone","s":"person-sport"},{"e":"🚴🏽","n":"person biking: medium skin tone","s":"person-sport"},{"e":"🚴🏾","n":"person biking: medium-dark skin tone","s":"person-sport"},{"e":"🚴🏿","n":"person biking: dark skin tone","s":"person-sport"},{"e":"🚴‍♂️","n":"man biking","s":"person-sport"},{"e":"🚴🏻‍♂️","n":"man biking: light skin tone","s":"person-sport"},{"e":"🚴🏼‍♂️","n":"man biking: medium-light skin tone","s":"person-sport"},{"e":"🚴🏽‍♂️","n":"man biking: medium skin tone","s":"person-sport"},{"e":"🚴🏾‍♂️","n":"man biking: medium-dark skin tone","s":"person-sport"},{"e":"🚴🏿‍♂️","n":"man biking: dark skin tone","s":"person-sport"},{"e":"🚴‍♀️","n":"woman biking","s":"person-sport"},{"e":"🚴🏻‍♀️","n":"woman biking: light skin tone","s":"person-sport"},{"e":"🚴🏼‍♀️","n":"woman biking: medium-light skin tone","s":"person-sport"},{"e":"🚴🏽‍♀️","n":"woman biking: medium skin tone","s":"person-sport"},{"e":"🚴🏾‍♀️","n":"woman biking: medium-dark skin tone","s":"person-sport"},{"e":"🚴🏿‍♀️","n":"woman biking: dark skin tone","s":"person-sport"},{"e":"🚵","n":"person mountain biking","s":"person-sport"},{"e":"🚵🏻","n":"person mountain biking: light skin tone","s":"person-sport"},{"e":"🚵🏼","n":"person mountain biking: medium-light skin tone","s":"person-sport"},{"e":"🚵🏽","n":"person mountain biking: medium skin tone","s":"person-sport"},{"e":"🚵🏾","n":"person mountain biking: medium-dark skin tone","s":"person-sport"},{"e":"🚵🏿","n":"person mountain biking: dark skin tone","s":"person-sport"},{"e":"🚵‍♂️","n":"man mountain biking","s":"person-sport"},{"e":"🚵🏻‍♂️","n":"man mountain biking: light skin tone","s":"person-sport"},{"e":"🚵🏼‍♂️","n":"man mountain biking: medium-light skin tone","s":"person-sport"},{"e":"🚵🏽‍♂️","n":"man mountain biking: medium skin tone","s":"person-sport"},{"e":"🚵🏾‍♂️","n":"man mountain biking: medium-dark skin tone","s":"person-sport"},{"e":"🚵🏿‍♂️","n":"man mountain biking: dark skin tone","s":"person-sport"},{"e":"🚵‍♀️","n":"woman mountain biking","s":"person-sport"},{"e":"🚵🏻‍♀️","n":"woman mountain biking: light skin tone","s":"person-sport"},{"e":"🚵🏼‍♀️","n":"woman mountain biking: medium-light skin tone","s":"person-sport"},{"e":"🚵🏽‍♀️","n":"woman mountain biking: medium skin tone","s":"person-sport"},{"e":"🚵🏾‍♀️","n":"woman mountain biking: medium-dark skin tone","s":"person-sport"},{"e":"🚵🏿‍♀️","n":"woman mountain biking: dark skin tone","s":"person-sport"},{"e":"🤸","n":"person cartwheeling","s":"person-sport"},{"e":"🤸🏻","n":"person cartwheeling: light skin tone","s":"person-sport"},{"e":"🤸🏼","n":"person cartwheeling: medium-light skin tone","s":"person-sport"},{"e":"🤸🏽","n":"person cartwheeling: medium skin tone","s":"person-sport"},{"e":"🤸🏾","n":"person cartwheeling: medium-dark skin tone","s":"person-sport"},{"e":"🤸🏿","n":"person cartwheeling: dark skin tone","s":"person-sport"},{"e":"🤸‍♂️","n":"man cartwheeling","s":"person-sport"},{"e":"🤸🏻‍♂️","n":"man cartwheeling: light skin tone","s":"person-sport"},{"e":"🤸🏼‍♂️","n":"man cartwheeling: medium-light skin tone","s":"person-sport"},{"e":"🤸🏽‍♂️","n":"man cartwheeling: medium skin tone","s":"person-sport"},{"e":"🤸🏾‍♂️","n":"man cartwheeling: medium-dark skin tone","s":"person-sport"},{"e":"🤸🏿‍♂️","n":"man cartwheeling: dark skin tone","s":"person-sport"},{"e":"🤸‍♀️","n":"woman cartwheeling","s":"person-sport"},{"e":"🤸🏻‍♀️","n":"woman cartwheeling: light skin tone","s":"person-sport"},{"e":"🤸🏼‍♀️","n":"woman cartwheeling: medium-light skin tone","s":"person-sport"},{"e":"🤸🏽‍♀️","n":"woman cartwheeling: medium skin tone","s":"person-sport"},{"e":"🤸🏾‍♀️","n":"woman cartwheeling: medium-dark skin tone","s":"person-sport"},{"e":"🤸🏿‍♀️","n":"woman cartwheeling: dark skin tone","s":"person-sport"},{"e":"🤼","n":"people wrestling","s":"person-sport"},{"e":"🤼🏻","n":"people wrestling: light skin tone","s":"person-sport"},{"e":"🤼🏼","n":"people wrestling: medium-light skin tone","s":"person-sport"},{"e":"🤼🏽","n":"people wrestling: medium skin tone","s":"person-sport"},{"e":"🤼🏾","n":"people wrestling: medium-dark skin tone","s":"person-sport"},{"e":"🤼🏿","n":"people wrestling: dark skin tone","s":"person-sport"},{"e":"🤼‍♂️","n":"men wrestling","s":"person-sport"},{"e":"🤼🏻‍♂️","n":"men wrestling: light skin tone","s":"person-sport"},{"e":"🤼🏼‍♂️","n":"men wrestling: medium-light skin tone","s":"person-sport"},{"e":"🤼🏽‍♂️","n":"men wrestling: medium skin tone","s":"person-sport"},{"e":"🤼🏾‍♂️","n":"men wrestling: medium-dark skin tone","s":"person-sport"},{"e":"🤼🏿‍♂️","n":"men wrestling: dark skin tone","s":"person-sport"},{"e":"🤼‍♀️","n":"women wrestling","s":"person-sport"},{"e":"🤼🏻‍♀️","n":"women wrestling: light skin tone","s":"person-sport"},{"e":"🤼🏼‍♀️","n":"women wrestling: medium-light skin tone","s":"person-sport"},{"e":"🤼🏽‍♀️","n":"women wrestling: medium skin tone","s":"person-sport"},{"e":"🤼🏾‍♀️","n":"women wrestling: medium-dark skin tone","s":"person-sport"},{"e":"🤼🏿‍♀️","n":"women wrestling: dark skin tone","s":"person-sport"},{"e":"🧑🏻‍🫯‍🧑🏼","n":"people wrestling: light skin tone, medium-light skin tone","s":"person-sport"},{"e":"🧑🏻‍🫯‍🧑🏽","n":"people wrestling: light skin tone, medium skin tone","s":"person-sport"},{"e":"🧑🏻‍🫯‍🧑🏾","n":"people wrestling: light skin tone, medium-dark skin tone","s":"person-sport"},{"e":"🧑🏻‍🫯‍🧑🏿","n":"people wrestling: light skin tone, dark skin tone","s":"person-sport"},{"e":"🧑🏼‍🫯‍🧑🏻","n":"people wrestling: medium-light skin tone, light skin tone","s":"person-sport"},{"e":"🧑🏼‍🫯‍🧑🏽","n":"people wrestling: medium-light skin tone, medium skin tone","s":"person-sport"},{"e":"🧑🏼‍🫯‍🧑🏾","n":"people wrestling: medium-light skin tone, medium-dark skin tone","s":"person-sport"},{"e":"🧑🏼‍🫯‍🧑🏿","n":"people wrestling: medium-light skin tone, dark skin tone","s":"person-sport"},{"e":"🧑🏽‍🫯‍🧑🏻","n":"people wrestling: medium skin tone, light skin tone","s":"person-sport"},{"e":"🧑🏽‍🫯‍🧑🏼","n":"people wrestling: medium skin tone, medium-light skin tone","s":"person-sport"},{"e":"🧑🏽‍🫯‍🧑🏾","n":"people wrestling: medium skin tone, medium-dark skin tone","s":"person-sport"},{"e":"🧑🏽‍🫯‍🧑🏿","n":"people wrestling: medium skin tone, dark skin tone","s":"person-sport"},{"e":"🧑🏾‍🫯‍🧑🏻","n":"people wrestling: medium-dark skin tone, light skin tone","s":"person-sport"},{"e":"🧑🏾‍🫯‍🧑🏼","n":"people wrestling: medium-dark skin tone, medium-light skin tone","s":"person-sport"},{"e":"🧑🏾‍🫯‍🧑🏽","n":"people wrestling: medium-dark skin tone, medium skin tone","s":"person-sport"},{"e":"🧑🏾‍🫯‍🧑🏿","n":"people wrestling: medium-dark skin tone, dark skin tone","s":"person-sport"},{"e":"🧑🏿‍🫯‍🧑🏻","n":"people wrestling: dark skin tone, light skin tone","s":"person-sport"},{"e":"🧑🏿‍🫯‍🧑🏼","n":"people wrestling: dark skin tone, medium-light skin tone","s":"person-sport"},{"e":"🧑🏿‍🫯‍🧑🏽","n":"people wrestling: dark skin tone, medium skin tone","s":"person-sport"},{"e":"🧑🏿‍🫯‍🧑🏾","n":"people wrestling: dark skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👨🏻‍🫯‍👨🏼","n":"men wrestling: light skin tone, medium-light skin tone","s":"person-sport"},{"e":"👨🏻‍🫯‍👨🏽","n":"men wrestling: light skin tone, medium skin tone","s":"person-sport"},{"e":"👨🏻‍🫯‍👨🏾","n":"men wrestling: light skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👨🏻‍🫯‍👨🏿","n":"men wrestling: light skin tone, dark skin tone","s":"person-sport"},{"e":"👨🏼‍🫯‍👨🏻","n":"men wrestling: medium-light skin tone, light skin tone","s":"person-sport"},{"e":"👨🏼‍🫯‍👨🏽","n":"men wrestling: medium-light skin tone, medium skin tone","s":"person-sport"},{"e":"👨🏼‍🫯‍👨🏾","n":"men wrestling: medium-light skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👨🏼‍🫯‍👨🏿","n":"men wrestling: medium-light skin tone, dark skin tone","s":"person-sport"},{"e":"👨🏽‍🫯‍👨🏻","n":"men wrestling: medium skin tone, light skin tone","s":"person-sport"},{"e":"👨🏽‍🫯‍👨🏼","n":"men wrestling: medium skin tone, medium-light skin tone","s":"person-sport"},{"e":"👨🏽‍🫯‍👨🏾","n":"men wrestling: medium skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👨🏽‍🫯‍👨🏿","n":"men wrestling: medium skin tone, dark skin tone","s":"person-sport"},{"e":"👨🏾‍🫯‍👨🏻","n":"men wrestling: medium-dark skin tone, light skin tone","s":"person-sport"},{"e":"👨🏾‍🫯‍👨🏼","n":"men wrestling: medium-dark skin tone, medium-light skin tone","s":"person-sport"},{"e":"👨🏾‍🫯‍👨🏽","n":"men wrestling: medium-dark skin tone, medium skin tone","s":"person-sport"},{"e":"👨🏾‍🫯‍👨🏿","n":"men wrestling: medium-dark skin tone, dark skin tone","s":"person-sport"},{"e":"👨🏿‍🫯‍👨🏻","n":"men wrestling: dark skin tone, light skin tone","s":"person-sport"},{"e":"👨🏿‍🫯‍👨🏼","n":"men wrestling: dark skin tone, medium-light skin tone","s":"person-sport"},{"e":"👨🏿‍🫯‍👨🏽","n":"men wrestling: dark skin tone, medium skin tone","s":"person-sport"},{"e":"👨🏿‍🫯‍👨🏾","n":"men wrestling: dark skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👩🏻‍🫯‍👩🏼","n":"women wrestling: light skin tone, medium-light skin tone","s":"person-sport"},{"e":"👩🏻‍🫯‍👩🏽","n":"women wrestling: light skin tone, medium skin tone","s":"person-sport"},{"e":"👩🏻‍🫯‍👩🏾","n":"women wrestling: light skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👩🏻‍🫯‍👩🏿","n":"women wrestling: light skin tone, dark skin tone","s":"person-sport"},{"e":"👩🏼‍🫯‍👩🏻","n":"women wrestling: medium-light skin tone, light skin tone","s":"person-sport"},{"e":"👩🏼‍🫯‍👩🏽","n":"women wrestling: medium-light skin tone, medium skin tone","s":"person-sport"},{"e":"👩🏼‍🫯‍👩🏾","n":"women wrestling: medium-light skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👩🏼‍🫯‍👩🏿","n":"women wrestling: medium-light skin tone, dark skin tone","s":"person-sport"},{"e":"👩🏽‍🫯‍👩🏻","n":"women wrestling: medium skin tone, light skin tone","s":"person-sport"},{"e":"👩🏽‍🫯‍👩🏼","n":"women wrestling: medium skin tone, medium-light skin tone","s":"person-sport"},{"e":"👩🏽‍🫯‍👩🏾","n":"women wrestling: medium skin tone, medium-dark skin tone","s":"person-sport"},{"e":"👩🏽‍🫯‍👩🏿","n":"women wrestling: medium skin tone, dark skin tone","s":"person-sport"},{"e":"👩🏾‍🫯‍👩🏻","n":"women wrestling: medium-dark skin tone, light skin tone","s":"person-sport"},{"e":"👩🏾‍🫯‍👩🏼","n":"women wrestling: medium-dark skin tone, medium-light skin tone","s":"person-sport"},{"e":"👩🏾‍🫯‍👩🏽","n":"women wrestling: medium-dark skin tone, medium skin tone","s":"person-sport"},{"e":"👩🏾‍🫯‍👩🏿","n":"women wrestling: medium-dark skin tone, dark skin tone","s":"person-sport"},{"e":"👩🏿‍🫯‍👩🏻","n":"women wrestling: dark skin tone, light skin tone","s":"person-sport"},{"e":"👩🏿‍🫯‍👩🏼","n":"women wrestling: dark skin tone, medium-light skin tone","s":"person-sport"},{"e":"👩🏿‍🫯‍👩🏽","n":"women wrestling: dark skin tone, medium skin tone","s":"person-sport"},{"e":"👩🏿‍🫯‍👩🏾","n":"women wrestling: dark skin tone, medium-dark skin tone","s":"person-sport"},{"e":"🤽","n":"person playing water polo","s":"person-sport"},{"e":"🤽🏻","n":"person playing water polo: light skin tone","s":"person-sport"},{"e":"🤽🏼","n":"person playing water polo: medium-light skin tone","s":"person-sport"},{"e":"🤽🏽","n":"person playing water polo: medium skin tone","s":"person-sport"},{"e":"🤽🏾","n":"person playing water polo: medium-dark skin tone","s":"person-sport"},{"e":"🤽🏿","n":"person playing water polo: dark skin tone","s":"person-sport"},{"e":"🤽‍♂️","n":"man playing water polo","s":"person-sport"},{"e":"🤽🏻‍♂️","n":"man playing water polo: light skin tone","s":"person-sport"},{"e":"🤽🏼‍♂️","n":"man playing water polo: medium-light skin tone","s":"person-sport"},{"e":"🤽🏽‍♂️","n":"man playing water polo: medium skin tone","s":"person-sport"},{"e":"🤽🏾‍♂️","n":"man playing water polo: medium-dark skin tone","s":"person-sport"},{"e":"🤽🏿‍♂️","n":"man playing water polo: dark skin tone","s":"person-sport"},{"e":"🤽‍♀️","n":"woman playing water polo","s":"person-sport"},{"e":"🤽🏻‍♀️","n":"woman playing water polo: light skin tone","s":"person-sport"},{"e":"🤽🏼‍♀️","n":"woman playing water polo: medium-light skin tone","s":"person-sport"},{"e":"🤽🏽‍♀️","n":"woman playing water polo: medium skin tone","s":"person-sport"},{"e":"🤽🏾‍♀️","n":"woman playing water polo: medium-dark skin tone","s":"person-sport"},{"e":"🤽🏿‍♀️","n":"woman playing water polo: dark skin tone","s":"person-sport"},{"e":"🤾","n":"person playing handball","s":"person-sport"},{"e":"🤾🏻","n":"person playing handball: light skin tone","s":"person-sport"},{"e":"🤾🏼","n":"person playing handball: medium-light skin tone","s":"person-sport"},{"e":"🤾🏽","n":"person playing handball: medium skin tone","s":"person-sport"},{"e":"🤾🏾","n":"person playing handball: medium-dark skin tone","s":"person-sport"},{"e":"🤾🏿","n":"person playing handball: dark skin tone","s":"person-sport"},{"e":"🤾‍♂️","n":"man playing handball","s":"person-sport"},{"e":"🤾🏻‍♂️","n":"man playing handball: light skin tone","s":"person-sport"},{"e":"🤾🏼‍♂️","n":"man playing handball: medium-light skin tone","s":"person-sport"},{"e":"🤾🏽‍♂️","n":"man playing handball: medium skin tone","s":"person-sport"},{"e":"🤾🏾‍♂️","n":"man playing handball: medium-dark skin tone","s":"person-sport"},{"e":"🤾🏿‍♂️","n":"man playing handball: dark skin tone","s":"person-sport"},{"e":"🤾‍♀️","n":"woman playing handball","s":"person-sport"},{"e":"🤾🏻‍♀️","n":"woman playing handball: light skin tone","s":"person-sport"},{"e":"🤾🏼‍♀️","n":"woman playing handball: medium-light skin tone","s":"person-sport"},{"e":"🤾🏽‍♀️","n":"woman playing handball: medium skin tone","s":"person-sport"},{"e":"🤾🏾‍♀️","n":"woman playing handball: medium-dark skin tone","s":"person-sport"},{"e":"🤾🏿‍♀️","n":"woman playing handball: dark skin tone","s":"person-sport"},{"e":"🤹","n":"person juggling","s":"person-sport"},{"e":"🤹🏻","n":"person juggling: light skin tone","s":"person-sport"},{"e":"🤹🏼","n":"person juggling: medium-light skin tone","s":"person-sport"},{"e":"🤹🏽","n":"person juggling: medium skin tone","s":"person-sport"},{"e":"🤹🏾","n":"person juggling: medium-dark skin tone","s":"person-sport"},{"e":"🤹🏿","n":"person juggling: dark skin tone","s":"person-sport"},{"e":"🤹‍♂️","n":"man juggling","s":"person-sport"},{"e":"🤹🏻‍♂️","n":"man juggling: light skin tone","s":"person-sport"},{"e":"🤹🏼‍♂️","n":"man juggling: medium-light skin tone","s":"person-sport"},{"e":"🤹🏽‍♂️","n":"man juggling: medium skin tone","s":"person-sport"},{"e":"🤹🏾‍♂️","n":"man juggling: medium-dark skin tone","s":"person-sport"},{"e":"🤹🏿‍♂️","n":"man juggling: dark skin tone","s":"person-sport"},{"e":"🤹‍♀️","n":"woman juggling","s":"person-sport"},{"e":"🤹🏻‍♀️","n":"woman juggling: light skin tone","s":"person-sport"},{"e":"🤹🏼‍♀️","n":"woman juggling: medium-light skin tone","s":"person-sport"},{"e":"🤹🏽‍♀️","n":"woman juggling: medium skin tone","s":"person-sport"},{"e":"🤹🏾‍♀️","n":"woman juggling: medium-dark skin tone","s":"person-sport"},{"e":"🤹🏿‍♀️","n":"woman juggling: dark skin tone","s":"person-sport"},{"e":"🧘","n":"person in lotus position","s":"person-resting"},{"e":"🧘🏻","n":"person in lotus position: light skin tone","s":"person-resting"},{"e":"🧘🏼","n":"person in lotus position: medium-light skin tone","s":"person-resting"},{"e":"🧘🏽","n":"person in lotus position: medium skin tone","s":"person-resting"},{"e":"🧘🏾","n":"person in lotus position: medium-dark skin tone","s":"person-resting"},{"e":"🧘🏿","n":"person in lotus position: dark skin tone","s":"person-resting"},{"e":"🧘‍♂️","n":"man in lotus position","s":"person-resting"},{"e":"🧘🏻‍♂️","n":"man in lotus position: light skin tone","s":"person-resting"},{"e":"🧘🏼‍♂️","n":"man in lotus position: medium-light skin tone","s":"person-resting"},{"e":"🧘🏽‍♂️","n":"man in lotus position: medium skin tone","s":"person-resting"},{"e":"🧘🏾‍♂️","n":"man in lotus position: medium-dark skin tone","s":"person-resting"},{"e":"🧘🏿‍♂️","n":"man in lotus position: dark skin tone","s":"person-resting"},{"e":"🧘‍♀️","n":"woman in lotus position","s":"person-resting"},{"e":"🧘🏻‍♀️","n":"woman in lotus position: light skin tone","s":"person-resting"},{"e":"🧘🏼‍♀️","n":"woman in lotus position: medium-light skin tone","s":"person-resting"},{"e":"🧘🏽‍♀️","n":"woman in lotus position: medium skin tone","s":"person-resting"},{"e":"🧘🏾‍♀️","n":"woman in lotus position: medium-dark skin tone","s":"person-resting"},{"e":"🧘🏿‍♀️","n":"woman in lotus position: dark skin tone","s":"person-resting"},{"e":"🛀","n":"person taking bath","s":"person-resting"},{"e":"🛀🏻","n":"person taking bath: light skin tone","s":"person-resting"},{"e":"🛀🏼","n":"person taking bath: medium-light skin tone","s":"person-resting"},{"e":"🛀🏽","n":"person taking bath: medium skin tone","s":"person-resting"},{"e":"🛀🏾","n":"person taking bath: medium-dark skin tone","s":"person-resting"},{"e":"🛀🏿","n":"person taking bath: dark skin tone","s":"person-resting"},{"e":"🛌","n":"person in bed","s":"person-resting"},{"e":"🛌🏻","n":"person in bed: light skin tone","s":"person-resting"},{"e":"🛌🏼","n":"person in bed: medium-light skin tone","s":"person-resting"},{"e":"🛌🏽","n":"person in bed: medium skin tone","s":"person-resting"},{"e":"🛌🏾","n":"person in bed: medium-dark skin tone","s":"person-resting"},{"e":"🛌🏿","n":"person in bed: dark skin tone","s":"person-resting"},{"e":"🧑‍🤝‍🧑","n":"people holding hands","s":"family"},{"e":"🧑🏻‍🤝‍🧑🏻","n":"people holding hands: light skin tone","s":"family"},{"e":"🧑🏻‍🤝‍🧑🏼","n":"people holding hands: light skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏻‍🤝‍🧑🏽","n":"people holding hands: light skin tone, medium skin tone","s":"family"},{"e":"🧑🏻‍🤝‍🧑🏾","n":"people holding hands: light skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏻‍🤝‍🧑🏿","n":"people holding hands: light skin tone, dark skin tone","s":"family"},{"e":"🧑🏼‍🤝‍🧑🏻","n":"people holding hands: medium-light skin tone, light skin tone","s":"family"},{"e":"🧑🏼‍🤝‍🧑🏼","n":"people holding hands: medium-light skin tone","s":"family"},{"e":"🧑🏼‍🤝‍🧑🏽","n":"people holding hands: medium-light skin tone, medium skin tone","s":"family"},{"e":"🧑🏼‍🤝‍🧑🏾","n":"people holding hands: medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏼‍🤝‍🧑🏿","n":"people holding hands: medium-light skin tone, dark skin tone","s":"family"},{"e":"🧑🏽‍🤝‍🧑🏻","n":"people holding hands: medium skin tone, light skin tone","s":"family"},{"e":"🧑🏽‍🤝‍🧑🏼","n":"people holding hands: medium skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏽‍🤝‍🧑🏽","n":"people holding hands: medium skin tone","s":"family"},{"e":"🧑🏽‍🤝‍🧑🏾","n":"people holding hands: medium skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏽‍🤝‍🧑🏿","n":"people holding hands: medium skin tone, dark skin tone","s":"family"},{"e":"🧑🏾‍🤝‍🧑🏻","n":"people holding hands: medium-dark skin tone, light skin tone","s":"family"},{"e":"🧑🏾‍🤝‍🧑🏼","n":"people holding hands: medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏾‍🤝‍🧑🏽","n":"people holding hands: medium-dark skin tone, medium skin tone","s":"family"},{"e":"🧑🏾‍🤝‍🧑🏾","n":"people holding hands: medium-dark skin tone","s":"family"},{"e":"🧑🏾‍🤝‍🧑🏿","n":"people holding hands: medium-dark skin tone, dark skin tone","s":"family"},{"e":"🧑🏿‍🤝‍🧑🏻","n":"people holding hands: dark skin tone, light skin tone","s":"family"},{"e":"🧑🏿‍🤝‍🧑🏼","n":"people holding hands: dark skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏿‍🤝‍🧑🏽","n":"people holding hands: dark skin tone, medium skin tone","s":"family"},{"e":"🧑🏿‍🤝‍🧑🏾","n":"people holding hands: dark skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏿‍🤝‍🧑🏿","n":"people holding hands: dark skin tone","s":"family"},{"e":"👭","n":"women holding hands","s":"family"},{"e":"👭🏻","n":"women holding hands: light skin tone","s":"family"},{"e":"👩🏻‍🤝‍👩🏼","n":"women holding hands: light skin tone, medium-light skin tone","s":"family"},{"e":"👩🏻‍🤝‍👩🏽","n":"women holding hands: light skin tone, medium skin tone","s":"family"},{"e":"👩🏻‍🤝‍👩🏾","n":"women holding hands: light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏻‍🤝‍👩🏿","n":"women holding hands: light skin tone, dark skin tone","s":"family"},{"e":"👩🏼‍🤝‍👩🏻","n":"women holding hands: medium-light skin tone, light skin tone","s":"family"},{"e":"👭🏼","n":"women holding hands: medium-light skin tone","s":"family"},{"e":"👩🏼‍🤝‍👩🏽","n":"women holding hands: medium-light skin tone, medium skin tone","s":"family"},{"e":"👩🏼‍🤝‍👩🏾","n":"women holding hands: medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏼‍🤝‍👩🏿","n":"women holding hands: medium-light skin tone, dark skin tone","s":"family"},{"e":"👩🏽‍🤝‍👩🏻","n":"women holding hands: medium skin tone, light skin tone","s":"family"},{"e":"👩🏽‍🤝‍👩🏼","n":"women holding hands: medium skin tone, medium-light skin tone","s":"family"},{"e":"👭🏽","n":"women holding hands: medium skin tone","s":"family"},{"e":"👩🏽‍🤝‍👩🏾","n":"women holding hands: medium skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏽‍🤝‍👩🏿","n":"women holding hands: medium skin tone, dark skin tone","s":"family"},{"e":"👩🏾‍🤝‍👩🏻","n":"women holding hands: medium-dark skin tone, light skin tone","s":"family"},{"e":"👩🏾‍🤝‍👩🏼","n":"women holding hands: medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏾‍🤝‍👩🏽","n":"women holding hands: medium-dark skin tone, medium skin tone","s":"family"},{"e":"👭🏾","n":"women holding hands: medium-dark skin tone","s":"family"},{"e":"👩🏾‍🤝‍👩🏿","n":"women holding hands: medium-dark skin tone, dark skin tone","s":"family"},{"e":"👩🏿‍🤝‍👩🏻","n":"women holding hands: dark skin tone, light skin tone","s":"family"},{"e":"👩🏿‍🤝‍👩🏼","n":"women holding hands: dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏿‍🤝‍👩🏽","n":"women holding hands: dark skin tone, medium skin tone","s":"family"},{"e":"👩🏿‍🤝‍👩🏾","n":"women holding hands: dark skin tone, medium-dark skin tone","s":"family"},{"e":"👭🏿","n":"women holding hands: dark skin tone","s":"family"},{"e":"👫","n":"woman and man holding hands","s":"family"},{"e":"👫🏻","n":"woman and man holding hands: light skin tone","s":"family"},{"e":"👩🏻‍🤝‍👨🏼","n":"woman and man holding hands: light skin tone, medium-light skin tone","s":"family"},{"e":"👩🏻‍🤝‍👨🏽","n":"woman and man holding hands: light skin tone, medium skin tone","s":"family"},{"e":"👩🏻‍🤝‍👨🏾","n":"woman and man holding hands: light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏻‍🤝‍👨🏿","n":"woman and man holding hands: light skin tone, dark skin tone","s":"family"},{"e":"👩🏼‍🤝‍👨🏻","n":"woman and man holding hands: medium-light skin tone, light skin tone","s":"family"},{"e":"👫🏼","n":"woman and man holding hands: medium-light skin tone","s":"family"},{"e":"👩🏼‍🤝‍👨🏽","n":"woman and man holding hands: medium-light skin tone, medium skin tone","s":"family"},{"e":"👩🏼‍🤝‍👨🏾","n":"woman and man holding hands: medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏼‍🤝‍👨🏿","n":"woman and man holding hands: medium-light skin tone, dark skin tone","s":"family"},{"e":"👩🏽‍🤝‍👨🏻","n":"woman and man holding hands: medium skin tone, light skin tone","s":"family"},{"e":"👩🏽‍🤝‍👨🏼","n":"woman and man holding hands: medium skin tone, medium-light skin tone","s":"family"},{"e":"👫🏽","n":"woman and man holding hands: medium skin tone","s":"family"},{"e":"👩🏽‍🤝‍👨🏾","n":"woman and man holding hands: medium skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏽‍🤝‍👨🏿","n":"woman and man holding hands: medium skin tone, dark skin tone","s":"family"},{"e":"👩🏾‍🤝‍👨🏻","n":"woman and man holding hands: medium-dark skin tone, light skin tone","s":"family"},{"e":"👩🏾‍🤝‍👨🏼","n":"woman and man holding hands: medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏾‍🤝‍👨🏽","n":"woman and man holding hands: medium-dark skin tone, medium skin tone","s":"family"},{"e":"👫🏾","n":"woman and man holding hands: medium-dark skin tone","s":"family"},{"e":"👩🏾‍🤝‍👨🏿","n":"woman and man holding hands: medium-dark skin tone, dark skin tone","s":"family"},{"e":"👩🏿‍🤝‍👨🏻","n":"woman and man holding hands: dark skin tone, light skin tone","s":"family"},{"e":"👩🏿‍🤝‍👨🏼","n":"woman and man holding hands: dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏿‍🤝‍👨🏽","n":"woman and man holding hands: dark skin tone, medium skin tone","s":"family"},{"e":"👩🏿‍🤝‍👨🏾","n":"woman and man holding hands: dark skin tone, medium-dark skin tone","s":"family"},{"e":"👫🏿","n":"woman and man holding hands: dark skin tone","s":"family"},{"e":"👬","n":"men holding hands","s":"family"},{"e":"👬🏻","n":"men holding hands: light skin tone","s":"family"},{"e":"👨🏻‍🤝‍👨🏼","n":"men holding hands: light skin tone, medium-light skin tone","s":"family"},{"e":"👨🏻‍🤝‍👨🏽","n":"men holding hands: light skin tone, medium skin tone","s":"family"},{"e":"👨🏻‍🤝‍👨🏾","n":"men holding hands: light skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏻‍🤝‍👨🏿","n":"men holding hands: light skin tone, dark skin tone","s":"family"},{"e":"👨🏼‍🤝‍👨🏻","n":"men holding hands: medium-light skin tone, light skin tone","s":"family"},{"e":"👬🏼","n":"men holding hands: medium-light skin tone","s":"family"},{"e":"👨🏼‍🤝‍👨🏽","n":"men holding hands: medium-light skin tone, medium skin tone","s":"family"},{"e":"👨🏼‍🤝‍👨🏾","n":"men holding hands: medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏼‍🤝‍👨🏿","n":"men holding hands: medium-light skin tone, dark skin tone","s":"family"},{"e":"👨🏽‍🤝‍👨🏻","n":"men holding hands: medium skin tone, light skin tone","s":"family"},{"e":"👨🏽‍🤝‍👨🏼","n":"men holding hands: medium skin tone, medium-light skin tone","s":"family"},{"e":"👬🏽","n":"men holding hands: medium skin tone","s":"family"},{"e":"👨🏽‍🤝‍👨🏾","n":"men holding hands: medium skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏽‍🤝‍👨🏿","n":"men holding hands: medium skin tone, dark skin tone","s":"family"},{"e":"👨🏾‍🤝‍👨🏻","n":"men holding hands: medium-dark skin tone, light skin tone","s":"family"},{"e":"👨🏾‍🤝‍👨🏼","n":"men holding hands: medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👨🏾‍🤝‍👨🏽","n":"men holding hands: medium-dark skin tone, medium skin tone","s":"family"},{"e":"👬🏾","n":"men holding hands: medium-dark skin tone","s":"family"},{"e":"👨🏾‍🤝‍👨🏿","n":"men holding hands: medium-dark skin tone, dark skin tone","s":"family"},{"e":"👨🏿‍🤝‍👨🏻","n":"men holding hands: dark skin tone, light skin tone","s":"family"},{"e":"👨🏿‍🤝‍👨🏼","n":"men holding hands: dark skin tone, medium-light skin tone","s":"family"},{"e":"👨🏿‍🤝‍👨🏽","n":"men holding hands: dark skin tone, medium skin tone","s":"family"},{"e":"👨🏿‍🤝‍👨🏾","n":"men holding hands: dark skin tone, medium-dark skin tone","s":"family"},{"e":"👬🏿","n":"men holding hands: dark skin tone","s":"family"},{"e":"💏","n":"kiss","s":"family"},{"e":"💏🏻","n":"kiss: light skin tone","s":"family"},{"e":"💏🏼","n":"kiss: medium-light skin tone","s":"family"},{"e":"💏🏽","n":"kiss: medium skin tone","s":"family"},{"e":"💏🏾","n":"kiss: medium-dark skin tone","s":"family"},{"e":"💏🏿","n":"kiss: dark skin tone","s":"family"},{"e":"🧑🏻‍❤️‍💋‍🧑🏼","n":"kiss: person, person, light skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏻‍❤️‍💋‍🧑🏽","n":"kiss: person, person, light skin tone, medium skin tone","s":"family"},{"e":"🧑🏻‍❤️‍💋‍🧑🏾","n":"kiss: person, person, light skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏻‍❤️‍💋‍🧑🏿","n":"kiss: person, person, light skin tone, dark skin tone","s":"family"},{"e":"🧑🏼‍❤️‍💋‍🧑🏻","n":"kiss: person, person, medium-light skin tone, light skin tone","s":"family"},{"e":"🧑🏼‍❤️‍💋‍🧑🏽","n":"kiss: person, person, medium-light skin tone, medium skin tone","s":"family"},{"e":"🧑🏼‍❤️‍💋‍🧑🏾","n":"kiss: person, person, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏼‍❤️‍💋‍🧑🏿","n":"kiss: person, person, medium-light skin tone, dark skin tone","s":"family"},{"e":"🧑🏽‍❤️‍💋‍🧑🏻","n":"kiss: person, person, medium skin tone, light skin tone","s":"family"},{"e":"🧑🏽‍❤️‍💋‍🧑🏼","n":"kiss: person, person, medium skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏽‍❤️‍💋‍🧑🏾","n":"kiss: person, person, medium skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏽‍❤️‍💋‍🧑🏿","n":"kiss: person, person, medium skin tone, dark skin tone","s":"family"},{"e":"🧑🏾‍❤️‍💋‍🧑🏻","n":"kiss: person, person, medium-dark skin tone, light skin tone","s":"family"},{"e":"🧑🏾‍❤️‍💋‍🧑🏼","n":"kiss: person, person, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏾‍❤️‍💋‍🧑🏽","n":"kiss: person, person, medium-dark skin tone, medium skin tone","s":"family"},{"e":"🧑🏾‍❤️‍💋‍🧑🏿","n":"kiss: person, person, medium-dark skin tone, dark skin tone","s":"family"},{"e":"🧑🏿‍❤️‍💋‍🧑🏻","n":"kiss: person, person, dark skin tone, light skin tone","s":"family"},{"e":"🧑🏿‍❤️‍💋‍🧑🏼","n":"kiss: person, person, dark skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏿‍❤️‍💋‍🧑🏽","n":"kiss: person, person, dark skin tone, medium skin tone","s":"family"},{"e":"🧑🏿‍❤️‍💋‍🧑🏾","n":"kiss: person, person, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👩‍❤️‍💋‍👨","n":"kiss: woman, man","s":"family"},{"e":"👩🏻‍❤️‍💋‍👨🏻","n":"kiss: woman, man, light skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👨🏼","n":"kiss: woman, man, light skin tone, medium-light skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👨🏽","n":"kiss: woman, man, light skin tone, medium skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👨🏾","n":"kiss: woman, man, light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👨🏿","n":"kiss: woman, man, light skin tone, dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👨🏻","n":"kiss: woman, man, medium-light skin tone, light skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👨🏼","n":"kiss: woman, man, medium-light skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👨🏽","n":"kiss: woman, man, medium-light skin tone, medium skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👨🏾","n":"kiss: woman, man, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👨🏿","n":"kiss: woman, man, medium-light skin tone, dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👨🏻","n":"kiss: woman, man, medium skin tone, light skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👨🏼","n":"kiss: woman, man, medium skin tone, medium-light skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👨🏽","n":"kiss: woman, man, medium skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👨🏾","n":"kiss: woman, man, medium skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👨🏿","n":"kiss: woman, man, medium skin tone, dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👨🏻","n":"kiss: woman, man, medium-dark skin tone, light skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👨🏼","n":"kiss: woman, man, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👨🏽","n":"kiss: woman, man, medium-dark skin tone, medium skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👨🏾","n":"kiss: woman, man, medium-dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👨🏿","n":"kiss: woman, man, medium-dark skin tone, dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👨🏻","n":"kiss: woman, man, dark skin tone, light skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👨🏼","n":"kiss: woman, man, dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👨🏽","n":"kiss: woman, man, dark skin tone, medium skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👨🏾","n":"kiss: woman, man, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👨🏿","n":"kiss: woman, man, dark skin tone","s":"family"},{"e":"👨‍❤️‍💋‍👨","n":"kiss: man, man","s":"family"},{"e":"👨🏻‍❤️‍💋‍👨🏻","n":"kiss: man, man, light skin tone","s":"family"},{"e":"👨🏻‍❤️‍💋‍👨🏼","n":"kiss: man, man, light skin tone, medium-light skin tone","s":"family"},{"e":"👨🏻‍❤️‍💋‍👨🏽","n":"kiss: man, man, light skin tone, medium skin tone","s":"family"},{"e":"👨🏻‍❤️‍💋‍👨🏾","n":"kiss: man, man, light skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏻‍❤️‍💋‍👨🏿","n":"kiss: man, man, light skin tone, dark skin tone","s":"family"},{"e":"👨🏼‍❤️‍💋‍👨🏻","n":"kiss: man, man, medium-light skin tone, light skin tone","s":"family"},{"e":"👨🏼‍❤️‍💋‍👨🏼","n":"kiss: man, man, medium-light skin tone","s":"family"},{"e":"👨🏼‍❤️‍💋‍👨🏽","n":"kiss: man, man, medium-light skin tone, medium skin tone","s":"family"},{"e":"👨🏼‍❤️‍💋‍👨🏾","n":"kiss: man, man, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏼‍❤️‍💋‍👨🏿","n":"kiss: man, man, medium-light skin tone, dark skin tone","s":"family"},{"e":"👨🏽‍❤️‍💋‍👨🏻","n":"kiss: man, man, medium skin tone, light skin tone","s":"family"},{"e":"👨🏽‍❤️‍💋‍👨🏼","n":"kiss: man, man, medium skin tone, medium-light skin tone","s":"family"},{"e":"👨🏽‍❤️‍💋‍👨🏽","n":"kiss: man, man, medium skin tone","s":"family"},{"e":"👨🏽‍❤️‍💋‍👨🏾","n":"kiss: man, man, medium skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏽‍❤️‍💋‍👨🏿","n":"kiss: man, man, medium skin tone, dark skin tone","s":"family"},{"e":"👨🏾‍❤️‍💋‍👨🏻","n":"kiss: man, man, medium-dark skin tone, light skin tone","s":"family"},{"e":"👨🏾‍❤️‍💋‍👨🏼","n":"kiss: man, man, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👨🏾‍❤️‍💋‍👨🏽","n":"kiss: man, man, medium-dark skin tone, medium skin tone","s":"family"},{"e":"👨🏾‍❤️‍💋‍👨🏾","n":"kiss: man, man, medium-dark skin tone","s":"family"},{"e":"👨🏾‍❤️‍💋‍👨🏿","n":"kiss: man, man, medium-dark skin tone, dark skin tone","s":"family"},{"e":"👨🏿‍❤️‍💋‍👨🏻","n":"kiss: man, man, dark skin tone, light skin tone","s":"family"},{"e":"👨🏿‍❤️‍💋‍👨🏼","n":"kiss: man, man, dark skin tone, medium-light skin tone","s":"family"},{"e":"👨🏿‍❤️‍💋‍👨🏽","n":"kiss: man, man, dark skin tone, medium skin tone","s":"family"},{"e":"👨🏿‍❤️‍💋‍👨🏾","n":"kiss: man, man, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏿‍❤️‍💋‍👨🏿","n":"kiss: man, man, dark skin tone","s":"family"},{"e":"👩‍❤️‍💋‍👩","n":"kiss: woman, woman","s":"family"},{"e":"👩🏻‍❤️‍💋‍👩🏻","n":"kiss: woman, woman, light skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👩🏼","n":"kiss: woman, woman, light skin tone, medium-light skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👩🏽","n":"kiss: woman, woman, light skin tone, medium skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👩🏾","n":"kiss: woman, woman, light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏻‍❤️‍💋‍👩🏿","n":"kiss: woman, woman, light skin tone, dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👩🏻","n":"kiss: woman, woman, medium-light skin tone, light skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👩🏼","n":"kiss: woman, woman, medium-light skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👩🏽","n":"kiss: woman, woman, medium-light skin tone, medium skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👩🏾","n":"kiss: woman, woman, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍💋‍👩🏿","n":"kiss: woman, woman, medium-light skin tone, dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👩🏻","n":"kiss: woman, woman, medium skin tone, light skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👩🏼","n":"kiss: woman, woman, medium skin tone, medium-light skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👩🏽","n":"kiss: woman, woman, medium skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👩🏾","n":"kiss: woman, woman, medium skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍💋‍👩🏿","n":"kiss: woman, woman, medium skin tone, dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👩🏻","n":"kiss: woman, woman, medium-dark skin tone, light skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👩🏼","n":"kiss: woman, woman, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👩🏽","n":"kiss: woman, woman, medium-dark skin tone, medium skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👩🏾","n":"kiss: woman, woman, medium-dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍💋‍👩🏿","n":"kiss: woman, woman, medium-dark skin tone, dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👩🏻","n":"kiss: woman, woman, dark skin tone, light skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👩🏼","n":"kiss: woman, woman, dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👩🏽","n":"kiss: woman, woman, dark skin tone, medium skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👩🏾","n":"kiss: woman, woman, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍💋‍👩🏿","n":"kiss: woman, woman, dark skin tone","s":"family"},{"e":"💑","n":"couple with heart","s":"family"},{"e":"💑🏻","n":"couple with heart: light skin tone","s":"family"},{"e":"💑🏼","n":"couple with heart: medium-light skin tone","s":"family"},{"e":"💑🏽","n":"couple with heart: medium skin tone","s":"family"},{"e":"💑🏾","n":"couple with heart: medium-dark skin tone","s":"family"},{"e":"💑🏿","n":"couple with heart: dark skin tone","s":"family"},{"e":"🧑🏻‍❤️‍🧑🏼","n":"couple with heart: person, person, light skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏻‍❤️‍🧑🏽","n":"couple with heart: person, person, light skin tone, medium skin tone","s":"family"},{"e":"🧑🏻‍❤️‍🧑🏾","n":"couple with heart: person, person, light skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏻‍❤️‍🧑🏿","n":"couple with heart: person, person, light skin tone, dark skin tone","s":"family"},{"e":"🧑🏼‍❤️‍🧑🏻","n":"couple with heart: person, person, medium-light skin tone, light skin tone","s":"family"},{"e":"🧑🏼‍❤️‍🧑🏽","n":"couple with heart: person, person, medium-light skin tone, medium skin tone","s":"family"},{"e":"🧑🏼‍❤️‍🧑🏾","n":"couple with heart: person, person, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏼‍❤️‍🧑🏿","n":"couple with heart: person, person, medium-light skin tone, dark skin tone","s":"family"},{"e":"🧑🏽‍❤️‍🧑🏻","n":"couple with heart: person, person, medium skin tone, light skin tone","s":"family"},{"e":"🧑🏽‍❤️‍🧑🏼","n":"couple with heart: person, person, medium skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏽‍❤️‍🧑🏾","n":"couple with heart: person, person, medium skin tone, medium-dark skin tone","s":"family"},{"e":"🧑🏽‍❤️‍🧑🏿","n":"couple with heart: person, person, medium skin tone, dark skin tone","s":"family"},{"e":"🧑🏾‍❤️‍🧑🏻","n":"couple with heart: person, person, medium-dark skin tone, light skin tone","s":"family"},{"e":"🧑🏾‍❤️‍🧑🏼","n":"couple with heart: person, person, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏾‍❤️‍🧑🏽","n":"couple with heart: person, person, medium-dark skin tone, medium skin tone","s":"family"},{"e":"🧑🏾‍❤️‍🧑🏿","n":"couple with heart: person, person, medium-dark skin tone, dark skin tone","s":"family"},{"e":"🧑🏿‍❤️‍🧑🏻","n":"couple with heart: person, person, dark skin tone, light skin tone","s":"family"},{"e":"🧑🏿‍❤️‍🧑🏼","n":"couple with heart: person, person, dark skin tone, medium-light skin tone","s":"family"},{"e":"🧑🏿‍❤️‍🧑🏽","n":"couple with heart: person, person, dark skin tone, medium skin tone","s":"family"},{"e":"🧑🏿‍❤️‍🧑🏾","n":"couple with heart: person, person, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👩‍❤️‍👨","n":"couple with heart: woman, man","s":"family"},{"e":"👩🏻‍❤️‍👨🏻","n":"couple with heart: woman, man, light skin tone","s":"family"},{"e":"👩🏻‍❤️‍👨🏼","n":"couple with heart: woman, man, light skin tone, medium-light skin tone","s":"family"},{"e":"👩🏻‍❤️‍👨🏽","n":"couple with heart: woman, man, light skin tone, medium skin tone","s":"family"},{"e":"👩🏻‍❤️‍👨🏾","n":"couple with heart: woman, man, light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏻‍❤️‍👨🏿","n":"couple with heart: woman, man, light skin tone, dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍👨🏻","n":"couple with heart: woman, man, medium-light skin tone, light skin tone","s":"family"},{"e":"👩🏼‍❤️‍👨🏼","n":"couple with heart: woman, man, medium-light skin tone","s":"family"},{"e":"👩🏼‍❤️‍👨🏽","n":"couple with heart: woman, man, medium-light skin tone, medium skin tone","s":"family"},{"e":"👩🏼‍❤️‍👨🏾","n":"couple with heart: woman, man, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍👨🏿","n":"couple with heart: woman, man, medium-light skin tone, dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍👨🏻","n":"couple with heart: woman, man, medium skin tone, light skin tone","s":"family"},{"e":"👩🏽‍❤️‍👨🏼","n":"couple with heart: woman, man, medium skin tone, medium-light skin tone","s":"family"},{"e":"👩🏽‍❤️‍👨🏽","n":"couple with heart: woman, man, medium skin tone","s":"family"},{"e":"👩🏽‍❤️‍👨🏾","n":"couple with heart: woman, man, medium skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍👨🏿","n":"couple with heart: woman, man, medium skin tone, dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍👨🏻","n":"couple with heart: woman, man, medium-dark skin tone, light skin tone","s":"family"},{"e":"👩🏾‍❤️‍👨🏼","n":"couple with heart: woman, man, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏾‍❤️‍👨🏽","n":"couple with heart: woman, man, medium-dark skin tone, medium skin tone","s":"family"},{"e":"👩🏾‍❤️‍👨🏾","n":"couple with heart: woman, man, medium-dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍👨🏿","n":"couple with heart: woman, man, medium-dark skin tone, dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍👨🏻","n":"couple with heart: woman, man, dark skin tone, light skin tone","s":"family"},{"e":"👩🏿‍❤️‍👨🏼","n":"couple with heart: woman, man, dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏿‍❤️‍👨🏽","n":"couple with heart: woman, man, dark skin tone, medium skin tone","s":"family"},{"e":"👩🏿‍❤️‍👨🏾","n":"couple with heart: woman, man, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍👨🏿","n":"couple with heart: woman, man, dark skin tone","s":"family"},{"e":"👨‍❤️‍👨","n":"couple with heart: man, man","s":"family"},{"e":"👨🏻‍❤️‍👨🏻","n":"couple with heart: man, man, light skin tone","s":"family"},{"e":"👨🏻‍❤️‍👨🏼","n":"couple with heart: man, man, light skin tone, medium-light skin tone","s":"family"},{"e":"👨🏻‍❤️‍👨🏽","n":"couple with heart: man, man, light skin tone, medium skin tone","s":"family"},{"e":"👨🏻‍❤️‍👨🏾","n":"couple with heart: man, man, light skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏻‍❤️‍👨🏿","n":"couple with heart: man, man, light skin tone, dark skin tone","s":"family"},{"e":"👨🏼‍❤️‍👨🏻","n":"couple with heart: man, man, medium-light skin tone, light skin tone","s":"family"},{"e":"👨🏼‍❤️‍👨🏼","n":"couple with heart: man, man, medium-light skin tone","s":"family"},{"e":"👨🏼‍❤️‍👨🏽","n":"couple with heart: man, man, medium-light skin tone, medium skin tone","s":"family"},{"e":"👨🏼‍❤️‍👨🏾","n":"couple with heart: man, man, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏼‍❤️‍👨🏿","n":"couple with heart: man, man, medium-light skin tone, dark skin tone","s":"family"},{"e":"👨🏽‍❤️‍👨🏻","n":"couple with heart: man, man, medium skin tone, light skin tone","s":"family"},{"e":"👨🏽‍❤️‍👨🏼","n":"couple with heart: man, man, medium skin tone, medium-light skin tone","s":"family"},{"e":"👨🏽‍❤️‍👨🏽","n":"couple with heart: man, man, medium skin tone","s":"family"},{"e":"👨🏽‍❤️‍👨🏾","n":"couple with heart: man, man, medium skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏽‍❤️‍👨🏿","n":"couple with heart: man, man, medium skin tone, dark skin tone","s":"family"},{"e":"👨🏾‍❤️‍👨🏻","n":"couple with heart: man, man, medium-dark skin tone, light skin tone","s":"family"},{"e":"👨🏾‍❤️‍👨🏼","n":"couple with heart: man, man, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👨🏾‍❤️‍👨🏽","n":"couple with heart: man, man, medium-dark skin tone, medium skin tone","s":"family"},{"e":"👨🏾‍❤️‍👨🏾","n":"couple with heart: man, man, medium-dark skin tone","s":"family"},{"e":"👨🏾‍❤️‍👨🏿","n":"couple with heart: man, man, medium-dark skin tone, dark skin tone","s":"family"},{"e":"👨🏿‍❤️‍👨🏻","n":"couple with heart: man, man, dark skin tone, light skin tone","s":"family"},{"e":"👨🏿‍❤️‍👨🏼","n":"couple with heart: man, man, dark skin tone, medium-light skin tone","s":"family"},{"e":"👨🏿‍❤️‍👨🏽","n":"couple with heart: man, man, dark skin tone, medium skin tone","s":"family"},{"e":"👨🏿‍❤️‍👨🏾","n":"couple with heart: man, man, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👨🏿‍❤️‍👨🏿","n":"couple with heart: man, man, dark skin tone","s":"family"},{"e":"👩‍❤️‍👩","n":"couple with heart: woman, woman","s":"family"},{"e":"👩🏻‍❤️‍👩🏻","n":"couple with heart: woman, woman, light skin tone","s":"family"},{"e":"👩🏻‍❤️‍👩🏼","n":"couple with heart: woman, woman, light skin tone, medium-light skin tone","s":"family"},{"e":"👩🏻‍❤️‍👩🏽","n":"couple with heart: woman, woman, light skin tone, medium skin tone","s":"family"},{"e":"👩🏻‍❤️‍👩🏾","n":"couple with heart: woman, woman, light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏻‍❤️‍👩🏿","n":"couple with heart: woman, woman, light skin tone, dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍👩🏻","n":"couple with heart: woman, woman, medium-light skin tone, light skin tone","s":"family"},{"e":"👩🏼‍❤️‍👩🏼","n":"couple with heart: woman, woman, medium-light skin tone","s":"family"},{"e":"👩🏼‍❤️‍👩🏽","n":"couple with heart: woman, woman, medium-light skin tone, medium skin tone","s":"family"},{"e":"👩🏼‍❤️‍👩🏾","n":"couple with heart: woman, woman, medium-light skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏼‍❤️‍👩🏿","n":"couple with heart: woman, woman, medium-light skin tone, dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍👩🏻","n":"couple with heart: woman, woman, medium skin tone, light skin tone","s":"family"},{"e":"👩🏽‍❤️‍👩🏼","n":"couple with heart: woman, woman, medium skin tone, medium-light skin tone","s":"family"},{"e":"👩🏽‍❤️‍👩🏽","n":"couple with heart: woman, woman, medium skin tone","s":"family"},{"e":"👩🏽‍❤️‍👩🏾","n":"couple with heart: woman, woman, medium skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏽‍❤️‍👩🏿","n":"couple with heart: woman, woman, medium skin tone, dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍👩🏻","n":"couple with heart: woman, woman, medium-dark skin tone, light skin tone","s":"family"},{"e":"👩🏾‍❤️‍👩🏼","n":"couple with heart: woman, woman, medium-dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏾‍❤️‍👩🏽","n":"couple with heart: woman, woman, medium-dark skin tone, medium skin tone","s":"family"},{"e":"👩🏾‍❤️‍👩🏾","n":"couple with heart: woman, woman, medium-dark skin tone","s":"family"},{"e":"👩🏾‍❤️‍👩🏿","n":"couple with heart: woman, woman, medium-dark skin tone, dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍👩🏻","n":"couple with heart: woman, woman, dark skin tone, light skin tone","s":"family"},{"e":"👩🏿‍❤️‍👩🏼","n":"couple with heart: woman, woman, dark skin tone, medium-light skin tone","s":"family"},{"e":"👩🏿‍❤️‍👩🏽","n":"couple with heart: woman, woman, dark skin tone, medium skin tone","s":"family"},{"e":"👩🏿‍❤️‍👩🏾","n":"couple with heart: woman, woman, dark skin tone, medium-dark skin tone","s":"family"},{"e":"👩🏿‍❤️‍👩🏿","n":"couple with heart: woman, woman, dark skin tone","s":"family"},{"e":"👨‍👩‍👦","n":"family: man, woman, boy","s":"family"},{"e":"👨‍👩‍👧","n":"family: man, woman, girl","s":"family"},{"e":"👨‍👩‍👧‍👦","n":"family: man, woman, girl, boy","s":"family"},{"e":"👨‍👩‍👦‍👦","n":"family: man, woman, boy, boy","s":"family"},{"e":"👨‍👩‍👧‍👧","n":"family: man, woman, girl, girl","s":"family"},{"e":"👨‍👨‍👦","n":"family: man, man, boy","s":"family"},{"e":"👨‍👨‍👧","n":"family: man, man, girl","s":"family"},{"e":"👨‍👨‍👧‍👦","n":"family: man, man, girl, boy","s":"family"},{"e":"👨‍👨‍👦‍👦","n":"family: man, man, boy, boy","s":"family"},{"e":"👨‍👨‍👧‍👧","n":"family: man, man, girl, girl","s":"family"},{"e":"👩‍👩‍👦","n":"family: woman, woman, boy","s":"family"},{"e":"👩‍👩‍👧","n":"family: woman, woman, girl","s":"family"},{"e":"👩‍👩‍👧‍👦","n":"family: woman, woman, girl, boy","s":"family"},{"e":"👩‍👩‍👦‍👦","n":"family: woman, woman, boy, boy","s":"family"},{"e":"👩‍👩‍👧‍👧","n":"family: woman, woman, girl, girl","s":"family"},{"e":"👨‍👦","n":"family: man, boy","s":"family"},{"e":"👨‍👦‍👦","n":"family: man, boy, boy","s":"family"},{"e":"👨‍👧","n":"family: man, girl","s":"family"},{"e":"👨‍👧‍👦","n":"family: man, girl, boy","s":"family"},{"e":"👨‍👧‍👧","n":"family: man, girl, girl","s":"family"},{"e":"👩‍👦","n":"family: woman, boy","s":"family"},{"e":"👩‍👦‍👦","n":"family: woman, boy, boy","s":"family"},{"e":"👩‍👧","n":"family: woman, girl","s":"family"},{"e":"👩‍👧‍👦","n":"family: woman, girl, boy","s":"family"},{"e":"👩‍👧‍👧","n":"family: woman, girl, girl","s":"family"},{"e":"🗣️","n":"speaking head","s":"person-symbol"},{"e":"👤","n":"bust in silhouette","s":"person-symbol"},{"e":"👥","n":"busts in silhouette","s":"person-symbol"},{"e":"🫂","n":"people hugging","s":"person-symbol"},{"e":"👪","n":"family","s":"person-symbol"},{"e":"🧑‍🧑‍🧒","n":"family: adult, adult, child","s":"person-symbol"},{"e":"🧑‍🧑‍🧒‍🧒","n":"family: adult, adult, child, child","s":"person-symbol"},{"e":"🧑‍🧒","n":"family: adult, child","s":"person-symbol"},{"e":"🧑‍🧒‍🧒","n":"family: adult, child, child","s":"person-symbol"},{"e":"👣","n":"footprints","s":"person-symbol"},{"e":"🫆","n":"fingerprint","s":"person-symbol"}],"COMPONENTS":[{"e":"🏻","n":"light skin tone","s":"skin-tone"},{"e":"🏼","n":"medium-light skin tone","s":"skin-tone"},{"e":"🏽","n":"medium skin tone","s":"skin-tone"},{"e":"🏾","n":"medium-dark skin tone","s":"skin-tone"},{"e":"🏿","n":"dark skin tone","s":"skin-tone"},{"e":"🦰","n":"red hair","s":"hair-style"},{"e":"🦱","n":"curly hair","s":"hair-style"},{"e":"🦳","n":"white hair","s":"hair-style"},{"e":"🦲","n":"bald","s":"hair-style"}],"NATURE":[{"e":"🐵","n":"monkey face","s":"animal-mammal"},{"e":"🐒","n":"monkey","s":"animal-mammal"},{"e":"🦍","n":"gorilla","s":"animal-mammal"},{"e":"🦧","n":"orangutan","s":"animal-mammal"},{"e":"🐶","n":"dog face","s":"animal-mammal"},{"e":"🐕","n":"dog","s":"animal-mammal"},{"e":"🦮","n":"guide dog","s":"animal-mammal"},{"e":"🐕‍🦺","n":"service dog","s":"animal-mammal"},{"e":"🐩","n":"poodle","s":"animal-mammal"},{"e":"🐺","n":"wolf","s":"animal-mammal"},{"e":"🦊","n":"fox","s":"animal-mammal"},{"e":"🦝","n":"raccoon","s":"animal-mammal"},{"e":"🐱","n":"cat face","s":"animal-mammal"},{"e":"🐈","n":"cat","s":"animal-mammal"},{"e":"🐈‍⬛","n":"black cat","s":"animal-mammal"},{"e":"🦁","n":"lion","s":"animal-mammal"},{"e":"🐯","n":"tiger face","s":"animal-mammal"},{"e":"🐅","n":"tiger","s":"animal-mammal"},{"e":"🐆","n":"leopard","s":"animal-mammal"},{"e":"🐴","n":"horse face","s":"animal-mammal"},{"e":"🫎","n":"moose","s":"animal-mammal"},{"e":"🫏","n":"donkey","s":"animal-mammal"},{"e":"🐎","n":"horse","s":"animal-mammal"},{"e":"🦄","n":"unicorn","s":"animal-mammal"},{"e":"🦓","n":"zebra","s":"animal-mammal"},{"e":"🦌","n":"deer","s":"animal-mammal"},{"e":"🦬","n":"bison","s":"animal-mammal"},{"e":"🐮","n":"cow face","s":"animal-mammal"},{"e":"🐂","n":"ox","s":"animal-mammal"},{"e":"🐃","n":"water buffalo","s":"animal-mammal"},{"e":"🐄","n":"cow","s":"animal-mammal"},{"e":"🐷","n":"pig face","s":"animal-mammal"},{"e":"🐖","n":"pig","s":"animal-mammal"},{"e":"🐗","n":"boar","s":"animal-mammal"},{"e":"🐽","n":"pig nose","s":"animal-mammal"},{"e":"🐏","n":"ram","s":"animal-mammal"},{"e":"🐑","n":"ewe","s":"animal-mammal"},{"e":"🐐","n":"goat","s":"animal-mammal"},{"e":"🐪","n":"camel","s":"animal-mammal"},{"e":"🐫","n":"two-hump camel","s":"animal-mammal"},{"e":"🦙","n":"llama","s":"animal-mammal"},{"e":"🦒","n":"giraffe","s":"animal-mammal"},{"e":"🐘","n":"elephant","s":"animal-mammal"},{"e":"🦣","n":"mammoth","s":"animal-mammal"},{"e":"🦏","n":"rhinoceros","s":"animal-mammal"},{"e":"🦛","n":"hippopotamus","s":"animal-mammal"},{"e":"🐭","n":"mouse face","s":"animal-mammal"},{"e":"🐁","n":"mouse","s":"animal-mammal"},{"e":"🐀","n":"rat","s":"animal-mammal"},{"e":"🐹","n":"hamster","s":"animal-mammal"},{"e":"🐰","n":"rabbit face","s":"animal-mammal"},{"e":"🐇","n":"rabbit","s":"animal-mammal"},{"e":"🐿️","n":"chipmunk","s":"animal-mammal"},{"e":"🦫","n":"beaver","s":"animal-mammal"},{"e":"🦔","n":"hedgehog","s":"animal-mammal"},{"e":"🦇","n":"bat","s":"animal-mammal"},{"e":"🐻","n":"bear","s":"animal-mammal"},{"e":"🐻‍❄️","n":"polar bear","s":"animal-mammal"},{"e":"🐨","n":"koala","s":"animal-mammal"},{"e":"🐼","n":"panda","s":"animal-mammal"},{"e":"🦥","n":"sloth","s":"animal-mammal"},{"e":"🦦","n":"otter","s":"animal-mammal"},{"e":"🦨","n":"skunk","s":"animal-mammal"},{"e":"🦘","n":"kangaroo","s":"animal-mammal"},{"e":"🦡","n":"badger","s":"animal-mammal"},{"e":"🐾","n":"paw prints","s":"animal-mammal"},{"e":"🦃","n":"turkey","s":"animal-bird"},{"e":"🐔","n":"chicken","s":"animal-bird"},{"e":"🐓","n":"rooster","s":"animal-bird"},{"e":"🐣","n":"hatching chick","s":"animal-bird"},{"e":"🐤","n":"baby chick","s":"animal-bird"},{"e":"🐥","n":"front-facing baby chick","s":"animal-bird"},{"e":"🐦","n":"bird","s":"animal-bird"},{"e":"🐧","n":"penguin","s":"animal-bird"},{"e":"🕊️","n":"dove","s":"animal-bird"},{"e":"🦅","n":"eagle","s":"animal-bird"},{"e":"🦆","n":"duck","s":"animal-bird"},{"e":"🦢","n":"swan","s":"animal-bird"},{"e":"🦉","n":"owl","s":"animal-bird"},{"e":"🦤","n":"dodo","s":"animal-bird"},{"e":"🪶","n":"feather","s":"animal-bird"},{"e":"🦩","n":"flamingo","s":"animal-bird"},{"e":"🦚","n":"peacock","s":"animal-bird"},{"e":"🦜","n":"parrot","s":"animal-bird"},{"e":"🪽","n":"wing","s":"animal-bird"},{"e":"🐦‍⬛","n":"black bird","s":"animal-bird"},{"e":"🪿","n":"goose","s":"animal-bird"},{"e":"🐦‍🔥","n":"phoenix","s":"animal-bird"},{"e":"🐸","n":"frog","s":"animal-amphibian"},{"e":"🐊","n":"crocodile","s":"animal-reptile"},{"e":"🐢","n":"turtle","s":"animal-reptile"},{"e":"🦎","n":"lizard","s":"animal-reptile"},{"e":"🐍","n":"snake","s":"animal-reptile"},{"e":"🐲","n":"dragon face","s":"animal-reptile"},{"e":"🐉","n":"dragon","s":"animal-reptile"},{"e":"🦕","n":"sauropod","s":"animal-reptile"},{"e":"🦖","n":"T-Rex","s":"animal-reptile"},{"e":"🐳","n":"spouting whale","s":"animal-marine"},{"e":"🐋","n":"whale","s":"animal-marine"},{"e":"🐬","n":"dolphin","s":"animal-marine"},{"e":"🫍","n":"orca","s":"animal-marine"},{"e":"🦭","n":"seal","s":"animal-marine"},{"e":"🐟","n":"fish","s":"animal-marine"},{"e":"🐠","n":"tropical fish","s":"animal-marine"},{"e":"🐡","n":"blowfish","s":"animal-marine"},{"e":"🦈","n":"shark","s":"animal-marine"},{"e":"🐙","n":"octopus","s":"animal-marine"},{"e":"🐚","n":"spiral shell","s":"animal-marine"},{"e":"🪸","n":"coral","s":"animal-marine"},{"e":"🪼","n":"jellyfish","s":"animal-marine"},{"e":"🦀","n":"crab","s":"animal-marine"},{"e":"🦞","n":"lobster","s":"animal-marine"},{"e":"🦐","n":"shrimp","s":"animal-marine"},{"e":"🦑","n":"squid","s":"animal-marine"},{"e":"🦪","n":"oyster","s":"animal-marine"},{"e":"🐌","n":"snail","s":"animal-bug"},{"e":"🦋","n":"butterfly","s":"animal-bug"},{"e":"🫌","n":"monarch butterfly","s":"animal-bug"},{"e":"🐛","n":"bug","s":"animal-bug"},{"e":"🐜","n":"ant","s":"animal-bug"},{"e":"🐝","n":"honeybee","s":"animal-bug"},{"e":"🪲","n":"beetle","s":"animal-bug"},{"e":"🐞","n":"lady beetle","s":"animal-bug"},{"e":"🦗","n":"cricket","s":"animal-bug"},{"e":"🪳","n":"cockroach","s":"animal-bug"},{"e":"🕷️","n":"spider","s":"animal-bug"},{"e":"🕸️","n":"spider web","s":"animal-bug"},{"e":"🦂","n":"scorpion","s":"animal-bug"},{"e":"🦟","n":"mosquito","s":"animal-bug"},{"e":"🪰","n":"fly","s":"animal-bug"},{"e":"🪱","n":"worm","s":"animal-bug"},{"e":"🦠","n":"microbe","s":"animal-bug"},{"e":"💐","n":"bouquet","s":"plant-flower"},{"e":"🌸","n":"cherry blossom","s":"plant-flower"},{"e":"💮","n":"white flower","s":"plant-flower"},{"e":"🪷","n":"lotus","s":"plant-flower"},{"e":"🏵️","n":"rosette","s":"plant-flower"},{"e":"🌹","n":"rose","s":"plant-flower"},{"e":"🥀","n":"wilted flower","s":"plant-flower"},{"e":"🌺","n":"hibiscus","s":"plant-flower"},{"e":"🌻","n":"sunflower","s":"plant-flower"},{"e":"🌼","n":"blossom","s":"plant-flower"},{"e":"🌷","n":"tulip","s":"plant-flower"},{"e":"🪻","n":"hyacinth","s":"plant-flower"},{"e":"🌱","n":"seedling","s":"plant-other"},{"e":"🪴","n":"potted plant","s":"plant-other"},{"e":"🌲","n":"evergreen tree","s":"plant-other"},{"e":"🌳","n":"deciduous tree","s":"plant-other"},{"e":"🌴","n":"palm tree","s":"plant-other"},{"e":"🌵","n":"cactus","s":"plant-other"},{"e":"🌾","n":"sheaf of rice","s":"plant-other"},{"e":"🌿","n":"herb","s":"plant-other"},{"e":"☘️","n":"shamrock","s":"plant-other"},{"e":"🍀","n":"four leaf clover","s":"plant-other"},{"e":"🍁","n":"maple leaf","s":"plant-other"},{"e":"🍂","n":"fallen leaf","s":"plant-other"},{"e":"🍃","n":"leaf fluttering in wind","s":"plant-other"},{"e":"🪹","n":"empty nest","s":"plant-other"},{"e":"🪺","n":"nest with eggs","s":"plant-other"},{"e":"🍄","n":"mushroom","s":"plant-other"},{"e":"🪾","n":"leafless tree","s":"plant-other"}],"FOOD":[{"e":"🍇","n":"grapes","s":"food-fruit"},{"e":"🍈","n":"melon","s":"food-fruit"},{"e":"🍉","n":"watermelon","s":"food-fruit"},{"e":"🍊","n":"tangerine","s":"food-fruit"},{"e":"🍋","n":"lemon","s":"food-fruit"},{"e":"🍋‍🟩","n":"lime","s":"food-fruit"},{"e":"🍌","n":"banana","s":"food-fruit"},{"e":"🍍","n":"pineapple","s":"food-fruit"},{"e":"🥭","n":"mango","s":"food-fruit"},{"e":"🍎","n":"red apple","s":"food-fruit"},{"e":"🍏","n":"green apple","s":"food-fruit"},{"e":"🍐","n":"pear","s":"food-fruit"},{"e":"🍑","n":"peach","s":"food-fruit"},{"e":"🍒","n":"cherries","s":"food-fruit"},{"e":"🍓","n":"strawberry","s":"food-fruit"},{"e":"🫐","n":"blueberries","s":"food-fruit"},{"e":"🥝","n":"kiwi fruit","s":"food-fruit"},{"e":"🍅","n":"tomato","s":"food-fruit"},{"e":"🫒","n":"olive","s":"food-fruit"},{"e":"🥥","n":"coconut","s":"food-fruit"},{"e":"🥑","n":"avocado","s":"food-vegetable"},{"e":"🍆","n":"eggplant","s":"food-vegetable"},{"e":"🥔","n":"potato","s":"food-vegetable"},{"e":"🥕","n":"carrot","s":"food-vegetable"},{"e":"🌽","n":"ear of corn","s":"food-vegetable"},{"e":"🌶️","n":"hot pepper","s":"food-vegetable"},{"e":"🫑","n":"bell pepper","s":"food-vegetable"},{"e":"🥒","n":"cucumber","s":"food-vegetable"},{"e":"🫝","n":"pickle","s":"food-vegetable"},{"e":"🥬","n":"leafy green","s":"food-vegetable"},{"e":"🥦","n":"broccoli","s":"food-vegetable"},{"e":"🧄","n":"garlic","s":"food-vegetable"},{"e":"🧅","n":"onion","s":"food-vegetable"},{"e":"🥜","n":"peanuts","s":"food-vegetable"},{"e":"🫘","n":"beans","s":"food-vegetable"},{"e":"🌰","n":"chestnut","s":"food-vegetable"},{"e":"🫚","n":"ginger root","s":"food-vegetable"},{"e":"🫛","n":"pea pod","s":"food-vegetable"},{"e":"🍄‍🟫","n":"brown mushroom","s":"food-vegetable"},{"e":"🫜","n":"root vegetable","s":"food-vegetable"},{"e":"🍞","n":"bread","s":"food-prepared"},{"e":"🥐","n":"croissant","s":"food-prepared"},{"e":"🥖","n":"baguette bread","s":"food-prepared"},{"e":"🫓","n":"flatbread","s":"food-prepared"},{"e":"🥨","n":"pretzel","s":"food-prepared"},{"e":"🥯","n":"bagel","s":"food-prepared"},{"e":"🥞","n":"pancakes","s":"food-prepared"},{"e":"🧇","n":"waffle","s":"food-prepared"},{"e":"🧀","n":"cheese wedge","s":"food-prepared"},{"e":"🍖","n":"meat on bone","s":"food-prepared"},{"e":"🍗","n":"poultry leg","s":"food-prepared"},{"e":"🥩","n":"cut of meat","s":"food-prepared"},{"e":"🥓","n":"bacon","s":"food-prepared"},{"e":"🍔","n":"hamburger","s":"food-prepared"},{"e":"🍟","n":"french fries","s":"food-prepared"},{"e":"🍕","n":"pizza","s":"food-prepared"},{"e":"🌭","n":"hot dog","s":"food-prepared"},{"e":"🥪","n":"sandwich","s":"food-prepared"},{"e":"🌮","n":"taco","s":"food-prepared"},{"e":"🌯","n":"burrito","s":"food-prepared"},{"e":"🫔","n":"tamale","s":"food-prepared"},{"e":"🥙","n":"stuffed flatbread","s":"food-prepared"},{"e":"🧆","n":"falafel","s":"food-prepared"},{"e":"🥚","n":"egg","s":"food-prepared"},{"e":"🍳","n":"cooking","s":"food-prepared"},{"e":"🥘","n":"shallow pan of food","s":"food-prepared"},{"e":"🍲","n":"pot of food","s":"food-prepared"},{"e":"🫕","n":"fondue","s":"food-prepared"},{"e":"🥣","n":"bowl with spoon","s":"food-prepared"},{"e":"🥗","n":"green salad","s":"food-prepared"},{"e":"🍿","n":"popcorn","s":"food-prepared"},{"e":"🧈","n":"butter","s":"food-prepared"},{"e":"🧂","n":"salt","s":"food-prepared"},{"e":"🥫","n":"canned food","s":"food-prepared"},{"e":"🍱","n":"bento box","s":"food-asian"},{"e":"🍘","n":"rice cracker","s":"food-asian"},{"e":"🍙","n":"rice ball","s":"food-asian"},{"e":"🍚","n":"cooked rice","s":"food-asian"},{"e":"🍛","n":"curry rice","s":"food-asian"},{"e":"🍜","n":"steaming bowl","s":"food-asian"},{"e":"🍝","n":"spaghetti","s":"food-asian"},{"e":"🍠","n":"roasted sweet potato","s":"food-asian"},{"e":"🍢","n":"oden","s":"food-asian"},{"e":"🍣","n":"sushi","s":"food-asian"},{"e":"🍤","n":"fried shrimp","s":"food-asian"},{"e":"🍥","n":"fish cake with swirl","s":"food-asian"},{"e":"🥮","n":"moon cake","s":"food-asian"},{"e":"🍡","n":"dango","s":"food-asian"},{"e":"🥟","n":"dumpling","s":"food-asian"},{"e":"🥠","n":"fortune cookie","s":"food-asian"},{"e":"🥡","n":"takeout box","s":"food-asian"},{"e":"🍦","n":"soft ice cream","s":"food-sweet"},{"e":"🍧","n":"shaved ice","s":"food-sweet"},{"e":"🍨","n":"ice cream","s":"food-sweet"},{"e":"🍩","n":"doughnut","s":"food-sweet"},{"e":"🍪","n":"cookie","s":"food-sweet"},{"e":"🎂","n":"birthday cake","s":"food-sweet"},{"e":"🍰","n":"shortcake","s":"food-sweet"},{"e":"🧁","n":"cupcake","s":"food-sweet"},{"e":"🥧","n":"pie","s":"food-sweet"},{"e":"🍫","n":"chocolate bar","s":"food-sweet"},{"e":"🍬","n":"candy","s":"food-sweet"},{"e":"🍭","n":"lollipop","s":"food-sweet"},{"e":"🍮","n":"custard","s":"food-sweet"},{"e":"🍯","n":"honey pot","s":"food-sweet"},{"e":"🍼","n":"baby bottle","s":"drink"},{"e":"🥛","n":"glass of milk","s":"drink"},{"e":"☕","n":"hot beverage","s":"drink"},{"e":"🫖","n":"teapot","s":"drink"},{"e":"🍵","n":"teacup without handle","s":"drink"},{"e":"🍶","n":"sake","s":"drink"},{"e":"🍾","n":"bottle with popping cork","s":"drink"},{"e":"🍷","n":"wine glass","s":"drink"},{"e":"🍸","n":"cocktail glass","s":"drink"},{"e":"🍹","n":"tropical drink","s":"drink"},{"e":"🍺","n":"beer mug","s":"drink"},{"e":"🍻","n":"clinking beer mugs","s":"drink"},{"e":"🥂","n":"clinking glasses","s":"drink"},{"e":"🥃","n":"tumbler glass","s":"drink"},{"e":"🫗","n":"pouring liquid","s":"drink"},{"e":"🥤","n":"cup with straw","s":"drink"},{"e":"🧋","n":"bubble tea","s":"drink"},{"e":"🧃","n":"beverage box","s":"drink"},{"e":"🧉","n":"mate","s":"drink"},{"e":"🧊","n":"ice","s":"drink"},{"e":"🥢","n":"chopsticks","s":"dishware"},{"e":"🍽️","n":"fork and knife with plate","s":"dishware"},{"e":"🍴","n":"fork and knife","s":"dishware"},{"e":"🥄","n":"spoon","s":"dishware"},{"e":"🔪","n":"kitchen knife","s":"dishware"},{"e":"🫙","n":"jar","s":"dishware"},{"e":"🏺","n":"amphora","s":"dishware"}],"TRAVEL":[{"e":"🌍","n":"globe showing Europe-Africa","s":"place-map"},{"e":"🌎","n":"globe showing Americas","s":"place-map"},{"e":"🌏","n":"globe showing Asia-Australia","s":"place-map"},{"e":"🌐","n":"globe with meridians","s":"place-map"},{"e":"🗺️","n":"world map","s":"place-map"},{"e":"🗾","n":"map of Japan","s":"place-map"},{"e":"🧭","n":"compass","s":"place-map"},{"e":"🏔️","n":"snow-capped mountain","s":"place-geographic"},{"e":"⛰️","n":"mountain","s":"place-geographic"},{"e":"🛘","n":"landslide","s":"place-geographic"},{"e":"🌋","n":"volcano","s":"place-geographic"},{"e":"🗻","n":"mount fuji","s":"place-geographic"},{"e":"🏕️","n":"camping","s":"place-geographic"},{"e":"🏖️","n":"beach with umbrella","s":"place-geographic"},{"e":"🏜️","n":"desert","s":"place-geographic"},{"e":"🏝️","n":"desert island","s":"place-geographic"},{"e":"🏞️","n":"national park","s":"place-geographic"},{"e":"🏟️","n":"stadium","s":"place-building"},{"e":"🏛️","n":"classical building","s":"place-building"},{"e":"🏗️","n":"building construction","s":"place-building"},{"e":"🧱","n":"brick","s":"place-building"},{"e":"🪨","n":"rock","s":"place-building"},{"e":"🪵","n":"wood","s":"place-building"},{"e":"🛖","n":"hut","s":"place-building"},{"e":"🏘️","n":"houses","s":"place-building"},{"e":"🏚️","n":"derelict house","s":"place-building"},{"e":"🏠","n":"house","s":"place-building"},{"e":"🏡","n":"house with garden","s":"place-building"},{"e":"🏢","n":"office building","s":"place-building"},{"e":"🏣","n":"Japanese post office","s":"place-building"},{"e":"🏤","n":"post office","s":"place-building"},{"e":"🏥","n":"hospital","s":"place-building"},{"e":"🏦","n":"bank","s":"place-building"},{"e":"🏨","n":"hotel","s":"place-building"},{"e":"🏩","n":"love hotel","s":"place-building"},{"e":"🏪","n":"convenience store","s":"place-building"},{"e":"🏫","n":"school","s":"place-building"},{"e":"🏬","n":"department store","s":"place-building"},{"e":"🏭","n":"factory","s":"place-building"},{"e":"🏯","n":"Japanese castle","s":"place-building"},{"e":"🏰","n":"castle","s":"place-building"},{"e":"💒","n":"wedding","s":"place-building"},{"e":"🗼","n":"Tokyo tower","s":"place-building"},{"e":"🗽","n":"Statue of Liberty","s":"place-building"},{"e":"⛪","n":"church","s":"place-religious"},{"e":"🕌","n":"mosque","s":"place-religious"},{"e":"🛕","n":"hindu temple","s":"place-religious"},{"e":"🕍","n":"synagogue","s":"place-religious"},{"e":"⛩️","n":"shinto shrine","s":"place-religious"},{"e":"🕋","n":"kaaba","s":"place-religious"},{"e":"⛲","n":"fountain","s":"place-other"},{"e":"⛺","n":"tent","s":"place-other"},{"e":"🌁","n":"foggy","s":"place-other"},{"e":"🌃","n":"night with stars","s":"place-other"},{"e":"🏙️","n":"cityscape","s":"place-other"},{"e":"🌄","n":"sunrise over mountains","s":"place-other"},{"e":"🌅","n":"sunrise","s":"place-other"},{"e":"🌆","n":"cityscape at dusk","s":"place-other"},{"e":"🌇","n":"sunset","s":"place-other"},{"e":"🌉","n":"bridge at night","s":"place-other"},{"e":"♨️","n":"hot springs","s":"place-other"},{"e":"🎠","n":"carousel horse","s":"place-other"},{"e":"🛝","n":"playground slide","s":"place-other"},{"e":"🎡","n":"ferris wheel","s":"place-other"},{"e":"🎢","n":"roller coaster","s":"place-other"},{"e":"💈","n":"barber pole","s":"place-other"},{"e":"🎪","n":"circus tent","s":"place-other"},{"e":"🚂","n":"locomotive","s":"transport-ground"},{"e":"🚃","n":"railway car","s":"transport-ground"},{"e":"🚄","n":"high-speed train","s":"transport-ground"},{"e":"🚅","n":"bullet train","s":"transport-ground"},{"e":"🚆","n":"train","s":"transport-ground"},{"e":"🚇","n":"metro","s":"transport-ground"},{"e":"🚈","n":"light rail","s":"transport-ground"},{"e":"🚉","n":"station","s":"transport-ground"},{"e":"🚊","n":"tram","s":"transport-ground"},{"e":"🚝","n":"monorail","s":"transport-ground"},{"e":"🚞","n":"mountain railway","s":"transport-ground"},{"e":"🚋","n":"tram car","s":"transport-ground"},{"e":"🚌","n":"bus","s":"transport-ground"},{"e":"🚍","n":"oncoming bus","s":"transport-ground"},{"e":"🚎","n":"trolleybus","s":"transport-ground"},{"e":"🚐","n":"minibus","s":"transport-ground"},{"e":"🚑","n":"ambulance","s":"transport-ground"},{"e":"🚒","n":"fire engine","s":"transport-ground"},{"e":"🚓","n":"police car","s":"transport-ground"},{"e":"🚔","n":"oncoming police car","s":"transport-ground"},{"e":"🚕","n":"taxi","s":"transport-ground"},{"e":"🚖","n":"oncoming taxi","s":"transport-ground"},{"e":"🚗","n":"automobile","s":"transport-ground"},{"e":"🚘","n":"oncoming automobile","s":"transport-ground"},{"e":"🚙","n":"sport utility vehicle","s":"transport-ground"},{"e":"🛻","n":"pickup truck","s":"transport-ground"},{"e":"🚚","n":"delivery truck","s":"transport-ground"},{"e":"🚛","n":"articulated lorry","s":"transport-ground"},{"e":"🚜","n":"tractor","s":"transport-ground"},{"e":"🏎️","n":"racing car","s":"transport-ground"},{"e":"🏍️","n":"motorcycle","s":"transport-ground"},{"e":"🛵","n":"motor scooter","s":"transport-ground"},{"e":"🦽","n":"manual wheelchair","s":"transport-ground"},{"e":"🦼","n":"motorized wheelchair","s":"transport-ground"},{"e":"🛺","n":"auto rickshaw","s":"transport-ground"},{"e":"🚲","n":"bicycle","s":"transport-ground"},{"e":"🛴","n":"kick scooter","s":"transport-ground"},{"e":"🛹","n":"skateboard","s":"transport-ground"},{"e":"🛼","n":"roller skate","s":"transport-ground"},{"e":"🚏","n":"bus stop","s":"transport-ground"},{"e":"🛣️","n":"motorway","s":"transport-ground"},{"e":"🛤️","n":"railway track","s":"transport-ground"},{"e":"🛢️","n":"oil drum","s":"transport-ground"},{"e":"⛽","n":"fuel pump","s":"transport-ground"},{"e":"🛞","n":"wheel","s":"transport-ground"},{"e":"🚨","n":"police car light","s":"transport-ground"},{"e":"🚥","n":"horizontal traffic light","s":"transport-ground"},{"e":"🚦","n":"vertical traffic light","s":"transport-ground"},{"e":"🛑","n":"stop sign","s":"transport-ground"},{"e":"🚧","n":"construction","s":"transport-ground"},{"e":"🛙","n":"lighthouse","s":"transport-water"},{"e":"⚓","n":"anchor","s":"transport-water"},{"e":"🛟","n":"ring buoy","s":"transport-water"},{"e":"⛵","n":"sailboat","s":"transport-water"},{"e":"🛶","n":"canoe","s":"transport-water"},{"e":"🚤","n":"speedboat","s":"transport-water"},{"e":"🛳️","n":"passenger ship","s":"transport-water"},{"e":"⛴️","n":"ferry","s":"transport-water"},{"e":"🛥️","n":"motor boat","s":"transport-water"},{"e":"🚢","n":"ship","s":"transport-water"},{"e":"✈️","n":"airplane","s":"transport-air"},{"e":"🛩️","n":"small airplane","s":"transport-air"},{"e":"🛫","n":"airplane departure","s":"transport-air"},{"e":"🛬","n":"airplane arrival","s":"transport-air"},{"e":"🪂","n":"parachute","s":"transport-air"},{"e":"💺","n":"seat","s":"transport-air"},{"e":"🚁","n":"helicopter","s":"transport-air"},{"e":"🚟","n":"suspension railway","s":"transport-air"},{"e":"🚠","n":"mountain cableway","s":"transport-air"},{"e":"🚡","n":"aerial tramway","s":"transport-air"},{"e":"🛰️","n":"satellite","s":"transport-air"},{"e":"🚀","n":"rocket","s":"transport-air"},{"e":"🛸","n":"flying saucer","s":"transport-air"},{"e":"🛎️","n":"bellhop bell","s":"hotel"},{"e":"🧳","n":"luggage","s":"hotel"},{"e":"⌛","n":"hourglass done","s":"time"},{"e":"⏳","n":"hourglass not done","s":"time"},{"e":"⌚","n":"watch","s":"time"},{"e":"⏰","n":"alarm clock","s":"time"},{"e":"⏱️","n":"stopwatch","s":"time"},{"e":"⏲️","n":"timer clock","s":"time"},{"e":"🕰️","n":"mantelpiece clock","s":"time"},{"e":"🕛","n":"twelve o’clock","s":"time"},{"e":"🕧","n":"twelve-thirty","s":"time"},{"e":"🕐","n":"one o’clock","s":"time"},{"e":"🕜","n":"one-thirty","s":"time"},{"e":"🕑","n":"two o’clock","s":"time"},{"e":"🕝","n":"two-thirty","s":"time"},{"e":"🕒","n":"three o’clock","s":"time"},{"e":"🕞","n":"three-thirty","s":"time"},{"e":"🕓","n":"four o’clock","s":"time"},{"e":"🕟","n":"four-thirty","s":"time"},{"e":"🕔","n":"five o’clock","s":"time"},{"e":"🕠","n":"five-thirty","s":"time"},{"e":"🕕","n":"six o’clock","s":"time"},{"e":"🕡","n":"six-thirty","s":"time"},{"e":"🕖","n":"seven o’clock","s":"time"},{"e":"🕢","n":"seven-thirty","s":"time"},{"e":"🕗","n":"eight o’clock","s":"time"},{"e":"🕣","n":"eight-thirty","s":"time"},{"e":"🕘","n":"nine o’clock","s":"time"},{"e":"🕤","n":"nine-thirty","s":"time"},{"e":"🕙","n":"ten o’clock","s":"time"},{"e":"🕥","n":"ten-thirty","s":"time"},{"e":"🕚","n":"eleven o’clock","s":"time"},{"e":"🕦","n":"eleven-thirty","s":"time"},{"e":"🌑","n":"new moon","s":"sky & weather"},{"e":"🌒","n":"waxing crescent moon","s":"sky & weather"},{"e":"🌓","n":"first quarter moon","s":"sky & weather"},{"e":"🌔","n":"waxing gibbous moon","s":"sky & weather"},{"e":"🌕","n":"full moon","s":"sky & weather"},{"e":"🌖","n":"waning gibbous moon","s":"sky & weather"},{"e":"🌗","n":"last quarter moon","s":"sky & weather"},{"e":"🌘","n":"waning crescent moon","s":"sky & weather"},{"e":"🌙","n":"crescent moon","s":"sky & weather"},{"e":"🌚","n":"new moon face","s":"sky & weather"},{"e":"🌛","n":"first quarter moon face","s":"sky & weather"},{"e":"🌜","n":"last quarter moon face","s":"sky & weather"},{"e":"🌡️","n":"thermometer","s":"sky & weather"},{"e":"☀️","n":"sun","s":"sky & weather"},{"e":"🌝","n":"full moon face","s":"sky & weather"},{"e":"🌞","n":"sun with face","s":"sky & weather"},{"e":"🪐","n":"ringed planet","s":"sky & weather"},{"e":"⭐","n":"star","s":"sky & weather"},{"e":"🌟","n":"glowing star","s":"sky & weather"},{"e":"🌠","n":"shooting star","s":"sky & weather"},{"e":"🌌","n":"milky way","s":"sky & weather"},{"e":"☁️","n":"cloud","s":"sky & weather"},{"e":"⛅","n":"sun behind cloud","s":"sky & weather"},{"e":"⛈️","n":"cloud with lightning and rain","s":"sky & weather"},{"e":"🌤️","n":"sun behind small cloud","s":"sky & weather"},{"e":"🌥️","n":"sun behind large cloud","s":"sky & weather"},{"e":"🌦️","n":"sun behind rain cloud","s":"sky & weather"},{"e":"🌧️","n":"cloud with rain","s":"sky & weather"},{"e":"🌨️","n":"cloud with snow","s":"sky & weather"},{"e":"🌩️","n":"cloud with lightning","s":"sky & weather"},{"e":"🌪️","n":"tornado","s":"sky & weather"},{"e":"🌫️","n":"fog","s":"sky & weather"},{"e":"🌬️","n":"wind face","s":"sky & weather"},{"e":"🌀","n":"cyclone","s":"sky & weather"},{"e":"🌈","n":"rainbow","s":"sky & weather"},{"e":"🌂","n":"closed umbrella","s":"sky & weather"},{"e":"☂️","n":"umbrella","s":"sky & weather"},{"e":"☔","n":"umbrella with rain drops","s":"sky & weather"},{"e":"⛱️","n":"umbrella on ground","s":"sky & weather"},{"e":"⚡","n":"high voltage","s":"sky & weather"},{"e":"❄️","n":"snowflake","s":"sky & weather"},{"e":"☃️","n":"snowman","s":"sky & weather"},{"e":"⛄","n":"snowman without snow","s":"sky & weather"},{"e":"☄️","n":"comet","s":"sky & weather"},{"e":"🪋","n":"meteor","s":"sky & weather"},{"e":"🔥","n":"fire","s":"sky & weather"},{"e":"💧","n":"droplet","s":"sky & weather"},{"e":"🌊","n":"water wave","s":"sky & weather"}],"ACTIVITIES":[{"e":"🎃","n":"jack-o-lantern","s":"event"},{"e":"🎄","n":"Christmas tree","s":"event"},{"e":"🎆","n":"fireworks","s":"event"},{"e":"🎇","n":"sparkler","s":"event"},{"e":"🧨","n":"firecracker","s":"event"},{"e":"✨","n":"sparkles","s":"event"},{"e":"🎈","n":"balloon","s":"event"},{"e":"🎉","n":"party popper","s":"event"},{"e":"🎊","n":"confetti ball","s":"event"},{"e":"🎋","n":"tanabata tree","s":"event"},{"e":"🎍","n":"pine decoration","s":"event"},{"e":"🎎","n":"Japanese dolls","s":"event"},{"e":"🎏","n":"carp streamer","s":"event"},{"e":"🎐","n":"wind chime","s":"event"},{"e":"🎑","n":"moon viewing ceremony","s":"event"},{"e":"🧧","n":"red envelope","s":"event"},{"e":"🎀","n":"ribbon","s":"event"},{"e":"🎁","n":"wrapped gift","s":"event"},{"e":"🎗️","n":"reminder ribbon","s":"event"},{"e":"🎟️","n":"admission tickets","s":"event"},{"e":"🎫","n":"ticket","s":"event"},{"e":"🎖️","n":"military medal","s":"award-medal"},{"e":"🏆","n":"trophy","s":"award-medal"},{"e":"🏅","n":"sports medal","s":"award-medal"},{"e":"🥇","n":"1st place medal","s":"award-medal"},{"e":"🥈","n":"2nd place medal","s":"award-medal"},{"e":"🥉","n":"3rd place medal","s":"award-medal"},{"e":"⚽","n":"soccer ball","s":"sport"},{"e":"⚾","n":"baseball","s":"sport"},{"e":"🥎","n":"softball","s":"sport"},{"e":"🏀","n":"basketball","s":"sport"},{"e":"🏐","n":"volleyball","s":"sport"},{"e":"🏈","n":"american football","s":"sport"},{"e":"🏉","n":"rugby football","s":"sport"},{"e":"🎾","n":"tennis","s":"sport"},{"e":"🥏","n":"flying disc","s":"sport"},{"e":"🎳","n":"bowling","s":"sport"},{"e":"🏏","n":"cricket game","s":"sport"},{"e":"🏑","n":"field hockey","s":"sport"},{"e":"🏒","n":"ice hockey","s":"sport"},{"e":"🥍","n":"lacrosse","s":"sport"},{"e":"🏓","n":"ping pong","s":"sport"},{"e":"🏸","n":"badminton","s":"sport"},{"e":"🥊","n":"boxing glove","s":"sport"},{"e":"🥋","n":"martial arts uniform","s":"sport"},{"e":"🥅","n":"goal net","s":"sport"},{"e":"⛳","n":"flag in hole","s":"sport"},{"e":"⛸️","n":"ice skate","s":"sport"},{"e":"🎣","n":"fishing pole","s":"sport"},{"e":"🤿","n":"diving mask","s":"sport"},{"e":"🎽","n":"running shirt","s":"sport"},{"e":"🎿","n":"skis","s":"sport"},{"e":"🛷","n":"sled","s":"sport"},{"e":"🥌","n":"curling stone","s":"sport"},{"e":"🎯","n":"bullseye","s":"game"},{"e":"🪀","n":"yo-yo","s":"game"},{"e":"🪁","n":"kite","s":"game"},{"e":"🔫","n":"water pistol","s":"game"},{"e":"🎱","n":"pool 8 ball","s":"game"},{"e":"🔮","n":"crystal ball","s":"game"},{"e":"🪄","n":"magic wand","s":"game"},{"e":"🎮","n":"video game","s":"game"},{"e":"🕹️","n":"joystick","s":"game"},{"e":"🎰","n":"slot machine","s":"game"},{"e":"🎲","n":"game die","s":"game"},{"e":"🧩","n":"puzzle piece","s":"game"},{"e":"🧸","n":"teddy bear","s":"game"},{"e":"🪅","n":"piñata","s":"game"},{"e":"🪩","n":"mirror ball","s":"game"},{"e":"🪆","n":"nesting dolls","s":"game"},{"e":"♠️","n":"spade suit","s":"game"},{"e":"♥️","n":"heart suit","s":"game"},{"e":"♦️","n":"diamond suit","s":"game"},{"e":"♣️","n":"club suit","s":"game"},{"e":"♟️","n":"chess pawn","s":"game"},{"e":"🃏","n":"joker","s":"game"},{"e":"🀄","n":"mahjong red dragon","s":"game"},{"e":"🎴","n":"flower playing cards","s":"game"},{"e":"🎭","n":"performing arts","s":"arts & crafts"},{"e":"🖼️","n":"framed picture","s":"arts & crafts"},{"e":"🎨","n":"artist palette","s":"arts & crafts"},{"e":"🧵","n":"thread","s":"arts & crafts"},{"e":"🪡","n":"sewing needle","s":"arts & crafts"},{"e":"🧶","n":"yarn","s":"arts & crafts"},{"e":"🪢","n":"knot","s":"arts & crafts"}],"OBJECTS":[{"e":"👓","n":"glasses","s":"clothing"},{"e":"🕶️","n":"sunglasses","s":"clothing"},{"e":"🥽","n":"goggles","s":"clothing"},{"e":"🥼","n":"lab coat","s":"clothing"},{"e":"🦺","n":"safety vest","s":"clothing"},{"e":"👔","n":"necktie","s":"clothing"},{"e":"👕","n":"t-shirt","s":"clothing"},{"e":"👖","n":"jeans","s":"clothing"},{"e":"🧣","n":"scarf","s":"clothing"},{"e":"🧤","n":"gloves","s":"clothing"},{"e":"🧥","n":"coat","s":"clothing"},{"e":"🧦","n":"socks","s":"clothing"},{"e":"👗","n":"dress","s":"clothing"},{"e":"👘","n":"kimono","s":"clothing"},{"e":"🥻","n":"sari","s":"clothing"},{"e":"🩱","n":"one-piece swimsuit","s":"clothing"},{"e":"🩲","n":"briefs","s":"clothing"},{"e":"🩳","n":"shorts","s":"clothing"},{"e":"👙","n":"bikini","s":"clothing"},{"e":"👚","n":"woman’s clothes","s":"clothing"},{"e":"🪭","n":"folding hand fan","s":"clothing"},{"e":"👛","n":"purse","s":"clothing"},{"e":"👜","n":"handbag","s":"clothing"},{"e":"👝","n":"clutch bag","s":"clothing"},{"e":"🛍️","n":"shopping bags","s":"clothing"},{"e":"🎒","n":"backpack","s":"clothing"},{"e":"🩴","n":"thong sandal","s":"clothing"},{"e":"👞","n":"man’s shoe","s":"clothing"},{"e":"👟","n":"running shoe","s":"clothing"},{"e":"🥾","n":"hiking boot","s":"clothing"},{"e":"🥿","n":"flat shoe","s":"clothing"},{"e":"👠","n":"high-heeled shoe","s":"clothing"},{"e":"👡","n":"woman’s sandal","s":"clothing"},{"e":"🩰","n":"ballet shoes","s":"clothing"},{"e":"👢","n":"woman’s boot","s":"clothing"},{"e":"🪮","n":"hair pick","s":"clothing"},{"e":"👑","n":"crown","s":"clothing"},{"e":"👒","n":"woman’s hat","s":"clothing"},{"e":"🎩","n":"top hat","s":"clothing"},{"e":"🎓","n":"graduation cap","s":"clothing"},{"e":"🧢","n":"billed cap","s":"clothing"},{"e":"🪖","n":"military helmet","s":"clothing"},{"e":"⛑️","n":"rescue worker’s helmet","s":"clothing"},{"e":"📿","n":"prayer beads","s":"clothing"},{"e":"💄","n":"lipstick","s":"clothing"},{"e":"💍","n":"ring","s":"clothing"},{"e":"💎","n":"gem stone","s":"clothing"},{"e":"🔇","n":"muted speaker","s":"sound"},{"e":"🔈","n":"speaker low volume","s":"sound"},{"e":"🔉","n":"speaker medium volume","s":"sound"},{"e":"🔊","n":"speaker high volume","s":"sound"},{"e":"📢","n":"loudspeaker","s":"sound"},{"e":"📣","n":"megaphone","s":"sound"},{"e":"📯","n":"postal horn","s":"sound"},{"e":"🔔","n":"bell","s":"sound"},{"e":"🔕","n":"bell with slash","s":"sound"},{"e":"🎼","n":"musical score","s":"music"},{"e":"🎵","n":"musical note","s":"music"},{"e":"🎶","n":"musical notes","s":"music"},{"e":"🎙️","n":"studio microphone","s":"music"},{"e":"🎚️","n":"level slider","s":"music"},{"e":"🎛️","n":"control knobs","s":"music"},{"e":"🎤","n":"microphone","s":"music"},{"e":"🎧","n":"headphone","s":"music"},{"e":"📻","n":"radio","s":"music"},{"e":"🎷","n":"saxophone","s":"musical-instrument"},{"e":"🎺","n":"trumpet","s":"musical-instrument"},{"e":"🪊","n":"trombone","s":"musical-instrument"},{"e":"🪗","n":"accordion","s":"musical-instrument"},{"e":"🎸","n":"guitar","s":"musical-instrument"},{"e":"🎹","n":"musical keyboard","s":"musical-instrument"},{"e":"🎻","n":"violin","s":"musical-instrument"},{"e":"🪕","n":"banjo","s":"musical-instrument"},{"e":"🥁","n":"drum","s":"musical-instrument"},{"e":"🪘","n":"long drum","s":"musical-instrument"},{"e":"🪇","n":"maracas","s":"musical-instrument"},{"e":"🪈","n":"flute","s":"musical-instrument"},{"e":"🪉","n":"harp","s":"musical-instrument"},{"e":"📱","n":"mobile phone","s":"phone"},{"e":"📲","n":"mobile phone with arrow","s":"phone"},{"e":"☎️","n":"telephone","s":"phone"},{"e":"📞","n":"telephone receiver","s":"phone"},{"e":"📟","n":"pager","s":"phone"},{"e":"📠","n":"fax machine","s":"phone"},{"e":"🔋","n":"battery","s":"computer"},{"e":"🪫","n":"low battery","s":"computer"},{"e":"🔌","n":"electric plug","s":"computer"},{"e":"💻","n":"laptop","s":"computer"},{"e":"🖥️","n":"desktop computer","s":"computer"},{"e":"🖨️","n":"printer","s":"computer"},{"e":"⌨️","n":"keyboard","s":"computer"},{"e":"🖱️","n":"computer mouse","s":"computer"},{"e":"🖲️","n":"trackball","s":"computer"},{"e":"💽","n":"computer disk","s":"computer"},{"e":"💾","n":"floppy disk","s":"computer"},{"e":"💿","n":"optical disk","s":"computer"},{"e":"📀","n":"dvd","s":"computer"},{"e":"🧮","n":"abacus","s":"computer"},{"e":"🎥","n":"movie camera","s":"light & video"},{"e":"🎞️","n":"film frames","s":"light & video"},{"e":"📽️","n":"film projector","s":"light & video"},{"e":"🎬","n":"clapper board","s":"light & video"},{"e":"📺","n":"television","s":"light & video"},{"e":"📷","n":"camera","s":"light & video"},{"e":"📸","n":"camera with flash","s":"light & video"},{"e":"📹","n":"video camera","s":"light & video"},{"e":"📼","n":"videocassette","s":"light & video"},{"e":"🔍","n":"magnifying glass tilted left","s":"light & video"},{"e":"🔎","n":"magnifying glass tilted right","s":"light & video"},{"e":"🕯️","n":"candle","s":"light & video"},{"e":"💡","n":"light bulb","s":"light & video"},{"e":"🔦","n":"flashlight","s":"light & video"},{"e":"🏮","n":"red paper lantern","s":"light & video"},{"e":"🪔","n":"diya lamp","s":"light & video"},{"e":"📔","n":"notebook with decorative cover","s":"book-paper"},{"e":"📕","n":"closed book","s":"book-paper"},{"e":"📖","n":"open book","s":"book-paper"},{"e":"📗","n":"green book","s":"book-paper"},{"e":"📘","n":"blue book","s":"book-paper"},{"e":"📙","n":"orange book","s":"book-paper"},{"e":"📚","n":"books","s":"book-paper"},{"e":"📓","n":"notebook","s":"book-paper"},{"e":"📒","n":"ledger","s":"book-paper"},{"e":"📃","n":"page with curl","s":"book-paper"},{"e":"📜","n":"scroll","s":"book-paper"},{"e":"📄","n":"page facing up","s":"book-paper"},{"e":"📰","n":"newspaper","s":"book-paper"},{"e":"🗞️","n":"rolled-up newspaper","s":"book-paper"},{"e":"📑","n":"bookmark tabs","s":"book-paper"},{"e":"🔖","n":"bookmark","s":"book-paper"},{"e":"🏷️","n":"label","s":"book-paper"},{"e":"🪙","n":"coin","s":"money"},{"e":"💰","n":"money bag","s":"money"},{"e":"🪎","n":"treasure chest","s":"money"},{"e":"💴","n":"yen banknote","s":"money"},{"e":"💵","n":"dollar banknote","s":"money"},{"e":"💶","n":"euro banknote","s":"money"},{"e":"💷","n":"pound banknote","s":"money"},{"e":"💸","n":"money with wings","s":"money"},{"e":"💳","n":"credit card","s":"money"},{"e":"🧾","n":"receipt","s":"money"},{"e":"💹","n":"chart increasing with yen","s":"money"},{"e":"✉️","n":"envelope","s":"mail"},{"e":"📧","n":"e-mail","s":"mail"},{"e":"📨","n":"incoming envelope","s":"mail"},{"e":"📩","n":"envelope with arrow","s":"mail"},{"e":"📤","n":"outbox tray","s":"mail"},{"e":"📥","n":"inbox tray","s":"mail"},{"e":"📦","n":"package","s":"mail"},{"e":"📫","n":"closed mailbox with raised flag","s":"mail"},{"e":"📪","n":"closed mailbox with lowered flag","s":"mail"},{"e":"📬","n":"open mailbox with raised flag","s":"mail"},{"e":"📭","n":"open mailbox with lowered flag","s":"mail"},{"e":"📮","n":"postbox","s":"mail"},{"e":"🗳️","n":"ballot box with ballot","s":"mail"},{"e":"✏️","n":"pencil","s":"writing"},{"e":"✒️","n":"black nib","s":"writing"},{"e":"🖋️","n":"fountain pen","s":"writing"},{"e":"🖊️","n":"pen","s":"writing"},{"e":"🖌️","n":"paintbrush","s":"writing"},{"e":"🖍️","n":"crayon","s":"writing"},{"e":"📝","n":"memo","s":"writing"},{"e":"🪌","n":"eraser","s":"writing"},{"e":"💼","n":"briefcase","s":"office"},{"e":"📁","n":"file folder","s":"office"},{"e":"📂","n":"open file folder","s":"office"},{"e":"🗂️","n":"card index dividers","s":"office"},{"e":"📅","n":"calendar","s":"office"},{"e":"📆","n":"tear-off calendar","s":"office"},{"e":"🗒️","n":"spiral notepad","s":"office"},{"e":"🗓️","n":"spiral calendar","s":"office"},{"e":"📇","n":"card index","s":"office"},{"e":"📈","n":"chart increasing","s":"office"},{"e":"📉","n":"chart decreasing","s":"office"},{"e":"📊","n":"bar chart","s":"office"},{"e":"📋","n":"clipboard","s":"office"},{"e":"📌","n":"pushpin","s":"office"},{"e":"📍","n":"round pushpin","s":"office"},{"e":"📎","n":"paperclip","s":"office"},{"e":"🖇️","n":"linked paperclips","s":"office"},{"e":"📏","n":"straight ruler","s":"office"},{"e":"📐","n":"triangular ruler","s":"office"},{"e":"✂️","n":"scissors","s":"office"},{"e":"🗃️","n":"card file box","s":"office"},{"e":"🗄️","n":"file cabinet","s":"office"},{"e":"🗑️","n":"wastebasket","s":"office"},{"e":"🔒","n":"locked","s":"lock"},{"e":"🔓","n":"unlocked","s":"lock"},{"e":"🔏","n":"locked with pen","s":"lock"},{"e":"🔐","n":"locked with key","s":"lock"},{"e":"🔑","n":"key","s":"lock"},{"e":"🗝️","n":"old key","s":"lock"},{"e":"🪍","n":"net with handle","s":"tool"},{"e":"🔨","n":"hammer","s":"tool"},{"e":"🪓","n":"axe","s":"tool"},{"e":"⛏️","n":"pick","s":"tool"},{"e":"⚒️","n":"hammer and pick","s":"tool"},{"e":"🛠️","n":"hammer and wrench","s":"tool"},{"e":"🗡️","n":"dagger","s":"tool"},{"e":"⚔️","n":"crossed swords","s":"tool"},{"e":"💣","n":"bomb","s":"tool"},{"e":"🪃","n":"boomerang","s":"tool"},{"e":"🏹","n":"bow and arrow","s":"tool"},{"e":"🛡️","n":"shield","s":"tool"},{"e":"🪚","n":"carpentry saw","s":"tool"},{"e":"🔧","n":"wrench","s":"tool"},{"e":"🪛","n":"screwdriver","s":"tool"},{"e":"🔩","n":"nut and bolt","s":"tool"},{"e":"⚙️","n":"gear","s":"tool"},{"e":"🗜️","n":"clamp","s":"tool"},{"e":"⚖️","n":"balance scale","s":"tool"},{"e":"🦯","n":"white cane","s":"tool"},{"e":"🔗","n":"link","s":"tool"},{"e":"⛓️‍💥","n":"broken chain","s":"tool"},{"e":"⛓️","n":"chains","s":"tool"},{"e":"🪝","n":"hook","s":"tool"},{"e":"🧰","n":"toolbox","s":"tool"},{"e":"🧲","n":"magnet","s":"tool"},{"e":"🪜","n":"ladder","s":"tool"},{"e":"🪏","n":"shovel","s":"tool"},{"e":"⚗️","n":"alembic","s":"science"},{"e":"🧪","n":"test tube","s":"science"},{"e":"🧫","n":"petri dish","s":"science"},{"e":"🧬","n":"dna","s":"science"},{"e":"🔬","n":"microscope","s":"science"},{"e":"🔭","n":"telescope","s":"science"},{"e":"📡","n":"satellite antenna","s":"science"},{"e":"💉","n":"syringe","s":"medical"},{"e":"🩸","n":"drop of blood","s":"medical"},{"e":"💊","n":"pill","s":"medical"},{"e":"🩹","n":"adhesive bandage","s":"medical"},{"e":"🩼","n":"crutch","s":"medical"},{"e":"🩺","n":"stethoscope","s":"medical"},{"e":"🩻","n":"x-ray","s":"medical"},{"e":"🚪","n":"door","s":"household"},{"e":"🛗","n":"elevator","s":"household"},{"e":"🪞","n":"mirror","s":"household"},{"e":"🪟","n":"window","s":"household"},{"e":"🛏️","n":"bed","s":"household"},{"e":"🛋️","n":"couch and lamp","s":"household"},{"e":"🪑","n":"chair","s":"household"},{"e":"🚽","n":"toilet","s":"household"},{"e":"🪠","n":"plunger","s":"household"},{"e":"🚿","n":"shower","s":"household"},{"e":"🛁","n":"bathtub","s":"household"},{"e":"🪤","n":"mouse trap","s":"household"},{"e":"🪒","n":"razor","s":"household"},{"e":"🧴","n":"lotion bottle","s":"household"},{"e":"🧷","n":"safety pin","s":"household"},{"e":"🧹","n":"broom","s":"household"},{"e":"🧺","n":"basket","s":"household"},{"e":"🧻","n":"roll of paper","s":"household"},{"e":"🪣","n":"bucket","s":"household"},{"e":"🧼","n":"soap","s":"household"},{"e":"🫧","n":"bubbles","s":"household"},{"e":"🪥","n":"toothbrush","s":"household"},{"e":"🧽","n":"sponge","s":"household"},{"e":"🧯","n":"fire extinguisher","s":"household"},{"e":"🛒","n":"shopping cart","s":"household"},{"e":"🚬","n":"cigarette","s":"other-object"},{"e":"⚰️","n":"coffin","s":"other-object"},{"e":"🪦","n":"headstone","s":"other-object"},{"e":"⚱️","n":"funeral urn","s":"other-object"},{"e":"🧿","n":"nazar amulet","s":"other-object"},{"e":"🪬","n":"hamsa","s":"other-object"},{"e":"🗿","n":"moai","s":"other-object"},{"e":"🪧","n":"placard","s":"other-object"},{"e":"🪪","n":"identification card","s":"other-object"}],"SYMBOLS":[{"e":"🏧","n":"ATM sign","s":"transport-sign"},{"e":"🚮","n":"litter in bin sign","s":"transport-sign"},{"e":"🚰","n":"potable water","s":"transport-sign"},{"e":"♿","n":"wheelchair symbol","s":"transport-sign"},{"e":"🚹","n":"men’s room","s":"transport-sign"},{"e":"🚺","n":"women’s room","s":"transport-sign"},{"e":"🚻","n":"restroom","s":"transport-sign"},{"e":"🚼","n":"baby symbol","s":"transport-sign"},{"e":"🚾","n":"water closet","s":"transport-sign"},{"e":"🛂","n":"passport control","s":"transport-sign"},{"e":"🛃","n":"customs","s":"transport-sign"},{"e":"🛄","n":"baggage claim","s":"transport-sign"},{"e":"🛅","n":"left luggage","s":"transport-sign"},{"e":"⚠️","n":"warning","s":"warning"},{"e":"🚸","n":"children crossing","s":"warning"},{"e":"⛔","n":"no entry","s":"warning"},{"e":"🚫","n":"prohibited","s":"warning"},{"e":"🚳","n":"no bicycles","s":"warning"},{"e":"🚭","n":"no smoking","s":"warning"},{"e":"🚯","n":"no littering","s":"warning"},{"e":"🚱","n":"non-potable water","s":"warning"},{"e":"🚷","n":"no pedestrians","s":"warning"},{"e":"📵","n":"no mobile phones","s":"warning"},{"e":"🔞","n":"no one under eighteen","s":"warning"},{"e":"☢️","n":"radioactive","s":"warning"},{"e":"☣️","n":"biohazard","s":"warning"},{"e":"⬆️","n":"up arrow","s":"arrow"},{"e":"↗️","n":"up-right arrow","s":"arrow"},{"e":"➡️","n":"right arrow","s":"arrow"},{"e":"↘️","n":"down-right arrow","s":"arrow"},{"e":"⬇️","n":"down arrow","s":"arrow"},{"e":"↙️","n":"down-left arrow","s":"arrow"},{"e":"⬅️","n":"left arrow","s":"arrow"},{"e":"↖️","n":"up-left arrow","s":"arrow"},{"e":"↕️","n":"up-down arrow","s":"arrow"},{"e":"↔️","n":"left-right arrow","s":"arrow"},{"e":"↩️","n":"right arrow curving left","s":"arrow"},{"e":"↪️","n":"left arrow curving right","s":"arrow"},{"e":"⤴️","n":"right arrow curving up","s":"arrow"},{"e":"⤵️","n":"right arrow curving down","s":"arrow"},{"e":"🔃","n":"clockwise vertical arrows","s":"arrow"},{"e":"🔄","n":"counterclockwise arrows button","s":"arrow"},{"e":"🔙","n":"BACK arrow","s":"arrow"},{"e":"🔚","n":"END arrow","s":"arrow"},{"e":"🔛","n":"ON! arrow","s":"arrow"},{"e":"🔜","n":"SOON arrow","s":"arrow"},{"e":"🔝","n":"TOP arrow","s":"arrow"},{"e":"🛐","n":"place of worship","s":"religion"},{"e":"⚛️","n":"atom symbol","s":"religion"},{"e":"🕉️","n":"om","s":"religion"},{"e":"✡️","n":"star of David","s":"religion"},{"e":"☸️","n":"wheel of dharma","s":"religion"},{"e":"☯️","n":"yin yang","s":"religion"},{"e":"✝️","n":"latin cross","s":"religion"},{"e":"☦️","n":"orthodox cross","s":"religion"},{"e":"☪️","n":"star and crescent","s":"religion"},{"e":"☮️","n":"peace symbol","s":"religion"},{"e":"🕎","n":"menorah","s":"religion"},{"e":"🔯","n":"dotted six-pointed star","s":"religion"},{"e":"🪯","n":"khanda","s":"religion"},{"e":"♈","n":"Aries","s":"zodiac"},{"e":"♉","n":"Taurus","s":"zodiac"},{"e":"♊","n":"Gemini","s":"zodiac"},{"e":"♋","n":"Cancer","s":"zodiac"},{"e":"♌","n":"Leo","s":"zodiac"},{"e":"♍","n":"Virgo","s":"zodiac"},{"e":"♎","n":"Libra","s":"zodiac"},{"e":"♏","n":"Scorpio","s":"zodiac"},{"e":"♐","n":"Sagittarius","s":"zodiac"},{"e":"♑","n":"Capricorn","s":"zodiac"},{"e":"♒","n":"Aquarius","s":"zodiac"},{"e":"♓","n":"Pisces","s":"zodiac"},{"e":"⛎","n":"Ophiuchus","s":"zodiac"},{"e":"🔀","n":"shuffle tracks button","s":"av-symbol"},{"e":"🔁","n":"repeat button","s":"av-symbol"},{"e":"🔂","n":"repeat single button","s":"av-symbol"},{"e":"▶️","n":"play button","s":"av-symbol"},{"e":"⏩","n":"fast-forward button","s":"av-symbol"},{"e":"⏭️","n":"next track button","s":"av-symbol"},{"e":"⏯️","n":"play or pause button","s":"av-symbol"},{"e":"◀️","n":"reverse button","s":"av-symbol"},{"e":"⏪","n":"fast reverse button","s":"av-symbol"},{"e":"⏮️","n":"last track button","s":"av-symbol"},{"e":"🔼","n":"upwards button","s":"av-symbol"},{"e":"⏫","n":"fast up button","s":"av-symbol"},{"e":"🔽","n":"downwards button","s":"av-symbol"},{"e":"⏬","n":"fast down button","s":"av-symbol"},{"e":"⏸️","n":"pause button","s":"av-symbol"},{"e":"⏹️","n":"stop button","s":"av-symbol"},{"e":"⏺️","n":"record button","s":"av-symbol"},{"e":"⏏️","n":"eject button","s":"av-symbol"},{"e":"🎦","n":"cinema","s":"av-symbol"},{"e":"🔅","n":"dim button","s":"av-symbol"},{"e":"🔆","n":"bright button","s":"av-symbol"},{"e":"📶","n":"antenna bars","s":"av-symbol"},{"e":"🛜","n":"wireless","s":"av-symbol"},{"e":"📳","n":"vibration mode","s":"av-symbol"},{"e":"📴","n":"mobile phone off","s":"av-symbol"},{"e":"♀️","n":"female sign","s":"gender"},{"e":"♂️","n":"male sign","s":"gender"},{"e":"⚧️","n":"transgender symbol","s":"gender"},{"e":"✖️","n":"multiply","s":"math"},{"e":"➕","n":"plus","s":"math"},{"e":"➖","n":"minus","s":"math"},{"e":"➗","n":"divide","s":"math"},{"e":"🟰","n":"heavy equals sign","s":"math"},{"e":"♾️","n":"infinity","s":"math"},{"e":"‼️","n":"double exclamation mark","s":"punctuation"},{"e":"⁉️","n":"exclamation question mark","s":"punctuation"},{"e":"❓","n":"red question mark","s":"punctuation"},{"e":"❔","n":"white question mark","s":"punctuation"},{"e":"❕","n":"white exclamation mark","s":"punctuation"},{"e":"❗","n":"red exclamation mark","s":"punctuation"},{"e":"〰️","n":"wavy dash","s":"punctuation"},{"e":"💱","n":"currency exchange","s":"currency"},{"e":"💲","n":"heavy dollar sign","s":"currency"},{"e":"⚕️","n":"medical symbol","s":"other-symbol"},{"e":"♻️","n":"recycling symbol","s":"other-symbol"},{"e":"⚜️","n":"fleur-de-lis","s":"other-symbol"},{"e":"🔱","n":"trident emblem","s":"other-symbol"},{"e":"📛","n":"name badge","s":"other-symbol"},{"e":"🔰","n":"Japanese symbol for beginner","s":"other-symbol"},{"e":"⭕","n":"hollow red circle","s":"other-symbol"},{"e":"✅","n":"check mark button","s":"other-symbol"},{"e":"☑️","n":"check box with check","s":"other-symbol"},{"e":"✔️","n":"check mark","s":"other-symbol"},{"e":"❌","n":"cross mark","s":"other-symbol"},{"e":"❎","n":"cross mark button","s":"other-symbol"},{"e":"➰","n":"curly loop","s":"other-symbol"},{"e":"➿","n":"double curly loop","s":"other-symbol"},{"e":"〽️","n":"part alternation mark","s":"other-symbol"},{"e":"✳️","n":"eight-spoked asterisk","s":"other-symbol"},{"e":"✴️","n":"eight-pointed star","s":"other-symbol"},{"e":"❇️","n":"sparkle","s":"other-symbol"},{"e":"©️","n":"copyright","s":"other-symbol"},{"e":"®️","n":"registered","s":"other-symbol"},{"e":"™️","n":"trade mark","s":"other-symbol"},{"e":"🫟","n":"splatter","s":"other-symbol"},{"e":"#️⃣","n":"keycap: #","s":"keycap"},{"e":"*️⃣","n":"keycap: *","s":"keycap"},{"e":"0️⃣","n":"keycap: 0","s":"keycap"},{"e":"1️⃣","n":"keycap: 1","s":"keycap"},{"e":"2️⃣","n":"keycap: 2","s":"keycap"},{"e":"3️⃣","n":"keycap: 3","s":"keycap"},{"e":"4️⃣","n":"keycap: 4","s":"keycap"},{"e":"5️⃣","n":"keycap: 5","s":"keycap"},{"e":"6️⃣","n":"keycap: 6","s":"keycap"},{"e":"7️⃣","n":"keycap: 7","s":"keycap"},{"e":"8️⃣","n":"keycap: 8","s":"keycap"},{"e":"9️⃣","n":"keycap: 9","s":"keycap"},{"e":"🔟","n":"keycap: 10","s":"keycap"},{"e":"🔠","n":"input latin uppercase","s":"alphanum"},{"e":"🔡","n":"input latin lowercase","s":"alphanum"},{"e":"🔢","n":"input numbers","s":"alphanum"},{"e":"🔣","n":"input symbols","s":"alphanum"},{"e":"🔤","n":"input latin letters","s":"alphanum"},{"e":"🅰️","n":"A button (blood type)","s":"alphanum"},{"e":"🆎","n":"AB button (blood type)","s":"alphanum"},{"e":"🅱️","n":"B button (blood type)","s":"alphanum"},{"e":"🆑","n":"CL button","s":"alphanum"},{"e":"🆒","n":"COOL button","s":"alphanum"},{"e":"🆓","n":"FREE button","s":"alphanum"},{"e":"ℹ️","n":"information","s":"alphanum"},{"e":"🆔","n":"ID button","s":"alphanum"},{"e":"Ⓜ️","n":"circled M","s":"alphanum"},{"e":"🆕","n":"NEW button","s":"alphanum"},{"e":"🆖","n":"NG button","s":"alphanum"},{"e":"🅾️","n":"O button (blood type)","s":"alphanum"},{"e":"🆗","n":"OK button","s":"alphanum"},{"e":"🅿️","n":"P button","s":"alphanum"},{"e":"🆘","n":"SOS button","s":"alphanum"},{"e":"🆙","n":"UP! button","s":"alphanum"},{"e":"🆚","n":"VS button","s":"alphanum"},{"e":"🈁","n":"Japanese “here” button","s":"alphanum"},{"e":"🈂️","n":"Japanese “service charge” button","s":"alphanum"},{"e":"🈷️","n":"Japanese “monthly amount” button","s":"alphanum"},{"e":"🈶","n":"Japanese “not free of charge” button","s":"alphanum"},{"e":"🈯","n":"Japanese “reserved” button","s":"alphanum"},{"e":"🉐","n":"Japanese “bargain” button","s":"alphanum"},{"e":"🈹","n":"Japanese “discount” button","s":"alphanum"},{"e":"🈚","n":"Japanese “free of charge” button","s":"alphanum"},{"e":"🈲","n":"Japanese “prohibited” button","s":"alphanum"},{"e":"🉑","n":"Japanese “acceptable” button","s":"alphanum"},{"e":"🈸","n":"Japanese “application” button","s":"alphanum"},{"e":"🈴","n":"Japanese “passing grade” button","s":"alphanum"},{"e":"🈳","n":"Japanese “vacancy” button","s":"alphanum"},{"e":"㊗️","n":"Japanese “congratulations” button","s":"alphanum"},{"e":"㊙️","n":"Japanese “secret” button","s":"alphanum"},{"e":"🈺","n":"Japanese “open for business” button","s":"alphanum"},{"e":"🈵","n":"Japanese “no vacancy” button","s":"alphanum"},{"e":"🔴","n":"red circle","s":"geometric"},{"e":"🟠","n":"orange circle","s":"geometric"},{"e":"🟡","n":"yellow circle","s":"geometric"},{"e":"🟢","n":"green circle","s":"geometric"},{"e":"🔵","n":"blue circle","s":"geometric"},{"e":"🟣","n":"purple circle","s":"geometric"},{"e":"🟤","n":"brown circle","s":"geometric"},{"e":"⚫","n":"black circle","s":"geometric"},{"e":"⚪","n":"white circle","s":"geometric"},{"e":"🟥","n":"red square","s":"geometric"},{"e":"🟧","n":"orange square","s":"geometric"},{"e":"🟨","n":"yellow square","s":"geometric"},{"e":"🟩","n":"green square","s":"geometric"},{"e":"🟦","n":"blue square","s":"geometric"},{"e":"🟪","n":"purple square","s":"geometric"},{"e":"🟫","n":"brown square","s":"geometric"},{"e":"⬛","n":"black large square","s":"geometric"},{"e":"⬜","n":"white large square","s":"geometric"},{"e":"◼️","n":"black medium square","s":"geometric"},{"e":"◻️","n":"white medium square","s":"geometric"},{"e":"◾","n":"black medium-small square","s":"geometric"},{"e":"◽","n":"white medium-small square","s":"geometric"},{"e":"▪️","n":"black small square","s":"geometric"},{"e":"▫️","n":"white small square","s":"geometric"},{"e":"🔶","n":"large orange diamond","s":"geometric"},{"e":"🔷","n":"large blue diamond","s":"geometric"},{"e":"🔸","n":"small orange diamond","s":"geometric"},{"e":"🔹","n":"small blue diamond","s":"geometric"},{"e":"🔺","n":"red triangle pointed up","s":"geometric"},{"e":"🔻","n":"red triangle pointed down","s":"geometric"},{"e":"💠","n":"diamond with a dot","s":"geometric"},{"e":"🔘","n":"radio button","s":"geometric"},{"e":"🔳","n":"white square button","s":"geometric"},{"e":"🔲","n":"black square button","s":"geometric"}],"FLAGS":[{"e":"🏁","n":"chequered flag","s":"flag"},{"e":"🚩","n":"triangular flag","s":"flag"},{"e":"🎌","n":"crossed flags","s":"flag"},{"e":"🏴","n":"black flag","s":"flag"},{"e":"🏳️","n":"white flag","s":"flag"},{"e":"🏳️‍🌈","n":"rainbow flag","s":"flag"},{"e":"🏳️‍⚧️","n":"transgender flag","s":"flag"},{"e":"🏴‍☠️","n":"pirate flag","s":"flag"},{"e":"🇦🇨","n":"flag: Ascension Island","s":"country-flag"},{"e":"🇦🇩","n":"flag: Andorra","s":"country-flag"},{"e":"🇦🇪","n":"flag: United Arab Emirates","s":"country-flag"},{"e":"🇦🇫","n":"flag: Afghanistan","s":"country-flag"},{"e":"🇦🇬","n":"flag: Antigua & Barbuda","s":"country-flag"},{"e":"🇦🇮","n":"flag: Anguilla","s":"country-flag"},{"e":"🇦🇱","n":"flag: Albania","s":"country-flag"},{"e":"🇦🇲","n":"flag: Armenia","s":"country-flag"},{"e":"🇦🇴","n":"flag: Angola","s":"country-flag"},{"e":"🇦🇶","n":"flag: Antarctica","s":"country-flag"},{"e":"🇦🇷","n":"flag: Argentina","s":"country-flag"},{"e":"🇦🇸","n":"flag: American Samoa","s":"country-flag"},{"e":"🇦🇹","n":"flag: Austria","s":"country-flag"},{"e":"🇦🇺","n":"flag: Australia","s":"country-flag"},{"e":"🇦🇼","n":"flag: Aruba","s":"country-flag"},{"e":"🇦🇽","n":"flag: Åland Islands","s":"country-flag"},{"e":"🇦🇿","n":"flag: Azerbaijan","s":"country-flag"},{"e":"🇧🇦","n":"flag: Bosnia & Herzegovina","s":"country-flag"},{"e":"🇧🇧","n":"flag: Barbados","s":"country-flag"},{"e":"🇧🇩","n":"flag: Bangladesh","s":"country-flag"},{"e":"🇧🇪","n":"flag: Belgium","s":"country-flag"},{"e":"🇧🇫","n":"flag: Burkina Faso","s":"country-flag"},{"e":"🇧🇬","n":"flag: Bulgaria","s":"country-flag"},{"e":"🇧🇭","n":"flag: Bahrain","s":"country-flag"},{"e":"🇧🇮","n":"flag: Burundi","s":"country-flag"},{"e":"🇧🇯","n":"flag: Benin","s":"country-flag"},{"e":"🇧🇱","n":"flag: St. Barthélemy","s":"country-flag"},{"e":"🇧🇲","n":"flag: Bermuda","s":"country-flag"},{"e":"🇧🇳","n":"flag: Brunei","s":"country-flag"},{"e":"🇧🇴","n":"flag: Bolivia","s":"country-flag"},{"e":"🇧🇶","n":"flag: Caribbean Netherlands","s":"country-flag"},{"e":"🇧🇷","n":"flag: Brazil","s":"country-flag"},{"e":"🇧🇸","n":"flag: Bahamas","s":"country-flag"},{"e":"🇧🇹","n":"flag: Bhutan","s":"country-flag"},{"e":"🇧🇻","n":"flag: Bouvet Island","s":"country-flag"},{"e":"🇧🇼","n":"flag: Botswana","s":"country-flag"},{"e":"🇧🇾","n":"flag: Belarus","s":"country-flag"},{"e":"🇧🇿","n":"flag: Belize","s":"country-flag"},{"e":"🇨🇦","n":"flag: Canada","s":"country-flag"},{"e":"🇨🇨","n":"flag: Cocos (Keeling) Islands","s":"country-flag"},{"e":"🇨🇩","n":"flag: Congo - Kinshasa","s":"country-flag"},{"e":"🇨🇫","n":"flag: Central African Republic","s":"country-flag"},{"e":"🇨🇬","n":"flag: Congo - Brazzaville","s":"country-flag"},{"e":"🇨🇭","n":"flag: Switzerland","s":"country-flag"},{"e":"🇨🇮","n":"flag: Côte d’Ivoire","s":"country-flag"},{"e":"🇨🇰","n":"flag: Cook Islands","s":"country-flag"},{"e":"🇨🇱","n":"flag: Chile","s":"country-flag"},{"e":"🇨🇲","n":"flag: Cameroon","s":"country-flag"},{"e":"🇨🇳","n":"flag: China","s":"country-flag"},{"e":"🇨🇴","n":"flag: Colombia","s":"country-flag"},{"e":"🇨🇵","n":"flag: Clipperton Island","s":"country-flag"},{"e":"🇨🇶","n":"flag: Sark","s":"country-flag"},{"e":"🇨🇷","n":"flag: Costa Rica","s":"country-flag"},{"e":"🇨🇺","n":"flag: Cuba","s":"country-flag"},{"e":"🇨🇻","n":"flag: Cape Verde","s":"country-flag"},{"e":"🇨🇼","n":"flag: Curaçao","s":"country-flag"},{"e":"🇨🇽","n":"flag: Christmas Island","s":"country-flag"},{"e":"🇨🇾","n":"flag: Cyprus","s":"country-flag"},{"e":"🇨🇿","n":"flag: Czechia","s":"country-flag"},{"e":"🇩🇪","n":"flag: Germany","s":"country-flag"},{"e":"🇩🇬","n":"flag: Diego Garcia","s":"country-flag"},{"e":"🇩🇯","n":"flag: Djibouti","s":"country-flag"},{"e":"🇩🇰","n":"flag: Denmark","s":"country-flag"},{"e":"🇩🇲","n":"flag: Dominica","s":"country-flag"},{"e":"🇩🇴","n":"flag: Dominican Republic","s":"country-flag"},{"e":"🇩🇿","n":"flag: Algeria","s":"country-flag"},{"e":"🇪🇦","n":"flag: Ceuta & Melilla","s":"country-flag"},{"e":"🇪🇨","n":"flag: Ecuador","s":"country-flag"},{"e":"🇪🇪","n":"flag: Estonia","s":"country-flag"},{"e":"🇪🇬","n":"flag: Egypt","s":"country-flag"},{"e":"🇪🇭","n":"flag: Western Sahara","s":"country-flag"},{"e":"🇪🇷","n":"flag: Eritrea","s":"country-flag"},{"e":"🇪🇸","n":"flag: Spain","s":"country-flag"},{"e":"🇪🇹","n":"flag: Ethiopia","s":"country-flag"},{"e":"🇪🇺","n":"flag: European Union","s":"country-flag"},{"e":"🇫🇮","n":"flag: Finland","s":"country-flag"},{"e":"🇫🇯","n":"flag: Fiji","s":"country-flag"},{"e":"🇫🇰","n":"flag: Falkland Islands","s":"country-flag"},{"e":"🇫🇲","n":"flag: Micronesia","s":"country-flag"},{"e":"🇫🇴","n":"flag: Faroe Islands","s":"country-flag"},{"e":"🇫🇷","n":"flag: France","s":"country-flag"},{"e":"🇬🇦","n":"flag: Gabon","s":"country-flag"},{"e":"🇬🇧","n":"flag: United Kingdom","s":"country-flag"},{"e":"🇬🇩","n":"flag: Grenada","s":"country-flag"},{"e":"🇬🇪","n":"flag: Georgia","s":"country-flag"},{"e":"🇬🇫","n":"flag: French Guiana","s":"country-flag"},{"e":"🇬🇬","n":"flag: Guernsey","s":"country-flag"},{"e":"🇬🇭","n":"flag: Ghana","s":"country-flag"},{"e":"🇬🇮","n":"flag: Gibraltar","s":"country-flag"},{"e":"🇬🇱","n":"flag: Greenland","s":"country-flag"},{"e":"🇬🇲","n":"flag: Gambia","s":"country-flag"},{"e":"🇬🇳","n":"flag: Guinea","s":"country-flag"},{"e":"🇬🇵","n":"flag: Guadeloupe","s":"country-flag"},{"e":"🇬🇶","n":"flag: Equatorial Guinea","s":"country-flag"},{"e":"🇬🇷","n":"flag: Greece","s":"country-flag"},{"e":"🇬🇸","n":"flag: South Georgia & South Sandwich Islands","s":"country-flag"},{"e":"🇬🇹","n":"flag: Guatemala","s":"country-flag"},{"e":"🇬🇺","n":"flag: Guam","s":"country-flag"},{"e":"🇬🇼","n":"flag: Guinea-Bissau","s":"country-flag"},{"e":"🇬🇾","n":"flag: Guyana","s":"country-flag"},{"e":"🇭🇰","n":"flag: Hong Kong SAR China","s":"country-flag"},{"e":"🇭🇲","n":"flag: Heard Island & McDonald Islands","s":"country-flag"},{"e":"🇭🇳","n":"flag: Honduras","s":"country-flag"},{"e":"🇭🇷","n":"flag: Croatia","s":"country-flag"},{"e":"🇭🇹","n":"flag: Haiti","s":"country-flag"},{"e":"🇭🇺","n":"flag: Hungary","s":"country-flag"},{"e":"🇮🇨","n":"flag: Canary Islands","s":"country-flag"},{"e":"🇮🇩","n":"flag: Indonesia","s":"country-flag"},{"e":"🇮🇪","n":"flag: Ireland","s":"country-flag"},{"e":"🇮🇱","n":"flag: Israel","s":"country-flag"},{"e":"🇮🇲","n":"flag: Isle of Man","s":"country-flag"},{"e":"🇮🇳","n":"flag: India","s":"country-flag"},{"e":"🇮🇴","n":"flag: British Indian Ocean Territory","s":"country-flag"},{"e":"🇮🇶","n":"flag: Iraq","s":"country-flag"},{"e":"🇮🇷","n":"flag: Iran","s":"country-flag"},{"e":"🇮🇸","n":"flag: Iceland","s":"country-flag"},{"e":"🇮🇹","n":"flag: Italy","s":"country-flag"},{"e":"🇯🇪","n":"flag: Jersey","s":"country-flag"},{"e":"🇯🇲","n":"flag: Jamaica","s":"country-flag"},{"e":"🇯🇴","n":"flag: Jordan","s":"country-flag"},{"e":"🇯🇵","n":"flag: Japan","s":"country-flag"},{"e":"🇰🇪","n":"flag: Kenya","s":"country-flag"},{"e":"🇰🇬","n":"flag: Kyrgyzstan","s":"country-flag"},{"e":"🇰🇭","n":"flag: Cambodia","s":"country-flag"},{"e":"🇰🇮","n":"flag: Kiribati","s":"country-flag"},{"e":"🇰🇲","n":"flag: Comoros","s":"country-flag"},{"e":"🇰🇳","n":"flag: St. Kitts & Nevis","s":"country-flag"},{"e":"🇰🇵","n":"flag: North Korea","s":"country-flag"},{"e":"🇰🇷","n":"flag: South Korea","s":"country-flag"},{"e":"🇰🇼","n":"flag: Kuwait","s":"country-flag"},{"e":"🇰🇾","n":"flag: Cayman Islands","s":"country-flag"},{"e":"🇰🇿","n":"flag: Kazakhstan","s":"country-flag"},{"e":"🇱🇦","n":"flag: Laos","s":"country-flag"},{"e":"🇱🇧","n":"flag: Lebanon","s":"country-flag"},{"e":"🇱🇨","n":"flag: St. Lucia","s":"country-flag"},{"e":"🇱🇮","n":"flag: Liechtenstein","s":"country-flag"},{"e":"🇱🇰","n":"flag: Sri Lanka","s":"country-flag"},{"e":"🇱🇷","n":"flag: Liberia","s":"country-flag"},{"e":"🇱🇸","n":"flag: Lesotho","s":"country-flag"},{"e":"🇱🇹","n":"flag: Lithuania","s":"country-flag"},{"e":"🇱🇺","n":"flag: Luxembourg","s":"country-flag"},{"e":"🇱🇻","n":"flag: Latvia","s":"country-flag"},{"e":"🇱🇾","n":"flag: Libya","s":"country-flag"},{"e":"🇲🇦","n":"flag: Morocco","s":"country-flag"},{"e":"🇲🇨","n":"flag: Monaco","s":"country-flag"},{"e":"🇲🇩","n":"flag: Moldova","s":"country-flag"},{"e":"🇲🇪","n":"flag: Montenegro","s":"country-flag"},{"e":"🇲🇫","n":"flag: St. Martin","s":"country-flag"},{"e":"🇲🇬","n":"flag: Madagascar","s":"country-flag"},{"e":"🇲🇭","n":"flag: Marshall Islands","s":"country-flag"},{"e":"🇲🇰","n":"flag: North Macedonia","s":"country-flag"},{"e":"🇲🇱","n":"flag: Mali","s":"country-flag"},{"e":"🇲🇲","n":"flag: Myanmar (Burma)","s":"country-flag"},{"e":"🇲🇳","n":"flag: Mongolia","s":"country-flag"},{"e":"🇲🇴","n":"flag: Macao SAR China","s":"country-flag"},{"e":"🇲🇵","n":"flag: Northern Mariana Islands","s":"country-flag"},{"e":"🇲🇶","n":"flag: Martinique","s":"country-flag"},{"e":"🇲🇷","n":"flag: Mauritania","s":"country-flag"},{"e":"🇲🇸","n":"flag: Montserrat","s":"country-flag"},{"e":"🇲🇹","n":"flag: Malta","s":"country-flag"},{"e":"🇲🇺","n":"flag: Mauritius","s":"country-flag"},{"e":"🇲🇻","n":"flag: Maldives","s":"country-flag"},{"e":"🇲🇼","n":"flag: Malawi","s":"country-flag"},{"e":"🇲🇽","n":"flag: Mexico","s":"country-flag"},{"e":"🇲🇾","n":"flag: Malaysia","s":"country-flag"},{"e":"🇲🇿","n":"flag: Mozambique","s":"country-flag"},{"e":"🇳🇦","n":"flag: Namibia","s":"country-flag"},{"e":"🇳🇨","n":"flag: New Caledonia","s":"country-flag"},{"e":"🇳🇪","n":"flag: Niger","s":"country-flag"},{"e":"🇳🇫","n":"flag: Norfolk Island","s":"country-flag"},{"e":"🇳🇬","n":"flag: Nigeria","s":"country-flag"},{"e":"🇳🇮","n":"flag: Nicaragua","s":"country-flag"},{"e":"🇳🇱","n":"flag: Netherlands","s":"country-flag"},{"e":"🇳🇴","n":"flag: Norway","s":"country-flag"},{"e":"🇳🇵","n":"flag: Nepal","s":"country-flag"},{"e":"🇳🇷","n":"flag: Nauru","s":"country-flag"},{"e":"🇳🇺","n":"flag: Niue","s":"country-flag"},{"e":"🇳🇿","n":"flag: New Zealand","s":"country-flag"},{"e":"🇴🇲","n":"flag: Oman","s":"country-flag"},{"e":"🇵🇦","n":"flag: Panama","s":"country-flag"},{"e":"🇵🇪","n":"flag: Peru","s":"country-flag"},{"e":"🇵🇫","n":"flag: French Polynesia","s":"country-flag"},{"e":"🇵🇬","n":"flag: Papua New Guinea","s":"country-flag"},{"e":"🇵🇭","n":"flag: Philippines","s":"country-flag"},{"e":"🇵🇰","n":"flag: Pakistan","s":"country-flag"},{"e":"🇵🇱","n":"flag: Poland","s":"country-flag"},{"e":"🇵🇲","n":"flag: St. Pierre & Miquelon","s":"country-flag"},{"e":"🇵🇳","n":"flag: Pitcairn Islands","s":"country-flag"},{"e":"🇵🇷","n":"flag: Puerto Rico","s":"country-flag"},{"e":"🇵🇸","n":"flag: Palestinian Territories","s":"country-flag"},{"e":"🇵🇹","n":"flag: Portugal","s":"country-flag"},{"e":"🇵🇼","n":"flag: Palau","s":"country-flag"},{"e":"🇵🇾","n":"flag: Paraguay","s":"country-flag"},{"e":"🇶🇦","n":"flag: Qatar","s":"country-flag"},{"e":"🇷🇪","n":"flag: Réunion","s":"country-flag"},{"e":"🇷🇴","n":"flag: Romania","s":"country-flag"},{"e":"🇷🇸","n":"flag: Serbia","s":"country-flag"},{"e":"🇷🇺","n":"flag: Russia","s":"country-flag"},{"e":"🇷🇼","n":"flag: Rwanda","s":"country-flag"},{"e":"🇸🇦","n":"flag: Saudi Arabia","s":"country-flag"},{"e":"🇸🇧","n":"flag: Solomon Islands","s":"country-flag"},{"e":"🇸🇨","n":"flag: Seychelles","s":"country-flag"},{"e":"🇸🇩","n":"flag: Sudan","s":"country-flag"},{"e":"🇸🇪","n":"flag: Sweden","s":"country-flag"},{"e":"🇸🇬","n":"flag: Singapore","s":"country-flag"},{"e":"🇸🇭","n":"flag: St. Helena, Ascension & Tristan da Cunha","s":"country-flag"},{"e":"🇸🇮","n":"flag: Slovenia","s":"country-flag"},{"e":"🇸🇯","n":"flag: Svalbard & Jan Mayen","s":"country-flag"},{"e":"🇸🇰","n":"flag: Slovakia","s":"country-flag"},{"e":"🇸🇱","n":"flag: Sierra Leone","s":"country-flag"},{"e":"🇸🇲","n":"flag: San Marino","s":"country-flag"},{"e":"🇸🇳","n":"flag: Senegal","s":"country-flag"},{"e":"🇸🇴","n":"flag: Somalia","s":"country-flag"},{"e":"🇸🇷","n":"flag: Suriname","s":"country-flag"},{"e":"🇸🇸","n":"flag: South Sudan","s":"country-flag"},{"e":"🇸🇹","n":"flag: São Tomé & Príncipe","s":"country-flag"},{"e":"🇸🇻","n":"flag: El Salvador","s":"country-flag"},{"e":"🇸🇽","n":"flag: Sint Maarten","s":"country-flag"},{"e":"🇸🇾","n":"flag: Syria","s":"country-flag"},{"e":"🇸🇿","n":"flag: Eswatini","s":"country-flag"},{"e":"🇹🇦","n":"flag: Tristan da Cunha","s":"country-flag"},{"e":"🇹🇨","n":"flag: Turks & Caicos Islands","s":"country-flag"},{"e":"🇹🇩","n":"flag: Chad","s":"country-flag"},{"e":"🇹🇫","n":"flag: French Southern and Antarctic Lands","s":"country-flag"},{"e":"🇹🇬","n":"flag: Togo","s":"country-flag"},{"e":"🇹🇭","n":"flag: Thailand","s":"country-flag"},{"e":"🇹🇯","n":"flag: Tajikistan","s":"country-flag"},{"e":"🇹🇰","n":"flag: Tokelau","s":"country-flag"},{"e":"🇹🇱","n":"flag: Timor-Leste","s":"country-flag"},{"e":"🇹🇲","n":"flag: Turkmenistan","s":"country-flag"},{"e":"🇹🇳","n":"flag: Tunisia","s":"country-flag"},{"e":"🇹🇴","n":"flag: Tonga","s":"country-flag"},{"e":"🇹🇷","n":"flag: Türkiye","s":"country-flag"},{"e":"🇹🇹","n":"flag: Trinidad & Tobago","s":"country-flag"},{"e":"🇹🇻","n":"flag: Tuvalu","s":"country-flag"},{"e":"🇹🇼","n":"flag: Taiwan","s":"country-flag"},{"e":"🇹🇿","n":"flag: Tanzania","s":"country-flag"},{"e":"🇺🇦","n":"flag: Ukraine","s":"country-flag"},{"e":"🇺🇬","n":"flag: Uganda","s":"country-flag"},{"e":"🇺🇲","n":"flag: U.S. Outlying Islands","s":"country-flag"},{"e":"🇺🇳","n":"flag: United Nations","s":"country-flag"},{"e":"🇺🇸","n":"flag: United States","s":"country-flag"},{"e":"🇺🇾","n":"flag: Uruguay","s":"country-flag"},{"e":"🇺🇿","n":"flag: Uzbekistan","s":"country-flag"},{"e":"🇻🇦","n":"flag: Vatican City","s":"country-flag"},{"e":"🇻🇨","n":"flag: St. Vincent & Grenadines","s":"country-flag"},{"e":"🇻🇪","n":"flag: Venezuela","s":"country-flag"},{"e":"🇻🇬","n":"flag: British Virgin Islands","s":"country-flag"},{"e":"🇻🇮","n":"flag: U.S. Virgin Islands","s":"country-flag"},{"e":"🇻🇳","n":"flag: Vietnam","s":"country-flag"},{"e":"🇻🇺","n":"flag: Vanuatu","s":"country-flag"},{"e":"🇼🇫","n":"flag: Wallis & Futuna","s":"country-flag"},{"e":"🇼🇸","n":"flag: Samoa","s":"country-flag"},{"e":"🇽🇰","n":"flag: Kosovo","s":"country-flag"},{"e":"🇾🇪","n":"flag: Yemen","s":"country-flag"},{"e":"🇾🇹","n":"flag: Mayotte","s":"country-flag"},{"e":"🇿🇦","n":"flag: South Africa","s":"country-flag"},{"e":"🇿🇲","n":"flag: Zambia","s":"country-flag"},{"e":"🇿🇼","n":"flag: Zimbabwe","s":"country-flag"},{"e":"🏴󠁧󠁢󠁥󠁮󠁧󠁿","n":"flag: England","s":"subdivision-flag"},{"e":"🏴󠁧󠁢󠁳󠁣󠁴󠁿","n":"flag: Scotland","s":"subdivision-flag"},{"e":"🏴󠁧󠁢󠁷󠁬󠁳󠁿","n":"flag: Wales","s":"subdivision-flag"}]};

        /* ======================================================
       AUSTRALIA BROKEN EMOJI FILTER V1 START

       Current Windows emoji font does not yet render:
       U+1FAF9 = leftwards thumb sign
       U+1FAFA = rightwards thumb sign

       Remove those base emoji and all skin-tone variants.
       ====================================================== */

    Object.keys(
        categories
    ).forEach(
        function (categoryName) {

            categories[categoryName] =
                (
                    categories[categoryName] ||
                    []
                ).filter(
                    function (item) {

                        if (
                            !item ||
                            !item.e
                        ) {
                            return false;
                        }

                        const firstCodePoint =
                            item.e.codePointAt(0);

                        return (
                            firstCodePoint !== 0x1FAF9 &&
                            firstCodePoint !== 0x1FAFA
                        );
                    }
                );
        }
    );

    /* AUSTRALIA BROKEN EMOJI FILTER V1 END */


    const categoryNames =
        Object.keys(categories).filter(
            function (name) {

                return (
                    name !== "FLAGS"
                );
            }
        );

    let currentCategory =
        categoryNames.includes("SMILEYS")
            ? "SMILEYS"
            : categoryNames[0];

    let picker = null;
    let button = null;
    let searchBox = null;

    /* ======================================================
       AUSTRALIA EMOTE SENTINEL GUARD V1 START

       Emote buttons must contain Unicode text ONLY.
       The site-wide Sentinel icon system is not permitted
       to inject Australia Control Center icons here.
       ====================================================== */

    let emoteSentinelObserver = null;


    function scrubSentinelIconsFromEmotes() {

        if (!picker) {
            return;
        }

        picker
            .querySelectorAll(
                ".ag-v2-emote[data-ag-emoji]"
            )
            .forEach(
                function (emote) {

                    const emoji =
                        emote.dataset.agEmoji || "";

                    /*
                     * An emote button is intentionally
                     * text-only.
                     *
                     * If Sentinel inserted an IMG, SPAN,
                     * SVG, icon container, etc., restore
                     * the button to the Unicode text.
                     */

                    const isRealFlag =
                        emote.classList.contains(
                            "ag-v2-real-flag"
                        );


                    if (isRealFlag) {

                        /*
                         * Real country flag images are the
                         * one permitted child element.
                         *
                         * Remove anything else Sentinel
                         * tries to inject.
                         */

                        Array.from(
                            emote.children
                        ).forEach(
                            function (child) {

                                if (
                                    !child.classList.contains(
                                        "ag-v2-country-flag"
                                    )
                                ) {

                                    child.remove();
                                }
                            }
                        );

                    } else if (
                        emote.children.length > 0 ||
                        emote.textContent !== emoji
                    ) {

                        emote.textContent =
                            emoji;
                    }


                    /*
                     * Remove Sentinel classes if the
                     * auto-icon system attached them
                     * directly to the button.
                     */

                    Array.from(
                        emote.classList
                    ).forEach(
                        function (className) {

                            if (
                                className.indexOf(
                                    "ag-sentinel-"
                                ) === 0
                            ) {

                                emote.classList.remove(
                                    className
                                );
                            }
                        }
                    );


                    /*
                     * Remove an inline Sentinel background
                     * if one was applied directly.
                     */

                    const inlineStyle =
                        (
                            emote.getAttribute(
                                "style"
                            ) || ""
                        ).toLowerCase();

                    if (
                        inlineStyle.includes(
                            "sentinel"
                        ) ||
                        inlineStyle.includes(
                            "assets/icons"
                        )
                    ) {

                        emote.style.setProperty(
                            "background-image",
                            "none",
                            "important"
                        );

                        emote.style.setProperty(
                            "mask-image",
                            "none",
                            "important"
                        );

                        emote.style.setProperty(
                            "-webkit-mask-image",
                            "none",
                            "important"
                        );
                    }
                }
            );
    }


    function installEmoteSentinelGuard() {

        if (!picker) {
            return;
        }


        /*
         * CSS protection.
         */

        if (
            !document.getElementById(
                "agV2EmoteSentinelGuardStyle"
            )
        ) {

            const style =
                document.createElement(
                    "style"
                );

            style.id =
                "agV2EmoteSentinelGuardStyle";

            style.textContent = `

#agV2EmotesPicker .ag-v2-emote
    > .ag-sentinel-auto-control-icon,

#agV2EmotesPicker .ag-v2-emote
    > .ag-sentinel-direct-icon,

#agV2EmotesPicker .ag-v2-emote
    [class*="ag-sentinel-"] {

    display: none !important;

    width: 0 !important;
    height: 0 !important;

    margin: 0 !important;
    padding: 0 !important;

    background: none !important;
    background-image: none !important;

    mask: none !important;
    -webkit-mask: none !important;
}


#agV2EmotesPicker .ag-v2-emote::before,
#agV2EmotesPicker .ag-v2-emote::after {

    content: none !important;
    display: none !important;

    background: none !important;
    background-image: none !important;

    mask: none !important;
    -webkit-mask: none !important;
}

            `;

            document.head.appendChild(
                style
            );
        }


        if (emoteSentinelObserver) {
            return;
        }


        /*
         * Watch only the EMOTES picker.
         *
         * If the sitewide icon system inserts one of
         * your Control Center icons later, remove it
         * immediately.
         */

        emoteSentinelObserver =
            new MutationObserver(
                function () {

                    scrubSentinelIconsFromEmotes();
                }
            );


        emoteSentinelObserver.observe(
            picker,
            {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: [
                    "class",
                    "style"
                ]
            }
        );


        scrubSentinelIconsFromEmotes();
    }

    /* AUSTRALIA EMOTE SENTINEL GUARD V1 END */


    function allItems() {

        const result = [];

        categoryNames.forEach(
            function (category) {

                (categories[category] || [])
                    .forEach(
                        function (item) {

                            result.push(item);
                        }
                    );
            }
        );

        return result;
    }


    const completeCatalog =
        allItems();


    /* ======================================================
       AUSTRALIA REAL FLAG IMAGES V1 START
       ====================================================== */

    function australiaRegionalFlagCode(
        emoji
    ) {

        const chars =
            Array.from(
                emoji || ""
            );


        if (chars.length !== 2) {
            return "";
        }


        const first =
            chars[0].codePointAt(0);

        const second =
            chars[1].codePointAt(0);


        if (
            first < 0x1F1E6 ||
            first > 0x1F1FF ||
            second < 0x1F1E6 ||
            second > 0x1F1FF
        ) {

            return "";
        }


        return (
            String.fromCharCode(
                97 +
                first -
                0x1F1E6
            ) +

            String.fromCharCode(
                97 +
                second -
                0x1F1E6
            )
        );
    }

    /* AUSTRALIA REAL FLAG IMAGES V1 END */

    function getMessageBox() {

        return document.getElementById(
            "agV2Message"
        );
    }


    function insertEmoji(emoji) {

        const box =
            getMessageBox();

        if (!box) {
            return;
        }

        const start =
            typeof box.selectionStart === "number"
                ? box.selectionStart
                : box.value.length;

        const end =
            typeof box.selectionEnd === "number"
                ? box.selectionEnd
                : start;

        box.value =
            box.value.slice(0, start) +
            emoji +
            box.value.slice(end);

        const next =
            start + emoji.length;

        box.focus();

        box.setSelectionRange(
            next,
            next
        );

        box.dispatchEvent(
            new Event(
                "input",
                {
                    bubbles: true
                }
            )
        );
    }


    function renderItems() {

        const grid =
            document.getElementById(
                "agV2EmotesGrid"
            );

        const count =
            document.getElementById(
                "agV2EmotesCount"
            );

        if (!grid) {
            return;
        }

        grid.innerHTML = "";

        let items =
            categories[currentCategory] || [];

        const query =
            searchBox
                ? searchBox.value
                    .trim()
                    .toLowerCase()
                : "";

        if (query !== "") {

            items =
                completeCatalog.filter(
                    function (item) {

                        return (
                            item.e.includes(query) ||
                            item.n
                                .toLowerCase()
                                .includes(query) ||
                            item.s
                                .toLowerCase()
                                .includes(query)
                        );
                    }
                );
        }


        if (count) {

            count.textContent =
                items.length +
                " / " +
                completeCatalog.length;
        }


        document
            .querySelectorAll(
                ".ag-v2-emotes-tab"
            )
            .forEach(
                function (tab) {

                    tab.classList.toggle(
                        "active",
                        query === "" &&
                        tab.dataset.category ===
                            currentCategory
                    );
                }
            );


        if (items.length === 0) {

            const empty =
                document.createElement(
                    "div"
                );

            empty.className =
                "ag-v2-emotes-empty";

            empty.textContent =
                "NO EMOTES FOUND";

            grid.appendChild(
                empty
            );

            return;
        }


        items.forEach(
            function (item) {

                const emote =
                    document.createElement(
                        "button"
                    );

                emote.type =
                    "button";

                emote.className =
                    "ag-v2-emote";

                const flagCode =
                    (
                        currentCategory === "FLAGS"
                    )
                        ? australiaRegionalFlagCode(
                            item.e
                        )
                        : "";


                emote.dataset.agEmoji =
                    item.e;


                if (flagCode !== "") {

                    emote.classList.add(
                        "ag-v2-real-flag"
                    );


                    const flagImage =
                        document.createElement(
                            "img"
                        );


                    flagImage.className =
                        "ag-v2-country-flag";


                    flagImage.src =
                        "/Other/assets/emoji-flags/" +
                        flagCode +
                        ".png";


                    flagImage.alt =
                        item.e;


                    flagImage.title =
                        item.n;


                    flagImage.loading =
                        "lazy";


                    flagImage.decoding =
                        "async";


                    flagImage.addEventListener(
                        "error",
                        function () {

                            emote.classList.remove(
                                "ag-v2-real-flag"
                            );

                            emote.textContent =
                                item.e;
                        },
                        {
                            once: true
                        }
                    );


                    emote.appendChild(
                        flagImage
                    );

                } else {

                    emote.textContent =
                        item.e;
                }

                emote.title =
                    item.n;

                emote.setAttribute(
                    "aria-label",
                    item.n
                );

                emote.addEventListener(
                    "click",
                    function () {

                        insertEmoji(
                            item.e
                        );
                    }
                );

                grid.appendChild(
                    emote
                );
            }
        );
    }


    function createPicker() {

        if (picker) {
            return picker;
        }


        picker =
            document.createElement(
                "div"
            );

        picker.id =
            "agV2EmotesPicker";

        picker.hidden =
            true;

        picker.setAttribute(
            "role",
            "dialog"
        );

        picker.setAttribute(
            "aria-label",
            "Emotes"
        );


        const head =
            document.createElement(
                "div"
            );

        head.className =
            "ag-v2-emotes-head";


        const titleWrap =
            document.createElement(
                "div"
            );


        const title =
            document.createElement(
                "div"
            );

        title.className =
            "ag-v2-emotes-title";

        title.textContent =
            "EMOTES";


        const count =
            document.createElement(
                "div"
            );

        count.id =
            "agV2EmotesCount";

        count.className =
            "ag-v2-emotes-count";


        titleWrap.appendChild(title);
        titleWrap.appendChild(count);


        const close =
            document.createElement(
                "button"
            );

        close.id =
            "agV2EmotesClose";

        close.type =
            "button";

        close.textContent =
            "×";

        close.addEventListener(
            "click",
            closePicker
        );


        head.appendChild(
            titleWrap
        );

        head.appendChild(
            close
        );


        const searchWrap =
            document.createElement(
                "div"
            );

        searchWrap.className =
            "ag-v2-emotes-search-wrap";


        searchBox =
            document.createElement(
                "input"
            );

        searchBox.id =
            "agV2EmotesSearch";

        searchBox.type =
            "search";

        searchBox.placeholder =
            "Search emotes...";

        searchBox.autocomplete =
            "off";

        searchBox.addEventListener(
            "input",
            renderItems
        );


        searchWrap.appendChild(
            searchBox
        );


        const tabs =
            document.createElement(
                "div"
            );

        tabs.className =
            "ag-v2-emotes-tabs";


        categoryNames.forEach(
            function (name) {

                if (
                    !categories[name] ||
                    categories[name].length === 0
                ) {
                    return;
                }

                const tab =
                    document.createElement(
                        "button"
                    );

                tab.type =
                    "button";

                tab.className =
                    "ag-v2-emotes-tab";

                tab.dataset.category =
                    name;

                tab.textContent =
                    name;

                tab.addEventListener(
                    "click",
                    function () {

                        currentCategory =
                            name;

                        searchBox.value =
                            "";

                        renderItems();
                    }
                );

                tabs.appendChild(
                    tab
                );
            }
        );


        const grid =
            document.createElement(
                "div"
            );

        grid.id =
            "agV2EmotesGrid";

        grid.className =
            "ag-v2-emotes-grid";


        picker.appendChild(head);
        picker.appendChild(searchWrap);
        picker.appendChild(tabs);
        picker.appendChild(grid);

        document.body.appendChild(
            picker
        );

        installEmoteSentinelGuard();

        renderItems();

        return picker;
    }


    function positionPicker() {

        if (
            !picker ||
            picker.hidden ||
            !button
        ) {
            return;
        }

        const rect =
            button.getBoundingClientRect();

        const width =
            Math.min(
                520,
                window.innerWidth - 24
            );

        let left =
            rect.right - width;

        if (left < 12) {
            left = 12;
        }

        if (
            left + width >
            window.innerWidth - 12
        ) {

            left =
                window.innerWidth -
                width -
                12;
        }

        picker.style.left =
            left + "px";

        picker.style.top =
            "auto";

        picker.style.bottom =
            (
                window.innerHeight -
                rect.top +
                8
            ) +
            "px";


        const pickerRect =
            picker.getBoundingClientRect();

        if (
            pickerRect.top < 12
        ) {

            picker.style.bottom =
                "auto";

            picker.style.top =
                Math.max(
                    12,
                    Math.min(
                        rect.bottom + 8,
                        window.innerHeight -
                        pickerRect.height -
                        12
                    )
                ) +
                "px";
        }
    }


    function openPicker() {

        createPicker();

        picker.hidden =
            false;

        if (button) {

            button.setAttribute(
                "aria-expanded",
                "true"
            );
        }

        positionPicker();
    }


    function closePicker() {

        if (picker) {
            picker.hidden = true;
        }

        if (button) {

            button.setAttribute(
                "aria-expanded",
                "false"
            );
        }
    }


    function togglePicker() {

        createPicker();

        if (picker.hidden) {
            openPicker();
        } else {
            closePicker();
        }
    }


    function installButton() {

        const composer =
            document.getElementById(
                "agV2Composer"
            );

        const send =
            document.getElementById(
                "agV2Send"
            );

        const message =
            getMessageBox();

        if (
            !composer ||
            !send ||
            !message
        ) {
            return false;
        }


        const existing =
            document.getElementById(
                "agV2EmotesButton"
            );

        if (existing) {

            button =
                existing;

            return true;
        }


        button =
            document.createElement(
                "button"
            );

        button.id =
            "agV2EmotesButton";

        button.type =
            "button";

        button.textContent =
            "EMOTES";

        button.setAttribute(
            "aria-expanded",
            "false"
        );

        button.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                togglePicker();
            }
        );

        composer.insertBefore(
            button,
            send
        );

        createPicker();

        return true;
    }


    document.addEventListener(
        "click",
        function (event) {

            if (
                !picker ||
                picker.hidden
            ) {
                return;
            }

            if (
                picker.contains(
                    event.target
                )
            ) {
                return;
            }

            if (
                button &&
                button.contains(
                    event.target
                )
            ) {
                return;
            }

            closePicker();
        }
    );


    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key ===
                "Escape"
            ) {
                closePicker();
            }
        }
    );


    window.addEventListener(
        "resize",
        positionPicker
    );

    window.addEventListener(
        "scroll",
        positionPicker,
        true
    );


    function install() {

        installButton();
    }


    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            install
        );

    } else {

        install();
    }


    const observer =
        new MutationObserver(
            install
        );

    observer.observe(
        document.documentElement,
        {
            childList: true,
            subtree: true
        }
    );

})();

/* ==========================================================
   AUSTRALIA CHAT EMOTES V2 END
   ========================================================== */


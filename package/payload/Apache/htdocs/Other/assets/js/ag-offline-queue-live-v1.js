(function(){
"use strict";

const QUEUE="/Other/messenger-queue.php";
const DELETE="/Other/messenger-delete.php";

let pending=0;
let busy=false;


function s(v){
    return v==null ? "" : String(v);
}


function stamp(raw){

    raw=s(raw).trim();

    if(!raw){
        return "";
    }

    const d=
        new Date(
            raw.replace(" ","T")
        );

    if(
        Number.isNaN(
            d.getTime()
        )
    ){
        return raw;
    }

    return new Intl.DateTimeFormat(
        "en-AU",
        {
            day:"2-digit",
            month:"2-digit",
            year:"numeric",
            hour:"numeric",
            minute:"2-digit",
            second:"2-digit",
            hour12:true
        }
    ).format(d);
}


function stats(messages){

    const recipients=
        new Set();

    const senders=
        new Set();


    messages.forEach(
        function(m){

            if(m.recipientId){
                recipients.add(
                    String(
                        m.recipientId
                    )
                );
            }

            if(m.senderId){
                senders.add(
                    String(
                        m.senderId
                    )
                );
            }
        }
    );


    document
        .querySelectorAll(
            ".stat-card"
        )
        .forEach(
            function(card){

                const label=
                    card.querySelector(
                        ".stat-label"
                    );

                const value=
                    card.querySelector(
                        ".stat-value"
                    );


                if(
                    !label ||
                    !value
                ){
                    return;
                }


                const name=
                    label
                        .textContent
                        .trim()
                        .toUpperCase();


                if(
                    name ===
                    "QUEUED MESSAGES"
                ){
                    value.textContent=
                        String(
                            messages.length
                        );
                }


                if(
                    name ===
                    "RECIPIENTS"
                ){
                    value.textContent=
                        String(
                            recipients.size
                        );
                }


                if(
                    name ===
                    "SENDERS"
                ){
                    value.textContent=
                        String(
                            senders.size
                        );
                }
            }
        );
}


function host(){

    const warning=
        document.querySelector(
            ".queue-warning"
        );


    if(!warning){
        return null;
    }


    let h=
        document.getElementById(
            "agLiveQueue"
        );


    if(!h){

        h=
            document.createElement(
                "div"
            );

        h.id=
            "agLiveQueue";

        warning.insertAdjacentElement(
            "afterend",
            h
        );
    }


    const parent=
        warning.parentElement;


    if(parent){

        parent
            .querySelectorAll(
                ".table-wrap"
            )
            .forEach(
                function(node){

                    if(
                        !h.contains(
                            node
                        )
                    ){
                        node.remove();
                    }
                }
            );


        parent
            .querySelectorAll(
                ".empty"
            )
            .forEach(
                function(node){

                    if(
                        !h.contains(
                            node
                        )
                    ){
                        node.remove();
                    }
                }
            );
    }


    return h;
}


function cell(row,value){

    const td=
        document.createElement(
            "td"
        );

    td.textContent=
        s(value);

    row.appendChild(
        td
    );
}


function closeDelete(){

    pending=0;

    const overlay=
        document.getElementById(
            "ausDeleteOverlay"
        );


    if(overlay){

        overlay.classList.remove(
            "show"
        );

        overlay.setAttribute(
            "aria-hidden",
            "true"
        );
    }
}


function openDelete(id){

    pending=
        Number(id) ||
        0;


    if(!pending){
        return;
    }


    const overlay=
        document.getElementById(
            "ausDeleteOverlay"
        );


    if(!overlay){

        if(
            confirm(
                "Delete this offline message?"
            )
        ){
            removeMessage(
                pending
            );
        }

        return;
    }


    overlay.classList.add(
        "show"
    );

    overlay.setAttribute(
        "aria-hidden",
        "false"
    );
}


function render(messages){

    const h=
        host();


    if(!h){
        return;
    }


    stats(
        messages
    );


    h.replaceChildren();


    if(
        !messages.length
    ){

        const empty=
            document.createElement(
                "div"
            );

        empty.className=
            "empty";

        empty.textContent=
            "There are currently no messages waiting in the OpenSim offline delivery queue.";

        h.appendChild(
            empty
        );

        return;
    }


    const wrap=
        document.createElement(
            "div"
        );

    wrap.className=
        "table-wrap";


    const table=
        document.createElement(
            "table"
        );

    table.className=
        "message-table";


    const thead=
        document.createElement(
            "thead"
        );

    const headRow=
        document.createElement(
            "tr"
        );


    [
        "ID",
        "RECIPIENT",
        "SENDER",
        "DATE / TIME",
        "MESSAGE",
        "ACTION"
    ].forEach(
        function(title){

            const th=
                document.createElement(
                    "th"
                );

            th.textContent=
                title;

            headRow.appendChild(
                th
            );
        }
    );


    thead.appendChild(
        headRow
    );

    table.appendChild(
        thead
    );


    const body=
        document.createElement(
            "tbody"
        );


    messages.forEach(
        function(m){

            const row=
                document.createElement(
                    "tr"
                );


            cell(
                row,
                m.id
            );


            cell(
                row,
                m.recipientName ||
                m.recipientId
            );


            cell(
                row,
                m.senderName ||
                m.senderId
            );


            cell(
                row,
                stamp(
                    m.timestamp
                )
            );


            cell(
                row,
                m.message
            );


            const actions=
                document.createElement(
                    "td"
                );


            const reply=
                document.createElement(
                    "button"
                );

            reply.className=
                "reply-button";

            reply.type=
                "button";

            reply.textContent=
                "REPLY";

            reply.dataset.recipientId=
                s(
                    m.senderId
                );

            reply.dataset.recipientName=
                s(
                    m.senderName ||
                    m.senderId
                );


            reply.addEventListener(
                "click",
                function(){

                    if(
                        typeof window.australiaOpenReply ===
                        "function"
                    ){
                        window.australiaOpenReply(
                            reply
                        );
                    }
                }
            );


            actions.appendChild(
                reply
            );


            actions.appendChild(
                document.createTextNode(
                    " "
                )
            );


            const form=
                document.createElement(
                    "form"
                );

            form.style.display=
                "inline";


            const del=
                document.createElement(
                    "button"
                );

            del.className=
                "delete-button";

            del.type=
                "submit";

            del.textContent=
                "DELETE";


            form.appendChild(
                del
            );


            form.addEventListener(
                "submit",
                function(event){

                    event.preventDefault();

                    openDelete(
                        m.id
                    );
                }
            );


            actions.appendChild(
                form
            );


            row.appendChild(
                actions
            );


            body.appendChild(
                row
            );
        }
    );


    table.appendChild(
        body
    );

    wrap.appendChild(
        table
    );

    h.appendChild(
        wrap
    );
}


async function refresh(){

    if(
        busy ||
        document.visibilityState ===
        "hidden"
    ){
        return;
    }


    busy=true;


    try {

        const response=
            await fetch(
                QUEUE +
                "?_=" +
                Date.now(),
                {
                    credentials:
                        "same-origin",

                    cache:
                        "no-store"
                }
            );


        const data=
            await response.json();


        if(
            !response.ok ||
            !data ||
            data.ok !== true
        ){
            throw new Error(
                data &&
                data.error
                    ? data.error
                    : "Queue refresh failed."
            );
        }


        render(
            Array.isArray(
                data.messages
            )
                ? data.messages
                : []
        );

    }
    catch(error){

        console.error(
            "Australia Messenger queue refresh:",
            error
        );

    }
    finally {

        busy=false;
    }
}


async function removeMessage(id){

    const button=
        document.getElementById(
            "ausDeleteConfirm"
        );


    if(button){

        button.disabled=
            true;

        button.textContent=
            "DELETING...";
    }


    try {

        const body=
            new URLSearchParams();

        body.set(
            "message_id",
            String(id)
        );


        const response=
            await fetch(
                DELETE,
                {
                    method:
                        "POST",

                    headers:{
                        "Content-Type":
                            "application/x-www-form-urlencoded; charset=UTF-8",

                        "X-Australia-Messenger":
                            "1"
                    },

                    body:
                        body.toString(),

                    credentials:
                        "same-origin",

                    cache:
                        "no-store"
                }
            );


        const data=
            await response.json();


        if(
            !response.ok ||
            !data ||
            data.ok !== true
        ){
            throw new Error(
                data &&
                data.error
                    ? data.error
                    : "Delete failed."
            );
        }


        closeDelete();

        await refresh();

    }
    catch(error){

        alert(
            error.message ||
            "Delete failed."
        );

    }
    finally {

        if(button){

            button.disabled=
                false;

            button.textContent=
                "DELETE MESSAGE";
        }
    }
}


function bridge(){

    const yes=
        document.getElementById(
            "ausDeleteConfirm"
        );


    if(yes){

        yes.addEventListener(
            "click",
            function(event){

                if(!pending){
                    return;
                }

                event.preventDefault();

                event.stopImmediatePropagation();

                const id=
                    pending;

                removeMessage(
                    id
                );
            },
            true
        );
    }


    const no=
        document.getElementById(
            "ausDeleteCancel"
        );


    if(no){

        no.addEventListener(
            "click",
            function(event){

                if(!pending){
                    return;
                }

                event.preventDefault();

                event.stopImmediatePropagation();

                closeDelete();
            },
            true
        );
    }


    const overlay=
        document.getElementById(
            "ausDeleteOverlay"
        );


    if(overlay){

        overlay.addEventListener(
            "click",
            function(event){

                if(
                    pending &&
                    event.target ===
                    overlay
                ){
                    event.preventDefault();

                    event.stopImmediatePropagation();

                    closeDelete();
                }
            },
            true
        );
    }


    document.addEventListener(
        "keydown",
        function(event){

            if(
                pending &&
                event.key ===
                "Escape"
            ){
                event.preventDefault();

                closeDelete();
            }
        },
        true
    );
}


function start(){

    bridge();

    refresh();


    setInterval(
        refresh,
        3000
    );


    window.agRefreshOfflineQueue=
        refresh;


    document.addEventListener(
        "visibilitychange",
        function(){

            if(
                document.visibilityState ===
                "visible"
            ){
                refresh();
            }
        }
    );
}


if(
    document.readyState ===
    "loading"
){

    document.addEventListener(
        "DOMContentLoaded",
        start,
        {
            once:true
        }
    );

}
else {

    start();
}

})();

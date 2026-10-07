(function(){

    "use strict";


    document.addEventListener(
        "click",
        function(event){

            const button =
                event.target.closest(
                    "[data-cc-open]"
                );


            if(!button){
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            const src =
                button.getAttribute(
                    "data-cc-src"
                );


            const title =
                button.getAttribute(
                    "data-cc-title"
                ) || "Control Center";


            const view =
                button.getAttribute(
                    "data-cc-view"
                ) || "";


            if(!src){
                return;
            }


            try{

                if(
                    window.parent &&
                    window.parent !== window &&
                    typeof window.parent.ccOpenPanel === "function"
                ){

                    window.parent.ccOpenPanel(
                        title,
                        src,
                        view,
                        true
                    );

                    return;
                }

            }
            catch(error){

                console.error(
                    "Control Center navigation failed:",
                    error
                );
            }


            /*
             * Only used if this panel
             * is opened outside the shell.
             */

            window.location.href =
                src;

        },
        false
    );

})();
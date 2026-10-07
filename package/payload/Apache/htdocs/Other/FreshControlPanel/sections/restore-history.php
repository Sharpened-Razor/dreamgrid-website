<section
    id="agcp-page-restore-history"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                RESTORES
            </div>

            <h1>
                Restore History
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-restore-refresh"
                class="agcp-button"
                type="button">
                REFRESH
            </button>

            <button
                id="agcp-restore-clear"
                class="agcp-button agcp-button-danger"
                type="button">
                CLEAR HISTORY
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    HISTORY
                </div>

                <div class="agcp-panel-title">
                    Restore Activity
                </div>
            </div>

            <div
                id="agcp-restore-count"
                class="agcp-status-pill">
                0 RECORDS
            </div>

        </div>


        <div class="agcp-restore-tools">

            <div class="agcp-restore-field agcp-restore-search">

                <label for="agcp-restore-search">
                    SEARCH
                </label>

                <input
                    id="agcp-restore-search"
                    type="search"
                    placeholder="Search restore history">

            </div>


            <div class="agcp-restore-field">

                <label for="agcp-restore-type">
                    TYPE
                </label>

                <select id="agcp-restore-type">
                    <option value="">ALL TYPES</option>
                </select>

            </div>


            <div class="agcp-restore-field">

                <label for="agcp-restore-status">
                    STATUS
                </label>

                <select id="agcp-restore-status">
                    <option value="">ALL STATUS</option>
                </select>

            </div>

        </div>


        <div
            id="agcp-restore-message"
            class="agcp-message">
            Loading restore history...
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    RECORDS
                </div>

                <div class="agcp-panel-title">
                    Restore Log
                </div>
            </div>

        </div>


        <div class="agcp-restore-head">
            <span>STATUS</span>
            <span>TYPE</span>
            <span>SOURCE</span>
            <span>TARGET</span>
            <span>REQUESTED BY</span>
            <span>TIME</span>
        </div>


        <div
            id="agcp-restore-list"
            class="agcp-restore-list">

            <div class="agcp-empty">
                Loading restore records...
            </div>

        </div>

    </div>

</section>
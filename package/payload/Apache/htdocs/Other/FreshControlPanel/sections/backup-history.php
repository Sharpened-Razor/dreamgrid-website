<section
    id="agcp-page-backup-history"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                BACKUPS
            </div>

            <h1>
                Backup History
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-history-refresh"
                class="agcp-button"
                type="button">
                REFRESH
            </button>

            <button
                id="agcp-history-clear"
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
                    Backup Activity
                </div>
            </div>

            <div
                id="agcp-history-count"
                class="agcp-status-pill">
                0 RECORDS
            </div>

        </div>


        <div class="agcp-history-tools">

            <div class="agcp-history-field agcp-history-search-field">

                <label for="agcp-history-search">
                    SEARCH
                </label>

                <input
                    id="agcp-history-search"
                    type="search"
                    placeholder="Search backup history">

            </div>


            <div class="agcp-history-field">

                <label for="agcp-history-type">
                    TYPE
                </label>

                <select id="agcp-history-type">
                    <option value="">ALL TYPES</option>
                </select>

            </div>


            <div class="agcp-history-field">

                <label for="agcp-history-result">
                    STATUS
                </label>

                <select id="agcp-history-result">
                    <option value="">ALL STATUS</option>
                </select>

            </div>


            <div class="agcp-history-field">

                <label for="agcp-history-source">
                    SOURCE
                </label>

                <select id="agcp-history-source">
                    <option value="">ALL SOURCES</option>
                </select>

            </div>

        </div>


        <div
            id="agcp-history-message"
            class="agcp-message">
            Loading backup history...
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    RECORDS
                </div>

                <div class="agcp-panel-title">
                    Backup Log
                </div>
            </div>

        </div>


        <div
            id="agcp-history-list"
            class="agcp-history-list">

            <div class="agcp-empty">
                Loading history records...
            </div>

        </div>

    </div>

</section>
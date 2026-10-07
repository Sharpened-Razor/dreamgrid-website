<section
    id="agcp-page-grid-backups"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                GRID
            </div>

            <h1>
                Grid Backups
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-backup-select-all"
                class="agcp-button"
                type="button">
                SELECT ALL
            </button>

            <button
                id="agcp-backup-clear"
                class="agcp-button"
                type="button">
                CLEAR
            </button>

            <button
                id="agcp-backup-selected"
                class="agcp-button agcp-button-gold"
                type="button"
                disabled>
                BACKUP SELECTED
            </button>

            <button
                id="agcp-backup-refresh"
                class="agcp-button"
                type="button">
                REFRESH
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    OAR BACKUPS
                </div>

                <div class="agcp-panel-title">
                    Backup Status
                </div>
            </div>

            <div
                id="agcp-backup-state"
                class="agcp-status-pill">
                READY
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>REGIONS</span>
                <strong id="agcp-backup-regions">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>SELECTED</span>
                <strong id="agcp-backup-selected-count">0</strong>
            </div>

            <div class="agcp-summary-item">
                <span>PROTECTED</span>
                <strong
                    id="agcp-backup-protected"
                    class="good">
                    -
                </strong>
            </div>

            <div class="agcp-summary-item">
                <span>NO BACKUP</span>
                <strong id="agcp-backup-missing">-</strong>
            </div>

        </div>

    </div>


    <div class="agcp-backup-strip">

        <div class="agcp-backup-strip-item">
            <span>OAR FILES</span>
            <strong id="agcp-backup-oar-count">-</strong>
        </div>

        <div class="agcp-backup-strip-item">
            <span>OAR STORAGE</span>
            <strong id="agcp-backup-storage-used">-</strong>
        </div>

        <div class="agcp-backup-strip-item">
            <span>DRIVE FREE</span>
            <strong id="agcp-backup-drive-free">-</strong>
        </div>

        <div class="agcp-backup-strip-item">
            <span>NEWEST OAR</span>
            <strong id="agcp-backup-newest">-</strong>
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    CURRENT OPERATION
                </div>

                <div class="agcp-panel-title">
                    OAR Backup Queue
                </div>
            </div>

            <div
                id="agcp-backup-operation"
                class="agcp-status-pill">
                IDLE
            </div>

        </div>

        <div class="agcp-backup-operation-body">

            <div
                id="agcp-backup-operation-copy"
                class="agcp-backup-operation-copy">
                Select one or more regions to create OAR backups.
            </div>

            <div class="agcp-backup-progress-line">

                <span id="agcp-backup-progress-text">
                    IDLE
                </span>

                <span id="agcp-backup-progress-percent">
                    0%
                </span>

            </div>

            <div class="agcp-backup-progress-track">

                <div
                    id="agcp-backup-progress-bar"
                    class="agcp-backup-progress-bar">
                </div>

            </div>

            <div
                id="agcp-backup-message"
                class="agcp-message">
                Grid Backups ready.
            </div>

        </div>

    </div>


    <div class="agcp-backup-tools">

        <div class="agcp-backup-field">
            <label for="agcp-backup-search">
                SEARCH
            </label>

            <input
                id="agcp-backup-search"
                type="search"
                placeholder="Search region or owner">
        </div>


        <div class="agcp-backup-field">
            <label for="agcp-backup-filter">
                FILTER
            </label>

            <select id="agcp-backup-filter">
                <option value="all">ALL REGIONS</option>
                <option value="online">ONLINE</option>
                <option value="missing">NO BACKUP</option>
                <option value="selected">SELECTED</option>
            </select>
        </div>


        <div class="agcp-backup-field">
            <label for="agcp-backup-sort">
                SORT
            </label>

            <select id="agcp-backup-sort">
                <option value="name">REGION NAME</option>
                <option value="last">LAST BACKUP</option>
                <option value="count">OAR COUNT</option>
                <option value="size">LATEST SIZE</option>
            </select>
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    REGIONS
                </div>

                <div class="agcp-panel-title">
                    Region Backup Manager
                </div>
            </div>

        </div>

        <div class="agcp-backup-head">
            <span></span>
            <span>REGION</span>
            <span>STATUS</span>
            <span>LAST BACKUP</span>
            <span>AGE</span>
            <span>OARS</span>
            <span>SIZE</span>
            <span>PRIMS</span>
            <span>ACTIONS</span>
        </div>

        <div
            id="agcp-backup-list"
            class="agcp-backup-list">

            <div class="agcp-empty">
                Loading backup information...
            </div>

        </div>

    </div>


    <div
        id="agcp-backup-session-panel"
        class="agcp-panel"
        hidden>

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    SESSION
                </div>

                <div class="agcp-panel-title">
                    Backup Results
                </div>
            </div>

            <div
                id="agcp-backup-session-summary"
                class="agcp-status-pill">
                -
            </div>

        </div>

        <div
            id="agcp-backup-session-list"
            class="agcp-backup-session-list">
        </div>

    </div>

</section>
<section
    id="agcp-page-backup-files"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                BACKUPS
            </div>

            <h1>
                Backup Files
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-files-refresh"
                class="agcp-button agcp-button-gold"
                type="button">
                REFRESH
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    ARCHIVES
                </div>

                <div class="agcp-panel-title">
                    Backup File Library
                </div>
            </div>

            <div
                id="agcp-files-count"
                class="agcp-status-pill">
                0 FILES
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>TOTAL FILES</span>
                <strong id="agcp-files-total">0</strong>
            </div>

            <div class="agcp-summary-item">
                <span>OAR FILES</span>
                <strong id="agcp-files-oar">0</strong>
            </div>

            <div class="agcp-summary-item">
                <span>IAR FILES</span>
                <strong id="agcp-files-iar">0</strong>
            </div>

            <div class="agcp-summary-item">
                <span>DISPLAYED</span>
                <strong id="agcp-files-visible">0</strong>
            </div>

        </div>

    </div>


    <div class="agcp-files-tools">

        <div class="agcp-files-field agcp-files-search">

            <label for="agcp-files-search">
                SEARCH
            </label>

            <input
                id="agcp-files-search"
                type="search"
                placeholder="Search file, region or avatar">

        </div>


        <div class="agcp-files-field">

            <label for="agcp-files-type">
                TYPE
            </label>

            <select id="agcp-files-type">
                <option value="">ALL FILES</option>
                <option value="OAR">OAR</option>
                <option value="IAR">IAR</option>
            </select>

        </div>


        <div class="agcp-files-field">

            <label for="agcp-files-sort">
                SORT
            </label>

            <select id="agcp-files-sort">
                <option value="newest">NEWEST FIRST</option>
                <option value="oldest">OLDEST FIRST</option>
                <option value="name">FILE NAME</option>
                <option value="size">LARGEST FIRST</option>
            </select>

        </div>

    </div>


    <div
        id="agcp-files-message"
        class="agcp-message">
        Loading backup files...
    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    FILES
                </div>

                <div class="agcp-panel-title">
                    Available Archives
                </div>
            </div>

        </div>


        <div class="agcp-files-head">
            <span>TYPE</span>
            <span>FILE</span>
            <span>OWNER / REGION</span>
            <span>DATE</span>
            <span>SIZE</span>
            <span>ACTION</span>
        </div>


        <div
            id="agcp-files-list"
            class="agcp-files-list">

            <div class="agcp-empty">
                Loading backup files...
            </div>

        </div>

    </div>

</section>
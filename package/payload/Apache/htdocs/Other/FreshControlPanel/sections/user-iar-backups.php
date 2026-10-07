<section
    id="agcp-page-user-iar"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                INVENTORY
            </div>

            <h1>
                User IAR Backups
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-user-iar-refresh"
                class="agcp-button"
                type="button">
                REFRESH USERS
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    ADMIN BACKUP
                </div>

                <div class="agcp-panel-title">
                    Local Avatar Inventory Archive
                </div>
            </div>

            <div
                id="agcp-user-iar-state"
                class="agcp-status-pill">
                READY
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>LOCAL AVATARS</span>
                <strong id="agcp-user-iar-count">0</strong>
            </div>

            <div class="agcp-summary-item">
                <span>SELECTED</span>
                <strong id="agcp-user-iar-selected">NONE</strong>
            </div>

            <div class="agcp-summary-item">
                <span>JOB</span>
                <strong id="agcp-user-iar-job">IDLE</strong>
            </div>

            <div class="agcp-summary-item">
                <span>RESULT</span>
                <strong id="agcp-user-iar-result">-</strong>
            </div>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    USER
                </div>

                <div class="agcp-panel-title">
                    Select Local Avatar
                </div>
            </div>

        </div>


        <div class="agcp-user-iar-form">

            <div class="agcp-user-iar-field">

                <label for="agcp-user-iar-search">
                    SEARCH LOCAL AVATARS
                </label>

                <input
                    id="agcp-user-iar-search"
                    type="search"
                    placeholder="Type avatar name">

            </div>


            <div class="agcp-user-iar-field">

                <label for="agcp-user-iar-select">
                    LOCAL AVATAR
                </label>

                <select id="agcp-user-iar-select">
                    <option value="">
                        Loading local avatars...
                    </option>
                </select>

            </div>


            <div class="agcp-user-iar-action">

                <button
                    id="agcp-user-iar-save"
                    class="agcp-button agcp-button-gold"
                    type="button"
                    disabled>
                    BACKUP IAR
                </button>

            </div>

        </div>


        <div
            id="agcp-user-iar-message"
            class="agcp-message">
            Loading local avatars...
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    STATUS
                </div>

                <div class="agcp-panel-title">
                    Current IAR Job
                </div>
            </div>

        </div>


        <div
            id="agcp-user-iar-job-detail"
            class="agcp-user-iar-job">

            <div class="agcp-user-iar-job-row">
                <span>AVATAR</span>
                <strong id="agcp-user-iar-job-avatar">-</strong>
            </div>

            <div class="agcp-user-iar-job-row">
                <span>JOB ID</span>
                <strong id="agcp-user-iar-job-id">-</strong>
            </div>

            <div class="agcp-user-iar-job-row">
                <span>ARCHIVE</span>
                <strong id="agcp-user-iar-job-archive">-</strong>
            </div>

            <div class="agcp-user-iar-job-row">
                <span>STATUS</span>
                <strong id="agcp-user-iar-job-status">IDLE</strong>
            </div>

        </div>

    </div>

</section>
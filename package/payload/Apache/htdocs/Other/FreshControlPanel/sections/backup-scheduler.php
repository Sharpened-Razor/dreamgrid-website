<section
    id="agcp-page-backup-scheduler"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                BACKUPS
            </div>

            <h1>
                Backup Scheduler
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-schedule-refresh"
                class="agcp-button"
                type="button">
                REFRESH
            </button>

            <button
                id="agcp-schedule-save"
                class="agcp-button agcp-button-gold"
                type="button">
                SAVE SCHEDULE
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    AUTOMATION
                </div>

                <div class="agcp-panel-title">
                    Scheduler Status
                </div>
            </div>

            <div
                id="agcp-schedule-state"
                class="agcp-status-pill">
                CHECKING
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>SCHEDULER</span>
                <strong id="agcp-schedule-summary-enabled">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>NEXT RUN</span>
                <strong id="agcp-schedule-summary-next">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>REGIONS</span>
                <strong id="agcp-schedule-summary-regions">0</strong>
            </div>

            <div class="agcp-summary-item">
                <span>RETENTION</span>
                <strong id="agcp-schedule-summary-keep">-</strong>
            </div>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    SETTINGS
                </div>

                <div class="agcp-panel-title">
                    Backup Schedule
                </div>
            </div>

        </div>


        <div class="agcp-schedule-settings">

            <div class="agcp-schedule-field">

                <label for="agcp-schedule-enabled">
                    ENABLED
                </label>

                <select id="agcp-schedule-enabled">
                    <option value="1">ENABLED</option>
                    <option value="0">DISABLED</option>
                </select>

            </div>


            <div class="agcp-schedule-field">

                <label for="agcp-schedule-type">
                    FREQUENCY
                </label>

                <select id="agcp-schedule-type">
                    <option value="daily">DAILY</option>
                    <option value="weekly">WEEKLY</option>
                    <option value="selected">SELECTED DAYS</option>
                </select>

            </div>


            <div class="agcp-schedule-field">

                <label for="agcp-schedule-time">
                    BACKUP TIME
                </label>

                <input
                    id="agcp-schedule-time"
                    type="time"
                    value="02:00">

            </div>


            <div class="agcp-schedule-field">

                <label for="agcp-schedule-keep">
                    KEEP LAST
                </label>

                <select id="agcp-schedule-keep">
                    <option value="1">1 BACKUP</option>
                    <option value="2">2 BACKUPS</option>
                    <option value="3">3 BACKUPS</option>
                    <option value="5">5 BACKUPS</option>
                    <option value="7">7 BACKUPS</option>
                </select>

            </div>

        </div>


        <div class="agcp-schedule-days">

            <label>
                <input
                    class="agcp-schedule-day"
                    type="checkbox"
                    value="0">
                SUN
            </label>

            <label>
                <input
                    class="agcp-schedule-day"
                    type="checkbox"
                    value="1">
                MON
            </label>

            <label>
                <input
                    class="agcp-schedule-day"
                    type="checkbox"
                    value="2">
                TUE
            </label>

            <label>
                <input
                    class="agcp-schedule-day"
                    type="checkbox"
                    value="3">
                WED
            </label>

            <label>
                <input
                    class="agcp-schedule-day"
                    type="checkbox"
                    value="4">
                THU
            </label>

            <label>
                <input
                    class="agcp-schedule-day"
                    type="checkbox"
                    value="5">
                FRI
            </label>

            <label>
                <input
                    class="agcp-schedule-day"
                    type="checkbox"
                    value="6">
                SAT
            </label>

        </div>

        <div
            id="agcp-schedule-message"
            class="agcp-message">
            Loading scheduler...
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    COVERAGE
                </div>

                <div class="agcp-panel-title">
                    Scheduled Regions
                </div>
            </div>

            <div class="agcp-title-actions">

                <button
                    id="agcp-schedule-select-all"
                    class="agcp-button"
                    type="button">
                    SELECT ALL
                </button>

                <button
                    id="agcp-schedule-clear"
                    class="agcp-button"
                    type="button">
                    CLEAR
                </button>

            </div>

        </div>


        <div class="agcp-schedule-region-tools">

            <input
                id="agcp-schedule-search"
                type="search"
                placeholder="Search regions">

        </div>


        <div class="agcp-schedule-region-head">
            <span></span>
            <span>REGION</span>
            <span>STATUS</span>
            <span>OWNER</span>
            <span>AVATARS</span>
            <span>PRIMS</span>
        </div>


        <div
            id="agcp-schedule-region-list"
            class="agcp-schedule-region-list">

            <div class="agcp-empty">
                Loading regions...
            </div>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    RUNNER
                </div>

                <div class="agcp-panel-title">
                    Scheduler Runner
                </div>
            </div>

        </div>

        <div
            id="agcp-schedule-runner"
            class="agcp-schedule-runner">

            <div class="agcp-empty">
                Loading runner state...
            </div>

        </div>

    </div>

</section>
<section
    id="agcp-page-backup-health"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                BACKUPS
            </div>

            <h1>
                Backup Health
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-health-refresh"
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
                    HEALTH
                </div>

                <div class="agcp-panel-title">
                    Backup System Status
                </div>
            </div>

            <div
                id="agcp-health-state"
                class="agcp-status-pill">
                CHECKING
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>COMPLETED</span>
                <strong
                    id="agcp-health-completed"
                    class="good">
                    0
                </strong>
            </div>

            <div class="agcp-summary-item">
                <span>SKIPPED</span>
                <strong id="agcp-health-skipped">0</strong>
            </div>

            <div class="agcp-summary-item">
                <span>ERRORS</span>
                <strong
                    id="agcp-health-errors"
                    class="bad">
                    0
                </strong>
            </div>

            <div class="agcp-summary-item">
                <span>TOTAL BACKUPS</span>
                <strong id="agcp-health-total">0</strong>
            </div>

        </div>

    </div>


    <div class="agcp-health-watch">

        <div class="agcp-health-watch-item">
            <span>SCHEDULER</span>
            <strong id="agcp-health-scheduler">-</strong>
            <small id="agcp-health-scheduler-copy">Checking...</small>
        </div>

        <div class="agcp-health-watch-item">
            <span>SCHEDULE</span>
            <strong id="agcp-health-schedule">-</strong>
            <small id="agcp-health-schedule-copy">Checking...</small>
        </div>

        <div class="agcp-health-watch-item">
            <span>NEXT BACKUP</span>
            <strong id="agcp-health-next">-</strong>
            <small id="agcp-health-next-copy">Checking...</small>
        </div>

        <div class="agcp-health-watch-item">
            <span>LAST BACKUP</span>
            <strong id="agcp-health-last">-</strong>
            <small id="agcp-health-last-copy">Checking...</small>
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    DIAGNOSTICS
                </div>

                <div class="agcp-panel-title">
                    Health Checks
                </div>
            </div>

        </div>

        <div
            id="agcp-health-checks"
            class="agcp-health-checks">

            <div class="agcp-empty">
                Loading health checks...
            </div>

        </div>

    </div>


    <div class="agcp-health-columns">

        <div class="agcp-panel">

            <div class="agcp-panel-head">

                <div>
                    <div class="agcp-panel-kicker">
                        ARCHIVES
                    </div>

                    <div class="agcp-panel-title">
                        Backup Inventory
                    </div>
                </div>

            </div>

            <div
                id="agcp-health-files"
                class="agcp-health-details">
            </div>

        </div>


        <div class="agcp-panel">

            <div class="agcp-panel-head">

                <div>
                    <div class="agcp-panel-kicker">
                        STORAGE
                    </div>

                    <div class="agcp-panel-title">
                        Drive Health
                    </div>
                </div>

            </div>

            <div
                id="agcp-health-storage"
                class="agcp-health-details">
            </div>

        </div>

    </div>


    <div
        id="agcp-health-message"
        class="agcp-message">
        Loading backup health...
    </div>

</section>
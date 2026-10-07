<?php require_once dirname(__DIR__) . '/core/grid-branding.php'; ?>
<div class="acc-shell">

<aside class="acc-sidebar">

    <div class="acc-brand">
        <div class="acc-brand-kicker"><?=ag_grid_name_html()?></div>
        <div class="acc-brand-title">CONTROL CENTER</div>
        <div class="acc-brand-user">
            GRID OWNER
            <strong>
                <?=htmlspecialchars(
                    $avatar,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                )?>
            </strong>
        </div>
    </div>

    <nav class="acc-nav">

        <div class="acc-nav-group">GRID</div>

        <button class="cp-nav-button acc-nav-item active"
                data-section="overview"
                data-title="GRID OVERVIEW"
                type="button">
            GRID OVERVIEW
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="control"
                data-title="GRID CONTROL"
                type="button">
            GRID CONTROL
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="alerts"
                data-title="GRID ALERTS"
                type="button">
            GRID ALERTS
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="grid-backup"
                data-title="GRID BACKUP"
                type="button">
            GRID BACKUP
        </button>

        <div class="acc-nav-group">BACKUPS</div>

        <button class="cp-nav-button acc-nav-item"
                data-section="scheduler"
                data-title="BACKUP SCHEDULER"
                type="button">
            BACKUP SCHEDULER
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="storage"
                data-title="BACKUP STORAGE"
                type="button">
            BACKUP STORAGE
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="history"
                data-title="BACKUP HISTORY"
                type="button">
            BACKUP HISTORY
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="files"
                data-title="BACKUP FILES"
                type="button">
            BACKUP FILES
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="health"
                data-title="BACKUP HEALTH"
                type="button">
            BACKUP HEALTH
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="restores"
                data-title="RESTORE HISTORY"
                type="button">
            RESTORE HISTORY
        </button>

        <div class="acc-nav-group">INVENTORY</div>

        <button class="cp-nav-button acc-nav-item"
                data-section="admin-iar"
                data-title="USER IAR BACKUP"
                type="button">
            USER IAR BACKUP
        </button>

        <button class="cp-nav-button acc-nav-item"
                data-section="my-iar"
                data-title="MY IAR BACKUPS"
                type="button">
            MY IAR BACKUPS
        </button>

    </nav>

</aside>

<main class="acc-work">

    <header class="acc-topbar">
        <div id="cp-top-title" class="acc-top-title">
            GRID OVERVIEW
        </div>

        <div id="cp-top-status" class="acc-top-status">
            LIVE CONTROL CENTER
        </div>
    </header>

    <div class="acc-scroll">

        <!-- GRID OVERVIEW -->

        <section id="section-overview"
                 class="cp-section acc-page active">

            <header class="acc-page-head">
                <div>
                    <div class="acc-kicker">CONTROL PANEL</div>
                    <h1>Grid Overview</h1>
                    <p>Live DreamGrid region and backup summary.</p>
                </div>

                <div class="acc-actions">
                    <button id="overview-refresh"
                            class="acc-btn acc-btn-gold"
                            type="button">
                        REFRESH
                    </button>
                </div>
            </header>

            <div class="acc-metrics acc-metrics-five">

                <div class="acc-metric">
                    <strong id="overview-regions">0</strong>
                    <span>TOTAL REGIONS</span>
                </div>

                <div class="acc-metric acc-good">
                    <strong id="overview-running">0</strong>
                    <span>RUNNING</span>
                </div>

                <div class="acc-metric">
                    <strong id="overview-stopped">0</strong>
                    <span>STOPPED</span>
                </div>

                <div id="overview-alert-card"
                     class="acc-metric acc-clickable"
                     role="button"
                     tabindex="0">

                    <strong id="overview-alerts">0</strong>
                    <span>ACTIVE ALERTS</span>
                    <small>CLICK TO VIEW</small>
                </div>

                <div class="acc-metric">
                    <strong id="health-overall">-</strong>
                    <span>BACKUP HEALTH</span>
                </div>

            </div>

            <div id="overview-alert-details"
                 class="acc-surface panel">

                <div class="acc-surface-head">
                    ACTIVE ALERT DETAILS
                </div>

                <div id="overview-alert-detail-list"
                     class="acc-surface-body">
                    Checking monitored systems...
                </div>

            </div>

            <div class="acc-columns">

                <section class="acc-surface panel">
                    <div class="acc-surface-head">
                        LIVE REGIONS
                    </div>

                    <div id="overview-region-list"
                         class="acc-surface-body region-list">
                    </div>
                </section>

                <section class="acc-surface panel">

                    <div class="acc-surface-head">
                        BACKUP HEALTH
                    </div>

                    <div id="overview-health"
                         class="acc-health-grid">

                        <div class="acc-health-cell">
                            <span>SCHEDULER</span>
                            <strong id="health-scheduler">-</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>CURRENT JOB</span>
                            <strong id="health-current-job">NONE</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>ERRORS</span>
                            <strong id="health-errors">0</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>REGIONS PROTECTED</span>
                            <strong id="health-protected">-</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>MISSING BACKUPS</span>
                            <strong id="health-missing">-</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>LAST SUCCESSFUL</span>
                            <strong id="health-last-success">-</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>BACKUP AGE</span>
                            <strong id="health-backup-age">-</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>OAR FILES</span>
                            <strong id="health-oar-count">0</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>IAR FILES</span>
                            <strong id="health-iar-count">0</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>STORAGE USED</span>
                            <strong id="health-storage-used">-</strong>
                        </div>

                        <div class="acc-health-cell">
                            <span>STORAGE FREE</span>
                            <strong id="health-storage-free">-</strong>
                        </div>

                        <div class="acc-health-wide">
                            <span>NEWEST OAR</span>
                            <strong id="health-newest-oar">-</strong>
                        </div>

                        <div class="acc-health-wide">
                            <span>OLDEST OAR</span>
                            <strong id="health-oldest-oar">-</strong>
                        </div>

                        <div id="health-reason"
                             class="acc-health-message">
                            Waiting for backup health information...
                        </div>

                    </div>

                </section>

            </div>

        </section>


        <!-- GRID CONTROL -->

        <section id="section-control"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">LIVE DREAMGRID</div>
                    <h1>Grid Control</h1>
                    <p>Start, stop, restart, freeze and thaw regions.</p>
                </div>

                <div class="acc-actions">

                    <button id="control-select-all"
                            class="acc-btn"
                            type="button">
                        SELECT ALL
                    </button>

                    <button id="control-clear"
                            class="acc-btn"
                            type="button">
                        CLEAR
                    </button>

                    <button id="control-refresh"
                            class="acc-btn acc-btn-gold"
                            type="button">
                        REFRESH
                    </button>

                </div>

            </header>

            <div class="acc-metrics">

                <div class="acc-metric">
                    <strong id="control-total">0</strong>
                    <span>TOTAL</span>
                </div>

                <div class="acc-metric acc-good">
                    <strong id="control-running">0</strong>
                    <span>RUNNING</span>
                </div>

                <div class="acc-metric">
                    <strong id="control-selected">0</strong>
                    <span>SELECTED</span>
                </div>

                <div class="acc-metric">
                    <strong id="control-other">0</strong>
                    <span>STOPPED / OTHER</span>
                </div>

            </div>

            <section class="acc-surface panel">

                <div class="acc-surface-head">
                    REGION COMMANDS
                </div>

                <div class="acc-surface-body">

                    <div class="acc-actions acc-actions-left">

                        <button id="control-start"
                                class="acc-btn"
                                disabled>
                            START SELECTED
                        </button>

                        <button id="control-stop"
                                class="acc-btn"
                                disabled>
                            STOP SELECTED
                        </button>

                        <button id="control-restart"
                                class="acc-btn"
                                disabled>
                            RESTART SELECTED
                        </button>

                        <button id="control-freeze"
                                class="acc-btn"
                                disabled>
                            FREEZE SELECTED
                        </button>

                        <button id="control-thaw"
                                class="acc-btn"
                                disabled>
                            THAW SELECTED
                        </button>

                        <button id="control-restart-all"
                                class="acc-btn acc-btn-gold">
                            RESTART ALL
                        </button>

                    </div>

                    <div id="control-status"
                         class="status-box acc-status">
                        Grid Control ready.
                    </div>

                </div>

            </section>

            <section class="acc-surface panel">

                <div class="acc-surface-head">
                    LIVE REGIONS
                </div>

                <div id="control-list"
                     class="acc-surface-body region-list">
                </div>

            </section>

        </section>


        <!-- GRID ALERTS -->

        <section id="section-alerts"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">MONITORING</div>
                    <h1>Grid Alerts</h1>
                    <p>
                        Live region, backup, storage and system conditions
                        requiring attention.
                    </p>
                </div>

                <div class="acc-actions">

                    <span class="acc-live">
                        LIVE - 60 SEC
                    </span>

                    <button id="alerts-refresh"
                            class="acc-btn acc-btn-gold"
                            type="button">
                        REFRESH
                    </button>

                </div>

            </header>

            <div class="acc-metrics">

                <div class="acc-metric acc-danger">
                    <strong id="alerts-critical-count">0</strong>
                    <span>CRITICAL</span>
                </div>

                <div class="acc-metric acc-warning">
                    <strong id="alerts-warning-count">0</strong>
                    <span>WARNINGS</span>
                </div>

                <div class="acc-metric">
                    <strong id="alerts-region-count">-</strong>
                    <span>REGIONS ONLINE</span>
                </div>

                <div class="acc-metric">
                    <strong id="alerts-last-check">-</strong>
                    <span>LAST CHECK</span>
                </div>

            </div>

            <section class="acc-surface panel">

                <div class="acc-surface-head acc-head-split">
                    <span>SYSTEM WATCH</span>
                    <strong id="alerts-watch-status">CHECKING</strong>
                </div>

                <div class="acc-watch-grid">

                    <div class="acc-watch">
                        <span>REGIONS</span>
                        <strong id="alerts-watch-regions">-</strong>
                        <small id="alerts-watch-regions-copy">
                            Checking region status
                        </small>
                    </div>

                    <div class="acc-watch">
                        <span>BACKUP HEALTH</span>
                        <strong id="alerts-watch-backup">-</strong>
                        <small id="alerts-watch-backup-copy">
                            Checking backup health
                        </small>
                    </div>

                    <div class="acc-watch">
                        <span>SCHEDULER</span>
                        <strong id="alerts-watch-scheduler">-</strong>
                        <small id="alerts-watch-scheduler-copy">
                            Checking scheduler
                        </small>
                    </div>

                    <div class="acc-watch">
                        <span>STORAGE</span>
                        <strong id="alerts-watch-storage">-</strong>
                        <small id="alerts-watch-storage-copy">
                            Checking backup storage
                        </small>
                    </div>

                    <div class="acc-watch">
                        <span>BACKUP ERRORS</span>
                        <strong id="alerts-watch-errors">-</strong>
                        <small id="alerts-watch-errors-copy">
                            Checking backup errors
                        </small>
                    </div>

                </div>

            </section>

            <section class="acc-surface panel">

                <div class="acc-surface-head acc-head-split">
                    <span>ACTIVE ALERTS</span>
                    <strong id="alerts-active-total">0 ACTIVE</strong>
                </div>

                <div id="alerts-list"
                     class="acc-surface-body grid-alert-list">
                    Checking monitored systems...
                </div>

            </section>

        </section>


        <!-- GRID BACKUP -->

        <section id="section-grid-backup"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">OAR BACKUPS</div>
                    <h1>Grid Backup</h1>
                    <p>Create DreamGrid OAR backups for selected regions.</p>
                </div>

                <div class="acc-actions">

                    <button id="backup-select-all"
                            class="acc-btn">
                        SELECT ALL
                    </button>

                    <button id="backup-clear"
                            class="acc-btn">
                        CLEAR
                    </button>

                    <button id="backup-selected"
                            class="acc-btn acc-btn-gold"
                            disabled>
                        BACKUP SELECTED
                    </button>

                    <button id="backup-refresh"
                            class="acc-btn">
                        REFRESH
                    </button>

                </div>

            </header>

            <div class="acc-metrics">

                <div class="acc-metric">
                    <strong id="grid-backup-summary-regions">0</strong>
                    <span>REGIONS</span>
                </div>

                <div class="acc-metric">
                    <strong id="grid-backup-summary-selected">0</strong>
                    <span>SELECTED</span>
                </div>

                <div class="acc-metric acc-good">
                    <strong id="grid-backup-summary-online">0 / 0</strong>
                    <span>ONLINE</span>
                </div>

                <div class="acc-metric">
                    <strong id="grid-backup-summary-prims">0</strong>
                    <span>TOTAL PRIMS</span>
                </div>

            </div>

            <div class="acc-metrics">

                <div class="acc-metric">
                    <strong id="grid-backup-protected">-</strong>
                    <span>PROTECTED</span>
                </div>

                <div class="acc-metric">
                    <strong id="grid-backup-missing">-</strong>
                    <span>NO BACKUP</span>
                </div>

                <div class="acc-metric">
                    <strong id="grid-backup-oar-count">-</strong>
                    <span>OAR FILES</span>
                </div>

                <div class="acc-metric">
                    <strong id="grid-backup-drive-free">-</strong>
                    <span>DRIVE FREE</span>
                </div>

            </div>

            <section class="acc-surface panel">

                <div class="acc-info-strip">

                    <div>
                        <span>OAR STORAGE</span>
                        <strong id="grid-backup-storage-used">-</strong>
                    </div>

                    <div>
                        <span>NEWEST OAR</span>
                        <strong id="grid-backup-newest">-</strong>
                    </div>

                    <div>
                        <span>OLDEST OAR</span>
                        <strong id="grid-backup-oldest">-</strong>
                    </div>

                    <div>
                        <span>EST. SELECTED SIZE</span>
                        <strong id="grid-backup-selected-size">-</strong>
                    </div>

                </div>

            </section>

            <section class="acc-surface panel">

                <div class="acc-surface-head acc-head-split">
                    <span>OAR BACKUP QUEUE</span>
                    <strong id="grid-backup-operation-state">
                        READY
                    </strong>
                </div>

                <div class="acc-surface-body">

                    <div id="grid-backup-operation-copy"
                         class="acc-copy">
                        Select one or more regions to create DreamGrid OAR backups.
                    </div>

                    <div id="grid-backup-progress-text"
                         class="acc-progress-text">
                        IDLE
                    </div>

                    <div class="acc-progress-track grid-backup-progress-track">
                        <div id="grid-backup-progress-bar"
                             class="acc-progress-bar">
                        </div>
                    </div>

                    <div id="grid-backup-status"
                         class="status-box acc-status">
                        Grid Backup ready.
                    </div>

                </div>

            </section>

            <div class="acc-toolbar">

                <div class="acc-field-group">
                    <label for="grid-backup-search">SEARCH</label>
                    <input id="grid-backup-search"
                           class="acc-field"
                           type="search"
                           placeholder="Search region or owner">
                </div>

                <div class="acc-field-group">
                    <label for="grid-backup-filter">FILTER</label>
                    <select id="grid-backup-filter"
                            class="acc-field">
                        <option value="all">ALL REGIONS</option>
                        <option value="online">ONLINE</option>
                        <option value="selected">SELECTED</option>
                        <option value="no-backup">NO BACKUP</option>
                        <option value="stale">STALE BACKUPS</option>
                    </select>
                </div>

                <div class="acc-field-group">
                    <label for="grid-backup-sort">SORT</label>
                    <select id="grid-backup-sort"
                            class="acc-field">
                        <option value="name">REGION NAME</option>
                        <option value="last">LAST BACKUP</option>
                        <option value="age">BACKUP AGE</option>
                        <option value="count">OAR COUNT</option>
                        <option value="size">LATEST OAR SIZE</option>
                        <option value="prims">PRIM COUNT</option>
                    </select>
                </div>

            </div>

            <section id="grid-backup-session-panel"
                     class="acc-surface panel"
                     hidden>

                <div class="acc-surface-head">
                    LAST BACKUP SESSION
                </div>

                <div id="grid-backup-session-summary"
                     class="acc-surface-body">
                </div>

                <div id="grid-backup-session-results"
                     class="acc-surface-body">
                </div>

            </section>

            <section class="acc-surface panel">

                <div class="acc-surface-head">
                    REGIONS
                </div>

                <div id="grid-backup-list"
                     class="acc-surface-body region-list">
                </div>

            </section>

        </section>


        <!-- BACKUP SCHEDULER -->

        <section id="section-scheduler"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">AUTOMATION</div>
                    <h1>Backup Scheduler</h1>
                    <p>Configure the DreamGrid backup schedule.</p>
                </div>

                <div class="acc-actions">

                    <button id="schedule-load"
                            class="acc-btn">
                        REFRESH
                    </button>

                    <button id="schedule-save"
                            class="acc-btn acc-btn-gold">
                        SAVE SCHEDULE
                    </button>

                </div>

            </header>

            <section class="acc-surface panel">

                <div class="acc-surface-head">
                    SCHEDULE SETTINGS
                </div>

                <div class="acc-surface-body">

                    <div class="acc-form-grid">

                        <div class="acc-field-group">
                            <label>ENABLED</label>
                            <select id="schedule-enabled"
                                    class="acc-field field">
                                <option value="1">ENABLED</option>
                                <option value="0">DISABLED</option>
                            </select>
                        </div>

                        <div class="acc-field-group">
                            <label>SCHEDULE</label>
                            <select id="schedule-type"
                                    class="acc-field field">
                                <option value="daily">DAILY</option>
                                <option value="weekly">WEEKLY</option>
                                <option value="selected">SELECTED DAYS</option>
                            </select>
                        </div>

                        <div class="acc-field-group">
                            <label>TIME</label>
                            <input id="schedule-time"
                                   class="acc-field field"
                                   type="time"
                                   value="03:00">
                        </div>

                        <div class="acc-field-group">
                            <label>KEEP LAST</label>
                            <select id="schedule-keep"
                                    class="acc-field field">
                                <option value="1">1 BACKUP</option>
                                <option value="2">2 BACKUPS</option>
                                <option value="3" selected>3 BACKUPS</option>
                                <option value="5">5 BACKUPS</option>
                                <option value="7">7 BACKUPS</option>
                            </select>
                        </div>

                    </div>

                    <div class="acc-days">

                        <label>
                            <input class="schedule-day"
                                   type="checkbox"
                                   value="monday">
                            MON
                        </label>

                        <label>
                            <input class="schedule-day"
                                   type="checkbox"
                                   value="tuesday">
                            TUE
                        </label>

                        <label>
                            <input class="schedule-day"
                                   type="checkbox"
                                   value="wednesday">
                            WED
                        </label>

                        <label>
                            <input class="schedule-day"
                                   type="checkbox"
                                   value="thursday">
                            THU
                        </label>

                        <label>
                            <input class="schedule-day"
                                   type="checkbox"
                                   value="friday">
                            FRI
                        </label>

                        <label>
                            <input class="schedule-day"
                                   type="checkbox"
                                   value="saturday">
                            SAT
                        </label>

                        <label>
                            <input class="schedule-day"
                                   type="checkbox"
                                   value="sunday">
                            SUN
                        </label>

                    </div>

                    <div id="schedule-status"
                         class="status-box acc-status">
                        Loading scheduler...
                    </div>

                </div>

            </section>

            <section class="acc-surface panel">

                <div class="acc-surface-head">
                    CURRENT SCHEDULER DATA
                </div>

                <div id="schedule-data"
                     class="acc-surface-body object-grid">
                </div>

            </section>

        </section>


        <!-- BACKUP STORAGE -->

        <section id="section-storage"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">BACKUP SYSTEM</div>
                    <h1>Backup Storage</h1>
                    <p>Current backup storage statistics.</p>
                </div>

                <div class="acc-actions">
                    <button id="storage-refresh"
                            class="acc-btn acc-btn-gold">
                        REFRESH
                    </button>
                </div>

            </header>

            <div id="storage-data"
                 class="acc-output">
            </div>

            <div id="storage-status"
                 class="status-box acc-status">
                Loading backup storage...
            </div>

        </section>


        <!-- BACKUP HISTORY -->

        <section id="section-history"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">ACTIVITY</div>
                    <h1>Backup History</h1>
                    <p>OAR and IAR backup activity.</p>
                </div>

            </header>

            <div class="acc-toolbar">

                <input id="history-search"
                       class="acc-field field"
                       type="search"
                       placeholder="Search history"
                       autocomplete="off">

                <select id="history-type-filter"
                        class="acc-field field">
                    <option value="ALL">ALL TYPES</option>
                    <option value="OAR">OAR</option>
                    <option value="IAR">IAR</option>
                </select>

                <select id="history-result-filter"
                        class="acc-field field">
                    <option value="ALL">ALL RESULTS</option>
                    <option value="SUCCESS">SUCCESS</option>
                    <option value="FAILED">FAILED</option>
                    <option value="SKIPPED">SKIPPED</option>
                </select>

                <select id="history-source-filter"
                        class="acc-field field">
                    <option value="ALL">ALL SOURCES</option>
                    <option value="MANUAL">MANUAL</option>
                    <option value="SCHEDULED">SCHEDULED</option>
                    <option value="IMPORTED">IMPORTED</option>
                </select>

                <button id="history-refresh"
                        class="acc-btn acc-btn-gold"
                        type="button">
                    REFRESH
                </button>

                <button id="history-clear"
                        class="acc-btn acc-btn-red"
                        type="button">
                    CLEAR HISTORY
                </button>

            </div>

            <div id="history-list"
                 class="acc-output record-list">
            </div>

            <div id="history-status"
                 class="status-box acc-status">
                Loading backup history...
            </div>

        </section>


        <!-- BACKUP FILES -->

        <section id="section-files"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">ARCHIVES</div>
                    <h1>Backup Files</h1>
                    <p>Browse and download available OAR and IAR backups.</p>
                </div>

            </header>

            <div class="acc-toolbar">

                <input id="files-search"
                       class="acc-field field"
                       type="search"
                       placeholder="Search backup files"
                       autocomplete="off">

                <select id="files-filter"
                        class="acc-field field">
                    <option value="">ALL TYPES</option>
                    <option value="OAR">OAR</option>
                    <option value="IAR">IAR</option>
                </select>

                <button id="files-refresh"
                        class="acc-btn acc-btn-gold"
                        type="button">
                    REFRESH
                </button>

            </div>

            <div id="files-list"
                 class="acc-output record-list">
            </div>

            <div id="files-status"
                 class="status-box acc-status">
                Loading backup files...
            </div>

        </section>


        <!-- BACKUP HEALTH -->

        <section id="section-health"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">MONITORING</div>
                    <h1>Backup Health</h1>
                    <p>Live backup health and status information.</p>
                </div>

                <div class="acc-actions">
                    <button id="health-refresh"
                            class="acc-btn acc-btn-gold">
                        REFRESH
                    </button>
                </div>

            </header>

            <div id="health-data"
                 class="acc-output object-grid">
            </div>

            <div id="health-status"
                 class="status-box acc-status">
                Loading backup health...
            </div>

        </section>


        <!-- RESTORE HISTORY -->

        <section id="section-restores"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">RESTORES</div>
                    <h1>Restore History</h1>
                    <p>Recent IAR and OAR restore activity.</p>
                </div>

                <div class="acc-actions">

                    <button id="restores-refresh"
                            class="acc-btn acc-btn-gold">
                        REFRESH
                    </button>

                    <button id="restores-clear"
                            class="acc-btn acc-btn-red">
                        CLEAR RESTORE HISTORY
                    </button>

                </div>

            </header>

            <div class="acc-toolbar">

                <input id="restores-search"
                       class="acc-field input"
                       type="search"
                       placeholder="Search restores...">

                <select id="restores-type-filter"
                        class="acc-field input">
                    <option value="">ALL TYPES</option>
                    <option value="OAR">OAR</option>
                    <option value="IAR">IAR</option>
                </select>

                <select id="restores-status-filter"
                        class="acc-field input">
                    <option value="">ALL STATUS</option>
                    <option value="COMPLETE">COMPLETE</option>
                    <option value="ERROR">ERROR</option>
                    <option value="RESTORING">RESTORING</option>
                    <option value="PREPARED">PREPARED</option>
                </select>

            </div>

            <div id="restores-list"
                 class="acc-output record-list">
            </div>

            <div id="restores-status"
                 class="status-box acc-status">
                Loading restore history...
            </div>

        </section>


        <!-- ADMIN USER IAR -->

        <section id="section-admin-iar"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">GRID OWNER</div>
                    <h1>User IAR Backup</h1>
                    <p>
                        Select a local avatar and create a server-side IAR backup.
                    </p>
                </div>

                <div class="acc-actions">
                    <button id="admin-iar-refresh-users"
                            class="acc-btn">
                        REFRESH USERS
                    </button>
                </div>

            </header>

            <section class="acc-surface panel">

                <div class="acc-surface-head">
                    LOCAL USER INVENTORY BACKUP
                </div>

                <div class="acc-surface-body">

                    <div class="acc-form-grid acc-form-three">

                        <div class="acc-field-group">
                            <label>SEARCH USER</label>
                            <input id="admin-iar-user-search"
                                   class="acc-field field"
                                   type="search"
                                   placeholder="Search local avatars...">
                        </div>

                        <div class="acc-field-group">
                            <label>LOCAL AVATAR</label>
                            <select id="admin-iar-user"
                                    class="acc-field field">
                                <option value="">
                                    Loading local avatars...
                                </option>
                            </select>
                        </div>

                        <div class="acc-field-group acc-field-action">
                            <label>BACKUP</label>
                            <button id="admin-iar-save"
                                    class="acc-btn acc-btn-gold"
                                    disabled>
                                BACKUP USER IAR
                            </button>
                        </div>

                    </div>

                    <div id="admin-iar-status"
                         class="status-box acc-status">
                        Select a local avatar.
                    </div>

                </div>

            </section>

        </section>


        <!-- MY IAR -->

        <section id="section-my-iar"
                 class="cp-section acc-page">

            <header class="acc-page-head">

                <div>
                    <div class="acc-kicker">INVENTORY ARCHIVES</div>
                    <h1>My IAR Backups</h1>
                    <p>Two retained inventory backups for the signed-in avatar.</p>
                </div>

                <div class="acc-actions">

                    <button id="my-iar-save"
                            class="acc-btn acc-btn-gold">
                        SAVE NEW IAR
                    </button>

                    <button id="my-iar-refresh"
                            class="acc-btn">
                        REFRESH
                    </button>

                </div>

            </header>

            <div id="my-iar-summary"
                 class="my-iar-summary acc-metrics">

                <div class="my-iar-summary-cell acc-metric">
                    <span>SLOTS USED</span>
                    <strong>-</strong>
                </div>

                <div class="my-iar-summary-cell acc-metric">
                    <span>AVAILABLE</span>
                    <strong>-</strong>
                </div>

                <div class="my-iar-summary-cell acc-metric">
                    <span>RETENTION</span>
                    <strong>2 SLOTS</strong>
                </div>

            </div>

            <div id="my-iar-slots"
                 class="iar-slots acc-iar-slots">
            </div>

            <section class="acc-surface panel">

                <div class="acc-surface-head">
                    UPLOAD IAR
                </div>

                <div class="acc-surface-body">

                    <div class="acc-upload-row">

                        <input id="my-iar-upload-file"
                               class="acc-field field"
                               type="file"
                               accept=".iar">

                        <button id="my-iar-upload"
                                class="acc-btn acc-btn-gold"
                                disabled>
                            UPLOAD IAR
                        </button>

                    </div>

                </div>

            </section>

            <div id="my-iar-status"
                 class="status-box acc-status">
                Loading IAR backups...
            </div>

        </section>


        <!-- CONFIRM MODAL -->

        <div id="cp-confirm-modal"
             class="acc-modal"
             aria-hidden="true">

            <div class="acc-modal-backdrop"></div>

            <div class="acc-modal-window"
                 role="dialog"
                 aria-modal="true">

                <div class="acc-modal-head">

                    <div id="cp-confirm-title"
                         class="acc-modal-title">
                        Confirm
                    </div>

                    <button id="cp-confirm-close"
                            class="acc-modal-close"
                            type="button">
                        X
                    </button>

                </div>

                <div class="acc-modal-body">

                    <div id="cp-confirm-message"
                         class="acc-modal-message">
                    </div>

                    <div id="cp-confirm-detail"
                         class="acc-modal-detail">
                    </div>

                </div>

                <div class="acc-modal-actions">

                    <button id="cp-confirm-cancel"
                            class="acc-btn"
                            type="button">
                        CANCEL
                    </button>

                    <button id="cp-confirm-ok"
                            class="acc-btn acc-btn-gold"
                            type="button">
                        CONTINUE
                    </button>

                </div>

            </div>

        </div>

    </div>

</main>

</div>
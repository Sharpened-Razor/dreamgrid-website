<section
    id="agcp-page-backup-storage"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                BACKUPS
            </div>

            <h1>
                Backup Storage
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-storage-refresh"
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
                    STORAGE
                </div>

                <div class="agcp-panel-title">
                    Backup Capacity
                </div>
            </div>

            <div
                id="agcp-storage-state"
                class="agcp-status-pill">
                CHECKING
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>BACKUP STORAGE</span>
                <strong id="agcp-storage-backup-bytes">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>OAR STORAGE</span>
                <strong id="agcp-storage-oar-bytes">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>IAR STORAGE</span>
                <strong id="agcp-storage-iar-bytes">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>DRIVE FREE</span>
                <strong id="agcp-storage-drive-free">-</strong>
            </div>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    DRIVE
                </div>

                <div class="agcp-panel-title">
                    Capacity Health
                </div>
            </div>

        </div>

        <div class="agcp-storage-capacity">

            <div class="agcp-storage-capacity-head">

                <div>
                    <span>USED</span>
                    <strong id="agcp-storage-drive-used">-</strong>
                </div>

                <div>
                    <span>TOTAL</span>
                    <strong id="agcp-storage-drive-total">-</strong>
                </div>

                <div>
                    <span>FREE</span>
                    <strong id="agcp-storage-free-percent">-</strong>
                </div>

            </div>

            <div class="agcp-storage-progress">

                <div
                    id="agcp-storage-drive-bar"
                    class="agcp-storage-progress-bar">
                </div>

            </div>

            <div
                id="agcp-storage-capacity-copy"
                class="agcp-storage-capacity-copy">
                Loading drive capacity...
            </div>

        </div>

    </div>


    <div class="agcp-storage-columns">

        <div class="agcp-panel">

            <div class="agcp-panel-head">

                <div>
                    <div class="agcp-panel-kicker">
                        OAR
                    </div>

                    <div class="agcp-panel-title">
                        Region Archives
                    </div>
                </div>

                <div
                    id="agcp-storage-oar-count"
                    class="agcp-status-pill">
                    -
                </div>

            </div>


            <div class="agcp-storage-breakdown">

                <div class="agcp-storage-breakdown-row">

                    <div>
                        <span>OAR SHARE</span>
                        <strong id="agcp-storage-oar-percent">-</strong>
                    </div>

                    <div class="agcp-storage-mini-track">
                        <div
                            id="agcp-storage-oar-bar"
                            class="agcp-storage-mini-bar">
                        </div>
                    </div>

                </div>

                <div class="agcp-storage-detail-row">
                    <span>REGION GROUPS</span>
                    <strong id="agcp-storage-oar-groups-count">-</strong>
                </div>

                <div class="agcp-storage-detail-row">
                    <span>LARGEST REGION</span>
                    <strong id="agcp-storage-largest-oar">-</strong>
                </div>

            </div>


            <div
                id="agcp-storage-oar-groups"
                class="agcp-storage-group-list">

                <div class="agcp-empty">
                    Loading OAR groups...
                </div>

            </div>

        </div>


        <div class="agcp-panel">

            <div class="agcp-panel-head">

                <div>
                    <div class="agcp-panel-kicker">
                        IAR
                    </div>

                    <div class="agcp-panel-title">
                        Inventory Archives
                    </div>
                </div>

                <div
                    id="agcp-storage-iar-count"
                    class="agcp-status-pill">
                    -
                </div>

            </div>


            <div class="agcp-storage-breakdown">

                <div class="agcp-storage-breakdown-row">

                    <div>
                        <span>IAR SHARE</span>
                        <strong id="agcp-storage-iar-percent">-</strong>
                    </div>

                    <div class="agcp-storage-mini-track">
                        <div
                            id="agcp-storage-iar-bar"
                            class="agcp-storage-mini-bar">
                        </div>
                    </div>

                </div>

                <div class="agcp-storage-detail-row">
                    <span>OWNER GROUPS</span>
                    <strong id="agcp-storage-iar-groups-count">-</strong>
                </div>

                <div class="agcp-storage-detail-row">
                    <span>LARGEST OWNER</span>
                    <strong id="agcp-storage-largest-iar">-</strong>
                </div>

            </div>


            <div
                id="agcp-storage-iar-groups"
                class="agcp-storage-group-list">

                <div class="agcp-empty">
                    Loading IAR groups...
                </div>

            </div>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    BACKUP ROOT
                </div>

                <div class="agcp-panel-title">
                    Storage Information
                </div>
            </div>

        </div>

        <div class="agcp-storage-info">

            <div class="agcp-storage-info-row">
                <span>ROOT</span>
                <strong id="agcp-storage-root">-</strong>
            </div>

            <div class="agcp-storage-info-row">
                <span>MODE</span>
                <strong id="agcp-storage-mode">-</strong>
            </div>

            <div class="agcp-storage-info-row">
                <span>TOTAL ARCHIVES</span>
                <strong id="agcp-storage-total-files">-</strong>
            </div>

            <div class="agcp-storage-info-row">
                <span>LAST REFRESH</span>
                <strong id="agcp-storage-refresh-time">-</strong>
            </div>

        </div>

        <div
            id="agcp-storage-message"
            class="agcp-message">
            Loading backup storage...
        </div>

    </div>

</section>
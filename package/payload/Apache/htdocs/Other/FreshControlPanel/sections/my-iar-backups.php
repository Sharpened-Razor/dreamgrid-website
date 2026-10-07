<section
    id="agcp-page-my-iar"
    class="agcp-page">

    <div class="agcp-titlebar">

        <div>
            <div class="agcp-title-kicker">
                INVENTORY
            </div>

            <h1>
                My IAR Backups
            </h1>
        </div>

        <div class="agcp-title-actions">

            <button
                id="agcp-my-iar-save"
                class="agcp-button agcp-button-gold"
                type="button">
                SAVE NEW IAR
            </button>

            <button
                id="agcp-my-iar-refresh"
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
                    MY INVENTORY
                </div>

                <div class="agcp-panel-title">
                    Personal IAR Backup Slots
                </div>
            </div>

            <div
                id="agcp-my-iar-state"
                class="agcp-status-pill">
                CHECKING
            </div>

        </div>


        <div class="agcp-summary">

            <div class="agcp-summary-item">
                <span>SLOTS USED</span>
                <strong id="agcp-my-iar-used">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>AVAILABLE</span>
                <strong id="agcp-my-iar-available">-</strong>
            </div>

            <div class="agcp-summary-item">
                <span>RETENTION</span>
                <strong>2 SLOTS</strong>
            </div>

            <div class="agcp-summary-item">
                <span>ACTIVE JOB</span>
                <strong id="agcp-my-iar-job">NONE</strong>
            </div>

        </div>

    </div>


    <div
        id="agcp-my-iar-slots"
        class="agcp-my-iar-slots">

        <div class="agcp-empty">
            Loading IAR backup slots...
        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    IMPORT
                </div>

                <div class="agcp-panel-title">
                    Upload IAR
                </div>
            </div>

        </div>


        <div class="agcp-my-iar-upload">

            <div class="agcp-my-iar-file-field">

                <label for="agcp-my-iar-upload-file">
                    IAR FILE
                </label>

                <input
                    id="agcp-my-iar-upload-file"
                    type="file"
                    accept=".iar">

            </div>

            <button
                id="agcp-my-iar-upload"
                class="agcp-button agcp-button-gold"
                type="button"
                disabled>
                UPLOAD IAR
            </button>

        </div>

    </div>


    <div class="agcp-panel">

        <div class="agcp-panel-head">

            <div>
                <div class="agcp-panel-kicker">
                    CURRENT JOB
                </div>

                <div class="agcp-panel-title">
                    IAR Operation
                </div>
            </div>

        </div>


        <div class="agcp-my-iar-job-grid">

            <div class="agcp-my-iar-job-row">
                <span>OPERATION</span>
                <strong id="agcp-my-iar-operation">IDLE</strong>
            </div>

            <div class="agcp-my-iar-job-row">
                <span>JOB ID</span>
                <strong id="agcp-my-iar-job-id">-</strong>
            </div>

            <div class="agcp-my-iar-job-row">
                <span>STATUS</span>
                <strong id="agcp-my-iar-job-status">READY</strong>
            </div>

            <div class="agcp-my-iar-job-row">
                <span>RESULT</span>
                <strong id="agcp-my-iar-job-result">-</strong>
            </div>

        </div>


        <div
            id="agcp-my-iar-message"
            class="agcp-message">
            Loading IAR backups...
        </div>

    </div>

</section>
<?php

/*
 * ============================================================
 * Grid - PUBLIC FRONT PAGE LIVE STATS V4
 *
 * READ ONLY
 *
 * USERS ONLINE:
 *
 * Local Australia residents:
 *     GridUser.Online = true
 *
 * Hypergrid visitors:
 *     Valid Presence record attached to an actual region.
 *
 * Stale HG Presence rows with zero RegionID are ignored.
 * ============================================================
 */

require_once __DIR__ . '/core/database.php';


header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


function ag_stats_fail(
    string $message
): void {

    http_response_code(503);

    echo json_encode(
        [
            'ok' => false,
            'error' => $message,
        ],
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


function ag_stats_count(
    mysqli $con,
    string $sql
): int {

    $result =
        @mysqli_query(
            $con,
            $sql
        );


    if (!$result) {

        throw new RuntimeException(
            mysqli_error($con)
        );
    }


    $row =
        mysqli_fetch_assoc(
            $result
        );


    mysqli_free_result(
        $result
    );


    return
        (int)(
            $row['c'] ??
            0
        );
}


$con =
    ag_db_connect();


if (!$con) {

    ag_stats_fail(
        'Grid database unavailable.'
    );
}


mysqli_set_charset(
    $con,
    'utf8mb4'
);


try {

    /*
     * ========================================================
     * USERS ONLINE
     *
     * First half:
     *   Local registered Australia users whose GridUser row
     *   explicitly says Online=true.
     *
     * Second half:
     *   Hypergrid / non-local users with a Presence record
     *   attached to a real current region UUID.
     *
     * UNION prevents double counting.
     * ========================================================
     */

    $usersOnline =
        ag_stats_count(
            $con,

            'SELECT COUNT(*) AS c ' .
            'FROM (' .

                'SELECT gu.UserID AS UserID ' .
                'FROM GridUser gu ' .

                'INNER JOIN UserAccounts ua ' .
                'ON ua.PrincipalID = gu.UserID ' .

                "WHERE LOWER(TRIM(gu.Online)) = 'true' " .
                'AND ua.UserLevel >= 0 ' .

                'UNION ' .

                'SELECT p.UserID AS UserID ' .
                'FROM Presence p ' .

                'LEFT JOIN UserAccounts ua2 ' .
                'ON ua2.PrincipalID = p.UserID ' .

                'INNER JOIN regions r ' .
                'ON r.uuid = p.RegionID ' .

                'WHERE ua2.PrincipalID IS NULL ' .
                "AND p.UserID <> '' " .
                'AND p.RegionID IS NOT NULL ' .
                "AND p.RegionID <> " .
                "'00000000-0000-0000-0000-000000000000'" .

            ') AS australia_live_users'
        );


    /*
     * ========================================================
     * REGIONS ONLINE
     * ========================================================
     */

    $regionsOnline =
        ag_stats_count(
            $con,
            'SELECT COUNT(*) AS c ' .
            'FROM regions'
        );


    /*
     * ========================================================
     * TOTAL REGISTERED LOCAL USERS
     * ========================================================
     */

    $totalUsers =
        ag_stats_count(
            $con,
            'SELECT COUNT(*) AS c ' .
            'FROM UserAccounts ' .
            'WHERE UserLevel >= 0'
        );


    /*
     * ========================================================
     * ACTIVE LOCAL USERS - LAST 30 DAYS
     * ========================================================
     */

    $threshold =
        time() -
        (30 * 24 * 60 * 60);


    $activeUsers =
        ag_stats_count(
            $con,

            'SELECT COUNT(DISTINCT gu.UserID) AS c ' .

            'FROM GridUser gu ' .

            'INNER JOIN UserAccounts ua ' .
            'ON ua.PrincipalID = gu.UserID ' .

            'WHERE ua.UserLevel >= 0 ' .

            "AND CAST(NULLIF(gu.Login, '') AS UNSIGNED) >= " .

            (int)$threshold
        );


    mysqli_close(
        $con
    );


    echo json_encode(
        [
            'ok' =>
                true,

            'users_online' =>
                $usersOnline,

            'regions_online' =>
                $regionsOnline,

            'total_users' =>
                $totalUsers,

            'active_users' =>
                $activeUsers,

            'generated' =>
                gmdate('c'),
        ],
        JSON_UNESCAPED_SLASHES
    );

}
catch (Throwable $e) {

    mysqli_close(
        $con
    );


    ag_stats_fail(
        'Unable to read live grid statistics.'
    );
}
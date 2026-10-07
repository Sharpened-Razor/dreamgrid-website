<?php
require_once __DIR__ . '/core/dreamgrid-env.php';

/*
 ============================================================
 Grid
 USER REGION OAR ROLLING BACKUPS V1

 This file manages ONLY resident rollback copies.

 DreamGrid's normal Autobackup OAR files are never deleted
 or rotated by this file.
 ============================================================
*/


function australiaUserOarOutworldzRoot()
{
    return ag_dg_root();
}


function australiaUserOarBackupRoot()
{
    return
        australiaUserOarOutworldzRoot() .
        DIRECTORY_SEPARATOR .
        'UserOARBackups';
}


function australiaUserOarDreamGridRoot()
{
    return
        australiaUserOarOutworldzRoot() .
        DIRECTORY_SEPARATOR .
        'Autobackup';
}


function australiaUserOarSafeComponent($value)
{
    $value =
        trim(
            (string)$value
        );

    $value =
        preg_replace(
            '/[<>:"\/\\\\|?*\x00-\x1F]/u',
            '_',
            $value
        );

    $value =
        preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

    $value =
        rtrim(
            (string)$value,
            " ."
        );

    if (
        $value === '' ||
        $value === '.' ||
        $value === '..'
    ) {
        $value = 'Unknown';
    }

    return $value;
}


function australiaUserOarRegionFolder(
    $owner,
    $region,
    $create = false
) {
    $folder =
        australiaUserOarBackupRoot() .
        DIRECTORY_SEPARATOR .
        australiaUserOarSafeComponent($owner) .
        DIRECTORY_SEPARATOR .
        australiaUserOarSafeComponent($region);

    if (
        $create &&
        !is_dir($folder)
    ) {
        if (
            !@mkdir(
                $folder,
                0775,
                true
            ) &&
            !is_dir($folder)
        ) {
            throw new RuntimeException(
                'Could not create resident OAR backup folder.'
            );
        }
    }

    return $folder;
}


function australiaUserOarSlotPath(
    $owner,
    $region,
    $slot
) {
    $slot =
        (int)$slot;

    if (
        $slot < 1 ||
        $slot > 2
    ) {
        throw new RuntimeException(
            'Invalid OAR backup slot.'
        );
    }

    return
        australiaUserOarRegionFolder(
            $owner,
            $region,
            false
        ) .
        DIRECTORY_SEPARATOR .
        'Backup-' .
        $slot .
        '.oar';
}


function australiaUserOarSlotInfo(
    $owner,
    $region,
    $slot
) {
    $slot =
        (int)$slot;

    $path =
        australiaUserOarSlotPath(
            $owner,
            $region,
            $slot
        );

    if (
        !is_file($path)
    ) {
        return array(
            'slot' =>
                $slot,

            'empty' =>
                true,

            'name' =>
                'Backup-' .
                $slot .
                '.oar',

            'size' =>
                0,

            'timestamp' =>
                0
        );
    }

    clearstatcache(
        true,
        $path
    );

    return array(
        'slot' =>
            $slot,

        'empty' =>
            false,

        'name' =>
            basename($path),

        'size' =>
            (int)filesize($path),

        'timestamp' =>
            (int)filemtime($path)
    );
}


function australiaUserOarSlots(
    $owner,
    $region
) {
    return array(
        australiaUserOarSlotInfo(
            $owner,
            $region,
            1
        ),

        australiaUserOarSlotInfo(
            $owner,
            $region,
            2
        ));
}


/*
 ============================================================
 DREAMGRID OAR DISCOVERY
 ============================================================
*/


function australiaDreamGridOarFilesForRegion(
    $region
) {
    $region = trim((string)$region);

    if ($region === '') {
        return [];
    }

    $root = australiaUserOarDreamGridRoot();

    if (
        !is_string($root) ||
        $root === '' ||
        !is_dir($root)
    ) {
        return [];
    }

    $files = [];

    /*
     * DreamGrid actual layout:
     *
     * Autobackup
     *   AutoBackup-YYYY-MM-DD
     *     OAR
     *       RegionName_YYYY-MM-DD_HH_MM_SS(1X1).oar
     *
     * There is NO OAR\RegionName folder.
     */

    $backupFolders = @glob(
        $root .
        DIRECTORY_SEPARATOR .
        'AutoBackup-*',
        GLOB_ONLYDIR
    );

    if (!is_array($backupFolders)) {
        return [];
    }

    foreach ($backupFolders as $backupFolder) {

        $oarDir =
            $backupFolder .
            DIRECTORY_SEPARATOR .
            'OAR';

        if (!is_dir($oarDir)) {
            continue;
        }

        try {

            $iterator =
                new DirectoryIterator(
                    $oarDir
                );

        }
        catch (Throwable $e) {

            continue;
        }

        $prefix = $region . '_';

        foreach ($iterator as $entry) {

            if (
                $entry->isDot() ||
                !$entry->isFile()
            ) {
                continue;
            }

            $name =
                $entry->getFilename();

            if (
                strcasecmp(
                    pathinfo(
                        $name,
                        PATHINFO_EXTENSION
                    ),
                    'oar'
                ) !== 0
            ) {
                continue;
            }

            if (
                strncasecmp(
                    $name,
                    $prefix,
                    strlen($prefix)
                ) !== 0
            ) {
                continue;
            }

            $path =
                $entry->getPathname();

            if (is_file($path)) {
                $files[] = $path;
            }
        }
    }

    $files =
        array_values(
            array_unique(
                $files
            )
        );

    usort(
        $files,
        static function (
            $a,
            $b
        ): int {

            $aTime = @filemtime($a);
            $bTime = @filemtime($b);

            $aTime =
                is_int($aTime)
                    ? $aTime
                    : 0;

            $bTime =
                is_int($bTime)
                    ? $bTime
                    : 0;

            if ($aTime === $bTime) {

                return
                    strcasecmp(
                        basename($b),
                        basename($a)
                    );
            }

            return
                $bTime <=> $aTime;
        }
    );

    return $files;
}


function australiaUserOarSnapshot(
    $region
) {
    return
        australiaDreamGridOarFilesForRegion(
            $region
        );
}


function australiaWaitForNewDreamGridOar(
    $region,
    $before,
    $startedAt,
    $timeoutSeconds = 90
) {
    $deadline =
        microtime(true) +
        max(
            5,
            (int)$timeoutSeconds
        );

    while (
        microtime(true) <
        $deadline
    ) {
        $current =
            australiaDreamGridOarFilesForRegion(
                $region
            );

        $candidates =
            array();

        /*
         * australiaDreamGridOarFilesForRegion() returns a
         * numerically indexed list of pathname strings.
         *
         * A candidate is therefore a pathname that did not
         * exist in the pre-save snapshot and whose filesystem
         * timestamp belongs to this Save OAR request.
         */
        foreach (
            $current
            as
            $path
        ) {
            $path =
                trim(
                    (string)$path
                );

            if (
                $path === '' ||
                !is_file($path)
            ) {
                continue;
            }

            $alreadyExisted =
                false;

            foreach (
                (array)$before
                as
                $beforePath
            ) {
                if (
                    strcasecmp(
                        trim(
                            (string)$beforePath
                        ),
                        $path
                    ) === 0
                ) {
                    $alreadyExisted =
                        true;

                    break;
                }
            }

            if ($alreadyExisted) {
                continue;
            }

            clearstatcache(
                true,
                $path
            );

            $size =
                (int)@filesize(
                    $path
                );

            $mtime =
                (int)@filemtime(
                    $path
                );

            if ($size <= 0) {
                continue;
            }

            if (
                $mtime <
                ((int)$startedAt - 10)
            ) {
                continue;
            }

            $candidates[] =
                array(
                    'path' =>
                        $path,

                    'size' =>
                        $size,

                    'mtime' =>
                        $mtime
                );
        }

        usort(
            $candidates,
            function(
                $a,
                $b
            ) {
                return
                    (int)$b['mtime'] <=>
                    (int)$a['mtime'];
            }
        );

        foreach (
            $candidates
            as
            $candidate
        ) {
            $path =
                $candidate['path'];

            if (
                !is_file($path)
            ) {
                continue;
            }

            clearstatcache(
                true,
                $path
            );

            $size1 =
                (int)filesize($path);

            $time1 =
                (int)filemtime($path);

            if (
                $size1 <= 0
            ) {
                continue;
            }

            if (
                $time1 >
                (time() - 2)
            ) {
                continue;
            }

            sleep(1);

            clearstatcache(
                true,
                $path
            );

            if (
                !is_file($path)
            ) {
                continue;
            }

            $size2 =
                (int)filesize($path);

            $time2 =
                (int)filemtime($path);

            sleep(1);

            clearstatcache(
                true,
                $path
            );

            if (
                !is_file($path)
            ) {
                continue;
            }

            $size3 =
                (int)filesize($path);

            $time3 =
                (int)filemtime($path);

            if (
                $size1 === $size2 &&
                $size2 === $size3 &&
                $time1 === $time2 &&
                $time2 === $time3 &&
                $size3 > 0
            ) {
                return $path;
            }
        }

        usleep(
            500000
        );
    }

    return null;
}


/*
 ============================================================
 SAFE 3 SLOT STORAGE
 ============================================================
*/


function australiaStoreUserOar(
    $owner,
    $region,
    $sourcePath
) {
    if (
        !is_file($sourcePath)
    ) {
        throw new RuntimeException(
            'OAR source file was not found.'
        );
    }

    clearstatcache(
        true,
        $sourcePath
    );

    $sourceSize =
        (int)filesize(
            $sourcePath
        );

    if (
        $sourceSize <= 0
    ) {
        throw new RuntimeException(
            'OAR source file is empty.'
        );
    }

    $sourceHash =
        hash_file(
            'sha256',
            $sourcePath
        );

    if (
        !is_string($sourceHash) ||
        $sourceHash === ''
    ) {
        throw new RuntimeException(
            'Could not verify the OAR checksum.'
        );
    }

    $folder =
        australiaUserOarRegionFolder(
            $owner,
            $region,
            true
        );

    $lockPath =
        $folder .
        DIRECTORY_SEPARATOR .
        '.slots.lock';

    $lock =
        @fopen(
            $lockPath,
            'c'
        );

    if (
        !$lock
    ) {
        throw new RuntimeException(
            'Could not lock resident OAR backup slots.'
        );
    }

    $locked =
        false;

    try {
        $locked =
            flock(
                $lock,
                LOCK_EX
            );

        if (
            !$locked
        ) {
            throw new RuntimeException(
                'Could not lock resident OAR backup slots.'
            );
        }

        $slots =
            array();

        for (
            $slot = 1;
            $slot <= 2;
            $slot++
        ) {
            $path =
                australiaUserOarSlotPath(
                    $owner,
                    $region,
                    $slot
                );

            if (
                is_file($path)
            ) {
                clearstatcache(
                    true,
                    $path
                );

                $slots[$slot] =
                    array(
                        'exists' =>
                            true,

                        'mtime' =>
                            (int)filemtime($path)
                    );
            }
            else {
                $slots[$slot] =
                    array(
                        'exists' =>
                            false,

                        'mtime' =>
                            0
                    );
            }
        }

        $chosenSlot =
            0;

        for (
            $slot = 1;
            $slot <= 2;
            $slot++
        ) {
            if (
                !$slots[$slot]['exists']
            ) {
                $chosenSlot =
                    $slot;

                break;
            }
        }

        if (
            $chosenSlot === 0
        ) {
            $oldestTime =
                PHP_INT_MAX;

            for (
                $slot = 1;
                $slot <= 2;
                $slot++
            ) {
                if (
                    $slots[$slot]['mtime'] <
                    $oldestTime
                ) {
                    $oldestTime =
                        $slots[$slot]['mtime'];

                    $chosenSlot =
                        $slot;
                }
            }
        }

        $target =
            australiaUserOarSlotPath(
                $owner,
                $region,
                $chosenSlot
            );

        $token =
            bin2hex(
                random_bytes(6)
            );

        $temporary =
            $folder .
            DIRECTORY_SEPARATOR .
            '.Backup-' .
            $chosenSlot .
            '.new-' .
            $token .
            '.oar';

        $previous =
            $folder .
            DIRECTORY_SEPARATOR .
            '.Backup-' .
            $chosenSlot .
            '.old-' .
            $token .
            '.oar';

        /*
         * Copy and verify NEW OAR before touching old slot.
         */
        if (
            !@copy(
                $sourcePath,
                $temporary
            )
        ) {
            throw new RuntimeException(
                'Could not copy the new OAR into resident backup storage.'
            );
        }

        clearstatcache(
            true,
            $temporary
        );

        $temporarySize =
            (int)filesize(
                $temporary
            );

        $temporaryHash =
            hash_file(
                'sha256',
                $temporary
            );

        if (
            $temporarySize !==
            $sourceSize ||

            !hash_equals(
                $sourceHash,
                (string)$temporaryHash
            )
        ) {
            @unlink(
                $temporary
            );

            throw new RuntimeException(
                'The copied OAR failed verification.'
            );
        }

        $sourceTime =
            (int)filemtime(
                $sourcePath
            );

        if (
            $sourceTime > 0
        ) {
            @touch(
                $temporary,
                $sourceTime
            );
        }

        $hadPrevious =
            is_file($target);

        /*
         * New backup is verified.
         * Only now may the old slot be moved aside.
         */
        if (
            $hadPrevious
        ) {
            if (
                !@rename(
                    $target,
                    $previous
                )
            ) {
                @unlink(
                    $temporary
                );

                throw new RuntimeException(
                    'Could not safely rotate the oldest OAR backup.'
                );
            }
        }

        if (
            !@rename(
                $temporary,
                $target
            )
        ) {
            if (
                $hadPrevious &&
                is_file($previous)
            ) {
                @rename(
                    $previous,
                    $target
                );
            }

            @unlink(
                $temporary
            );

            throw new RuntimeException(
                'Could not activate the new OAR backup slot.'
            );
        }

        clearstatcache(
            true,
            $target
        );

        $finalSize =
            (int)filesize(
                $target
            );

        $finalHash =
            hash_file(
                'sha256',
                $target
            );

        if (
            $finalSize !==
            $sourceSize ||

            !hash_equals(
                $sourceHash,
                (string)$finalHash
            )
        ) {
            @unlink(
                $target
            );

            if (
                $hadPrevious &&
                is_file($previous)
            ) {
                @rename(
                    $previous,
                    $target
                );
            }

            throw new RuntimeException(
                'Final OAR slot verification failed.'
            );
        }

        if (
            $hadPrevious &&
            is_file($previous)
        ) {
            @unlink(
                $previous
            );
        }

        return
            australiaUserOarSlotInfo(
                $owner,
                $region,
                $chosenSlot
            );
    }
    finally {
        if (
            $locked
        ) {
            @flock(
                $lock,
                LOCK_UN
            );
        }

        @fclose(
            $lock
        );
    }
}
/*
 * ============================================================
 * AUSTRALIA OAR PENDING FINALIZER V1
 *
 * Save OAR can outlive the original browser HTTP request.
 *
 * The initial request marks a region as pending.
 * The Regions data refresh later detects the completed
 * DreamGrid OAR and installs it into one of TWO resident slots.
 *
 * A hard link is used where possible so multi-GB OAR files do
 * not need to be duplicated just to create the saved slot.
 * ============================================================
 */


function australiaUserOarPendingRoot()
{
    $root =
        australiaUserOarBackupRoot() .
        DIRECTORY_SEPARATOR .
        '.pending';

    if (!is_dir($root)) {

        if (
            !@mkdir(
                $root,
                0775,
                true
            )
            &&
            !is_dir($root)
        ) {

            throw new RuntimeException(
                'Unable to create pending OAR folder.'
            );
        }
    }

    return $root;
}


function australiaUserOarPendingPath(
    $owner,
    $region
) {

    $key =
        sha1(
            strtolower(
                trim((string)$owner) .
                "\n" .
                trim((string)$region)
            )
        );

    return
        australiaUserOarPendingRoot() .
        DIRECTORY_SEPARATOR .
        $key .
        '.json';
}


function australiaUserOarMarkPending(
    $owner,
    $region,
    $startedAt = null
) {

    if ($startedAt === null) {
        $startedAt = microtime(true);
    }

    $path =
        australiaUserOarPendingPath(
            $owner,
            $region
        );

    $payload =
        array(
            'owner' =>
                trim((string)$owner),

            'region' =>
                trim((string)$region),

            'started' =>
                (float)$startedAt,

            'created' =>
                time()
        );

    $json =
        json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES
        );

    if (!is_string($json)) {

        throw new RuntimeException(
            'Unable to encode pending OAR state.'
        );
    }

    if (
        @file_put_contents(
            $path,
            $json,
            LOCK_EX
        ) === false
    ) {

        throw new RuntimeException(
            'Unable to save pending OAR state.'
        );
    }

    return $path;
}


function australiaUserOarClearPending(
    $owner,
    $region
) {

    $path =
        australiaUserOarPendingPath(
            $owner,
            $region
        );

    if (is_file($path)) {
        @unlink($path);
    }
}


function australiaUserOarReadPending(
    $owner,
    $region
) {

    $path =
        australiaUserOarPendingPath(
            $owner,
            $region
        );

    if (!is_file($path)) {
        return null;
    }

    $raw =
        @file_get_contents(
            $path
        );

    if ($raw === false) {
        return null;
    }

    $data =
        json_decode(
            $raw,
            true
        );

    if (!is_array($data)) {
        return null;
    }

    return $data;
}


/*
 * Install a DreamGrid OAR into one of TWO saved OAR slots.
 *
 * Hard link first:
 *   nearly instant, even for very large OAR files.
 *
 * If hard linking is not available, copy is used as fallback.
 */
function australiaUserOarStoreLinked(
    $owner,
    $region,
    $sourcePath
) {

    if (!is_file($sourcePath)) {

        throw new RuntimeException(
            'Completed DreamGrid OAR was not found.'
        );
    }


    clearstatcache(
        true,
        $sourcePath
    );


    $sourceSize =
        (int)@filesize(
            $sourcePath
        );


    if ($sourceSize <= 0) {

        throw new RuntimeException(
            'Completed DreamGrid OAR is empty.'
        );
    }


    $folder =
        australiaUserOarRegionFolder(
            $owner,
            $region,
            true
        );


    $lockPath =
        $folder .
        DIRECTORY_SEPARATOR .
        '.slots.lock';


    $lock =
        @fopen(
            $lockPath,
            'c'
        );


    if (!$lock) {

        throw new RuntimeException(
            'Unable to open OAR slot lock.'
        );
    }


    $locked =
        false;


    try {

        if (
            !@flock(
                $lock,
                LOCK_EX
            )
        ) {

            throw new RuntimeException(
                'Unable to lock OAR slots.'
            );
        }


        $locked =
            true;


        /*
         * Choose an empty slot first.
         * Otherwise replace the oldest of SLOT 1 / SLOT 2.
         */
        $chosen =
            0;

        $oldestSlot =
            0;

        $oldestTime =
            PHP_INT_MAX;


        for (
            $slot = 1;
            $slot <= 2;
            $slot++
        ) {

            $slotPath =
                australiaUserOarSlotPath(
                    $owner,
                    $region,
                    $slot
                );


            if (!is_file($slotPath)) {

                if ($chosen === 0) {
                    $chosen = $slot;
                }

                continue;
            }


            clearstatcache(
                true,
                $slotPath
            );


            $mtime =
                (int)@filemtime(
                    $slotPath
                );


            if ($mtime < $oldestTime) {

                $oldestTime =
                    $mtime;

                $oldestSlot =
                    $slot;
            }
        }


        if ($chosen === 0) {
            $chosen = $oldestSlot;
        }


        if (
            $chosen < 1
            ||
            $chosen > 2
        ) {

            throw new RuntimeException(
                'Unable to select OAR backup slot.'
            );
        }


        $target =
            australiaUserOarSlotPath(
                $owner,
                $region,
                $chosen
            );


        $token =
            bin2hex(
                random_bytes(6)
            );


        $temporary =
            $folder .
            DIRECTORY_SEPARATOR .
            '.new-' .
            $token .
            '.oar';


        $old =
            $folder .
            DIRECTORY_SEPARATOR .
            '.old-' .
            $token .
            '.oar';


        /*
         * Hard link is ideal because DreamGrid Autobackup and
         * UserOARBackups are under the same DreamGrid tree.
         */
        $made =
            @link(
                $sourcePath,
                $temporary
            );


        /*
         * Fallback for a filesystem that does not support
         * hard links.
         */
        if (!$made) {

            $made =
                @copy(
                    $sourcePath,
                    $temporary
                );
        }


        if (!$made) {

            throw new RuntimeException(
                'Unable to stage completed OAR.'
            );
        }


        clearstatcache(
            true,
            $temporary
        );


        $temporarySize =
            (int)@filesize(
                $temporary
            );


        if ($temporarySize !== $sourceSize) {

            @unlink($temporary);

            throw new RuntimeException(
                'Staged OAR size does not match source.'
            );
        }


        $hadOld =
            is_file(
                $target
            );


        if ($hadOld) {

            if (
                !@rename(
                    $target,
                    $old
                )
            ) {

                @unlink($temporary);

                throw new RuntimeException(
                    'Unable to move old OAR slot aside.'
                );
            }
        }


        if (
            !@rename(
                $temporary,
                $target
            )
        ) {

            if (
                $hadOld
                &&
                is_file($old)
            ) {

                @rename(
                    $old,
                    $target
                );
            }

            @unlink($temporary);

            throw new RuntimeException(
                'Unable to activate new OAR slot.'
            );
        }


        if (
            $hadOld
            &&
            is_file($old)
        ) {

            @unlink($old);
        }


        /*
         * Slot timestamp represents when it became the saved
         * dashboard backup.
         */
        @touch(
            $target
        );


        /*
         * Never leave old SLOT 3 from the previous system.
         */
        $oldSlot3 =
            $folder .
            DIRECTORY_SEPARATOR .
            'Backup-3.oar';

        if (is_file($oldSlot3)) {
            @unlink($oldSlot3);
        }


        return
            australiaUserOarSlotInfo(
                $owner,
                $region,
                $chosen
            );
    }
    finally {

        if ($locked) {

            @flock(
                $lock,
                LOCK_UN
            );
        }

        @fclose(
            $lock
        );
    }
}


/*
 * Called by the Regions data refresh.
 *
 * If Save OAR previously started but its browser request did
 * not survive long enough to install the slot, this completes
 * the job.
 */
function australiaUserOarFileStable(
    $path,
    $samples = 3
) {
    $path = (string)$path;

    if (!is_file($path)) {
        return false;
    }

    clearstatcache(
        true,
        $path
    );

    $size1 =
        (int)@filesize(
            $path
        );

    $time1 =
        (int)@filemtime(
            $path
        );

    if ($size1 <= 0) {
        return false;
    }

    if (
        $time1 >
        (time() - 2)
    ) {
        return false;
    }

    sleep(1);

    clearstatcache(
        true,
        $path
    );

    if (!is_file($path)) {
        return false;
    }

    $size2 =
        (int)@filesize(
            $path
        );

    $time2 =
        (int)@filemtime(
            $path
        );

    sleep(1);

    clearstatcache(
        true,
        $path
    );

    if (!is_file($path)) {
        return false;
    }

    $size3 =
        (int)@filesize(
            $path
        );

    $time3 =
        (int)@filemtime(
            $path
        );

    return (
        $size1 === $size2 &&
        $size2 === $size3 &&
        $time1 === $time2 &&
        $time2 === $time3 &&
        $size3 > 0
    );
}

function australiaUserOarFinalizePending(
    $owner,
    $region
) {

    $pending =
        australiaUserOarReadPending(
            $owner,
            $region
        );


    if (!is_array($pending)) {
        return null;
    }


    $started =
        (float)(
            $pending['started'] ??
            0
        );


    if ($started <= 0) {

        australiaUserOarClearPending(
            $owner,
            $region
        );

        return null;
    }


    /*
     * Stale failed requests are discarded after one hour.
     */
    if (
        microtime(true) -
        $started >
        3600
    ) {

        australiaUserOarClearPending(
            $owner,
            $region
        );

        return null;
    }


    $files =
        australiaDreamGridOarFilesForRegion(
            $region
        );


    foreach ($files as $source) {

        clearstatcache(
            true,
            $source
        );


        $mtime =
            (int)@filemtime(
                $source
            );


        if (
            $mtime <
            ((int)$started - 5)
        ) {
            continue;
        }


        if (
            !australiaUserOarFileStable(
                $source,
                3
            )
        ) {
            continue;
        }


        $stored =
            australiaUserOarStoreLinked(
                $owner,
                $region,
                $source
            );


        australiaUserOarClearPending(
            $owner,
            $region
        );


        return $stored;
    }


    return null;
}

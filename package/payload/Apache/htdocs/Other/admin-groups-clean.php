<?php

require_once __DIR__ . '/admin-groups-clean-controller.php';

function mg_h($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function mg_group_type_class(string $type): string
{
    return $type === 'LOCAL'
        ? 'local'
        : 'hg';
}

function mg_yes_no($value): string
{
    return ((int)$value) !== 0
        ? 'YES'
        : 'NO';
}

function mg_founder_name(array $group): string
{
    $name =
        trim(
            (string)($group['FounderFirstName'] ?? '') .
            ' ' .
            (string)($group['FounderLastName'] ?? '')
        );

    return $name;
}

?>
<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width,initial-scale=1">

<title>Manage Groups</title>

<link
    rel="stylesheet"
    href="/Other/assets/css/admin-groups-clean-v1.css?v=balanced-2026-09-16_13-38-36">

<link
    rel="stylesheet"
    href="/Other/site-design-display.php?slot=admin-control-center-background">

</head>

<body>

<div class="mg-shell">

    <section class="mg-hero">

        <div class="mg-hero-icon">

            <img
                src="/Other/assets/icons/sentinel/users.png"
                alt="">

        </div>

        <div class="mg-hero-copy">

            <div class="mg-kicker">
                ADMIN / GROUP MANAGEMENT
            </div>

            <h1>
                MANAGE GROUPS
            </h1>

            <p>
                Search, review and manage local and Hypergrid group records.
            </p>

        </div>

        <div class="mg-ready">
            GROUP SYSTEM READY
        </div>

    </section>


    <?php if (isset($_GET['deleted_group'])): ?>

        <div class="mg-success">

            Group permanently deleted:

            <strong>
                <?=mg_h(
                    (string)(
                        $_GET['deleted_group_name'] ?? ''
                    )
                )?>
            </strong>

        </div>

    <?php endif; ?>


    <section class="mg-summary-grid">

        <article class="mg-summary-card">

            <div class="mg-summary-icon">

                <img
                    src="/Other/assets/icons/sentinel/users.png"
                    alt="">

            </div>

            <div>

                <span>
                    GROUP RECORDS
                </span>

                <strong>
                    <?=mg_h($totalGroups)?>
                </strong>

            </div>

        </article>


        <article class="mg-summary-card">

            <div class="mg-summary-icon">

                <img
                    src="/Other/assets/icons/sentinel/security.png"
                    alt="">

            </div>

            <div>

                <span>
                    LOCAL GROUPS
                </span>

                <strong>
                    <?=mg_h($localGroups)?>
                </strong>

            </div>

        </article>


        <article class="mg-summary-card">

            <div class="mg-summary-icon">

                <img
                    src="/Other/assets/icons/sentinel/account.png"
                    alt="">

            </div>

            <div>

                <span>
                    MEMBERSHIPS
                </span>

                <strong>
                    <?=mg_h($totalMemberships)?>
                </strong>

            </div>

        </article>


        <article class="mg-summary-card">

            <div class="mg-summary-icon">

                <img
                    src="/Other/assets/icons/sentinel/alert.png"
                    alt="">

            </div>

            <div>

                <span>
                    GROUP NOTICES
                </span>

                <strong>
                    <?=mg_h($totalNotices)?>
                </strong>

            </div>

        </article>

    </section>


    <section class="mg-toolbar">

        <form
            class="mg-search"
            method="get"
            action="/Other/admin-groups-clean.php">

            <img
                src="/Other/assets/icons/sentinel/search.png"
                alt="">

            <input
                type="text"
                name="q"
                value="<?=mg_h($q)?>"
                placeholder="Search group name, GroupID, founder name or UUID">

            <button type="submit">
                SEARCH
            </button>

            <?php if ($q !== ''): ?>

                <a
                    class="mg-clear"
                    href="/Other/admin-groups-clean.php">

                    CLEAR

                </a>

            <?php endif; ?>

        </form>


        <a
            class="mg-create"
            href="<?=mg_h('/Other/admin-group-create-clean.php')?>">

            <img
                src="/Other/assets/icons/sentinel/users.png"
                alt="">

            CREATE GROUP

        </a>

    </section>


    <section class="mg-main-grid">

        <aside class="mg-group-list-panel">

            <div class="mg-panel-heading">

                <div>

                    <span>
                        GROUP DIRECTORY
                    </span>

                    <strong>
                        <?=count($groups)?>
                        SHOWN
                    </strong>

                </div>

            </div>


            <div class="mg-group-list">

                <?php if (!$groups): ?>

                    <div class="mg-empty">

                        No groups matched your search.

                    </div>

                <?php else: ?>

                    <?php foreach ($groups as $group): ?>

                        <?php

                        $gid =
                            (string)$group['GroupID'];

                        $founderName =
                            mg_founder_name(
                                $group
                            );

                        $type =
                            groupType(
                                $group
                            );

                        $url =
                            '/Other/admin-groups-clean.php?' .
                            http_build_query(
                                array_filter(
                                    [
                                        'q'  => $q,
                                        'id' => $gid,
                                    ],
                                    static fn($value) =>
                                        $value !== ''
                                )
                            );

                        $selectedClass =
                            $selectedId === $gid
                                ? ' selected'
                                : '';

                        ?>

                        <a
                            class="mg-group-card<?=$selectedClass?>"
                            href="<?=mg_h($url)?>">

                            <div class="mg-group-card-top">

                                <strong class="mg-group-name">
                                    <?=mg_h($group['Name'])?>
                                </strong>

                                <span
                                    class="mg-type-badge <?=mg_group_type_class($type)?>">

                                    <?=mg_h($type)?>

                                </span>

                            </div>


                            <div class="mg-group-stats">

                                <span>
                                    Members:
                                    <strong>
                                        <?=mg_h($group['MemberCount'])?>
                                    </strong>
                                </span>

                                <span>
                                    Roles:
                                    <strong>
                                        <?=mg_h($group['RoleCount'])?>
                                    </strong>
                                </span>

                                <span>
                                    Notices:
                                    <strong>
                                        <?=mg_h($group['NoticeCount'])?>
                                    </strong>
                                </span>

                            </div>


                            <div class="mg-founder">

                                Founder:

                                <?= $founderName !== ''
                                    ? mg_h($founderName)
                                    : mg_h($group['FounderID']) ?>

                            </div>

                        </a>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </aside>


        <main class="mg-detail-panel">

            <?php if (!$selected): ?>

                <div class="mg-select-message">

                    <img
                        src="/Other/assets/icons/sentinel/view.png"
                        alt="">

                    <strong>
                        SELECT A GROUP
                    </strong>

                    <span>
                        View group details, members, roles and recent notices.
                    </span>

                </div>

            <?php else: ?>

                <?php

                $selectedFounderName =
                    mg_founder_name(
                        $selected
                    );

                $selectedType =
                    groupType(
                        $selected
                    );

                ?>

                <section class="mg-detail-hero">

                    <div>

                        <div class="mg-kicker">
                            SELECTED GROUP
                        </div>

                        <div class="mg-detail-title-row">

                            <h2>
                                <?=mg_h($selected['Name'])?>
                            </h2>

                            <span
                                class="mg-type-badge <?=mg_group_type_class($selectedType)?>">

                                <?=mg_h($selectedType)?>

                            </span>

                        </div>

                        <div class="mg-uuid">
                            <?=mg_h($selected['GroupID'])?>
                        </div>

                    </div>


                    <?php if ($selectedType === 'LOCAL'): ?>

                        <a
                            class="mg-manage-button"
                            href="/Other/admin-group-manage-clean.php?id=<?=rawurlencode((string)$selected['GroupID'])?>">

                            MANAGE GROUP

                        </a>

                    <?php endif; ?>

                </section>


                <section class="mg-detail-grid">

                    <article>

                        <span>
                            FOUNDER
                        </span>

                        <strong>
                            <?= $selectedFounderName !== ''
                                ? mg_h($selectedFounderName)
                                : '—' ?>
                        </strong>

                    </article>


                    <article>

                        <span>
                            FOUNDER UUID
                        </span>

                        <strong class="mg-uuid">
                            <?=mg_h($selected['FounderID'])?>
                        </strong>

                    </article>


                    <article>

                        <span>
                            LOCATION
                        </span>

                        <strong>
                            <?= trim((string)$selected['Location']) !== ''
                                ? mg_h($selected['Location'])
                                : '—' ?>
                        </strong>

                    </article>


                    <article>

                        <span>
                            MEMBERSHIP FEE
                        </span>

                        <strong>
                            <?=mg_h($selected['MembershipFee'])?>
                        </strong>

                    </article>


                    <article>

                        <span>
                            OPEN ENROLLMENT
                        </span>

                        <strong>
                            <?=mg_yes_no($selected['OpenEnrollment'])?>
                        </strong>

                    </article>


                    <article>

                        <span>
                            SHOW IN LIST
                        </span>

                        <strong>
                            <?=mg_yes_no($selected['ShowInList'])?>
                        </strong>

                    </article>


                    <article>

                        <span>
                            ALLOW PUBLISH
                        </span>

                        <strong>
                            <?=mg_yes_no($selected['AllowPublish'])?>
                        </strong>

                    </article>


                    <article>

                        <span>
                            MATURE PUBLISH
                        </span>

                        <strong>
                            <?=mg_yes_no($selected['MaturePublish'])?>
                        </strong>

                    </article>


                    <article class="wide">

                        <span>
                            OWNER ROLE ID
                        </span>

                        <strong class="mg-uuid">
                            <?=mg_h($selected['OwnerRoleID'])?>
                        </strong>

                    </article>


                    <?php if (
                        trim(
                            (string)($selected['Charter'] ?? '')
                        ) !== ''
                    ): ?>

                        <article class="wide">

                            <span>
                                CHARTER
                            </span>

                            <div class="mg-charter">
                                <?=nl2br(
                                    mg_h(
                                        $selected['Charter']
                                    )
                                )?>
                            </div>

                        </article>

                    <?php endif; ?>

                </section>


                <section class="mg-section">

                    <div class="mg-section-heading">

                        <div>

                            <span>
                                GROUP MEMBERS
                            </span>

                            <h3>
                                Members
                            </h3>

                        </div>

                        <strong>
                            <?=count($members)?>
                        </strong>

                    </div>


                    <?php if (!$members): ?>

                        <div class="mg-empty">
                            No membership rows.
                        </div>

                    <?php else: ?>

                        <div class="mg-table-wrap">

                            <table>

                                <thead>

                                <tr>

                                    <th>MEMBER</th>
                                    <th>SELECTED ROLE</th>
                                    <th>NOTICES</th>
                                    <th>PROFILE</th>
                                    <th>PRINCIPAL ID</th>

                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach ($members as $member): ?>

                                    <?php

                                    $memberName =
                                        trim(
                                            (string)($member['FirstName'] ?? '') .
                                            ' ' .
                                            (string)($member['LastName'] ?? '')
                                        );

                                    ?>

                                    <tr>

                                        <td>
                                            <?= $memberName !== ''
                                                ? mg_h($memberName)
                                                : 'HG / UNKNOWN MEMBER' ?>
                                        </td>

                                        <td>
                                            <?= trim(
                                                (string)(
                                                    $member['SelectedRoleName'] ?? ''
                                                )
                                            ) !== ''
                                                ? mg_h(
                                                    $member['SelectedRoleName']
                                                )
                                                : 'Everyone / default' ?>
                                        </td>

                                        <td>
                                            <?=mg_yes_no(
                                                $member['AcceptNotices']
                                            )?>
                                        </td>

                                        <td>
                                            <?=mg_yes_no(
                                                $member['ListInProfile']
                                            )?>
                                        </td>

                                        <td class="mg-uuid">
                                            <?=mg_h(
                                                $member['PrincipalID']
                                            )?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </section>


                <section class="mg-section">

                    <div class="mg-section-heading">

                        <div>

                            <span>
                                GROUP ROLES
                            </span>

                            <h3>
                                Roles
                            </h3>

                        </div>

                        <strong>
                            <?=count($roles)?>
                        </strong>

                    </div>


                    <?php if (!$roles): ?>

                        <div class="mg-empty">
                            No role rows.
                        </div>

                    <?php else: ?>

                        <div class="mg-role-grid">

                            <?php foreach ($roles as $role): ?>

                                <article class="mg-role-card">

                                    <div class="mg-role-title">

                                        <strong>
                                            <?= trim(
                                                (string)(
                                                    $role['Name'] ?? ''
                                                )
                                            ) !== ''
                                                ? mg_h($role['Name'])
                                                : 'Unnamed Role' ?>
                                        </strong>

                                        <span>
                                            <?=mg_h(
                                                $role['Title'] ?? ''
                                            )?>
                                        </span>

                                    </div>

                                    <?php if (
                                        trim(
                                            (string)(
                                                $role['Description'] ?? ''
                                            )
                                        ) !== ''
                                    ): ?>

                                        <p>
                                            <?=mg_h(
                                                $role['Description']
                                            )?>
                                        </p>

                                    <?php endif; ?>

                                    <div class="mg-uuid">
                                        <?=mg_h($role['RoleID'])?>
                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>


                <section class="mg-section">

                    <div class="mg-section-heading">

                        <div>

                            <span>
                                ASSIGNED ROLES
                            </span>

                            <h3>
                                Role Memberships
                            </h3>

                        </div>

                        <strong>
                            <?=count($roleMemberships)?>
                        </strong>

                    </div>


                    <?php if (!$roleMemberships): ?>

                        <div class="mg-empty">
                            No explicit role membership rows.
                        </div>

                    <?php else: ?>

                        <div class="mg-table-wrap">

                            <table>

                                <thead>

                                <tr>

                                    <th>MEMBER</th>
                                    <th>ROLE</th>
                                    <th>PRINCIPAL ID</th>

                                </tr>

                                </thead>

                                <tbody>

                                <?php foreach (
                                    $roleMemberships as $membership
                                ): ?>

                                    <?php

                                    $memberName =
                                        trim(
                                            (string)($membership['FirstName'] ?? '') .
                                            ' ' .
                                            (string)($membership['LastName'] ?? '')
                                        );

                                    ?>

                                    <tr>

                                        <td>
                                            <?= $memberName !== ''
                                                ? mg_h($memberName)
                                                : 'HG / UNKNOWN MEMBER' ?>
                                        </td>

                                        <td>
                                            <?= trim(
                                                (string)(
                                                    $membership['RoleName'] ?? ''
                                                )
                                            ) !== ''
                                                ? mg_h(
                                                    $membership['RoleName']
                                                )
                                                : mg_h(
                                                    $membership['RoleID']
                                                ) ?>
                                        </td>

                                        <td class="mg-uuid">
                                            <?=mg_h(
                                                $membership['PrincipalID']
                                            )?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </section>


                <section class="mg-section">

                    <div class="mg-section-heading">

                        <div>

                            <span>
                                GROUP ACTIVITY
                            </span>

                            <h3>
                                Recent Notices
                            </h3>

                        </div>

                        <strong>
                            <?=count($notices)?>
                        </strong>

                    </div>


                    <?php if (!$notices): ?>

                        <div class="mg-empty">
                            No notices stored for this group.
                        </div>

                    <?php else: ?>

                        <div class="mg-notices">

                            <?php foreach ($notices as $notice): ?>

                                <article class="mg-notice-card">

                                    <div class="mg-notice-top">

                                        <strong>
                                            <?=mg_h(
                                                $notice['Subject']
                                            )?>
                                        </strong>

                                        <?php if (
                                            (int)$notice['HasAttachment']
                                        ): ?>

                                            <span class="mg-attachment">
                                                ATTACHMENT
                                            </span>

                                        <?php endif; ?>

                                    </div>


                                    <div class="mg-notice-meta">

                                        From
                                        <?=mg_h(
                                            $notice['FromName']
                                        )?>

                                        <span>•</span>

                                        <?= (int)$notice['TMStamp'] > 0
                                            ? mg_h(
                                                date(
                                                    'j M Y g:i A',
                                                    (int)$notice['TMStamp']
                                                )
                                            )
                                            : 'Unknown time' ?>

                                    </div>


                                    <?php if (
                                        trim(
                                            (string)$notice['Message']
                                        ) !== ''
                                    ): ?>

                                        <div class="mg-notice-message">
                                            <?=nl2br(
                                                mg_h(
                                                    $notice['Message']
                                                )
                                            )?>
                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        (int)$notice['HasAttachment']
                                    ): ?>

                                        <div class="mg-notice-attachment">

                                            Attachment:
                                            <?=mg_h(
                                                $notice['AttachmentName']
                                            )?>

                                        </div>

                                    <?php endif; ?>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

        </main>

    </section>

</div>

</body>
</html>
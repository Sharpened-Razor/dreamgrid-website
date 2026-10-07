Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

Add-Type -AssemblyName UIAutomationClient
Add-Type -AssemblyName UIAutomationTypes
Add-Type -AssemblyName System.Windows.Forms


if (-not ("GridRegionBridgeNative" -as [type])) {

    Add-Type @"
using System;
using System.Text;
using System.Runtime.InteropServices;

public static class GridRegionBridgeNative
{
    public const int WM_COMMAND    = 0x0111;

    public const int CB_GETCOUNT     = 0x0146;
    public const int CB_GETCURSEL    = 0x0147;
    public const int CB_GETLBTEXT    = 0x0148;
    public const int CB_GETLBTEXTLEN = 0x0149;
    public const int CB_SETCURSEL    = 0x014E;

    public const int CBN_SELCHANGE = 1;
    public const int CBN_SELENDOK  = 9;

    [StructLayout(LayoutKind.Sequential)]
    public struct POINT
    {
        public int X;
        public int Y;
    }

    [DllImport("user32.dll")]
    public static extern bool GetCursorPos(out POINT p);

    [DllImport("user32.dll")]
    public static extern bool SetCursorPos(int x, int y);

    [DllImport("user32.dll")]
    public static extern void mouse_event(
        uint flags,
        uint dx,
        uint dy,
        uint data,
        UIntPtr extra
    );

    [DllImport("user32.dll")]
    public static extern bool SetForegroundWindow(
        IntPtr hWnd
    );

    [DllImport("user32.dll")]
    public static extern IntPtr GetParent(
        IntPtr hWnd
    );

    [DllImport("user32.dll")]
    public static extern int GetDlgCtrlID(
        IntPtr hWnd
    );

    [DllImport("user32.dll", CharSet = CharSet.Unicode)]
    public static extern IntPtr SendMessage(
        IntPtr hWnd,
        int msg,
        IntPtr wParam,
        IntPtr lParam
    );

    [DllImport("user32.dll", CharSet = CharSet.Unicode)]
    public static extern IntPtr SendMessage(
        IntPtr hWnd,
        int msg,
        IntPtr wParam,
        StringBuilder lParam
    );
}
"@
}


# ------------------------------------------------------------
# PORTABLE PACKAGE PATHS
# ------------------------------------------------------------

$packageRoot =
    Split-Path $PSScriptRoot -Parent

$privateDir =
    Join-Path $packageRoot "private"

$queueDir =
    Join-Path $privateDir "region-bridge"

New-Item `
    -ItemType Directory `
    -Path $queueDir `
    -Force |
Out-Null


# Walk upward from the package directory to the manager root

$managerRoot =
    $packageRoot

for ($i = 0; $i -lt 4; $i++) {

    $managerRoot =
        Split-Path $managerRoot -Parent
}

$startExe =
    Join-Path $managerRoot "Start.exe"


# ------------------------------------------------------------
# MUTEX - ONLY ONE UI WRITER AT A TIME
# ------------------------------------------------------------

$mutex =
    New-Object System.Threading.Mutex(
        $false,
        "GridWebsiteRegionSettingsBridge"
    )

$hasMutex =
    $false

try {

    $hasMutex =
        $mutex.WaitOne(
            [TimeSpan]::FromSeconds(2)
        )

    if (-not $hasMutex) {
        exit 0
    }


    # --------------------------------------------------------
    # FIND START.EXE
    # --------------------------------------------------------

    function Find-StartProcess {

        return (
            Get-CimInstance Win32_Process |
            Where-Object {
                $_.Name -eq "Start.exe" -and
                $_.ExecutablePath -eq $startExe
            } |
            Select-Object -First 1
        )
    }


    # --------------------------------------------------------
    # FIND REGION WINDOW
    # --------------------------------------------------------

    function Find-RegionsWindow {

        param(
            [int]$ProcessId
        )

        $desktop =
            [System.Windows.Automation.AutomationElement]::RootElement

        $condition =
            New-Object System.Windows.Automation.PropertyCondition(
                [System.Windows.Automation.AutomationElement]::ProcessIdProperty,
                $ProcessId
            )

        $windows =
            $desktop.FindAll(
                [System.Windows.Automation.TreeScope]::Children,
                $condition
            )

        foreach ($window in $windows) {

            try {

                if (
                    [string]$window.Current.AutomationId -eq
                    "Region List"
                ) {
                    return $window
                }
            }
            catch {
            }
        }

        return $null
    }


    # --------------------------------------------------------
    # RELIABLE DETAILSGRID LOCATOR
    # --------------------------------------------------------

    function Find-DetailsGrid {

        param(
            [int]$ProcessId
        )

        for ($attempt = 1; $attempt -le 20; $attempt++) {

            $window =
                Find-RegionsWindow `
                    -ProcessId $ProcessId

            if (!$window) {

                Start-Sleep -Milliseconds 300

                continue
            }


            try {

                $windowPattern =
                    $null

                if (
                    $window.TryGetCurrentPattern(
                        [System.Windows.Automation.WindowPattern]::Pattern,
                        [ref]$windowPattern
                    )
                ) {

                    if (
                        $windowPattern.Current.WindowVisualState -eq
                        [System.Windows.Automation.WindowVisualState]::Minimized
                    ) {

                        $windowPattern.SetWindowVisualState(
                            [System.Windows.Automation.WindowVisualState]::Normal
                        )

                        Start-Sleep -Milliseconds 300
                    }
                }


                $handle =
                    [IntPtr][int]$window.Current.NativeWindowHandle

                if ($handle -ne [IntPtr]::Zero) {

                    [void][GridRegionBridgeNative]::SetForegroundWindow(
                        $handle
                    )
                }


                $window.SetFocus()

                Start-Sleep -Milliseconds 250
            }
            catch {
            }


            $condition =
                New-Object System.Windows.Automation.PropertyCondition(
                    [System.Windows.Automation.AutomationElement]::AutomationIdProperty,
                    "DetailsGrid"
                )


            try {

                $grid =
                    $window.FindFirst(
                        [System.Windows.Automation.TreeScope]::Descendants,
                        $condition
                    )

                if ($grid) {
                    return $grid
                }
            }
            catch {
            }


            Start-Sleep -Milliseconds 300
        }

        return $null
    }


    # --------------------------------------------------------
    # FIND REGION ROW BY REAL REGION NAME
    # --------------------------------------------------------

    function Find-RegionRow {

        param(
            [System.Windows.Automation.AutomationElement]$Grid,
            [string]$RegionName
        )

        $elements =
            $Grid.FindAll(
                [System.Windows.Automation.TreeScope]::Descendants,
                [System.Windows.Automation.Condition]::TrueCondition
            )


        foreach ($element in $elements) {

            try {

                if (
                    [string]$element.Current.Name -notlike
                    "Region Name Row *"
                ) {
                    continue
                }


                $valuePattern =
                    $element.GetCurrentPattern(
                        [System.Windows.Automation.ValuePattern]::Pattern
                    )


                if (
                    [string]$valuePattern.Current.Value -ne
                    $RegionName
                ) {
                    continue
                }


                $gridPattern =
                    $element.GetCurrentPattern(
                        [System.Windows.Automation.GridItemPattern]::Pattern
                    )


                return [int]$gridPattern.Current.Row
            }
            catch {
            }
        }


        return $null
    }


    # --------------------------------------------------------
    # FIND SETTING CELL
    # --------------------------------------------------------

    function Find-SettingCell {

        param(
            [System.Windows.Automation.AutomationElement]$Grid,
            [string]$Prefix,
            [int]$Row
        )

        $elements =
            $Grid.FindAll(
                [System.Windows.Automation.TreeScope]::Descendants,
                [System.Windows.Automation.Condition]::TrueCondition
            )


        foreach ($element in $elements) {

            try {

                if (
                    [string]$element.Current.Name -notlike
                    "$Prefix Row *"
                ) {
                    continue
                }


                $gridPattern =
                    $element.GetCurrentPattern(
                        [System.Windows.Automation.GridItemPattern]::Pattern
                    )


                if (
                    [int]$gridPattern.Current.Row -eq
                    $Row
                ) {
                    return $element
                }
            }
            catch {
            }
        }


        return $null
    }


    # --------------------------------------------------------
    # FIND REAL WINFORMS EDITING COMBO
    # --------------------------------------------------------

    function Find-EditingCombo {

        param(
            [int]$ProcessId
        )


        for (
            $attempt = 1;
            $attempt -le 20;
            $attempt++
        ) {

            $root =
                [System.Windows.Automation.AutomationElement]::RootElement


            $processCondition =
                New-Object System.Windows.Automation.PropertyCondition(
                    [System.Windows.Automation.AutomationElement]::ProcessIdProperty,
                    $ProcessId
                )


            $windows =
                $root.FindAll(
                    [System.Windows.Automation.TreeScope]::Children,
                    $processCondition
                )


            $fallback =
                $null


            foreach ($window in $windows) {

                try {

                    if (
                        [string]$window.Current.AutomationId -ne
                        "Region List"
                    ) {
                        continue
                    }


                    $controls =
                        $window.FindAll(
                            [System.Windows.Automation.TreeScope]::Descendants,
                            [System.Windows.Automation.Condition]::TrueCondition
                        )


                    foreach ($control in $controls) {

                        try {

                            if (
                                [string]$control.Current.ControlType.ProgrammaticName -ne
                                "ControlType.ComboBox"
                            ) {
                                continue
                            }


                            if (
                                [int]$control.Current.NativeWindowHandle -eq 0
                            ) {
                                continue
                            }


                            if (
                                [bool]$control.Current.HasKeyboardFocus
                            ) {

                                return $control
                            }


                            if (!$fallback) {
                                $fallback = $control
                            }
                        }
                        catch {
                        }
                    }
                }
                catch {
                }
            }


            if ($fallback) {
                return $fallback
            }


            Start-Sleep -Milliseconds 150
        }


        return $null
    }


    function Get-ComboItems {

        param(
            [IntPtr]$Handle
        )

        $count =
            [GridRegionBridgeNative]::SendMessage(
                $Handle,
                [GridRegionBridgeNative]::CB_GETCOUNT,
                [IntPtr]::Zero,
                [IntPtr]::Zero
            ).ToInt32()


        $result =
            @()


        for ($i = 0; $i -lt $count; $i++) {

            $length =
                [GridRegionBridgeNative]::SendMessage(
                    $Handle,
                    [GridRegionBridgeNative]::CB_GETLBTEXTLEN,
                    [IntPtr]$i,
                    [IntPtr]::Zero
                ).ToInt32()


            if ($length -lt 0) {
                continue
            }


            $buffer =
                New-Object System.Text.StringBuilder(
                    ($length + 2)
                )


            [void][GridRegionBridgeNative]::SendMessage(
                $Handle,
                [GridRegionBridgeNative]::CB_GETLBTEXT,
                [IntPtr]$i,
                $buffer
            )


            $result +=
                $buffer.ToString()
        }


        return $result
    }


    # --------------------------------------------------------
    # SET MAPS THROUGH REAL NATIVE COMBO
    # --------------------------------------------------------

    function Set-NativeMaps {

    param(
        [int]$ProcessId,
        [string]$RegionName,

        [Alias("NativeValue")]
        [string]$RequestedValue
    )


    # Accept both our web values and native display values.
    switch -Regex ($RequestedValue) {

        '^(Default|Use Default)$' {

            $expectedValues =
                @(
                    "Use Default",
                    "Default"
                )

            break
        }


        '^None$' {

            $expectedValues =
                @(
                    "None"
                )

            break
        }


        '^(Simple|Simple but fast)$' {

            $expectedValues =
                @(
                    "Simple but fast",
                    "Simple"
                )

            break
        }


        '^(Good|Good \(Warp3D\))$' {

            $expectedValues =
                @(
                    "Good (Warp3D)",
                    "Good"
                )

            break
        }


        '^(Better|Better \(Prims, Slow\))$' {

            $expectedValues =
                @(
                    "Better (Prims, Slow)",
                    "Better"
                )

            break
        }


        '^(Best|Best \(Prims \+ Mesh, Very Slow\))$' {

            $expectedValues =
                @(
                    "Best (Prims + Mesh, Very Slow)",
                    "Best"
                )

            break
        }


        default {

            throw (
                "Unsupported Maps value '$RequestedValue'."
            )
        }
    }


    # --------------------------------------------------------
    # FIND NATIVE REGIONS GRID / ROW / MAPS CELL
    # --------------------------------------------------------

    $grid =
        Find-DetailsGrid `
            -ProcessId $ProcessId


    if (!$grid) {
        throw "Native Regions grid was not available."
    }


    $row =
        Find-RegionRow `
            -Grid $grid `
            -RegionName $RegionName


    if ($null -eq $row) {

        throw (
            "Region '$RegionName' was not found in the native Regions window."
        )
    }


    $cell =
        Find-SettingCell `
            -Grid $grid `
            -Prefix "Maps" `
            -Row $row


    if (!$cell) {

        throw (
            "Maps cell was not found for region '$RegionName'."
        )
    }


    # --------------------------------------------------------
    # IF NATIVE GUI IS ALREADY ON THE REQUESTED VALUE,
    # RETURN SUCCESS WITHOUT NEEDLESSLY EDITING IT.
    # --------------------------------------------------------

    try {

        $currentPattern =
            $cell.GetCurrentPattern(
                [System.Windows.Automation.ValuePattern]::Pattern
            )


        $currentValue =
            [string]$currentPattern.Current.Value


        foreach ($candidate in $expectedValues) {

            if (
                [string]::Equals(
                    $currentValue,
                    $candidate,
                    [System.StringComparison]::OrdinalIgnoreCase
                )
            ) {

                return $currentValue
            }
        }
    }
    catch {
        # Continue into native editing.
    }


    # --------------------------------------------------------
    # SAVE CURRENT MOUSE POSITION
    # --------------------------------------------------------

    $oldMouse =
        New-Object GridRegionBridgeNative+POINT


    [void][GridRegionBridgeNative]::GetCursorPos(
        [ref]$oldMouse
    )


    try {

        # ----------------------------------------------------
        # PHYSICALLY CLICK THE REAL MAPS DATAGRIDVIEW CELL
        # ----------------------------------------------------

        $rect =
            $cell.Current.BoundingRectangle


        if (
            $rect.Width -le 0 -or
            $rect.Height -le 0
        ) {

            throw "Maps cell has no clickable screen area."
        }


        $x =
            [int][Math]::Round(
                $rect.Left +
                ($rect.Width / 2)
            )


        $y =
            [int][Math]::Round(
                $rect.Top +
                ($rect.Height / 2)
            )


        [void][GridRegionBridgeMouse]::SetCursorPos(
            $x,
            $y
        )


        Start-Sleep -Milliseconds 150


        [GridRegionBridgeMouse]::mouse_event(
            [GridRegionBridgeMouse]::LEFTDOWN,
            0,
            0,
            0,
            [UIntPtr]::Zero
        )


        Start-Sleep -Milliseconds 70


        [GridRegionBridgeMouse]::mouse_event(
            [GridRegionBridgeMouse]::LEFTUP,
            0,
            0,
            0,
            [UIntPtr]::Zero
        )


        Start-Sleep -Milliseconds 200


        # ----------------------------------------------------
        # CREATE THE REAL WINDOWS FORMS COMBOBOX EDITOR
        # ----------------------------------------------------

        [System.Windows.Forms.SendKeys]::SendWait(
            "{F2}"
        )


        Start-Sleep -Milliseconds 350


        $combo =
            Find-EditingCombo `
                -ProcessId $ProcessId


        if (!$combo) {

            throw (
                "Native Maps editing control was not created."
            )
        }


        $comboHandle =
            [IntPtr][int]$combo.Current.NativeWindowHandle


        if ($comboHandle -eq [IntPtr]::Zero) {

            throw (
                "Native Maps editing control has no window handle."
            )
        }


        # ----------------------------------------------------
        # READ THE MANAGER'S REAL MAPS VALUES
        # ----------------------------------------------------

        $items =
            @(
                Get-ComboItems `
                    -Handle $comboHandle
            )


        if ($items.Count -eq 0) {

            throw (
                "Native Maps dropdown contains no values."
            )
        }


        $targetIndex =
            -1


        $nativeTarget =
            ""


        for (
            $i = 0;
            $i -lt $items.Count;
            $i++
        ) {

            $item =
                [string]$items[$i]


            foreach ($candidate in $expectedValues) {

                if (
                    [string]::Equals(
                        $item,
                        $candidate,
                        [System.StringComparison]::OrdinalIgnoreCase
                    )
                ) {

                    $targetIndex =
                        $i

                    $nativeTarget =
                        $item

                    break
                }
            }


            if ($targetIndex -ge 0) {
                break
            }
        }


        if ($targetIndex -lt 0) {

            throw (
                "Requested Maps value was not found. Native values: " +
                ($items -join ", ")
            )
        }


        # ----------------------------------------------------
        # SELECT THE REAL NATIVE COMBOBOX VALUE
        # ----------------------------------------------------

        $result =
            [GridRegionBridgeNative]::SendMessage(
                $comboHandle,
                [GridRegionBridgeNative]::CB_SETCURSEL,
                [IntPtr]$targetIndex,
                [IntPtr]::Zero
            ).ToInt32()


        if ($result -lt 0) {

            throw (
                "Native Maps dropdown rejected '$nativeTarget'."
            )
        }


        # ----------------------------------------------------
        # FIRE THE SAME WINDOWS COMBOBOX NOTIFICATIONS USED
        # BY THE WORKING SMART START BRIDGE.
        # ----------------------------------------------------

        $parentHandle =
            [GridRegionBridgeNative]::GetParent(
                $comboHandle
            )


        if ($parentHandle -eq [IntPtr]::Zero) {

            throw (
                "Native Maps parent control was not found."
            )
        }


        $controlId =
            [GridRegionBridgeNative]::GetDlgCtrlID(
                $comboHandle
            )


        # WM_COMMAND
        $WM_COMMAND =
            0x0111


        # CBN_SELCHANGE
        $selChange =
            [IntPtr][int64](
                ($controlId -band 0xFFFF) -bor
                (1 -shl 16)
            )


        # CBN_SELENDOK
        $selEnd =
            [IntPtr][int64](
                ($controlId -band 0xFFFF) -bor
                (9 -shl 16)
            )


        [void][GridRegionBridgeNative]::SendMessage(
            $parentHandle,
            $WM_COMMAND,
            $selChange,
            $comboHandle
        )


        Start-Sleep -Milliseconds 100


        [void][GridRegionBridgeNative]::SendMessage(
            $parentHandle,
            $WM_COMMAND,
            $selEnd,
            $comboHandle
        )


        Start-Sleep -Milliseconds 200


        # ----------------------------------------------------
        # TAB OUT SO DATAGRIDVIEW COMMITS AND THE MANAGER RUNS
        # ITS OWN MAP SETTINGS SAVE CODE.
        # ----------------------------------------------------

        try {
            $combo.SetFocus()
        }
        catch {
        }


        [System.Windows.Forms.SendKeys]::SendWait(
            "{TAB}"
        )


        Start-Sleep -Milliseconds 1800


        # ----------------------------------------------------
        # STRICTLY VERIFY THE NATIVE MAPS CELL
        # ----------------------------------------------------

        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$verifyGrid) {

            throw (
                "Regions grid disappeared during Maps verification."
            )
        }


        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName


        if ($null -eq $verifyRow) {

            throw (
                "Region disappeared during Maps verification."
            )
        }


        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "Maps" `
                -Row $verifyRow


        if (!$verifyCell) {

            throw (
                "Maps cell disappeared during verification."
            )
        }


        try {

            $vp =
                $verifyCell.GetCurrentPattern(
                    [System.Windows.Automation.ValuePattern]::Pattern
                )


            $actual =
                [string]$vp.Current.Value
        }
        catch {

            throw (
                "Native Maps value could not be read after the change."
            )
        }


        $verified =
            $false


        foreach ($candidate in $expectedValues) {

            if (
                [string]::Equals(
                    $actual,
                    $candidate,
                    [System.StringComparison]::OrdinalIgnoreCase
                )
            ) {

                $verified =
                    $true

                break
            }
        }


        if (!$verified) {

            throw (
                "Maps did not change. Requested '$nativeTarget', native value is '$actual'."
            )
        }


        return $actual
    }
    finally {

        [void][GridRegionBridgeNative]::SetCursorPos(
            $oldMouse.X,
            $oldMouse.Y
        )
    }
}


    # --------------------------------------------------------

    # --------------------------------------------------------
    # WEB_NATIVE_SMART_START_BRIDGE
    # --------------------------------------------------------


    if (-not ([System.Management.Automation.PSTypeName]'GridRegionBridgeMouse').Type) {

        Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;

public static class GridRegionBridgeMouse
{
    [DllImport("user32.dll")]
    public static extern bool SetCursorPos(int X, int Y);

    [DllImport("user32.dll")]
    public static extern void mouse_event(
        uint dwFlags,
        uint dx,
        uint dy,
        uint dwData,
        UIntPtr dwExtraInfo
    );

    public const uint LEFTDOWN = 0x0002;
    public const uint LEFTUP   = 0x0004;
}
"@
    }

    function Set-NativeSmartStart {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )


        switch ($RequestedValue) {

            "off" {
                $expected = "Off"
            }

            "boot" {
                $expected = "Smart Boot"
            }

            "suspend" {
                $expected = "Smart Suspend"
            }

            default {
                throw "Unsupported Smart Start value."
            }
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName

        if ($null -eq $row) {
            throw "Region '$RegionName' was not found."
        }


        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Smart Start" `
                -Row $row

        if (!$cell) {

            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "SmartStart" `
                    -Row $row
        }

        if (!$cell) {
            throw "Smart Start cell was not found."
        }


        $oldMouse =
            New-Object GridRegionBridgeNative+POINT

        [void][GridRegionBridgeNative]::GetCursorPos(
            [ref]$oldMouse
        )


        try {

            # ------------------------------------------------
            # CLICK REAL DATAGRIDVIEW CELL
            # ------------------------------------------------

            $rect =
                $cell.Current.BoundingRectangle

            if (
                $rect.Width -le 0 -or
                $rect.Height -le 0
            ) {
                throw "Smart Start cell has no clickable area."
            }


            $x =
                [int][Math]::Round(
                    $rect.Left + ($rect.Width / 2)
                )

            $y =
                [int][Math]::Round(
                    $rect.Top + ($rect.Height / 2)
                )


            [void][GridRegionBridgeMouse]::SetCursorPos(
                $x,
                $y
            )

            Start-Sleep -Milliseconds 150


            [GridRegionBridgeMouse]::mouse_event(
                [GridRegionBridgeMouse]::LEFTDOWN,
                0, 0, 0,
                [UIntPtr]::Zero
            )

            Start-Sleep -Milliseconds 70

            [GridRegionBridgeMouse]::mouse_event(
                [GridRegionBridgeMouse]::LEFTUP,
                0, 0, 0,
                [UIntPtr]::Zero
            )


            Start-Sleep -Milliseconds 200


            # Create the REAL Windows Forms ComboBox editor.
            [System.Windows.Forms.SendKeys]::SendWait(
                "{F2}"
            )

            Start-Sleep -Milliseconds 350


            $combo =
                Find-EditingCombo `
                    -ProcessId $ProcessId


            if (!$combo) {
                throw "Native Smart Start editing control was not created."
            }


            $comboHandle =
                [IntPtr][int]$combo.Current.NativeWindowHandle


            if ($comboHandle -eq [IntPtr]::Zero) {
                throw "Native Smart Start editing control has no handle."
            }


            # ------------------------------------------------
            # READ REAL NATIVE OPTIONS
            # ------------------------------------------------

            $items =
                @(Get-ComboItems -Handle $comboHandle)


            if ($items.Count -eq 0) {
                throw "Native Smart Start dropdown contains no values."
            }


            $targetIndex = -1
            $nativeValue = ""


            for ($i = 0; $i -lt $items.Count; $i++) {

                $item =
                    [string]$items[$i]


                $matches =
                    switch ($RequestedValue) {

                        "off" {
                            $item -ieq "Off"
                        }

                        "boot" {
                            (
                                $item -ieq "Smart Boot" -or
                                $item -ieq "Boot"
                            )
                        }

                        "suspend" {
                            (
                                $item -ieq "Smart Suspend" -or
                                $item -ieq "Suspend"
                            )
                        }
                    }


                if ($matches) {

                    $targetIndex =
                        $i

                    $nativeValue =
                        $item

                    break
                }
            }


            if ($targetIndex -lt 0) {

                throw (
                    "Requested Smart Start value was not found. Native values: " +
                    ($items -join ", ")
                )
            }


            # ------------------------------------------------
            # CHANGE THE REAL NATIVE COMBOBOX
            # ------------------------------------------------

            $result =
                [GridRegionBridgeNative]::SendMessage(
                    $comboHandle,
                    [GridRegionBridgeNative]::CB_SETCURSEL,
                    [IntPtr]$targetIndex,
                    [IntPtr]::Zero
                ).ToInt32()


            if ($result -lt 0) {
                throw "Native Smart Start dropdown rejected the value."
            }


            $parentHandle =
                [GridRegionBridgeNative]::GetParent(
                    $comboHandle
                )


            if ($parentHandle -eq [IntPtr]::Zero) {
                throw "Native Smart Start parent control was not found."
            }


            $controlId =
                [GridRegionBridgeNative]::GetDlgCtrlID(
                    $comboHandle
                )


            # WM_COMMAND = 0x0111
            # CBN_SELCHANGE = 1
            # CBN_SELENDOK  = 9

            $selChange =
                [IntPtr][int64](
                    ($controlId -band 0xFFFF) -bor
                    (1 -shl 16)
                )


            $selEnd =
                [IntPtr][int64](
                    ($controlId -band 0xFFFF) -bor
                    (9 -shl 16)
                )


            [void][GridRegionBridgeNative]::SendMessage(
                $parentHandle,
                0x0111,
                $selChange,
                $comboHandle
            )


            Start-Sleep -Milliseconds 100


            [void][GridRegionBridgeNative]::SendMessage(
                $parentHandle,
                0x0111,
                $selEnd,
                $comboHandle
            )


            Start-Sleep -Milliseconds 200


            # Leave cell so normal DataGridView commit/save runs.
            try {
                $combo.SetFocus()
            }
            catch {
            }


            [System.Windows.Forms.SendKeys]::SendWait(
                "{TAB}"
            )


            Start-Sleep -Milliseconds 1500


            # ------------------------------------------------
            # STRICT VERIFICATION
            # ------------------------------------------------

            $verifyGrid =
                Find-DetailsGrid `
                    -ProcessId $ProcessId

            if (!$verifyGrid) {
                throw "Regions grid disappeared during verification."
            }


            $verifyRow =
                Find-RegionRow `
                    -Grid $verifyGrid `
                    -RegionName $RegionName

            if ($null -eq $verifyRow) {
                throw "Region disappeared during verification."
            }


            $verifyCell =
                Find-SettingCell `
                    -Grid $verifyGrid `
                    -Prefix "Smart Start" `
                    -Row $verifyRow

            if (!$verifyCell) {

                $verifyCell =
                    Find-SettingCell `
                        -Grid $verifyGrid `
                        -Prefix "SmartStart" `
                        -Row $verifyRow
            }

            if (!$verifyCell) {
                throw "Smart Start disappeared during verification."
            }


            $vp =
                $verifyCell.GetCurrentPattern(
                    [System.Windows.Automation.ValuePattern]::Pattern
                )


            $actual =
                [string]$vp.Current.Value


            if (
                ![string]::Equals(
                    $actual,
                    $expected,
                    [System.StringComparison]::OrdinalIgnoreCase
                )
            ) {

                throw (
                    "Smart Start did not change. Requested '$expected', native value is '$actual'."
                )
            }


            return $actual
        }
        finally {

            [void][GridRegionBridgeNative]::SetCursorPos(
                $oldMouse.X,
                $oldMouse.Y
            )
        }
    }


    function Get-NativeMapsSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        try {

            $decoded =
                $RequestedJson |
                ConvertFrom-Json
        }
        catch {

            throw "Invalid Maps snapshot request."
        }


        $regionNames =
            @($decoded)


        if (
            $regionNames.Count -lt 1 -or
            $regionNames.Count -gt 200
        ) {

            throw "Invalid Maps snapshot region count."
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {

            throw "Native Regions grid was not available."
        }


        $values =
            [ordered]@{}


        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()


            if (
                [string]::IsNullOrWhiteSpace($name) -or
                $name.Length -gt 128 -or
                $name -match '[\x00-\x1F]'
            ) {

                continue
            }


            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name


            if ($null -eq $row) {

                continue
            }


            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Maps" `
                    -Row $row


            if (!$cell) {

                continue
            }


            try {

                $vp =
                    $cell.GetCurrentPattern(
                        [System.Windows.Automation.ValuePattern]::Pattern
                    )


                $nativeValue =
                    ([string]$vp.Current.Value).Trim()


                if ($nativeValue -ne "") {

                    $values[$name] =
                        $nativeValue
                }
            }
            catch {
            }
        }


        return $values
    }


    function Set-NativePhysics {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )


        switch ($RequestedValue) {

            "" {
                $expectedValues =
                    @(
                        "Use Default",
                        "Default"
                    )
            }

            "2" {
                $expectedValues =
                    @(
                        "Bullet"
                    )
            }

            "3" {
                $expectedValues =
                    @(
                        "Bullet Threaded"
                    )
            }

            "4" {
                $expectedValues =
                    @(
                        "ubODE"
                    )
            }

            "5" {
                $expectedValues =
                    @(
                        "ubODE Hybrid"
                    )
            }

            default {
                throw "Unsupported Physics value."
            }
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName

        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }


        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Physics" `
                -Row $row

        if (!$cell) {
            throw "Physics cell was not found for '$RegionName'."
        }


        try {

            $vp =
                $cell.GetCurrentPattern(
                    [System.Windows.Automation.ValuePattern]::Pattern
                )

            $current =
                ([string]$vp.Current.Value).Trim()

            foreach ($candidate in $expectedValues) {

                if (
                    [string]::Equals(
                        $current,
                        $candidate,
                        [System.StringComparison]::OrdinalIgnoreCase
                    )
                ) {
                    return $current
                }
            }
        }
        catch {
        }


        $oldMouse =
            New-Object GridRegionBridgeNative+POINT

        [void][GridRegionBridgeNative]::GetCursorPos(
            [ref]$oldMouse
        )


        try {

            $rect =
                $cell.Current.BoundingRectangle

            if (
                $rect.Width -le 0 -or
                $rect.Height -le 0
            ) {
                throw "Physics cell has no clickable area."
            }


            $x =
                [int][Math]::Round(
                    $rect.Left +
                    ($rect.Width / 2)
                )

            $y =
                [int][Math]::Round(
                    $rect.Top +
                    ($rect.Height / 2)
                )


            [void][GridRegionBridgeMouse]::SetCursorPos(
                $x,
                $y
            )

            Start-Sleep -Milliseconds 150


            [GridRegionBridgeMouse]::mouse_event(
                [GridRegionBridgeMouse]::LEFTDOWN,
                0,0,0,
                [UIntPtr]::Zero
            )

            Start-Sleep -Milliseconds 70


            [GridRegionBridgeMouse]::mouse_event(
                [GridRegionBridgeMouse]::LEFTUP,
                0,0,0,
                [UIntPtr]::Zero
            )

            Start-Sleep -Milliseconds 200


            [System.Windows.Forms.SendKeys]::SendWait(
                "{F2}"
            )

            Start-Sleep -Milliseconds 350


            $combo =
                Find-EditingCombo `
                    -ProcessId $ProcessId

            if (!$combo) {
                throw "Native Physics editing control was not created."
            }


            $comboHandle =
                [IntPtr][int]$combo.Current.NativeWindowHandle

            if ($comboHandle -eq [IntPtr]::Zero) {
                throw "Native Physics control has no window handle."
            }


            $items =
                @(
                    Get-ComboItems `
                        -Handle $comboHandle
                )

            if ($items.Count -eq 0) {
                throw "Native Physics dropdown contains no values."
            }


            $targetIndex =
                -1

            $nativeTarget =
                ""


            for (
                $i = 0;
                $i -lt $items.Count;
                $i++
            ) {

                $item =
                    ([string]$items[$i]).Trim()


                foreach ($candidate in $expectedValues) {

                    if (
                        [string]::Equals(
                            $item,
                            $candidate,
                            [System.StringComparison]::OrdinalIgnoreCase
                        )
                    ) {

                        $targetIndex =
                            $i

                        $nativeTarget =
                            $item

                        break
                    }
                }


                if ($targetIndex -ge 0) {
                    break
                }
            }


            if ($targetIndex -lt 0) {

                throw (
                    "Requested Physics value was not found. GUI values: " +
                    ($items -join ", ")
                )
            }


            $result =
                [GridRegionBridgeNative]::SendMessage(
                    $comboHandle,
                    [GridRegionBridgeNative]::CB_SETCURSEL,
                    [IntPtr]$targetIndex,
                    [IntPtr]::Zero
                ).ToInt32()


            if ($result -lt 0) {
                throw "GUI Physics dropdown rejected '$nativeTarget'."
            }


            $parentHandle =
                [GridRegionBridgeNative]::GetParent(
                    $comboHandle
                )

            if ($parentHandle -eq [IntPtr]::Zero) {
                throw "GUI Physics parent control was not found."
            }


            $controlId =
                [GridRegionBridgeNative]::GetDlgCtrlID(
                    $comboHandle
                )


            $selChange =
                [IntPtr][int64](
                    ($controlId -band 0xFFFF) -bor
                    (1 -shl 16)
                )


            $selEnd =
                [IntPtr][int64](
                    ($controlId -band 0xFFFF) -bor
                    (9 -shl 16)
                )


            [void][GridRegionBridgeNative]::SendMessage(
                $parentHandle,
                0x0111,
                $selChange,
                $comboHandle
            )

            Start-Sleep -Milliseconds 100


            [void][GridRegionBridgeNative]::SendMessage(
                $parentHandle,
                0x0111,
                $selEnd,
                $comboHandle
            )

            Start-Sleep -Milliseconds 200


            try {
                $combo.SetFocus()
            }
            catch {
            }


            [System.Windows.Forms.SendKeys]::SendWait(
                "{TAB}"
            )

            Start-Sleep -Milliseconds 1500


            $verifyGrid =
                Find-DetailsGrid `
                    -ProcessId $ProcessId

            $verifyRow =
                Find-RegionRow `
                    -Grid $verifyGrid `
                    -RegionName $RegionName

            $verifyCell =
                Find-SettingCell `
                    -Grid $verifyGrid `
                    -Prefix "Physics" `
                    -Row $verifyRow


            $verifyPattern =
                $verifyCell.GetCurrentPattern(
                    [System.Windows.Automation.ValuePattern]::Pattern
                )

            $actual =
                ([string]$verifyPattern.Current.Value).Trim()


            $verified =
                $false

            foreach ($candidate in $expectedValues) {

                if (
                    [string]::Equals(
                        $actual,
                        $candidate,
                        [System.StringComparison]::OrdinalIgnoreCase
                    )
                ) {

                    $verified =
                        $true

                    break
                }
            }


            if (!$verified) {

                throw (
                    "Physics did not change. Requested '$nativeTarget', GUI value is '$actual'."
                )
            }


            return $actual
        }
        finally {

            [void][GridRegionBridgeNative]::SetCursorPos(
                $oldMouse.X,
                $oldMouse.Y
            )
        }
    }



    function Get-NativePhysicsSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Physics snapshot request."
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $values =
            [ordered]@{}


        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()

            if ($name -eq "") {
                continue
            }


            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name

            if ($null -eq $row) {
                continue
            }


            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Physics" `
                    -Row $row

            if (!$cell) {
                continue
            }


            try {

                $vp =
                    $cell.GetCurrentPattern(
                        [System.Windows.Automation.ValuePattern]::Pattern
                    )

                $value =
                    ([string]$vp.Current.Value).Trim()

                if ($value -ne "") {

                    $values[$name] =
                        $value
                }
            }
            catch {
            }
        }


        return $values
    }


    function Convert-NativeBirdsValue {

        param(
            [string]$Value
        )

        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()

        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }

        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }

        throw "Unsupported Birds value."
    }



    function Get-NativeCheckboxPattern {

        param(
            [System.Windows.Automation.AutomationElement]$Cell
        )


        if (!$Cell) {
            return $null
        }


        # First try the DataGridView cell itself.
        try {

            $pattern =
                $Cell.GetCurrentPattern(
                    [System.Windows.Automation.TogglePattern]::Pattern
                )

            if ($pattern) {
                return $pattern
            }
        }
        catch {
        }


        # Some DataGridView checkbox cells expose TogglePattern
        # on a child CheckBox instead of the cell.
        try {

            $condition =
                New-Object `
                    System.Windows.Automation.PropertyCondition(
                        [System.Windows.Automation.AutomationElement]::ControlTypeProperty,
                        [System.Windows.Automation.ControlType]::CheckBox
                    )


            $checkbox =
                $Cell.FindFirst(
                    [System.Windows.Automation.TreeScope]::Descendants,
                    $condition
                )


            if ($checkbox) {

                $pattern =
                    $checkbox.GetCurrentPattern(
                        [System.Windows.Automation.TogglePattern]::Pattern
                    )

                if ($pattern) {
                    return $pattern
                }
            }
        }
        catch {
        }


        # Last fallback: inspect immediate children for TogglePattern.
        try {

            $children =
                $Cell.FindAll(
                    [System.Windows.Automation.TreeScope]::Children,
                    [System.Windows.Automation.Condition]::TrueCondition
                )


            foreach ($child in $children) {

                try {

                    $pattern =
                        $child.GetCurrentPattern(
                            [System.Windows.Automation.TogglePattern]::Pattern
                        )

                    if ($pattern) {
                        return $pattern
                    }
                }
                catch {
                }
            }
        }
        catch {
        }


        return $null
    }



    function Get-NativeCheckboxState {

        param(
            [System.Windows.Automation.AutomationElement]$Cell
        )


        $pattern =
            Get-NativeCheckboxPattern `
                -Cell $Cell


        if (!$pattern) {
            throw "GUI checkbox does not expose TogglePattern."
        }


        return (
            $pattern.Current.ToggleState -eq
            [System.Windows.Automation.ToggleState]::On
        )
    }



    function Set-NativeBirds {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )


        $desired =
            Convert-NativeBirdsValue `
                -Value $RequestedValue


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName


        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }


        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Birds" `
                -Row $row


        if (!$cell) {
            throw "Birds cell was not found for '$RegionName'."
        }


        $current =
            Get-NativeCheckboxState `
                -Cell $cell


        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }


        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell


        if (!$toggle) {
            throw "Birds checkbox TogglePattern was not found."
        }


        try {
            $cell.SetFocus()
        }
        catch {
        }


        $toggle.Toggle()


        Start-Sleep -Milliseconds 500


        try {

            [System.Windows.Forms.SendKeys]::SendWait(
                "{TAB}"
            )
        }
        catch {
        }


        Start-Sleep -Milliseconds 900


        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName


        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "Birds" `
                -Row $verifyRow


        if (!$verifyCell) {
            throw "Birds cell disappeared during verification."
        }


        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell


        if ($actual -ne $desired) {
            throw "Birds did not change in the GUI."
        }


        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativeBirdsSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Birds snapshot request."
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $values =
            [ordered]@{}


        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()


            if ($name -eq "") {
                continue
            }


            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name


            if ($null -eq $row) {
                continue
            }


            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Birds" `
                    -Row $row


            if (!$cell) {
                continue
            }


            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell


                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }


        return $values
    }


    function Convert-NativeTidesValue {

        param(
            [string]$Value
        )


        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()


        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }


        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }


        throw "Unsupported Tides value."
    }



    function Set-NativeTides {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )


        $desired =
            Convert-NativeTidesValue `
                -Value $RequestedValue


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName


        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }


        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Tides" `
                -Row $row


        if (!$cell) {
            throw "Tides cell was not found for '$RegionName'."
        }


        $current =
            Get-NativeCheckboxState `
                -Cell $cell


        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }


        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell


        if (!$toggle) {
            throw "Tides checkbox TogglePattern was not found."
        }


        try {
            $cell.SetFocus()
        }
        catch {
        }


        $toggle.Toggle()

        Start-Sleep -Milliseconds 500


        try {
            [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        }
        catch {
        }


        Start-Sleep -Milliseconds 900


        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName


        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "Tides" `
                -Row $verifyRow


        if (!$verifyCell) {
            throw "Tides cell disappeared during verification."
        }


        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell


        if ($actual -ne $desired) {
            throw "Tides did not change in the GUI."
        }


        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativeTidesSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Tides snapshot request."
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $values =
            [ordered]@{}


        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()


            if ($name -eq "") {
                continue
            }


            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name


            if ($null -eq $row) {
                continue
            }


            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Tides" `
                    -Row $row


            if (!$cell) {
                continue
            }


            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell


                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }


        return $values
    }


    function Convert-NativeTeleportValue {

        param(
            [string]$Value
        )


        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()


        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }


        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }


        throw "Unsupported Teleport value."
    }



    function Set-NativeTeleport {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )


        $desired =
            Convert-NativeTeleportValue `
                -Value $RequestedValue


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName


        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }


        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Teleport" `
                -Row $row


        if (!$cell) {
            throw "Teleport cell was not found for '$RegionName'."
        }


        $current =
            Get-NativeCheckboxState `
                -Cell $cell


        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }


        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell


        if (!$toggle) {
            throw "Teleport checkbox TogglePattern was not found."
        }


        try {
            $cell.SetFocus()
        }
        catch {
        }


        $toggle.Toggle()

        Start-Sleep -Milliseconds 500


        try {
            [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        }
        catch {
        }


        Start-Sleep -Milliseconds 900


        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName


        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "Teleport" `
                -Row $verifyRow


        if (!$verifyCell) {
            throw "Teleport cell disappeared during verification."
        }


        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell


        if ($actual -ne $desired) {
            throw "Teleport did not change in the GUI."
        }


        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativeTeleportSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Teleport snapshot request."
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $values =
            [ordered]@{}


        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()


            if ($name -eq "") {
                continue
            }


            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name


            if ($null -eq $row) {
                continue
            }


            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Teleport" `
                    -Row $row


            if (!$cell) {
                continue
            }


            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell


                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }


        return $values
    }


    function Convert-NativeAllowValue {

        param(
            [string]$Value
        )

        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()

        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }

        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }

        throw "Unsupported Allow value."
    }



    function Find-NativeAllowCell {

        param(
            $Grid,
            $Row
        )


        # First try the native header lookup.
        try {

            $normal =
                Find-SettingCell `
                    -Grid $Grid `
                    -Prefix "Allow" `
                    -Row $Row


            if ($normal) {
                return $normal
            }
        }
        catch {
        }


        # The native GUI does not expose the Allow header reliably.
        # Locate Teleport first, then find the next checkbox cell
        # immediately to its right on the same row.

        $teleport =
            Find-SettingCell `
                -Grid $Grid `
                -Prefix "Teleport" `
                -Row $Row


        if (!$teleport) {
            throw "Teleport cell was not found while locating Allow."
        }


        $teleportRect =
            $teleport.Current.BoundingRectangle


        if (
            $teleportRect.Width -le 0 -or
            $teleportRect.Height -le 0
        ) {
            throw "Teleport cell has no usable screen bounds."
        }


        $teleportCenterY =
            $teleportRect.Top +
            ($teleportRect.Height / 2)


        $teleportRight =
            $teleportRect.Left +
            $teleportRect.Width


        $all =
            $Grid.FindAll(
                [System.Windows.Automation.TreeScope]::Descendants,
                [System.Windows.Automation.Condition]::TrueCondition
            )


        $best =
            $null

        $bestLeft =
            [double]::PositiveInfinity


        foreach ($candidate in $all) {

            try {

                $rect =
                    $candidate.Current.BoundingRectangle


                if (
                    $rect.Width -le 1 -or
                    $rect.Height -le 1
                ) {
                    continue
                }


                $candidateCenterY =
                    $rect.Top +
                    ($rect.Height / 2)


                # Must belong to the same visible region row.
                if (
                    [Math]::Abs(
                        $candidateCenterY -
                        $teleportCenterY
                    ) -gt 6
                ) {
                    continue
                }


                # Must be to the right of Teleport.
                if (
                    $rect.Left -lt
                    ($teleportRight - 1)
                ) {
                    continue
                }


                # It must actually represent a checkbox.
                $toggle =
                    Get-NativeCheckboxPattern `
                        -Cell $candidate


                if (!$toggle) {
                    continue
                }


                if ($rect.Left -lt $bestLeft) {

                    $best =
                        $candidate

                    $bestLeft =
                        $rect.Left
                }
            }
            catch {
            }
        }


        if (!$best) {
            throw "Allow checkbox could not be located to the right of Teleport."
        }


        return $best
    }


    function Set-NativeAllow {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )

        $desired =
            Convert-NativeAllowValue `
                -Value $RequestedValue

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName

        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }

        $cell =
            Find-NativeAllowCell `
                    -Grid $grid `
                    -Row $row

        if (!$cell) {
            throw "Allow cell was not found for '$RegionName'."
        }

        $current =
            Get-NativeCheckboxState `
                -Cell $cell

        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }

        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell

        if (!$toggle) {
            throw "Allow checkbox TogglePattern was not found."
        }

        try {
            $cell.SetFocus()
        }
        catch {
        }

        $toggle.Toggle()

        Start-Sleep -Milliseconds 500

        try {
            [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        }
        catch {
        }

        Start-Sleep -Milliseconds 900

        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName

        $verifyCell =
            Find-NativeAllowCell `
                    -Grid $verifyGrid `
                    -Row $verifyRow

        if (!$verifyCell) {
            throw "Allow cell disappeared during verification."
        }

        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell

        if ($actual -ne $desired) {
            throw "Allow did not change in the GUI."
        }

        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativeAllowSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )

        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Allow snapshot request."
        }

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $values =
            [ordered]@{}

        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()

            if ($name -eq "") {
                continue
            }

            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name

            if ($null -eq $row) {
                continue
            }

            $cell =
                Find-NativeAllowCell `
                    -Grid $grid `
                    -Row $row

            if (!$cell) {
                continue
            }

            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell

                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }

        return $values
    }


    function Convert-NativeOwnerGodValue {

        param(
            [string]$Value
        )

        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()

        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }

        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }

        throw "Unsupported Owner God value."
    }



    function Set-NativeOwnerGod {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )

        $desired =
            Convert-NativeOwnerGodValue `
                -Value $RequestedValue

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName

        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }

        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Owner God" `
                -Row $row

        if (!$cell) {
            throw "Owner God cell was not found for '$RegionName'."
        }

        $current =
            Get-NativeCheckboxState `
                -Cell $cell

        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }

        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell

        if (!$toggle) {
            throw "Owner God checkbox TogglePattern was not found."
        }

        try {
            $cell.SetFocus()
        }
        catch {
        }

        $toggle.Toggle()

        Start-Sleep -Milliseconds 500

        try {
            [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        }
        catch {
        }

        Start-Sleep -Milliseconds 900

        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName

        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "Owner God" `
                -Row $verifyRow

        if (!$verifyCell) {
            throw "Owner God cell disappeared during verification."
        }

        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell

        if ($actual -ne $desired) {
            throw "Owner God did not change in the GUI."
        }

        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativeOwnerGodSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )

        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Owner God snapshot request."
        }

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $values =
            [ordered]@{}

        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()

            if ($name -eq "") {
                continue
            }

            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name

            if ($null -eq $row) {
                continue
            }

            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Owner God" `
                    -Row $row

            if (!$cell) {
                continue
            }

            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell

                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }

        return $values
    }


    function Convert-NativeManagerGodValue {

        param(
            [string]$Value
        )

        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()

        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }

        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }

        throw "Unsupported Manager God value."
    }



    function Set-NativeManagerGod {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )

        $desired =
            Convert-NativeManagerGodValue `
                -Value $RequestedValue

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName

        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }

        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Manager_God" `
                -Row $row

        if (!$cell) {
            throw "Manager God cell was not found for '$RegionName'."
        }

        $current =
            Get-NativeCheckboxState `
                -Cell $cell

        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }

        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell

        if (!$toggle) {
            throw "Manager God checkbox TogglePattern was not found."
        }

        try {
            $cell.SetFocus()
        }
        catch {
        }

        $toggle.Toggle()

        Start-Sleep -Milliseconds 500

        try {
            [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        }
        catch {
        }

        Start-Sleep -Milliseconds 900

        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName

        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "Manager_God" `
                -Row $verifyRow

        if (!$verifyCell) {
            throw "Manager God cell disappeared during verification."
        }

        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell

        if ($actual -ne $desired) {
            throw "Manager God did not change in the GUI."
        }

        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativeManagerGodSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )

        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Manager God snapshot request."
        }

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $values =
            [ordered]@{}

        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()

            if ($name -eq "") {
                continue
            }

            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name

            if ($null -eq $row) {
                continue
            }

            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Manager_God" `
                    -Row $row

            if (!$cell) {
                continue
            }

            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell

                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }

        return $values
    }


    function Convert-NativeAutoBackupValue {

        param(
            [string]$Value
        )

        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()

        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }

        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }

        throw "Unsupported AutoBackup value."
    }



    function Set-NativeAutoBackup {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )

        $desired =
            Convert-NativeAutoBackupValue `
                -Value $RequestedValue

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName

        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }

        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "AutoBackup OAR" `
                -Row $row

        if (!$cell) {
            throw "AutoBackup cell was not found for '$RegionName'."
        }

        $current =
            Get-NativeCheckboxState `
                -Cell $cell

        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }

        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell

        if (!$toggle) {
            throw "AutoBackup checkbox TogglePattern was not found."
        }

        try {
            $cell.SetFocus()
        }
        catch {
        }

        $toggle.Toggle()

        Start-Sleep -Milliseconds 500

        try {
            [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        }
        catch {
        }

        Start-Sleep -Milliseconds 900

        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName

        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "AutoBackup OAR" `
                -Row $verifyRow

        if (!$verifyCell) {
            throw "AutoBackup cell disappeared during verification."
        }

        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell

        if ($actual -ne $desired) {
            throw "AutoBackup did not change in the GUI."
        }

        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativeAutoBackupSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )

        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid AutoBackup snapshot request."
        }

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $values =
            [ordered]@{}

        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()

            if ($name -eq "") {
                continue
            }

            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name

            if ($null -eq $row) {
                continue
            }

            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "AutoBackup OAR" `
                    -Row $row

            if (!$cell) {
                continue
            }

            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell

                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }

        return $values
    }


    function Convert-NativePublicityValue {

        param(
            [string]$Value
        )

        $normalized =
            ([string]$Value).Trim().ToLowerInvariant()

        if (
            $normalized -in @(
                "1",
                "true",
                "on",
                "yes"
            )
        ) {
            return $true
        }

        if (
            $normalized -in @(
                "0",
                "false",
                "off",
                "no",
                ""
            )
        ) {
            return $false
        }

        throw "Unsupported Publicity value."
    }



    function Set-NativePublicity {

        param(
            [int]$ProcessId,
            [string]$RegionName,
            [string]$RequestedValue
        )

        $desired =
            Convert-NativePublicityValue `
                -Value $RequestedValue

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $row =
            Find-RegionRow `
                -Grid $grid `
                -RegionName $RegionName

        if ($null -eq $row) {
            throw "Region '$RegionName' was not found in the GUI."
        }

        $cell =
            Find-SettingCell `
                -Grid $grid `
                -Prefix "Publicity" `
                -Row $row

        if (!$cell) {
            throw "Publicity cell was not found for '$RegionName'."
        }

        $current =
            Get-NativeCheckboxState `
                -Cell $cell

        if ($current -eq $desired) {

            if ($current) {
                return "1"
            }

            return "0"
        }

        $toggle =
            Get-NativeCheckboxPattern `
                -Cell $cell

        if (!$toggle) {
            throw "Publicity checkbox TogglePattern was not found."
        }

        try {
            $cell.SetFocus()
        }
        catch {
        }

        $toggle.Toggle()

        Start-Sleep -Milliseconds 500

        try {
            [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        }
        catch {
        }

        Start-Sleep -Milliseconds 900

        $verifyGrid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        $verifyRow =
            Find-RegionRow `
                -Grid $verifyGrid `
                -RegionName $RegionName

        $verifyCell =
            Find-SettingCell `
                -Grid $verifyGrid `
                -Prefix "Publicity" `
                -Row $verifyRow

        if (!$verifyCell) {
            throw "Publicity cell disappeared during verification."
        }

        $actual =
            Get-NativeCheckboxState `
                -Cell $verifyCell

        if ($actual -ne $desired) {
            throw "Publicity did not change in the GUI."
        }

        if ($actual) {
            return "1"
        }

        return "0"
    }



    function Get-NativePublicitySnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )

        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid Publicity snapshot request."
        }

        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId

        if (!$grid) {
            throw "Native Regions grid was not available."
        }

        $values =
            [ordered]@{}

        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()

            if ($name -eq "") {
                continue
            }

            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name

            if ($null -eq $row) {
                continue
            }

            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix "Publicity" `
                    -Row $row

            if (!$cell) {
                continue
            }

            try {

                $isOn =
                    Get-NativeCheckboxState `
                        -Cell $cell

                if ($isOn) {
                    $values[$name] = "1"
                }
                else {
                    $values[$name] = "0"
                }
            }
            catch {
            }
        }

        return $values
    }


    function Get-NativeDataCellValue {

        param(
            [System.Windows.Automation.AutomationElement]$Cell
        )

        if (!$Cell) {
            return ""
        }


        try {

            $pattern =
                $Cell.GetCurrentPattern(
                    [System.Windows.Automation.ValuePattern]::Pattern
                )

            if ($pattern) {

                $value =
                    [string]$pattern.Current.Value

                if (
                    ![string]::IsNullOrWhiteSpace(
                        $value
                    )
                ) {
                    return $value.Trim()
                }
            }
        }
        catch {
        }


        try {

            $legacy =
                $Cell.GetCurrentPattern(
                    [System.Windows.Automation.LegacyIAccessiblePattern]::Pattern
                )

            if ($legacy) {

                $value =
                    [string]$legacy.Current.Value

                if (
                    ![string]::IsNullOrWhiteSpace(
                        $value
                    )
                ) {
                    return $value.Trim()
                }
            }
        }
        catch {
        }


        try {

            $children =
                $Cell.FindAll(
                    [System.Windows.Automation.TreeScope]::Descendants,
                    [System.Windows.Automation.Condition]::TrueCondition
                )


            foreach ($child in $children) {

                if (
                    $child.Current.ControlType -eq
                    [System.Windows.Automation.ControlType]::Text
                ) {

                    $value =
                        [string]$child.Current.Name

                    if (
                        ![string]::IsNullOrWhiteSpace(
                            $value
                        )
                    ) {
                        return $value.Trim()
                    }
                }
            }
        }
        catch {
        }


        return ""
    }



    function Get-NativeDataColumnSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson,
            [string]$Prefix
        )


        try {

            $regionNames =
                @(
                    $RequestedJson |
                    ConvertFrom-Json
                )
        }
        catch {

            throw "Invalid GUI data column snapshot request."
        }


        $grid =
            Find-DetailsGrid `
                -ProcessId $ProcessId


        if (!$grid) {
            throw "Native Regions grid was not available."
        }


        $values =
            [ordered]@{}


        foreach ($item in $regionNames) {

            $name =
                ([string]$item).Trim()


            if ($name -eq "") {
                continue
            }


            $row =
                Find-RegionRow `
                    -Grid $grid `
                    -RegionName $name


            if ($null -eq $row) {
                continue
            }


            $cell =
                Find-SettingCell `
                    -Grid $grid `
                    -Prefix $Prefix `
                    -Row $row


            if (!$cell) {
                continue
            }


            try {

                $value =
                    Get-NativeDataCellValue `
                        -Cell $cell


                $values[$name] =
                    [string]$value
            }
            catch {
            }
        }


        return $values
    }



    function Get-NativeScriptRateSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        $values =
            Get-NativeDataColumnSnapshot `
                -ProcessId $ProcessId `
                -RequestedJson $RequestedJson `
                -Prefix "Script Rate"


        return $values
    }



    function Get-NativeFrameRateSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        $values =
            Get-NativeDataColumnSnapshot `
                -ProcessId $ProcessId `
                -RequestedJson $RequestedJson `
                -Prefix "Frame Rate"


        return $values
    }


    function Get-NativeRegionSettingsSnapshot {

        param(
            [int]$ProcessId,
            [string]$RequestedJson
        )


        $readers =
            [ordered]@{

                "maps" =
                    "Get-NativeMapsSnapshot"

                "physics" =
                    "Get-NativePhysicsSnapshot"

                "birds" =
                    "Get-NativeBirdsSnapshot"

                "tides" =
                    "Get-NativeTidesSnapshot"

                "teleport" =
                    "Get-NativeTeleportSnapshot"

                "allow" =
                    "Get-NativeAllowSnapshot"

                "owner_god" =
                    "Get-NativeOwnerGodSnapshot"

                "manager_god" =
                    "Get-NativeManagerGodSnapshot"

                "auto_backup" =
                    "Get-NativeAutoBackupSnapshot"

                "publicity" =
                    "Get-NativePublicitySnapshot"
                            "script_rate" =
                    "Get-NativeScriptRateSnapshot"

                "frame_rate" =
                    "Get-NativeFrameRateSnapshot"
}


        $result =
            [ordered]@{}


        foreach ($entry in $readers.GetEnumerator()) {

            $functionName =
                [string]$entry.Value


            $result[$entry.Key] =
                & $functionName `
                    -ProcessId $ProcessId `
                    -RequestedJson $RequestedJson
        }


        return $result
    }


    # PROCESS REQUEST FILES
    # --------------------------------------------------------

    $requests =
        Get-ChildItem `
            -Path $queueDir `
            -Filter "*.request.json" `
            -File `
            -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime


    foreach ($requestFile in $requests) {

        $requestId =
            $requestFile.Name.Replace(
                ".request.json",
                ""
            )


        $responsePath =
            Join-Path $queueDir (
                "$requestId.response.json"
            )


        $response =
            [ordered]@{
                ok           = $false
                id           = $requestId
                region       = ""
                setting      = ""
                requested    = ""
                native_value = ""
                native_values = $null
                message      = ""
                timestamp    = [DateTime]::UtcNow.ToString("o")
            }


        try {

            if (
                $requestId -notmatch
                '^[0-9a-fA-F-]{36}$'
            ) {
                throw "Invalid request identifier."
            }


            $json =
                [System.IO.File]::ReadAllText(
                    $requestFile.FullName
                )


            $request =
                $json |
                ConvertFrom-Json


            $region =
                [string]$request.region

            $setting =
                [string]$request.setting

            $value =
                [string]$request.value


            $response.region =
                $region

            $response.setting =
                $setting

            $response.requested =
                $value


            if (
                [string]::IsNullOrWhiteSpace($region) -or
                $region.Length -gt 128 -or
                $region -match '[\x00-\x1F]'
            ) {
                throw "Invalid region name."
            }


            $proc =
                Find-StartProcess


            if (!$proc) {
                throw "The native grid manager is not running."
            }


            switch ($setting) {

                "smart_mode" {

                    if (
                        $value -notin @(
                            "off",
                            "boot",
                            "suspend"
                        )
                    ) {
                        throw "Unsupported Smart Start value."
                    }


                    $actual =
                        Set-NativeSmartStart `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value


                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Smart Start updated successfully."
                }


                "maps" {

                    if (
                        $value -notin @(
                            "Default",
                            "None",
                            "Simple",
                            "Good",
                            "Better",
                            "Best"
                        )
                    ) {
                        throw "Unsupported Maps value."
                    }


                    $actual =
                        Set-NativeMaps `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value


                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Maps updated successfully."
                }


                


                "maps_snapshot" {

                    $actual =
                        Get-NativeMapsSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value


                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Maps GUI snapshot read successfully."
                }

                "physics" {

                    if (
                        $value -notin @(
                            "",
                            "2",
                            "3",
                            "4",
                            "5"
                        )
                    ) {
                        throw "Unsupported Physics value."
                    }


                    $actual =
                        Set-NativePhysics `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value


                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Physics updated successfully."
                }


                "physics_snapshot" {

                    $actual =
                        Get-NativePhysicsSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value


                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Physics GUI snapshot read successfully."
                }

                "birds" {

                    $actual =
                        Set-NativeBirds `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value


                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Birds updated successfully."
                }


                "birds_snapshot" {

                    $actual =
                        Get-NativeBirdsSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value


                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Birds GUI snapshot read successfully."
                }

                "tides" {

                    $actual =
                        Set-NativeTides `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value


                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Tides updated successfully."
                }


                "tides_snapshot" {

                    $actual =
                        Get-NativeTidesSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value


                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Tides GUI snapshot read successfully."
                }

                "teleport" {

                    $actual =
                        Set-NativeTeleport `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value


                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Teleport updated successfully."
                }


                "teleport_snapshot" {

                    $actual =
                        Get-NativeTeleportSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value


                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Teleport GUI snapshot read successfully."
                }

                "allow" {

                    $actual =
                        Set-NativeAllow `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value

                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Allow updated successfully."
                }


                "allow_snapshot" {

                    $actual =
                        Get-NativeAllowSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value

                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Allow GUI snapshot read successfully."
                }

                "owner_god" {

                    $actual =
                        Set-NativeOwnerGod `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value

                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Owner God updated successfully."
                }


                "owner_god_snapshot" {

                    $actual =
                        Get-NativeOwnerGodSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value

                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Owner God GUI snapshot read successfully."
                }

                "manager_god" {

                    $actual =
                        Set-NativeManagerGod `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value

                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Manager God updated successfully."
                }


                "manager_god_snapshot" {

                    $actual =
                        Get-NativeManagerGodSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value

                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Manager God GUI snapshot read successfully."
                }

                "auto_backup" {

                    $actual =
                        Set-NativeAutoBackup `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value

                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "AutoBackup updated successfully."
                }


                "auto_backup_snapshot" {

                    $actual =
                        Get-NativeAutoBackupSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value

                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "AutoBackup GUI snapshot read successfully."
                }

                "publicity" {

                    $actual =
                        Set-NativePublicity `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RegionName $region `
                            -RequestedValue $value

                    $response.ok =
                        $true

                    $response.native_value =
                        $actual

                    $response.message =
                        "Publicity updated successfully."
                }


                "publicity_snapshot" {

                    $actual =
                        Get-NativePublicitySnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value

                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Publicity GUI snapshot read successfully."
                }

                "region_snapshot" {

                    $actual =
                        Get-NativeRegionSettingsSnapshot `
                            -ProcessId ([int]$proc.ProcessId) `
                            -RequestedJson $value


                    $response.ok =
                        $true

                    $response.native_values =
                        $actual

                    $response.message =
                        "Region GUI snapshot read successfully."
                }

                default {

                    throw "Unsupported region setting."
                }
            }
        }
        catch {

            $response.ok =
                $false

            $response.message =
                $_.Exception.Message
        }


        $responseJson =
            $response |
            ConvertTo-Json -Depth 8 `
                -Compress


        $tmpResponse =
            "$responsePath.tmp"


        [System.IO.File]::WriteAllText(
            $tmpResponse,
            $responseJson,
            (
                New-Object System.Text.UTF8Encoding($false)
            )
        )


        Move-Item `
            $tmpResponse `
            $responsePath `
            -Force


        Remove-Item `
            $requestFile.FullName `
            -Force `
            -ErrorAction SilentlyContinue
    }
}
finally {

    if ($hasMutex) {

        try {
            $mutex.ReleaseMutex()
        }
        catch {
        }
    }


    $mutex.Dispose()
}
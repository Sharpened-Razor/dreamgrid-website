function Get-WebsiteProcessPath($Process){
    if($Process.ExecutablePath){return $Process.ExecutablePath}
    # CIM omits elevated executable paths for an unelevated caller. Limited
    # query access retrieves the path without reading or changing the process.
    if(-not ('DreamGridWebsiteProcess' -as [type])){
        Add-Type -TypeDefinition @'
using System;using System.Text;using System.Runtime.InteropServices;
public static class DreamGridWebsiteProcess {
 [DllImport("kernel32.dll",SetLastError=true)]static extern IntPtr OpenProcess(uint access,bool inherit,uint id);
 [DllImport("kernel32.dll",CharSet=CharSet.Unicode,SetLastError=true)]static extern bool QueryFullProcessImageName(IntPtr process,int flags,StringBuilder name,ref uint length);
 [DllImport("kernel32.dll")]static extern bool CloseHandle(IntPtr handle);
 public static string Get(uint id){var handle=OpenProcess(0x1000,false,id);if(handle==IntPtr.Zero)return null;try{uint length=32768;var name=new StringBuilder((int)length);return QueryFullProcessImageName(handle,0,name,ref length)?name.ToString():null;}finally{CloseHandle(handle);}}
}
'@
    }
    $path=[DreamGridWebsiteProcess]::Get([uint32]$Process.ProcessId)
    if($path){return $path}
    # A SYSTEM-owned Apache worker may deny even limited query access. Trace
    # its service ancestor and read that service's configured application.
    $services=@(Get-CimInstance Win32_Service|Where-Object ProcessId -gt 0)
    $processes=@(Get-CimInstance Win32_Process)
    $current=$Process;$seen=[Collections.Generic.HashSet[uint32]]::new()
    while($current -and $seen.Add([uint32]$current.ProcessId)){
        $service=$services|Where-Object ProcessId -eq $current.ProcessId|Select-Object -First 1
        if($service){
            $parameters=Get-ItemProperty -LiteralPath ("Registry::HKEY_LOCAL_MACHINE\SYSTEM\CurrentControlSet\Services\"+$service.Name+'\Parameters') -ErrorAction SilentlyContinue
            if($parameters.Application){return $parameters.Application}
            if($service.PathName -match '^\s*"?(.+?\.exe)"?(?:\s|$)'){return $Matches[1]}
        }
        $current=$processes|Where-Object ProcessId -eq $current.ParentProcessId|Select-Object -First 1
    }
    return $null
}

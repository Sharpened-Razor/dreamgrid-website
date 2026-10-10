using System.Diagnostics;
using System.Text;
using System.Text.RegularExpressions;

namespace DreamGridWebsiteSetup;

static class Destination
{
    public static bool TryValidate(string path, out string root)
    {
        root = "";
        try { root = Validate(path); return true; }
        catch (Exception e) when (e is IOException or ArgumentException or UnauthorizedAccessException or InvalidOperationException or System.Security.SecurityException) { return false; }
    }
    public static string Validate(string path)
    {
        if (string.IsNullOrWhiteSpace(path)) throw new ArgumentException("Choose a DreamGrid installation using Browse.");
        var full = Path.GetFullPath(path);
        foreach (var candidate in new[] { full, Path.Combine(full, "OutworldzFiles") })
        {
            if (!File.Exists(Path.Combine(candidate, "Settings.ini")) || !Directory.Exists(Path.Combine(candidate, "Opensim")) || !File.Exists(Path.Combine(candidate, "Apache", "bin", "httpd.exe"))) continue;
            if (!Directory.EnumerateDirectories(candidate, "PHP*").Any(d => Regex.IsMatch(Path.GetFileName(d), "^PHP[0-9]+$", RegexOptions.IgnoreCase) && File.Exists(Path.Combine(d, "php.exe")))) continue;
            return Path.TrimEndingDirectorySeparator(candidate);
        }
        throw new InvalidOperationException("Invalid DreamGrid folder. Select the installation folder or its OutworldzFiles folder containing Settings.ini, Opensim, Apache/bin/httpd.exe and a PHP runtime. No files changed.");
    }
}

static class TransportNotice
{
    public static string For(string url)
    {
        if (Uri.TryCreate(url, UriKind.Absolute, out var uri) && uri.Scheme == Uri.UriSchemeHttps)
            return "The supplied website URL uses HTTPS. Certificate validity and server TLS configuration are not tested by this installer.";
        return "WARNING (non-blocking): HTTPS is strongly recommended for public internet-facing grids, especially for Admin login and authenticated pages. " +
            (Uri.TryCreate(url, UriKind.Absolute, out uri) && uri.Scheme == Uri.UriSchemeHttp ? "The supplied URL uses plain HTTP, which does not protect credentials or sessions with TLS." : "HTTPS has not been established; enter your actual website URL above.") +
            " Local/LAN HTTP remains supported. HTTPS setup is the grid owner's responsibility.";
    }
}

sealed record OtherPlan(string Path, byte[] Original, byte[] Updated, List<string> Changes);

static class OtherConfiguration
{
    static readonly Regex Section = new(@"^\s*\[([^\]]+)\]\s*$");
    static readonly Regex Value = new(@"^(\s*)([^;#=]+?)(\s*=\s*)(.*?)(\s*)$");
    // Native 7.2115 reads [Data]CMS overrides before CMS. Do not accept global keys.
    internal static Dictionary<string, string> Read(string text)
    {
        var result = new Dictionary<string, string>(StringComparer.Ordinal); string section = "";
        foreach (var line in Regex.Split(text, "\r\n|\n|\r"))
        {
            var header = Section.Match(line); if (header.Success) { section = header.Groups[1].Value; continue; }
            if (section != "Data") continue;
            var match = Value.Match(line); if (!match.Success) continue;
            var key = match.Groups[2].Value.Trim();
            if (!new[] { "CMS", "OtherCMS", "[Data]CMS", "[Data]OtherCMS" }.Contains(key)) continue;
            if (!result.TryAdd(key, match.Groups[4].Value.Trim())) throw new InvalidDataException("Duplicate DIVA/OTHER key: " + key + ". Resolve it in DreamGrid before configuration.");
        }
        return result;
    }
    static string Strip(string value) => value.Replace("\"", "").Trim();
    internal static string Effective(Dictionary<string,string> values, string key, string fallback) => Strip(values.TryGetValue("[Data]" + key, out var over) && over.Length > 0 ? over : values.GetValueOrDefault(key, fallback));
    static bool IsOther(string value) => string.Equals(value, "Other", StringComparison.OrdinalIgnoreCase);
    static (Encoding Encoding, int Prefix) Decode(byte[] bytes)
    {
        if (bytes.AsSpan().StartsWith(new byte[] {255,254})) return (new UnicodeEncoding(false, false, true), 2);
        if (bytes.AsSpan().StartsWith(new byte[] {254,255})) return (new UnicodeEncoding(true, false, true), 2);
        return (new UTF8Encoding(false, true), bytes.AsSpan().StartsWith(new byte[] {239,187,191}) ? 3 : 0);
    }
    public static OtherPlan Plan(string root)
    {
        string file = System.IO.Path.Combine(Destination.Validate(root), "Settings.ini");
        var original = File.ReadAllBytes(file); var (encoding, prefix) = Decode(original);
        var text = encoding.GetString(original, prefix, original.Length-prefix);
        var values = Read(text); var changes = new List<string>();
        // An already-correct destination must remain byte-for-byte untouched.
        if (IsOther(Effective(values, "CMS", "DreamGrid")) && IsOther(Effective(values, "OtherCMS", "Other"))) return new(file, original, original, changes);
        var updates = new Dictionary<string,string>();
        foreach (var key in new[] { "CMS", "OtherCMS" })
        {
            if (!IsOther(Strip(values.GetValueOrDefault(key, key == "CMS" ? "DreamGrid" : "Other")))) updates[key] = "Other";
            if (values.TryGetValue("[Data]"+key, out var over) && over.Length > 0 && !IsOther(Strip(over))) updates["[Data]"+key] = "Other";
        }
        var pieces = Regex.Split(text, "(\r\n|\n|\r)"); string section = ""; int sections = 0;
        var pending = new HashSet<string>(updates.Keys);
        for (int i=0; i<pieces.Length; i+=2)
        {
            var header = Section.Match(pieces[i]);
            if (header.Success) { section = header.Groups[1].Value; if(section == "Data") sections++; continue; }
            if (section != "Data") continue;
            var match=Value.Match(pieces[i]); if (!match.Success) continue;
            var key=match.Groups[2].Value.Trim(); if (!updates.ContainsKey(key)) continue;
            pieces[i]=match.Groups[1].Value+match.Groups[2].Value+match.Groups[3].Value+"Other"+match.Groups[5].Value;
            pending.Remove(key);
        }
        if (sections != 1) throw new InvalidDataException("Expected one [Data] section; no configuration was changed.");
        text=string.Concat(pieces);
        var newline=text.Contains("\r\n") ? "\r\n" : "\n";
        if(pending.Count>0)
        {
            // Insert only missing required keys immediately under [Data].
            text=Regex.Replace(text,@"(?m)^([ \t]*\[Data\][ \t]*)(\r?\n|$)",m=>m.Groups[1].Value+newline+string.Join(newline,pending.Select(k=>k+"=Other"))+newline,RegexOptions.None,TimeSpan.FromSeconds(1));
        }
        foreach(var key in updates.Keys) changes.Add("[Data] " + key + ": " + values.GetValueOrDefault(key,"(absent/default)") + " → Other");
        var content=encoding.GetBytes(text); var updated=new byte[prefix+content.Length]; original.AsSpan(0,prefix).CopyTo(updated); content.CopyTo(updated,prefix);
        var verify=Read(text);
        if(!IsOther(Effective(verify,"CMS","DreamGrid")) || !IsOther(Effective(verify,"OtherCMS","Other"))) throw new InvalidDataException("OTHER plan verification failed. No files changed.");
        return new(file,original,updated,changes);
    }
    public static string Apply(OtherPlan plan, string archiveParent) => ApplyChecked(plan, archiveParent, EnsureStopped);
    internal static string ApplyChecked(OtherPlan plan, string archiveParent, Action<string> verifyStopped)
    {
        if(plan.Changes.Count==0) return "No changes needed";
        var root=System.IO.Path.GetDirectoryName(plan.Path)!; verifyStopped(root);
        using var destination=new FileStream(plan.Path,FileMode.Open,FileAccess.ReadWrite,FileShare.None);
        var current=new byte[checked((int)destination.Length)]; destination.ReadExactly(current);
        if(!current.SequenceEqual(plan.Original)) throw new IOException("Settings.ini changed since confirmation. Review a fresh plan.");
        string backup=System.IO.Path.Combine(archiveParent,"DreamGrid-OTHER-Backup-"+DateTime.Now.ToString("yyyyMMdd-HHmmssfff")+"-"+Guid.NewGuid().ToString("N"));
        Directory.CreateDirectory(backup); var backupFile=System.IO.Path.Combine(backup,"Settings.ini");
        using(var saved=new FileStream(backupFile,FileMode.CreateNew)) {saved.Write(plan.Original); saved.Flush(true);}
        if(!File.ReadAllBytes(backupFile).SequenceEqual(plan.Original)) throw new IOException("Backup verification failed. Settings.ini untouched.");
        File.WriteAllText(System.IO.Path.Combine(backup,"changes.txt"),"Destination: "+plan.Path+Environment.NewLine+string.Join(Environment.NewLine,plan.Changes)+Environment.NewLine+"To restore: stop this grid and Apache, copy Settings.ini from this backup to the destination, and verify SHA-256 matches.");
        try
        {
            destination.Position=0; destination.Write(plan.Updated); destination.SetLength(plan.Updated.Length); destination.Flush(true);
            destination.Position=0; var actual=new byte[checked((int)destination.Length)]; destination.ReadExactly(actual);
            if(!actual.SequenceEqual(plan.Updated)) throw new IOException("Write verification failed.");
            var (enc,prefix)=Decode(actual); var values=Read(enc.GetString(actual,prefix,actual.Length-prefix));
            if(!IsOther(Effective(values,"CMS","DreamGrid")) || !IsOther(Effective(values,"OtherCMS","Other"))) throw new IOException("Native setting precedence verification failed.");
            return backup;
        }
        catch
        {
            destination.Position=0; destination.Write(plan.Original); destination.SetLength(plan.Original.Length); destination.Flush(true); destination.Position=0;
            var restored=new byte[plan.Original.Length];destination.ReadExactly(restored);
            if(!restored.SequenceEqual(plan.Original)) throw new IOException("Rollback verification failed. Restore from "+backupFile);
            throw;
        }
    }
    static void EnsureStopped(string root)
    {
        var parent=System.IO.Path.GetDirectoryName(root)!+System.IO.Path.DirectorySeparatorChar;
        foreach(var name in new[]{"Start","DreamGrid","httpd"}) foreach(var process in Process.GetProcessesByName(name)) using(process)
        {
            try { var exe=Discovery.ProcessPath(process); if(exe==null || System.IO.Path.GetFullPath(exe).StartsWith(parent,StringComparison.OrdinalIgnoreCase)) throw new InvalidOperationException("Stop the selected destination DreamGrid and Apache before configuring OTHER."); }
            catch(System.ComponentModel.Win32Exception) { throw new InvalidOperationException("Cannot verify that "+name+" is stopped. Close it before configuring OTHER."); }
        }
    }
}

static class FreshWebsite
{
    public static bool IsEmpty(string root)
    {
        var folder = Path.Combine(Destination.Validate(root), "Apache", "htdocs", "Other");
        if (!Directory.Exists(folder)) return true;
        var pending = new Queue<DirectoryInfo>(); pending.Enqueue(new DirectoryInfo(folder));
        while (pending.Count > 0)
        {
            var directory = pending.Dequeue();
            if ((directory.Attributes & FileAttributes.ReparsePoint) != 0) throw new IOException("OTHER must not contain reparse points.");
            foreach (var entry in directory.EnumerateFileSystemInfos())
            {
                if ((entry.Attributes & FileAttributes.ReparsePoint) != 0) throw new IOException("OTHER must not contain reparse points.");
                if (entry is DirectoryInfo child) pending.Enqueue(child);
                else if (entry is FileInfo file && !(new[] { ".keep", ".gitkeep" }.Contains(file.Name, StringComparer.OrdinalIgnoreCase) && file.Length <= 4096)) return false;
            }
        }
        return true;
    }
}

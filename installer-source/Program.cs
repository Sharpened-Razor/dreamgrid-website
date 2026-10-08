using System.Diagnostics;
using System.IO.Compression;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using Microsoft.Win32;

namespace DreamGridWebsiteSetup;

static class Program
{
    internal const string PayloadSha256 = "aeb331caea3ed90e005c0ce83a15fdb04cd9a711d05d16bd6796bd3c7e789403";
    [STAThread]
    static int Main(string[] args)
    {
        if (args.Length > 0)
        {
            try
            {
                var options = Options.Parse(args);
                if (options.Discover)
                {
                    var paths = Discovery.Find();
                    File.WriteAllText(options.LogPath, JsonSerializer.Serialize(paths, new JsonSerializerOptions { WriteIndented = true }));
                    return 0;
                }
                using var engine = new Engine(options.LogPath);
                return engine.Run(options).GetAwaiter().GetResult();
            }
            catch (Exception error)
            {
                var log = Options.Value(args, "--log") ?? Options.DefaultLog();
                Directory.CreateDirectory(Path.GetDirectoryName(Path.GetFullPath(log))!);
                File.AppendAllText(log, "SETUP FAILED: " + error.Message + Environment.NewLine);
                return 1;
            }
        }
        ApplicationConfiguration.Initialize();
        Application.Run(new SetupWindow());
        return 0;
    }
}

sealed class Options
{
    public string Root { get; set; } = "";
    public string? RestoreArchive { get; set; }
    public string? ExternalPackage { get; set; }
    public string ArchiveParent { get; set; } = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
    public string LogPath { get; set; } = DefaultLog();
    public int SimulateFailure { get; set; }
    public bool Discover { get; set; }
    public static string DefaultLog()
    {
        var folder = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
        if (string.IsNullOrEmpty(folder)) folder = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
        return Path.Combine(folder, "DreamGrid-Website-Setup-" + DateTime.Now.ToString("yyyyMMdd-HHmmssfff") + ".log");
    }
    public static string? Value(string[] args, string name)
    {
        int index = Array.IndexOf(args, name);
        return index >= 0 && index + 1 < args.Length ? args[index + 1] : null;
    }
    public static Options Parse(string[] args)
    {
        var allowed = new HashSet<string> { "--install", "--discover", "--root", "--restore", "--archive-parent", "--log", "--package", "--simulate-failure-after" };
        for (int i = 0; i < args.Length; i++)
        {
            if (!allowed.Contains(args[i])) throw new ArgumentException("Unknown setup argument: " + args[i]);
            if (args[i] != "--install" && args[i] != "--discover")
            {
                if (i + 1 >= args.Length || args[i + 1].StartsWith("--")) throw new ArgumentException("Missing argument value");
                i++;
            }
        }
        var options = new Options {
            Root = Value(args, "--root") ?? "", RestoreArchive = Value(args, "--restore"),
            ExternalPackage = Value(args, "--package"), LogPath = Value(args, "--log") ?? DefaultLog(),
            Discover = args.Contains("--discover")
        };
        options.ArchiveParent = Value(args, "--archive-parent") ?? options.ArchiveParent;
        if (Value(args, "--simulate-failure-after") is string fail) options.SimulateFailure = int.Parse(fail);
        if (!options.Discover && options.RestoreArchive == null && !args.Contains("--install")) throw new ArgumentException("Specify --install or --restore");
        if (options.RestoreArchive == null && !options.Discover && options.Root == "")
        {
            var candidates = Discovery.Find();
            if (candidates.Count != 1) throw new ArgumentException("Choose the DreamGrid folder using Browse or --root.");
            options.Root = candidates[0];
        }
        return options;
    }
}

sealed class Engine : IDisposable
{
    readonly StreamWriter log;
    public event Action<string>? Progress;
    public string? ExtractionDirectory { get; private set; }
    public Engine(string logPath)
    {
        Directory.CreateDirectory(Path.GetDirectoryName(Path.GetFullPath(logPath))!);
        log = new StreamWriter(logPath, true, new UTF8Encoding(false)) { AutoFlush = true };
    }
    void Write(string text) { log.WriteLine(text); Progress?.Invoke(text); }
    static string Quote(string text) => "'" + text.Replace("'", "''") + "'";
    public async Task<int> Run(Options options)
    {
        Write("DreamGrid Website Setup 1.0.2 — Sharpened-Razor/dreamgrid-website");
        Write("Windows x64; bundled .NET runtime; built-in Windows PowerShell 5.1 engine. No PowerShell 7 needed.");
        try
        {
            using Stream payload = options.ExternalPackage != null ? File.OpenRead(options.ExternalPackage)
                : Assembly.GetExecutingAssembly().GetManifestResourceStream("payload.zip") ?? throw new IOException("Embedded package unavailable");
            var hash = Convert.ToHexString(SHA256.HashData(payload)).ToLowerInvariant();
            if (hash != Program.PayloadSha256) throw new InvalidDataException("Package integrity check failed; no destination files changed.");
            payload.Position = 0;
            string tempBase = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "DreamGridWebsiteSetup");
            ExtractionDirectory = Path.Combine(tempBase, Guid.NewGuid().ToString("N"));
            Directory.CreateDirectory(ExtractionDirectory);
            using (var zip = new ZipArchive(payload, ZipArchiveMode.Read, true))
            {
                string prefix = Path.GetFullPath(ExtractionDirectory) + Path.DirectorySeparatorChar;
                foreach (var entry in zip.Entries)
                {
                    string path = Path.GetFullPath(Path.Combine(ExtractionDirectory, entry.FullName.Replace('/', Path.DirectorySeparatorChar)));
                    if (!path.StartsWith(prefix, StringComparison.OrdinalIgnoreCase)) throw new InvalidDataException("Unsafe package entry");
                    if (entry.FullName.EndsWith('/')) { Directory.CreateDirectory(path); continue; }
                    Directory.CreateDirectory(Path.GetDirectoryName(path)!);
                    using var input = entry.Open(); using var output = new FileStream(path, FileMode.CreateNew); input.CopyTo(output);
                }
            }
            Write("Embedded package checksum verified. Validating prerequisites and every payload file before deployment.");
            string script = options.RestoreArchive != null
                ? "& " + Quote(Path.Combine(ExtractionDirectory, "Restore-DreamGridWebsite.ps1")) + " -ArchiveDirectory " + Quote(options.RestoreArchive)
                : "& " + Quote(Path.Combine(ExtractionDirectory, "Install-DreamGridWebsite.ps1")) + " -Root " + Quote(options.Root)
                    + " -PackageDirectory " + Quote(ExtractionDirectory) + " -ArchiveParent " + Quote(options.ArchiveParent)
                    + " -SimulateFailureAfterFiles " + options.SimulateFailure;
            string runner = Path.Combine(ExtractionDirectory, "setup-runner.ps1");
            File.WriteAllText(runner, "$ErrorActionPreference='Stop'\n[Console]::OutputEncoding=[Text.UTF8Encoding]::new($false)\ntry { " + script + " | ConvertTo-Json -Depth 12; exit 0 } catch { [Console]::Error.WriteLine($_.Exception.Message); exit 1 }\n", new UTF8Encoding(true));
            string powershell = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.Windows), "System32", "WindowsPowerShell", "v1.0", "powershell.exe");
            var start = new ProcessStartInfo(powershell) { UseShellExecute = false, CreateNoWindow = true, RedirectStandardOutput = true, RedirectStandardError = true };
            // PowerShell 7 parents can export their module path. Use the Windows
            // engine's own modules so normal Windows cmdlets load consistently.
            start.Environment["PSModulePath"] = Path.Combine(Path.GetDirectoryName(powershell)!, "Modules");
            foreach (var argument in new[] { "-NoProfile", "-NonInteractive", "-ExecutionPolicy", "Bypass", "-File", runner }) start.ArgumentList.Add(argument);
            using var process = Process.Start(start) ?? throw new IOException("Windows installer engine could not start");
            Task<string> outputTask = process.StandardOutput.ReadToEndAsync(), errorTask = process.StandardError.ReadToEndAsync();
            await process.WaitForExitAsync();
            string outputText = await outputTask, errorText = await errorTask;
            if (outputText.Length > 0) Write(outputText);
            if (errorText.Length > 0) Write(errorText);
            Write(process.ExitCode == 0 ? "SETUP SUCCEEDED. Backup/restore location is recorded above." : "SETUP FAILED. Review the reason above. Deployment failures automatically restore and verify backed-up files.");
            return process.ExitCode;
        }
        catch (Exception error) { Write("SETUP FAILED: " + error.Message); return 1; }
        finally
        {
            if (ExtractionDirectory != null)
            {
                string expected = Path.GetFullPath(Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "DreamGridWebsiteSetup")) + Path.DirectorySeparatorChar;
                string target = Path.GetFullPath(ExtractionDirectory);
                if (target.StartsWith(expected, StringComparison.OrdinalIgnoreCase))
                {
                    try { Directory.Delete(target, true); } catch (IOException) { Write("Temporary package cleanup deferred: " + target); }
                    catch (UnauthorizedAccessException) { Write("Temporary package cleanup deferred: " + target); }
                }
            }
        }
    }
    public void Dispose() => log.Dispose();
}

static class Discovery
{
    [DllImport("kernel32.dll", SetLastError = true)] static extern IntPtr OpenProcess(uint access, bool inherit, uint id);
    [DllImport("kernel32.dll", CharSet = CharSet.Unicode)] static extern bool QueryFullProcessImageName(IntPtr process, int flags, StringBuilder name, ref uint length);
    [DllImport("kernel32.dll")] static extern bool CloseHandle(IntPtr handle);
    static void Add(string path, HashSet<string> found)
    {
        try
        {
            var directory = new DirectoryInfo(Path.GetFullPath(path));
            for (int i = 0; i < 5 && directory != null; i++, directory = directory.Parent)
            {
                foreach (var candidate in new[] { directory.FullName, Path.Combine(directory.FullName, "OutworldzFiles") })
                    if (File.Exists(Path.Combine(candidate, "Settings.ini")) && Directory.Exists(Path.Combine(candidate, "Apache"))) found.Add(candidate);
            }
        }
        catch (Exception error) when (error is IOException or ArgumentException or UnauthorizedAccessException) { }
    }
    public static List<string> Find()
    {
        var found = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        Add(AppContext.BaseDirectory, found);
        foreach (string name in new[] { "Start", "Robust", "OpenSim", "DreamGrid" })
        foreach (var process in Process.GetProcessesByName(name))
        {
            using (process)
            {
                var handle = OpenProcess(0x1000, false, (uint)process.Id);
                if (handle == IntPtr.Zero) continue;
                try { uint size = 32768; var path = new StringBuilder((int)size); if (QueryFullProcessImageName(handle, 0, path, ref size)) Add(Path.GetDirectoryName(path.ToString())!, found); }
                finally { CloseHandle(handle); }
            }
        }
        foreach (var hive in new[] { Registry.CurrentUser, Registry.LocalMachine })
        foreach (string keyPath in new[] { @"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall", @"SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall" })
        {
            using var key = hive.OpenSubKey(keyPath);
            if (key == null) continue;
            foreach (string child in key.GetSubKeyNames())
            {
                using var entry = key.OpenSubKey(child);
                if (entry?.GetValue("DisplayName") is string display && display.Contains("DreamGrid", StringComparison.OrdinalIgnoreCase) && entry.GetValue("InstallLocation") is string location) Add(location, found);
            }
        }
        return found.OrderBy(x => x, StringComparer.OrdinalIgnoreCase).ToList();
    }
}

sealed class SetupWindow : Form
{
    readonly ComboBox root = new() { Dock = DockStyle.Fill, DropDownStyle = ComboBoxStyle.DropDown };
    readonly Button install = new() { Text = "Install / upgrade", AutoSize = true };
    readonly Button restore = new() { Text = "Restore backup…", AutoSize = true };
    readonly Button browse = new() { Text = "Browse…", AutoSize = true };
    readonly TextBox log = new() { Dock = DockStyle.Fill, Multiline = true, ReadOnly = true, ScrollBars = ScrollBars.Both, WordWrap = false };
    bool busy;
    public SetupWindow()
    {
        Text = "DreamGrid Website Setup"; Width = 840; Height = 560; MinimumSize = new Size(700, 450);
        Font = new Font("Segoe UI", 10); StartPosition = FormStartPosition.CenterScreen;
        var layout = new TableLayoutPanel { Dock = DockStyle.Fill, Padding = new Padding(18), ColumnCount = 2, RowCount = 5 };
        layout.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 100)); layout.ColumnStyles.Add(new ColumnStyle(SizeType.AutoSize));
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize)); layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize)); layout.RowStyles.Add(new RowStyle(SizeType.Percent, 100)); layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        var intro = new Label { AutoSize = true, MaximumSize = new Size(770, 0), Text = "Install the website into an existing DreamGrid installation.\nStop destination DreamGrid and Apache first. DIVA OFF · OTHER selected · Folder Other.\nCustom content is preserved. Replaced files are backed up and verified before deployment." };
        layout.Controls.Add(intro, 0, 0); layout.SetColumnSpan(intro, 2);
        layout.Controls.Add(root, 0, 1); layout.Controls.Add(browse, 1, 1);
        var actions = new FlowLayoutPanel { AutoSize = true, Dock = DockStyle.Fill }; actions.Controls.Add(install); actions.Controls.Add(restore);
        layout.Controls.Add(actions, 0, 2); layout.SetColumnSpan(actions, 2);
        layout.Controls.Add(log, 0, 3); layout.SetColumnSpan(log, 2);
        var footer = new Label { AutoSize = true, Text = "Version 1.0.2 · Windows x64 · Unsigned · Readable log saved to Desktop" };
        layout.Controls.Add(footer, 0, 4); layout.SetColumnSpan(footer, 2); Controls.Add(layout);
        foreach (string candidate in Discovery.Find()) root.Items.Add(candidate);
        if (root.Items.Count == 1) root.SelectedIndex = 0;
        browse.Click += (_, _) => { using var dialog = new FolderBrowserDialog { Description = "Select the DreamGrid folder containing Settings.ini, Apache and Opensim" }; if (dialog.ShowDialog(this) == DialogResult.OK) root.Text = dialog.SelectedPath; };
        install.Click += async (_, _) => { if (root.Text.Trim() == "") { MessageBox.Show(this, "Choose the DreamGrid folder using Browse."); return; } await Execute(new Options { Root = root.Text.Trim() }); };
        restore.Click += async (_, _) => { using var dialog = new FolderBrowserDialog { Description = "Select the backup folder containing manifest.json and installation.json" }; if (dialog.ShowDialog(this) == DialogResult.OK) await Execute(new Options { RestoreArchive = dialog.SelectedPath }); };
        FormClosing += (_, e) => { if (busy) { e.Cancel = true; MessageBox.Show(this, "Wait for installation or automatic rollback to finish."); } };
    }
    async Task Execute(Options options)
    {
        busy = true; install.Enabled = restore.Enabled = browse.Enabled = root.Enabled = false;
        log.AppendText("Log: " + options.LogPath + Environment.NewLine);
        try
        {
            using var engine = new Engine(options.LogPath);
            engine.Progress += text => { if (!IsDisposed) BeginInvoke(() => log.AppendText(text + Environment.NewLine)); };
            int result = await Task.Run(() => engine.Run(options));
            MessageBox.Show(this, result == 0 ? "Completed. The log records the verified backup/restore location." : "Setup could not complete. Read the log for the exact cause.", Text, MessageBoxButtons.OK, result == 0 ? MessageBoxIcon.Information : MessageBoxIcon.Error);
        }
        catch (Exception error) { log.AppendText(error.Message + Environment.NewLine); MessageBox.Show(this, error.Message); }
        finally { busy = false; install.Enabled = restore.Enabled = browse.Enabled = root.Enabled = true; }
    }
}

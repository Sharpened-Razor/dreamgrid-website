using System;
using System.Collections;
using System.Collections.Generic;
using System.Globalization;
using System.IO;
using System.Net;
using System.Net.Sockets;
using System.Reflection;
using System.Text;
using System.Threading;

internal class StartupHook
{
    public static void Initialize()
    {
        try
        {
            Thread thread =
                new Thread(
                    DreamGridNativeBridge.Run
                );

            thread.IsBackground = true;
            thread.Name = "DreamGrid Native Region Bridge";
            thread.Start();
        }
        catch
        {
            // The bridge must never stop DreamGrid from starting.
        }
    }
}

internal static class DreamGridNativeBridge
{
    private const string KeyHeader =
        "X-DreamGrid-Bridge-Key";

    private static string ControlDirectory {
        get { return FindControlDirectory(AppContext.BaseDirectory); }
    }

    private static string FindControlDirectory(string startDirectory) {
        string configured = Environment.GetEnvironmentVariable("DREAMGRID_ROOT");
        if (!String.IsNullOrWhiteSpace(configured)) {
            string root = Path.GetFullPath(configured);
            if (!File.Exists(Path.Combine(root, "Settings.ini")))
                throw new InvalidOperationException("Configured DreamGrid root has no Settings.ini.");
            return Path.Combine(root, "_WEB_CONTROL");
        }
        DirectoryInfo current = new DirectoryInfo(startDirectory);
        while (current != null) {
            if (File.Exists(Path.Combine(current.FullName, "Settings.ini")))
                return Path.Combine(current.FullName, "_WEB_CONTROL");
            string candidate = null;
            foreach (DirectoryInfo child in current.GetDirectories()) {
                if (!File.Exists(Path.Combine(child.FullName, "Settings.ini")) ||
                    !Directory.Exists(Path.Combine(child.FullName, "_WEB_CONTROL"))) continue;
                if (candidate != null)
                    throw new InvalidOperationException("More than one DreamGrid root was found; configure DREAMGRID_ROOT.");
                candidate = Path.Combine(child.FullName, "_WEB_CONTROL");
            }
            if (candidate != null) return candidate;
            current = current.Parent;
        }
        throw new DirectoryNotFoundException("DreamGrid settings were not found.");
    }

    private static string KeyPath
    {
        get
        {
            return Path.Combine(
                ControlDirectory,
                "DreamGrid.NativeBridge.key"
            );
        }
    }

    private static string PortPath
    {
        get
        {
            return Path.Combine(
                ControlDirectory,
                "DreamGrid.NativeBridge.port"
            );
        }
    }

    private static string LogPath
    {
        get
        {
            return Path.Combine(
                ControlDirectory,
                "DreamGrid.NativeBridge.log"
            );
        }
    }

    public static void Run()
    {
        try
        {
            Directory.CreateDirectory(
                ControlDirectory
            );

            string key =
                File.ReadAllText(
                    KeyPath
                ).Trim();

            int port =
                Int32.Parse(
                    File.ReadAllText(
                        PortPath
                    ).Trim(),
                    CultureInfo.InvariantCulture
                );

            TcpListener listener =
                new TcpListener(
                    IPAddress.Loopback,
                    port
                );

            listener.Start(8);

            Log(
                "Listening on " + IPAddress.Loopback.ToString() + ":" +
                port.ToString(
                    CultureInfo.InvariantCulture
                )
            );

            while (true)
            {
                TcpClient client =
                    listener.AcceptTcpClient();

                ThreadPool.QueueUserWorkItem(
                    delegate
                    {
                        HandleClient(
                            client,
                            key
                        );
                    }
                );
            }
        }
        catch (Exception ex)
        {
            Log(
                "Bridge failed: " +
                ExceptionText(ex)
            );
        }
    }

    private static void HandleClient(
        TcpClient client,
        string key
    )
    {
        using (client)
        {
            try
            {
                client.ReceiveTimeout = 10000;
                client.SendTimeout = 150000;

                IPEndPoint remote =
                    client.Client.RemoteEndPoint
                    as IPEndPoint;

                if (
                    remote == null ||
                    !IPAddress.IsLoopback(
                        remote.Address
                    )
                )
                {
                    WriteResponse(
                        client,
                        403,
                        false,
                        false,
                        "Loopback access only."
                    );

                    return;
                }

                NetworkStream stream =
                    client.GetStream();

                StreamReader reader =
                    new StreamReader(
                        stream,
                        new UTF8Encoding(false),
                        false,
                        4096,
                        true
                    );

                string requestLine =
                    reader.ReadLine();

                if (
                    String.IsNullOrWhiteSpace(
                        requestLine
                    )
                )
                {
                    return;
                }

                Dictionary<string, string> headers =
                    new Dictionary<string, string>(
                        StringComparer.OrdinalIgnoreCase
                    );

                while (true)
                {
                    string line =
                        reader.ReadLine();

                    if (
                        line == null ||
                        line.Length == 0
                    )
                    {
                        break;
                    }

                    int colon =
                        line.IndexOf(':');

                    if (colon <= 0)
                    {
                        continue;
                    }

                    headers[
                        line.Substring(
                            0,
                            colon
                        ).Trim()
                    ] =
                        line.Substring(
                            colon + 1
                        ).Trim();
                }

                string suppliedKey = "";

                headers.TryGetValue(
                    KeyHeader,
                    out suppliedKey
                );

                if (
                    !String.Equals(
                        suppliedKey,
                        key,
                        StringComparison.Ordinal
                    )
                )
                {
                    WriteResponse(
                        client,
                        403,
                        false,
                        false,
                        "Bridge authentication failed."
                    );

                    return;
                }

                string[] requestParts =
                    requestLine.Split(
                        new char[] { ' ' },
                        StringSplitOptions.RemoveEmptyEntries
                    );

                if (requestParts.Length < 2)
                {
                    WriteResponse(
                        client,
                        400,
                        false,
                        false,
                        "Invalid HTTP request."
                    );

                    return;
                }

                string route =
                    requestParts[1];

                int question =
                    route.IndexOf('?');

                if (question >= 0)
                {
                    route =
                        route.Substring(
                            0,
                            question
                        );
                }

                int contentLength = 0;
                string contentLengthText = "";

                if (
                    headers.TryGetValue(
                        "Content-Length",
                        out contentLengthText
                    )
                )
                {
                    Int32.TryParse(
                        contentLengthText,
                        NumberStyles.Integer,
                        CultureInfo.InvariantCulture,
                        out contentLength
                    );
                }

                string body = "";

                if (contentLength > 0)
                {
                    char[] buffer =
                        new char[
                            contentLength
                        ];

                    int total = 0;

                    while (total < contentLength)
                    {
                        int read =
                            reader.Read(
                                buffer,
                                total,
                                contentLength - total
                            );

                        if (read <= 0)
                        {
                            break;
                        }

                        total += read;
                    }

                    body =
                        new string(
                            buffer,
                            0,
                            total
                        );
                }

                if (
                    route.Equals(
                        "/health",
                        StringComparison.OrdinalIgnoreCase
                    )
                )
                {
                    bool ready =
                        IsReady();

                    WriteResponse(
                        client,
                        ready ? 200 : 503,
                        ready,
                        ready,
                        ready
                            ? "DreamGrid Native Bridge is ready."
                            : "DreamGrid FormSetup is not ready yet."
                    );

                    return;
                }

                Dictionary<string, string> form =
                    ParseForm(
                        body
                    );

                string uuidText = "";

                form.TryGetValue(
                    "uuid",
                    out uuidText
                );

                Guid uuid;

                if (
                    !Guid.TryParse(
                        uuidText,
                        out uuid
                    )
                )
                {
                    WriteResponse(
                        client,
                        400,
                        false,
                        IsReady(),
                        "A valid RegionUUID is required."
                    );

                    return;
                }

                if (
                    route.Equals(
                        "/deregister",
                        StringComparison.OrdinalIgnoreCase
                    )
                )
                {
                    string message =
                        RunOnDreamGridUi(
                            delegate
                            {
                                return NativeDeregister(
                                    uuid
                                );
                            }
                        );

                    WriteResponse(
                        client,
                        200,
                        true,
                        true,
                        message
                    );

                    return;
                }

                if (
                    route.Equals(
                        "/delete",
                        StringComparison.OrdinalIgnoreCase
                    )
                )
                {
                    string message =
                        RunOnDreamGridUi(
                            delegate
                            {
                                return NativeDelete(
                                    uuid
                                );
                            }
                        );

                    WriteResponse(
                        client,
                        200,
                        true,
                        true,
                        message
                    );

                    return;
                }

                WriteResponse(
                    client,
                    404,
                    false,
                    IsReady(),
                    "Unknown bridge command."
                );
            }
            catch (Exception ex)
            {
                Log(
                    "Request failed: " +
                    ExceptionText(ex)
                );

                try
                {
                    WriteResponse(
                        client,
                        500,
                        false,
                        IsReady(),
                        ExceptionText(ex)
                    );
                }
                catch
                {
                }
            }
        }
    }

    private static string NativeDeregister(
        Guid uuid
    )
    {
        CallStatic(
            "Outworldz.MysqlInterface",
            "StartMysql",
            Type.EmptyTypes,
            Array.Empty<object>()
        );

        CallStatic(
            "Outworldz.RegionMaker",
            "StopOneRegion",
            new Type[]
            {
                typeof(Guid)
            },
            new object[]
            {
                uuid
            }
        );

        bool running = true;

        for (
            int attempt = 0;
            attempt < 120;
            attempt++
        )
        {
            running =
                Convert.ToBoolean(
                    CallStatic(
                        "Outworldz.PublicIP",
                        "CheckPid",
                        new Type[]
                        {
                            typeof(Guid)
                        },
                        new object[]
                        {
                            uuid
                        }
                    ),
                    CultureInfo.InvariantCulture
                );

            if (!running)
            {
                break;
            }

            Thread.Sleep(
                1000
            );
        }

        if (running)
        {
            throw new InvalidOperationException(
                "DreamGrid timed out waiting for the selected region process to stop. Deregistration was NOT performed."
            );
        }

        CallStatic(
            "Outworldz.MysqlInterface",
            "DeregisterRegionUuid",
            new Type[]
            {
                typeof(Guid)
            },
            new object[]
            {
                uuid
            }
        );

        return
            "DreamGrid native deregistration completed.";
    }

    private static string NativeDelete(
        Guid uuid
    )
    {
        CallStatic(
            "Outworldz.FileStuff",
            "DeleteAllContents",
            new Type[]
            {
                typeof(Guid)
            },
            new object[]
            {
                uuid
            }
        );

        return
            "DreamGrid native permanent delete completed.";
    }

    private static object CallStatic(
        string typeName,
        string methodName,
        Type[] parameterTypes,
        object[] arguments
    )
    {
        Assembly start =
            FindStartAssembly();

        if (start == null)
        {
            throw new InvalidOperationException(
                "DreamGrid Start assembly is not loaded."
            );
        }

        Type type =
            start.GetType(
                typeName,
                true
            );

        MethodInfo method =
            type.GetMethod(
                methodName,
                BindingFlags.Public |
                BindingFlags.NonPublic |
                BindingFlags.Static,
                null,
                parameterTypes,
                null
            );

        if (method == null)
        {
            throw new MissingMethodException(
                typeName +
                "." +
                methodName
            );
        }

        try
        {
            return
                method.Invoke(
                    null,
                    arguments
                );
        }
        catch (Exception ex)
        {
            throw Unwrap(ex);
        }
    }

    private static string RunOnDreamGridUi(
        Func<string> work
    )
    {
        object setup =
            FindOpenFormSetup();

        if (setup == null)
        {
            throw new InvalidOperationException(
                "The existing DreamGrid FormSetup window was not found."
            );
        }

        MethodInfo runOnUi =
            setup.GetType().GetMethod(
                "RunOnUi",
                BindingFlags.Public |
                BindingFlags.NonPublic |
                BindingFlags.Instance,
                null,
                new Type[]
                {
                    typeof(Action)
                },
                null
            );

        if (runOnUi == null)
        {
            throw new MissingMethodException(
                "Outworldz.FormSetup.RunOnUi(Action)"
            );
        }

        string result = "";
        Exception operationError = null;

        Action action =
            delegate
            {
                try
                {
                    result =
                        work() ?? "";
                }
                catch (Exception ex)
                {
                    operationError =
                        Unwrap(ex);
                }
            };

        try
        {
            runOnUi.Invoke(
                setup,
                new object[]
                {
                    action
                }
            );
        }
        catch (Exception ex)
        {
            throw Unwrap(ex);
        }

        if (operationError != null)
        {
            throw operationError;
        }

        return result;
    }

    private static object FindOpenFormSetup()
    {
        try
        {
            Type applicationType =
                Type.GetType(
                    "System.Windows.Forms.Application, System.Windows.Forms",
                    false
                );

            if (applicationType == null)
            {
                return null;
            }

            PropertyInfo openFormsProperty =
                applicationType.GetProperty(
                    "OpenForms",
                    BindingFlags.Public |
                    BindingFlags.Static
                );

            if (openFormsProperty == null)
            {
                return null;
            }

            object collection =
                openFormsProperty.GetValue(
                    null
                );

            IEnumerable enumerable =
                collection
                as IEnumerable;

            if (enumerable == null)
            {
                return null;
            }

            foreach (object form in enumerable)
            {
                if (form == null)
                {
                    continue;
                }

                if (
                    String.Equals(
                        form.GetType().FullName,
                        "Outworldz.FormSetup",
                        StringComparison.Ordinal
                    )
                )
                {
                    return form;
                }
            }
        }
        catch
        {
        }

        return null;
    }

    private static Assembly FindStartAssembly()
    {
        Assembly[] assemblies =
            AppDomain.CurrentDomain.GetAssemblies();

        foreach (Assembly assembly in assemblies)
        {
            try
            {
                if (
                    String.Equals(
                        assembly.GetName().Name,
                        "Start",
                        StringComparison.OrdinalIgnoreCase
                    )
                )
                {
                    return assembly;
                }
            }
            catch
            {
            }
        }

        return null;
    }

    private static bool IsReady()
    {
        return
            FindStartAssembly() != null &&
            FindOpenFormSetup() != null;
    }

    private static Dictionary<string, string> ParseForm(
        string body
    )
    {
        Dictionary<string, string> result =
            new Dictionary<string, string>(
                StringComparer.OrdinalIgnoreCase
            );

        if (
            String.IsNullOrWhiteSpace(
                body
            )
        )
        {
            return result;
        }

        string[] pieces =
            body.Split('&');

        foreach (string piece in pieces)
        {
            int equals =
                piece.IndexOf('=');

            string name =
                equals >= 0
                    ? piece.Substring(
                        0,
                        equals
                    )
                    : piece;

            string value =
                equals >= 0
                    ? piece.Substring(
                        equals + 1
                    )
                    : "";

            name =
                Uri.UnescapeDataString(
                    name.Replace(
                        "+",
                        " "
                    )
                );

            value =
                Uri.UnescapeDataString(
                    value.Replace(
                        "+",
                        " "
                    )
                );

            result[name] =
                value;
        }

        return result;
    }

    private static void WriteResponse(
        TcpClient client,
        int status,
        bool ok,
        bool ready,
        string message
    )
    {
        string json =
            "{" +
            "\"ok\":" +
            (ok ? "true" : "false") +
            "," +
            "\"ready\":" +
            (ready ? "true" : "false") +
            "," +
            "\"message\":\"" +
            JsonEscape(
                message ?? ""
            ) +
            "\"" +
            "}";

        byte[] body =
            Encoding.UTF8.GetBytes(
                json
            );

        string statusText =
            status == 200
                ? "OK"
                : status == 400
                    ? "Bad Request"
                    : status == 403
                        ? "Forbidden"
                        : status == 404
                            ? "Not Found"
                            : status == 503
                                ? "Service Unavailable"
                                : "Internal Server Error";

        string headers =
            "HTTP/1.1 " +
            status.ToString(
                CultureInfo.InvariantCulture
            ) +
            " " +
            statusText +
            "\r\n" +
            "Content-Type: application/json; charset=utf-8\r\n" +
            "Content-Length: " +
            body.Length.ToString(
                CultureInfo.InvariantCulture
            ) +
            "\r\n" +
            "Connection: close\r\n" +
            "\r\n";

        byte[] headerBytes =
            Encoding.ASCII.GetBytes(
                headers
            );

        NetworkStream stream =
            client.GetStream();

        stream.Write(
            headerBytes,
            0,
            headerBytes.Length
        );

        stream.Write(
            body,
            0,
            body.Length
        );

        stream.Flush();
    }

    private static string JsonEscape(
        string text
    )
    {
        return
            text
                .Replace(
                    "\\",
                    "\\\\"
                )
                .Replace(
                    "\"",
                    "\\\""
                )
                .Replace(
                    "\r",
                    "\\r"
                )
                .Replace(
                    "\n",
                    "\\n"
                )
                .Replace(
                    "\t",
                    "\\t"
                );
    }

    private static Exception Unwrap(
        Exception ex
    )
    {
        Exception current =
            ex;

        while (
            current is TargetInvocationException &&
            current.InnerException != null
        )
        {
            current =
                current.InnerException;
        }

        return current;
    }

    private static string ExceptionText(
        Exception ex
    )
    {
        Exception actual =
            Unwrap(ex);

        return
            actual.GetType().Name +
            ": " +
            actual.Message;
    }

    private static void Log(
        string text
    )
    {
        try
        {
            Directory.CreateDirectory(
                ControlDirectory
            );

            File.AppendAllText(
                LogPath,
                DateTime.Now.ToString(
                    "yyyy-MM-dd HH:mm:ss",
                    CultureInfo.InvariantCulture
                ) +
                " " +
                text +
                Environment.NewLine,
                new UTF8Encoding(false)
            );
        }
        catch
        {
        }
    }
}
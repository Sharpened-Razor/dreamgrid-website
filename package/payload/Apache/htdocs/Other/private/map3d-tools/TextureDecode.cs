using System;
using System.IO;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Runtime.Loader;
using System.Text.Json;
class TextureDecode {
    static int Main(string[] args) {
        try {
            if(args.Length!=3)return 2;
            string bin=Path.GetFullPath(args[0]);
            AppDomain.CurrentDomain.AssemblyResolve += (sender,e) => {
                string name=new AssemblyName(e.Name).Name;
                if(name!="OpenMetaverse"&&name!="OpenMetaverseTypes"&&name!="OpenMetaverse.StructuredData"&&name!="System.Drawing.Common"&&name!="Microsoft.Win32.SystemEvents"&&name!="log4net"&&name!="CSJ2K")return null;
                return Assembly.LoadFrom(Path.Combine(bin,name+".dll"));
            };
            // The installed library owns its DLL-map resolver; its relative native paths
            // must resolve against OpenSim/bin, independently of Apache's directory.
            Directory.SetCurrentDirectory(bin);
            // Preserve OpenMetaverse's resolver, with an explicit fallback for hosts
            // where its DLL-map platform selection cannot locate the native library.
            AssemblyLoadContext.Default.ResolvingUnmanagedDll += (assembly,name) =>
                name=="openjpeg-dotnet" ? NativeLibrary.Load(Path.Combine(bin,"lib64","openjpeg-dotnet-x86_64.dll")) : IntPtr.Zero;
            Decode(args[1],args[2]);return 0;
        } catch(Exception e) { Console.Error.WriteLine(e.ToString()); Console.WriteLine(JsonSerializer.Serialize(new {ok=false,error=e.GetType().Name}));return 1; }
    }
    [System.Runtime.CompilerServices.MethodImpl(System.Runtime.CompilerServices.MethodImplOptions.NoInlining)]
    static void Decode(string source,string output) {
        byte[] bytes=File.ReadAllBytes(source);
        if(bytes.Length>16777216)throw new ArgumentException("Asset exceeds limit");
        OpenMetaverse.Imaging.ManagedImage image;
        if(!OpenMetaverse.Imaging.OpenJPEG.DecodeToImage(bytes,out image)||image==null)throw new InvalidDataException();
        if(image.Width<1||image.Height<1||image.Width>4096||image.Height>4096)throw new InvalidDataException();
        using(var bitmap=image.ExportBitmap()) bitmap.Save(output,System.Drawing.Imaging.ImageFormat.Png);
        Console.WriteLine(JsonSerializer.Serialize(new {ok=true,Width=image.Width,Height=image.Height}));
    }
}

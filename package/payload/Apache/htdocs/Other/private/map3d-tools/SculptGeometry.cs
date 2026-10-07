using System;
using System.IO;
using System.Drawing;
using System.Reflection;
using System.Collections.Generic;
using System.Text.Json;

// Read-only adapter around the installed OpenSimulator PrimMesher implementation.
// Input is the full decoded sculpt map, never a resampled display preview.
class SculptGeometry {
    static int Main(string[] args) {
        try {
            if (args.Length != 5) return 2;
            string bin = Path.GetFullPath(args[0]);
            AppDomain.CurrentDomain.AssemblyResolve += delegate(object sender, ResolveEventArgs e) {
                string name = new AssemblyName(e.Name).Name;
                if (name != "PrimMesher" && name != "OpenMetaverseTypes" && name != "OpenMetaverse" && name != "OpenMetaverse.StructuredData" && name != "OpenMetaverse.Rendering.Meshmerizer" && name != "System.Drawing.Common" && name != "Microsoft.Win32.SystemEvents" && name != "log4net") return null;
                return Assembly.LoadFrom(Path.Combine(bin, name + ".dll"));
            };
            Generate(args);
            return 0;
        } catch (Exception e) { Console.Error.WriteLine(e.GetType().Name + ": " + e.Message); return 1; }
    }
    [System.Runtime.CompilerServices.MethodImpl(System.Runtime.CompilerServices.MethodImplOptions.NoInlining)]
    static void Generate(string[] args) {
        int flags = Int32.Parse(args[4]), type = flags & 7;
        if (flags == 0) { GeneratePrim(args); return; }
        if (type < 1 || type > 4 || (flags & ~199) != 0) throw new ArgumentException("Invalid sculpt type");
        using (Bitmap bitmap = new Bitmap(args[1])) {
            if (bitmap.Width > 2048 || bitmap.Height > 2048) throw new ArgumentException("Sculpt image exceeds limit");
            var mesh = new PrimMesher.SculptMesh(bitmap, (PrimMesher.SculptMesh.SculptType)type, 32, true,
                (flags & 128) != 0, (flags & 64) != 0);
            var positions = new List<float>(); var normals = new List<float>();
            var uvs = new List<float>(); var indices = new List<int>();
            foreach (var v in mesh.coords) { positions.Add(v.X); positions.Add(v.Z); positions.Add(-v.Y); }
            foreach (var n in mesh.normals) { normals.Add(n.X); normals.Add(n.Z); normals.Add(-n.Y); }
            foreach (var uv in mesh.uvs) { uvs.Add(uv.U); uvs.Add(uv.V); }
            foreach (var face in mesh.faces) { indices.Add(face.v1); indices.Add(face.v2); indices.Add(face.v3); }
            var result = new { ok=true, MeshUuid=args[3], Source="OPENSIM_SCULPT", Lod="sculpt_32",
                VertexCount=mesh.coords.Count, TriangleCount=mesh.faces.Count,
                Positions=positions, Normals=normals, Uvs=uvs, Indices=indices,
                Submeshes=new [] {new {Slot=0, VertexStart=0, VertexCount=mesh.coords.Count, IndexStart=0, IndexCount=indices.Count}} };
            File.WriteAllText(args[2], JsonSerializer.Serialize(result));
        }
    }
    static void GeneratePrim(string[] args) {
        int[] s = Array.ConvertAll(args[1].Split(','), Int32.Parse);
        if (s.Length != 20 || s[0] != 9) throw new ArgumentException("Invalid prim parameters");
        var p = new OpenMetaverse.Primitive();
        p.Textures = new OpenMetaverse.Primitive.TextureEntry(OpenMetaverse.UUID.Zero);
        p.PrimData.PCode = OpenMetaverse.PCode.Prim;
        p.PrimData.profileCurve = (byte)s[2]; p.PrimData.PathCurve = (OpenMetaverse.PathCurve)s[3];
        p.PrimData.ProfileBegin = OpenMetaverse.Primitive.UnpackBeginCut((ushort)s[4]);
        p.PrimData.ProfileEnd = OpenMetaverse.Primitive.UnpackEndCut((ushort)s[5]);
        p.PrimData.ProfileHollow = OpenMetaverse.Primitive.UnpackProfileHollow((ushort)s[6]);
        p.PrimData.PathBegin = OpenMetaverse.Primitive.UnpackBeginCut((ushort)s[7]);
        p.PrimData.PathEnd = OpenMetaverse.Primitive.UnpackEndCut((ushort)s[8]);
        p.PrimData.PathScaleX = OpenMetaverse.Primitive.UnpackPathScale((byte)s[9]);
        p.PrimData.PathScaleY = OpenMetaverse.Primitive.UnpackPathScale((byte)s[10]);
        p.PrimData.PathShearX = OpenMetaverse.Primitive.UnpackPathShear(unchecked((sbyte)s[11]));
        p.PrimData.PathShearY = OpenMetaverse.Primitive.UnpackPathShear(unchecked((sbyte)s[12]));
        p.PrimData.PathTwist = OpenMetaverse.Primitive.UnpackPathTwist(unchecked((sbyte)s[13]));
        p.PrimData.PathTwistBegin = OpenMetaverse.Primitive.UnpackPathTwist(unchecked((sbyte)s[14]));
        p.PrimData.PathRadiusOffset = OpenMetaverse.Primitive.UnpackPathTwist(unchecked((sbyte)s[15]));
        p.PrimData.PathTaperX = OpenMetaverse.Primitive.UnpackPathTaper(unchecked((sbyte)s[16]));
        p.PrimData.PathTaperY = OpenMetaverse.Primitive.UnpackPathTaper(unchecked((sbyte)s[17]));
        p.PrimData.PathRevolutions = OpenMetaverse.Primitive.UnpackPathRevolutions((byte)s[18]);
        p.PrimData.PathSkew = OpenMetaverse.Primitive.UnpackPathTwist(unchecked((sbyte)s[19]));
        var mesh = new OpenMetaverse.Rendering.MeshmerizerR().GenerateFacetedMesh(p, OpenMetaverse.Rendering.DetailLevel.High);
        if (mesh == null) throw new ArgumentException("Unsupported prim shape");
        var positions = new List<float>(); var normals = new List<float>();
        var uvs = new List<float>(); var indices = new List<int>(); var slots = new List<object>();
        for (int slot=0;slot<mesh.Faces.Count;slot++) {
            var face=mesh.Faces[slot]; int start=positions.Count/3, indexStart=indices.Count;
            foreach(var v in face.Vertices) {
                positions.Add(v.Position.X); positions.Add(v.Position.Z); positions.Add(-v.Position.Y);
                normals.Add(v.Normal.X); normals.Add(v.Normal.Z); normals.Add(-v.Normal.Y);
                uvs.Add(v.TexCoord.X); uvs.Add(v.TexCoord.Y);
            }
            foreach(var index in face.Indices) indices.Add(start+index);
            slots.Add(new {Slot=slot, VertexStart=start, VertexCount=face.Vertices.Count, IndexStart=indexStart, IndexCount=face.Indices.Count});
        }
        var result=new {ok=true,MeshUuid=args[3],Source="OPENSIM_PRIM",Lod="prim_high",
            VertexCount=positions.Count/3,TriangleCount=indices.Count/3,Positions=positions,Normals=normals,Uvs=uvs,Indices=indices,Submeshes=slots};
        File.WriteAllText(args[2],JsonSerializer.Serialize(result));
    }
}

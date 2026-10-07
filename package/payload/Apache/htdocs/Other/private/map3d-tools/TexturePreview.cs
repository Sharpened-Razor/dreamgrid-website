using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;

// Converts only a server-selected PNG cache file; original OpenSim assets are never written.
internal static class TexturePreview {
    private static int Main(string[] args) {
        try {
            int limit;
            if (args.Length != 3 || !int.TryParse(args[2], out limit) || limit < 64 || limit > 512) return 2;
            using (Image input = Image.FromFile(args[0])) {
                if (input.Width > 8192 || input.Height > 8192) return 3;
                double ratio = Math.Min(1.0, (double)limit / Math.Max(input.Width, input.Height));
                int width = Math.Max(1, (int)Math.Round(input.Width * ratio));
                int height = Math.Max(1, (int)Math.Round(input.Height * ratio));
                using (Bitmap output = new Bitmap(width, height, PixelFormat.Format32bppArgb))
                using (Graphics graphics = Graphics.FromImage(output))
                using (ImageAttributes attributes = new ImageAttributes()) {
                    graphics.CompositingMode = CompositingMode.SourceCopy;
                    graphics.InterpolationMode = InterpolationMode.HighQualityBicubic;
                    graphics.PixelOffsetMode = PixelOffsetMode.HighQuality;
                    attributes.SetWrapMode(WrapMode.TileFlipXY);
                    graphics.DrawImage(input, new Rectangle(0, 0, width, height), 0, 0, input.Width, input.Height, GraphicsUnit.Pixel, attributes);
                    output.Save(args[1], ImageFormat.Png);
                }
                Console.Write("{\"ok\":true,\"width\":" + width + ",\"height\":" + height + "}");
            }
            return 0;
        } catch (Exception) { Console.Error.Write("Texture preview conversion failed."); return 1; }
    }
}

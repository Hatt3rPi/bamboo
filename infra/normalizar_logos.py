#!/usr/bin/env python3
"""
Normaliza los logos de aseguradoras del marquee de la landing a un ESTÁNDAR uniforme
(pedido Adriana jul-2026: "que los logos tengan el mismo tamaño").

Qué hace con cada imagen:
  1. Recorta el margen en blanco/transparente real (trim por diferencia de color sobre
     blanco — robusto ante "halos" tenues del canal alpha que hacían fallar el trim por alpha).
  2. Escala el contenido a una ALTURA fija (H). Si a esa altura el ancho supera MAXW
     (wordmarks muy anchos, p.ej. CHUBB), escala por ancho (queda algo más bajo).
  3. Lo centra en un lienzo transparente de altura fija (H + 2*PADV) y ancho = contenido + 2*PADH.
Como todas las imágenes de salida comparten el mismo alto de lienzo, en el CSS basta
`.marquee img{height:42px}` para que se vean parejas.

Uso:
  # 1) Coloca los logos ORIGINALES en <IN_DIR> (PNG/JPG). Para SVG, rasteriza antes con
  #    ImageMagick:  magick -background none -density 500 logo.svg logo_raster.png
  # 2) python infra/normalizar_logos.py <IN_DIR> <OUT_DIR>
Requiere: Pillow.  (SVG -> PNG: ImageMagick `magick`.)
"""
import sys, os
from PIL import Image, ImageChops

# --- Estándar (ajústalo aquí si cambia el criterio) ---
H, MAXW, PADV, PADH, THR = 76, 380, 8, 22, 40

def trim(im, thr=THR):
    """Bounding box del contenido real, robusto ante halos de alpha."""
    im = im.convert("RGBA")
    white = Image.new("RGBA", im.size, (255, 255, 255, 255))
    comp = Image.alpha_composite(white, im).convert("RGB")
    diff = ImageChops.difference(comp, Image.new("RGB", im.size, (255, 255, 255))).convert("L")
    bb = diff.point(lambda p: 255 if p > thr else 0).getbbox() or im.split()[-1].getbbox()
    return im.crop(bb) if bb else im

def normalize(im):
    im = trim(im); w, h = im.size
    s = H / h
    if w * s > MAXW:
        s = MAXW / w
    nw, nh = max(1, round(w * s)), max(1, round(h * s))
    im = im.resize((nw, nh), Image.LANCZOS)
    canvas = Image.new("RGBA", (nw + 2 * PADH, H + 2 * PADV), (0, 0, 0, 0))
    canvas.paste(im, (PADH, (H + 2 * PADV - nh) // 2), im)
    return canvas

def main(in_dir, out_dir):
    os.makedirs(out_dir, exist_ok=True)
    n = 0
    for f in sorted(os.listdir(in_dir)):
        if f.lower().endswith((".png", ".jpg", ".jpeg")):
            base = os.path.splitext(f)[0]
            normalize(Image.open(os.path.join(in_dir, f))).save(os.path.join(out_dir, base + ".png"))
            n += 1
    print(f"Normalizados {n} logos -> {out_dir}")

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(__doc__); sys.exit(1)
    main(sys.argv[1], sys.argv[2])

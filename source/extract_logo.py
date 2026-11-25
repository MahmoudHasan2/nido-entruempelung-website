"""
Rebuilds the Nido logo from the vector shapes of the original flyer (Flyer.pdf, page 1) as a clean,
transparent logo. Requires: pip install pymupdf pillow
Usage: python source/extract_logo.py
Colours sampled from the flyer: orange #E77524 (0.9037,0.46,0.143), charcoal #231F20 (0.1367,0.1220,0.1253).
"""
import pymupdf as fitz

src = fitz.open("source/Flyer.pdf")
pg = src[0]
R = fitz.Rect(20, 205, 305, 308)                       # logo area on page 1
shapes = [d for d in pg.get_drawings() if d["rect"].intersects(R) and d["rect"].width < 400]
font_name, ext, typ, buf = src.extract_font(11)         # embedded MyriadPro-Bold subset (CFF)

X0, Y0, X1, Y1 = 22, 205, 298, 304.5
out = fitz.open()
page = out.new_page(width=X1 - X0, height=Y1 - Y0)
P = lambda p: fitz.Point(p.x - X0, p.y - Y0)
sh = page.new_shape()
for d in shapes:
    for it in d["items"]:
        if it[0] == "l":
            sh.draw_line(P(it[1]), P(it[2]))
        elif it[0] == "c":
            sh.draw_bezier(P(it[1]), P(it[2]), P(it[3]), P(it[4]))
        elif it[0] == "re":
            sh.draw_rect(fitz.Rect(it[1].x0 - X0, it[1].y0 - Y0, it[1].x1 - X0, it[1].y1 - Y0))
    sh.finish(fill=d["fill"], color=None, even_odd=d.get("even_odd", False), closePath=True)
sh.commit()
page.insert_font(fontname="myr", fontbuffer=buf)
page.insert_text(fitz.Point(125.55 - X0, 273.12 - Y0), "NIDO", fontname="myr", fontsize=67.8, color=(0.13669, 0.12195, 0.12529))
page.insert_text(fitz.Point(132.08 - X0, 299.31 - Y0), "ENTRÜMPELUNG", fontname="myr", fontsize=19.0, color=(1, 1, 1))
out.save("source/logo-from-flyer.pdf")
open("assets/img/logo.svg", "w", encoding="utf-8").write(page.get_svg_image(text_as_path=True))
# light variant for dark backgrounds: swap the charcoal fill for white
svg = open("assets/img/logo.svg", encoding="utf-8").read()
open("assets/img/logo-light.svg", "w", encoding="utf-8").write(svg.replace("#231f20", "#ffffff"))

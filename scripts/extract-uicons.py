"""
Extrait les icônes UIcons (Flaticon, style « Regular Rounded ») utilisées par le site en fichiers SVG
dans resources/icons/, plutôt que de charger toute la police d'icônes (385 Ko).

Usage : python3 scripts/extract-uicons.py        (nécessite fonttools et brotli : pip install fonttools brotli)
Source : node_modules/@flaticon/flaticon-uicons (dépendance de développement).
Licence : gratuite avec mention « Icônes : UIcons par Flaticon » (pied de page du site et README).
"""
import glob
import os
import re

from fontTools.pens.boundsPen import BoundsPen
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.ttLib import TTFont

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PACKAGE = os.path.join(ROOT, 'node_modules', '@flaticon', 'flaticon-uicons', 'css')
OUT = os.path.join(ROOT, 'resources', 'icons')

# Nom dans le site => (série, nom UIcons). « rr » : Regular Rounded ; « brands » : GitHub ; « sr » : Solid Rounded (logo).
ICONS = {
    'propose': ('rr', 'add'),
    'search': ('rr', 'search'),
    'all-cards': ('rr', 'apps'),
    'awaiting': ('rr', 'hourglass-end'),
    'upcoming': ('rr', 'calendar-clock'),
    'review': ('rr', 'eye'),
    'closed': ('rr', 'gavel'),
    'verdict-positive': ('rr', 'check-circle'),
    'verdict-partial': ('rr', 'circle-half-stroke'),
    'verdict-negative': ('rr', 'cross-circle'),
    'verdict-neutral': ('rr', 'interrogation'),
    'theme': ('rr', 'hastag'),
    'promise': ('rr', 'handshake'),
    'prediction': ('rr', 'crystal-ball'),
    'account': ('rr', 'user'),
    'moderation': ('rr', 'shield-check'),
    'logout': ('rr', 'sign-out-alt'),
    'login': ('rr', 'sign-in-alt'),
    'menu': ('rr', 'menu-burger'),
    'list': ('rr', 'list'),
    'close': ('rr', 'cross-small'),
    'flag': ('rr', 'flag'),
    'calendar': ('rr', 'calendar'),
    'link': ('rr', 'link-alt'),
    'github': ('brands', 'github'),
    'logo-quote': ('sr', 'quote-right'),
}

SERIES = {
    'rr': ('regular/rounded.css', 'uicons-regular-rounded-*.woff2', 'fi-rr-'),
    'brands': ('brands/all.css', 'uicons-brands-*.woff2', 'fi-brands-'),
    'sr': ('solid/rounded.css', 'uicons-solid-rounded-*.woff2', 'fi-sr-'),
}


def codepoints(css_file, prefix):
    css = open(os.path.join(PACKAGE, css_file), encoding='utf-8').read()
    return {name: int(code, 16) for name, code in re.findall(r'\.' + re.escape(prefix) + r'([a-z0-9-]+):before\s*\{\s*content:\s*"\\([0-9a-f]+)"', css)}


os.makedirs(OUT, exist_ok=True)
fonts, maps = {}, {}
for key, (css_file, font_glob, prefix) in SERIES.items():
    fonts[key] = TTFont(glob.glob(os.path.join(PACKAGE, font_glob))[0])
    maps[key] = codepoints(css_file, prefix)

for name, (series, uicon) in ICONS.items():
    font = fonts[series]
    glyph_name = font.getBestCmap()[maps[series][uicon]]
    glyphs = font.getGlyphSet()
    em = font['head'].unitsPerEm
    ascent = font['hhea'].ascent

    pen = SVGPathPen(glyphs)
    glyphs[glyph_name].draw(pen)
    # Les polices ont l'axe vertical vers le haut : on le retourne pour le SVG.
    svg = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {em} {em}" fill="currentColor" aria-hidden="true">'
           f'<path transform="matrix(1 0 0 -1 0 {ascent})" d="{pen.getCommands()}"/></svg>\n')
    with open(os.path.join(OUT, f'{name}.svg'), 'w', encoding='utf-8') as file:
        file.write(svg)
    print(f'→ resources/icons/{name}.svg ({series} {uicon})')

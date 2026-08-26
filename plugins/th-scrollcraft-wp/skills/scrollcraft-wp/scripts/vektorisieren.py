#!/usr/bin/env python3
"""Flache Illustration aus PNG zurueck in geschichtetes SVG holen.

Wozu: eine Scroll-Seite will Bauteile bewegen, nicht ein Bild schieben. Liegt
der Hausstil nur als PNG vor, ist der Weg zurueck in Pfade der Unterschied
zwischen "wir animieren die Marke" und "wir schieben ein Bild hin und her".

Warum das ueberhaupt geht: eine flache Vektor-Illustration, die zu PNG
gerendert wurde, hat harte Kanten und eine Handvoll echter Farben. Die
zweitausend Farben, die eine Zaehlung findet, sind fast alle Kantenglaettung.
Wer auf die echte Palette quantisiert, bekommt saubere Flaechen zurueck, und
deren Umrisse sind wieder Pfade. Bei einem Foto waere das aussichtslos.

    ./vektorisieren.py bild.png ziel.svg [--farben 14] [--toleranz 0.8]

Ausgabe ist ein SVG, in dem jede Farbflaeche eine eigene Gruppe mit id ist,
von hinten nach vorne sortiert. Genau das, was man braucht, um einzelne Teile
an --sc-p zu haengen.

Grenzen, ehrlich:

- Es kommt heraus, was im Bild steht. Eine Figur laesst sich damit verschieben,
  skalieren und ueberblenden, aber nicht neu stellen. Wer einen anderen
  Blickwinkel braucht, braucht den Illustrator.
- Verlaeufe werden zu Stufen. Bei flacher Illustration egal, sonst nicht.
- Sehr feine Details unter der Toleranz fallen weg. Der Bericht sagt, wieviel.
"""

import argparse
import sys
from pathlib import Path

import numpy as np
from PIL import Image
from scipy import ndimage
from skimage import measure

# Wieviel feiner die Maske abgetastet wird, bevor die Kontur gesucht wird. Auf
# dem Pixelgitter selbst ist jede schraege Kante eine Treppe, und die bleibt in
# den Pfaden stehen. Vierfach reicht, achtfach kostet nur Zeit.
UEBERABTASTUNG = 4


def palette_holen(bild: Image.Image, farben: int):
    """Auf die echte Palette quantisieren und Flaechen zurueckgeben."""
    # Kantenglaettung erzeugt Zwischenfarben, die keine Flaechen sind. Ein
    # adaptives Quantisieren schnappt sie auf den naechsten echten Ton.
    quant = bild.convert("RGB").quantize(colors=farben, method=Image.MEDIANCUT, dither=Image.NONE)
    palette = np.array(quant.getpalette()[: farben * 3], dtype=np.uint8).reshape(-1, 3)
    indizes = np.array(quant, dtype=np.uint8)
    return indizes, palette


def teile_aus_maske(maske: np.ndarray):
    """Eine Farbflaeche in zusammenhaengende Stuecke zerlegen.

    Ohne das ist eine Ebene "alles Gruene": die Pflanze, der Punkt oben rechts
    und der Streifen am Aermel in einem Pfad. Zum Animieren ist das wertlos,
    weil man die Pflanze nicht bewegen kann, ohne den Punkt mitzunehmen.

    Mit Achter-Nachbarschaft, sonst zerfaellt jede diagonale Kante in Krumen.
    """
    struktur = np.ones((3, 3), dtype=bool)
    beschriftet, anzahl = ndimage.label(maske, structure=struktur)

    for n in range(1, anzahl + 1):
        stueck = beschriftet == n
        if stueck.sum() < 4:
            continue
        ys, xs = np.nonzero(stueck)
        yield stueck, (int(xs.min()), int(ys.min()), int(xs.max()), int(ys.max())), int(stueck.sum())


def als_kurve(x: np.ndarray, y: np.ndarray) -> str:
    """Geschlossenen Streckenzug als glatten Bezier-Pfad schreiben.

    Ein Streckenzug bleibt eckig, egal wie fein er abgetastet ist. Aus drei
    aufeinanderfolgenden Punkten laesst sich aber die Tangente im mittleren
    schaetzen, und daraus werden die beiden Kontrollpunkte eines kubischen
    Segments. Das ist Catmull-Rom, umgerechnet auf Bezier.

    Der Faktor 6 ist die uebliche Umrechnung. Kleiner macht die Kurve schlaffer,
    groesser laesst sie zwischen den Punkten ausbeulen.
    """
    n = len(x)
    if n < 3:
        return ""

    teile = [f"M {x[0]:.2f},{y[0]:.2f}"]

    for i in range(n):
        p0x, p0y = x[i - 1], y[i - 1]
        p1x, p1y = x[i], y[i]
        p2x, p2y = x[(i + 1) % n], y[(i + 1) % n]
        p3x, p3y = x[(i + 2) % n], y[(i + 2) % n]

        c1x, c1y = p1x + (p2x - p0x) / 6.0, p1y + (p2y - p0y) / 6.0
        c2x, c2y = p2x - (p3x - p1x) / 6.0, p2y - (p3y - p1y) / 6.0

        teile.append(f"C {c1x:.2f},{c1y:.2f} {c2x:.2f},{c2y:.2f} {p2x:.2f},{p2y:.2f}")

    teile.append("Z")
    return " ".join(teile)


def pfade_aus_maske(maske: np.ndarray, toleranz: float, min_flaeche: float):
    """Umrisse einer Flaeche als SVG-Pfaddaten.

    find_contours liefert Aussen- und Innenkonturen getrennt. Beide landen in
    einem Pfad, und fill-rule evenodd macht daraus Loecher statt Klumpen. Ohne
    das ist jede Brille eine Sonnenbrille.

    WARUM UEBERABTASTEN UND WEICHZEICHNEN

    Auf dem Pixelgitter hat jede schraege Kante Stufen. Wer die Kontur direkt
    dort sucht, bekommt genau diese Stufen als Pfad, und im SVG sieht man sie
    bei jeder Vergroesserung. Das Ergebnis ist dann schlechter als das PNG,
    weil dem PNG wenigstens die Kantenglaettung hilft.

    Also: die Maske vierfach feiner abtasten, leicht weichzeichnen, und erst
    dann die Kontur bei 0,5 suchen. Der Weichzeichner verschiebt die Kante um
    weniger als einen Originalpixel, glaettet die Treppe aber vollstaendig.
    Danach wird zurueckgerechnet.
    """
    # NUR DEN KASTEN DES TEILS ABTASTEN, nicht das ganze Bild.
    #
    # Vierfache Ueberabtastung eines 1280x960-Feldes sind 20 Millionen Werte,
    # und das je Ebene und je Teil. Der erste Versuch lief nach zwei Minuten
    # noch. Die meisten Teile sind aber klein: ein Auge, ein Punkt, ein Knopf.
    # Auf ihren Kasten beschnitten ist derselbe Lauf eine Sache von
    # Sekundenbruchteilen, und das Ergebnis ist identisch, weil ausserhalb des
    # Kastens ohnehin nichts steht.
    ys, xs = np.nonzero(maske)

    if len(ys) == 0:
        return "", 0, 0

    y0, y1 = int(ys.min()), int(ys.max()) + 1
    x0, x1 = int(xs.min()), int(xs.max()) + 1
    ausschnitt = maske[y0:y1, x0:x1]

    gepolstert = np.pad(ausschnitt.astype(float), 2, mode="constant", constant_values=0)

    fein = ndimage.zoom(gepolstert, UEBERABTASTUNG, order=1)
    fein = ndimage.gaussian_filter(fein, sigma=UEBERABTASTUNG * 0.5)

    stuecke = []
    verworfen = 0

    for kontur in measure.find_contours(fein, 0.5):
        # Toleranz in der feinen Aufloesung, damit sie dieselbe Bedeutung hat.
        vereinfacht = measure.approximate_polygon(kontur, tolerance=toleranz * UEBERABTASTUNG)

        if len(vereinfacht) < 4:
            verworfen += 1
            continue

        # Zurueck in die Koordinaten des Originals: Ueberabtastung heraus,
        # Polsterung heraus, Versatz des Ausschnitts wieder drauf.
        y = vereinfacht[:, 0] / UEBERABTASTUNG - 2 + y0
        x = vereinfacht[:, 1] / UEBERABTASTUNG - 2 + x0

        # approximate_polygon gibt den ersten Punkt am Ende noch einmal aus. Als
        # Stuetzstelle einer geschlossenen Kurve waere er ein Duplikat und
        # erzeugt dort eine Delle.
        if abs(x[0] - x[-1]) < 1e-6 and abs(y[0] - y[-1]) < 1e-6:
            x, y = x[:-1], y[:-1]

        if len(x) < 3:
            verworfen += 1
            continue

        flaeche = 0.5 * abs(np.dot(x, np.roll(y, 1)) - np.dot(y, np.roll(x, 1)))

        if flaeche < min_flaeche:
            verworfen += 1
            continue

        pfad = als_kurve(x, y)

        if pfad:
            stuecke.append(pfad)

    return " ".join(stuecke), len(stuecke), verworfen


def main() -> int:
    p = argparse.ArgumentParser(description="Flache Illustration zurueck in SVG")
    p.add_argument("quelle")
    p.add_argument("ziel")
    p.add_argument("--farben", type=int, default=14)
    p.add_argument("--toleranz", type=float, default=0.8)
    p.add_argument("--min-flaeche", type=float, default=12.0)
    p.add_argument("--grund", default="FFFFFF", help="Farbe, die Grund ist und weggelassen wird")
    args = p.parse_args()

    quelle = Path(args.quelle)
    if not quelle.exists():
        print(f"Nicht gefunden: {quelle}", file=sys.stderr)
        return 2

    original = Image.open(quelle)
    # Transparenz auf Weiss legen, sonst wird der Alphakanal zu schwarzen Flecken.
    if original.mode in ("RGBA", "LA", "P"):
        grund = Image.new("RGB", original.size, (255, 255, 255))
        original = original.convert("RGBA")
        grund.paste(original, mask=original.split()[-1])
        original = grund

    breite, hoehe = original.size
    indizes, palette = palette_holen(original, args.farben)
    grundfarbe = tuple(int(args.grund[i : i + 2], 16) for i in (0, 2, 4))

    ebenen = []
    for i, farbe in enumerate(palette):
        maske = indizes == i
        anteil = maske.sum()

        if anteil == 0:
            continue
        if tuple(int(c) for c in farbe) == grundfarbe:
            continue

        ebenen.append((anteil, i, farbe, maske))

    # Grosse Flaechen nach hinten. Bei flacher Illustration ist das fast immer
    # richtig: der weiche Fleck hinter der Figur ist die groesste Flaeche.
    ebenen.sort(key=lambda e: -e[0])

    teile = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {breite} {hoehe}" '
        f'width="{breite}" height="{hoehe}" fill-rule="evenodd">',
        f"<!-- Aus {quelle.name} zurueckgewonnen. Jede Gruppe ist eine Farbflaeche, "
        f"von hinten nach vorne. -->",
    ]
    gesamt_pfade = gesamt_verworfen = 0
    bericht = []

    for rang, (anteil, i, farbe, maske) in enumerate(ebenen):
        hexf = "#%02X%02X%02X" % tuple(int(c) for c in farbe)
        stuecke = []

        # Jedes zusammenhaengende Stueck bekommt einen eigenen Pfad mit id und
        # Kasten. Erst damit laesst sich ein einzelner Gegenstand anfassen.
        for k, (stueck, kasten, groesse) in enumerate(
            sorted(teile_aus_maske(maske), key=lambda t: -t[2])
        ):
            d, n, verworfen = pfade_aus_maske(stueck, args.toleranz, args.min_flaeche)
            gesamt_pfade += n
            gesamt_verworfen += verworfen

            if not d:
                continue

            x0, y0, x1, y1 = kasten
            stuecke.append(
                f'<path id="e{rang:02d}-t{k:02d}" d="{d}" '
                f'data-kasten="{x0},{y0},{x1 - x0},{y1 - y0}"/>'
            )

        if not stuecke:
            continue

        teile.append(f'<g id="ebene-{rang:02d}" fill="{hexf}">' + "".join(stuecke) + "</g>")
        bericht.append((hexf, 100 * anteil / (breite * hoehe), len(stuecke)))

    teile.append("</svg>")
    ziel = Path(args.ziel)
    ziel.write_text("\n".join(teile), encoding="utf-8")

    print(f"{quelle.name}  {breite}x{hoehe}")
    print(f"  {len(bericht)} Ebenen, {gesamt_pfade} Pfade, {gesamt_verworfen} zu klein verworfen")
    print(f"  {ziel.name}  {ziel.stat().st_size // 1024} KB gegen {quelle.stat().st_size // 1024} KB PNG")
    print()
    print(f"  {'Ebene':7s} {'Farbe':9s} {'Anteil':>7s} {'Teile':>6s}")
    for rang, (hexf, prozent, n) in enumerate(bericht):
        print(f"  {rang:<7d} {hexf:9s} {prozent:6.1f}% {n:6d}")

    return 0


if __name__ == "__main__":
    sys.exit(main())

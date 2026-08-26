#!/usr/bin/env python3
"""Einen Gegenstand aus einem vektorisierten SVG herausschneiden.

`vektorisieren.py` gibt jedem zusammenhaengenden Stueck ein `data-kasten` mit
seinen Massen. Damit laesst sich ein Gegenstand ueber seine Lage im Bild
herausziehen, ohne die Datei von Hand zu zerlegen.

    ./ausschneiden.py quelle.svg ziel.svg --bereich 700,60,140,150 [--rand 6]

Der Bereich ist x,y,breite,hoehe in den Koordinaten des Originals. Genommen
wird jedes Stueck, dessen Kasten ueberwiegend darin liegt, damit ein Umriss,
der knapp uebersteht, nicht verloren geht.

Wozu: eine Scroll-Seite baut Aussagen aus Bauteilen. Der kleine Roboter aus
einer Illustration und der Serverschrank aus einer anderen ergeben zusammen
eine dritte, die es vorher nicht gab, und alles bleibt im Hausstil, weil jedes
Teil aus dem Hausstil stammt.

Ohne --behalte-flaeche wird der viewBox auf den Bereich gesetzt, das Teil steht
also allein und faengt bei 0,0 an.
"""

import argparse
import re
import sys
from pathlib import Path
from xml.etree import ElementTree as ET

NS = "http://www.w3.org/2000/svg"


def ueberlappung(kasten, bereich) -> float:
    """Wieviel vom Teil liegt im Bereich, als Anteil seiner Flaeche."""
    kx, ky, kb, kh = kasten
    bx, by, bb, bh = bereich

    breite = max(0, min(kx + kb, bx + bb) - max(kx, bx))
    hoehe = max(0, min(ky + kh, by + bh) - max(ky, by))
    eigen = max(kb * kh, 1)

    return (breite * hoehe) / eigen


def main() -> int:
    p = argparse.ArgumentParser(description="Gegenstand aus vektorisiertem SVG schneiden")
    p.add_argument("quelle")
    p.add_argument("ziel")
    p.add_argument("--bereich", required=True, help="x,y,breite,hoehe im Original")
    p.add_argument("--anteil", type=float, default=0.6, help="ab welcher Ueberlappung ein Teil mitkommt")
    p.add_argument("--rand", type=float, default=0.0, help="Luft um den Bereich im Ziel-viewBox")
    p.add_argument("--behalte-flaeche", action="store_true", help="viewBox des Originals behalten")
    args = p.parse_args()

    quelle = Path(args.quelle)
    if not quelle.exists():
        print(f"Nicht gefunden: {quelle}", file=sys.stderr)
        return 2

    bereich = tuple(float(v) for v in args.bereich.split(","))
    if len(bereich) != 4:
        print("--bereich braucht x,y,breite,hoehe", file=sys.stderr)
        return 2

    ET.register_namespace("", NS)
    baum = ET.parse(quelle)
    wurzel = baum.getroot()

    gruppen = []
    genommen = verworfen = 0

    for g in wurzel.findall(f"{{{NS}}}g"):
        fuellung = g.get("fill", "#000")
        pfade = []

        for pfad in g.findall(f"{{{NS}}}path"):
            kasten_roh = pfad.get("data-kasten")

            if not kasten_roh:
                continue

            kasten = tuple(float(v) for v in kasten_roh.split(","))

            if ueberlappung(kasten, bereich) >= args.anteil:
                pfade.append(pfad)
                genommen += 1
            else:
                verworfen += 1

        if pfade:
            gruppen.append((g.get("id", ""), fuellung, pfade))

    if not gruppen:
        print("Nichts im Bereich gefunden. Andere Koordinaten?", file=sys.stderr)
        return 1

    x, y, b, h = bereich
    if args.behalte_flaeche:
        vb = wurzel.get("viewBox", f"0 0 {b} {h}")
        breite, hoehe = wurzel.get("width", b), wurzel.get("height", h)
        verschiebung = ""
    else:
        r = args.rand
        vb = f"{x - r} {y - r} {b + 2 * r} {h + 2 * r}"
        breite, hoehe = b + 2 * r, h + 2 * r
        verschiebung = ""

    teile = [
        f'<svg xmlns="{NS}" viewBox="{vb}" width="{breite}" height="{hoehe}" fill-rule="evenodd">',
        f"<!-- Aus {quelle.name}, Bereich {args.bereich}. {genommen} Teile genommen, {verworfen} gelassen. -->",
    ]

    for gid, fuellung, pfade in gruppen:
        inhalt = "".join(
            f'<path id="{pf.get("id","")}" d="{pf.get("d","")}" data-kasten="{pf.get("data-kasten","")}"/>'
            for pf in pfade
        )
        teile.append(f'<g id="{gid}" fill="{fuellung}">{verschiebung}{inhalt}</g>')

    teile.append("</svg>")
    ziel = Path(args.ziel)
    ziel.write_text("\n".join(teile), encoding="utf-8")

    print(f"{quelle.name} -> {ziel.name}")
    print(f"  {len(gruppen)} Ebenen, {genommen} Teile genommen, {verworfen} gelassen")
    print(f"  {ziel.stat().st_size // 1024} KB")

    return 0


if __name__ == "__main__":
    sys.exit(main())

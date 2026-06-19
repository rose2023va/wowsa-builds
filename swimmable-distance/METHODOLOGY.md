# WOWSA Swimmable Distance Methodology

Version: 1.0  
Library: searoute-py (see pinned version in requirements.txt)  
Units: kilometres (km), with miles provided as a secondary output  
Coordinate convention: inputs use lat,lon; internally GeoJSON order (lon,lat) is used throughout

---

## Why this tool exists

An AI giving a swim distance is not a defensible source of truth. The same question
can return a different answer on a different run with no way to audit how it was derived.

A method is defensible when:
- The same inputs always produce the same output
- The method is documented and published
- Anyone can rerun it and verify the result

This tool meets all three criteria.

---

## Layer 1 — Calculation: searoute-py

searoute-py computes the shortest sea route between two coordinate pairs by routing
through a maritime network that avoids land, rather than drawing a straight line.
It returns a GeoJSON LineString tracing the path plus the total distance in km.

**Known limitation:** The underlying network was built for commercial shipping between
ports, not for coastline-hugging swim routes. For point-to-point ocean crossings with
open water between them, the result will closely match the real swimmable path. For
routes that follow a coastline tightly, the network may be too coarse. Always compare
the output against the swimmer's own route before treating it as the official figure.

---

## Layer 2 — Visualization: Google Maps

Google Maps is used only for display. It has no maritime routing mode and cannot
compute a swimmable distance on its own. The coordinate list returned by searoute-py
is passed to the Google Static Maps API as an encoded polyline, producing an
embeddable satellite image of the computed route. The image is derived from
searoute-py's output — Google does not influence the distance figure in any way.

---

## Point-to-Point Swims

Run `calculate.py` with `--origin` and `--destination` (or `--gpx`).

```
python calculate.py --origin 35.6762,139.6503 --destination 51.5074,-0.1278
```

The tool returns the distance in km and miles, the full GeoJSON route, and optionally
a Google Static Maps URL if a `--maps-key` is provided.

---

## Circumnavigation Swims

`circumnavigation.py` handles routes that close back on themselves (e.g., swimming
around an island). It accepts a locked waypoint list, calls searoute-py on each
consecutive pair, and sums the legs.

### Waypoint process (required)

1. A human or AI proposes candidate waypoints placed in water around the landmass.
2. A human manually confirms each waypoint sits in water and traces the coastline sensibly.
3. The confirmed list is saved as a JSON file of `[lon, lat]` pairs.
4. That file is treated as fixed reference data for this route — the same status as
   the published start and finish coordinates. It must not be regenerated or modified
   between runs.

This process is required because AI cannot decide waypoints at calculation time.
A different prompt or a different run could place them differently, breaking reproducibility.

### Waypoint file format

```json
[
  [-74.0060, 40.7128],
  [-73.9857, 40.7484],
  [-73.9442, 40.7831]
]
```

Coordinates are `[longitude, latitude]` (GeoJSON order). The loop closes automatically
from the last waypoint back to the first — do not repeat the starting point.

```
python circumnavigation.py --waypoints my_island_waypoints.json
```

---

## Locked reference data

Any route that has been officially measured using this tool should record:

- searoute-py version used (check: `pip show searoute`)
- Origin and destination coordinates (or waypoint file for circumnavigations)
- Date of measurement
- Distance in km and miles
- GeoJSON output file

This allows any swimmer, organizer, or ratification body to rerun the calculation
and reproduce the same result independently.

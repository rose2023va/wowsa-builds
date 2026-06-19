#!/usr/bin/env python3
"""
WOWSA Circumnavigation Distance Calculator

For swims that loop back to the starting point (e.g., circumnavigating an island).
Calls searoute-py between consecutive waypoints and sums the legs into a total distance.

Waypoints must be human-confirmed and locked before running this script.
Use propose-waypoints.py to verify AI-proposed candidates first — see METHODOLOGY.md.

Usage:
  python3 circumnavigation.py --waypoints locked-manhattan-waypoints.json --maps-key YOUR_KEY
  python3 circumnavigation.py --waypoints locked-manhattan-waypoints.json --maps-key YOUR_KEY --output result.json

Waypoints file format ([longitude, latitude] — GeoJSON order):
  [
    [-74.0060, 40.7128],
    [-73.9857, 40.7484],
    ...
  ]
"""

import argparse
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

from calculate import calculate, km_to_miles


def circumnavigate(waypoints):
    """
    Calculate total swimmable distance around a closed loop of waypoints.
    The loop closes automatically from the last waypoint back to the first.

    waypoints: list of [lon, lat] pairs in order around the landmass.
    Returns a result dict including route_coordinates for map rendering.
    """
    closed = waypoints + [waypoints[0]]

    legs = []
    total_km = 0.0
    all_coordinates = []

    for i in range(len(closed) - 1):
        origin = closed[i]
        destination = closed[i + 1]

        print(f"  Computing leg {i + 1}/{len(closed) - 1}...")
        leg_result = calculate(origin, destination)
        leg_km = leg_result['distance_km']
        total_km += leg_km

        legs.append({
            "leg": i + 1,
            "origin": {"lat": origin[1], "lon": origin[0]},
            "destination": {"lat": destination[1], "lon": destination[0]},
            "distance_km": leg_km,
            "distance_miles": leg_result['distance_miles'],
        })

        coords = leg_result['geojson']['geometry']['coordinates']
        if all_coordinates:
            all_coordinates.extend(coords[1:])
        else:
            all_coordinates.extend(coords)

    total_km = round(total_km, 3)
    return {
        "total_distance_km": total_km,
        "total_distance_miles": km_to_miles(total_km),
        "legs": legs,
        "waypoints_used": [{"lat": w[1], "lon": w[0]} for w in waypoints],
        "route_coordinates": all_coordinates,
    }


def main():
    parser = argparse.ArgumentParser(
        description="WOWSA Circumnavigation Distance Calculator — sums searoute-py legs around a closed loop."
    )
    parser.add_argument('--waypoints', metavar='FILE', required=True,
                        help='Locked waypoints JSON file: [[lon, lat], ...]')
    parser.add_argument('--maps-key', metavar='KEY',
                        help='Google Maps JavaScript API key (optional — opens interactive map in browser)')
    parser.add_argument('--output', metavar='FILE',
                        help='Save full results to a JSON file')

    args = parser.parse_args()

    wp_path = Path(args.waypoints)
    if not wp_path.exists():
        print(f"Error: waypoints file not found: {wp_path}", file=sys.stderr)
        sys.exit(1)

    if "locked" not in wp_path.name:
        print(f"Warning: waypoints file name does not contain 'locked'.")
        print(f"  Run propose-waypoints.py first to verify waypoints before using them here.")
        confirm = input("  Continue anyway? (yes/no): ").strip().lower()
        if confirm != "yes":
            sys.exit(0)

    with open(wp_path) as f:
        waypoints = json.load(f)

    if not isinstance(waypoints, list) or len(waypoints) < 2:
        print("Error: waypoints file must be a JSON array of at least 2 [lon, lat] pairs.",
              file=sys.stderr)
        sys.exit(1)

    timestamp = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")

    print(f"\nCircumnavigation — {len(waypoints)} waypoints, {len(waypoints)} legs")
    print("=" * 50)

    result = circumnavigate(waypoints)

    print(f"\n{'=' * 50}")
    print(f"  WOWSA CIRCUMNAVIGATION — RATIFICATION RECORD")
    print(f"{'=' * 50}")
    print(f"  Date computed:  {timestamp}")
    print(f"  Waypoints file: {wp_path.name}")
    print(f"  Waypoints used: {len(waypoints)}")
    print(f"  Method:         searoute-py (maritime routing, avoids land)")
    print(f"{'=' * 50}")
    print(f"  TOTAL DISTANCE:  {result['total_distance_km']} km  /  {result['total_distance_miles']} miles")
    print(f"{'=' * 50}")
    print(f"\n  Leg breakdown:")
    for leg in result['legs']:
        print(f"    Leg {leg['leg']}: {leg['distance_km']} km  /  {leg['distance_miles']} miles")

    if args.maps_key:
        from map_output import circumnavigation_html
        circumnavigation_html(result, args.maps_key)

    if args.output:
        out_path = Path(args.output)
        record = {
            "computed_at": timestamp,
            "method": "searoute-py",
            "waypoints_file": wp_path.name,
            "total_distance_km": result["total_distance_km"],
            "total_distance_miles": result["total_distance_miles"],
            "legs": result["legs"],
            "waypoints_used": result["waypoints_used"],
        }
        with open(out_path, 'w') as f:
            json.dump(record, f, indent=2)
        print(f"\nRecord saved to: {out_path}")


if __name__ == '__main__':
    main()

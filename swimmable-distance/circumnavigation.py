#!/usr/bin/env python3
"""
WOWSA Circumnavigation Distance Calculator

For swims that loop back to the starting point (e.g., circumnavigating an island).
Calls searoute-py between consecutive waypoints and sums the legs into a total distance.

Waypoint requirement: waypoints must be manually confirmed by a human (each point
sitting in water and tracing the coastline sensibly) before being locked into a
JSON file. AI may propose candidate waypoints but cannot decide them at runtime —
see METHODOLOGY.md for the required process.

Usage:
  python circumnavigation.py --waypoints manhattan_waypoints.json
  python circumnavigation.py --waypoints manhattan_waypoints.json --maps-key YOUR_KEY --output result.json

Waypoints file format (lon, lat order — GeoJSON standard):
  [
    [-74.0060, 40.7128],
    [-73.9857, 40.7484],
    ...
  ]
"""

import argparse
import json
import sys
from pathlib import Path

from calculate import calculate, km_to_miles, build_maps_url


def circumnavigate(waypoints, maps_key=None):
    """
    Calculate total swimmable distance around a closed loop of waypoints.
    The loop closes automatically from the last waypoint back to the first.

    waypoints: list of [lon, lat] pairs in order around the landmass.
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
    output = {
        "total_distance_km": total_km,
        "total_distance_miles": km_to_miles(total_km),
        "legs": legs,
        "waypoints_used": [{"lat": w[1], "lon": w[0]} for w in waypoints],
    }

    if maps_key and all_coordinates:
        output["maps_url"] = build_maps_url(all_coordinates, maps_key)

    return output


def main():
    parser = argparse.ArgumentParser(
        description="WOWSA Circumnavigation Distance Calculator — sums searoute-py legs around a closed loop."
    )
    parser.add_argument('--waypoints', metavar='FILE', required=True,
                        help='JSON file with locked waypoints: [[lon, lat], ...]')
    parser.add_argument('--maps-key', metavar='KEY',
                        help='Google Maps Static API key (optional — generates a map image URL)')
    parser.add_argument('--output', metavar='FILE',
                        help='Save full results to a JSON file')

    args = parser.parse_args()

    wp_path = Path(args.waypoints)
    if not wp_path.exists():
        print(f"Error: waypoints file not found: {wp_path}", file=sys.stderr)
        sys.exit(1)

    with open(wp_path) as f:
        waypoints = json.load(f)

    if not isinstance(waypoints, list) or len(waypoints) < 2:
        print("Error: waypoints file must be a JSON array of at least 2 [lon, lat] pairs.", file=sys.stderr)
        sys.exit(1)

    print(f"\nCircumnavigation — {len(waypoints)} waypoints, {len(waypoints)} legs")
    print("=" * 50)

    result = circumnavigate(waypoints, maps_key=args.maps_key)

    print(f"\nTotal Swimmable Distance")
    print(f"========================")
    print(f"  {result['total_distance_km']} km  /  {result['total_distance_miles']} miles")
    print(f"\nLeg breakdown:")
    for leg in result['legs']:
        print(f"  Leg {leg['leg']}: {leg['distance_km']} km")

    if 'maps_url' in result:
        print(f"\nMap URL:")
        print(f"  {result['maps_url']}")

    if args.output:
        out_path = Path(args.output)
        with open(out_path, 'w') as f:
            json.dump(result, f, indent=2)
        print(f"\nResults saved to: {out_path}")


if __name__ == '__main__':
    main()

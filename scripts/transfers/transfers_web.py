#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
transfers_web.py — WEB ENTRY (called from Laravel).
Contract: JSON in via stdin  ->  JSON out via stdout (single line).

Different from build_transfers_aws.py (that one is for manual CLI testing):
  - this file prints NOTHING except one JSON object (so the server can parse it easily)
  - parameters come from the web form, not from constants at the top of the file

INPUT (stdin):
{
  "action": "preview" | "generate",
  "excel": "/abs/path/file.xlsx",
  "region": "ap-southeast-1",            # optional (falls back to .env)
  "api_key": "....",                     # optional (falls back to .env) — passed by server
  "cache_path": "/abs/walk_cache.json",  # optional (so regenerate doesn't hit the API again)
  "travel_mode": "Pedestrian",
  "max_transfer_m": 500,
  "default_type": 2,
  "max_walk_sec": 900,
  "detour_factor": 3.0,
  "auto_type3": false,
  "min_floor_sec": 30,
  "overrides": [ {"from_id":"..","to_id":"..","transfer_type":3,"min_transfer_time":null} ]
}

OUTPUT (stdout): {"ok":true, ...}  or  {"ok":false,"error":".."}
"""

import sys, os, json, math, re

MODES = {
    'KCIC_Stations': ('KCIC', 'POI'), 'MRT_Stations': ('MRT', 'POI'),
    'KRL_Station': ('KRL', 'POI IT'), 'LRT_Stations': ('LRT', 'POI IT'),
    'Free Shuttle Bus_Stations': ('FREE_SHUTTLE', 'POI IT'),
    'Paid Shuttle Bus_Stations': ('PAID_SHUTTLE', 'POI IT'),
}

# A transfer that enters one of these (rail/metro) gets the station-access buffer
# added on top of walking time (go down, buy ticket, find platform).
RAIL_METRO = {'KRL', 'MRT', 'LRT', 'KCIC'}


def fail(msg):
    sys.stdout.write(json.dumps({"ok": False, "error": str(msg)}))
    sys.exit(0)


def read_input():
    if "--input" in sys.argv:
        with open(sys.argv[sys.argv.index("--input") + 1], encoding="utf-8") as f:
            return json.load(f)
    data = sys.stdin.read()
    return json.loads(data) if data.strip() else {}


# ---------- extract stops ----------
def _pc(v):
    if v is None: return None
    s = str(v).strip().strip(',').strip()
    if s == '' or s.lower() == 'nan': return None
    return [p.strip() for p in s.split(',') if p.strip()] if ',' in s else [s]

def _latlng(a, b):
    la, ln = _pc(a), _pc(b)
    try:
        if la and len(la) == 2:   lat, lng = float(la[0]), float(la[1])  # lat+lng in one cell
        elif la and ln:           lat, lng = float(la[0]), float(ln[0])
        else: return None
    except ValueError:
        return None
    if -7.5 <= lat <= -5.5 and 106 <= lng <= 108: return (round(lat, 6), round(lng, 6))
    if -7.5 <= lng <= -5.5 and 106 <= lat <= 108: return (round(lng, 6), round(lat, 6))  # swapped
    return None

_clean = lambda v: re.sub(r'\s+', '', str(v)) if v is not None else ''

def extract_stops(path):
    import pandas as pd
    rows = []
    for sheet, (mode, poicol) in MODES.items():
        df = pd.read_excel(path, sheet_name=sheet)
        ent = df[df.get('Type', '').astype(str).str.upper() == 'ENTRANCE']
        for _, r in ent.iterrows():
            poi = _clean(r.get(poicol))
            c = _latlng(r.get('Lattitude'), r.get('Longtitude'))
            if not poi or poi in ('', 'nan', '-') or not c:
                continue
            rows.append(dict(stop_id=poi, mode=mode,
                             station=str(r.get('Geofence Name', '')).strip(),
                             code=_clean(r.get('Code')), lat=c[0], lng=c[1]))
    return pd.DataFrame(rows).drop_duplicates('stop_id').reset_index(drop=True)


def haversine(a, b):
    R = 6371000; p = math.pi / 180
    return 2 * R * math.asin(math.sqrt(
        math.sin((b[0]-a[0])*p/2)**2 +
        math.cos(a[0]*p)*math.cos(b[0]*p)*math.sin((b[1]-a[1])*p/2)**2))

def find_pairs(m, max_m):
    rr = m.to_dict('records'); pairs = []
    for i in range(len(rr)):
        for j in range(i+1, len(rr)):
            a, b = rr[i], rr[j]
            if a['code'] and a['code'] == b['code']:   # same physical station -> not a transfer
                continue
            d = haversine((a['lat'], a['lng']), (b['lat'], b['lng']))
            if d <= max_m:
                pairs.append(dict(a=a, b=b, dist_m=round(d)))
    pairs.sort(key=lambda x: x['dist_m'])
    return pairs


# ---------- AWS routing ----------
def aws_call(region, key, mode, a, b):
    import requests
    url = f"https://routes.geo.{region}.amazonaws.com/v2/routes?key={key}"
    body = {"Origin": [a['lng'], a['lat']], "Destination": [b['lng'], b['lat']],
            "TravelMode": mode, "LegGeometryFormat": "Simple",
            "InstructionsMeasurementSystem": "Metric", "TravelStepType": "TurnByTurn"}
    r = requests.post(url, json=body, timeout=30)
    r.raise_for_status()
    return r.json()

def parse_aws(j):
    routes = j.get("Routes") or []
    if not routes: return None
    s = routes[0].get("Summary", {})
    dur, dist = s.get("Duration"), s.get("Distance")
    if dur is None: return None
    return float(dur), float(dist or 0)


# ---------- geo-tools routing (Grab internal, same as VN team) ----------
def parse_curl(curl):
    """Extract (url, headers) from a browser 'Copy as cURL' string."""
    headers = {}
    for pat in (r"-H\s+'([^']+)'", r'-H\s+"([^"]+)"', r"--header\s+'([^']+)'"):
        for m in re.finditer(pat, curl):
            k, _, v = m.group(1).partition(':')
            if k.strip(): headers[k.strip()] = v.strip()
    cm = re.search(r"-b\s+'([^']+)'", curl) or re.search(r'--cookie\s+"([^"]+)"', curl)
    if cm: headers.setdefault('Cookie', cm.group(1))
    um = (re.search(r"curl\s+'(https?://[^']+)'", curl) or re.search(r'curl\s+"(https?://[^"]+)"', curl)
          or re.search("(https?://[^\\s\"']+route-engine[^\\s\"']*)", curl))
    url = um.group(1) if um else "https://geo-tools.grabtaxi.com/api/v3/public/routing/route-engine"
    headers = {k: v for k, v in headers.items()
               if not k.startswith(':') and k.lower() not in ('content-length', 'host', 'accept-encoding')}
    return url, headers

def geotools_call(url, headers, a, b):
    import requests
    payload = {"request": {"requestID": "geo-tools",
        "coordinates": [f"{a['lng']},{a['lat']}", f"{b['lng']},{b['lat']}"],   # geo-tools = lng,lat
        "geometries": "polyline6", "overview": "full", "annotations": "congestion",
        "alternatives": 3, "profile": "walking", "steps": True}}
    h = dict(headers); h.setdefault('Content-Type', 'application/json')
    r = requests.post(url, json=payload, headers=h, timeout=30)
    r.raise_for_status()
    return r.json()

def find_dur_dist(o):
    """Recursively pull (duration, distance) from any OSRM/geo-tools-shaped JSON."""
    if isinstance(o, dict):
        if isinstance(o.get('routes'), list) and o['routes']:
            g = find_dur_dist(o['routes'][0])
            if g: return g
        if isinstance(o.get('duration'), (int, float)) and 'distance' in o:
            return float(o['duration']), float(o['distance'])
        for v in o.values():
            g = find_dur_dist(v)
            if g: return g
    elif isinstance(o, list):
        for v in o:
            g = find_dur_dist(v)
            if g: return g
    return None


def classify(straight_m, walk_m, walk_sec, s):
    if walk_sec is None:
        return "review", "routing failed"
    if s["max_walk_sec"] and walk_sec > s["max_walk_sec"]:
        return "review", f"walk {int(walk_sec)}s > {s['max_walk_sec']}s (maybe not a transfer / type 3)"
    if walk_m > s["detour_factor"] * max(straight_m, 1) and (walk_m - straight_m) > 250:
        return "review", f"big detour: straight {straight_m}m vs walk {int(walk_m)}m (barrier? type 3?)"
    return "ok", ""


def load_env_key(region, key):
    if region and key: return region, key
    d = os.path.dirname(os.path.abspath(__file__))
    for _ in range(6):
        envp = os.path.join(d, ".env")
        if os.path.exists(envp):
            for line in open(envp, encoding="utf-8", errors="ignore"):
                line = line.strip()
                if line.startswith("#") or "=" not in line: continue
                k, _, v = line.partition("="); k = k.strip(); v = v.strip().strip('"').strip("'")
                if k == "AWS_REGION" and not region: region = v
                if k == "AWS_API_KEY" and not key: key = v
            break
        d = os.path.dirname(d)
    return (region or "ap-southeast-1"), key


def pairmeta(a, b, p):
    return {"from_id": a['stop_id'], "from_station": a['station'], "from_mode": a['mode'],
            "to_id": b['stop_id'], "to_station": b['station'], "to_mode": b['mode'],
            "straight_m": p['dist_m'], "cross_mode": a['mode'] != b['mode']}


def run():
    cfg = read_input()
    action = cfg.get("action", "preview")
    excel = cfg.get("excel")
    if not excel or not os.path.exists(excel):
        fail(f"Excel file not found: {excel}")

    s = {
        "travel_mode":   cfg.get("travel_mode", "Pedestrian"),
        "max_transfer_m": int(cfg.get("max_transfer_m", 500)),
        "default_type":  int(cfg.get("default_type", 2)),
        "max_walk_sec":  int(cfg.get("max_walk_sec", 900)),
        "detour_factor": float(cfg.get("detour_factor", 3.0)),
        "auto_type3":    bool(cfg.get("auto_type3", False)),
        "min_floor_sec": int(cfg.get("min_floor_sec", 30)),
        "access_buffer_sec": int(cfg.get("access_buffer_sec", 180)),
    }

    try:
        stops = extract_stops(excel)
    except Exception as e:
        fail(f"failed to read Excel (make sure pandas + openpyxl are installed): {e}")
    pairs = find_pairs(stops, s["max_transfer_m"])

    # ---- PREVIEW: no API ----
    if action == "preview":
        out = [pairmeta(p['a'], p['b'], p) for p in pairs]
        sys.stdout.write(json.dumps({"ok": True, "action": "preview",
            "stops": len(stops), "pair_count": len(pairs), "pairs": out, "settings": s}))
        return

    # ---- GENERATE ----
    try:
        import requests  # noqa
    except ImportError:
        fail("python 'requests' is not installed on the server (pip install requests)")

    provider = cfg.get("provider", "aws")
    region = key = gt_url = gt_headers = None
    if provider == "geotools":
        curl = (cfg.get("geotools_curl") or "").strip()
        if not curl:
            fail("geo-tools selected but auth is empty. In Geotools open DevTools > Network > route-engine > right-click > Copy as cURL, then paste it.")
        gt_url, gt_headers = parse_curl(curl)
        if not any(k.lower() in ('cookie', 'authorization') for k in gt_headers):
            fail("the pasted cURL has no Cookie/Authorization header — copy it from a route-engine request that actually succeeded.")
    else:
        region, key = load_env_key(cfg.get("region"), cfg.get("api_key"))
        if not key:
            fail("AWS_API_KEY is empty (pass it from the server or set it in .env)")

    cache_path = cfg.get("cache_path")
    cache = {}
    if cache_path and os.path.exists(cache_path):
        try: cache = json.load(open(cache_path))
        except Exception: cache = {}

    overrides = {}
    for o in (cfg.get("overrides") or []):
        fid = str(o.get("from_id", "")).strip(); tid = str(o.get("to_id", "")).strip()
        if not fid or not tid: continue
        try: tp = int(o.get("transfer_type"))
        except (TypeError, ValueError): continue
        mt = o.get("min_transfer_time")
        mt = int(mt) if str(mt).strip().isdigit() else None
        overrides["|".join(sorted([fid, tid]))] = {"type": tp, "min": mt, "from_id": fid, "to_id": tid}

    confirmed, review = [], []
    for p in pairs:
        a, b = p['a'], p['b']; pk = "|".join(sorted([a['stop_id'], b['stop_id']]))
        if pk not in cache:
            try:
                if provider == "geotools":
                    dd = find_dur_dist(geotools_call(gt_url, gt_headers, a, b))
                else:
                    dd = parse_aws(aws_call(region, key, s["travel_mode"], a, b))
                if not dd: raise ValueError("routing response had no duration/distance")
                cache[pk] = {"duration": dd[0], "distance": dd[1]}
            except Exception as e:
                rec = pairmeta(a, b, p); rec.update(walk_m=None, walk_sec=None, status="failed",
                                                    reason=f"routing failed: {e}")
                review.append(rec); continue
        c = cache[pk]; walk = max(s["min_floor_sec"], round(c["duration"]))
        # station-access buffer: added when the transfer enters a rail/metro station
        buf = s["access_buffer_sec"] if (a['mode'] in RAIL_METRO or b['mode'] in RAIL_METRO) else 0
        status, reason = classify(p['dist_m'], c["distance"], walk, s)   # classify on walking only
        rec = pairmeta(a, b, p)
        rec.update(walk_m=round(c["distance"]), walk_sec=walk, buffer_sec=buf, min_time=walk + buf)
        if status == "ok":
            rec["transfer_type"] = s["default_type"]; confirmed.append(rec)
        elif s["auto_type3"]:
            rec["transfer_type"] = 3; rec["reason"] = reason; confirmed.append(rec)
        else:
            rec["status"] = "review"; rec["reason"] = reason; review.append(rec)

    if cache_path:
        try: json.dump(cache, open(cache_path, "w"))
        except Exception: pass

    # apply overrides
    kk = lambda x, y: "|".join(sorted([x, y]))
    for r in confirmed:
        k = kk(r["from_id"], r["to_id"])
        if k in overrides:
            r["transfer_type"] = overrides[k]["type"]
            if overrides[k]["min"] is not None: r["min_time"] = overrides[k]["min"]
    emitted = {kk(r["from_id"], r["to_id"]) for r in confirmed}
    extra = [o for k, o in overrides.items() if k not in emitted]

    def row(fid, tid, tp, sec):
        mt = sec if (tp == 2 and sec is not None) else ""
        return f"{fid},{tid},{tp},{mt}"

    lines = ["from_stop_id,to_stop_id,transfer_type,min_transfer_time"]
    for r in confirmed:
        tp = r["transfer_type"]; sec = r.get("min_time") if tp == 2 else None
        lines.append(row(r["from_id"], r["to_id"], tp, sec))
        lines.append(row(r["to_id"], r["from_id"], tp, sec))
    for o in extra:
        tp = o["type"]; sec = o["min"] if tp == 2 else None
        lines.append(row(o["from_id"], o["to_id"], tp, sec))
        lines.append(row(o["to_id"], o["from_id"], tp, sec))
    txt = "\n".join(lines) + "\n"

    from collections import Counter
    counts = Counter(r["transfer_type"] for r in confirmed)
    for o in extra: counts[o["type"]] += 1

    sys.stdout.write(json.dumps({"ok": True, "action": "generate", "stops": len(stops),
        "rows": len(lines) - 1, "counts": {str(k): v for k, v in sorted(counts.items())},
        "confirmed": confirmed, "review": review, "extra": extra, "transfers_txt": txt}))


if __name__ == "__main__":
    try:
        run()
    except SystemExit:
        raise
    except Exception as e:
        fail(f"unexpected error: {e}")

#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build_transfers.py
Generate GTFS transfers.txt for JKT multimodal from the "Working Sheet" Excel,
using GrabMaps route-engine (walking profile) to get the REAL transfer walking time.

stop_id  = kolom POI IT (IT.xxx) pada baris ENTRANCE
transfer = pasangan stop berbeda yang jaraknya < MAX_TRANSFER_M

INPUT  : Excel di EXCEL_PATH (default: ~/Downloads/...)
OUTPUT : ditaruh di folder skrip ini (scripts/transfers/)

CARA PAKAI
  0) pip install openpyxl pandas requests
  1) Isi AUTH_HEADERS di bawah (buka DevTools>Network pas route-engine jalan,
     copy header Authorization / Cookie-nya ke situ).
  2) Cek lokal dulu (TANPA internet):   python3 build_transfers.py --dry
  3) Tes 1 panggilan API + auto-deteksi urutan koordinat:
                                        python3 build_transfers.py --test
  4) Jalan penuh:                       python3 build_transfers.py
        -> tulis transfers.txt + transfer_pairs_review.csv (enak buat dicek manusia)
        -> cache di walk_cache.json (kalau putus di tengah, tinggal jalanin lagi, lanjut)
"""

import os, sys, json, time, math, argparse, re
import pandas as pd

# ============================ CONFIG ============================
EXCEL_PATH    = os.path.expanduser("~/Downloads/Working Sheet - [Paratransit] JKT_Public Transport.xlsx")
OUT_DIR       = os.path.dirname(os.path.abspath(__file__))   # output di folder skrip ini (dalam project)
OUT_TRANSFERS = os.path.join(OUT_DIR, "transfers.txt")
OUT_REVIEW    = os.path.join(OUT_DIR, "transfer_pairs_review.csv")
CACHE_PATH    = os.path.join(OUT_DIR, "walk_cache.json")

API_URL = "https://geo-tools.grabtaxi.com/api/v3/public/routing/route-engine"

# --- AUTH: ISI INI. Tanpa ini API-nya nolak. ---
AUTH_HEADERS = {
    "Content-Type": "application/json",
    # "Authorization": "Bearer <token>",
    # "Cookie": "<cookie kamu>",
}

MAX_TRANSFER_M = 500      # ambang jarak garis-lurus utk dianggap kandidat transfer
MAX_WALK_SEC   = 900      # buang pasangan kalau jalan kakinya > ini (None = jangan buang)
TRANSFER_TYPE  = 2        # 2 = pakai min_transfer_time hasil ukur (yang diminta engineer)
MIN_FLOOR_SEC  = 30       # lantai bawah, jaga-jaga API balikin angka kekecilan
REQUEST_DELAY  = 0.3      # jeda antar panggilan (detik) biar sopan ke server
TIMEOUT        = 20
COORD_ORDER    = "auto"   # "auto" | "latlng" | "lnglat"

# sheet -> (mode, nama kolom POI)
MODES = {
    'KCIC_Stations':('KCIC','POI'), 'MRT_Stations':('MRT','POI'),
    'KRL_Station':('KRL','POI IT'), 'LRT_Stations':('LRT','POI IT'),
    'Free Shuttle Bus_Stations':('FREE_SHUTTLE','POI IT'),
    'Paid Shuttle Bus_Stations':('PAID_SHUTTLE','POI IT'),
}
# ===============================================================


# ---------- 1. ekstrak stop dari Excel ----------
def _pc(v):
    if v is None: return None
    s = str(v).strip().strip(',').strip()
    if s == '' or s.lower() == 'nan': return None
    return [p.strip() for p in s.split(',') if p.strip()] if ',' in s else [s]

def _latlng(a, b):
    la, ln = _pc(a), _pc(b)
    try:
        if la and len(la) == 2:   lat, lng = float(la[0]), float(la[1])  # lat+lng nyatu 1 sel
        elif la and ln:           lat, lng = float(la[0]), float(ln[0])
        else: return None
    except ValueError:
        return None
    if -7.5 <= lat <= -5.5 and 106 <= lng <= 108: return (round(lat, 6), round(lng, 6))
    if -7.5 <= lng <= -5.5 and 106 <= lat <= 108: return (round(lng, 6), round(lat, 6))  # ketuker
    return None

_clean = lambda v: re.sub(r'\s+', '', str(v)) if v is not None else ''

def extract_stops(path):
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
    m = pd.DataFrame(rows).drop_duplicates('stop_id').reset_index(drop=True)
    return m


# ---------- 2. cari pasangan transfer ----------
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
            if a['code'] and a['code'] == b['code']:   # stasiun fisik yang sama -> bukan transfer
                continue
            d = haversine((a['lat'], a['lng']), (b['lat'], b['lng']))
            if d <= max_m:
                pairs.append(dict(a=a, b=b, dist_m=round(d)))
    pairs.sort(key=lambda x: x['dist_m'])
    return pairs


# ---------- 3. panggil route-engine ----------
def _coord_str(lat, lng, order):
    return f"{lat},{lng}" if order == "latlng" else f"{lng},{lat}"

def call_api(a, b, order):
    import requests
    payload = {"request": {
        "requestID": "geo-tools",
        "coordinates": [_coord_str(a[0], a[1], order), _coord_str(b[0], b[1], order)],
        "geometries": "polyline6", "overview": "full", "annotations": "congestion",
        "alternatives": 3, "profile": "walking", "steps": True}}
    resp = requests.post(API_URL, headers=AUTH_HEADERS, json=payload, timeout=TIMEOUT)
    resp.raise_for_status()
    return resp.json()

def find_dur_dist(obj):
    """Cari (duration, distance) pertama dari respons, tahan banting thd bentuk JSON apa pun."""
    if isinstance(obj, dict):
        if isinstance(obj.get("routes"), list) and obj["routes"]:
            got = find_dur_dist(obj["routes"][0])
            if got: return got
        if isinstance(obj.get("duration"), (int, float)) and "distance" in obj:
            return float(obj["duration"]), float(obj["distance"])
        for v in obj.values():
            got = find_dur_dist(v)
            if got: return got
    elif isinstance(obj, list):
        for v in obj:
            got = find_dur_dist(v)
            if got: return got
    return None

def detect_order(pairs):
    """Coba kedua urutan koordinat di 1 pasangan; pilih yg jarak API-nya paling masuk akal vs garis-lurus."""
    s = pairs[0]; a, b, hav = s['a'], s['b'], s['dist_m']
    print(f"[deteksi urutan koordinat] pakai {a['station']} <-> {b['station']} (garis lurus {hav}m)")
    best = None
    for order in ("latlng", "lnglat"):
        try:
            dd = find_dur_dist(call_api((a['lat'], a['lng']), (b['lat'], b['lng']), order))
            if not dd:
                print(f"  - {order}: respons tanpa duration/distance"); continue
            dur, dist = dd; ratio = dist / max(hav, 1)
            print(f"  - {order}: jarak_API={dist:.0f}m  durasi={dur:.0f}s  (ratio vs garis-lurus {ratio:.2f})")
            if 0.7 <= ratio <= 6:
                score = abs(math.log(ratio))
                if best is None or score < best[1]:
                    best = (order, score)
        except Exception as e:
            print(f"  - {order}: ERROR {e}")
        time.sleep(REQUEST_DELAY)
    if best:
        print(f"[deteksi] -> pakai urutan: {best[0]}")
        return best[0]
    print("[deteksi] GAGAL nentuin urutan. Set COORD_ORDER manual ('latlng'/'lnglat').")
    return None


# ---------- 4. cache ----------
def load_cache():
    if os.path.exists(CACHE_PATH):
        try: return json.load(open(CACHE_PATH))
        except Exception: return {}
    return {}

def save_cache(c):
    json.dump(c, open(CACHE_PATH, 'w'))

def pair_key(a, b):
    return "|".join(sorted([a['stop_id'], b['stop_id']]))


# ---------- main ----------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry',   action='store_true', help='ekstrak+pasangan+contoh payload, TANPA internet')
    ap.add_argument('--test',  action='store_true', help='panggil API utk 1 pasangan, print respons mentah')
    ap.add_argument('--limit', type=int, default=0,  help='batasi N pasangan (buat tes parsial)')
    ap.add_argument('--order', default=COORD_ORDER,  help="latlng | lnglat | auto")
    ap.add_argument('--max-m', type=int, default=MAX_TRANSFER_M)
    args = ap.parse_args()

    print(f"Baca: {EXCEL_PATH}")
    stops = extract_stops(EXCEL_PATH)
    print(f"Stop unik (stop_id=POI IT): {len(stops)}")
    pairs = find_pairs(stops, args.max_m)
    if args.limit: pairs = pairs[:args.limit]
    print(f"Kandidat transfer (<{args.max_m}m): {len(pairs)} pasang\n")
    for p in pairs:
        x = '  (lintas-moda)' if p['a']['mode'] != p['b']['mode'] else ''
        print(f"  {p['dist_m']:4}m  {p['a']['station']} [{p['a']['mode']}]  <->  {p['b']['station']} [{p['b']['mode']}]{x}")

    # ---- DRY: cuma tunjukin payload, gak nembak API ----
    if args.dry:
        print("\n--- contoh payload yang AKAN dikirim (pasangan pertama, urutan latlng) ---")
        p = pairs[0]
        payload = {"request": {"requestID": "geo-tools",
            "coordinates": [_coord_str(p['a']['lat'], p['a']['lng'], 'latlng'),
                            _coord_str(p['b']['lat'], p['b']['lng'], 'latlng')],
            "geometries": "polyline6", "overview": "full", "annotations": "congestion",
            "alternatives": 3, "profile": "walking", "steps": True}}
        print(json.dumps(payload, indent=2))
        print("\nDRY selesai. Isi AUTH lalu jalankan: python3 build_transfers.py --test")
        return

    # ---- mulai sini butuh requests + auth ----
    try:
        import requests  # noqa
    except ImportError:
        sys.exit("Butuh 'requests'.  pip install requests")
    if not any(k.lower() in ('authorization', 'cookie') for k in AUTH_HEADERS):
        print("\n⚠️  AUTH_HEADERS belum diisi (Authorization/Cookie). API kemungkinan nolak.\n")

    order = args.order
    if order == 'auto':
        order = detect_order(pairs) or 'latlng'

    # ---- TEST: 1 pasangan, dump mentah ----
    if args.test:
        p = pairs[0]
        print(f"\n[TEST] {p['a']['station']} <-> {p['b']['station']} (order={order})")
        raw = call_api((p['a']['lat'], p['a']['lng']), (p['b']['lat'], p['b']['lng']), order)
        print(json.dumps(raw, indent=2)[:2000])
        dd = find_dur_dist(raw)
        print(f"\n-> parsed: duration={dd[0] if dd else '??'}s  distance={dd[1] if dd else '??'}m")
        return

    # ---- FULL: loop semua pasangan, isi transfers.txt ----
    cache = load_cache(); done = 0; failed = []
    review = []
    for i, p in enumerate(pairs, 1):
        a, b = p['a'], p['b']; key = pair_key(a, b)
        if key not in cache:
            try:
                dd = find_dur_dist(call_api((a['lat'], a['lng']), (b['lat'], b['lng']), order))
                if not dd: raise ValueError("respons tanpa duration/distance")
                cache[key] = {"duration": dd[0], "distance": dd[1], "order": order}
                save_cache(cache)
                time.sleep(REQUEST_DELAY)
            except Exception as e:
                failed.append((a['station'], b['station'], str(e)))
                print(f"  [{i}/{len(pairs)}] GAGAL {a['station']}<->{b['station']}: {e}")
                continue
        c = cache[key]; sec = max(MIN_FLOOR_SEC, round(c['duration']))
        if MAX_WALK_SEC and sec > MAX_WALK_SEC:
            print(f"  [skip] {a['station']}<->{b['station']} jalan {sec}s > {MAX_WALK_SEC}s (mgkn kepisah jalan/sungai)")
            continue
        review.append(dict(from_station=a['station'], from_mode=a['mode'], from_id=a['stop_id'],
                           to_station=b['station'], to_mode=b['mode'], to_id=b['stop_id'],
                           straight_m=p['dist_m'], walk_m=round(c['distance']), walk_sec=sec))
        done += 1
        print(f"  [{i}/{len(pairs)}] {a['station']}<->{b['station']}: jalan {round(c['distance'])}m, {sec}s")

    # tulis transfers.txt (2 baris per pasangan: A->B dan B->A)
    with open(OUT_TRANSFERS, 'w', newline='') as f:
        f.write("from_stop_id,to_stop_id,transfer_type,min_transfer_time\n")
        for r in review:
            f.write(f"{r['from_id']},{r['to_id']},{TRANSFER_TYPE},{r['walk_sec']}\n")
            f.write(f"{r['to_id']},{r['from_id']},{TRANSFER_TYPE},{r['walk_sec']}\n")
    pd.DataFrame(review).to_csv(OUT_REVIEW, index=False)

    print(f"\n✅ Selesai. {done} pasang -> {done*2} baris")
    print(f"   transfers.txt : {OUT_TRANSFERS}")
    print(f"   review (cek)  : {OUT_REVIEW}")
    if failed:
        print(f"\n⚠️  {len(failed)} pasangan gagal (jalankan ulang utk retry, yg sukses udah di-cache):")
        for fa, fb, er in failed: print(f"     - {fa} <-> {fb}: {er}")

if __name__ == "__main__":
    main()

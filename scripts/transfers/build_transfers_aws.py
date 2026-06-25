#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build_transfers_aws.py
Versi AWS dari build_transfers.py — pakai AWS Location Service Routes v2 (GrabMaps),
BUKAN endpoint internal geo-tools.grabtaxi.com.

Kenapa beda file: endpoint, payload, urutan koordinat, & auth-nya beda total.
  - Endpoint : https://routes.geo.{region}.amazonaws.com/v2/routes?key=...
  - Auth     : API key dari .env app ini (AWS_API_KEY) — diambil OTOMATIS
  - Koordinat: AWS selalu [lng, lat]  (jadi nggak perlu auto-deteksi urutan)
  - Walking  : TravelMode = "Pedestrian"
  - Respons  : Routes[0].Summary -> Distance (meter) + Duration (detik)

INPUT  : Excel di EXCEL_PATH (default: ~/Downloads/...)
OUTPUT : ditaruh di folder skrip ini (transfers.txt, transfer_pairs_review.csv, walk_cache_aws.json)

CARA PAKAI
  0) pip install openpyxl pandas requests
  1) Cek lokal dulu (TANPA internet):   python3 build_transfers_aws.py --dry
  2) Tes 1 panggilan AWS:               python3 build_transfers_aws.py --test
  3) Jalan penuh:                       python3 build_transfers_aws.py
        -> tulis transfers.txt + transfer_pairs_review.csv
        -> cache di walk_cache_aws.json (putus di tengah? jalanin lagi, lanjut)
"""

import os, sys, json, time, math, argparse, re
import pandas as pd

# ============================ CONFIG ============================
EXCEL_PATH    = os.path.expanduser("~/Downloads/Working Sheet - [Paratransit] JKT_Public Transport.xlsx")
OUT_DIR       = os.path.dirname(os.path.abspath(__file__))
OUT_TRANSFERS = os.path.join(OUT_DIR, "transfers.txt")
OUT_CONFIRMED = os.path.join(OUT_DIR, "transfer_confirmed.csv")   # pasangan type 2 (enak dibaca manusia)
OUT_REVIEW    = os.path.join(OUT_DIR, "transfer_review.csv")      # ditahan, butuh keputusan manusia
OVERRIDES     = os.path.join(OUT_DIR, "transfer_overrides.csv")   # INPUT manual: type 0/1/3
CACHE_PATH    = os.path.join(OUT_DIR, "walk_cache_aws.json")

# AWS_REGION & AWS_API_KEY diambil otomatis dari .env app (lihat load_env()).
# Bisa di-override: set env var AWS_REGION / AWS_API_KEY sebelum jalan.
TRAVEL_MODE    = "Pedestrian"   # jalan kaki di AWS Routes v2
MAX_TRANSFER_M = 500            # ambang jarak garis-lurus utk dianggap kandidat transfer
DEFAULT_TYPE   = 2             # tipe utk pasangan yg waktunya kita UKUR (lihat catatan transfer_type)
MAX_WALK_SEC   = 900           # > ini -> TIDAK ditulis, ditahan utk dicek (mungkin type 3)
DETOUR_FACTOR  = 3.0           # jalan kaki > 3x garis-lurus -> ditahan (mungkin kepisah barrier -> type 3)
AUTO_TYPE3     = False         # True (atau flag --auto-type3): pasangan janggal LANGSUNG jadi type 3, tanpa ditahan
MIN_FLOOR_SEC  = 30
REQUEST_DELAY  = 0.2
TIMEOUT        = 30

# --- transfer_type (GTFS) ---
#   0 = recommended transfer   -> engine pakai default 90 detik
#   1 = timed transfer         -> engine pakai default 90 detik
#   2 = pakai min_transfer_time hasil UKUR   <-- DEFAULT kita (paling bernilai)
#   3 = transfer TIDAK mungkin (blokir; mis. kepisah tol/sungai/pagar)
# Yg waktunya kita ukur dari AWS = type 2 otomatis.
# type 0/1/3 = keputusan manusia -> isi via transfer_overrides.csv
#   (bikin templatenya: python3 build_transfers_aws.py --init-overrides)

MODES = {
    'KCIC_Stations':('KCIC','POI'), 'MRT_Stations':('MRT','POI'),
    'KRL_Station':('KRL','POI IT'), 'LRT_Stations':('LRT','POI IT'),
    'Free Shuttle Bus_Stations':('FREE_SHUTTLE','POI IT'),
    'Paid Shuttle Bus_Stations':('PAID_SHUTTLE','POI IT'),
}
# ===============================================================


# ---------- 0. ambil kredensial AWS dari .env app ----------
def load_env():
    region = os.environ.get("AWS_REGION")
    key    = os.environ.get("AWS_API_KEY")
    d = os.path.dirname(os.path.abspath(__file__))
    for _ in range(6):                       # naik cari .env sampai root project
        envp = os.path.join(d, ".env")
        if os.path.exists(envp):
            for line in open(envp, encoding="utf-8", errors="ignore"):
                line = line.strip()
                if line.startswith("#") or "=" not in line:
                    continue
                k, _, v = line.partition("=")
                k = k.strip(); v = v.strip().strip('"').strip("'")
                if k == "AWS_REGION" and not region: region = v
                if k == "AWS_API_KEY" and not key:   key = v
            break
        d = os.path.dirname(d)
    return (region or "ap-southeast-1"), key

REGION, API_KEY = load_env()


# ---------- 1. ekstrak stop dari Excel ----------
def _pc(v):
    if v is None: return None
    s = str(v).strip().strip(',').strip()
    if s == '' or s.lower() == 'nan': return None
    return [p.strip() for p in s.split(',') if p.strip()] if ',' in s else [s]

def _latlng(a, b):
    la, ln = _pc(a), _pc(b)
    try:
        if la and len(la) == 2:   lat, lng = float(la[0]), float(la[1])
        elif la and ln:           lat, lng = float(la[0]), float(ln[0])
        else: return None
    except ValueError:
        return None
    if -7.5 <= lat <= -5.5 and 106 <= lng <= 108: return (round(lat, 6), round(lng, 6))
    if -7.5 <= lng <= -5.5 and 106 <= lat <= 108: return (round(lng, 6), round(lat, 6))
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
    return pd.DataFrame(rows).drop_duplicates('stop_id').reset_index(drop=True)


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
            if a['code'] and a['code'] == b['code']:
                continue
            d = haversine((a['lat'], a['lng']), (b['lat'], b['lng']))
            if d <= max_m:
                pairs.append(dict(a=a, b=b, dist_m=round(d)))
    pairs.sort(key=lambda x: x['dist_m'])
    return pairs


# ---------- 3. panggil AWS Location Routes v2 ----------
def aws_url():
    return f"https://routes.geo.{REGION}.amazonaws.com/v2/routes?key={API_KEY}"

def aws_body(a, b):   # a,b = stop dict -> AWS minta [lng,lat]
    return {
        "Origin":      [a['lng'], a['lat']],
        "Destination": [b['lng'], b['lat']],
        "TravelMode":  TRAVEL_MODE,
        "LegGeometryFormat": "Simple",
        "InstructionsMeasurementSystem": "Metric",
        "TravelStepType": "TurnByTurn",
    }

def call_aws(a, b):
    import requests
    resp = requests.post(aws_url(), json=aws_body(a, b), timeout=TIMEOUT)
    resp.raise_for_status()
    return resp.json()

def parse_aws(j):
    """Routes[0].Summary -> (duration_sec, distance_m)."""
    routes = j.get("Routes") or []
    if not routes:
        return None
    s = routes[0].get("Summary", {})
    dur, dist = s.get("Duration"), s.get("Distance")
    if dur is None:
        return None
    return float(dur), float(dist or 0)


# ---------- 4. cache ----------
def load_cache():
    if os.path.exists(CACHE_PATH):
        try: return json.load(open(CACHE_PATH))
        except Exception: return {}
    return {}

def save_cache(c): json.dump(c, open(CACHE_PATH, 'w'))
def pair_key(a, b): return "|".join(sorted([a['stop_id'], b['stop_id']]))


# ---------- 5. tipe transfer ----------
def classify(straight_m, walk_m, walk_sec):
    """Tentukan apakah pasangan layak jadi type 2 otomatis, atau ditahan utk dicek manusia."""
    if walk_sec is None:
        return "review", "routing gagal"
    if MAX_WALK_SEC and walk_sec > MAX_WALK_SEC:
        return "review", f"jalan {int(walk_sec)}s > {MAX_WALK_SEC}s (mungkin bukan transfer / type 3)"
    if walk_m > DETOUR_FACTOR * max(straight_m, 1) and (walk_m - straight_m) > 250:
        return "review", f"muter jauh: lurus {straight_m}m vs jalan {int(walk_m)}m (kepisah barrier? type 3?)"
    return "ok", ""

def fmt_row(fid, tid, ttype, sec):
    """min_transfer_time hanya diisi utk type 2; type 0/1/3 dikosongin (sesuai spec)."""
    mt = sec if (ttype == 2 and sec is not None) else ""
    return f"{fid},{tid},{ttype},{mt}"

def load_overrides():
    """transfer_overrides.csv -> dict[pairkey] = {type,min,note,from_id,to_id}.
    Kolom: from_id,to_id,transfer_type[,min_transfer_time][,note]. Baris '#'=komentar."""
    import csv
    ov = {}
    if not os.path.exists(OVERRIDES):
        return ov
    with open(OVERRIDES, encoding="utf-8") as f:
        lines = [ln for ln in f if not ln.lstrip().startswith("#")]
    for row in csv.DictReader(lines):
        fid = (row.get("from_id") or "").strip()
        tid = (row.get("to_id") or "").strip()
        if not fid or not tid or fid.startswith("#"):
            continue
        try:
            tp = int(str(row.get("transfer_type")).strip())
        except (TypeError, ValueError):
            continue
        mt = str(row.get("min_transfer_time") or "").strip()
        mt = int(mt) if mt.isdigit() else None
        ov["|".join(sorted([fid, tid]))] = {"type": tp, "min": mt,
            "note": (row.get("note") or "").strip(), "from_id": fid, "to_id": tid}
    return ov

OVERRIDES_TEMPLATE = (
    "# transfer_overrides.csv — set transfer_type manual (yg API nggak bisa nentuin).\n"
    "# transfer_type: 0/1 = transfer pakai default 90s | 2 = pakai min_transfer_time | 3 = TIDAK mungkin (blokir)\n"
    "# min_transfer_time (detik) HANYA utk type 2; type 0/1/3 kosongin aja.\n"
    "# Hapus '#' di baris contoh utk mengaktifkan, atau tambah baris sendiri.\n"
    "from_id,to_id,transfer_type,min_transfer_time,note\n"
    "# IT.contohA,IT.contohB,3,,dua stop deket tapi kepisah tol -> blokir transfer\n"
    "# IT.contohC,IT.contohD,2,210,override waktu manual hasil survey lapangan\n"
)


# ---------- main ----------
def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--dry',   action='store_true', help='ekstrak+pasangan+contoh body, TANPA internet')
    ap.add_argument('--test',  action='store_true', help='panggil AWS utk 1 pasangan, print respons mentah')
    ap.add_argument('--limit', type=int, default=0)
    ap.add_argument('--max-m', type=int, default=MAX_TRANSFER_M)
    ap.add_argument('--init-overrides', action='store_true', help='bikin template transfer_overrides.csv lalu keluar')
    ap.add_argument('--auto-type3', action='store_true', help='pasangan janggal langsung ditulis type 3 (blokir), tanpa ditahan')
    args = ap.parse_args()

    if args.init_overrides:
        if os.path.exists(OVERRIDES):
            print(f"Sudah ada (nggak ditimpa): {OVERRIDES}")
        else:
            open(OVERRIDES, "w", encoding="utf-8").write(OVERRIDES_TEMPLATE)
            print(f"Template dibuat: {OVERRIDES}\nEdit file itu utk set type 0/1/3, lalu jalankan lagi tanpa --init-overrides.")
        return

    print(f"AWS region : {REGION}")
    print(f"AWS api_key: {'(ketemu di .env)' if API_KEY else 'TIDAK KETEMU — set AWS_API_KEY'}")
    print(f"Baca       : {EXCEL_PATH}")
    stops = extract_stops(EXCEL_PATH)
    print(f"Stop unik (stop_id=POI IT): {len(stops)}")
    pairs = find_pairs(stops, args.max_m)
    if args.limit: pairs = pairs[:args.limit]
    print(f"Kandidat transfer (<{args.max_m}m): {len(pairs)} pasang\n")
    for p in pairs:
        x = '  (lintas-moda)' if p['a']['mode'] != p['b']['mode'] else ''
        print(f"  {p['dist_m']:4}m  {p['a']['station']} [{p['a']['mode']}]  <->  {p['b']['station']} [{p['b']['mode']}]{x}")

    # ---- DRY ----
    if args.dry:
        p = pairs[0]
        print(f"\n--- URL (key disensor) ---\nhttps://routes.geo.{REGION}.amazonaws.com/v2/routes?key=***")
        print("--- contoh body (pasangan pertama) ---")
        print(json.dumps(aws_body(p['a'], p['b']), indent=2))
        print("\nDRY selesai. Lanjut: python3 build_transfers_aws.py --test")
        return

    try:
        import requests  # noqa
    except ImportError:
        sys.exit("Butuh 'requests'.  pip install requests")
    if not API_KEY:
        sys.exit("AWS_API_KEY tidak ketemu di .env. Set dulu atau export AWS_API_KEY=...")

    # ---- TEST ----
    if args.test:
        p = pairs[0]
        print(f"\n[TEST] {p['a']['station']} <-> {p['b']['station']}")
        raw = call_aws(p['a'], p['b'])
        print(json.dumps(raw, indent=2)[:2000])
        dd = parse_aws(raw)
        print(f"\n-> parsed: duration={dd[0] if dd else '??'}s  distance={dd[1] if dd else '??'}m")
        return

    # ---- FULL ----
    cache = load_cache(); confirmed = []; review = []
    auto3 = AUTO_TYPE3 or args.auto_type3
    for i, p in enumerate(pairs, 1):
        a, b = p['a'], p['b']; key = pair_key(a, b)
        if key not in cache:
            try:
                dd = parse_aws(call_aws(a, b))
                if not dd: raise ValueError("respons tanpa Routes/Summary.Duration")
                cache[key] = {"duration": dd[0], "distance": dd[1]}
                save_cache(cache); time.sleep(REQUEST_DELAY)
            except Exception as e:
                review.append(dict(from_station=a['station'], from_mode=a['mode'], from_id=a['stop_id'],
                                   to_station=b['station'], to_mode=b['mode'], to_id=b['stop_id'],
                                   straight_m=p['dist_m'], walk_m='', walk_sec='', reason=f"routing gagal: {e}"))
                print(f"  [{i}/{len(pairs)}] GAGAL {a['station']}<->{b['station']}: {e}")
                continue
        c = cache[key]; sec = max(MIN_FLOOR_SEC, round(c['duration']))
        status, reason = classify(p['dist_m'], c['distance'], sec)
        rec = dict(from_station=a['station'], from_mode=a['mode'], from_id=a['stop_id'],
                   to_station=b['station'], to_mode=b['mode'], to_id=b['stop_id'],
                   straight_m=p['dist_m'], walk_m=round(c['distance']), walk_sec=sec)
        if status == 'ok':
            rec['transfer_type'] = DEFAULT_TYPE
            confirmed.append(rec)
            print(f"  [{i}/{len(pairs)}] {a['station']}<->{b['station']}: {round(c['distance'])}m, {sec}s  -> type {DEFAULT_TYPE}")
        elif auto3:
            rec['transfer_type'] = 3      # janggal -> blokir otomatis
            rec['reason'] = reason
            confirmed.append(rec)
            print(f"  [{i}/{len(pairs)}] {a['station']}<->{b['station']}: -> type 3 (blokir) — {reason}")
        else:
            rec['reason'] = reason
            review.append(rec)
            print(f"  [{i}/{len(pairs)}] {a['station']}<->{b['station']}: DITAHAN — {reason}")

    # ---- override manual (type 0/1/3) dari transfer_overrides.csv ----
    ov = load_overrides(); applied = 0
    kk = lambda x, y: "|".join(sorted([x, y]))
    for r in confirmed:
        k = kk(r['from_id'], r['to_id'])
        if k in ov:
            r['transfer_type'] = ov[k]['type']
            if ov[k]['min'] is not None: r['walk_sec'] = ov[k]['min']
            applied += 1
    emitted = {kk(r['from_id'], r['to_id']) for r in confirmed}
    extra = [o for k, o in ov.items() if k not in emitted]   # override yg menambah pasangan baru

    # ---- tulis transfers.txt (2 arah per pasangan; min_transfer_time cuma utk type 2) ----
    rows = []
    for r in confirmed:
        tp = r['transfer_type']; sec = r['walk_sec'] if tp == 2 else None
        rows.append(fmt_row(r['from_id'], r['to_id'], tp, sec))
        rows.append(fmt_row(r['to_id'], r['from_id'], tp, sec))
    for o in extra:
        tp = o['type']; sec = o['min'] if tp == 2 else None
        rows.append(fmt_row(o['from_id'], o['to_id'], tp, sec))
        rows.append(fmt_row(o['to_id'], o['from_id'], tp, sec))
    with open(OUT_TRANSFERS, 'w', newline='') as f:
        f.write("from_stop_id,to_stop_id,transfer_type,min_transfer_time\n")
        f.write("\n".join(rows) + ("\n" if rows else ""))
    if confirmed: pd.DataFrame(confirmed).to_csv(OUT_CONFIRMED, index=False)
    if review:    pd.DataFrame(review).to_csv(OUT_REVIEW, index=False)

    # ---- ringkasan per type ----
    from collections import Counter
    tc = Counter(r['transfer_type'] for r in confirmed)
    for o in extra: tc[o['type']] += 1
    print(f"\n✅ Selesai. transfers.txt: {len(rows)} baris  -> {OUT_TRANSFERS}")
    if tc:        print("   per type: " + " | ".join(f"type {t} = {n} pasang" for t, n in sorted(tc.items())))
    if confirmed: print(f"   confirmed (cek): {OUT_CONFIRMED}")
    if applied:   print(f"   override manual diterapkan: {applied} pasang")
    if extra:     print(f"   pasangan dari override (di luar kandidat): {len(extra)}")
    if review:
        print(f"   ⚠️ DITAHAN {len(review)} pasang utk dicek manusia -> {OUT_REVIEW}")
        print(f"      kalau perlu set type-nya (mis. 3=blokir), isi transfer_overrides.csv")
        print(f"      template: python3 build_transfers_aws.py --init-overrides")

if __name__ == "__main__":
    main()

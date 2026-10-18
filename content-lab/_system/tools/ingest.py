#!/usr/bin/env python3
"""Vatan content-lab ingest: probe videos/audio, detect cuts, extract keyframes,
contact sheet, colors, motion, audio tempo. Output -> _system/cache/<slug>/
Usage: python3 ingest.py [inbox|music|all]
"""
import json, os, re, subprocess, sys, hashlib, wave
import array, math, random

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CACHE = os.path.join(ROOT, "_system", "cache")
VID = (".mp4", ".mov", ".m4v", ".webm", ".mkv")
AUD = (".mp3", ".wav", ".m4a", ".aac", ".ogg", ".flac")

def sh(cmd):
    return subprocess.run(cmd, capture_output=True, text=True)

def slugify(path):
    base = os.path.splitext(os.path.basename(path))[0]
    s = re.sub(r"[^\w؀-ۿ-]+", "-", base).strip("-")[:40] or "item"
    h = hashlib.md5(path.encode()).hexdigest()[:6]
    return f"{s}-{h}"

def probe(p):
    r = sh(["ffprobe", "-v", "quiet", "-print_format", "json", "-show_format", "-show_streams", p])
    d = json.loads(r.stdout or "{}")
    v = next((s for s in d.get("streams", []) if s["codec_type"] == "video"), None)
    a = next((s for s in d.get("streams", []) if s["codec_type"] == "audio"), None)
    out = {"duration": round(float(d.get("format", {}).get("duration", 0) or 0), 2), "has_audio": a is not None}
    if v:
        w, h = int(v["width"]), int(v["height"])
        rot = int((v.get("tags", {}) or {}).get("rotate", 0) or 0)
        for sd in v.get("side_data_list", []) or []:
            if "rotation" in sd: rot = int(sd["rotation"])
        if abs(rot) in (90, 270): w, h = h, w
        num, den = (v.get("r_frame_rate", "30/1").split("/") + ["1"])[:2]
        out.update(width=w, height=h, fps=round(float(num) / float(den or 1), 2),
                   aspect=f"{w}:{h}", ratio=round(w / h, 3))
    return out

def cuts(p, thr=0.3):
    r = sh(["ffmpeg", "-hide_banner", "-i", p, "-vf", f"select='gt(scene,{thr})',showinfo", "-f", "null", "-"])
    return [round(float(m), 2) for m in re.findall(r"pts_time:([\d.]+)", r.stderr)]

def _raw(p, t, w, h, fmt="rgb24"):
    r = subprocess.run(["ffmpeg", "-v", "quiet", "-ss", f"{t:.2f}", "-i", p, "-frames:v", "1", "-vf", f"scale={w}:{h}",
                        "-f", "rawvideo", "-pix_fmt", fmt, "-"], capture_output=True)
    n = w * h * (3 if fmt == "rgb24" else 1)
    return r.stdout if len(r.stdout) >= n else None

def save_frame(p, t, out, width=540):
    sh(["ffmpeg", "-y", "-v", "quiet", "-ss", f"{t:.2f}", "-i", p, "-frames:v", "1", "-vf", f"scale={width}:-2", "-q:v", "3", out])
    return os.path.exists(out)

def colors(p, times, k=5):
    px = []
    for t in times[:8]:
        b = _raw(p, t, 24, 24)
        if b: px += [(b[i], b[i+1], b[i+2]) for i in range(0, len(b), 3)]
    if not px: return []
    rnd = random.Random(7); cen = rnd.sample(px, min(k, len(px)))
    for _ in range(12):
        groups = [[] for _ in cen]
        for c in px:
            j = min(range(len(cen)), key=lambda i: (c[0]-cen[i][0])**2 + (c[1]-cen[i][1])**2 + (c[2]-cen[i][2])**2)
            groups[j].append(c)
        cen = [tuple(sum(v[i] for v in g) / len(g) for i in range(3)) if g else cen[j] for j, g in enumerate(groups)]
    tot = len(px); order = sorted(range(len(cen)), key=lambda j: -len(groups[j]))
    return [{"hex": "#%02x%02x%02x" % tuple(int(x) for x in cen[j]), "share": round(len(groups[j]) / tot, 2)} for j in order]

def motion(p, dur):
    n = min(40, max(int(dur * 3), 2)); fps = n / max(dur, 0.1)
    r = subprocess.run(["ffmpeg", "-v", "quiet", "-i", p, "-vf", f"fps={fps:.3f},scale=80:142", "-f", "rawvideo", "-pix_fmt", "gray", "-"], capture_output=True)
    fs = 80 * 142; b = r.stdout; diffs = []
    frames = [b[i:i+fs] for i in range(0, len(b) - fs + 1, fs)]
    for a, c in zip(frames, frames[1:]):
        diffs.append(sum(abs(x - y) for x, y in zip(a, c)) / fs)
    m = sum(diffs) / len(diffs) if diffs else 0
    return {"mean_diff": round(m, 2), "level": "low" if m < 10 else "medium" if m < 25 else "high"}

def audio_feats(p, wav_out):
    sh(["ffmpeg", "-y", "-v", "quiet", "-i", p, "-vn", "-ac", "1", "-ar", "11025", "-t", "120", wav_out])
    if not os.path.exists(wav_out): return None
    with wave.open(wav_out) as w:
        x = array.array("h"); x.frombytes(w.readframes(w.getnframes())); sr = w.getframerate()
    if sys.byteorder == "big": x.byteswap()
    if len(x) < sr: return {"tempo_bpm": None}
    hop = 256; n = len(x) // hop
    env = [math.sqrt(sum((v / 32768) ** 2 for v in x[i*hop:(i+1)*hop]) / hop) for i in range(n)]
    on = [max(env[i+1] - env[i], 0) for i in range(n - 1)]; mu = sum(on) / len(on); on = [v - mu for v in on]
    fps = sr / hop; lo, hi = int(fps * 60 / 180), int(fps * 60 / 70); bpm = None
    if hi < len(on):
        best = max(range(lo, hi), key=lambda L: sum(on[i] * on[i+L] for i in range(0, len(on) - L, 2)))
        bpm = round(60 * fps / best)
    rms = math.sqrt(sum((v / 32768) ** 2 for v in x[::4]) / len(x[::4]))
    return {"tempo_bpm": bpm, "rms": round(rms, 3), "energy": "low" if rms < 0.06 else "medium" if rms < 0.14 else "high"}

def sheet(d, out, cols=4):
    fr = sorted(f for f in os.listdir(os.path.join(d, "frames")) if f.endswith(".jpg"))
    if not fr: return
    rows = (len(fr) + cols - 1) // cols
    sh(["ffmpeg", "-y", "-v", "quiet", "-pattern_type", "glob", "-i", os.path.join(d, "frames", "*.jpg"),
        "-vf", f"scale=270:-2,tile={cols}x{rows}:padding=4:color=0x141414", "-frames:v", "1", "-q:v", "4", out])

def linspace(a, b, n):
    return [a + (b - a) * i / (n - 1) for i in range(n)] if n > 1 else [a]

def ingest_video(p, kind):
    slug = slugify(p); d = os.path.join(CACHE, slug); os.makedirs(os.path.join(d, "frames"), exist_ok=True)
    mf = os.path.join(d, "meta.json")
    if os.path.exists(mf) and json.load(open(mf)).get("source_mtime") == os.path.getmtime(p):
        return slug, "cached"
    m = probe(p); dur = m["duration"]
    c = cuts(p); bounds = [0] + c + [dur]
    shots = [(bounds[i], bounds[i+1]) for i in range(len(bounds) - 1) if bounds[i+1] - bounds[i] > 0.15]
    times = [round((a + b) / 2, 2) for a, b in shots]
    if len(times) > 12: times = [times[int(i)] for i in linspace(0, len(times) - 1, 12)]
    if len(times) < 6: times = sorted(set(times + [round(t, 2) for t in linspace(0.2, max(dur - 0.2, 0.3), 6)]))[:12]
    ft = [t for t in times if save_frame(p, t, os.path.join(d, "frames", f"{t:06.2f}.jpg"))]
    if ft: sheet(d, os.path.join(d, "sheet.jpg"))
    a = audio_feats(p, os.path.join("/tmp", slug + ".wav")) if m["has_audio"] else None
    if os.path.exists(os.path.join("/tmp", slug + ".wav")): os.remove(os.path.join("/tmp", slug + ".wav"))
    meta = {"slug": slug, "kind": kind, "source": os.path.relpath(p, ROOT), "source_mtime": os.path.getmtime(p),
            **m, "cuts": c, "shot_count": len(shots), "avg_shot_len": round(dur / max(len(shots), 1), 2),
            "keyframes": ft, "colors": colors(p, ft) if ft else [], "motion": motion(p, dur), "audio": a,
            "status": "analyzed_pending_review"}
    if kind == "music_video":
        out = os.path.join(ROOT, "02-music", "_extracted", slug + ".m4a")
        sh(["ffmpeg", "-y", "-v", "quiet", "-i", p, "-vn", "-c:a", "aac", "-b:a", "192k", out])
        meta["extracted_audio"] = os.path.relpath(out, ROOT) if os.path.exists(out) else None
    json.dump(meta, open(mf, "w"), ensure_ascii=False, indent=2)
    return slug, "ok"

def ingest_audio(p):
    slug = slugify(p); d = os.path.join(CACHE, slug); os.makedirs(d, exist_ok=True)
    m = probe(p); a = audio_feats(p, os.path.join("/tmp", slug + ".wav"))
    if os.path.exists(os.path.join("/tmp", slug + ".wav")): os.remove(os.path.join("/tmp", slug + ".wav"))
    lic = os.path.splitext(p)[0] + ".txt"
    meta = {"slug": slug, "kind": "music_audio", "source": os.path.relpath(p, ROOT), "duration": m["duration"],
            "audio": a, "license_note": open(lic).read().strip() if os.path.exists(lic) else None, "status": "analyzed_pending_review"}
    json.dump(meta, open(os.path.join(d, "meta.json"), "w"), ensure_ascii=False, indent=2)
    return slug, "ok"

def walk(folder, exts):
    for r, _, fs in os.walk(os.path.join(ROOT, folder)):
        for f in sorted(fs):
            if f.lower().endswith(exts) and not f.startswith("."): yield os.path.join(r, f)

if __name__ == "__main__":
    mode = sys.argv[1] if len(sys.argv) > 1 else "all"
    os.makedirs(CACHE, exist_ok=True); index = []
    if mode in ("inbox", "all"):
        for p in walk("00-inbox", VID): index.append(("inbox",) + ingest_video(p, "style_reference"))
    if mode in ("music", "all"):
        for p in list(walk("02-music/from-video", VID)) + list(walk("02-music/audio", VID)): index.append(("music_video",) + ingest_video(p, "music_video"))
        for p in walk("02-music/audio", AUD): index.append(("music_audio",) + ingest_audio(p))
    for k, s, st in index: print(k, s, st)
    print("total", len(index))

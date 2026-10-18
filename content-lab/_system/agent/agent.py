#!/usr/bin/env python3
"""
ایجنت مک کارخانه‌ی محتوای وطن
- کار را از داشبورد (لوکال یا سرور اصلی) می‌گیرد، روی مک اجرا می‌کند و نتیجه را برمی‌گرداند.
- کارها: ingest_videos (تحلیل ویدیوهای 00-inbox و انتقال به پوشه‌ی سبک) · ingest_music (تحلیل موزیک و انتقال به کتابخانه)
         sync_files (جابه‌جایی بعد از تغییر دستی سبک) · render (ساخت ریلز/اسلاید/پوستر، کاور و استوری با Remotion)
اجرا:
  python3 content-lab/_system/agent/agent.py --server https://aivatan.com --token XXXX          (همیشه روشن)
  python3 content-lab/_system/agent/agent.py --server http://localhost:8888 --token XXXX --once (فقط کارهای فعلی)
فقط کتابخانه‌ی استاندارد پایتون + ffmpeg + node (اگر نباشند با brew نصب می‌شوند).
  python3 agent.py --install --server http://vatan-ai.test --token A --server https://aivatan.com --token B   (ایجنت دائمی macOS)
  python3 agent.py --doctor   (بررسی وابستگی‌ها)
"""
import argparse, json, os, shutil, socket, subprocess, sys, time, traceback, uuid, mimetypes, urllib.request, urllib.error

HERE = os.path.dirname(os.path.abspath(__file__))
LAB = os.path.dirname(os.path.dirname(HERE))
TOOLS = os.path.join(LAB, '_system', 'tools')
RENDER = os.path.join(LAB, '_system', 'render')
VERSION = '0.3.0'
VID = ('.mp4', '.mov', '.m4v', '.webm', '.mkv')
AUD = ('.mp3', '.wav', '.m4a', '.aac', '.ogg', '.flac')
sys.path.insert(0, TOOLS)
os.environ['PATH'] = ':'.join(['/opt/homebrew/bin', '/usr/local/bin', os.path.expanduser('~/.nvm/current/bin'), os.environ.get('PATH', '/usr/bin:/bin')])
os.environ.setdefault('HOME', os.path.expanduser('~'))
LABEL = 'com.vatan.content-agent'


def brew():
    return shutil.which('brew') or next((p for p in ('/opt/homebrew/bin/brew', '/usr/local/bin/brew') if os.path.exists(p)), None)


def ensure_tool(name, formula=None):
    """اگر ابزار نبود و brew بود، خودکار نصب می‌کند؛ وگرنه خطای قابل‌فهم می‌دهد."""
    if shutil.which(name):
        return True
    b = brew()
    if b:
        log(f'{name} نصب نیست؛ نصب با brew …')
        subprocess.run([b, 'install', formula or name], capture_output=True, text=True, timeout=1800, env={**os.environ, 'HOMEBREW_NO_AUTO_UPDATE': '1'})
        if shutil.which(name):
            return True
    raise RuntimeError(f'«{name}» روی مک نصب نیست. در ترمینال بزنید: brew install {formula or name}' + ('' if b else ' (اول Homebrew را از brew.sh نصب کنید)'))


def doctor():
    def ver(cmd):
        try:
            return subprocess.run(cmd, capture_output=True, text=True, timeout=20).stdout.splitlines()[0][:80]
        except Exception:
            return None
    return {'python': sys.version.split()[0], 'python_bin': sys.executable, 'ffmpeg': ver(['ffmpeg', '-version']),
            'ffprobe': bool(shutil.which('ffprobe')), 'node': ver(['node', '-v']), 'npm': bool(shutil.which('npm')),
            'brew': brew(), 'render_deps': os.path.isdir(os.path.join(RENDER, 'node_modules', 'remotion')),
            'claude_code': claude_bin(), 'launch_agent': os.path.exists(os.path.expanduser(f'~/Library/LaunchAgents/{LABEL}.plist')), 'lab': LAB}


def install(servers):
    cfg = os.path.join(HERE, 'config.json')
    json.dump({'servers': [{'url': u, 'token': t} for u, t in servers]}, open(cfg, 'w'), ensure_ascii=False, indent=2)
    agents = os.path.expanduser('~/Library/LaunchAgents')
    os.makedirs(agents, exist_ok=True)
    plist = os.path.join(agents, LABEL + '.plist')
    logf = os.path.join(HERE, 'agent.log')
    open(plist, 'w').write(f'''<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0"><dict>
<key>Label</key><string>{LABEL}</string>
<key>ProgramArguments</key><array><string>{sys.executable}</string><string>{os.path.abspath(__file__)}</string></array>
<key>RunAtLoad</key><true/><key>KeepAlive</key><true/>
<key>WorkingDirectory</key><string>{HERE}</string>
<key>EnvironmentVariables</key><dict><key>PATH</key><string>{os.environ['PATH']}</string></dict>
<key>StandardOutPath</key><string>{logf}</string><key>StandardErrorPath</key><string>{logf}</string>
</dict></plist>
''')
    uid = str(os.getuid())
    subprocess.run(['launchctl', 'bootout', f'gui/{uid}/{LABEL}'], capture_output=True)
    r = subprocess.run(['launchctl', 'bootstrap', f'gui/{uid}', plist], capture_output=True, text=True)
    if r.returncode != 0:
        r = subprocess.run(['launchctl', 'load', '-w', plist], capture_output=True, text=True)
    print(json.dumps({'ok': r.returncode == 0, 'plist': plist, 'config': cfg, 'err': (r.stderr or '')[-300:]}, ensure_ascii=False))


class Api:
    def __init__(self, server, token):
        self.base = server.rstrip('/') + '/api/content-factory'
        self.token = token

    def _req(self, path, data=None, files=None, timeout=120):
        url = self.base + path
        headers = {'X-CF-Token': self.token, 'Accept': 'application/json'}
        if files:
            boundary = uuid.uuid4().hex
            body = b''
            for k, v in (data or {}).items():
                body += f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
            for k, p in files.items():
                name = os.path.basename(p)
                ctype = mimetypes.guess_type(name)[0] or 'application/octet-stream'
                body += f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{name}"\r\nContent-Type: {ctype}\r\n\r\n'.encode()
                body += open(p, 'rb').read() + b'\r\n'
            body += f'--{boundary}--\r\n'.encode()
            headers['Content-Type'] = f'multipart/form-data; boundary={boundary}'
        else:
            body = json.dumps(data or {}, ensure_ascii=False).encode()
            headers['Content-Type'] = 'application/json'
        req = urllib.request.Request(url, data=body, headers=headers, method='POST')
        try:
            with urllib.request.urlopen(req, timeout=timeout) as r:
                return json.loads(r.read().decode() or '{}')
        except urllib.error.HTTPError as e:
            raise RuntimeError(f'HTTP {e.code} {path}: {e.read().decode(errors="ignore")[:300]}')

    def post(self, path, data=None, timeout=120):
        return self._req(path, data, timeout=timeout)

    def upload(self, file, dir_, chunk=1024 * 1024):
        size = os.path.getsize(file)
        total = max(1, -(-size // chunk))
        uid = uuid.uuid4().hex
        tmp = os.path.join(HERE, '.chunk')
        res = {}
        with open(file, 'rb') as fh:
            for i in range(total):
                open(tmp, 'wb').write(fh.read(chunk))
                res = self._req('/agent/chunk', {'upload_id': uid, 'index': i, 'total': total, 'name': os.path.basename(file), 'dir': dir_}, {'chunk': tmp}, timeout=300)
        try:
            os.remove(tmp)
        except OSError:
            pass
        if not res.get('path'):
            raise RuntimeError('آپلود ناقص ماند: ' + os.path.basename(file))
        return res['path']


def log(*a):
    print(time.strftime('%H:%M:%S'), *a, flush=True)


def lab(*p):
    return os.path.join(LAB, *p)


def files_in(folder, exts):
    d = lab(folder)
    if not os.path.isdir(d):
        return []
    return sorted(os.path.join(d, f) for f in os.listdir(d) if f.lower().endswith(exts) and not f.startswith('.'))


def safe_move(src, dst_dir):
    os.makedirs(dst_dir, exist_ok=True)
    dst = os.path.join(dst_dir, os.path.basename(src))
    if os.path.abspath(src) == os.path.abspath(dst):
        return dst
    if os.path.exists(dst):
        base, ext = os.path.splitext(dst)
        dst = f'{base}-{uuid.uuid4().hex[:4]}{ext}'
    shutil.move(src, dst)
    return dst


def pending():
    return {'inbox': len(files_in('00-inbox', VID)),
            'music': len(files_in('02-music/audio', AUD + VID)) + len(files_in('02-music/from-video', VID))}


# ── کارها ───────────────────────────────────────────────────────────
def job_ingest_videos(api, job, report):
    ensure_tool('ffmpeg')
    import ingest
    items = files_in('00-inbox', VID)
    if not items:
        return {'count': 0, 'note': 'پوشه‌ی 00-inbox خالی است'}
    done = []
    for i, p in enumerate(items):
        report(int(i * 100 / len(items)), f'تحلیل {i + 1} از {len(items)}: {os.path.basename(p)}')
        slug, _ = ingest.ingest_video(p, 'style_reference')
        cache = lab('_system', 'cache', slug)
        meta = json.load(open(os.path.join(cache, 'meta.json')))
        sheet = api.upload(os.path.join(cache, 'sheet.jpg'), 'references') if os.path.exists(os.path.join(cache, 'sheet.jpg')) else None
        r = api.post('/agent/reference', {'meta': meta, 'sheet_path': sheet})
        folder = r.get('folder') or '_unsorted'
        dst = safe_move(p, lab('01-styles', folder, 'refs'))
        api.post('/agent/moved', {'kind': 'reference', 'slug': slug, 'path': os.path.relpath(dst, LAB)})
        done.append({'file': os.path.basename(p), 'style': r.get('style_name') or 'بدون سبک', 'confidence': r.get('confidence')})
        log('ref', os.path.basename(p), '→', folder)
    return {'count': len(done), 'items': done}


def job_ingest_music(api, job, report):
    ensure_tool('ffmpeg')
    import ingest
    items = [(p, 'music_video') for p in files_in('02-music/from-video', VID) + [x for x in files_in('02-music/audio', VID)]]
    items += [(p, 'music_audio') for p in files_in('02-music/audio', AUD)]
    if not items:
        return {'count': 0, 'note': 'پوشه‌های موزیک خالی است'}
    done = []
    for i, (p, kind) in enumerate(items):
        report(int(i * 100 / len(items)), f'تحلیل موزیک {i + 1} از {len(items)}: {os.path.basename(p)}')
        if kind == 'music_video':
            slug, _ = ingest.ingest_video(p, 'music_video')
        else:
            slug, _ = ingest.ingest_audio(p)
        meta = json.load(open(lab('_system', 'cache', slug, 'meta.json')))
        audio_local = lab(meta['extracted_audio']) if meta.get('extracted_audio') else p
        audio = api.upload(audio_local, 'music') if os.path.exists(audio_local) else None
        r = api.post('/agent/music', {'meta': meta, 'audio_path': audio})
        dst_dir = lab('02-music', 'library', r.get('folder') or 'medium-mid')
        dst = safe_move(p, dst_dir)
        if meta.get('extracted_audio') and os.path.exists(audio_local):
            safe_move(audio_local, dst_dir)
        api.post('/agent/moved', {'kind': 'music', 'slug': slug, 'path': os.path.relpath(dst, LAB)})
        done.append({'file': os.path.basename(p), 'folder': r.get('label')})
    return {'count': len(done), 'items': done}


def job_sync_files(api, job, report):
    r = api.post('/agent/assignments')
    moved = 0
    for a in r.get('references', []):
        cur = lab(a['path']) if a.get('path') else None
        if not cur or not os.path.exists(cur):
            continue
        want = lab('01-styles', a['folder'], 'refs')
        if os.path.dirname(os.path.abspath(cur)) != os.path.abspath(want):
            dst = safe_move(cur, want)
            api.post('/agent/moved', {'kind': 'reference', 'slug': a['slug'], 'path': os.path.relpath(dst, LAB)})
            moved += 1
    for s in r.get('styles', []):
        os.makedirs(lab('01-styles', s, 'refs'), exist_ok=True)
    return {'moved': moved}


FALLBACK_IMAGES = ['media/d01.webp', 'media/d05.webp', 'media/d02.webp', 'media/d12.webp']


def resolve_props(props, missing):
    """lab:… → کپی به render/public/lab-cache ؛ فایل نبود → جایگزین امن و ثبت هشدار"""
    cache = os.path.join(RENDER, 'public', 'lab-cache')
    os.makedirs(cache, exist_ok=True)

    def fix(v):
        if isinstance(v, str) and v.startswith('lab:'):
            src = lab(v[4:])
            if os.path.exists(src):
                name = uuid.uuid5(uuid.NAMESPACE_URL, src).hex[:10] + os.path.splitext(src)[1]
                shutil.copy2(src, os.path.join(cache, name))
                return 'lab-cache/' + name
            missing.append(v[4:])
            return None
        if isinstance(v, list):
            return [x for x in (fix(i) for i in v) if x is not None]
        if isinstance(v, dict):
            out = {k: fix(x) for k, x in v.items()}
            if out.get('type') == 'split' and (not out.get('before') or not out.get('after')):
                img = out.get('after') or out.get('before') or FALLBACK_IMAGES[len(missing) % 4]
                return {'type': 'card', 'dur': out.get('dur', 60), 'image': img, 'tag': out.get('labelAfter') or '✦ با وطن', 'text': out.get('text'), 'hl': out.get('hl', [])}
            for k in ('image',):
                if k in v and v[k] and out.get(k) is None:
                    out[k] = FALLBACK_IMAGES[len(missing) % 4]
            if out.get('type') == 'card' and not out.get('image'):
                out['image'] = FALLBACK_IMAGES[len(missing) % 4]
            if 'images' in out and isinstance(out['images'], list) and len(out['images']) < len(v.get('images', [])):
                out['images'] = (out['images'] + FALLBACK_IMAGES)[:len(v['images'])]
            return out
        return v
    return fix(props)


def ensure_render():
    ensure_tool('ffmpeg')
    ensure_tool('node')
    if not os.path.isdir(os.path.join(RENDER, 'node_modules', 'remotion')):
        log('نصب وابستگی‌های رندر (یک بار)…')
        subprocess.run(['npm', 'install', '--no-audit', '--no-fund'], cwd=RENDER, check=True)


def ffprobe(p):
    r = subprocess.run(['ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_format', '-show_streams', p], capture_output=True, text=True)
    return json.loads(r.stdout or '{}')


def qc_video(p):
    d = ffprobe(p)
    v = next((s for s in d.get('streams', []) if s.get('codec_type') == 'video'), {})
    dur = float(d.get('format', {}).get('duration', 0) or 0)
    bd = subprocess.run(['ffmpeg', '-hide_banner', '-i', p, '-vf', 'blackdetect=d=0.4:pix_th=0.05', '-an', '-f', 'null', '-'], capture_output=True, text=True).stderr
    return dur, {'۹:۱۶ و ۱۰۸۰×۱۹۲۰': v.get('width') == 1080 and v.get('height') == 1920, 'بدون فریم سیاه': 'black_start' not in bd,
                 'صدا دارد': any(s.get('codec_type') == 'audio' for s in d.get('streams', [])), 'مدت ۵ تا ۶۰ ثانیه': 5 <= dur <= 60}


def job_render(api, job, report):
    spec = job['payload']
    ensure_render()
    out_dir = lab('06-productions', spec['code'], f"v{spec['version']}")
    os.makedirs(out_dir, exist_ok=True)
    files = {'stories': [], 'slides': []}
    missing, qc, duration = [], {}, None
    t0 = time.time()
    outs = spec['outputs']
    for i, o in enumerate(outs):
        report(int(i * 90 / len(outs)), f"رندر {o['name']} ({i + 1} از {len(outs)})")
        props = resolve_props(o['props'], missing)
        pf = os.path.join(out_dir, o['name'] + '.props.json')
        json.dump(props, open(pf, 'w'), ensure_ascii=False)
        target = os.path.join(out_dir, o['name'])
        cmd = ['node', 'render.mjs', o['composition'], pf, target]
        if o.get('still'):
            cmd += ['--still'] + ([f"--frame={o['frame']}"] if o.get('frame') is not None else ['--frame=0'])
        r = subprocess.run(cmd, cwd=RENDER, capture_output=True, text=True, timeout=1200)
        if r.returncode != 0 or not os.path.exists(target):
            raise RuntimeError(f"رندر {o['name']} شکست خورد: " + (r.stderr or r.stdout)[-800:])
        path = api.upload(target, f"productions/{spec['code']}/v{spec['version']}")
        if o['role'] == 'video':
            files['video'] = path
            duration, qc = qc_video(target)
        elif o['role'] == 'cover':
            files['cover'] = path
        elif o['role'] == 'story':
            files['stories'].append(path)
        else:
            files['slides'].append(path)
    if missing:
        qc['تصویرهای پوشه‌ی مک موجود بود'] = False
    notes = ('تصویرهای ناموجود با نمونه جایگزین شدند: ' + '، '.join(sorted(set(missing)))) if missing else None
    api.post(f"/agent/productions/{spec['production_id']}/version", {'version': spec['version'], 'files': files, 'duration': duration,
             'qc': qc, 'notes': notes, 'build_seconds': int(time.time() - t0)})
    return {'files': files, 'missing': sorted(set(missing)), 'seconds': int(time.time() - t0)}


def claude_bin():
    for p in (shutil.which('claude'), os.path.expanduser('~/.claude/local/claude'), os.path.expanduser('~/.local/bin/claude'),
              '/opt/homebrew/bin/claude', '/usr/local/bin/claude'):
        if p and os.path.exists(p):
            return p
    return None


def job_ai_write(api, job, report):
    """نوشتن صحنه‌ها/استوری/کپشن با Claude Code روی مک (اشتراک Pro) طبق پرامپت جامع همان پست."""
    b = claude_bin()
    if not b:
        raise RuntimeError('Claude Code روی مک پیدا نشد. نصب: npm i -g @anthropic-ai/claude-code و یک بار claude را اجرا و وارد حساب شوید.')
    p = job['payload']
    report(10, f"Claude Code در حال نوشتن {p.get('code', '')} …")
    prompt = (p.get('system') or '') + '\n\n' + (p.get('user') or '') + '\n\nفقط JSON خروجی را بنویس.'
    r = subprocess.run([b, '-p', '--output-format', 'text'], input=prompt, capture_output=True, text=True, timeout=900, cwd=HERE)
    out = (r.stdout or '').strip()
    if r.returncode != 0 or '{' not in out:
        raise RuntimeError('Claude Code جواب معتبر نداد: ' + ((r.stderr or out)[-500:]))
    return {'text': out, 'item_id': p.get('item_id')}


def job_doctor(api, job, report):
    return doctor()


JOBS = {'doctor': job_doctor, 'ai_write': job_ai_write, 'ingest_videos': job_ingest_videos, 'ingest_music': job_ingest_music, 'sync_files': job_sync_files, 'render': job_render}


def run_job(api, job):
    jid = job['id']
    last = [0]

    def report(progress, message):
        if time.time() - last[0] > 1.5:
            last[0] = time.time()
            try:
                r = api.post(f'/agent/jobs/{jid}/progress', {'progress': progress, 'message': message})
                if r.get('cancel'):
                    raise KeyboardInterrupt('لغو شد')
            except RuntimeError as e:
                log('progress', e)
    log('▶', jid, job['type'])
    try:
        res = JOBS[job['type']](api, job, report)
        api.post(f'/agent/jobs/{jid}/complete', {'status': 'done', 'result': res, 'message': 'انجام شد'})
        log('✓', jid)
    except KeyboardInterrupt as e:
        log('✗ لغو', jid, e)
    except Exception as e:
        tb = traceback.format_exc()
        log('✗', jid, e)
        try:
            api.post(f'/agent/jobs/{jid}/complete', {'status': 'failed', 'message': str(e)[:250], 'log': tb[-3000:]})
        except Exception as e2:
            log('complete failed', e2)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--server', action='append', help='آدرس داشبورد (چندبار مجاز)')
    ap.add_argument('--token', action='append')
    ap.add_argument('--once', action='store_true')
    ap.add_argument('--interval', type=int, default=5)
    ap.add_argument('--install', action='store_true')
    ap.add_argument('--doctor', action='store_true')
    a = ap.parse_args()
    if a.doctor:
        print(json.dumps(doctor(), ensure_ascii=False, indent=2))
        return
    if a.install:
        install(list(zip(a.server or [], a.token or [])))
        return
    cfg_path = os.path.join(HERE, 'config.json')
    servers = []
    if a.server:
        servers = list(zip(a.server, a.token or []))
    elif os.path.exists(cfg_path):
        servers = [(s['url'], s['token']) for s in json.load(open(cfg_path)).get('servers', [])]
    if not servers:
        sys.exit('آدرس داشبورد و توکن لازم است (--server … --token …) یا فایل config.json')
    apis = [Api(u, t) for u, t in servers]
    host = socket.gethostname()
    log(f'ایجنت کارخانه‌ی محتوای وطن v{VERSION} · {LAB}')
    caps = doctor()
    while True:
        worked = False
        for api in apis:
            try:
                api.post('/agent/hello', {'host': host, 'version': VERSION, 'lab': LAB, 'pending': pending(),
                                          'caps': caps}, timeout=20)
                while True:
                    j = api.post('/agent/next', {'host': host}, timeout=30).get('job')
                    if not j:
                        break
                    worked = True
                    run_job(api, j)
            except Exception as e:
                log('اتصال', api.base, '→', e)
        if a.once:
            for api in apis:
                try:
                    api.post('/agent/hello', {'host': host, 'bye': 1, 'pending': pending()}, timeout=20)
                except Exception:
                    pass
            break
        time.sleep(1 if worked else a.interval)


if __name__ == '__main__':
    main()

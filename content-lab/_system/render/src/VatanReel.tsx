import React from 'react';
import {AbsoluteFill, Audio, Img, interpolate, Sequence, spring, useCurrentFrame, useVideoConfig, Easing} from 'remotion';
import {C, F, THEMES, Theme, ThemeName} from './brand';
import {Curve, Fonts, GridBg, KenBurns, Logo, Pill, PopText, Vignette, src} from './parts';

/*
| موتور واحد ریلز وطن: هر سبک = یک تم (رنگ، فونت، گذار، تزئین) + دستور صحنه‌ها.
| صحنه‌ها: text · card · split · grid · stat · list · quote · image · cta
*/
type Bg = 'dark' | 'light' | 'brand';
export type Scene =
  | {type: 'text'; dur: number; text?: string; kicker?: string; lines?: string[]; hl?: string[]; bg?: Bg; size?: number}
  | {type: 'card'; dur: number; image: string; tag?: string; text?: string; hl?: string[]; bg?: Bg}
  | {type: 'split'; dur: number; before: string; after: string; labelBefore?: string; labelAfter?: string; text?: string; hl?: string[]}
  | {type: 'grid'; dur: number; images: string[]; text?: string; hl?: string[]; bg?: Bg}
  | {type: 'stat'; dur: number; value: number; prefix?: string; suffix?: string; label: string; text?: string; bg?: Bg}
  | {type: 'list'; dur: number; title?: string; items: string[]; bg?: Bg; mark?: 'check' | 'cross' | 'num'}
  | {type: 'quote'; dur: number; text: string; by?: string; bg?: Bg}
  | {type: 'image'; dur: number; image: string; text?: string; hl?: string[]; tag?: string}
  | {type: 'cta'; dur: number; text: string; sub?: string; keyword?: string; bg?: Bg};
export type ReelProps = {theme?: ThemeName; scenes: Scene[]; music?: string; musicStart?: number; musicVolume?: number; watermark?: boolean};
export const reelDuration = (p: ReelProps) => p.scenes.reduce((a, s) => a + s.dur, 0);

const fa = (n: number) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[+d]);
const bgColor = (t: Theme, b?: Bg) => (b === 'light' ? t.bgLight : b === 'brand' ? t.bgBrand : t.bgDark);
const fgColor = (t: Theme, b?: Bg) => (b === 'light' ? t.textOnLight : b === 'brand' && t.bgBrand === C.lime ? C.ink : t.text);
const Ctx = React.createContext<{t: Theme; name: ThemeName}>({t: THEMES.punch, name: 'punch'});

const Backdrop: React.FC<{b?: Bg; dur: number}> = ({b, dur}) => {
  const {t, name} = React.useContext(Ctx);
  return <AbsoluteFill style={{background: bgColor(t, b)}}>
    {t.grid !== 'transparent' && <GridBg color={b === 'light' ? 'rgba(0,0,0,.05)' : t.grid} size={name === 'techgrid' ? 60 : 90} />}
    {name === 'editorial' && <Curve color={b === 'light' ? 'rgba(22,89,79,.25)' : 'rgba(194,253,117,.22)'} dur={dur} />}
    {name === 'techgrid' && <HudCorners />}
    {name === 'tactile' && <AbsoluteFill style={{background: 'radial-gradient(ellipse at 30% 20%, rgba(255,255,255,.25), transparent 60%)'}} />}
  </AbsoluteFill>;
};

const HudCorners: React.FC = () => {
  const s = {position: 'absolute' as const, width: 70, height: 70, borderColor: 'rgba(194,253,117,.45)', borderStyle: 'solid' as const};
  return <AbsoluteFill>
    <div style={{...s, top: 230, left: 50, borderWidth: '4px 0 0 4px'}} /><div style={{...s, top: 230, right: 50, borderWidth: '4px 4px 0 0'}} />
    <div style={{...s, bottom: 400, left: 50, borderWidth: '0 0 4px 4px'}} /><div style={{...s, bottom: 400, right: 50, borderWidth: '0 4px 4px 0'}} />
  </AbsoluteFill>;
};

const Transition: React.FC = () => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame();
  if (t.transition === 'flash') return <AbsoluteFill style={{background: C.white, opacity: interpolate(f, [0, 4], [0.32, 0], {extrapolateRight: 'clamp'}), pointerEvents: 'none'}} />;
  return null;
};

const Enter: React.FC<{children: React.ReactNode}> = ({children}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame(); const {fps} = useVideoConfig();
  if (t.transition !== 'slide') return <>{children}</>;
  const k = spring({frame: f, fps, config: {damping: 20, stiffness: 140}});
  return <AbsoluteFill style={{transform: `translateX(${(1 - k) * -120}px)`, opacity: Math.min(1, k * 1.4)}}>{children}</AbsoluteFill>;
};

const TextS: React.FC<{s: Extract<Scene, {type: 'text'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const k = spring({frame: f, fps, config: {damping: 16}}); const fg = fgColor(t, s.bg);
  return <AbsoluteFill style={{justifyContent: 'center', padding: '0 70px'}}>
    {s.kicker && <div style={{textAlign: 'center', marginBottom: 22, direction: 'rtl', fontFamily: F.display, fontWeight: 300, fontSize: 68, color: s.bg === 'light' ? C.primary : t.kicker, opacity: k, transform: `translateY(${(1 - k) * 26}px)`}}>{s.kicker}</div>}
    {s.lines ? <div style={{display: 'flex', flexDirection: 'column', gap: 14}}>{s.lines.map((l, i) => <PopText key={i} text={l} hl={s.hl} size={s.size ?? 124} color={fg} start={i * 8} stagger={2} hlBg={t.hl} hlColor={t.hlText} weight={t.weight} />)}</div>
      : <PopText text={s.text ?? ''} hl={s.hl} size={s.size ?? 132} color={fg} start={s.kicker ? 6 : 0} hlBg={t.hl} hlColor={t.hlText} weight={t.weight} />}
  </AbsoluteFill>;
};

const CardS: React.FC<{s: Extract<Scene, {type: 'card'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const k = spring({frame: f, fps, config: {damping: 13, stiffness: 120}}); const z = interpolate(f, [0, s.dur], [1, 1.06]);
  return <AbsoluteFill style={{alignItems: 'center'}}>
    <div style={{position: 'absolute', top: 300, width: 760, height: 960, borderRadius: 44, overflow: 'hidden', transform: `translateY(${(1 - k) * 260}px) rotate(${(1 - k) * -6}deg) scale(${z})`,
      boxShadow: '0 40px 90px rgba(0,0,0,.45)', border: `6px solid ${s.bg === 'light' ? C.white : 'rgba(255,255,255,.12)'}`}}>
      <Img src={src(s.image)} style={{width: '100%', height: '100%', objectFit: 'cover'}} />
      {s.tag && <div style={{position: 'absolute', top: 26, right: 26}}><Pill size={34} bg={t.hl} color={t.hlText}>{s.tag}</Pill></div>}
    </div>
    {s.text && <div style={{position: 'absolute', top: 1300, left: 70, right: 70}}><PopText text={s.text} hl={s.hl} size={88} color={fgColor(t, s.bg)} start={5} hlBg={t.hl} hlColor={t.hlText} weight={t.weight} /></div>}
  </AbsoluteFill>;
};

const SplitS: React.FC<{s: Extract<Scene, {type: 'split'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame();
  const p = interpolate(f, [10, 10 + s.dur * 0.45], [0, 100], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.inOut(Easing.cubic)});
  return <AbsoluteFill>
    <KenBurns src={s.before} dur={s.dur} zoom={[1.02, 1.06]} />
    <AbsoluteFill style={{clipPath: `inset(0 0 0 ${100 - p}%)`}}><KenBurns src={s.after} dur={s.dur} zoom={[1.08, 1.0]} /></AbsoluteFill>
    {p > 0 && p < 100 && <div style={{position: 'absolute', top: 0, bottom: 0, left: `${100 - p}%`, width: 8, background: t.hl, boxShadow: `0 0 40px ${t.hl}`}} />}
    <Vignette />
    <div style={{position: 'absolute', top: 290, right: 70}}><Pill size={36} bg={p > 50 ? t.hl : 'rgba(255,255,255,.92)'} color={C.ink}>{p > 50 ? (s.labelAfter ?? '✦ با وطن') : (s.labelBefore ?? 'عکس موبایل')}</Pill></div>
    {s.text && <div style={{position: 'absolute', bottom: 470, left: 70, right: 70}}><PopText text={s.text} hl={s.hl} size={86} start={Math.round(s.dur * 0.45)} hlBg={t.hl} hlColor={t.hlText} weight={t.weight} /></div>}
  </AbsoluteFill>;
};

const GridS: React.FC<{s: Extract<Scene, {type: 'grid'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame(); const {fps} = useVideoConfig();
  return <AbsoluteFill>
    <div style={{position: 'absolute', top: 290, left: 70, right: 70, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 22}}>
      {s.images.slice(0, 4).map((im, i) => { const k = spring({frame: f - i * 3, fps, config: {damping: 14}});
        return <div key={i} style={{aspectRatio: '4 / 5', borderRadius: 28, overflow: 'hidden', transform: `scale(${0.6 + 0.4 * k})`, opacity: k}}><Img src={src(im)} style={{width: '100%', height: '100%', objectFit: 'cover'}} /></div>; })}
    </div>
    {s.text && <div style={{position: 'absolute', top: 1320, left: 70, right: 70}}><PopText text={s.text} hl={s.hl} size={84} start={8} color={fgColor(t, s.bg)} hlBg={t.hl} hlColor={t.hlText} weight={t.weight} /></div>}
  </AbsoluteFill>;
};

const StatS: React.FC<{s: Extract<Scene, {type: 'stat'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame(); const fg = fgColor(t, s.bg);
  const v = Math.round(interpolate(f, [0, Math.min(30, s.dur - 5)], [0, s.value], {extrapolateRight: 'clamp', easing: Easing.out(Easing.cubic)}));
  return <AbsoluteFill style={{justifyContent: 'center', alignItems: 'center', gap: 20, padding: '0 70px'}}>
    <div style={{fontFamily: F.display, fontWeight: 900, fontSize: 300, color: t.hl === C.primary && s.bg !== 'light' ? C.lime : t.hl, lineHeight: 1, direction: 'rtl'}}>{s.prefix ?? ''}{fa(v)}{s.suffix ?? ''}</div>
    <div style={{fontFamily: F.display, fontWeight: 800, fontSize: 70, color: fg, direction: 'rtl', textAlign: 'center'}}>{s.label}</div>
    {s.text && <div style={{marginTop: 20}}><PopText text={s.text} size={60} color={fg} start={18} weight={500} /></div>}
  </AbsoluteFill>;
};

const ListS: React.FC<{s: Extract<Scene, {type: 'list'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame(); const {fps} = useVideoConfig(); const fg = fgColor(t, s.bg);
  const per = Math.max(6, Math.floor((s.dur - 12) / Math.max(1, s.items.length)));
  return <AbsoluteFill style={{justifyContent: 'center', padding: '0 90px', gap: 34, direction: 'rtl'}}>
    {s.title && <div style={{fontFamily: F.display, fontWeight: 900, fontSize: 92, color: fg, marginBottom: 20}}>{s.title}</div>}
    {s.items.map((it, i) => { const k = spring({frame: f - 6 - i * per, fps, config: {damping: 15}});
      const mark = s.mark === 'cross' ? '✕' : s.mark === 'num' ? fa(i + 1) : '✓';
      return <div key={i} style={{display: 'flex', alignItems: 'center', gap: 28, opacity: k, transform: `translateX(${(1 - k) * -80}px)`}}>
        <div style={{width: 92, height: 92, borderRadius: 26, background: s.mark === 'cross' ? C.red : t.hl, color: s.mark === 'cross' ? C.white : t.hlText, display: 'grid', placeItems: 'center', fontFamily: F.display, fontWeight: 900, fontSize: 52, flexShrink: 0}}>{mark}</div>
        <div style={{fontFamily: F.display, fontWeight: 800, fontSize: 74, color: fg, lineHeight: 1.25}}>{it}</div>
      </div>; })}
  </AbsoluteFill>;
};

const QuoteS: React.FC<{s: Extract<Scene, {type: 'quote'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const fg = fgColor(t, s.bg);
  return <AbsoluteFill style={{justifyContent: 'center', padding: '0 90px', direction: 'rtl'}}>
    <div style={{fontFamily: F.display, fontWeight: 900, fontSize: 260, color: t.hl, lineHeight: 0.6}}>«</div>
    <PopText text={s.text} size={92} color={fg} align="right" stagger={2} weight={800} hlBg={t.hl} hlColor={t.hlText} />
    {s.by && <div style={{marginTop: 40, fontFamily: F.body, fontWeight: 700, fontSize: 48, color: t.kicker}}>— {s.by}</div>}
  </AbsoluteFill>;
};

const ImageS: React.FC<{s: Extract<Scene, {type: 'image'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx);
  return <AbsoluteFill>
    <KenBurns src={s.image} dur={s.dur} />
    <AbsoluteFill style={{background: 'linear-gradient(180deg, rgba(3,15,9,.15) 40%, rgba(3,15,9,.85) 100%)'}} />
    {s.tag && <div style={{position: 'absolute', top: 290, right: 70}}><Pill size={36} bg={t.hl} color={t.hlText}>{s.tag}</Pill></div>}
    {s.text && <div style={{position: 'absolute', bottom: 470, left: 70, right: 70}}><PopText text={s.text} hl={s.hl} size={92} hlBg={t.hl} hlColor={t.hlText} weight={t.weight} /></div>}
  </AbsoluteFill>;
};

export const CtaS: React.FC<{s: Extract<Scene, {type: 'cta'}>}> = ({s}) => {
  const {t} = React.useContext(Ctx); const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const k = spring({frame: f, fps, config: {damping: 14}}); const pulse = 1 + Math.sin(f / 5) * 0.03;
  const light = s.bg === 'light';
  return <AbsoluteFill style={{background: light ? t.bgLight : `radial-gradient(circle at 50% 38%, ${C.primary} 0%, ${C.dark} 72%)`, justifyContent: 'center', alignItems: 'center', gap: 56, padding: '0 70px'}}>
    <div style={{transform: `scale(${k})`}}><Logo size={150} on={light ? 'light' : 'dark'} /></div>
    <PopText text={s.text} size={96} start={6} color={light ? C.ink : C.white} hlBg={t.hl} hlColor={t.hlText} weight={t.weight} />
    {s.keyword && <div style={{opacity: k, direction: 'rtl', display: 'flex', alignItems: 'center', gap: 18, background: light ? C.white : 'rgba(255,255,255,.08)', border: `3px solid ${C.lime}`, borderRadius: 30, padding: '22px 36px'}}>
      <span style={{fontFamily: F.body, fontWeight: 700, fontSize: 44, color: light ? C.ink : C.white}}>کامنت کن</span>
      <span style={{fontFamily: F.display, fontWeight: 900, fontSize: 64, color: C.ink, background: C.lime, borderRadius: 18, padding: '0 26px'}}>{s.keyword}</span></div>}
    {s.sub && <div style={{transform: `scale(${pulse})`, opacity: k}}><Pill size={50} bg={C.lime}>{s.sub}</Pill></div>}
    <div style={{fontFamily: F.body, fontWeight: 700, fontSize: 40, color: light ? C.soft : C.muted, direction: 'ltr', opacity: k}}>aivatan.com</div>
  </AbsoluteFill>;
};

export const VatanReel: React.FC<ReelProps> = ({theme = 'punch', scenes, music, musicStart = 0, musicVolume = 0.85, watermark = true}) => {
  const t = THEMES[theme] ?? THEMES.punch; const total = scenes.reduce((a, s) => a + s.dur, 0);
  let at = 0;
  return <Ctx.Provider value={{t, name: theme}}><AbsoluteFill style={{background: t.bgDark}}><Fonts />
    {music && <Audio src={src(music)} startFrom={Math.round(musicStart * 30)} volume={(f) => interpolate(f, [0, 6, total - 20, total], [0, musicVolume, musicVolume, 0], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'})} />}
    {scenes.map((s, i) => { const from = at; at += s.dur;
      const own = s.type === 'split' || s.type === 'image' || s.type === 'cta';
      return <Sequence key={i} from={from} durationInFrames={s.dur}>
        {!own && <Backdrop b={(s as any).bg} dur={s.dur} />}
        <Enter>
          {s.type === 'text' && <TextS s={s} />}{s.type === 'card' && <CardS s={s} />}{s.type === 'split' && <SplitS s={s} />}
          {s.type === 'grid' && <GridS s={s} />}{s.type === 'stat' && <StatS s={s} />}{s.type === 'list' && <ListS s={s} />}
          {s.type === 'quote' && <QuoteS s={s} />}{s.type === 'image' && <ImageS s={s} />}{s.type === 'cta' && <CtaS s={s} />}
        </Enter>
        {i > 0 && <Transition />}
        {watermark && s.type !== 'cta' && <div style={{position: 'absolute', top: 120, left: 60, opacity: 0.9}}><Logo size={54} badgeOnly /></div>}
      </Sequence>; })}
  </AbsoluteFill></Ctx.Provider>;
};

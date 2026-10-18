import React from 'react';
import {AbsoluteFill, Img} from 'remotion';
import {C, F, THEMES, ThemeName} from './brand';
import {Fonts, GridBg, KenBurns, Logo, Pill, PopText, src} from './parts';

/* ── استوری ۹:۱۶ ─────────────────────────────────────────── */
export type StoryProps = {kind: 'teaser' | 'poll' | 'cta' | 'question' | 'tip'; image?: string; title: string; titleHl?: string[]; sub?: string; badge?: string; guides?: boolean; theme?: ThemeName};
export const Story: React.FC<StoryProps> = ({kind, image, title, titleHl, sub, badge, guides = false, theme = 'punch'}) => {
  const t = THEMES[theme] ?? THEMES.punch;
  return <AbsoluteFill style={{background: C.dark}}>
    <Fonts />
    {image ? <><KenBurns src={image} dur={150} zoom={[1.05, 1.12]} />
      <AbsoluteFill style={{background: kind === 'teaser' ? 'linear-gradient(180deg, rgba(3,15,9,.5) 0%, rgba(3,15,9,.2) 40%, rgba(3,15,9,.92) 100%)' : 'rgba(3,15,9,.62)'}} /></>
      : <AbsoluteFill style={{background: `radial-gradient(circle at 50% 30%, ${C.primary}, ${C.dark} 75%)`}}><GridBg color="rgba(255,255,255,.04)" /></AbsoluteFill>}
    <div style={{position: 'absolute', top: 170, left: 0, right: 0, display: 'flex', justifyContent: 'center'}}><Logo size={76} /></div>
    {badge && <div style={{position: 'absolute', top: 300, left: 0, right: 0, textAlign: 'center'}}><Pill size={36}>{badge}</Pill></div>}
    <AbsoluteFill style={{justifyContent: kind === 'teaser' ? 'flex-end' : 'center', padding: '0 80px', paddingBottom: kind === 'teaser' ? 470 : 0, top: kind === 'teaser' ? 0 : -240}}>
      <PopText text={title} hl={titleHl} size={kind === 'teaser' ? 100 : 104} still hlBg={t.hl} hlColor={t.hlText} />
      {sub && <div style={{marginTop: 34, textAlign: 'center', direction: 'rtl', fontFamily: F.body, fontWeight: 700, fontSize: 46, color: C.light, lineHeight: 1.7}}>{sub}</div>}
    </AbsoluteFill>
    {guides && kind !== 'teaser' && kind !== 'tip' && <div style={{position: 'absolute', left: 140, right: 140, top: 1080, height: kind === 'cta' ? 150 : 300, border: `4px dashed ${C.lime}`, borderRadius: 36, opacity: .55,
      display: 'flex', alignItems: 'center', justifyContent: 'center', direction: 'rtl', fontFamily: F.body, fontWeight: 700, fontSize: 30, color: C.lime}}>
      {kind === 'poll' ? 'جای استیکر نظرسنجی' : kind === 'question' ? 'جای استیکر سؤال' : 'جای استیکر لینک'}</div>}
  </AbsoluteFill>;
};

/* ── کاور ریلز (شبکه فقط ۱۰۸۰×۱۳۵۰ وسط را نشان می‌دهد) ─────────── */
export type CoverProps = {image?: string; title: string; titleHl?: string[]; kicker?: string; theme?: ThemeName};
export const Cover: React.FC<CoverProps> = ({image, title, titleHl, kicker, theme = 'punch'}) => {
  const t = THEMES[theme] ?? THEMES.punch;
  return <AbsoluteFill style={{background: t.bgDark}}>
    <Fonts />
    {image ? <><Img src={src(image)} style={{width: '100%', height: '100%', objectFit: 'cover'}} /><AbsoluteFill style={{background: 'linear-gradient(180deg, rgba(3,15,9,0) 30%, rgba(3,15,9,.92) 78%)'}} /></>
      : <GridBg color={t.grid} />}
    <AbsoluteFill style={{justifyContent: 'flex-end', padding: '0 70px', paddingBottom: 420}}>
      {kicker && <div style={{textAlign: 'center', marginBottom: 28}}><Pill size={42}>{kicker}</Pill></div>}
      <PopText text={title} hl={titleHl} size={112} still hlBg={t.hl} hlColor={t.hlText} />
    </AbsoluteFill>
    <div style={{position: 'absolute', top: 320, left: 0, right: 0, display: 'flex', justifyContent: 'center'}}><Logo size={70} /></div>
  </AbsoluteFill>;
};

/* ── اسلاید پست کاروسل ۴:۵ ─────────────────────────────────── */
export type SlideProps = {kind: 'cover' | 'tip' | 'compare' | 'list' | 'cta' | 'image'; n?: number; total?: number; title: string; titleHl?: string[]; body?: string; image?: string; before?: string; after?: string; items?: string[]; light?: boolean};
const fa = (n: number) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[+d]);
export const Slide: React.FC<SlideProps> = ({kind, n, total, title, titleHl, body, image, before, after, items, light = false}) => {
  const bg = light ? C.paper : C.dark; const fg = light ? C.ink : C.white;
  return <AbsoluteFill style={{background: bg, direction: 'rtl'}}>
    <Fonts />
    <GridBg color={light ? 'rgba(0,0,0,.05)' : 'rgba(255,255,255,.04)'} />
    {kind === 'cover' && image && <><Img src={src(image)} style={{position: 'absolute', inset: 0, width: '100%', height: '100%', objectFit: 'cover'}} /><AbsoluteFill style={{background: 'linear-gradient(180deg, rgba(3,15,9,.1) 25%, rgba(3,15,9,.92) 80%)'}} /></>}
    <div style={{position: 'absolute', top: 56, right: 64, left: 64, display: 'flex', justifyContent: 'space-between', alignItems: 'center', direction: 'ltr'}}>
      <Logo size={58} on={light && kind !== 'cover' ? 'light' : 'dark'} />
      {n !== undefined && total !== undefined && <div style={{fontFamily: F.body, fontWeight: 700, fontSize: 32, color: light && kind !== 'cover' ? C.soft : C.muted}}>{fa(n)} / {fa(total)}</div>}
    </div>
    <div style={{position: 'absolute', left: 70, right: 70, top: kind === 'cover' ? undefined : 190, bottom: kind === 'cover' ? 110 : undefined, display: 'flex', flexDirection: 'column', gap: 34}}>
      {kind === 'tip' && n !== undefined && <div style={{fontFamily: F.display, fontWeight: 900, fontSize: 150, color: light ? C.primary : C.lime, lineHeight: 1}}>{fa(n - 1)}</div>}
      <PopText text={title} hl={titleHl} size={kind === 'cover' ? 104 : 84} still color={kind === 'cover' ? C.white : fg} align={kind === 'cover' || kind === 'cta' ? 'center' : 'right'} hlBg={light && kind !== 'cover' ? C.primary : C.lime} hlColor={light && kind !== 'cover' ? C.white : C.ink} />
      {body && <div style={{fontFamily: F.body, fontWeight: 500, fontSize: 44, lineHeight: 1.8, color: kind === 'cover' ? C.light : (light ? '#2b3b35' : C.light), textAlign: kind === 'cover' || kind === 'cta' ? 'center' : 'right'}}>{body}</div>}
      {kind === 'list' && items && <div style={{display: 'flex', flexDirection: 'column', gap: 22, marginTop: 10}}>{items.map((it, i) => <div key={i} style={{display: 'flex', gap: 22, alignItems: 'center'}}>
        <div style={{width: 64, height: 64, borderRadius: 18, background: light ? C.primary : C.lime, color: light ? C.white : C.ink, display: 'grid', placeItems: 'center', fontFamily: F.display, fontWeight: 900, fontSize: 36, flexShrink: 0}}>{fa(i + 1)}</div>
        <div style={{fontFamily: F.display, fontWeight: 800, fontSize: 52, color: fg}}>{it}</div></div>)}</div>}
      {kind === 'image' && image && <div style={{height: 640, borderRadius: 36, overflow: 'hidden'}}><Img src={src(image)} style={{width: '100%', height: '100%', objectFit: 'cover'}} /></div>}
      {kind === 'compare' && before && after && <div style={{display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, height: 640}}>
        {[[after, '✦ با وطن', true], [before, 'عکس موبایل', false]].map(([im, lb, good], i) => <div key={i} style={{position: 'relative', borderRadius: 30, overflow: 'hidden'}}>
          <Img src={src(im as string)} style={{width: '100%', height: '100%', objectFit: 'cover'}} />
          <div style={{position: 'absolute', top: 18, right: 18}}><Pill size={28} bg={good ? C.lime : 'rgba(255,255,255,.92)'}>{lb as string}</Pill></div></div>)}</div>}
      {kind === 'cta' && <div style={{display: 'flex', justifyContent: 'center', marginTop: 20}}><Pill size={48}>لینک در بایو</Pill></div>}
    </div>
    {kind !== 'cta' && kind !== 'cover' && n !== undefined && total !== undefined && n < total && <div style={{position: 'absolute', bottom: 60, left: 64, fontFamily: F.body, fontWeight: 700, fontSize: 32, color: light ? C.soft : C.muted}}>ورق بزن ←</div>}
  </AbsoluteFill>;
};

/* ── پست تکی ۴:۵ ─────────────────────────────────────────── */
export type PosterProps = {title: string; titleHl?: string[]; body?: string; image?: string; badge?: string; light?: boolean};
export const Poster: React.FC<PosterProps> = (p) => <Slide kind={p.image ? 'cover' : 'cta'} title={p.title} titleHl={p.titleHl} body={p.body} image={p.image} light={p.light} />;

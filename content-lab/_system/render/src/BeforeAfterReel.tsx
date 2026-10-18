import React from 'react';
import {AbsoluteFill, Audio, Img, interpolate, Sequence, spring, useCurrentFrame, useVideoConfig, Easing} from 'remotion';
import {C} from './brand';
import {Fonts, KenBurns, Logo, Pill, PopText, Grain, src} from './parts';

export type Pair = {before: string; after: string; label?: string};
export type BAProps = {
  hook: string; hookHl?: string[]; revealText: string; revealHl?: string[];
  pairs: Pair[]; benefits: string[]; punch: string; punchHl?: string[];
  cta: string; ctaSub: string; music?: string; musicStart?: number; musicVolume?: number;
};
export const T = {hook: 66, reveal: 84, pair: 48, benefit: 30, punch: 66, cta: 90};
export const baDuration = (p: BAProps) => T.hook + T.reveal + Math.max(p.pairs.length - 1, 0) * T.pair + p.benefits.length * T.benefit + T.punch + T.cta;

const Tag: React.FC<{text: string; good?: boolean}> = ({text, good}) => {
  const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const s = spring({frame: f - 4, fps, config: {damping: 12}});
  return <div style={{position: 'absolute', top: 290, right: 70, transform: `scale(${s})`}}>
    <Pill bg={good ? C.accent : 'rgba(255,255,255,.92)'} color={C.dark} size={38}>{text}</Pill></div>;
};

const Wipe: React.FC<{before: string; after: string; dur: number}> = ({before, after, dur}) => {
  const f = useCurrentFrame();
  const p = interpolate(f, [8, 8 + dur * 0.4], [0, 100], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.inOut(Easing.cubic)});
  return <AbsoluteFill>
    <KenBurns src={before} dur={dur} zoom={[1.02, 1.06]} />
    <AbsoluteFill style={{clipPath: `inset(0 0 0 ${100 - p}%)`}}><KenBurns src={after} dur={dur} zoom={[1.08, 1.0]} /></AbsoluteFill>
    {p > 0 && p < 100 && <div style={{position: 'absolute', top: 0, bottom: 0, left: `${100 - p}%`, width: 8, background: C.accent, boxShadow: `0 0 40px ${C.accent}`}} />}
  </AbsoluteFill>;
};

const PairQuick: React.FC<{pair: Pair}> = ({pair}) => {
  const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const s = spring({frame: f - 6, fps, config: {damping: 15}});
  return <AbsoluteFill>
    <KenBurns src={pair.after} dur={T.pair} zoom={[1.12, 1.02]} />
    <Grain />
    <div style={{position: 'absolute', left: 60, bottom: 470, width: 300, height: 380, borderRadius: 28, overflow: 'hidden',
      border: `6px solid ${C.white}`, transform: `translateX(${(1 - s) * -420}px) rotate(-4deg)`, boxShadow: '0 20px 60px rgba(0,0,0,.5)'}}>
      <Img src={src(pair.before)} style={{width: '100%', height: '100%', objectFit: 'cover'}} />
      <div style={{position: 'absolute', bottom: 12, left: 0, right: 0, textAlign: 'center'}}><Pill bg="rgba(0,0,0,.7)" color={C.white} size={26}>قبل</Pill></div>
    </div>
    {pair.label && <div style={{position: 'absolute', top: 290, right: 70}}><Pill size={36}>{pair.label}</Pill></div>}
  </AbsoluteFill>;
};

const Benefit: React.FC<{text: string; i: number}> = ({text, i}) => (
  <AbsoluteFill style={{background: i % 2 ? C.dark : C.primary, justifyContent: 'center', alignItems: 'center', padding: '0 90px'}}>
    <div style={{fontFamily: 'YB', fontWeight: 900, fontSize: 40, color: C.accent, marginBottom: 24, direction: 'rtl'}}>{'✕'}</div>
    <PopText text={text} size={110} stagger={2} />
  </AbsoluteFill>
);

export const BeforeAfterReel: React.FC<BAProps> = (p) => {
  let t = 0; const at = (d: number) => { const s = t; t += d; return s; };
  const sHook = at(T.hook), sReveal = at(T.reveal);
  const sPairs = p.pairs.slice(1).map(() => at(T.pair));
  const sBen = p.benefits.map(() => at(T.benefit));
  const sPunch = at(T.punch), sCta = at(T.cta);
  const first = p.pairs[0];
  return (
    <AbsoluteFill style={{background: C.dark}}>
      <Fonts />
      {p.music && <Audio src={src(p.music)} startFrom={Math.round((p.musicStart ?? 0) * 30)} volume={(f) => interpolate(f, [0, 10, t - 25, t], [0, p.musicVolume ?? 0.9, p.musicVolume ?? 0.9, 0], {extrapolateRight: 'clamp'})} />}
      <Sequence from={sHook} durationInFrames={T.hook}>
        <KenBurns src={first.before} dur={T.hook} zoom={[1.0, 1.08]} /><Grain />
        <Tag text="📱 عکس موبایل" />
        <AbsoluteFill style={{justifyContent: 'center', padding: '0 80px', top: 120}}><PopText text={p.hook} hl={p.hookHl} size={96} /></AbsoluteFill>
      </Sequence>
      <Sequence from={sReveal} durationInFrames={T.reveal}>
        <Wipe before={first.before} after={first.after} dur={T.reveal} /><Grain />
        <Sequence from={30}><Tag text="✦ با وطن" good /></Sequence>
        <Sequence from={34}><AbsoluteFill style={{justifyContent: 'flex-end', paddingBottom: 480, paddingLeft: 80, paddingRight: 80}}><PopText text={p.revealText} hl={p.revealHl} size={84} /></AbsoluteFill></Sequence>
      </Sequence>
      {p.pairs.slice(1).map((pr, i) => <Sequence key={i} from={sPairs[i]} durationInFrames={T.pair}><PairQuick pair={pr} /></Sequence>)}
      {p.benefits.map((b, i) => <Sequence key={'b' + i} from={sBen[i]} durationInFrames={T.benefit}><Benefit text={b} i={i} /></Sequence>)}
      <Sequence from={sPunch} durationInFrames={T.punch}>
        <AbsoluteFill style={{background: C.primary, justifyContent: 'center', padding: '0 80px'}}><PopText text={p.punch} hl={p.punchHl} size={104} stagger={4} /></AbsoluteFill>
      </Sequence>
      <Sequence from={sCta} durationInFrames={T.cta}><CtaCard cta={p.cta} sub={p.ctaSub} /></Sequence>
    </AbsoluteFill>
  );
};

export const CtaCard: React.FC<{cta: string; sub: string}> = ({cta, sub}) => {
  const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const s = spring({frame: f, fps, config: {damping: 14}});
  const pulse = 1 + Math.sin(f / 5) * 0.03;
  return <AbsoluteFill style={{background: `radial-gradient(circle at 50% 40%, ${C.primary} 0%, ${C.dark} 70%)`, justifyContent: 'center', alignItems: 'center', gap: 60}}>
    <div style={{transform: `scale(${s})`}}><Logo size={130} /></div>
    <PopText text={cta} size={86} start={6} />
    <div style={{transform: `scale(${pulse})`, opacity: s}}><Pill size={50}>{sub}</Pill></div>
    <div style={{fontFamily: 'YB', fontWeight: 700, fontSize: 40, color: C.muted, direction: 'ltr', opacity: s}}>aivatan.com</div>
  </AbsoluteFill>;
};

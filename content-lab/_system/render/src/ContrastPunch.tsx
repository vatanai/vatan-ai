import React from 'react';
import {AbsoluteFill, Audio, Img, interpolate, Sequence, spring, useCurrentFrame, useVideoConfig, Easing} from 'remotion';
import {C} from './brand';
import {Fonts, Logo, Pill, PopText, src} from './parts';
import {CtaCard} from './BeforeAfterReel';

// Scene types inspired by ref style "contrast-punch": alternating dark/light, one highlighted word, product card pops.
export type Scene =
  | {type: 'text'; text: string; hl?: string[]; bg?: 'dark' | 'light' | 'green'; dur: number; size?: number; kicker?: string; lines?: string[]}
  | {type: 'card'; image: string; text?: string; hl?: string[]; tag?: string; bg?: 'dark' | 'light' | 'green'; dur: number}
  | {type: 'grid'; images: string[]; text?: string; hl?: string[]; dur: number}
  | {type: 'cta'; text: string; sub: string; dur: number};
export type CPProps = {scenes: Scene[]; music?: string; musicStart?: number; musicVolume?: number};
export const cpDuration = (p: CPProps) => p.scenes.reduce((a, s) => a + s.dur, 0);

const bgOf = (b?: string) => (b === 'light' ? C.light : b === 'green' ? C.primary : C.dark);
const fgOf = (b?: string) => (b === 'light' ? C.dark : C.white);

const Grid: React.FC<{bg?: string}> = ({bg}) => (
  <AbsoluteFill style={{backgroundImage: `linear-gradient(${bg === 'light' ? 'rgba(0,0,0,.05)' : 'rgba(255,255,255,.05)'} 1px, transparent 1px), linear-gradient(90deg, ${bg === 'light' ? 'rgba(0,0,0,.05)' : 'rgba(255,255,255,.05)'} 1px, transparent 1px)`, backgroundSize: '90px 90px'}} />
);

const Flash: React.FC = () => {
  const f = useCurrentFrame();
  return <AbsoluteFill style={{background: C.white, opacity: interpolate(f, [0, 4], [0.35, 0], {extrapolateRight: 'clamp'}), pointerEvents: 'none'}} />;
};

const CardScene: React.FC<{s: Extract<Scene, {type: 'card'}>}> = ({s}) => {
  const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const k = spring({frame: f, fps, config: {damping: 13, stiffness: 120}});
  const z = interpolate(f, [0, s.dur], [1, 1.06]);
  return <AbsoluteFill style={{background: bgOf(s.bg), alignItems: 'center'}}>
    <Grid bg={s.bg} />
    <div style={{position: 'absolute', top: 330, width: 760, height: 950, borderRadius: 44, overflow: 'hidden', transform: `translateY(${(1 - k) * 260}px) rotate(${(1 - k) * -6}deg) scale(${z})`,
      boxShadow: '0 40px 90px rgba(0,0,0,.45)', border: `6px solid ${s.bg === 'light' ? C.white : 'rgba(255,255,255,.12)'}`}}>
      <Img src={src(s.image)} style={{width: '100%', height: '100%', objectFit: 'cover'}} />
      {s.tag && <div style={{position: 'absolute', top: 26, right: 26}}><Pill size={34}>{s.tag}</Pill></div>}
    </div>
    {s.text && <div style={{position: 'absolute', top: 1320, left: 70, right: 70}}><PopText text={s.text} hl={s.hl} size={92} color={fgOf(s.bg)} start={5} /></div>}
  </AbsoluteFill>;
};

const GridScene: React.FC<{s: Extract<Scene, {type: 'grid'}>}> = ({s}) => {
  const f = useCurrentFrame(); const {fps} = useVideoConfig();
  return <AbsoluteFill style={{background: C.dark}}>
    <div style={{position: 'absolute', top: 300, left: 70, right: 70, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 22}}>
      {s.images.slice(0, 4).map((im, i) => { const k = spring({frame: f - i * 3, fps, config: {damping: 14}});
        return <div key={i} style={{aspectRatio: '4 / 5', borderRadius: 28, overflow: 'hidden', transform: `scale(${0.6 + 0.4 * k})`, opacity: k}}><Img src={src(im)} style={{width: '100%', height: '100%', objectFit: 'cover'}} /></div>; })}
    </div>
    {s.text && <div style={{position: 'absolute', top: 1330, left: 70, right: 70}}><PopText text={s.text} hl={s.hl} size={76} start={8} /></div>}
  </AbsoluteFill>;
};

const TextScene: React.FC<{s: Extract<Scene, {type: 'text'}>}> = ({s}) => {
  const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const k = spring({frame: f, fps, config: {damping: 16}});
  return <AbsoluteFill style={{background: bgOf(s.bg), justifyContent: 'center', padding: '0 70px'}}>
    <Grid bg={s.bg} />
    {s.kicker && <div style={{textAlign: 'center', marginBottom: 26, direction: 'rtl', fontFamily: 'YB', fontWeight: 500, fontSize: 70, color: s.bg === 'light' ? C.primary : C.muted, opacity: k, transform: `translateY(${(1 - k) * 30}px)`}}>{s.kicker}</div>}
    {s.lines ? <div style={{display: 'flex', flexDirection: 'column', gap: 18}}>{s.lines.map((l, i) => <PopText key={i} text={l} hl={s.hl} size={s.size ?? 128} color={fgOf(s.bg)} start={i * 9} stagger={2} />)}</div>
      : <PopText text={s.text} hl={s.hl} size={s.size ?? 136} color={fgOf(s.bg)} start={s.kicker ? 6 : 0} stagger={3} />}
  </AbsoluteFill>;
};

export const ContrastPunch: React.FC<CPProps> = ({scenes, music, musicStart = 0, musicVolume = 0.9}) => {
  const total = scenes.reduce((a, s) => a + s.dur, 0);
  let t = 0;
  return <AbsoluteFill style={{background: C.dark}}><Fonts />
    {music && <Audio src={src(music)} startFrom={Math.round(musicStart * 30)} volume={(f) => interpolate(f, [0, 6, total - 20, total], [0, musicVolume, musicVolume, 0], {extrapolateRight: 'clamp'})} />}
    {scenes.map((s, i) => { const from = t; t += s.dur;
      return <Sequence key={i} from={from} durationInFrames={s.dur}>
        {s.type === 'text' && <TextScene s={s} />}
        {s.type === 'card' && <CardScene s={s} />}
        {s.type === 'grid' && <GridScene s={s} />}
        {s.type === 'cta' && <CtaCard cta={s.text} sub={s.sub} />}
        {i > 0 && <Flash />}
      </Sequence>; })}
  </AbsoluteFill>;
};

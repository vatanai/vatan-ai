import React from 'react';
import {AbsoluteFill, Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig, Easing} from 'remotion';
import {C, F, fontCss} from './brand';

export const Fonts: React.FC = () => <style>{fontCss}</style>;
export const src = (p: string) => (/^(https?:|data:)/.test(p) ? p : staticFile(p));

// لوگوی رسمی وطن: نشان لیمویی + واژه‌نشان (روشن برای زمینه‌ی تیره، تیره برای زمینه‌ی روشن)
export const Logo: React.FC<{size?: number; on?: 'dark' | 'light'; badgeOnly?: boolean}> = ({size = 90, on = 'dark', badgeOnly = false}) => (
  <div style={{display: 'flex', alignItems: 'center', gap: size * 0.18, direction: 'ltr'}}>
    {!badgeOnly && <Img src={staticFile(on === 'dark' ? 'brand/wordmark-light.png' : 'brand/wordmark-dark.png')} style={{height: size * 0.82, width: 'auto'}} />}
    <Img src={staticFile('brand/badge.png')} style={{width: size, height: size}} />
  </div>
);

// تیتر کلمه‌به‌کلمه (راست‌به‌چپ) با هایلایت
export const PopText: React.FC<{text: string; start?: number; size?: number; color?: string; hl?: string[]; hlBg?: string; hlColor?: string; stagger?: number; font?: string; weight?: number; still?: boolean; align?: 'center' | 'right'}> = ({text, start = 0, size = 92, color = C.white, hl = [], hlBg = C.lime, hlColor = C.ink, stagger = 3, font = F.display, weight = 900, still = false, align = 'center'}) => {
  const f = useCurrentFrame(); const {fps} = useVideoConfig();
  const words = text.split(' ').filter(Boolean);
  return (
    <div style={{direction: 'rtl', display: 'flex', flexWrap: 'wrap', justifyContent: align === 'center' ? 'center' : 'flex-start', gap: `${size * 0.08}px ${size * 0.26}px`, lineHeight: 1.3, textAlign: align}}>
      {words.map((w, i) => {
        const s = still ? 1 : spring({frame: f - start - i * stagger, fps, config: {damping: 14, stiffness: 170}});
        const isHl = hl.some((h) => h && w.includes(h));
        return (
          <span key={i} style={{fontFamily: font, fontWeight: weight, fontSize: size, color: isHl ? hlColor : color, opacity: s,
            transform: `translateY(${(1 - s) * 36}px) scale(${0.88 + s * 0.12})`, display: 'inline-block',
            background: isHl ? hlBg : 'transparent', padding: isHl ? `0 ${size * 0.16}px` : 0, borderRadius: size * 0.16,
            textShadow: isHl ? 'none' : (color === C.white ? '0 6px 30px rgba(0,0,0,.45)' : 'none')}}>{w}</span>
        );
      })}
    </div>
  );
};

export const KenBurns: React.FC<{src: string; from?: number; dur: number; zoom?: [number, number]; pan?: number; fit?: 'cover' | 'contain'}> = ({src: s, from = 0, dur, zoom = [1.04, 1.14], pan = 0, fit = 'cover'}) => {
  const f = useCurrentFrame();
  const p = interpolate(f - from, [0, dur], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.inOut(Easing.quad)});
  const z = zoom[0] + (zoom[1] - zoom[0]) * p;
  return <AbsoluteFill style={{overflow: 'hidden'}}><Img src={src(s)} style={{width: '100%', height: '100%', objectFit: fit, transform: `scale(${z}) translateX(${pan * p}px)`}} /></AbsoluteFill>;
};

export const Vignette: React.FC = () => <AbsoluteFill style={{background: 'radial-gradient(ellipse at center, transparent 45%, rgba(0,0,0,.55) 100%)', pointerEvents: 'none'}} />;
export const Grain = Vignette;

export const Pill: React.FC<{children: React.ReactNode; bg?: string; color?: string; size?: number; font?: string}> = ({children, bg = C.lime, color = C.ink, size = 40, font = F.body}) => (
  <div style={{direction: 'rtl', fontFamily: font, fontWeight: 900, fontSize: size, color, background: bg, padding: `${size * 0.28}px ${size * 0.8}px`, borderRadius: 999, display: 'inline-block', whiteSpace: 'nowrap'}}>{children}</div>
);

export const GridBg: React.FC<{color: string; size?: number}> = ({color, size = 90}) => (
  <AbsoluteFill style={{backgroundImage: `linear-gradient(${color} 1px, transparent 1px), linear-gradient(90deg, ${color} 1px, transparent 1px)`, backgroundSize: `${size}px ${size}px`}} />
);

// خط منحنی ادیتوریال (امضای سبک «داستان برند»)
export const Curve: React.FC<{color: string; dur: number}> = ({color, dur}) => {
  const f = useCurrentFrame();
  const p = interpolate(f, [0, Math.max(dur * 0.6, 1)], [1, 0], {extrapolateRight: 'clamp', easing: Easing.out(Easing.cubic)});
  return <svg width="1080" height="1920" style={{position: 'absolute', inset: 0}} viewBox="0 0 1080 1920">
    <path d="M -80 1500 C 260 1180, 520 1820, 1160 1240" fill="none" stroke={color} strokeWidth="5" strokeDasharray="1600" strokeDashoffset={1600 * p} />
  </svg>;
};

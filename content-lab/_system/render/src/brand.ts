import {staticFile} from 'remotion';
// کیت برند وطن — تنها منبع رنگ و فونت قالب‌ها
export const C = {
  primary: '#16594f', primaryDeep: '#0b2f29', accent: '#C2FD75', lime: '#CFFC00', dark: '#030f09', deep: '#0b231d',
  light: '#f5f5f5', paper: '#f2f1ec', white: '#ffffff', ink: '#0c1a14', muted: '#a9b4c7', soft: '#686E6B', logo: '#0BBF53',
  warn: '#f5923a', red: '#ef4444',
};
export const F = {display: 'Modam', body: 'YB', alt: 'Peyda'};
export const fontCss = `
@font-face{font-family:'YB';src:url('${staticFile('fonts/YekanBakh-Medium.ttf')}');font-weight:500}
@font-face{font-family:'YB';src:url('${staticFile('fonts/YekanBakh-Bold.ttf')}');font-weight:700}
@font-face{font-family:'YB';src:url('${staticFile('fonts/YekanBakh-Heavy.ttf')}');font-weight:900}
@font-face{font-family:'Modam';src:url('${staticFile('fonts/ModamFaNum-Light.ttf')}');font-weight:300}
@font-face{font-family:'Modam';src:url('${staticFile('fonts/ModamFaNum-Regular.ttf')}');font-weight:400}
@font-face{font-family:'Modam';src:url('${staticFile('fonts/ModamFaNum-Medium.ttf')}');font-weight:500}
@font-face{font-family:'Modam';src:url('${staticFile('fonts/ModamFaNum-ExtraBold.ttf')}');font-weight:800}
@font-face{font-family:'Modam';src:url('${staticFile('fonts/ModamFaNum-Black.ttf')}');font-weight:900}
@font-face{font-family:'Peyda';src:url('${staticFile('fonts/Peyda-Black.ttf')}');font-weight:900}
`;
// Reels safe zone (1080x1920): text between y=250..1500, x=60..940
export const SAFE = {top: 250, bottom: 420, left: 60, right: 140};

export type ThemeName = 'punch' | 'editorial' | 'kinetic' | 'techgrid' | 'stage' | 'tactile';
export type Theme = {bgDark: string; bgLight: string; bgBrand: string; text: string; textOnLight: string; hl: string; hlText: string; kicker: string; grid: string; font: string; weight: number; transition: 'flash' | 'slide' | 'cut'};
export const THEMES: Record<ThemeName, Theme> = {
  punch: {bgDark: C.dark, bgLight: C.light, bgBrand: C.primary, text: C.white, textOnLight: C.ink, hl: C.lime, hlText: C.ink, kicker: C.muted, grid: 'rgba(255,255,255,.05)', font: F.display, weight: 900, transition: 'flash'},
  editorial: {bgDark: C.deep, bgLight: C.paper, bgBrand: C.primary, text: C.white, textOnLight: C.ink, hl: C.primary, hlText: C.white, kicker: C.soft, grid: 'rgba(0,0,0,.05)', font: F.display, weight: 800, transition: 'slide'},
  kinetic: {bgDark: C.ink, bgLight: C.white, bgBrand: C.lime, text: C.white, textOnLight: C.ink, hl: C.lime, hlText: C.ink, kicker: C.muted, grid: 'transparent', font: F.display, weight: 900, transition: 'cut'},
  techgrid: {bgDark: '#04241f', bgLight: '#e9f2ef', bgBrand: C.primary, text: C.white, textOnLight: C.ink, hl: C.warn, hlText: C.ink, kicker: '#7fb5a8', grid: 'rgba(194,253,117,.08)', font: F.display, weight: 900, transition: 'flash'},
  stage: {bgDark: C.dark, bgLight: C.light, bgBrand: C.primary, text: C.white, textOnLight: C.ink, hl: C.lime, hlText: C.ink, kicker: C.muted, grid: 'rgba(255,255,255,.04)', font: F.display, weight: 900, transition: 'slide'},
  tactile: {bgDark: '#2b2620', bgLight: '#efe6d8', bgBrand: C.primary, text: C.white, textOnLight: '#2a2118', hl: C.primary, hlText: C.white, kicker: '#8a7a66', grid: 'transparent', font: F.display, weight: 800, transition: 'slide'},
};

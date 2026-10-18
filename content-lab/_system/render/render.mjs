// رندرگر کارخانه‌ی محتوای وطن
// usage A: node render.mjs <compositionId> <props.json> <out> [--still] [--frame=N]
// usage B: node render.mjs --batch <batch.json>   batch = {"outputs":[{"comp","props","out","still":bool,"frame":N,"allFrames":bool}]}
import {bundle} from '@remotion/bundler';
import {renderMedia, renderStill, selectComposition} from '@remotion/renderer';
import fs from 'fs'; import path from 'path';

const macChrome = ['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', '/Applications/Chromium.app/Contents/MacOS/Chromium'].find(p => fs.existsSync(p));
const browserExecutable = process.env.CHROME || macChrome || undefined;
const chromeMode = browserExecutable && !/headless/i.test(browserExecutable) ? 'chrome-for-testing' : undefined;

const args = process.argv.slice(2);
let outputs;
if (args[0] === '--batch') outputs = JSON.parse(fs.readFileSync(args[1], 'utf8')).outputs;
else {
  const [id, propsFile, out, ...rest] = args;
  const fr = rest.find(r => r.startsWith('--frame='));
  outputs = [{comp: id, props: JSON.parse(fs.readFileSync(propsFile, 'utf8')), out, still: rest.includes('--still'), frame: fr ? +fr.split('=')[1] : undefined}];
}
const t0 = Date.now();
const serveUrl = await bundle({entryPoint: path.resolve('src/index.ts'), publicDir: path.resolve('public')});
console.log(`bundle ${Date.now() - t0}ms`);
for (const o of outputs) {
  const composition = await selectComposition({serveUrl, id: o.comp, inputProps: o.props, browserExecutable, chromeMode});
  fs.mkdirSync(path.dirname(o.out), {recursive: true});
  if (o.allFrames) {
    for (let f = 0; f < composition.durationInFrames; f++) {
      const out = o.out.replace('{n}', String(f + 1).padStart(2, '0'));
      await renderStill({composition, serveUrl, output: out, inputProps: o.props, frame: f, browserExecutable, chromeMode, imageFormat: 'jpeg', jpegQuality: 92});
      console.log('done', out);
    }
  } else if (o.still) {
    await renderStill({composition, serveUrl, output: o.out, inputProps: o.props, frame: o.frame ?? composition.durationInFrames - 1, browserExecutable, chromeMode, ...(o.out.endsWith('.jpg') ? {imageFormat: 'jpeg', jpegQuality: 92} : {imageFormat: 'png'})});
    console.log('done', o.out);
  } else {
    await renderMedia({composition, serveUrl, codec: 'h264', outputLocation: o.out, inputProps: o.props, browserExecutable, chromeMode, crf: 18, audioBitrate: '192k', concurrency: 2,
      onProgress: ({progress}) => { if (Math.round(progress * 100) % 20 === 0) process.stdout.write(`progress ${Math.round(progress * 100)}\n`); }});
    console.log('done', o.out, composition.durationInFrames);
  }
}
console.log(`total ${Date.now() - t0}ms`);

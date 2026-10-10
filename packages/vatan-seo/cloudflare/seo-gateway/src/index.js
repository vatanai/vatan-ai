/**
 * Vatan SEO Gateway — پل امن سرور ← سرویس‌های گوگل (فقط اگر سرور دسترسی مستقیم ندارد)
 * نحوه‌ی استفاده: https://<worker>/www.googleapis.com/webmasters/v3/... ← https://www.googleapis.com/webmasters/v3/...
 * فقط میزبان‌های لیست سفید عبور می‌کنند و فقط با هدر X-Vatan-Gateway-Key.
 */
const ALLOWED_HOSTS = new Set([
  'www.googleapis.com',
  'oauth2.googleapis.com',
  'searchconsole.googleapis.com',
  'analyticsdata.googleapis.com',
  'suggestqueries.google.com',
]);

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    if (url.pathname === '/health') return Response.json({ ok: true, service: 'vatan-seo-gateway' });

    const supplied = request.headers.get('X-Vatan-Gateway-Key') || '';
    if (!env.GATEWAY_SHARED_SECRET || !timingSafeEqual(supplied, env.GATEWAY_SHARED_SECRET)) {
      return Response.json({ error: 'Unauthorized' }, { status: 401 });
    }
    const [, host, ...rest] = url.pathname.split('/');
    if (!ALLOWED_HOSTS.has(host)) return Response.json({ error: 'Host not allowed' }, { status: 403 });

    const upstream = new URL('https://' + host + '/' + rest.join('/') + url.search);
    const headers = new Headers(request.headers);
    headers.delete('X-Vatan-Gateway-Key');
    headers.delete('Host');
    return fetch(upstream, { method: request.method, headers, body: ['GET', 'HEAD'].includes(request.method) ? undefined : request.body, redirect: 'manual' });
  },
};

function timingSafeEqual(a, b) {
  if (a.length !== b.length) return false;
  let m = 0;
  for (let i = 0; i < a.length; i++) m |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return m === 0;
}

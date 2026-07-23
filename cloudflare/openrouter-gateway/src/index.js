const OPENROUTER_ORIGIN = 'https://openrouter.ai';
const ALLOWED_PATHS = new Set([
  '/api/v1/images',
  '/api/v1/chat/completions',
]);

export default {
  async fetch(request, env) {
    const url = new URL(request.url);

    if (request.method === 'GET' && url.pathname === '/health') {
      return Response.json({ ok: true, service: 'vatan-openrouter-gateway' });
    }

    if (request.method !== 'POST' || !ALLOWED_PATHS.has(url.pathname)) {
      return Response.json({ success: false, error: 'Not found' }, { status: 404 });
    }

    if (!env.OPENROUTER_API_KEY || !env.GATEWAY_SHARED_SECRET) {
      return Response.json({ success: false, error: 'Gateway is not configured' }, { status: 503 });
    }

    const suppliedSecret = request.headers.get('X-Vatan-Gateway-Key');
    if (!suppliedSecret || !timingSafeEqual(suppliedSecret, env.GATEWAY_SHARED_SECRET)) {
      return Response.json({ success: false, error: 'Unauthorized' }, { status: 401 });
    }

    const upstreamUrl = new URL(url.pathname + url.search, OPENROUTER_ORIGIN);
    const headers = new Headers(request.headers);
    headers.delete('X-Vatan-Gateway-Key');
    headers.set('Authorization', `Bearer ${env.OPENROUTER_API_KEY}`);
    headers.set('HTTP-Referer', 'https://aivatan.com');
    headers.set('X-Title', 'Vatan AI');
    headers.set('Content-Type', 'application/json');

    return fetch(upstreamUrl, {
      method: 'POST',
      headers,
      body: request.body,
      redirect: 'manual',
    });
  },
};

function timingSafeEqual(left, right) {
  if (left.length !== right.length) return false;

  let mismatch = 0;
  for (let index = 0; index < left.length; index += 1) {
    mismatch |= left.charCodeAt(index) ^ right.charCodeAt(index);
  }

  return mismatch === 0;
}

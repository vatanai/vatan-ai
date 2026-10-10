const OPENROUTER_ORIGIN = 'https://openrouter.ai';
// POST: تولید تصویر و چت · GET: فهرست مدل‌ها و اعتبار کلید (برای موتور سئو)
const ALLOWED = new Map([
  ['/api/v1/images', 'POST'],
  ['/api/v1/chat/completions', 'POST'],
  ['/api/v1/models', 'GET'],
  ['/api/v1/key', 'GET'],
]);

export default {
  async fetch(request, env) {
    const url = new URL(request.url);

    if (request.method === 'GET' && url.pathname === '/health') {
      return Response.json({ ok: true, service: 'vatan-openrouter-gateway' });
    }

    if (ALLOWED.get(url.pathname) !== request.method) {
      return Response.json({ success: false, error: 'Not found' }, { status: 404 });
    }

    if (!env.OPENROUTER_API_KEY || !env.GATEWAY_SHARED_SECRET) {
      return Response.json({ success: false, error: 'Gateway is not configured' }, { status: 503 });
    }

    const suppliedSecret = request.headers.get('X-Vatan-Gateway-Key');
    if (!suppliedSecret || !timingSafeEqual(suppliedSecret, env.GATEWAY_SHARED_SECRET)) {
      return Response.json({ success: false, error: 'Unauthorized' }, { status: 401 });
    }

    // کلید اختصاصی کلاینت (مثلاً کلید سئو با سقف خرج جدا) — فقط بعد از تأیید Secret مشترک پذیرفته می‌شود
    const clientKey = request.headers.get('X-Vatan-Client-Key');
    const apiKey = clientKey && /^sk-or-[A-Za-z0-9_-]{20,}$/.test(clientKey) ? clientKey : env.OPENROUTER_API_KEY;

    const upstreamUrl = new URL(url.pathname + url.search, OPENROUTER_ORIGIN);
    const headers = new Headers(request.headers);
    headers.delete('X-Vatan-Gateway-Key');
    headers.delete('X-Vatan-Client-Key');
    headers.set('Authorization', `Bearer ${apiKey}`);
    headers.set('HTTP-Referer', 'https://aivatan.com');
    headers.set('X-Title', request.headers.get('X-Title') || 'Vatan AI');
    if (request.method === 'POST') headers.set('Content-Type', 'application/json');

    return fetch(upstreamUrl, {
      method: request.method,
      headers,
      body: request.method === 'POST' ? request.body : undefined,
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

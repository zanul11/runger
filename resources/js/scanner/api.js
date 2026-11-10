/** Klien API timing (same-origin, token Sanctum bearer). */
const BASE = '/api/timing';

export class ApiError extends Error {
    constructor(status, body) {
        super(body?.message || `HTTP ${status}`);
        this.status = status;
        this.body = body;
    }

    /** Error jaringan / server → boleh dicoba lagi. 4xx = jawaban final. */
    get retryable() {
        return this.status === 0 || this.status === 429 || this.status >= 500;
    }
}

export async function api(path, { method = 'GET', body, token, timeout = 15000 } = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeout);

    let res;
    try {
        res = await fetch(BASE + path, {
            method,
            headers: {
                Accept: 'application/json',
                ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
            body: body !== undefined ? JSON.stringify(body) : undefined,
            signal: controller.signal,
            cache: 'no-store',
        });
    } catch (e) {
        throw new ApiError(0, { message: 'Tidak ada koneksi ke server.' });
    } finally {
        clearTimeout(timer);
    }

    const data = await res.json().catch(() => null);
    if (!res.ok) throw new ApiError(res.status, data);

    return data;
}

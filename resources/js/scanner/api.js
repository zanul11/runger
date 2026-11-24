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

    // FormData (upload file) dikirim apa adanya — browser yang mengisi Content-Type multipart.
    const isForm = body instanceof FormData;
    let res;
    try {
        res = await fetch(BASE + path, {
            method,
            headers: {
                Accept: 'application/json',
                ...(body !== undefined && !isForm ? { 'Content-Type': 'application/json' } : {}),
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
            body: body === undefined ? undefined : isForm ? body : JSON.stringify(body),
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

/** GET file ber-token (kwitansi, export) → Blob. */
export async function apiBlob(path, { token, timeout = 30000 } = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeout);
    let res;
    try {
        res = await fetch(BASE + path, {
            headers: { ...(token ? { Authorization: `Bearer ${token}` } : {}) },
            signal: controller.signal,
            cache: 'no-store',
        });
    } catch (e) {
        throw new ApiError(0, { message: 'Tidak ada koneksi ke server.' });
    } finally {
        clearTimeout(timer);
    }
    if (!res.ok) throw new ApiError(res.status, await res.json().catch(() => null));

    return res.blob();
}

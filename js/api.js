/* ─────────────────────────────────────────────────────────────────────────────
 * Tara Pangasinan — AJAX Wrapper (js/api.js)
 *
 * Single source of truth for all frontend ↔ backend communication.
 * Refactored to modern ES6+ Vanilla JavaScript.
 *
 * Include BEFORE any page script that calls Api.*:
 *   <script src="js/api.js"></script>
 *   <script src="js/main.js"></script>
 *
 * Public API:
 *   await Api.get('spots/list.php', { category: 'Beach' })   → Promise<data>
 *   await Api.post('auth/login.php', { email, password })     → Promise<data>
 *   Api.BASE                                            → '/tara-pangasinan/api/'
 *   Api.ApiError                                        → Error subclass
 * ───────────────────────────────────────────────────────────────────────────── */
(() => {
    'use strict';

    /* ── Base URL ─────────────────────────────────────────────────────────────
     * Auto-detects the project root from the current page's pathname.
     * Works whether served from / or a subfolder like /tara-pangasinan/.
     * ─────────────────────────────────────────────────────────────────────── */
    const BASE = (() => {
        const path = window.location.pathname;
        const root = path.substring(0, path.lastIndexOf('/') + 1);
        return `${root}api/`;
    })();

    /* ── Custom Error Class ───────────────────────────────────────────────────
     * Carries structured fields from the server's JSON error envelope.
     * ─────────────────────────────────────────────────────────────────────── */
    class ApiError extends Error {
        constructor(message, code, status, details) {
            super(message);
            this.name    = 'ApiError';
            this.code    = code;
            this.status  = status;
            this.details = details;
        }
    }

    /* ── Core Request Function ────────────────────────────────────────────────
     * @param {string} method  'GET' or 'POST'
     * @param {string} path    Relative to /api/ (e.g. 'spots/list.php')
     * @param {object} body    For GET: becomes query-string params.
     *                         For POST: JSON-stringified as the request body.
     * @returns {Promise}      Resolves with the `data` field from the envelope.
     *                         Rejects with an ApiError on any failure.
     * ─────────────────────────────────────────────────────────────────────── */
    const request = async (method, path, body) => {
        let url = `${BASE}${path}`;
        const init = {
            method,
            credentials: 'same-origin', // always send the PHPSESSID cookie
            headers: { 'Accept': 'application/json' }
        };

        if (method === 'GET' && body) {
            const qs = new URLSearchParams(body).toString();
            url += url.includes('?') ? `&${qs}` : `?${qs}`;
        } else if (method !== 'GET') {
            if (body instanceof FormData) {
                // DON'T set Content-Type header so browser adds boundary
                init.body = body;
            } else {
                init.headers['Content-Type'] = 'application/json';
                init.body = JSON.stringify(body || {});
            }
        }

        try {
            const res = await fetch(url, init);
            
            let json;
            try {
                json = await res.json();
            } catch (parseError) {
                throw new ApiError('Server returned a non-JSON response.', 'invalid_response', res.status);
            }

            if (!json.success) {
                throw new ApiError(
                    json.error || 'Request failed.',
                    json.code || 'unknown',
                    res.status,
                    json.details || null
                );
            }

            return json.data !== undefined ? json.data : null;
        } catch (err) {
            // Rethrow ApiErrors directly, otherwise wrap network errors
            if (err instanceof ApiError) throw err;
            throw new ApiError(`Network or Fetch Error: ${err.message}`, 'network_error', 0);
        }
    };

    /* ── Public Surface ───────────────────────────────────────────────────── */
    window.Api = {
        /**
         * Perform a GET request.
         * @param {string} path   e.g. 'spots/list.php'
         * @param {object} query  Optional key/value pairs appended as ?param=value
         */
        get: (path, query) => request('GET', path, query),

        /**
         * Perform a POST request with a JSON body.
         * @param {string} path  e.g. 'auth/login.php'
         * @param {object} body  Payload object
         */
        post: (path, body) => request('POST', path, body),

        ApiError,
        BASE
    };

})();

/* ─────────────────────────────────────────────────────────────────────────────
 * Tara Pangasinan — AJAX Wrapper (js/api.js)
 *
 * Single source of truth for all frontend ↔ backend communication.
 *
 * Include BEFORE any page script that calls Api.*:
 *   <script src="js/api.js"></script>
 *   <script src="js/main.js"></script>
 *
 * Public API:
 *   Api.get('spots/list.php', { category: 'Beach' })   → Promise<data>
 *   Api.post('auth/login.php', { email, password })     → Promise<data>
 *   Api.BASE                                            → '/tara-pangasinan/api/'
 *   Api.ApiError                                        → Error subclass
 * ───────────────────────────────────────────────────────────────────────────── */
(function (global) {
    'use strict';

    /* ── Base URL ─────────────────────────────────────────────────────────────
     * Auto-detects the project root from the current page's pathname.
     * Works whether served from / or a subfolder like /tara-pangasinan/.
     * Example: on http://localhost/tara-pangasinan/home.html
     *   pathname  → /tara-pangasinan/home.html
     *   root      → /tara-pangasinan/
     *   BASE      → /tara-pangasinan/api/
     * ─────────────────────────────────────────────────────────────────────── */
    var BASE = (function () {
        var path = window.location.pathname;
        var root = path.substring(0, path.lastIndexOf('/') + 1);
        return root + 'api/';
    })();

    /* ── Custom Error Class ───────────────────────────────────────────────────
     * Carries structured fields from the server's JSON error envelope.
     * ─────────────────────────────────────────────────────────────────────── */
    function ApiError(message, code, status, details) {
        this.name    = 'ApiError';
        this.message = message;
        this.code    = code;
        this.status  = status;
        this.details = details;
    }
    ApiError.prototype = Object.create(Error.prototype);
    ApiError.prototype.constructor = ApiError;

    /* ── Core Request Function ────────────────────────────────────────────────
     * @param {string} method  'GET' or 'POST'
     * @param {string} path    Relative to /api/ (e.g. 'spots/list.php')
     * @param {object} body    For GET: becomes query-string params.
     *                         For POST: JSON-stringified as the request body.
     * @returns {Promise}      Resolves with the `data` field from the envelope.
     *                         Rejects with an ApiError on any failure.
     * ─────────────────────────────────────────────────────────────────────── */
    function request(method, path, body) {
        var url  = BASE + path;
        var init = {
            method:      method,
            credentials: 'same-origin',  // always send the PHPSESSID cookie
            headers:     { 'Accept': 'application/json' }
        };

        if (method === 'GET' && body) {
            // Append object properties as query-string parameters
            var qs = new URLSearchParams(body).toString();
            url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
        } else if (method !== 'GET') {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(body || {});
        }

        return fetch(url, init)
            .then(function (res) {
                return res.json()
                    .catch(function () {
                        // Server returned something that isn't JSON
                        throw new ApiError(
                            'Server returned a non-JSON response.',
                            'invalid_response',
                            res.status
                        );
                    })
                    .then(function (json) {
                        if (!json.success) {
                            throw new ApiError(
                                json.error   || 'Request failed.',
                                json.code    || 'unknown',
                                res.status,
                                json.details || null
                            );
                        }
                        // Return the payload, or null if the endpoint sends no data
                        return json.data !== undefined ? json.data : null;
                    });
            });
    }

    /* ── Public Surface ───────────────────────────────────────────────────── */
    global.Api = {
        /**
         * Perform a GET request.
         * @param {string} path   e.g. 'spots/list.php'
         * @param {object} query  Optional key/value pairs appended as ?param=value
         */
        get: function (path, query) {
            return request('GET', path, query);
        },

        /**
         * Perform a POST request with a JSON body.
         * @param {string} path  e.g. 'auth/login.php'
         * @param {object} body  Payload object
         */
        post: function (path, body) {
            return request('POST', path, body);
        },

        ApiError: ApiError,
        BASE:     BASE
    };

})(window);

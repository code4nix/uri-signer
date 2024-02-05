<?php

declare(strict_types=1);

/*
 * This file is part of Uri Signer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Marko Cupic <m.cupic@gmx.ch>
 *
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/code4nix/uri-signer
 */

namespace Code4Nix\UriSigner;

use Symfony\Component\HttpFoundation\Request;

/**
 * Signs URIs with optional timeout.
 */
readonly class UriSigner
{
    /**
     * @param string $secret    A secret
     * @param string $parameter Query string parameter to use
     *                          param int $expires defines the time in seconds until the link has expired
     */
    public function __construct(
        private string $secret,
        private string $parameter = '_hash',
        private int $expiry = 3600,
    ) {
    }

    /**
     * Signs a URI.
     *
     * The given URI is signed by adding the query string parameter
     * which value depends on the URI, the expiry and the secret.
     *
     * @param int|null $expiry expiration time in seconds (default to 86400)
     *
     * @throws \Exception
     */
    public function sign(string $uri, int|null $expiry = null): string
    {
        // Override from configuration if empty
        $timeout = $expiry ?? $this->expiry;

        $url = parse_url($uri);

        if (isset($url['query'])) {
            parse_str($url['query'], $params);
        } else {
            $params = [];
        }

        if ($timeout < 0) {
            throw new \Exception('Invalid parameter "$expiry". The expiration time must be an integer larger than 0 and indicates how many seconds the url is valid.');
        }

        $strEncodedExpiration = $this->encodeExpiration(time() + $timeout);

        $uri = $this->buildUrl($url, $params).$strEncodedExpiration;
        $params[$this->parameter] = base64_encode($this->computeHash($uri).'.'.$strEncodedExpiration);

        return $this->buildUrl($url, $params);
    }

    /**
     * Checks that a URI contains the correct hash.
     */
    public function check(string $uri): bool
    {
        $url = parse_url($uri);

        if (isset($url['query'])) {
            parse_str($url['query'], $params);
        } else {
            $params = [];
        }

        if (empty($params[$this->parameter])) {
            return false;
        }

        $expirationTime = $this->getExpirationTimeFromHash($params[$this->parameter]);
        $hash = $this->getHashCodeFromHash($params[$this->parameter]);

        // Check if url has expired
        if ($expirationTime < time()) {
            return false;
        }

        unset($params[$this->parameter]);

        $strEncodedExpiration = $this->encodeExpiration($expirationTime);

        return hash_equals($this->computeHash($this->buildUrl($url, $params).$strEncodedExpiration), $hash);
    }

    public function checkRequest(Request $request): bool
    {
        $qs = ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '';

        // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        return $this->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs);
    }

    private function computeHash(string $uri): string
    {
        return base64_encode(hash_hmac('sha256', $uri, $this->secret, true));
    }

    private function buildUrl(array $url, array $params = []): string
    {
        ksort($params, SORT_STRING);
        $url['query'] = http_build_query($params, '', '&');

        $scheme = isset($url['scheme']) ? $url['scheme'].'://' : '';
        $host = $url['host'] ?? '';
        $port = isset($url['port']) ? ':'.$url['port'] : '';
        $user = $url['user'] ?? '';
        $pass = isset($url['pass']) ? ':'.$url['pass'] : '';
        $pass = $user || $pass ? "$pass@" : '';
        $path = $url['path'] ?? '';
        $query = $url['query'] ? '?'.$url['query'] : '';
        $fragment = isset($url['fragment']) ? '#'.$url['fragment'] : '';

        return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
    }

    private function getExpirationTimeFromHash(string $hash): int
    {
        $hash = base64_decode($hash, true);

        $arrHash = explode('.', $hash);

        if (empty($arrHash[1])) {
            return 0;
        }

        $arrSecuritySuffix = json_decode(base64_decode($arrHash[1], true), true);

        if (\is_array($arrSecuritySuffix) && isset($arrSecuritySuffix['expiry'])) {
            return (int) $arrSecuritySuffix['expiry'];
        }

        return 0;
    }

    private function getHashCodeFromHash(string $hash): string
    {
        $hash = base64_decode($hash, true);

        $arrHash = explode('.', $hash);

        return $arrHash[0];
    }

    private function encodeExpiration(int $timeout): string
    {
        return base64_encode(json_encode([
            'expiry' => $timeout,
        ]));
    }
}

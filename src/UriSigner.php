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
     * @param string $secret     A secret
     * @param string $parameter  Query string parameter to use
     * @param int    $expiration defines the time in seconds until the link has expired
     */
    public function __construct(
        #[\SensitiveParameter] private string $secret,
        private string $parameter = '_hash',
        private int $expiration = 3600,
    ) {
    }

    /**
     * Signs a URI.
     *
     * The given URI is signed by adding the query string parameter
     * which value depends on the URI, the expiration and the secret.
     *
     * The default expiration is 86400 seconds (1 day).
     * It can be configured in your config/config.yaml.
     *
     * @see https://github.com/code4nix/uri-signer
     *
     * @param int|null $expiration expiration time in seconds
     *
     * @throws \Exception
     */
    public function sign(string $uri, int|null $expiration = null): string
    {
        $arrUrl = parse_url($uri);

        if (!\is_array($arrUrl)) {
            throw new \Exception('Invalid parameter #1 $uri detected. The parameter should contain a valid URI.');
        }

        // Override from configuration if empty
        $expiration = $expiration ?? $this->expiration;

        if ($expiration < 1) {
            throw new \Exception('Invalid parameter #2 $expiration detected. The expiration time should contain an integer larger than 0 and indicates how many seconds the url is valid.');
        }

        if (isset($arrUrl['query'])) {
            parse_str($arrUrl['query'], $params);
        } else {
            $params = [];
        }

        $expiration = time() + $expiration;

        $params['expiration'] = $expiration;

        $uri = $this->buildUrl($arrUrl, $params);

        $params[$this->parameter] = base64_encode(json_encode([
            'hashed' => $this->computeHash($uri),
            'expiration' => $expiration,
        ]));

        unset($params['expiration']);

        return $this->buildUrl($arrUrl, $params);
    }

    /**
     * Checks that a URI contains the correct hash.
     */
    public function check(string $uri): bool
    {
        $arrUrl = parse_url($uri);

        if (!\is_array($arrUrl)) {
            return false;
        }

        if (isset($arrUrl['query'])) {
            parse_str($arrUrl['query'], $params);
        } else {
            $params = [];
        }

        if (empty($params[$this->parameter])) {
            return false;
        }

        $expiration = $this->getExpirationFromParameter($params[$this->parameter]);
        $hash = $this->getHashedUrlFromParameter($params[$this->parameter]);

        // Check for expired url and empty hash
        if ($expiration < time() || empty($hash)) {
            return false;
        }

        unset($params[$this->parameter]);
        $params['expiration'] = $expiration;

        return hash_equals($this->computeHash($this->buildUrl($arrUrl, $params)), $hash);
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

    private function buildUrl(array $arrUrl, array $params = []): string
    {
        ksort($params, SORT_STRING);
        $arrUrl['query'] = http_build_query($params, '', '&');

        $scheme = isset($arrUrl['scheme']) ? $arrUrl['scheme'].'://' : '';
        $host = $arrUrl['host'] ?? '';
        $port = isset($arrUrl['port']) ? ':'.$arrUrl['port'] : '';
        $user = $arrUrl['user'] ?? '';
        $pass = isset($arrUrl['pass']) ? ':'.$arrUrl['pass'] : '';
        $pass = $user || $pass ? "$pass@" : '';
        $path = $arrUrl['path'] ?? '';
        $query = $arrUrl['query'] ? '?'.$arrUrl['query'] : '';
        $fragment = isset($arrUrl['fragment']) ? '#'.$arrUrl['fragment'] : '';

        return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
    }

    private function getExpirationFromParameter(string $hash): int
    {
        try {
            $arrHash = json_decode(base64_decode($hash, true), true);
            $expiration = (int) $arrHash['expiration'];
        } catch (\Exception $e) {
            return 0;
        }

        return $expiration;
    }

    private function getHashedUrlFromParameter(string $hash): string
    {
        try {
            $arrHash = json_decode(base64_decode($hash, true), true);
            $hashed = trim($arrHash['hashed']);
        } catch (\Exception $e) {
            return '';
        }

        return $hashed;
    }
}

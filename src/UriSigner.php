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

use Code4Nix\UriSigner\Exception\ExpiredLinkException;
use Code4Nix\UriSigner\Exception\InvalidSignatureException;
use Code4Nix\UriSigner\Exception\MalformedUriException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Signs URIs with expiration time support.
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

        // Append the expiration time temporarily to the url
        $params['expiration'] = $expiration;

        $uri = $this->buildUrl($arrUrl, $params);
        $signature = $this->computeHash($uri);

        $params[$this->parameter] = base64_encode(json_encode([
            'signature' => $signature,
            'expiration' => $expiration,
        ]));

        // Return the url without the expiration time
        unset($params['expiration']);

        return $this->buildUrl($arrUrl, $params);
    }

    /**
     * Checks that a URI contains the correct signature.
     */
    public function check(string $uri, bool $throwExceptions = false): bool
    {
        $arrUrl = parse_url($uri);

        if (!\is_array($arrUrl)) {
            if ($throwExceptions) {
                throw new MalformedUriException(sprintf('Malformed URI detected. Parsing the URI failed! "%s".', $uri), 1);
            }

            return false;
        }

        if (isset($arrUrl['query'])) {
            parse_str($arrUrl['query'], $params);
        } else {
            $params = [];
        }

        if (empty($params[$this->parameter])) {
            if ($throwExceptions) {
                throw new MalformedUriException(sprintf('Malformed URI detected. Missing or empty "%s" query parameter! "%s".', $this->parameter, $uri), 2);
            }

            return false;
        }

        $expiration = $this->getExpirationTimeFromParameter($params[$this->parameter]);

        // Check link expiration
        if ($expiration < time()) {
            if ($throwExceptions) {
                throw new ExpiredLinkException(sprintf('The link has expired on "%s".', date('r', $expiration)), 3);
            }

            return false;
        }

        $params['expiration'] = $expiration;

        $signature = $this->getSignatureFromParameter($params[$this->parameter]);

        // Check if there is a signature
        if (empty($signature)) {
            if ($throwExceptions) {
                throw new InvalidSignatureException(sprintf('Signature not found in "%s".', $uri), 4);
            }

            return false;
        }

        unset($params[$this->parameter]);

        $blnValid = hash_equals($this->computeHash($this->buildUrl($arrUrl, $params)), $signature);

        if ($throwExceptions) {
            throw new InvalidSignatureException(sprintf('Invalid signature detected "%s". The URI could have been tampered.', $signature), 5);
        }

        return $blnValid;
    }

    public function checkRequest(Request $request, bool $throwExceptions = false): bool
    {
        $qs = ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '';

        // we cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        return $this->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().$qs, $throwExceptions);
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

    private function getExpirationTimeFromParameter(string $hash): int
    {
        try {
            $arrHash = json_decode(base64_decode($hash, true), true);
            $expiration = (int) $arrHash['expiration'];
        } catch (\Exception) {
            return 0;
        }

        return $expiration;
    }

    private function getSignatureFromParameter(string $hash): string
    {
        try {
            $arrHash = json_decode(base64_decode($hash, true), true);
            $signature = trim($arrHash['signature']);
        } catch (\Exception) {
            return '';
        }

        return $signature;
    }
}

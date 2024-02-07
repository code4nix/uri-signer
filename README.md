# URI Signer with expiration support for Symfony framework
Create and check signed URIs with expiration time support.
This bundle is a further development of Symfony's [URI Signer](https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/HttpFoundation/UriSigner.php).

## Installation

```
composer require code4nix/uri-signer
```

## Usage

### Create a signed URI
To create a signed URI with an expiration time of 1 day (default), you can use `$this->uriSigner->sign($strUri)`.
You can add a second parameter `$intExpires` to add a custom expiration time. E.g. call `$this->uriSigner->sign($strUri, 600)` to get a URI with an expiration time of 10 minutes.

### Check the signed URI/request
To check the URI you can use `$this->uriSigner->check($strUri)`.

Instead of building a URI you can call `$this->uriSigner->checkRequest($request)` method
 and pass a `Symfony\Component\HttpFoundation\Request` object to check the signature of its related URI. Both methods return boolean `true` if the URI passes validation, or boolean `false` if the URI is invalid or expired.

### Error handling
Alternatively you can run the `check()` or `checkRequest()` methods with a second optional boolean parameter `$this->uriSigner->check($strUri, true)` or `$this->uriSigner->checkRequest($request, true)`, which will raise exceptions if a URI cannot be verified (e.g. has been tampered, has expired or is a malformed URI).

- `MalformedUriException`
- `InvalidSignatureException`
- `ExpiredLinkException`

## Usage in your controller:
```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Code4Nix\UriSigner\Exception\ExpiredLinkException;
use Code4Nix\UriSigner\Exception\InvalidSignatureException;
use Code4Nix\UriSigner\Exception\MalformedUriException;
use Code4Nix\UriSigner\UriSigner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/uri_signer_test", name="uri_signer_test")
 */
class UriSignerTestController extends AbstractController
{

    public function __construct(
        private readonly UriSigner $uriSigner,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Sign a URI with an expiration time of 10 min
        $uri = 'https://foo.bar/test';

        //https://foo.bar/test?_hash=eyJoYXNoZWQiOiJqNXUxeE1NRnpTRU1yRnREc
        $signedUri = $this->uriSigner->sign($uri, 600);

        // For test usage we will create our request object manually.
        $request = Request::create(
            $signedUri,
        );

        $responseText = 'URI is valid.';

        // Use check($signedUri, true) or checkRequest($request, true)
        // If set to true the second boolean parameter will raise exceptions if a URI cannot be verified.
        try {
            $this->uriSigner->checkRequest($request, true); // or $this->uriSigner->check($signedUri, true);
        } catch (MalformedUriException $e) {
            $responseText = 'Malformed URI detected!';
        } catch (ExpiredLinkException $e) {
            $responseText = 'URI has expired!';
        } catch (InvalidSignatureException $e) {
            $responseText = 'Invalid signature detected! The URI could have been tampered.';
        } catch (\Exception $e) {
            $responseText = 'Oh, la, la! Something went wrong ;-(';
        }

        return new Response($responseText);
    }
}
```

## Configuration:

The default expiration and the parameter are configurable:

```yaml
# config/config.yaml
code4nix_uri_signer:
  parameter: '_signed' # default: '_hash'
  expiration: 20 # default: 86400 (1 day)
```

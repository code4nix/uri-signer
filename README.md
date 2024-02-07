# Uri Signer with expiration support for Symfony framework
Create and check signed URLs with expiration time support.
This bundle is a further development of the Symfony's [Uri Signer](https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/HttpFoundation/UriSigner.php).

## Installation

```
composer require code4nix/uri-signer
```

## Usage

### Create a signed URL
To create a signed URL with an expiration time of 1 day (default), you can use `$this->uriSigner->sign($strUri)`.
You can add a second parameter `$intExpires` to add a custom expiration time. Use `$this->uriSigner->sign($strUri, 600)` to get a URL with an expiration time of 10 minutes.

### Check the signed URL/request
To check the URL you can use `$this->uriSigner->check($strUri)`.

Instead of building a URL you can use the `$this->uriSigner->checkRequest($request)` method
 and pass a `Symfony\Component\HttpFoundation\Request` object to check the signature of its related URL. Both methods return boolean `true` if the URL passes the check, or boolean `false` if the URL is invalid or expired.

### Error handling
Alternatively you can run `check()` or `checkRequest()` with a second optional boolean parameter `$this->uriSigner->check($strUri, true)` or `$this->uriSigner->checkRequest($request, true)`, which will raise exceptions if a URL cannot be verified (e.g. has been tampered, has expired or is a malformed URL).

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
        // Sign a URL with an expiration time of 10 min
        $uri = 'https://foo.bar/test';

        //https://foo.bar/test?_hash=eyJoYXNoZWQiOiJqNXUxeE1NRnpTRU1yRnREc
        $signedUri = $this->uriSigner->sign($uri, 600);

        // For test usage we will create our request object manually.
        $request = Request::create(
            $signedUri,
        );

        $responseText = 'URI is valid.';

        // Use check($signedUri, true) or checkRequest($request, true)
        // If set to true the second boolean parameter will raise exceptions if a URL cannot be verified.
        try {
            $this->uriSigner->checkRequest($request, true); // or $this->uriSigner->check($signedUri, true);
        } catch (MalformedUriException $e) {
            $responseText = 'Malformed URL detected!';
        } catch (ExpiredLinkException $e) {
            $responseText = 'URL has expired!';
        } catch (InvalidSignatureException $e) {
            $responseText = 'Invalid signature detected! The URL could have been tampered.';
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

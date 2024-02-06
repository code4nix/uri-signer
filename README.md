# Uri Signer for Symfony framework with expiration
This bundle is a further development of the Symfony Uri Signer. You can specify how long the URL may be valid, when signing it.

**ATTENTION**: This repo is still in early development and subject to changes!

```php
<?php

declare(strict_types=1);

namespace App\Controller;

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
        // Sign an uri with an expiration time of 10 min (default 24 h)
        $uri = 'https://foo.bar/test';

        // Create a signed URL: https://foo.bar/test?_hash=eyJoYXNoZWQiOiJqNXUxeE1NRnpTRU1yRnREc...
        $signedUri = $this->uriSigner->sign($uri, 600);

        // Check the uri
        if ($this->uriSigner->check($signedUri)) {
            $responseText = 'URI is valid';
        } else {
            $responseText = 'URI is invalid';
        }

        // Instead of building an uri you can use the checkRequest() method
        // and pass a Symfony\Component\HttpFoundation\Request object to check
        // the signature of its related URL:

        $request = Request::create(
            $signedUri,
        );

        // Check the request object
        if ($this->uriSigner->checkRequest($request)) {
            $responseText = 'URI is valid';
        } else {
            $responseText = 'URI is invalid';
        }

        return new Response($responseText);
    }
}
```

## Configuration

The default expiration and the parameter are configurable:

```yaml
# config/config.yaml
code4nix_uri_signer:
  parameter: '_signed' # default: '_hash'
  expiration: 20 # default: 86400 (1 day)
```

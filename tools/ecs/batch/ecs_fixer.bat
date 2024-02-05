:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/code4nix/uri-signer/src --fix --config vendor/code4nix/uri-signer/tools/ecs/config.php
php vendor\bin\ecs check vendor/code4nix/uri-signer/contao --fix --config vendor/code4nix/uri-signer/tools/ecs/config.php
php vendor\bin\ecs check vendor/code4nix/uri-signer/config --fix --config vendor/code4nix/uri-signer/tools/ecs/config.php
php vendor\bin\ecs check vendor/code4nix/uri-signer/templates --fix --config vendor/code4nix/uri-signer/tools/ecs/config.php
php vendor\bin\ecs check vendor/code4nix/uri-signer/tests --fix --config vendor/code4nix/uri-signer/tools/ecs/config.php

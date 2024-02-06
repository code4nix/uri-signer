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

namespace Code4Nix\UriSigner\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Code4NixUriSignerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return Configuration::ROOT_KEY;
    }

    /**
     * @param array<int|string> $configs
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('services.yaml');

        $rootKey = $this->getAlias();

        $container->setParameter($rootKey.'.parameter', $config['parameter']);
        $container->setParameter($rootKey.'.expiration', $config['expiration']);
    }
}

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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_KEY = 'code4nix_uri_signer';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_KEY);

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('parameter')
                    ->cannotBeEmpty()
                    ->defaultValue('_hash')
                ->end()
                ->integerNode('expiration')
                    ->info('Expiration time in seconds')
                    ->defaultValue(3600)
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

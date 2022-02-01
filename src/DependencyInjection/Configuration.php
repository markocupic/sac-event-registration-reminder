<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Registration Reminder.
 * 
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-registration-reminder
 */

namespace Markocupic\SacEventRegistrationReminder\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_KEY = 'markocupic_sac_event_registration_reminder';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_KEY);

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('foo')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('bar')
                            ->cannotBeEmpty()
                            ->defaultValue('***')
                        ->end()
                    ->end()
                ->end() // end foo
            ->end()
        ;

        return $treeBuilder;
    }
}

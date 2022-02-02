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
    public const ROOT_KEY = 'sac_evt_reg_reminder';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_KEY);

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('disable')
                    ->info('Disable the application globally.')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('sid')
                    ->info('SID -> https://domain.com/_event_registration_reminder/{sid}')
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('allow_web_scope')
                    ->info('Allow running CRON in webscope.')
                    ->defaultValue(true)
                ->end()
                ->integerNode('max_notifications_per_request')
                    ->info('Add a limit for sending email per request.')
                    ->defaultValue(100)
                ->end()
                ->scalarNode('fallback_language')
                    ->info('Add a fallback language (notification center).')
                    ->cannotBeEmpty()
                    ->defaultValue('de')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

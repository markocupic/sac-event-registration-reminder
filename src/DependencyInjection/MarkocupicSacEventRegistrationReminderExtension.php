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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class MarkocupicSacEventRegistrationReminderExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return Configuration::ROOT_KEY;
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('parameters.yml');
        $loader->load('services.yml');
        $loader->load('listener.yml');

        $rootKey = $this->getAlias();

        $container->setParameter($rootKey.'.disable', $config['disable']);
        $container->setParameter($rootKey.'.sid', $config['sid']);
        $container->setParameter($rootKey.'.allow_web_scope', $config['allow_web_scope']);
        $container->setParameter($rootKey.'.notification_limit_per_request', $config['notification_limit_per_request']);
        $container->setParameter($rootKey.'.default_locale', $config['default_locale']);
        $container->setParameter($rootKey.'.cron_schedule', $config['cron_schedule']);

        $this->addCronSchedule($container, $config);
    }

    private function addCronSchedule(ContainerBuilder $container): void
    {
        if (!$container->has('Markocupic\SacEventRegistrationReminder\Cron\NotificationCron')) {
            return;
        }

        if (!$container->hasParameter('sac_evt_reg_reminder.cron_schedule')) {
            return;
        }

        $cronSchedule = $container
            ->getParameter('sac_evt_reg_reminder.cron_schedule')
        ;

        $cron = $container
            ->findDefinition('Markocupic\SacEventRegistrationReminder\Cron\NotificationCron')
        ;

        $cron->clearTag('contao.cronjob');

        $cron->addTag('contao.cronjob', [
            'interval' => $cronSchedule,
        ]);
    }
}

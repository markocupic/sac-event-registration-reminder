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

use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_event_registration_reminder_notification'] = [
    'config'   => [
        'dataContainer' => 'Table',
        'closed'        => true,
        'sql'           => [
            'keys' => [
                'id'                      => 'primary',
                'dateAdded,user,calendar' => 'index',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_SORTABLE,
            'panelLayout' => 'filter;sort,search,limit',
            'fields'      => ['tstamp DESC'],
        ],
        'label'             => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_event_registration_reminder_notification']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_event_registration_reminder_notification']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.svg',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_event_registration_reminder_notification']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show'   => [
                'label'      => &$GLOBALS['TL_LANG']['tl_event_registration_reminder_notification']['show'],
                'href'       => 'act=show',
                'icon'       => 'show.svg',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{first_legend},title,user,calendar,dateAdded,prevReminderTstamp,history',
    ],
    'fields'   => [
        'id'                 => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'             => [
            'flag'    => DataContainer::SORT_DAY_DESC,
            'sorting' => true,
            'sql'     => "int(10) unsigned NOT NULL default '0'",
        ],
        'title'              => [
            'eval'      => ['tl_class' => 'w50'],
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'dateAdded'          => [
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => "int(10) unsigned NOT NULL default '0'",
        ],
        'prevReminderTstamp' => [
            'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'sorting'   => true,
            'sql'       => "int(10) unsigned NOT NULL default '0'",
        ],
        'user'               => [
            'eval'       => ['tl_class' => 'w50'],
            'exclude'    => true,
            'filter'     => true,
            'foreignKey' => 'tl_user.name',
            'inputType'  => 'text',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql'        => 'int(10) unsigned NOT NULL default 0',
        ],
        'calendar'           => [
            'eval'       => ['tl_class' => 'w50'],
            'exclude'    => true,
            'filter'     => true,
            'foreignKey' => 'tl_calendar.title',
            'inputType'  => 'select',
            'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
            'sql'        => "varchar(255) NOT NULL default ''",
        ],
        'history'            => [
            'exclude'   => true,
            'inputType' => 'textarea',
            'eval'      => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => 'text NULL',
        ],
    ],
];

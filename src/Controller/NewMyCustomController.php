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

namespace Markocupic\SacEventRegistrationReminder\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Markocupic\SacEventRegistrationReminder\Data\Data;
use Markocupic\SacEventRegistrationReminder\Data\MessageGenerator;
use Markocupic\SacEventRegistrationReminder\Util\DataCollector;
use Markocupic\SacEventRegistrationReminder\Util\NotificationHelper;
use Markocupic\SacEventToolBundle\Config\EventSubscriptionLevel;
use NotificationCenter\Model\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NewMyCustomController.
 *
 * @Route("/remindertest",
 *     name=NewMyCustomController::class,
 *     defaults={
 *         "_scope" = "frontend",
 *         "_token_check" = true
 *     }
 * )
 */
class NewMyCustomController extends AbstractController
{
    private ContaoFramework $framework;
    private Connection $connection;
    private DataCollector $dataCollector;
    private MessageGenerator $messageGenerator;
    private NotificationHelper $notificationHelper;

    public function __construct(ContaoFramework $framework, Connection $connection, DataCollector $dataCollector, MessageGenerator $messageGenerator, NotificationHelper $notificationHelper)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->dataCollector = $dataCollector;
        $this->messageGenerator = $messageGenerator;
        $this->notificationHelper = $notificationHelper;
    }

    public function __invoke(): Response
    {
        $this->framework->initialize();
        $start = time();

        $state = EventSubscriptionLevel::SUBSCRIPTION_NOT_CONFIRMED;

        $arrData = $this->dataCollector->getData($state);

        $itCalendar = (new Data($arrData))->getIterator();

        $i = 0;

        while ($itCalendar->valid()) {
            $calendarId = (int) $itCalendar->key();
            $arrUsers = $itCalendar->current();

            if (\is_array($arrUsers)) {
                $itUsers = (new Data($arrUsers))->getIterator();

                while ($itUsers->valid()) {
                    ++$i;

                    $userId = (int) $itUsers->key();

                    $arrUser = $itUsers->current();

                    $strRegistrations = $this->messageGenerator
                        ->generate($arrUser, $userId)
                    ;

                    if (null !== ($notification = $this->getNotification($calendarId))) {
                        $arrTokens = [
                            'registrations' => $strRegistrations,
                        ];

                        $arr = $this->notificationHelper->send($notification, $userId, $arrTokens);

                        if (!empty($arr) && \is_array($arr)) {
                            $user = $this->connection->fetchOne('SELECT name FROM tl_user WHERE id = ?', [$userId]);

                            $set = [
                                'tstamp'   => time(),
                                'addedOn'  => time(),
                                'title'    => 'Reminder sent for '.$user.'.',
                                'user'     => $userId,
                                'calendar' => $calendarId,
                            ];

                            $this->connection->insert('tl_event_registration_reminder_notification', $set);
                        }
                    }

                    $itUsers->next();
                }
            }
            $itCalendar->next();
        }

        $responseMsg = sprintf('We\'ve sent %s messages to %s users.<br>Script runtime: %s s.', $i,$i,time() - $start);

        return new Response($responseMsg);
    }

    private function getNotification(int $calendarId)
    {
        if ($id = $this->connection->fetchOne('SELECT sendReminderNotification from tl_calendar WHERE id = ?', [$calendarId])) {
            if (null !== ($notification = Notification::findByPk($id))) {
                return $notification;
            }
        }

        return null;
    }
}

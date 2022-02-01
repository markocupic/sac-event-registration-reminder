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
use Contao\Email;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Markocupic\SacEventToolBundle\Config\EventSubscriptionLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MyCustomController.
 *
 * @Route("/remindertest2",
 *     name=MyCustomController::class,
 *     defaults={
 *         "_scope" = "frontend",
 *         "_token_check" = true
 *     }
 * )
 */
class MyCustomController extends AbstractController
{
    private ContaoFramework $framework;
    private Connection $connection;

    public function __construct(ContaoFramework $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;
    }

    public function __invoke()
    {

        $this->framework->initialize();

        $start = time();

        $timeLimit = time() - 7 * 24 * 3600;
        $state = EventSubscriptionLevel::SUBSCRIPTION_NOT_CONFIRMED;
        $arrData = [];

        $arrUsers['users'] = array_map(static fn ($id) => (int) $id, $this->getUsers());





        foreach ($arrUsers['users'] as $userId) {
            $blnSend = false;
            $arrData['users'][$userId]['events'] = [];
            $arrEvents = array_map(static fn ($id) => (int) $id, $this->getEventsByUser($userId));

            foreach ($arrEvents as $eventId) {
                $members = $this->getRegistrationByEventAndState($eventId, $state, $timeLimit);

                if ($members) {
                    $arrData['users'][$userId]['events'][$eventId]['registrations'] = $members;
                    $blnSend = true;
                }
            }
            if(!$blnSend){
                unset($arrData['users'][$userId]);
            }else{
                $arrData['users'][$userId]['doNotify'] = $blnSend;

                $this->sendEmail($userId);
            }
        }

        $arrData = array_filter($arrData);
        $end = time();

        $durationtime = $end-$start;

        $data = [
            'duration' => $durationtime,
            'content' => print_r($arrData,true)
        ];


        return new JsonResponse($data);
    }

    private function getUsers()
    {
        return $this->connection->fetchFirstColumn('SELECT id FROM tl_user WHERE disable = ?', ['']);
    }

    private function getEventsByUser(int $userId): array
    {
        $arrEvents = [];
        $arrEvents[] = $this->connection->fetchFirstColumn('SELECT * FROM tl_calendar_events WHERE registrationGoesTo = ? AND startDate > ?', [$userId, time()]);
        $arrEvents[] = $this->connection->fetchFirstColumn('SELECT * FROM tl_calendar_events AS t1 WHERE t1.registrationGoesTo != ? AND t1.id IN (SELECT t2.pid FROM tl_calendar_events_instructor AS t2 WHERE t2.isMainInstructor = ? AND t2.userId = ?) AND t1.startDate > ?', [$userId, '1', $userId, time()]);

        return array_unique(array_merge(...$arrEvents));
    }

    private function getRegistrationByEventAndState(int $intEventId, string $strState, int $intTimeLimit): ?array
    {
        return $this->connection->fetchFirstColumn('SELECT * FROM tl_calendar_events_member WHERE eventId = ? AND stateOfSubscription = ? AND addedOn < ?', [$intEventId, $strState, $intTimeLimit]) ?: null;
    }

    private function sendEmail($userId)
    {

        $userModel = UserModel::findByPk($userId);
        if(null !== $userModel)
        {
            $email = new Email();

            $email->from = 'internet@sac-pilatus.ch';
            $email->subject = 'TEST SAC ' . $userModel->name;
            $email->text = 'test ' . $userModel->name;
            $email->sendTo('m.cupic@gmx.ch');
        }


    }
}

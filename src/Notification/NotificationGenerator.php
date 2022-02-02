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

namespace Markocupic\SacEventRegistrationReminder\Notification;

use Contao\CalendarEventsMemberModel;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Contao\UserModel;
use Markocupic\SacEventRegistrationReminder\String\Sanitizer;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class NotificationGenerator
{
    private ContaoFramework $framework;
    private Environment $twig;
    private TranslatorInterface $translator;
    private Sanitizer $sanitizer;
    private ?array $data;
    private ?UserModel $user;
    private string $fallbackLanguage;

    public function __construct(ContaoFramework $framework, Environment $twig, TranslatorInterface $translator, Sanitizer $sanitizer, string $fallbackLanguage)
    {
        $this->framework = $framework;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->sanitizer = $sanitizer;
        $this->fallbackLanguage = $fallbackLanguage;
    }

    /**
     * @throws \Exception
     */
    public function generate(array $arrData, int $userId): string
    {
        $this->initialize($arrData, $userId);

        return $this->render($this->prepareTwigData());
    }

    /**
     * @throws StringsException
     */
    private function initialize(array $arrData, int $userId): void
    {
        $this->data = $arrData;

        if (null === ($this->user = UserModel::findByPk($userId))) {
            throw new \Exception(sprintf('User with ID %s not found', $userId));
        }
    }

    private function prepareTwigData(): array
    {
        $arrData = [];

        $locale = $this->user->language ?: $this->fallbackLanguage;

        $systemAdapter = $this->framework->getAdapter(System::class);

        $systemAdapter->loadLanguageFile('default', $locale);

        foreach ($this->data as $eventId => $arrEvent) {
            $event = CalendarEventsModel::findByPk($eventId);

            if (null === $event) {
                continue;
            }

            $rowEvent = [];
            $rowEvent['event_title'] = $this->sanitizer->sanitize($event->title);
            $rowEvent['trans']['event_type'] = $this->translator->trans('MSC.'.$event->eventType, [], 'contao_default', $locale);

            $rowEvent['registrations_outside_deadline'] = [];
            $rowEvent['registrations_within_deadline'] = [];

            foreach (['outside_deadline', 'within_deadline'] as $deadlineKey) {
                $rowEvent['has_registrations_'.$deadlineKey] = !empty($arrEvent[$deadlineKey]);

                foreach ($arrEvent[$deadlineKey] as $registrationId) {
                    $registration = CalendarEventsMemberModel::findByPk($registrationId);

                    if (null === $registration) {
                        continue;
                    }

                    $rowEvent['registrations_'.$deadlineKey][] = [
                        'user_locale' => $locale,
                        'firstname' => $registration->firstname,
                        'lastname' => $registration->lastname,
                        'sac_member_id' => $registration->sacMemberId,
                        'registered_since' => (string) ceil((time() - (int) $registration->addedOn) / strtotime('1 day', 0)),
                        'trans' => [
                            'participant' => 'female' === $registration->user->gender ? $this->translator->trans('MSC.serr_participant_female', [], 'contao_default', $locale) : $this->translator->trans('MSC.serr_participant_male', [], 'contao_default', $locale),
                        ],
                    ];
                }
            }

            $arrData[] = $rowEvent;
        }

        return $arrData;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function render(array $arrData): string
    {
        return $this->twig->render(
            '@MarkocupicSacEventRegistrationReminder/message_partial.twig',
            [
                'user' => $this->user->row(),
                'events' => $arrData,
            ]
        );
    }
}

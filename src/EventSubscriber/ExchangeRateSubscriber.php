<?php

namespace App\EventSubscriber;

use App\Event\ExchangeRateEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

readonly class ExchangeRateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TemplatedEmail $templatedEmail,
        private MailerInterface $mailer,
        private Address $from,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function onExchangeRateEvent(ExchangeRateEvent $event): void
    {
        $violations = $event->getViolations();

        $email = $this->templatedEmail
            ->to(new Address(
                $this->parameterBag->get('exchange_rate_alert_email'),
                $this->parameterBag->get('exchange_rate_alert_title')
            ))
            ->from($this->from)
            ->subject('Exchange rate alert') // we can use translation here, but it's not required.
            ->htmlTemplate('email/exchange_rates.html.twig')
            ->context([
                'violations' => $violations,
            ]);

        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExchangeRateEvent::class => 'onExchangeRateEvent',
        ];
    }
}

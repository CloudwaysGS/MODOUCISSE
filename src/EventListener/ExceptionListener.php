<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof \PDOException && $exception->getCode() === 'HY000' && strpos($exception->getMessage(), '2002') !== false) {
            $message = 'Impossible de se connecter à la base de données. Veuillez réessayer plus tard ou contacter le support technique.';
            $event->setResponse(new \Symfony\Component\HttpFoundation\Response($message, 500));
        }
    }
}

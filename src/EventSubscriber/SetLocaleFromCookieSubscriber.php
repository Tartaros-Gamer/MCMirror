<?php declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class SetLocaleFromCookieSubscriber implements EventSubscriberInterface
{
    private $defaultLocale;

    public function __construct(string $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getSession() === null) {
            $request->setSession(new Session());
        }

        if ($locale = $request->query->get('lang')) {
            $request->getSession()->set('lang', $locale);
        } elseif ($locale = $request->attributes->get('lang')) {
            $request->getSession()->set('lang', $locale);
        }

        if (!$request->getSession()->has('lang')) {
            $request->getSession()->set('lang', $this->defaultLocale);
        }

        $request->setLocale($request->getSession()->get('lang'));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => ['onKernelRequest', 20],
        ];
    }
}

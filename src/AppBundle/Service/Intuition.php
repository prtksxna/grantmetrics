<?php

namespace AppBundle\Service;

use Krinkle\Intuition\Intuition as KrinkleIntuition;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Intuition extends KrinkleIntuition
{

    /**
     * @param RequestStack $requestStack
     * @param SessionInterface $session
     * @param string $rootDir
     * @return Intuition
     */
    public static function serviceFactory(RequestStack $requestStack, SessionInterface $session, string $rootDir)
    {
        $useLang = 'en';

        // Current request doesn't exist in unit tests, in which case we'll fall back to English.
        if ($requestStack->getCurrentRequest() !== null) {
            // Use lang from the request or the session.
            $queryLang = $requestStack->getCurrentRequest()->query->get('uselang');
            $sessionLang = $session->get('lang');
            if (!empty($queryLang)) {
                $useLang = $queryLang;
            } elseif (!empty($sessionLang)) {
                $useLang = $sessionLang;
            }

            // Save the language to the session.
            if ($session->get('lang') !== $useLang) {
                $session->set('lang', $useLang);
            }
        }

        // Set up Intuition, using the selected language.
        $intuition = new static('grantmetrics');
        $intuition->registerDomain('grantmetrics', $rootDir.'/../i18n');
        $intuition->setLang(strtolower($useLang));
        return $intuition;
    }
}

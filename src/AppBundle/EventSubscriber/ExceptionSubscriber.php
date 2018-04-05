<?php
/**
 * This file contains only the ExceptionSubscriber class.
 */

namespace AppBundle\EventSubscriber;

use AppBundle\EventSubscriber\I18nException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Templating\EngineInterface;
use Twig_Error_Runtime;

/**
 * A ExceptionSubscriber ensures Twig exceptions are properly
 * handled, so that a friendly error page is shown to the user.
 */
class ExceptionSubscriber
{
    /** @var EngineInterface For rendering the view. */
    private $templateEngine;

    /** @var LoggerInterface For logging the exception. */
    private $logger;

    /** @var string The environment. */
    private $environment;

    /**
     * Constructor for the ExecptionListener.
     * @param EngineInterface $templateEngine
     * @param LoggerInterface $logger
     * @param string $environment
     */
    public function __construct(EngineInterface $templateEngine, LoggerInterface $logger, $environment = 'prod')
    {
        $this->templateEngine = $templateEngine;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * Capture the exception, check if it's a Twig error and if so
     * throw the previous exception, which should be more meaningful.
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof Twig_Error_Runtime) {
            $response = $this->getTwigExceptionResponse($exception);
        } elseif ($execption instanceof I18nException) {
            $response = $this->getI18nExceptionResponse($exception);
        } else {
            return;
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }

    private function getTwigExceptionResponse(Twig_Error_Runtime $exception)
    {
        // We only care about the previous (original) exception,
        // not the one Twig put on top of it.
        $prevException = $exception->getPrevious();

        // No previous exception, so we've got nothing to show. This is basically a fail-safe.
        if ($prevException === null) {
            return;
        }

        if ($this->environment !== 'prod') {
            throw $prevException;
        }

        // Log the exception, since we're handling it and it won't automatically be logged.
        $file = explode('/', $prevException->getFile());
        $this->logger->error(
            '>>> CRITICAL (\''.$prevException->getMessage().'\' - '.
            end($file).' - line '.$prevException->getLine().')'
        );

        return new Response(
            $this->templateEngine->render('TwigBundle:Exception:error.html.twig', [
                'status_code' => 500,
                'status_text' => 'Internal Server Error',
                'exception' => $prevException,
            ])
        );
    }

    private function getI18nExceptionResponse(\Exception $exception)
    {
        if ($this->environment !== 'prod') {
            throw $exception;
        }

        // Log the exception, since we're handling it and it won't automatically be logged.
        $file = explode('/', $exception->getFile());
        $this->logger->error(
            '>>> CRITICAL (Intuition - \''.$exception->getMessage().'\' - '.
            end($file).' - line '.$exception->getLine().')'
        );

        return new Response(
            $this->templateEngine->render('TwigBundle:Exception:error.html.twig', [
                'status_code' => 500,
                'status_text' => 'Internal Server Error',
                'exception' => $prevException,
            ])
        );
    }
}

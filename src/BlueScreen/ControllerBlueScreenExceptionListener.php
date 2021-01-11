<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle\BlueScreen;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Throwable;
use Tracy\BlueScreen;

use function header;
use function headers_sent;
use function strpos;

final class ControllerBlueScreenExceptionListener
{
    /** @var BlueScreen */
    private $blueScreen;

    public function __construct(BlueScreen $blueScreen)
    {
        $this->blueScreen = $blueScreen;
    }

    public function onKernelException(ExceptionEvent $event) : void
    {
        $this->forceExceptionControllerHtml($event->getRequest());
        $this->renderBlueScreen($event->getThrowable());
    }

    private function forceExceptionControllerHtml(Request $request) : void
    {
        $request->setRequestFormat('html');
        $request->attributes->set('_format', 'html');
    }

    private function renderBlueScreen(Throwable $exception) : void
    {
        if (! headers_sent()) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            $code = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE ') !== false
                ? 503
                : 500;
            header($protocol . ' ' . $code, true, $code);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $this->blueScreen->render($exception);
    }
}

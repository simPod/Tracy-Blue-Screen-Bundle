<?php

declare(strict_types=1);

namespace Cdn77\TracyBlueScreenBundle\BlueScreen;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Throwable;
use Tracy\BlueScreen;

use function assert;
use function ob_get_clean;
use function ob_start;

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
        $blueScreenResponse = $this->renderBlueScreen($event->getThrowable());

        $event->setResponse($blueScreenResponse);
    }

    private function renderBlueScreen(Throwable $exception) : Response
    {
        ob_start();

        $this->blueScreen->render($exception);

        $contents = ob_get_clean();
        assert($contents !== false);

        return new Response($contents, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

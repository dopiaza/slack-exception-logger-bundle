<?php

namespace Dopiaza\Slack\ExceptionLoggerBundle\Notifier;

interface Notifier
{
    /**
     * @param \Exception $exception
     * @param null $routeInfo
     * @return
     */
    public function notify(\Exception $exception, $routeInfo = null);
}

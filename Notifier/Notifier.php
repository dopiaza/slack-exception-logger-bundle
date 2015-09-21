<?php

namespace Dopiaza\Slack\ExceptionLoggerBundle\Notifier;

interface Notifier
{
    /**
     * @param \Exception $exception
     */
    public function notify(\Exception $exception);
}

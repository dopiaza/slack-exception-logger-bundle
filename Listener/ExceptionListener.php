<?php

namespace Dopiaza\Slack\ExceptionLoggerBundle\Listener;

use Dopiaza\Slack\ExceptionLoggerBundle\Notifier\Notifier;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    /** @var string */
    private $environment;

    /** @var string */
    private $notifier;

    /** @var array */
    private $environmentConfigurations;

    /**
     * @param Notifier $notifier
     * @param $environment
     */
    public function __construct(Notifier $notifier, $environment)
    {
        $this->notifier = $notifier;
        $this->environment = $environment;
    }

    /**
     * @param array $environmentConfigurations
     */
    public function setEnvironmentConfigurations($environmentConfigurations)
    {
        $this->environmentConfigurations = $environmentConfigurations;
    }

    /**
     * Handle the exception.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($this->shouldProcessException($exception)) {
            $this->notifier->notify($exception);
        }

        return;
    }

     /**
      * Check to see if this exception is in an exclude list.
      *
      * @param $exception
      *
      * @return bool
      */
     private function shouldProcessException(\Exception $exception)
     {
         $config = isset($this->environmentConfigurations[$this->environment]) ? $this->environmentConfigurations[$this->environment] : null;

         // check if we have a config
         if (empty($config)) {
             return false;
         }

         // check if the config is enabled
         if (isset($config['enabled']) && $config['enabled'] === false) {
             return false;
         }

         // check if we have excluded this particular exception
         if (array_key_exists('exclude_exception', $config)) {
             $className = get_class($exception);
             $excludeList = $config['exclude_exception'];
             foreach ($excludeList as $exclude) {
                 if ($exclude == $className) {
                     return false;
                 }
             }
         }

         return true;
     }
}

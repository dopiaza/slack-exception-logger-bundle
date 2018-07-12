<?php

/**
 * Log symfony exceptionst to Slack
 *
 */
namespace Dopiaza\Slack\ExceptionLoggerBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

class ExceptionHandler
{
    /** @var  LoggerInterface  */
    private $logger;

    /** @var string */
    private $environment;

    /** @var string */
    private $webhook;

    /** @var string */
    private $name;

    /** @var string */
    private $botname;

    /** @var array */
    private $environmentConfigurations;

    /**
     * @param LoggerInterface $logger
     * @param $environment
     */
    public function __construct(LoggerInterface $logger, $environment)
    {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * Handle kernel exception
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($this->shouldProcessException($exception))
        {
            $this->postToSlack($exception);
        }

        return;
    }

    /**
     * Handle console error
     *
     * @param ConsoleErrorEvent $event
     */
    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $exception = $event->getError();

        if ($this->shouldProcessException($exception))
        {
            $this->postToSlack($exception);
        }

        return;
    }

    /**
     * Post exception details to Slack
     *
     * @param \Exception $exception
     */
    protected function postToSlack(\Throwable $exception)
    {
        $url = $this->getWebhook();
        $message = $this->formatSlackMessageForException($exception);
        if (!empty($message))
        {
            $this->post($url, $message);
        }
    }

    /**
     * Format the JSON message to post to Slack
     *
     * @param \Exception $exception
     * @return null|string
     */
    protected function formatSlackMessageForException(\Throwable  $exception)
    {
        $config = $this->getConfigForEnvironment();
        $json = null;

        if (!empty($config) && $config['enabled'])
        {
            $code = $exception->getCode();
            $text = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $fullClassName = get_class($exception);
            $className = preg_replace('/^.*\\\\([^\\\\]+)$/', '$1', $fullClassName);
            $now = new \DateTime();

            $message = array(
                'channel' => '#' . $config['channel'],
                'text' => $className . ' thrown in ' . $this->getName(),
                'attachments' => array(
                    array(
                        'fallback'=> $text,
                        'color' => $config['color'],
                        'pretext' => '',
                        'title' => $fullClassName,
                        'fields' => array(
                            array(
                                'title' => 'Message',
                                'value' => $text,
                            ),
                            array(
                                'title' => 'System',
                                'value' => $this->getName(),
                                'short' => 1,
                            ),
                            array(
                                'title' => 'Timestamp',
                                'value' => $now->format(DATE_ISO8601),
                                'short' => 1,
                            ),
                            array(
                                'title' => 'Code',
                                'value' => $code,
                                'short' => 1,
                            ),
                            array(
                                'title' => 'Environment',
                                'value' => $this->environment,
                                'short' => 1,
                            ),
                            array(
                                'title' => 'File',
                                'value' => $file,
                                'short' => 1,
                            ),
                            array(
                                'title' => 'Line',
                                'value' => $line,
                                'short' => 1,
                            ),
                        ),
                    ),
                ),
            );

            if (!empty($this->botname))
            {
                $message['username'] = $this->botname;
            }

            $json = json_encode($message);
        }

        return $json;
    }

    /**
     * Do an HTTP post
     *
     * @param $url
     * @param null $body
     * @return bool
     */
    protected function post($url, $body = null)
    {
        if (empty($body))
        {
            return false;
        }

        $ch = curl_init();

        if (!$ch)
        {
            $this->log('Failed to create curl handle');
            return false;
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($body))
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpStatusCode != 200)
        {
            $this->log('Failed to post to slack: status ' . $httpStatusCode);
            return false;
        }
        if ($response != 'ok')
        {
            $this->log('Didn\'t get an "ok" back from slack, got: ' . $response);
            return false;
        }

        return true;
    }

    /**
     * Check to see if this exception is in an exclude list
     *
     * @param $exception
     * @return bool
     */
    private function shouldProcessException(\Throwable $exception)
    {
        $shouldProcess = true;

        $config = $this->getConfigForEnvironment();
        if (!empty($config))
        {
            if (array_key_exists('exclude_exception', $config))
            {
                $excludeList = $config['exclude_exception'];
                foreach ($excludeList as $exclude)
                {
                    if ($exception instanceof $exclude)
                    {
                        $shouldProcess = false;
                        break;
                    }
                }
            }
        }

        return $shouldProcess;
    }

    /**
     * Get the configuration for the current environment
     *
     * @return mixed
     */
    private function getConfigForEnvironment()
    {
        return isset($this->environmentConfigurations[$this->environment])
            ? $this->environmentConfigurations[$this->environment]
            : null;
    }

    protected function log($message)
    {
        if (!empty($this->logger))
        {
            $this->logger->info($message);
        }
    }

    /**
     * @return string
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * @param string $webhook
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getBotname()
    {
        return $this->botname;
    }

    /**
     * @param string $botname
     */
    public function setBotname($botname)
    {
        $this->botname = $botname;
    }

    /**
     * @return array
     */
    public function getEnvironmentConfigurations()
    {
        return $this->environmentConfigurations;
    }

    /**
     * @param array $environmentConfigurations
     */
    public function setEnvironmentConfigurations($environmentConfigurations)
    {
        $this->environmentConfigurations = $environmentConfigurations;
    }
}

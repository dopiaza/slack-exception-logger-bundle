<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 18/09/15
 * Time: 16:17
 */

namespace Dopiaza\Slack\ExceptionLoggerBundle\Notifier;

use Dopiaza\Slack\ExceptionLoggerBundle\HttpClient\HttpClient;

/**
 * Class SlackNotifier
 * @package Dopiaza\Slack\ExceptionLoggerBundle\Notifier
 */
class SlackNotifier implements Notifier
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /** @var string */
    private $name;

    /** @var string */
    private $webhook;

    /** @var string */
    private $botname;

    /** @var  string */
    private $environment;

    /** @var array */
    private $environmentConfigurations;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient, $environment)
    {
        $this->httpClient = $httpClient;
        $this->environment = $environment;
    }

    /**
     * @param \Exception $exception
     * @param null $routeInfo
     */
    public function notify(\Exception $exception, $routeInfo = null)
    {
        $message = $this->formatSlackMessageForException($exception, $routeInfo);
        if (!empty($message)) {
            $this->httpClient->post($this->webhook, $message);
        }
    }

    /**
     * @param array $environmentConfigurations
     */
    public function setEnvironmentConfigurations($environmentConfigurations)
    {
        $this->environmentConfigurations = $environmentConfigurations;
    }

    /**
     * @param string $webhook
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $botname
     */
    public function setBotname($botname)
    {
        $this->botname = $botname;
    }

    /**
     * Format the JSON message to post to Slack
     *
     * @param \Exception $exception
     * @param null $routeInfo
     * @return null|string
     */
    protected function formatSlackMessageForException(\Exception $exception, $routeInfo = null)
    {
        $config = isset($this->environmentConfigurations[$this->environment]) ? $this->environmentConfigurations[$this->environment] : null;

        $code = $exception->getCode();
        $text = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $fullClassName = get_class($exception);
        $className = preg_replace('/^.*\\\\([^\\\\]+)$/', '$1', $fullClassName);
        $now = new \DateTime();

        $message = array(
            'channel' => '#' . $config['channel'],
            'text' => $className . ' thrown in ' . $this->name,
            'attachments' => array(
                array(
                    'fallback' => $text,
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
                            'value' => $this->name,
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

        if (is_array($routeInfo)) {
            foreach ($routeInfo as $key => $value) {
                $message['attachments'][0]['fields'][] = array(
                    'title' => ucfirst(ltrim($key, '_')),
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'short' => 1
                );

            }
        }

        if (!empty($this->botname)) {
            $message['username'] = $this->botname;
        }

        $json = json_encode($message);

        return $json;
    }
}
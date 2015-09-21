<?php

namespace Dopiaza\Slack\ExceptionLoggerBundle\HttpClient;

use Psr\Log\LoggerInterface;

class CurlHttpClient implements HttpClient
{
    /** @var LoggerInterface  */
    private $logger;

    /**
     * CurlHttpClient constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Do an HTTP post.
     *
     * @param $url
     * @param null $body
     *
     * @return bool
     */
    public function post($url, $body = null)
    {
        if (empty($body)) {
            return false;
        }

        $ch = curl_init();

        if (!$ch) {
            $this->logger->info('Failed to create curl handle');

            return false;
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: '.strlen($body), )
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $response = curl_exec($ch);
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpStatusCode != 200) {
            $this->logger->info('Failed to post to slack: status '.$httpStatusCode);

            return false;
        }
        if ($response != 'ok') {
            $this->logger->info('Didn\'t get an "ok" back from slack, got: '.$response);

            return false;
        }

        return true;
    }
}

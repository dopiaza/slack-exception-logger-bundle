<?php

namespace Dopiaza\Slack\ExceptionLoggerBundle\HttpClient;

interface HttpClient
{
    /**
     * Do an HTTP post.
     *
     * @param $url
     * @param null $body
     *
     * @return bool
     */
    public function post($url, $body);
}

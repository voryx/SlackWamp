<?php

namespace SlackWamp;

use Rx\Observable;
use Rx\React\Http as RxHttp;

class Http
{
    private $token;
    private $slackURL = 'https://slack.com/api/';

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function get($method, array $params = []): Observable
    {
        $url = $this->slackURL . $method . '?token=' . $this->token . '&' . http_build_query($params);

        return RxHttp::get($url)->map('json_decode');
    }
}

<?php

namespace SlackWamp;

use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use Rx\DisposableInterface;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\React\Promise;
use Rx\Thruway\Client;

class RealTimeBridge extends Observable
{
    private $wamp;
    private $loop;
    private $uriPrefix;
    private $http;

    public function __construct(Client $wamp, string $token, string $uriPrefix = 'slack')
    {
        $this->uriPrefix = $uriPrefix . '.';
        $this->loop      = \EventLoop\getLoop();
        $this->wamp      = $wamp;
        $this->http      = new Http($token);
    }

    protected function _subscribe(ObserverInterface $observer): DisposableInterface
    {
        return $this->http->get('rtm.start')
            ->flatMapLatest(function ($data) {
                if (!isset($data->ok) || $data->ok !== true) {
                    throw new \Exception(json_encode($data));
                }

                $connector = new Connector($this->loop);
                return Promise::toObservable($connector($data->url));
            })
            ->flatMap(function (WebSocket $conn) {
                return new Observable\FromEventEmitterObservable($conn, 'message');
            })
            ->pluck(0)
            ->map(function (Message $msg) {
                return $msg->getPayload();
            })
            ->map('json_decode')
            ->do(
                function ($msg) {
                    $topic = $this->uriPrefix ? $this->uriPrefix . $msg->type : $msg->type;

                    //Publish all messages to the equivalent WAMP topic
                    $this->wamp->publish($topic, $msg);
                },
                function (\Throwable $error) {
                    echo "register subscriptions error {$error->getMessage()}", PHP_EOL;
                }
            )
            ->subscribe($observer);
    }
}

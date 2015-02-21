<?php


namespace SlackWamp;


use Ratchet\Client\WebSocket;
use React\Promise\Deferred;
use Thruway\Peer\Client;

/**
 * Class SlackClient
 * @package SlackWamp
 */
class SlackClient extends Client
{

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $slackURL = 'https://slack.com/api/';

    /**
     * @param string $token
     * @param \React\EventLoop\LoopInterface $realm
     * @param null $loop
     */
    function __construct($token, $realm, $loop = null)
    {
        $this->token = $token;
        parent::__construct($realm, $loop);
    }


    /**
     * On Session Start
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {

        $this->registerSubscriptions();
        $this->registerCalls();

    }

    /**
     * Listen for messages on the slack side and publish them to WAMP
     *
     */
    protected function registerSubscriptions()
    {
        $this->request('rtm.start')->then(
            function ($data) {
                if (!isset($data->ok) || $data->ok !== true) {
                    return;
                }

                $connector = new \Ratchet\Client\Factory($this->getLoop());
                $connector($data->url)->then(
                    function (WebSocket $conn) {
                        $conn->on('message', function ($msg) {
                            $msg = json_decode($msg);
                            $this->getSession()->publish($msg->type, [$msg]);
                        });
                    },
                    function ($e) {
                        echo "Could not connect: {$e->getMessage()}\n";
                    }
                );

            },
            function ($error) {
                echo "error {$error}";
            }
        );

    }

    /**
     * Register a WAMP call for each Slack RPC
     *
     */
    protected function registerCalls()
    {
        foreach ($this->callMap() as $call) {

            $this->getSession()->register(strtolower($call), function ($args, $argskw) use ($call) {
                $deferred = new Deferred();
                $this->request($call, http_build_query((array)$argskw))->then(
                    function ($data) use ($deferred) {
                        $deferred->resolve($data);
                    },
                    function ($error) use ($deferred) {
                        $deferred->reject($error);
                    });

                return $deferred->promise();

            });
        }

    }


    /**
     * List of Slack to WAMP RPCs calls
     *
     * @return array
     */
    private function callMap()
    {
        return [
            'api.test',
            'auth.test',
            'channels.archive',
            'channels.create',
            'channels.history',
            'channels.info',
            'channels.invite',
            'channels.join',
            'channels.kick',
            'channels.leave',
            'channels.list',
            'channels.mark',
            'channels.rename',
            'channels.setPurpose',
            'channels.setTopic',
            'channels.unarchive',
            'chat.delete',
            'chat.postMessage',
            'chat.update',
            'emoji.list',
            'files.delete',
            'files.info',
            'files.list',
            'files.upload',
            'groups.archive',
            'groups.close',
            'groups.create',
            'groups.createChild',
            'groups.history',
            'groups.invite',
            'groups.kick',
            'groups.leave',
            'groups.list',
            'groups.mark',
            'groups.open',
            'groups.rename',
            'groups.setPurpose',
            'groups.setTopic',
            'groups.unarchive',
            'im.close',
            'im.history',
            'im.list',
            'im.mark',
            'im.open',
            'search.all',
            'search.files',
            'search.messages',
            'stars.list',
            'users.getPresence',
            'users.info',
            'users.list',
            'users.setActive',
            'users.setPresence'
        ];
    }

    /**
     * @param $method
     * @param null $params
     * @return \React\Promise\Promise
     */
    private function request($method, $params = null)
    {

        $deferred = new Deferred();

        $loop               = $this->getLoop();
        $dnsResolverFactory = new \React\Dns\Resolver\Factory();
        $dnsResolver        = $dnsResolverFactory->createCached('8.8.8.8', $loop);
        $factory            = new \React\HttpClient\Factory();
        $client             = $factory->create($loop, $dnsResolver);
        $request            = $client->request('GET', $this->slackURL . $method . '?token=' . $this->token . '&' . $params);
        $buffer             = '';

        $request->on('response', function ($response) use (&$buffer, $deferred) {
            $response->on('data', function ($data) use (&$buffer) {
                $buffer .= $data;
            });
            $response->on('error', function ($error) use ($deferred) {
                $deferred->reject($error);
            });
        });

        $request->on('end', function () use (&$buffer, $deferred) {
            $deferred->resolve(json_decode($buffer));
        });

        $request->on('error', function ($error) use ($deferred) {
            $deferred->reject($error);
        });

        $request->end();

        return $deferred->promise();
    }

}
<?php

namespace SlackWamp;

use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Thruway\Peer\Client;

/**
 * Class SlackClient
 * @package SlackWamp
 */
class SlackClient extends Client
{
    /** @var string */
    private $token;

    /** @var string */
    private $slackURL = 'https://slack.com/api/';

    /** @var string | null */
    private $uriPrefix;

    /**
     * @param string $token
     * @param string $realm
     * @param LoopInterface | null $loop
     * @param string | null $uriPrefix
     */
    function __construct($token, $realm, LoopInterface $loop = null, $uriPrefix = "slack")
    {
        $this->token     = $token;
        $this->uriPrefix = $uriPrefix.".";

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
                        echo $msg, PHP_EOL;
                        $msg   = json_decode($msg);
                        $topic = $this->uriPrefix? $this->uriPrefix.$msg->type : $msg->type;

                        //Publish all messages to the equivalent WAMP topic
                        $this->getSession()->publish($topic, [$msg]);
                    });
                },
                function ($e) {
                    echo "Could not connect: {$e->getMessage()}\n";
                });
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

            $call = $this->uriPrefix? $this->uriPrefix.$call : $call;

            $this->getSession()->register(strtolower($call), function ($args, $argskw) use ($call) {
                $deferred = new Deferred();

                //strip prefix from uri
                $uri = substr($call, strlen($this->uriPrefix));
                $this->request($uri, http_build_query((array)$argskw))->then(
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
          'dnd.endDnd',
          'dnd.endSnooze',
          'dnd.info',
          'dnd.setSnooze',
          'dnd.teamInfo',
          'emoji.list',
          'files.comments.add',
          'files.comments.delete',
          'files.comments.edit',
          'files.delete',
          'files.info',
          'files.list',
          'files.revokePublicURL',
          'files.sharedPublicURL',
          'files.upload',
          'groups.archive',
          'groups.close',
          'groups.create',
          'groups.createChild',
          'groups.history',
          'groups.info',
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
          'mpim.close',
          'mpim.history',
          'mpim.list',
          'mpim.mark',
          'mpim.open',
          'oauth.access',
          'pins.add',
          'pins.list',
          'pins.remove',
          'reactions.add',
          'reactions.get',
          'reactions.list',
          'reactions.remove',
          'rtm.start',
          'search.all',
          'search.files',
          'search.messages',
          'stars.list',
          'stars.add',
          'stars.remove',
          'team.accessLogs',
          'team.info',
          'team.integrationLogs',
          'usergroups.create',
          'usergroups.disable',
          'usergroups.enable',
          'usergroups.list',
          'usergroups.update',
          'usergroups.users.list',
          'usergroups.users.update',
          'users.getPresence',
          'users.info',
          'users.list',
          'users.setActive',
          'users.setPresence',
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
        $request            = $client->request('GET', $this->slackURL.$method.'?token='.$this->token.'&'.$params);
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

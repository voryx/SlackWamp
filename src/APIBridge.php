<?php

namespace SlackWamp;

use Rx\DisposableInterface;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\Thruway\Client;

class APIBridge extends Observable
{
    private $uriPrefix;
    private $wamp;
    private $http;

    public function __construct(Client $wamp, string $token, string $uriPrefix = 'slack')
    {
        $this->uriPrefix = $uriPrefix . '.';
        $this->wamp      = $wamp;
        $this->http      = new Http($token);
    }

    protected function _subscribe(ObserverInterface $observer): DisposableInterface
    {
        return Observable::fromArray($this->callMap())
            ->map(function ($call) {
                return $this->uriPrefix ? $this->uriPrefix . $call : $call;
            })
            ->flatMap(function ($call) {
                $uri = strtolower($call);
                return $this->wamp->registerExtended($uri, function ($args, $argskw) use ($call) {
                    //strip prefix from uri
                    $uri = substr($call, strlen($this->uriPrefix));

                    //Make API Call
                    return $this->http->get($uri, (array)$argskw);
                })->mapTo("Registered: {$uri}");
            })
            ->doOnError(function (\Throwable $error) {
                echo "register calls error {$error->getMessage()}", PHP_EOL;
            })
            ->subscribe($observer);
    }

    /**
     * List of Slack to WAMP RPCs calls
     *
     * @return string[]
     */
    private function callMap(): array
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
}

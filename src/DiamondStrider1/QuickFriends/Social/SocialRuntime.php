<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\Social;

use DiamondStrider1\QuickFriends\Structures\FriendRequest;
use LogicException;
use pocketmine\utils\ReversePriorityQueue;

/**
 * Data related to the Social Module that need not be persisted.
 */
final class SocialRuntime
{
    /**
     * @phpstan-var ReversePriorityQueue<int, FriendRequest>
     */
    private ReversePriorityQueue $friendRequestQueue;
    /**
     * @phpstan-var array<string, FriendRequest> $friendRequests key is $requester.$receiver
     */
    private array $friendRequests = [];

    public function __construct(
        private SocialConfig $socialConfig
    ) {
        $this->friendRequestQueue = new ReversePriorityQueue();
    }

    public function addFriendRequest(FriendRequest $friendRequest): void
    {
        $this->clearExpired();
        $this->friendRequestQueue->insert($friendRequest, $friendRequest->creationTime());
        $id = $friendRequest->requester().':'.$friendRequest->receiver();
        $this->friendRequests[$id] = $friendRequest;
    }

    public function getFriendRequest(string $requester, string $receiver): ?FriendRequest
    {
        $request = $this->friendRequests[$requester.':'.$receiver] ?? null;
        if (null != $request && !$request->claimed) {
            return $request;
        }

        return null;
    }

    /**
     * @return FriendRequest[]
     */
    public function getAllFriendRequests(): array
    {
        return $this->friendRequests;
    }

    public function clearExpired(): void
    {
        $duration = $this->socialConfig->friendRequestDuration();
        while (!$this->friendRequestQueue->isEmpty()) {
            $request = $this->friendRequestQueue->top();
            if (!$request instanceof FriendRequest) {
                throw new LogicException('Current value was not a FriendRequest!');
            }
            $notTimedOut = $request->creationTime() + $duration > time();
            if ($notTimedOut && !$request->claimed) {
                break;
            }
            $this->friendRequestQueue->extract();
            unset($this->friendRequestsByReceiver[$request->receiver()]);
        }
    }
}

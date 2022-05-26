-- #!sqlite
-- #{ quickfriends
-- # { init
CREATE TABLE IF NOT EXISTS quickfriends_player_data (
    uuid CHAR(36) PRIMARY KEY,
    username CHAR(100) NOT NULL,
    last_os CHAR(20) NOT NULL,
    last_join_time TIMESTAMP NOT NULL,
    prefers_text BOOLEAN NOT NULL CHECK (prefers_text IN (0, 1)),
    os_visibility INT NOT NULL,
    mute_friend_requests BOOLEAN NOT NULL CHECK (mute_friend_requests IN (0, 1))
);
-- #&
CREATE TABLE IF NOT EXISTS quickfriends_player_friends (
    requester CHAR(36) NOT NULL,
    accepter CHAR(36) NOT NULL,
    creation_time TIMESTAMP NOT NULL,

    PRIMARY KEY(requester, accepter),

    FOREIGN KEY (requester)
        REFERENCES quickfriends_player_data(uuid)
        ON DELETE CASCADE,
    FOREIGN KEY (accepter)
        REFERENCES quickfriends_player_data(uuid)
        ON DELETE CASCADE
)
-- #&
CREATE TABLE IF NOT EXISTS quickfriends_player_blocked (
    player CHAR(36) NOT NULL,
    blocked CHAR(36) NOT NULL,
    creation_time TIMESTAMP NOT NULL,

    PRIMARY KEY(player, blocked),

    FOREIGN KEY (player)
        REFERENCES quickfriends_player_data(uuid)
        ON DELETE CASCADE,
    FOREIGN KEY (blocked)
        REFERENCES quickfriends_player_data(uuid)
        ON DELETE CASCADE
)
-- # }
-- # { get_player_data
-- #     :uuid string
SELECT * FROM quickfriends_player_data
WHERE uuid=:uuid;
-- # }
-- # { touch_player_data
-- #     :uuid string
-- #     :username string
-- #     :last_os string
-- #     :last_join_time int
-- #     :default_prefers_text bool
-- #     :default_os_visibility int
-- #     :default_mute_friend_requests bool
INSERT INTO quickfriends_player_data (
  uuid, username, last_os, last_join_time,
  prefers_text, os_visibility, mute_friend_requests
) VALUES (
  :uuid, :username, :last_os, :last_join_time,
  :default_prefers_text, :default_os_visibility, :default_mute_friend_requests
) ON CONFLICT(uuid) DO UPDATE SET username=:username, last_os=:last_os, last_join_time=:last_join_time;
-- # }
-- # { update_player_data
-- #     :uuid string
-- #     :username string
-- #     :last_os string
-- #     :last_join_time int
-- #     :prefers_text bool
-- #     :os_visibility int
-- #     :mute_friend_requests bool
REPLACE INTO quickfriends_player_data (
  uuid, username, last_os, last_join_time,
  prefers_text, os_visibility, mute_friend_requests
) VALUES (
  :uuid, :username, :last_os, :last_join_time,
  :prefers_text, :os_visibility, :mute_friend_requests
);
-- # }
-- # { add_friend
-- #     :requester_uuid string
-- #     :requester_username string
-- #     :requester_last_os string
-- #     :requester_last_join_time int
-- #     :accepter_uuid string
-- #     :accepter_username string
-- #     :accepter_last_os string
-- #     :accepter_last_join_time int
-- #     :default_prefers_text bool
-- #     :default_os_visibility int
-- #     :default_mute_friend_requests bool
-- #     :creation_time int
INSERT INTO quickfriends_player_data (
  uuid, username, last_os, last_join_time,
  prefers_text, os_visibility, mute_friend_requests
) VALUES (
  :requester_uuid, :requester_username, :requester_last_os, :requester_last_join_time,
  :default_prefers_text, :default_os_visibility, :default_mute_friend_requests
) ON CONFLICT(uuid) DO UPDATE SET username=:requester_username, last_os=:requester_last_os, last_join_time=:requester_last_join_time;
-- #&
INSERT INTO quickfriends_player_data (
  uuid, username, last_os, last_join_time,
  prefers_text, os_visibility, mute_friend_requests
) VALUES (
  :accepter_uuid, :accepter_username, :accepter_last_os, :accepter_last_join_time,
  :default_prefers_text, :default_os_visibility, :default_mute_friend_requests
) ON CONFLICT(uuid) DO UPDATE SET username=:accepter_username, last_os=:accepter_last_os, last_join_time=:accepter_last_join_time;
-- #&
INSERT INTO quickfriends_player_friends
(requester, accepter, creation_time)
VALUES (:requester_uuid, :accepter_uuid, :creation_time);
-- # }
-- # { remove_friend
-- #     :uuids list:string
DELETE FROM quickfriends_player_friends
WHERE requester IN :uuids AND accepter IN :uuids;
-- # }
-- # { list_friends
-- #     :uuid string
SELECT friend.requester AS requester_uuid, friend.accepter AS accepter_uuid, requester.username AS requester_username, requester.last_os AS requester_last_os, requester.last_join_time AS requester_last_join_time, requester.prefers_text AS requester_prefers_text, requester.os_visibility AS requester_os_visibility, requester.mute_friend_requests AS requester_mute_friend_requests, accepter.uuid AS accepter_uuid, accepter.username AS accepter_username, accepter.last_os AS accepter_last_os, accepter.last_join_time AS accepter_last_join_time, accepter.prefers_text AS accepter_prefers_text, accepter.os_visibility AS accepter_os_visibility, accepter.mute_friend_requests AS accepter_mute_friend_requests, friend.creation_time AS creation_time
FROM quickfriends_player_friends friend
INNER JOIN quickfriends_player_data requester
    ON requester.uuid = friend.requester
INNER JOIN quickfriends_player_data accepter
    ON accepter.uuid = friend.accepter
WHERE requester=:uuid OR accepter=:uuid;
-- # }
-- # { get_friendship
-- #     :uuids list:string
SELECT friend.requester AS requester_uuid, friend.accepter AS accepter_uuid, requester.username AS requester_username, requester.last_os AS requester_last_os, requester.last_join_time AS requester_last_join_time, requester.prefers_text AS requester_prefers_text, requester.os_visibility AS requester_os_visibility, requester.mute_friend_requests AS requester_mute_friend_requests, accepter.uuid AS accepter_uuid, accepter.username AS accepter_username, accepter.last_os AS accepter_last_os, accepter.last_join_time AS accepter_last_join_time, accepter.prefers_text AS accepter_prefers_text, accepter.os_visibility AS accepter_os_visibility, accepter.mute_friend_requests AS accepter_mute_friend_requests, friend.creation_time AS creation_time
FROM quickfriends_player_friends friend
INNER JOIN quickfriends_player_data requester
    ON requester.uuid = friend.requester
INNER JOIN quickfriends_player_data accepter
    ON accepter.uuid = friend.accepter
WHERE requester IN :uuids AND accepter IN :uuids;
-- # }
-- # { add_block
-- #     :player_uuid string
-- #     :player_username string
-- #     :player_last_os string
-- #     :player_last_join_time int
-- #     :blocked_uuid string
-- #     :blocked_username string
-- #     :blocked_last_os string
-- #     :blocked_last_join_time int
-- #     :default_prefers_text bool
-- #     :default_os_visibility int
-- #     :default_mute_friend_requests bool
-- #     :creation_time int
INSERT INTO quickfriends_player_data (
  uuid, username, last_os, last_join_time,
  prefers_text, os_visibility, mute_friend_requests
) VALUES (
  :player_uuid, :player_username, :player_last_os, :player_last_join_time,
  :default_prefers_text, :default_os_visibility, :default_mute_friend_requests
) ON CONFLICT(uuid) DO UPDATE SET username=:player_username, last_os=:player_last_os, last_join_time=:player_last_join_time;
-- #&
INSERT INTO quickfriends_player_data (
  uuid, username, last_os, last_join_time,
  prefers_text, os_visibility, mute_friend_requests
) VALUES (
  :blocked_uuid, :blocked_username, :blocked_last_os, :blocked_last_join_time,
  :default_prefers_text, :default_os_visibility, :default_mute_friend_requests
) ON CONFLICT(uuid) DO UPDATE SET username=:blocked_username, last_os=:blocked_last_os, last_join_time=:blocked_last_join_time;
-- #&
INSERT INTO quickfriends_player_blocked
(player, blocked, creation_time) VALUES
(:player_uuid, :blocked_uuid, :creation_time);
-- # }
-- # { remove_block
-- #     :player string
-- #     :blocked string
DELETE FROM quickfriends_player_blocked
WHERE player=:player AND blocked=:blocked;
-- # }
-- # { get_blocks
-- #     :uuids list:string
SELECT block.player AS player_uuid, block.blocked AS blocked_uuid, player.username AS player_username, player.last_os AS player_last_os, player.last_join_time AS player_last_join_time, player.prefers_text AS player_prefers_text, player.os_visibility AS player_os_visibility, player.mute_friend_requests AS player_mute_friend_requests, blocked.username AS blocked_username, blocked.last_os AS blocked_last_os, blocked.last_join_time AS blocked_last_join_time, blocked.prefers_text AS blocked_prefers_text, blocked.os_visibility AS blocked_os_visibility, blocked.mute_friend_requests AS blocked_mute_friend_requests, block.creation_time AS creation_time
FROM quickfriends_player_blocked block
INNER JOIN quickfriends_player_data player
    ON player.uuid = block.player
INNER JOIN quickfriends_player_data blocked
    ON blocked.uuid = block.blocked
WHERE block.player IN :uuids AND block.blocked IN :uuids;
-- # }
-- # { list_blocked
-- #     :player string
SELECT block.player AS player_uuid, block.blocked AS blocked_uuid, player.username AS player_username, player.last_os AS player_last_os, player.last_join_time AS player_last_join_time, player.prefers_text AS player_prefers_text, player.os_visibility AS player_os_visibility, player.mute_friend_requests AS player_mute_friend_requests, blocked.username AS blocked_username, blocked.last_os AS blocked_last_os, blocked.last_join_time AS blocked_last_join_time, blocked.prefers_text AS blocked_prefers_text, blocked.os_visibility AS blocked_os_visibility, blocked.mute_friend_requests AS blocked_mute_friend_requests, block.creation_time AS creation_time
FROM quickfriends_player_blocked block
INNER JOIN quickfriends_player_data player
    ON player.uuid = block.player
INNER JOIN quickfriends_player_data blocked
    ON blocked.uuid = block.blocked
WHERE block.player=:player;
-- # }
-- # { list_blocked_by
-- #     :blocked string
SELECT block.player AS player_uuid, block.blocked AS blocked_uuid, player.username AS player_username, player.last_os AS player_last_os, player.last_join_time AS player_last_join_time, player.prefers_text AS player_prefers_text, player.os_visibility AS player_os_visibility, player.mute_friend_requests AS player_mute_friend_requests, blocked.username AS blocked_username, blocked.last_os AS blocked_last_os, blocked.last_join_time AS blocked_last_join_time, blocked.prefers_text AS blocked_prefers_text, blocked.os_visibility AS blocked_os_visibility, blocked.mute_friend_requests AS blocked_mute_friend_requests, block.creation_time AS creation_time
FROM quickfriends_player_blocked block
INNER JOIN quickfriends_player_data player
    ON player.uuid = block.player
INNER JOIN quickfriends_player_data blocked
    ON blocked.uuid = block.blocked
WHERE block.blocked=:blocked;
-- # }
-- #}

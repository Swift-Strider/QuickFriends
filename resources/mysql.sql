-- #!mysql
-- #{ quickfriends
-- # { init
CREATE TABLE IF NOT EXISTS quickfriends_player_data (
    uuid CHAR(36) PRIMARY KEY,
    username CHAR(100) NOT NULL,
    last_os CHAR(20) NOT NULL,
    last_join_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
-- #&
CREATE TABLE IF NOT EXISTS quickfriends_user_preferences (
    uuid CHAR(36) PRIMARY KEY,
    prefers_text BOOLEAN NOT NULL CHECK (prefers_text IN (0, 1)),
    os_visibility BOOLEAN NOT NULL,
    mute_friend_requests BOOLEAN NOT NULL CHECK (mute_friend_requests IN (0, 1)),

    FOREIGN KEY (uuid)
        REFERENCES quickfriends_player_data(uuid)
        ON DELETE CASCADE
);
-- #&
CREATE TABLE IF NOT EXISTS quickfriends_player_friends (
    requester CHAR(36) NOT NULL,
    accepter CHAR(36) NOT NULL,
    creation_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

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
    creation_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

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
WHERE uuid = :uuid;
-- # }
-- # { set_player_data
-- #     :uuid string
-- #     :username string
-- #     :last_os string
REPLACE INTO quickfriends_player_data
(uuid, username, last_os)
VALUES (:uuid, :username, :last_os);
-- # }
-- # { get_user_preferences
-- #     :uuid string
SELECT * FROM quickfriends_user_preferences
WHERE uuid = :uuid;
-- # }
-- # { set_user_preferences
-- #     :uuid string
-- #     :prefers_text int
-- #     :os_visibility int
-- #     :mute_friend_requests int
REPLACE INTO quickfriends_user_preferences
(uuid, prefers_text, os_visibility, mute_friend_requests)
VALUES (:uuid, :prefers_text, :os_visibility, :mute_friend_requests);
-- # }
-- # { add_friend
-- #     :requester string
-- #     :accepter string
REPLACE INTO quickfriends_player_friends
(requester, accepter)
VALUES (:requester, :accepter);
-- # }
-- # { remove_friend
-- #     :uuids list:string
DELETE FROM quickfriends_player_friends
WHERE requester IN :uuids AND accepter IN :uuids;
-- # }
-- # { list_friends
-- #     :uuid string
SELECT * FROM quickfriends_player_friends
WHERE requester = :uuid OR accepter = :uuid;
-- # }
-- # { block_player
-- #     :player string
-- #     :blocked string
REPLACE INTO quickfriends_player_blocked
(player, blocked)
VALUES (:player, :blocked);
-- # }
-- # { unblock_player
-- #     :player string
-- #     :blocked string
DELETE FROM quickfriends_player_blocked
WHERE player = :player AND blocked = :blocked;
-- # }
-- # { list_blocked
-- #     :uuid string
SELECT * FROM quickfriends_player_blocked
WHERE player = :uuid;
-- # }
-- # { list_blocked_by
-- #     :uuid string
SELECT * FROM quickfriends_player_blocked
WHERE blocked = :uuid;
-- # }
-- #}

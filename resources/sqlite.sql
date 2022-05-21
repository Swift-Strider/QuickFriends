-- #!sqlite
-- #{ quickfriends
-- # { init
CREATE TABLE IF NOT EXISTS quickfriends_player_data (
    uuid TEXT PRIMARY KEY,
    username TEXT NOT NULL,
    last_os TEXT NOT NULL,
    last_join_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
-- #&
CREATE TABLE IF NOT EXISTS quickfriends_user_preferences (
    uuid TEXT PRIMARY KEY,
    prefers_text BOOLEAN NOT NULL CHECK (prefers_text IN (0, 1)),
    os_visibility BOOLEAN NOT NULL,
    mute_friend_requests BOOLEAN NOT NULL CHECK (mute_friend_requests IN (0, 1)),

    FOREIGN KEY (uuid)
        REFERENCES quickfriends_player_data(uuid)
        ON DELETE CASCADE
);
-- #&
CREATE TABLE IF NOT EXISTS quickfriends_player_friends (
    requester TEXT NOT NULL,
    accepter TEXT NOT NULL,
    creation_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY(requester, accepter),

    FOREIGN KEY (requester)
        REFERENCES quickfriends_player_data(uuid)
        ON DELETE CASCADE,
    FOREIGN KEY (accepter)
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
-- #}

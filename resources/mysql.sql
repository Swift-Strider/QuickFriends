-- #!mysql
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
-- #&
DROP PROCEDURE IF EXISTS get_friend_request_status;
-- #&
CREATE PROCEDURE get_friend_request_status(
    IN requester_uuid CHAR(36),
    IN requester_username CHAR(100),
    IN requester_last_os CHAR(20),
    IN requester_last_join_time TIMESTAMP,
    IN receiver_uuid CHAR(36),
    IN receiver_username CHAR(100),
    IN receiver_last_os CHAR(20),
    IN receiver_last_join_time TIMESTAMP,
    IN default_prefers_text BOOLEAN,
    IN default_os_visibility INT,
    IN default_mute_friend_requests BOOLEAN,
    IN max_friends INT,
    OUT status INT
)
BEGIN
    DECLARE has_blocked BOOLEAN;
    DECLARE is_blocked_by BOOLEAN;
    DECLARE are_friends BOOLEAN;
    DECLARE num_friends INT;
    DECLARE mute BOOLEAN;

    INSERT INTO quickfriends_player_data (
    uuid, username, last_os, last_join_time,
    prefers_text, os_visibility, mute_friend_requests
    ) VALUES (
    requester_uuid, requester_username, requester_last_os, requester_last_join_time,
    default_prefers_text, default_os_visibility, default_mute_friend_requests
    ) ON DUPLICATE KEY UPDATE username=requester_username, last_os=requester_last_os, last_join_time=requester_last_join_time;

    INSERT INTO quickfriends_player_data (
    uuid, username, last_os, last_join_time,
    prefers_text, os_visibility, mute_friend_requests
    ) VALUES (
    receiver_uuid, receiver_username, receiver_last_os, receiver_last_join_time,
    default_prefers_text, default_os_visibility, default_mute_friend_requests
    ) ON DUPLICATE KEY UPDATE username=receiver_username, last_os=receiver_last_os, last_join_time=receiver_last_join_time;

    SELECT COUNT(*) INTO has_blocked FROM quickfriends_player_blocked
    WHERE player=requester_uuid AND blocked=receiver_uuid;

    SELECT COUNT(*) INTO is_blocked_by FROM quickfriends_player_blocked
    WHERE player=receiver_uuid AND blocked=requester_uuid;

    SELECT COUNT(*) INTO are_friends FROM quickfriends_player_friends
    WHERE requester IN (requester_uuid, receiver_uuid)
        AND accepter IN (requester_uuid, receiver_uuid);

    SELECT COUNT(*) INTO num_friends FROM quickfriends_player_friends
    WHERE requester=requester_uuid OR accepter=receiver_uuid;

    SELECT mute_friend_requests INTO mute FROM quickfriends_player_data
    WHERE uuid=receiver_uuid;

    IF has_blocked THEN
        SET status=2;
    ELSEIF is_blocked_by THEN
        SET status=3;
    ELSEIF are_friends THEN
        SET status=4;
    ELSEIF num_friends >= max_friends THEN
        SET status=5;
    ELSEIF mute=1 THEN
        SET status=1;
    ELSE
        SET status=0;
    END IF;
END;
-- #&
DROP PROCEDURE IF EXISTS add_friend;
-- #&
CREATE PROCEDURE add_friend(
    IN requester_uuid CHAR(36),
    IN requester_username CHAR(100),
    IN requester_last_os CHAR(20),
    IN requester_last_join_time TIMESTAMP,
    IN accepter_uuid CHAR(36),
    IN accepter_username CHAR(100),
    IN accepter_last_os CHAR(20),
    IN accepter_last_join_time TIMESTAMP,
    IN creation_time TIMESTAMP,
    IN default_prefers_text BOOLEAN,
    IN default_os_visibility INT,
    IN default_mute_friend_requests BOOLEAN,
    IN max_friends INT,
    OUT status INT
)
BEGIN
    DECLARE are_friends BOOLEAN;
    DECLARE requester_friend_count INT;
    DECLARE accepter_friend_count INT;

    INSERT INTO quickfriends_player_data (
    uuid, username, last_os, last_join_time,
    prefers_text, os_visibility, mute_friend_requests
    ) VALUES (
    requester_uuid, requester_username, requester_last_os, requester_last_join_time,
    default_prefers_text, default_os_visibility, default_mute_friend_requests
    ) ON DUPLICATE KEY UPDATE username=requester_username, last_os=requester_last_os, last_join_time=requester_last_join_time;

    INSERT INTO quickfriends_player_data (
    uuid, username, last_os, last_join_time,
    prefers_text, os_visibility, mute_friend_requests
    ) VALUES (
    accepter_uuid, accepter_username, accepter_last_os, accepter_last_join_time,
    default_prefers_text, default_os_visibility, default_mute_friend_requests
    ) ON DUPLICATE KEY UPDATE username=accepter_username, last_os=accepter_last_os, last_join_time=accepter_last_join_time;

    DELETE FROM quickfriends_player_blocked
    WHERE player=requester_uuid AND blocked=accepter_uuid;

    DELETE FROM quickfriends_player_blocked
    WHERE player=accepter_uuid AND blocked=requester_uuid;

    SELECT COUNT(*) INTO are_friends FROM quickfriends_player_friends
    WHERE requester IN (requester_uuid, accepter_uuid)
        AND accepter IN (requester_uuid, accepter_uuid);

    SELECT COUNT(*) INTO requester_friend_count FROM quickfriends_player_friends
    WHERE requester=requester_uuid OR accepter=requester_uuid;

    SELECT COUNT(*) INTO accepter_friend_count FROM quickfriends_player_friends
    WHERE requester=accepter_uuid OR accepter=accepter_uuid;

    IF are_friends THEN
        SET status=1;
    ELSEIF requester_friend_count >= max_friends THEN
        SET status=2;
    ELSEIF accepter_friend_count >= max_friends THEN
        SET status=3;
    ELSE
        SET status=0;
        INSERT INTO quickfriends_player_friends
        (requester, accepter, creation_time)
        VALUES (requester_uuid, accepter_uuid, creation_time);
    END IF;
END;
-- #&
DROP PROCEDURE IF EXISTS remove_friend;
-- #&
CREATE PROCEDURE remove_friend(
    IN player1 CHAR(36),
    IN player2 CHAR(36),
    OUT o_requester_uuid CHAR(36),
    OUT o_requester_username CHAR(100),
    OUT o_requester_last_os CHAR(20),
    OUT o_requester_last_join_time TIMESTAMP,
    OUT o_requester_prefers_text BOOLEAN,
    OUT o_requester_os_visibility INT,
    OUT o_requester_mute_friend_requests BOOLEAN,
    OUT o_accepter_uuid CHAR(36),
    OUT o_accepter_username CHAR(100),
    OUT o_accepter_last_os CHAR(20),
    OUT o_accepter_last_join_time TIMESTAMP,
    OUT o_accepter_prefers_text BOOLEAN,
    OUT o_accepter_os_visibility INT,
    OUT o_accepter_mute_friend_requests BOOLEAN,
    OUT o_creation_time TIMESTAMP,
    OUT status INT
)
BEGIN
    DECLARE are_friends BOOLEAN;

    SELECT COUNT(*), requester.uuid, requester.username, requester.last_os, requester.last_join_time, requester.prefers_text, requester.os_visibility, requester.mute_friend_requests, accepter.uuid, accepter.username, accepter.last_os, accepter.last_join_time, accepter.prefers_text, accepter.os_visibility, accepter.mute_friend_requests, friend.creation_time
    INTO are_friends, o_requester_uuid, o_requester_username, o_requester_last_os, o_requester_last_join_time, o_requester_prefers_text, o_requester_os_visibility, o_requester_mute_friend_requests, o_accepter_uuid, o_accepter_username, o_accepter_last_os, o_accepter_last_join_time, o_accepter_prefers_text, o_accepter_os_visibility, o_accepter_mute_friend_requests, o_creation_time
    FROM quickfriends_player_friends friend
    INNER JOIN quickfriends_player_data requester
        ON requester.uuid = friend.requester
    INNER JOIN quickfriends_player_data accepter
        ON accepter.uuid = friend.accepter
    WHERE requester IN (player1, player2)
        AND accepter IN (player1, player2)
    GROUP BY requester.uuid, accepter.uuid, requester.username, requester.last_os, requester.last_join_time, requester.prefers_text, requester.os_visibility, requester.mute_friend_requests, accepter.username, accepter.last_os, accepter.last_join_time, accepter.prefers_text, accepter.os_visibility, accepter.mute_friend_requests, friend.creation_time;

    IF are_friends THEN
        SET status=-1;
        DELETE FROM quickfriends_player_friends
        WHERE requester IN (player1, player2)
            AND accepter IN (player1, player2);
    ELSE
        SET status=0;
    END IF;
END;
-- #&
DROP PROCEDURE IF EXISTS add_block;
-- #&
CREATE PROCEDURE add_block(
    IN player_uuid CHAR(36),
    IN player_username CHAR(100),
    IN player_last_os CHAR(20),
    IN player_last_join_time TIMESTAMP,
    IN blocked_uuid CHAR(36),
    IN blocked_username CHAR(100),
    IN blocked_last_os CHAR(20),
    IN blocked_last_join_time TIMESTAMP,
    IN creation_time TIMESTAMP,
    IN default_prefers_text BOOLEAN,
    IN default_os_visibility INT,
    IN default_mute_friend_requests BOOLEAN,
    OUT status INT
)
BEGIN
    DECLARE already_blocked BOOLEAN;
    DECLARE rm_status INT;
    DECLARE _timestamp TIMESTAMP;
    DECLARE _uuid CHAR(36);
    DECLARE _username CHAR(100);
    DECLARE _os CHAR(100);
    DECLARE _int INT;

    INSERT INTO quickfriends_player_data (
    uuid, username, last_os, last_join_time,
    prefers_text, os_visibility, mute_friend_requests
    ) VALUES (
    player_uuid, player_username, player_last_os, player_last_join_time,
    default_prefers_text, default_os_visibility, default_mute_friend_requests
    ) ON DUPLICATE KEY UPDATE username=player_username, last_os=player_last_os, last_join_time=player_last_join_time;

    INSERT INTO quickfriends_player_data (
    uuid, username, last_os, last_join_time,
    prefers_text, os_visibility, mute_friend_requests
    ) VALUES (
    blocked_uuid, blocked_username, blocked_last_os, blocked_last_join_time,
    default_prefers_text, default_os_visibility, default_mute_friend_requests
    ) ON DUPLICATE KEY UPDATE username=blocked_username, last_os=blocked_last_os, last_join_time=blocked_last_join_time;

    SELECT COUNT(*) INTO already_blocked FROM quickfriends_player_blocked
    WHERE player=player_uuid AND blocked=blocked_uuid;

    IF already_blocked THEN
        SET status=2;
    ELSE
        CALL remove_friend(player_uuid, blocked_uuid, _uuid, _username, _os, _timestamp, _int, _int, _int, _uuid, _username, _os, _timestamp, _int, _int, _int, _timestamp, rm_status);
        IF rm_status = -1 THEN
            SET status=1;
        ELSE
            SET status=0;
        END IF;
        INSERT INTO quickfriends_player_blocked
        (player, blocked, creation_time)
        VALUES (player_uuid, blocked_uuid, creation_time);
    END IF;
END;
-- #&
DROP PROCEDURE IF EXISTS remove_block;
-- #&
CREATE PROCEDURE remove_block(
    IN i_player CHAR(36),
    IN i_blocked CHAR(36),
    OUT o_player_username CHAR(100),
    OUT o_player_last_os CHAR(20),
    OUT o_player_last_join_time TIMESTAMP,
    OUT o_player_prefers_text BOOLEAN,
    OUT o_player_os_visibility INT,
    OUT o_player_mute_friend_requests BOOLEAN,
    OUT o_blocked_username CHAR(100),
    OUT o_blocked_last_os CHAR(20),
    OUT o_blocked_last_join_time TIMESTAMP,
    OUT o_blocked_prefers_text BOOLEAN,
    OUT o_blocked_os_visibility INT,
    OUT o_blocked_mute_friend_requests BOOLEAN,
    OUT o_creation_time TIMESTAMP,
    OUT status INT
)
BEGIN
    DECLARE has_blocked BOOLEAN;

    SELECT COUNT(*), player.username, player.last_os, player.last_join_time, player.prefers_text, player.os_visibility, player.mute_friend_requests, blocked.username, blocked.last_os, blocked.last_join_time, blocked.prefers_text, blocked.os_visibility, blocked.mute_friend_requests, block.creation_time
    INTO has_blocked, o_player_username, o_player_last_os, o_player_last_join_time, o_player_prefers_text, o_player_os_visibility, o_player_mute_friend_requests, o_blocked_username, o_blocked_last_os, o_blocked_last_join_time, o_blocked_prefers_text, o_blocked_os_visibility, o_blocked_mute_friend_requests, o_creation_time
    FROM quickfriends_player_blocked block
    INNER JOIN quickfriends_player_data player
        ON player.uuid = block.player
    INNER JOIN quickfriends_player_data blocked
        ON blocked.uuid = block.blocked
    WHERE player=i_player AND blocked=i_blocked
    GROUP BY player.username, player.last_os, player.last_join_time, player.prefers_text, player.os_visibility, player.mute_friend_requests, blocked.username, blocked.last_os, blocked.last_join_time, blocked.prefers_text, blocked.os_visibility, blocked.mute_friend_requests, block.creation_time;

    IF has_blocked THEN
        SET status=-1;
        DELETE FROM quickfriends_player_blocked
        WHERE player=i_player AND blocked=i_blocked;
    ELSE
        SET status=0;
    END IF;
END;
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
  :uuid, :username, :last_os, FROM_UNIXTIME(:last_join_time),
  :default_prefers_text, :default_os_visibility, :default_mute_friend_requests
) ON DUPLICATE KEY UPDATE username=:username, last_os=:last_os, last_join_time=FROM_UNIXTIME(:last_join_time);
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
  :uuid, :username, :last_os, FROM_UNIXTIME(:last_join_time),
  :prefers_text, :os_visibility, :mute_friend_requests
);
-- # }
-- # { get_friend_request_status
-- #     :requester_uuid string
-- #     :requester_username string
-- #     :requester_last_os string
-- #     :requester_last_join_time int
-- #     :receiver_uuid string
-- #     :receiver_username string
-- #     :receiver_last_os string
-- #     :receiver_last_join_time int
-- #     :default_prefers_text bool
-- #     :default_os_visibility int
-- #     :default_mute_friend_requests bool
-- #     :max_friends int
START TRANSACTION;
-- #&
CALL get_friend_request_status(
    :requester_uuid,
    :requester_username,
    :requester_last_os,
    FROM_UNIXTIME(:requester_last_join_time),
    :receiver_uuid,
    :receiver_username,
    :receiver_last_os,
    FROM_UNIXTIME(:receiver_last_join_time),
    :default_prefers_text,
    :default_os_visibility,
    :default_mute_friend_requests,
    :max_friends,
    @status
);
-- #&
COMMIT;
-- #&
SELECT @status AS status;
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
-- #     :max_friends int
START TRANSACTION;
-- #&
CALL add_friend(
    :requester_uuid,
    :requester_username,
    :requester_last_os,
    FROM_UNIXTIME(:requester_last_join_time),
    :accepter_uuid,
    :accepter_username,
    :accepter_last_os,
    FROM_UNIXTIME(:accepter_last_join_time),
    FROM_UNIXTIME(:creation_time),
    :default_prefers_text,
    :default_os_visibility,
    :default_mute_friend_requests,
    :max_friends,
    @status
);
-- #&
COMMIT;
-- #&
SELECT @status AS status;
-- # }
-- # { remove_friend
-- #     :player1 string
-- #     :player2 string
START TRANSACTION;
-- #&
CALL remove_friend(
    :player1,
    :player2,
    @requester_uuid,
    @requester_username,
    @requester_last_os,
    @requester_last_join_time,
    @requester_prefers_text,
    @requester_os_visibility,
    @requester_mute_friend_requests,
    @accepter_uuid,
    @accepter_username,
    @accepter_last_os,
    @accepter_last_join_time,
    @accepter_prefers_text,
    @accepter_os_visibility,
    @accepter_mute_friend_requests,
    @creation_time,
    @status
);
-- #&
COMMIT;
-- #&
SELECT @status AS status,
    @requester_uuid AS requester_uuid,
    @requester_username AS requester_username,
    @requester_last_os AS requester_last_os,
    @requester_last_join_time AS requester_last_join_time,
    @requester_prefers_text AS requester_prefers_text,
    @requester_os_visibility AS requester_os_visibility,
    @requester_mute_friend_requests AS requester_mute_friend_requests,
    @accepter_uuid AS accepter_uuid,
    @accepter_username AS accepter_username,
    @accepter_last_os AS accepter_last_os,
    @accepter_last_join_time AS accepter_last_join_time,
    @accepter_prefers_text AS accepter_prefers_text,
    @accepter_os_visibility AS accepter_os_visibility,
    @accepter_mute_friend_requests AS accepter_mute_friend_requests,
    @creation_time AS creation_time;
-- # }
-- # { list_friends
-- #     :uuid string
SELECT friend.requester AS requester_uuid, friend.accepter AS accepter_uuid, requester.username AS requester_username, requester.last_os AS requester_last_os, requester.last_join_time AS requester_last_join_time, requester.prefers_text AS requester_prefers_text, requester.os_visibility AS requester_os_visibility, requester.mute_friend_requests AS requester_mute_friend_requests, accepter.uuid AS accepter_uuid, accepter.username AS accepter_username, accepter.last_os AS accepter_last_os, accepter.last_join_time AS accepter_last_join_time, accepter.prefers_text AS accepter_prefers_text, accepter.os_visibility AS accepter_os_visibility, accepter.mute_friend_requests AS accepter_mute_friend_requests, friend.creation_time AS creation_time
FROM quickfriends_player_friends friend
INNER JOIN quickfriends_player_data requester
    ON requester.uuid = friend.requester
INNER JOIN quickfriends_player_data accepter
    ON accepter.uuid = friend.accepter
WHERE friend.requester=:uuid OR friend.accepter=:uuid;
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
START TRANSACTION;
-- #&
CALL add_block(
    :player_uuid,
    :player_username,
    :player_last_os,
    FROM_UNIXTIME(:player_last_join_time),
    :blocked_uuid,
    :blocked_username,
    :blocked_last_os,
    FROM_UNIXTIME(:blocked_last_join_time),
    FROM_UNIXTIME(:creation_time),
    :default_prefers_text,
    :default_os_visibility,
    :default_mute_friend_requests,
    @status
);
-- #&
COMMIT;
-- #&
SELECT @status AS status;
-- # }
-- # { remove_block
-- #     :player string
-- #     :blocked string
START TRANSACTION;
-- #&
CALL remove_block(
    :player,
    :blocked,
    @player_username,
    @player_last_os,
    @player_last_join_time,
    @player_prefers_text,
    @player_os_visibility,
    @player_mute_friend_requests,
    @blocked_username,
    @blocked_last_os,
    @blocked_last_join_time,
    @blocked_prefers_text,
    @blocked_os_visibility,
    @blocked_mute_friend_requests,
    @creation_time,
    @status
);
-- #&
COMMIT;
-- #&
SELECT @status AS status,
    :player AS player_uuid,
    :blocked AS blocked_uuid,
    @player_username AS player_username,
    @player_last_os AS player_last_os,
    @player_last_join_time AS player_last_join_time,
    @player_prefers_text AS player_prefers_text,
    @player_os_visibility AS player_os_visibility,
    @player_mute_friend_requests AS player_mute_friend_requests,
    @blocked_username AS blocked_username,
    @blocked_last_os AS blocked_last_os,
    @blocked_last_join_time AS blocked_last_join_time,
    @blocked_prefers_text AS blocked_prefers_text,
    @blocked_os_visibility AS blocked_os_visibility,
    @blocked_mute_friend_requests AS blocked_mute_friend_requests,
    @creation_time AS creation_time;
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

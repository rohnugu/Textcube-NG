-- MySQL 5.7.6+ / 8.0+ compatible.
-- GRANT ... IDENTIFIED BY was deprecated in MySQL 5.7 and removed in MySQL 8.0.
-- User creation and privilege grant are now separated.
USE PlaceholderForDbName;

DROP PROCEDURE IF EXISTS add_user;

CREATE PROCEDURE add_user()
BEGIN
    DECLARE EXIT HANDLER FOR 1044 BEGIN END;
    DECLARE EXIT HANDLER FOR 1396 BEGIN END;
    CREATE USER IF NOT EXISTS 'PlaceholderForDbUser'@'localhost' IDENTIFIED BY 'PlaceholderForDbPassword';
    GRANT ALL PRIVILEGES ON PlaceholderForDbName.* TO 'PlaceholderForDbUser'@'localhost';
    FLUSH PRIVILEGES;
END
;

CALL add_user();

DROP PROCEDURE IF EXISTS add_user;

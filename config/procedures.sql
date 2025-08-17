DELIMITER $

DROP PROCEDURE IF EXISTS `AddCommentAttachment`
$

DROP PROCEDURE IF EXISTS `AddPostAttachment`
$

DROP PROCEDURE IF EXISTS `AddUserIcon`
$

/* Create Stored Procedures */

CREATE PROCEDURE AddCommentAttachment(IN p_name VARCHAR(255), IN p_comment_id BIGINT, OUT p_attachment_id BIGINT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        SET p_attachment_id = NULL;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error creating comment attachment';
    END;

    START TRANSACTION;
    
    -- Insert into attachment table
    INSERT INTO `attachment` (`name`) VALUES (p_name);
    SET p_attachment_id = LAST_INSERT_ID();
    
    -- Insert into comment_attachment table
    INSERT INTO `comment_attachment` (`id`, `comment_id`) 
    VALUES (p_attachment_id, p_comment_id);
    
    COMMIT;
END $

CREATE PROCEDURE AddPostAttachment(IN p_name VARCHAR(255), IN p_post_id BIGINT, OUT p_attachment_id BIGINT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        SET p_attachment_id = NULL;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error creating post attachment';
    END;

    START TRANSACTION;
    
    -- Insert into attachment table
    INSERT INTO `attachment` (`name`) VALUES (p_name);
    SET p_attachment_id = LAST_INSERT_ID();
    
    -- Insert into post_attachment table
    INSERT INTO `post_attachment` (`id`, `post_id`) 
    VALUES (p_attachment_id, p_post_id);
    
    COMMIT;
END
$

CREATE PROCEDURE AddUserIcon (IN p_name VARCHAR(255), IN p_user_id BIGINT,IN p_is_default BOOL, OUT p_attachment_id BIGINT )
BEGIN
    DECLARE v_non_default_count INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        SET p_attachment_id = NULL;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error creating user icon';
    END;

    START TRANSACTION;
    
    -- Check if non-default icon already exists for the user
    IF NOT p_is_default THEN
        SELECT COUNT(*) INTO v_non_default_count 
        FROM `user_icon` 
        WHERE `user_id` = p_user_id AND `is_default` = FALSE;
        
        IF v_non_default_count >= 1 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Cannot add non-default icon: user already has a non-default icon';
        END IF;
    END IF;
    
    -- Insert into attachment table
    INSERT INTO `attachment` (`name`) VALUES (p_name);
    SET p_attachment_id = LAST_INSERT_ID();
    
    -- Insert into user_icon table
    INSERT INTO `user_icon` (`id`, `is_default`, `user_id`) 
    VALUES (p_attachment_id, p_is_default, p_user_id);
    
    COMMIT;
END
$

DELIMITER ;

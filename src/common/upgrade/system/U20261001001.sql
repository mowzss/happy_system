ALTER TABLE `ha_system_spider_logs`
    ADD `referer` VARCHAR(200) NULL     DEFAULT NULL AFTER `user_agent`,
    ADD `device`  INT(1)       NOT NULL DEFAULT '0' COMMENT '设备 1pc 2移动' AFTER `referer`,
    ADD INDEX `referer` (`referer`),
    ADD INDEX `device` (`device`);

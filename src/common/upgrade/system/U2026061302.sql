ALTER TABLE `ha_system_spider_logs`
    ADD `domain` VARCHAR(255) NULL DEFAULT NULL COMMENT '抓取域名' AFTER `module`,
    ADD INDEX `domain` (`domain`);

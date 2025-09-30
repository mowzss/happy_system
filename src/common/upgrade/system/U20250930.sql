# 增加长期友情链接字段
ALTER TABLE `ha_system_links`
    ADD `is_long` INT(1) NOT NULL DEFAULT '0' AFTER `end_time`,
    ADD INDEX `is_long` (`is_long`);

ALTER TABLE `ha_system_icon`
    ADD `is_show` INT(1) NOT NULL DEFAULT '0' COMMENT '是否后台引用显示' AFTER `title`;

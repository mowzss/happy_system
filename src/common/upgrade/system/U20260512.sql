ALTER TABLE `ha_system_nav`
    ADD `active` VARCHAR(64) NULL DEFAULT NULL COMMENT '菜单激活标记' AFTER `params`;

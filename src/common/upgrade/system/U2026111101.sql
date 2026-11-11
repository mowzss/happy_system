-- 附件表升级（已有站点手动执行）
-- 1) 扩展名字段扩容：varchar(4) 会截断 webp/jpeg 之外的长后缀
ALTER TABLE `ha_system_attachment`
    MODIFY COLUMN `ext` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件类型';

-- 2) 历史数据修正：旧版 save() 把 driver 写死为 local，云存储站点删除附件会走错磁盘。
--    执行前请把 {oss_domain} / {qiniu_domain} 替换为后台实际配置的域名。
-- UPDATE `ha_system_attachment` SET `driver` = 'oss'
--   WHERE `driver` = 'local' AND `url` LIKE 'https://{oss_domain}/%';
-- UPDATE `ha_system_attachment` SET `driver` = 'qiniu'
--   WHERE `driver` = 'local' AND `url` LIKE 'https://{qiniu_domain}/%';

-- 3) 直传对象 key 使用 uploads/{uid}/{Ymd}/ 前缀，历史 local 记录不受影响。

--
-- 表的结构 `ha_user_oauth`
--

CREATE TABLE `ha_user_oauth`
(
    `id`          int(11)     NOT NULL,
    `uid`         int(11)     NOT NULL COMMENT '用户id',
    `type`        varchar(64) NOT NULL COMMENT 'oauth类型',
    `openid`      varchar(64) NOT NULL COMMENT '唯一码',
    `create_time` int(11)     NOT NULL DEFAULT '0' COMMENT '创建日期',
    `update_time` int(11)     NOT NULL DEFAULT '0' COMMENT '更新日期'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='快捷登录';

--
-- 转储表的索引
--

--
-- 表的索引 `ha_user_oauth`
--
ALTER TABLE `ha_user_oauth`
    ADD PRIMARY KEY (`id`),
    ADD KEY `typeanduid` (`type`, `uid`),
    ADD KEY `type` (`type`, `openid`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ha_user_oauth`
--
ALTER TABLE `ha_user_oauth`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

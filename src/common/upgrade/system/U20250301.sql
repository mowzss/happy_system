--
-- 表的结构 `ha_system_jobs`
--

CREATE TABLE `ha_system_jobs`
(
    `id`             int(11) UNSIGNED NOT NULL,
    `queue`          varchar(255)        DEFAULT NULL,
    `payload`        longtext,
    `attempts`       tinyint(4) UNSIGNED DEFAULT NULL,
    `reserve_time`   int(11) UNSIGNED    DEFAULT NULL,
    `available_time` int(11) UNSIGNED    DEFAULT NULL,
    `create_time`    int(11) UNSIGNED    DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_jobs_failed`
--

CREATE TABLE `ha_system_jobs_failed`
(
    `id`         int(11) UNSIGNED NOT NULL,
    `connection` text,
    `queue`      text,
    `payload`    longtext,
    `exception`  longtext,
    `fail_time`  timestamp        NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_sitemap`
--

CREATE TABLE `ha_system_sitemap`
(
    `id`          int(11) UNSIGNED NOT NULL,
    `module`      varchar(64)      NOT NULL COMMENT '模块',
    `class`       varchar(255)     DEFAULT NULL COMMENT '数据类型',
    `url`         varchar(2555)    NOT NULL COMMENT '生成url',
    `type`        varchar(255)     NOT NULL COMMENT '地图类型',
    `domain`      varchar(255)     DEFAULT '',
    `create_time` int(10) UNSIGNED DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_tasks`
--

CREATE TABLE `ha_system_tasks`
(
    `id`          int(11)   NOT NULL,
    `title`       char(50)           DEFAULT NULL COMMENT '任务名称',
    `exptime`     char(200) NOT NULL DEFAULT '* * * * *' COMMENT '任务周期',
    `task`        text COMMENT '任务命令',
    `data`        longtext COMMENT '附加参数',
    `list`        int(11)   NOT NULL DEFAULT '0' COMMENT '排序',
    `count`       int(11)   NOT NULL DEFAULT '0' COMMENT '执行次数',
    `last_time`   datetime           DEFAULT NULL COMMENT '最后执行时间',
    `next_time`   datetime           DEFAULT NULL COMMENT '下次执行时间',
    `status`      int(1)    NOT NULL DEFAULT '1' COMMENT '任务状态',
    `output_msg`  text,
    `create_time` int(11)            DEFAULT NULL COMMENT '创建时间',
    `update_time` int(11)            DEFAULT NULL COMMENT '更新时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_tasks_log`
--

CREATE TABLE `ha_system_tasks_log`
(
    `id`            int(11)      NOT NULL,
    `task_name`     varchar(255) NOT NULL,
    `task_id`       int(11)               DEFAULT '0',
    `status`        int(11)      NOT NULL DEFAULT '1',
    `output`        text,
    `error_message` text,
    `create_time`   int(11)               DEFAULT '0',
    `update_time`   int(11)               DEFAULT '0'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `ha_system_jobs`
--
ALTER TABLE `ha_system_jobs`
    ADD PRIMARY KEY (`id`),
    ADD KEY `queue` (`queue`);

--
-- 表的索引 `ha_system_jobs_failed`
--
ALTER TABLE `ha_system_jobs_failed`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_system_sitemap`
--
ALTER TABLE `ha_system_sitemap`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_site_sitemap_module` (`module`),
    ADD KEY `idx_site_sitemap_type` (`type`(191));

--
-- 表的索引 `ha_system_tasks`
--
ALTER TABLE `ha_system_tasks`
    ADD PRIMARY KEY (`id`),
    ADD KEY `ha_system_tasks_list_index` (`list`),
    ADD KEY `ha_system_tasks_next_time_index` (`next_time`),
    ADD KEY `ha_system_tasks_status_index` (`status`);

--
-- 表的索引 `ha_system_tasks_log`
--
ALTER TABLE `ha_system_tasks_log`
    ADD PRIMARY KEY (`id`),
    ADD KEY `ha_system_tasks_log_status_index` (`status`),
    ADD KEY `ha_system_tasks_log_task_id_index` (`task_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ha_system_jobs`
--
ALTER TABLE `ha_system_jobs`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_system_jobs_failed`
--
ALTER TABLE `ha_system_jobs_failed`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_system_sitemap`
--
ALTER TABLE `ha_system_sitemap`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_system_tasks`
--
ALTER TABLE `ha_system_tasks`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_system_tasks_log`
--
ALTER TABLE `ha_system_tasks_log`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

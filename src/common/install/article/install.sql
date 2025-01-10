-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-01-10 11:39:58
-- 服务器版本： 5.7.44-log
-- PHP 版本： 8.1.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `tp55_com`
--

-- --------------------------------------------------------

--
-- 表的结构 `ha_article_column`
--

CREATE TABLE `ha_article_column`
(
    `id`              int(11) NOT NULL,
    `pid`             int(11)       DEFAULT '0' COMMENT '父栏目',
    `mid`             int(11)       DEFAULT '0' COMMENT '所属模型',
    `title`           varchar(128)  DEFAULT '' COMMENT '名称',
    `image`           text COMMENT '栏目图片',
    `icon`            varchar(255)  DEFAULT '',
    `seo_title`       varchar(2000) DEFAULT '' COMMENT 'SEO标题',
    `seo_keywords`    varchar(200)  DEFAULT '' COMMENT '关键词',
    `seo_description` text COMMENT '描述',
    `list`            int(11)       DEFAULT '0' COMMENT '排序',
    `view_file`       varchar(128)  DEFAULT '' COMMENT '模板路径',
    `status`          int(11)       DEFAULT '1' COMMENT '状态',
    `create_time`     int(11)       DEFAULT '0' COMMENT '创建时间',
    `update_time`     int(11)       DEFAULT '0' COMMENT '更新时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='分类表';

-- --------------------------------------------------------

--
-- 表的结构 `ha_article_content`
--

CREATE TABLE `ha_article_content`
(
    `id`          bigint(20) UNSIGNED   NOT NULL COMMENT '主键ID',
    `mid`         smallint(5) UNSIGNED  NOT NULL DEFAULT '0' COMMENT '模型ID',
    `cid`         mediumint(8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `title`       varchar(256)          NOT NULL DEFAULT '' COMMENT '标题',
    `uid`         int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '用户ID',
    `view`        int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '浏览量',
    `status`      tinyint(4)            NOT NULL DEFAULT '1' COMMENT '状态：0未审 1已审 2推荐',
    `list`        int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '排序值',
    `create_time` int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '修改时间',
    `delete_time` int(11)                        DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='测试';


-- --------------------------------------------------------

--
-- 表的结构 `ha_article_content_1`
--

CREATE TABLE `ha_article_content_1`
(
    `id`          bigint(20) UNSIGNED   NOT NULL COMMENT '主键ID',
    `mid`         smallint(5) UNSIGNED  NOT NULL DEFAULT '0' COMMENT '模型ID',
    `cid`         mediumint(8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '栏目ID',
    `title`       varchar(256)          NOT NULL DEFAULT '' COMMENT '标题',
    `is_pic`      tinyint(4)            NOT NULL DEFAULT '0' COMMENT '是否带组图',
    `uid`         int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '用户ID',
    `view`        int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '浏览量',
    `status`      tinyint(4)            NOT NULL DEFAULT '1' COMMENT '状态：0未审 1已审 2推荐',
    `replynum`    int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '评论数',
    `description` text COMMENT '简介',
    `list`        int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '排序值',
    `images`      text                  NOT NULL COMMENT '组图',
    `keywords`    varchar(500)          NOT NULL DEFAULT '' COMMENT '关键词',
    `extend`      text COMMENT '扩展字段',
    `create_time` int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '修改时间',
    `delete_time` int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '软删除'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='测试';

-- --------------------------------------------------------

--
-- 表的结构 `ha_article_content_1s`
--

CREATE TABLE `ha_article_content_1s`
(
    `id`      bigint(20) UNSIGNED NOT NULL COMMENT '主键ID',
    `content` longtext COMMENT '内容'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='测试';

-- --------------------------------------------------------

--
-- 表的结构 `ha_article_field`
--

CREATE TABLE `ha_article_field`
(
    `id`          int(11) UNSIGNED NOT NULL,
    `mid`         int(11)      DEFAULT '0' COMMENT '所属模型',
    `name`        varchar(64)  DEFAULT '' COMMENT '字段名称',
    `type`        varchar(64)  DEFAULT '' COMMENT '字段类型',
    `title`       varchar(256) DEFAULT '' COMMENT '标签名称',
    `options`     text,
    `help`        text COMMENT '表单说明',
    `required`    int(11)      DEFAULT '0' COMMENT '是否必填',
    `list`        int(11)      DEFAULT '100' COMMENT '排序',
    `edit`        int(11)      DEFAULT '1' COMMENT '是否能修改 1可以修改 0不能修改',
    `extend`      longtext COMMENT '扩展参数',
    `status`      int(11)      DEFAULT '1' COMMENT '状态',
    `create_time` int(11)      DEFAULT '0' COMMENT '创建时间',
    `update_time` int(11)      DEFAULT '0' COMMENT '更新时间',
    `is_search`   int(11)      DEFAULT NULL COMMENT '是否搜索 1搜索 0不启动'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='字段设计';

--
-- 转存表中的数据 `ha_article_field`
--

INSERT INTO `ha_article_field` (`id`, `mid`, `name`, `type`, `title`, `options`, `help`, `required`, `list`, `edit`,
                                `extend`, `status`, `create_time`, `update_time`, `is_search`)
VALUES (1, 1, 'title', 'text', '标题', '', NULL, 1, 1000, 1,
        '{\"field\":{\"type\":\"VARCHAR\",\"length\":\"256\",\"unsigned\":\"0\",\"null\":\"0\",\"default\":\"\'\'\"},\"search\":{\"is_open\":\"1\",\"linq\":\"like\"},\"tables\":{\"is_show\":\"1\",\"templet\":\"\",\"switch\":{\"name\":\"\"},\"edit\":\"0\"},\"add\":{\"is_show\":\"1\"}}',
        1, 1735613014, 1735613014, NULL),
       (2, 1, 'keywords', 'text', '关键词', '', NULL, 0, 100, 1,
        '{\"field\":{\"type\":\"VARCHAR\",\"length\":\"2000\",\"unsigned\":\"0\",\"null\":\"0\",\"default\":\"\'\'\"},\"search\":{\"is_open\":\"0\",\"linq\":\"\"},\"tables\":{\"is_show\":\"0\",\"templet\":\"\",\"switch\":{\"name\":\"\"},\"edit\":\"0\"},\"add\":{\"is_show\":\"0\"}}',
        1, 1735613014, 1735613014, NULL),
       (3, 1, 'description', 'textarea', '简介', '', NULL, 0, 100, 1,
        '{\"field\":{\"type\":\"TEXT\",\"length\":\"\",\"unsigned\":\"0\",\"null\":\"0\",\"default\":\"\"},\"search\":{\"is_open\":\"0\",\"linq\":\"\"},\"tables\":{\"is_show\":\"0\",\"templet\":\"\",\"switch\":{\"name\":\"\"},\"edit\":\"0\"},\"add\":{\"is_show\":\"1\"}}',
        1, 1735613014, 1735613014, NULL),
       (4, 1, 'content', 'editor', '内容', '', NULL, 1, 1, 1,
        '{\"field\":{\"type\":\"LONGTEXT\",\"length\":\"\",\"unsigned\":\"0\",\"null\":\"0\",\"default\":\"\"},\"search\":{\"is_open\":\"0\",\"linq\":\"\"},\"tables\":{\"is_show\":\"0\",\"templet\":\"\",\"switch\":{\"name\":\"\"},\"edit\":\"0\"},\"add\":{\"is_show\":\"1\"}}',
        1, 1735613014, 1735613014, NULL),
       (5, 1, 'images', 'images', '组图', '', NULL, 0, 80, 1,
        '{\"field\":{\"type\":\"TEXT\",\"length\":\"\",\"unsigned\":\"0\",\"null\":\"0\",\"default\":\"\"},\"search\":{\"is_open\":\"0\",\"linq\":\"\"},\"tables\":{\"is_show\":\"1\",\"templet\":\"image\",\"switch\":{\"name\":\"\"},\"edit\":\"0\"},\"add\":{\"is_show\":\"1\"}}',
        1, 1735613014, 1735662989, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `ha_article_model`
--

CREATE TABLE `ha_article_model`
(
    `id`     int(11) NOT NULL,
    `title`  varchar(64)  DEFAULT '' COMMENT '名称',
    `info`   varchar(512) DEFAULT '' COMMENT '描述',
    `list`   int(11)      DEFAULT '100' COMMENT '排序',
    `is_del` int(11)      DEFAULT '1' COMMENT '可否删除'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- 转存表中的数据 `ha_article_model`
--

INSERT INTO `ha_article_model` (`id`, `title`, `info`, `list`, `is_del`)
VALUES (-1, '栏目', '栏目模型，内置字段不可修改，仅支持扩展字段', -100, 0),
       (1, '文章模型', '', 100, 1);

-- --------------------------------------------------------

--
-- 表的结构 `ha_article_tag`
--

CREATE TABLE `ha_article_tag`
(
    `id`              int(10) UNSIGNED      NOT NULL,
    `title`           varchar(256)          NOT NULL DEFAULT '' COMMENT '标题',
    `view`            mediumint(8) UNSIGNED NOT NULL DEFAULT '0' COMMENT '浏览量',
    `count`           int(11)               NOT NULL DEFAULT '0' COMMENT '内容总数',
    `status`          tinyint(4)            NOT NULL DEFAULT '1' COMMENT '状态：0未审 1已审 2推荐',
    `list`            int(10) UNSIGNED      NOT NULL DEFAULT '0' COMMENT '排序值',
    `uid`             int(11)               NOT NULL DEFAULT '1',
    `image`           text COMMENT '封面图',
    `seo_description` mediumtext COMMENT '文章内容',
    `seo_title`       varchar(128)                   DEFAULT NULL COMMENT 'SEO标题',
    `seo_keyword`     varchar(128)                   DEFAULT NULL COMMENT '关键词',
    `create_time`     int(10) UNSIGNED               DEFAULT NULL,
    `update_time`     int(10) UNSIGNED               DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='tag';


-- --------------------------------------------------------

--
-- 表的结构 `ha_article_tag_info`
--

CREATE TABLE `ha_article_tag_info`
(
    `id`  int(11) NOT NULL,
    `aid` bigint(22)       DEFAULT NULL COMMENT '内容id',
    `tid` int(11)          DEFAULT NULL COMMENT 'tag id',
    `mid` int(11) NOT NULL DEFAULT '0' COMMENT '模型id'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='tag关联信息';

--
-- 转储表的索引
--

--
-- 表的索引 `ha_article_column`
--
ALTER TABLE `ha_article_column`
    ADD PRIMARY KEY (`id`),
    ADD KEY `article_column_pid_index` (`pid`),
    ADD KEY `article_column_title_index` (`title`);

--
-- 表的索引 `ha_article_content`
--
ALTER TABLE `ha_article_content`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_article_content_1`
--
ALTER TABLE `ha_article_content_1`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_article_content_1s`
--
ALTER TABLE `ha_article_content_1s`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_article_field`
--
ALTER TABLE `ha_article_field`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_article_model`
--
ALTER TABLE `ha_article_model`
    ADD PRIMARY KEY (`id`),
    ADD KEY `article_model_title_index` (`title`);

--
-- 表的索引 `ha_article_tag`
--
ALTER TABLE `ha_article_tag`
    ADD PRIMARY KEY (`id`),
    ADD KEY `count` (`count`),
    ADD KEY `list` (`list`),
    ADD KEY `status` (`status`),
    ADD KEY `view` (`view`),
    ADD KEY `create_time` (`create_time`),
    ADD KEY `update_time` (`update_time`);

--
-- 表的索引 `ha_article_tag_info`
--
ALTER TABLE `ha_article_tag_info`
    ADD PRIMARY KEY (`id`),
    ADD KEY `astro_tag_info_aid_index` (`aid`),
    ADD KEY `astro_tag_info_tid_index` (`tid`),
    ADD KEY `mid` (`mid`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ha_article_column`
--
ALTER TABLE `ha_article_column`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 5;

--
-- 使用表AUTO_INCREMENT `ha_article_content`
--
ALTER TABLE `ha_article_content`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    AUTO_INCREMENT = 12;

--
-- 使用表AUTO_INCREMENT `ha_article_content_1`
--
ALTER TABLE `ha_article_content_1`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    AUTO_INCREMENT = 12;

--
-- 使用表AUTO_INCREMENT `ha_article_content_1s`
--
ALTER TABLE `ha_article_content_1s`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    AUTO_INCREMENT = 12;

--
-- 使用表AUTO_INCREMENT `ha_article_field`
--
ALTER TABLE `ha_article_field`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 6;

--
-- 使用表AUTO_INCREMENT `ha_article_model`
--
ALTER TABLE `ha_article_model`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 2;

--
-- 使用表AUTO_INCREMENT `ha_article_tag`
--
ALTER TABLE `ha_article_tag`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 45;

--
-- 使用表AUTO_INCREMENT `ha_article_tag_info`
--
ALTER TABLE `ha_article_tag_info`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;

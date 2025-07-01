-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-07-01 09:25:46
-- 服务器版本： 5.7.44-log
-- PHP 版本： 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `xz_com`
--

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_spider_date`
--

CREATE TABLE `ha_system_spider_date`
(
    `id`           bigint(20) UNSIGNED NOT NULL,
    `date`         date                NOT NULL COMMENT '统计日期',
    `name`         varchar(255)        NOT NULL COMMENT '蜘蛛名称',
    `total_visits` int(10) UNSIGNED    NOT NULL DEFAULT '0' COMMENT '访问次数',
    `unique_urls`  int(11)                      DEFAULT '0' COMMENT '访问的不同 URL 数量'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_spider_logs`
--

CREATE TABLE `ha_system_spider_logs`
(
    `id`          bigint(20) UNSIGNED NOT NULL,
    `name`        varchar(128)        NOT NULL COMMENT '蜘蛛名称，如 Googlebot',
    `url`         varchar(512)        NOT NULL COMMENT '访问的 URL',
    `ip`          varchar(45)         NOT NULL COMMENT 'IP 地址',
    `user_agent`  text                NOT NULL COMMENT 'User-Agent 字符串',
    `create_time` int(11) DEFAULT '0'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `ha_system_spider_date`
--
ALTER TABLE `ha_system_spider_date`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `date_spider` (`date`, `name`);

--
-- 表的索引 `ha_system_spider_logs`
--
ALTER TABLE `ha_system_spider_logs`
    ADD PRIMARY KEY (`id`),
    ADD KEY `ha_system_spider_logs_name_index` (`name`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ha_system_spider_date`
--
ALTER TABLE `ha_system_spider_date`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_system_spider_logs`
--
ALTER TABLE `ha_system_spider_logs`
    MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;

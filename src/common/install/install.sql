-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-01-10 00:26:47
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
-- 数据库： `md_cn`
--

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_attachment`
--

CREATE TABLE `ha_system_attachment`
(
    `id`          int(11) UNSIGNED                        NOT NULL,
    `uid`         mediumint(8) UNSIGNED                   NOT NULL DEFAULT '0' COMMENT '用户id',
    `name`        varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件名',
    `path`        varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
    `url`         varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件链接',
    `imagewidth`  int(10) UNSIGNED                                 DEFAULT '0' COMMENT '宽度',
    `imageheight` int(10) UNSIGNED                                 DEFAULT '0' COMMENT '高度',
    `mime`        varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件mime类型',
    `ext`         varchar(4) COLLATE utf8mb4_unicode_ci   NOT NULL DEFAULT '' COMMENT '文件类型',
    `size`        int(11) UNSIGNED                        NOT NULL DEFAULT '0' COMMENT '文件大小',
    `md5`         varchar(32) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT '文件md5',
    `sha1`        varchar(40) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT 'sha1 散列值',
    `driver`      varchar(16) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT 'local' COMMENT '上传驱动',
    `create_time` int(10) UNSIGNED                                 DEFAULT NULL COMMENT '上传时间',
    `update_time` int(10) UNSIGNED                                 DEFAULT NULL COMMENT '更新时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='附件表';

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_config`
--

CREATE TABLE `ha_system_config`
(
    `id`          int(11)                                NOT NULL,
    `name`        varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名称',
    `type`        varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置类型',
    `title`       varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置说明',
    `group_id`    int(11)                                NOT NULL DEFAULT '0' COMMENT '配置分组',
    `options`     text COLLATE utf8mb4_unicode_ci COMMENT '配置项',
    `help`        text COLLATE utf8mb4_unicode_ci COMMENT '配置说明',
    `value`       longtext COLLATE utf8mb4_unicode_ci COMMENT '配置值',
    `extend`      text COLLATE utf8mb4_unicode_ci COMMENT '扩展属性',
    `list`        int(11)                                NOT NULL DEFAULT '0' COMMENT '排序',
    `module`      varchar(64) COLLATE utf8mb4_unicode_ci          DEFAULT NULL COMMENT '归属模块',
    `status`      tinyint(2)                             NOT NULL DEFAULT '1' COMMENT '状态',
    `create_time` int(11)                                         DEFAULT '0' COMMENT '创建时间',
    `update_time` int(11)                                         DEFAULT '0' COMMENT '更新时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='网站配置';

--
-- 转存表中的数据 `ha_system_config`
--

INSERT INTO `ha_system_config` (`id`, `name`, `type`, `title`, `group_id`, `options`, `help`, `value`, `extend`, `list`,
                                `module`, `status`, `create_time`, `update_time`)
VALUES (1, 'storage_driver', 'radio', '存储引擎', 4,
        'local|本地\noss|阿里云oss|oss_accesskeyid,oss_accesskeysecret,oss_bucket,oss_domain,oss_endpoint\nqiniu|七牛云|qiniu_domain,qiniu_secret,qiniu_access,qiniu_bucket',
        '', 'local', NULL, 0, 'system', 1, 1733713725, 1736439933),
       (2, 'site_name', 'text', '网站名称', 1, '', '', '星座之家', NULL, 0, 'system', 1, 1733733724, 1733800178),
       (3, 'seo_title', 'text', '网站标题', 1, '', '', '网站标题', NULL, 0, 'system', 1, 1733733742, 1733733752),
       (4, 'seo_keywords', 'text', '网站关键词', 1, '', '', '网站关键词', NULL, 0, 'system', 1, 1733733850, 1733733901),
       (5, 'seo_description', 'textarea', '网站描述', 1, '', '', '网站描述', NULL, 0, 'system', 1, 1733733889,
        1733733901),
       (6, 'file_type', 'text', '上传文件类型', 4, '', '多个,号分割', 'gif,ico,jpg,png,jpeg,svg,pdf', NULL, 0, 'system',
        1, 1733733995, 1734016919),
       (7, 'oss_endpoint', 'text', 'endpoint', 4, '', '', '', NULL, 0, 'system', 1, 1733750382, 1734448128),
       (8, 'oss_domain', 'text', '域名', 4, '', '', '', NULL, 0, 'system', 1, 1733750422, 1734448128),
       (9, 'oss_bucket', 'text', 'bucket', 4, '', '', '', NULL, 0, 'system', 1, 1733750444, 1734448128),
       (10, 'oss_accesskeysecret', 'text', 'keysecret', 4, '', '', '', NULL, 0, 'system', 1, 1733750477, 1734448128),
       (11, 'oss_accesskeyid', 'text', 'keyid', 4, '', '', '', NULL, 0, 'system', 1, 1733750500, 1736439944),
       (12, 'file_size', 'text', '文件大小限制', 4, '',
        '最大上传大小，单位b，默认102400， 10Mb按需设置 一般最大不建议超过50Mb', '50', NULL, 0, 'system', 1, 1733801716,
        1733991578),
       (13, 'site_logo', 'image', '网站LOGO', 1, '', '', '', NULL, 0, 'system', 1, 1733801969, 1734621924),
       (14, 'qiniu_bucket', 'text', 'bucket', 4, '', '', '', NULL, 0, 'system', 1, 1734454078, 1734454703),
       (15, 'qiniu_access', 'text', 'accessKey ', 4, '', '', '', NULL, 0, 'system', 1, 1734454126, 1734454703),
       (16, 'qiniu_secret', 'text', 'secretKey ', 4, '', '', '', NULL, 0, 'system', 1, 1734454157, 1734454703),
       (17, 'qiniu_domain', 'text', '访问域名', 4, '', '', '', NULL, 0, 'system', 1, 1734454447, 1734454703),
       (18, 'copyright', 'textarea', '版权信息', 1, '', '', 'hpAdmin', NULL, 0, 'system', 1, 1734602379, 1736139452),
       (19, 'mail_host', 'text', '邮件服务器', 3, '', '', 'smtp.qq.com', NULL, 100, 'system', 1, 1735801122,
        1735802269),
       (20, 'mail_username', 'text', '邮箱账号', 3, '', '', '', NULL, 91, 'system', 1, 1735801302, 1735802304),
       (21, 'mail_password', 'text', '邮箱密码', 3, '', '', '', NULL, 90, 'system', 1, 1735801367, 1735802304),
       (22, 'mail_port', 'text', '端口', 3, '', '', '465', NULL, 99, 'system', 1, 1735801925, 1735802269),
       (23, 'mail_test', 'radio', '发送测试邮件', 3, '0|不发送\n1|发送', '', '1', NULL, 10, 'system', 1, 1735802080,
        1735804998),
       (24, 'mail_send_user', 'text', '测试收件邮箱', 3, '', '', '', NULL, 5, 'system', 1, 1735802132, 1735804998),
       (25, 'is_open_site', 'radio', '网站是否开启', 1, '1|开启\n0|关闭', '是否开启站点', '1', NULL, 0, 'system', 1,
        1735962684, 1735974616),
       (26, 'icp_number', 'text', 'ICP备案号', 1, '', '', 'XICP备XXXXX号', NULL, 0, 'system', 1, 1736139836,
        1736139873),
       (27, 'wa_number', 'text', '网安备案号', 1, '', '', '', NULL, 0, 'system', 1, 1736213129, 1736213129),
       (28, 'sms_access_key_id ', 'text', '阿里AccessKeyId', 5, '', '', '', NULL, 0, 'system', 1, 1736217060,
        1736217060),
       (29, 'sms_access_key_secret', 'text', '阿里AccessKeySecret', 5, '', '', '', NULL, 0, 'system', 1, 1736217106,
        1736217106),
       (30, 'sms_sign_name', 'text', '签名', 5, '', '', '', NULL, 0, 'system', 1, 1736217146, 1736217153),
       (31, 'sms_code_template', 'text', '验证码模板', 5, '',
        '模板中使用的变量只能是code,如果不是的话,请先修改或者重新申请一个把变量名换用code', '', NULL, 0, 'system', 1,
        1736217219, 1736217219),
       (32, 'is_reg', 'radio', '开启注册', 2, '1|开启\n0|关闭', '', '0', NULL, 0, 'system', 1, 1736217376, 1736217951),
       (33, 'is_reg_image_code', 'select', '注册图形验证码', 2, '0|关闭\n1|开启\n2|自动（3次失败后开启）', '', '0', NULL,
        0, 'system', 1, 1736217499, 1736217951),
       (34, 'is_reg_sms_code', 'radio', '注册短信验证码', 2, '0|关闭\n1|开启', '', '0', NULL, 0, 'system', 1,
        1736217584, 1736217951),
       (35, 'is_login_image_code', 'select', '登录图形验证码', 2, '0|关闭\n1|开启\n2|自动（三次失败自动开启）', '', '0',
        NULL, 0, 'system', 1, 1736217669, 1736217951),
       (36, 'forbid_username', 'textarea', '禁止注册账号', 2, '', '每行一个', 'admin', NULL, 0, 'system', 1, 1736217885,
        1736217966),
       (37, 'square_logo', 'image', '方形LOGO', 1, '', '后台，二维码等场景使用', '', NULL, 0, 'system', 1, 1736227701,
        1736439835),
       (38, 'home_pc_style', 'select', '网站PC风格', 1, '\\mowzs\\lib\\helper\\TemplateHelper@getTemplateData@home', '',
        'default', NULL, 0, 'system', 1, 1736259709, 1736262456),
       (39, 'home_wap_style', 'select', '网站wap风格', 1, '\\mowzs\\lib\\helper\\TemplateHelper@getTemplateData@home',
        '', 'default', NULL, 0, 'system', 1, 1736259735, 1736261908),
       (40, 'admin_style', 'select', '后台风格', 1, '\\mowzs\\lib\\helper\\TemplateHelper@getTemplateData@admin', '',
        'default', NULL, 0, 'system', 1, 1736259762, 1736261908),
       (41, 'user_pc_style', 'select', '会员PC风格', 1, '\\mowzs\\lib\\helper\\TemplateHelper@getTemplateData@user', '',
        'default', NULL, 0, 'system', 1, 1736259818, 1736264408),
       (42, 'user_wap_style', 'select', '会员wap风格', 1, '\\mowzs\\lib\\helper\\TemplateHelper@getTemplateData@user',
        '', 'default', NULL, 0, 'system', 1, 1736259844, 1736264408);

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_config_group`
--

CREATE TABLE `ha_system_config_group`
(
    `id`       int(11) NOT NULL,
    `title`    varchar(256) DEFAULT NULL COMMENT '名称',
    `sys_show` int(11)      DEFAULT '0' COMMENT '系统设置显示',
    `module`   varchar(255) DEFAULT '' COMMENT '归属模块 system为系统模块',
    `status`   int(11)      DEFAULT '1' COMMENT '状态'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='设置分组';

--
-- 转存表中的数据 `ha_system_config_group`
--

INSERT INTO `ha_system_config_group` (`id`, `title`, `sys_show`, `module`, `status`)
VALUES (1, '基础设置', 1, 'system', 1),
       (2, '会员设置', 1, 'system', 1),
       (3, '邮件设置', 1, 'system', 1),
       (4, '存储设置', 1, 'system', 1),
       (5, '短信设置', 1, 'system', 1);

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_event`
--

CREATE TABLE `ha_system_event`
(
    `id`          int(11) UNSIGNED NOT NULL,
    `name`        varchar(80)      NOT NULL DEFAULT '' COMMENT '钩子名称',
    `info`        text COMMENT '钩子描述',
    `status`      int(1)           NOT NULL DEFAULT '1' COMMENT '是否启用',
    `list`        int(11)          NOT NULL DEFAULT '0' COMMENT '排序值',
    `params_info` text
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='接口(钩子)列表';

--
-- 转存表中的数据 `ha_system_event`
--

INSERT INTO `ha_system_event` (`id`, `name`, `info`, `status`, `list`, `params_info`)
VALUES (1, 'AppInit', '应用初始化标签位', 1, 0, NULL),
       (2, 'HttpRun', '应用开始标签位', 1, 0, NULL),
       (3, 'HttpEnd', '应用结束标签位', 1, 0, NULL),
       (4, 'LogWrite', '日志write方法标签位', 1, 0, NULL),
       (5, 'RouteLoaded', '路由加载完成', 1, 0, NULL),
       (6, 'LogRecord', '日志记录', 1, 0, NULL),
       (7, 'HomeIndex', '网站首页', 1, 0, '无');

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_event_listen`
--

CREATE TABLE `ha_system_event_listen`
(
    `id`          int(11) UNSIGNED NOT NULL,
    `event_key`   varchar(50)      NOT NULL COMMENT '所归属的接口关键字',
    `plugin_key`  varchar(50)               DEFAULT NULL COMMENT '所归属的插件关键字,也即目录名',
    `event_class` varchar(80)      NOT NULL COMMENT '钩子运行的类名',
    `info`        varchar(255)     NOT NULL COMMENT '此钩子插件能实现的功能描述',
    `status`      int(1)           NOT NULL DEFAULT '1' COMMENT '是否启用',
    `list`        int(11)          NOT NULL DEFAULT '0' COMMENT '执行的先后顺序',
    `author`      varchar(80)               DEFAULT NULL COMMENT '开发者',
    `author_url`  varchar(120)              DEFAULT NULL COMMENT '开发者网站',
    `version`     varchar(60)               DEFAULT NULL COMMENT '版本信息'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='事件监听';

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_feedback`
--

CREATE TABLE `ha_system_feedback`
(
    `id`           int(11)      NOT NULL COMMENT '主键ID',
    `uid`          int(11)               DEFAULT NULL COMMENT '用户ID (可选，匿名用户可以为空)',
    `contact_info` varchar(255)          DEFAULT NULL COMMENT '联系方式 (如邮箱、电话等)',
    `category`     int(11)      NOT NULL DEFAULT '0' COMMENT '类别',
    `title`        varchar(255) NOT NULL COMMENT '标题',
    `page_url`     varchar(500) NOT NULL COMMENT '举报页面',
    `content`      text         NOT NULL COMMENT '内容',
    `status`       tinyint(1)   NOT NULL DEFAULT '0' COMMENT '状态 (0: 待处理, 1: 已处理)',
    `create_time`  int(11)               DEFAULT '0' COMMENT '创建时间',
    `update_time`  int(11)               DEFAULT NULL COMMENT '更新时间',
    `handled_by`   int(11)               DEFAULT NULL COMMENT '处理人ID (可选)',
    `handled_time` int(11)               DEFAULT NULL COMMENT '处理时间',
    `response`     text COMMENT '回复内容 (管理员对用户的回复)'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='举报/反馈表';

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_icon`
--

CREATE TABLE `ha_system_icon`
(
    `id`          int(11)      NOT NULL,
    `path`        varchar(255) NOT NULL,
    `url`         varchar(255) NOT NULL,
    `title`       varchar(64)           DEFAULT NULL COMMENT '名称',
    `name`        varchar(100) NOT NULL COMMENT '字体名称',
    `prefix`      varchar(50)  NOT NULL,
    `create_time` int(11)      NOT NULL DEFAULT '0',
    `update_time` int(11)      NOT NULL DEFAULT '0',
    `list`        int(11)               DEFAULT '0' COMMENT '排序',
    `status`      int(11)               DEFAULT '1' COMMENT '状态'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

--
-- 转存表中的数据 `ha_system_icon`
--

INSERT INTO `ha_system_icon` (`id`, `path`, `url`, `title`, `name`, `prefix`, `create_time`, `update_time`, `list`,
                              `status`)
VALUES (1, 'public/static/libs/layui/css/layui.css', '/static/libs/layui/css/layui.css', 'layui', 'layui-icon',
        'layui-icon-', 1732974203, 1734425923, 15, 1),
       (3, 'public/static/admin/css/icon.css', '/static/admin/css/icon.css', '阿里自选', 'iconfont', 'icon-',
        1734400900, 1735806355, 17, 1);

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_links`
--

CREATE TABLE `ha_system_links`
(
    `id`          int(11) UNSIGNED NOT NULL,
    `cid`         int(11)          DEFAULT '0' COMMENT '分类id',
    `type`        int(11)          DEFAULT '0' COMMENT '友链类型',
    `title`       varchar(255)     DEFAULT NULL COMMENT '网站标题',
    `url`         varchar(500)     DEFAULT NULL COMMENT '网站链接',
    `list`        int(11)          DEFAULT '0' COMMENT '排序',
    `qq`          varchar(255)     DEFAULT NULL COMMENT '联系方式',
    `status`      int(11)          DEFAULT '1' COMMENT '状态(1正常 0隐藏)',
    `start_time`  datetime         DEFAULT NULL COMMENT '开始日期',
    `end_time`    datetime         DEFAULT NULL COMMENT '结束日期',
    `create_time` int(10) UNSIGNED DEFAULT NULL,
    `update_time` int(10) UNSIGNED DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='友情链接';

--
-- 转存表中的数据 `ha_system_links`
--

INSERT INTO `ha_system_links` (`id`, `cid`, `type`, `title`, `url`, `list`, `qq`, `status`, `start_time`, `end_time`,
                               `create_time`, `update_time`)
VALUES (1, 1, 1, '星座之家', 'https://www.xingzuohome.com/', 0, '123789', 1, '2025-01-06 11:45:42',
        '2025-01-06 11:45:42', 1736135142, 1736135142);

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_menu`
--

CREATE TABLE `ha_system_menu`
(
    `id`     int(11) NOT NULL,
    `pid`    int(11)      DEFAULT '0' COMMENT '父级栏目ID',
    `title`  varchar(64)  DEFAULT NULL COMMENT '菜单名称',
    `icon`   varchar(64)  DEFAULT NULL,
    `node`   varchar(128) DEFAULT NULL COMMENT '节点',
    `params` varchar(512) DEFAULT NULL COMMENT '参数',
    `class`  int(11)      DEFAULT '1' COMMENT '类型 1节点 2链接',
    `list`   int(11)      DEFAULT '0' COMMENT '排序',
    `status` int(11)      DEFAULT '1' COMMENT '状态'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='系统菜单';

--
-- 转存表中的数据 `ha_system_menu`
--

INSERT INTO `ha_system_menu` (`id`, `pid`, `title`, `icon`, `node`, `params`, `class`, `list`, `status`)
VALUES (1, 0, '系统管理', 'layui-icon layui-icon-set', '#', '', 1, 9000, 1),
       (2, 1, '设置管理', 'layui-icon layui-icon-set', '#', '', 1, 1000, 1),
       (3, 2, '系统设置', '', 'system/setting/index', '', 1, 800, 1),
       (4, 24, '后台菜单', '', 'system/menu/index', '', 1, 700, 1),
       (5, 0, '用户管理', 'layui-icon layui-icon-username', '#', '', 1, 1000, 1),
       (6, 5, '用户信息', 'layui-icon layui-icon-username', '#', '', 1, 900, 1),
       (7, 6, '用户资料', '', 'user/info/index', '', 1, 800, 1),
       (8, 24, '图标管理', '', 'system/icon/index', '', 1, 900, 1),
       (9, 2, '设置分组', '', 'system/configGroup/index', '', 1, 100, 1),
       (10, 0, '内容模块', 'layui-icon layui-icon-list', '#', '', 1, 11, 1),
       (12, 0, '扩展功能', 'layui-icon layui-icon-slider', '#', '', 1, 11, 1),
       (13, 6, '用户权限', 'layui-icon layui-icon-vercode', 'user/auth/index', '', 1, 0, 1),
       (14, 24, '文件管理', 'layui-icon layui-icon-folder', 'system/attachment/index', '', 1, 0, 1),
       (15, 1, '事件管理', 'layui-icon layui-icon-rate-half', '#', '', 1, 800, 1),
       (16, 15, '事件设置', '', 'system/event/index', '', 1, 0, 1),
       (17, 15, '事件监听', '', 'system/eventListen/index', '', 1, 0, 1),
       (24, 1, '系统功能', 'layui-icon layui-icon-slider', '#', '', 1, 900, 1),
       (25, 2, '设置字段', '', 'system/config/index', '', 1, 0, 1),
       (26, 24, '系统模块', '', 'system/module/index', '', 1, 0, 1),
       (27, 12, '系统扩展', 'layui-icon layui-icon-cols', '#', '', 1, 0, 1),
       (28, 27, '友情链接', '', 'system/links/index', '', 1, 0, 1),
       (29, 27, '投诉反馈', '', 'system/feedback/index', '', 1, 0, 1),
       (30, 5, '用户功能', 'layui-icon layui-icon-survey', '#', '', 1, 0, 1),
       (31, 30, '用户收藏', '', 'user/fav/index', '', 1, 0, 1),
       (32, 6, '用户组', '', 'user/group/index', '', 1, 0, 1),
       (33, 6, '积分记录', '', 'user/PointsLog/index', '', 1, 0, 1),
       (34, 1, '系统日志', 'layui-icon layui-icon-tips', '#', '', 1, 0, 1),
       (35, 34, '操作日志', '', 'system/OperationLog/index', '', 1, 0, 1),
       (36, 1, '网站管理', 'layui-icon layui-icon-website', '', '#', 1, 700, 1),
       (37, 36, '网站菜单', '', 'system/nav/index', '', 1, 0, 1);

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_module`
--

CREATE TABLE `ha_system_module`
(
    `id`          int(11) NOT NULL,
    `title`       varchar(64) DEFAULT '' COMMENT '模块名称',
    `dir`         varchar(64) DEFAULT '' COMMENT '模块标记/目录',
    `type`        int(11)     DEFAULT '1' COMMENT '模块类型 1为系统模块 2插件',
    `status`      int(11)     DEFAULT NULL COMMENT '状态 1正常 0禁用',
    `create_time` int(11)     DEFAULT '0' COMMENT '创建时间',
    `update_time` int(11)     DEFAULT '0' COMMENT '修改时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='系统模块';

--
-- 转存表中的数据 `ha_system_module`
--

INSERT INTO `ha_system_module` (`id`, `title`, `dir`, `type`, `status`, `create_time`, `update_time`)
VALUES (1, '系统核心', 'system', 1, 1, 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_nav`
--

CREATE TABLE `ha_system_nav`
(
    `id`     int(11) NOT NULL,
    `pid`    int(11)      DEFAULT '0' COMMENT '父级栏目ID',
    `title`  varchar(64)  DEFAULT NULL COMMENT '菜单名称',
    `icon`   varchar(64)  DEFAULT NULL,
    `dir`    varchar(64)  DEFAULT '' COMMENT '链接分类标识',
    `url`    varchar(256) DEFAULT NULL COMMENT '链接地址',
    `target` varchar(16)  DEFAULT '_self' COMMENT '打开方式 _self, _blank 等',
    `node`   varchar(128) DEFAULT NULL COMMENT '节点',
    `params` varchar(512) DEFAULT NULL COMMENT '参数',
    `type`   int(11)      DEFAULT '1' COMMENT '类型 1节点 2链接',
    `class`  varchar(512) DEFAULT NULL COMMENT '自定义样式类',
    `list`   int(11)      DEFAULT '0' COMMENT '排序',
    `status` int(11)      DEFAULT '1' COMMENT '状态'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='网站菜单导航';

--
-- 转存表中的数据 `ha_system_nav`
--

INSERT INTO `ha_system_nav` (`id`, `pid`, `title`, `icon`, `dir`, `url`, `target`, `node`, `params`, `type`, `class`,
                             `list`, `status`)
VALUES (1, 0, '首页', 'layui-icon layui-icon-home', 'pc', '/', '_self', '', '', 2, '', 0, 1),
       (2, 0, '测试栏目', '', 'pc', '/', '_blank', '', '', 2, '', 0, 1),
       (3, 2, '测试子栏目', '', 'pc', '/', '_blank', '', '', 2, '', 0, 1),
       (4, 0, '个人信息', 'layui-icon layui-icon-username', 'pc_user', '#', '', '', '', 2, '', 10000, 1),
       (5, 4, '我的信息', '', 'pc_user', '', '', 'user/index/main', '', 1, '', 1000, 1),
       (6, 4, '修改资料', '', 'pc_user', '', '', 'user/index/profile', '', 1, '', 0, 1),
       (7, 4, '修改密码', '', 'pc_user', '', '', 'user/index/pass', '', 1, '', 0, 1),
       (8, 4, '积分记录', '', 'pc_user', '', '', 'user/points_log/index', '', 1, '', 0, 1),
       (9, 4, '登录记录', '', 'pc_user', '', '', 'user/login_log/index', '', 1, '', 0, 1);

-- --------------------------------------------------------

--
-- 表的结构 `ha_system_operation_log`
--

CREATE TABLE `ha_system_operation_log`
(
    `id`          int(10) UNSIGNED NOT NULL COMMENT '主键ID',
    `uid`         int(10) UNSIGNED NOT NULL COMMENT '管理员ID',
    `node`        varchar(255)     NOT NULL COMMENT '操作节点',
    `desc`        text COMMENT '操作描述',
    `ip`          varchar(45)      NOT NULL COMMENT '操作时的IP地址',
    `user_agent`  text COMMENT '操作时的User-Agent信息',
    `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '操作时间戳'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='系统操作日志表';

-- --------------------------------------------------------

--
-- 表的结构 `ha_user_auth`
--

CREATE TABLE `ha_user_auth`
(
    `id`           int(11) NOT NULL COMMENT 'ID',
    `title`        varchar(64) DEFAULT NULL COMMENT '权限组',
    `desc`         text COMMENT '说明',
    `nodes`        longtext COMMENT '权限节点',
    `is_authorize` int(11)     DEFAULT '0' COMMENT '后台权限',
    `status`       int(11)     DEFAULT '1' COMMENT '状态'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='权限组';

--
-- 转存表中的数据 `ha_user_auth`
--

INSERT INTO `ha_user_auth` (`id`, `title`, `desc`, `nodes`, `is_authorize`, `status`)
VALUES (1, '超级管理员', '超级管理员，拥有超多权限', '', 1, 1);

-- --------------------------------------------------------

--
-- 表的结构 `ha_user_fav`
--

CREATE TABLE `ha_user_fav`
(
    `id`            int(11) UNSIGNED    NOT NULL COMMENT '主键ID',
    `uid`           int(11) UNSIGNED    NOT NULL COMMENT '用户ID',
    `module`        varchar(50)         NOT NULL COMMENT '模块名称 (如文章、商品等)',
    `mid`           int(11) UNSIGNED    NOT NULL COMMENT '模型ID (对应模块中的内容ID)',
    `content_id`    bigint(20) UNSIGNED NOT NULL COMMENT '内容ID (具体收藏的内容ID)',
    `content_title` varchar(255)        NOT NULL COMMENT '内容标题',
    `content_url`   varchar(500)                 DEFAULT NULL COMMENT '内容链接 (可选)',
    `create_time`   int(11) UNSIGNED             DEFAULT NULL COMMENT '收藏时间',
    `update_time`   int(11) UNSIGNED             DEFAULT NULL COMMENT '更新时间',
    `status`        tinyint(1)          NOT NULL DEFAULT '1' COMMENT '状态 (1: 正常, 0: 已删除)'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='收藏记录表';

-- --------------------------------------------------------

--
-- 表的结构 `ha_user_group`
--

CREATE TABLE `ha_user_group`
(
    `id`             int(10) UNSIGNED    NOT NULL COMMENT '主键ID',
    `name`           varchar(64)         NOT NULL COMMENT '用户组名称',
    `desc`           text COMMENT '用户组描述',
    `status`         tinyint(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '状态 (1: 正常, 0: 禁用)',
    `create_time`    int(10) UNSIGNED    NOT NULL DEFAULT '0' COMMENT '创建时间戳',
    `update_time`    int(10) UNSIGNED    NOT NULL DEFAULT '0' COMMENT '更新时间戳',
    `upgrade_points` int(10) UNSIGNED    NOT NULL DEFAULT '0' COMMENT '升级所需积分',
    `upgrade_day`    int(10) UNSIGNED    NOT NULL DEFAULT '0' COMMENT '升级有效时长 (天)'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户组表';

--
-- 转存表中的数据 `ha_user_group`
--

INSERT INTO `ha_user_group` (`id`, `name`, `desc`, `status`, `create_time`, `update_time`, `upgrade_points`,
                             `upgrade_day`)
VALUES (1, '初级用户', '初级刚注册的默认用户组', 1, 1735873039, 1735873075, 0, 0);

-- --------------------------------------------------------

--
-- 表的结构 `ha_user_group_upgrade_log`
--

CREATE TABLE `ha_user_group_upgrade_log`
(
    `id`            int(10) UNSIGNED NOT NULL COMMENT '主键ID',
    `uid`           int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `from_group_id` int(10) UNSIGNED NOT NULL COMMENT '原用户组ID',
    `to_group_id`   int(10) UNSIGNED NOT NULL COMMENT '目标用户组ID',
    `points_used`   int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '使用的积分',
    `duration`      int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '升级有效时长 (秒)',
    `valid_until`   int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '有效期截止时间戳',
    `create_time`   int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间戳'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户组升级日志表';

-- --------------------------------------------------------

--
-- 表的结构 `ha_user_info`
--

CREATE TABLE `ha_user_info`
(
    `id`          int(11)          NOT NULL COMMENT 'ID',
    `username`    varchar(64)      NOT NULL COMMENT '用户名',
    `password`    varchar(64)               DEFAULT '' COMMENT '密码',
    `mobile`      varchar(18)               DEFAULT '' COMMENT '手机号',
    `nickname`    varchar(64)               DEFAULT '' COMMENT '昵称',
    `group_id`    int(11)                   DEFAULT '1' COMMENT '用户组',
    `auth_id`     int(11)                   DEFAULT '0' COMMENT '权限组',
    `login_num`   int(11)                   DEFAULT '0' COMMENT '登陆次数',
    `last_time`   int(11)                   DEFAULT '0' COMMENT '最近登录时间',
    `last_ip`     varchar(64)               DEFAULT NULL COMMENT '最近登录IP',
    `status`      tinyint(4)                DEFAULT '1' COMMENT '用户状态',
    `points`      int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '用户积分',
    `avatar`      varchar(500)              DEFAULT NULL COMMENT '头像',
    `describe`    text COMMENT '签名',
    `sex`         tinyint(4)                DEFAULT '0' COMMENT '性别',
    `create_time` int(11)                   DEFAULT '0',
    `update_time` int(11)                   DEFAULT '0',
    `birthday`    date                      DEFAULT NULL COMMENT '生日'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户表';

--
-- 转存表中的数据 `ha_user_info`
--

INSERT INTO `ha_user_info` (`id`, `username`, `password`, `mobile`, `nickname`, `group_id`, `auth_id`, `login_num`,
                            `last_time`, `last_ip`, `status`, `points`, `avatar`, `describe`, `sex`, `create_time`,
                            `update_time`, `birthday`)
VALUES (10000, 'admin', '$2y$10$8yB0meTRNSOrzfuczRz/iejjCteuGjB6HPsnQpXZ/njpcutWDqHCC', '', '', 1, 0, 1, 1736439093,
        '192.168.31.109', 1, 0, NULL, NULL, 0, 0, 1736439093, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `ha_user_login_log`
--

CREATE TABLE `ha_user_login_log`
(
    `id`          int(11) NOT NULL,
    `ip`          varchar(64) DEFAULT '',
    `uid`         int(11)     DEFAULT '0' COMMENT '用户id',
    `create_time` int(11)     DEFAULT NULL COMMENT '登录时间'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='登录日志';

-- --------------------------------------------------------

--
-- 表的结构 `ha_user_points_log`
--

CREATE TABLE `ha_user_points_log`
(
    `id`          int(10) UNSIGNED NOT NULL COMMENT '主键ID',
    `uid`         int(10) UNSIGNED NOT NULL COMMENT '用户ID',
    `points`      int(11)          NOT NULL DEFAULT '0' COMMENT '积分变动值 (正数为增加，负数为扣减)',
    `type`        varchar(32)      NOT NULL COMMENT '积分变动类型 (如：签到、消费、升级等)',
    `desc`        text COMMENT '积分变动描述',
    `create_time` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间戳'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户积分记录表';

--
-- 转储表的索引
--

--
-- 表的索引 `ha_system_attachment`
--
ALTER TABLE `ha_system_attachment`
    ADD PRIMARY KEY (`id`),
    ADD KEY `system_attachment_ext_index` (`ext`);

--
-- 表的索引 `ha_system_config`
--
ALTER TABLE `ha_system_config`
    ADD PRIMARY KEY (`id`),
    ADD KEY `type` (`type`),
    ADD KEY `group` (`group_id`),
    ADD KEY `system_config_status_index` (`status`),
    ADD KEY `system_config_module_index` (`module`);

--
-- 表的索引 `ha_system_config_group`
--
ALTER TABLE `ha_system_config_group`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_system_event`
--
ALTER TABLE `ha_system_event`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_site_event_name` (`name`),
    ADD KEY `system_event_status_index` (`status`);

--
-- 表的索引 `ha_system_event_listen`
--
ALTER TABLE `ha_system_event_listen`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_site_event_plugin_event_key` (`event_key`),
    ADD KEY `idx_site_event_plugin_plugin_key` (`plugin_key`),
    ADD KEY `idx_site_event_plugin_sort` (`list`),
    ADD KEY `idx_site_event_plugin_status` (`status`);

--
-- 表的索引 `ha_system_feedback`
--
ALTER TABLE `ha_system_feedback`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_status` (`status`),
    ADD KEY `idx_user_id` (`id`),
    ADD KEY `idx_handled_by` (`handled_by`);

--
-- 表的索引 `ha_system_icon`
--
ALTER TABLE `ha_system_icon`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_system_links`
--
ALTER TABLE `ha_system_links`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_site_link_cid` (`cid`);

--
-- 表的索引 `ha_system_menu`
--
ALTER TABLE `ha_system_menu`
    ADD PRIMARY KEY (`id`),
    ADD KEY `system_menu_node_index` (`node`),
    ADD KEY `system_menu_status_index` (`status`);

--
-- 表的索引 `ha_system_module`
--
ALTER TABLE `ha_system_module`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_system_nav`
--
ALTER TABLE `ha_system_nav`
    ADD PRIMARY KEY (`id`),
    ADD KEY `ha_system_nav_dir_index` (`dir`),
    ADD KEY `ha_system_nav_pid_index` (`pid`);

--
-- 表的索引 `ha_system_operation_log`
--
ALTER TABLE `ha_system_operation_log`
    ADD PRIMARY KEY (`id`),
    ADD KEY `system_operation_log_admin_id_index` (`uid`),
    ADD KEY `system_operation_log_create_time_index` (`create_time`);

--
-- 表的索引 `ha_user_auth`
--
ALTER TABLE `ha_user_auth`
    ADD PRIMARY KEY (`id`);

--
-- 表的索引 `ha_user_fav`
--
ALTER TABLE `ha_user_fav`
    ADD PRIMARY KEY (`id`),
    ADD KEY `idx_uid` (`uid`),
    ADD KEY `idx_module_mid` (`module`, `mid`),
    ADD KEY `idx_content_id` (`content_id`),
    ADD KEY `idx_status` (`status`);

--
-- 表的索引 `ha_user_group`
--
ALTER TABLE `ha_user_group`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `user_group_name_uindex` (`name`),
    ADD KEY `user_group_status_create_time_index` (`status`, `create_time`);

--
-- 表的索引 `ha_user_group_upgrade_log`
--
ALTER TABLE `ha_user_group_upgrade_log`
    ADD PRIMARY KEY (`id`),
    ADD KEY `user_group_upgrade_log_user_id_index` (`uid`),
    ADD KEY `user_group_upgrade_log_create_time_index` (`create_time`);

--
-- 表的索引 `ha_user_info`
--
ALTER TABLE `ha_user_info`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `user_info_username_uindex` (`username`),
    ADD UNIQUE KEY `user_info_mobile_uindex` (`mobile`),
    ADD KEY `user_info_create_time_index` (`create_time`),
    ADD KEY `user_info_last_time_index` (`last_time`);

--
-- 表的索引 `ha_user_login_log`
--
ALTER TABLE `ha_user_login_log`
    ADD PRIMARY KEY (`id`),
    ADD KEY `ha_user_login_log_create_time_index` (`create_time`),
    ADD KEY `ha_user_login_log_uid_index` (`uid`);

--
-- 表的索引 `ha_user_points_log`
--
ALTER TABLE `ha_user_points_log`
    ADD PRIMARY KEY (`id`),
    ADD KEY `user_points_log_user_id_index` (`uid`),
    ADD KEY `user_points_log_create_time_index` (`create_time`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ha_system_attachment`
--
ALTER TABLE `ha_system_attachment`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_system_config`
--
ALTER TABLE `ha_system_config`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 43;

--
-- 使用表AUTO_INCREMENT `ha_system_config_group`
--
ALTER TABLE `ha_system_config_group`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 6;

--
-- 使用表AUTO_INCREMENT `ha_system_event`
--
ALTER TABLE `ha_system_event`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 8;

--
-- 使用表AUTO_INCREMENT `ha_system_event_listen`
--
ALTER TABLE `ha_system_event_listen`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_system_feedback`
--
ALTER TABLE `ha_system_feedback`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID';

--
-- 使用表AUTO_INCREMENT `ha_system_icon`
--
ALTER TABLE `ha_system_icon`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 4;

--
-- 使用表AUTO_INCREMENT `ha_system_links`
--
ALTER TABLE `ha_system_links`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 2;

--
-- 使用表AUTO_INCREMENT `ha_system_menu`
--
ALTER TABLE `ha_system_menu`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 38;

--
-- 使用表AUTO_INCREMENT `ha_system_module`
--
ALTER TABLE `ha_system_module`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 2;

--
-- 使用表AUTO_INCREMENT `ha_system_nav`
--
ALTER TABLE `ha_system_nav`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
    AUTO_INCREMENT = 10;

--
-- 使用表AUTO_INCREMENT `ha_system_operation_log`
--
ALTER TABLE `ha_system_operation_log`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID';

--
-- 使用表AUTO_INCREMENT `ha_user_auth`
--
ALTER TABLE `ha_user_auth`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    AUTO_INCREMENT = 2;

--
-- 使用表AUTO_INCREMENT `ha_user_fav`
--
ALTER TABLE `ha_user_fav`
    MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID';

--
-- 使用表AUTO_INCREMENT `ha_user_group`
--
ALTER TABLE `ha_user_group`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    AUTO_INCREMENT = 2;

--
-- 使用表AUTO_INCREMENT `ha_user_group_upgrade_log`
--
ALTER TABLE `ha_user_group_upgrade_log`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID';

--
-- 使用表AUTO_INCREMENT `ha_user_info`
--
ALTER TABLE `ha_user_info`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    AUTO_INCREMENT = 10001;

--
-- 使用表AUTO_INCREMENT `ha_user_login_log`
--
ALTER TABLE `ha_user_login_log`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ha_user_points_log`
--
ALTER TABLE `ha_user_points_log`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;

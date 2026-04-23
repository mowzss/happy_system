/*同步更新数据至2026-04-23  */

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE = @@TIME_ZONE */;
/*!40103 SET TIME_ZONE = '+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
/*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

--
-- Table structure for table `ha_system_attachment`
--

DROP TABLE IF EXISTS `ha_system_attachment`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_attachment`
(
    `id`          int(11) unsigned                        NOT NULL AUTO_INCREMENT,
    `uid`         mediumint(8) unsigned                   NOT NULL DEFAULT '0' COMMENT '用户id',
    `name`        varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件名',
    `path`        varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件路径',
    `url`         varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件链接',
    `imagewidth`  int(10) unsigned                                 DEFAULT '0' COMMENT '宽度',
    `imageheight` int(10) unsigned                                 DEFAULT '0' COMMENT '高度',
    `mime`        varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '文件mime类型',
    `ext`         varchar(4) COLLATE utf8mb4_unicode_ci   NOT NULL DEFAULT '' COMMENT '文件类型',
    `size`        int(11) unsigned                        NOT NULL DEFAULT '0' COMMENT '文件大小',
    `md5`         varchar(32) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT '文件md5',
    `sha1`        varchar(40) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT '' COMMENT 'sha1 散列值',
    `driver`      varchar(16) COLLATE utf8mb4_unicode_ci  NOT NULL DEFAULT 'local' COMMENT '上传驱动',
    `create_time` int(10) unsigned                                 DEFAULT NULL COMMENT '上传时间',
    `update_time` int(10) unsigned                                 DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `system_attachment_ext_index` (`ext`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='附件表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_attachment`
--

LOCK TABLES `ha_system_attachment` WRITE;
/*!40000 ALTER TABLE `ha_system_attachment`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_attachment`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_config`
--

DROP TABLE IF EXISTS `ha_system_config`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_config`
(
    `id`          int(11)                                NOT NULL AUTO_INCREMENT,
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
    `update_time` int(11)                                         DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `type` (`type`),
    KEY `group` (`group_id`),
    KEY `system_config_status_index` (`status`),
    KEY `system_config_module_index` (`module`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 52
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT ='网站配置';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_config`
--

LOCK TABLES `ha_system_config` WRITE;
/*!40000 ALTER TABLE `ha_system_config`
    DISABLE KEYS */;
INSERT INTO `ha_system_config`
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
        '', 'default', NULL, 0, 'system', 1, 1736259844, 1736264408),
       (43, 'site_domain', 'text', '网站域名', 1, '',
        '尽量填写域名，需要带http://或https:// 命令行等模式下部分服务需使用域名', '', NULL, 0, 'system', 1, 1776861707,
        1776861707),
       (44, 'is_open', 'radio', '开启推送', 6, '1|开启\n0|关闭', '是否开启IndexNow推送', '', NULL, 1000, 'p_index_now',
        1, 1776861707, 1776861707),
       (45, 'open_module', 'checkbox', '开启模块', 6, '\\app\\common\\fun\\system\\SystemModule@getAllModule',
        '选择开启IndexNow推送模块', '', NULL, 800, 'p_index_now', 1, 1776861707, 1776861707),
       (46, 'index_key', 'text', '推送秘钥', 6, '', '设置推送秘钥', '5c24c0585ea94a21258dd3d95b05ca51', NULL, 900,
        'p_index_now', 1, 1776861707, 1776861707),
       (47, 'editor_default', 'select', '默认富文本编辑器', 1,
        'tinymce|tinymce编辑器\nwangeditor|WangEditor\nueditor|百度Ueditor',
        '模型设计中默认的富文本编辑器，如模型设计中指定了编辑器，则此设置无效', '', NULL, 0, 'system', 1, 1776861707,
        1776861707),
       (48, 'static_upload', 'radio', '前端静态文件存储位置', 7, 'local|本地\noss|阿里云oss\nqiniu|七牛云',
        '前端静态文件存储位置，如存储至阿里云oss则需要在系统设置-存储配置中配置oss信息。 配置后可执行php think cloud:upload-static 初始化上传静态文件数据,也可以通过定时任务执行。',
        'local', NULL, 0, 'system', 1, 1776861707, 1776861707),
       (49, 'static_prefix', 'text', '云存储路径前缀', 7, '', '上传至远程的文件路径前缀', 'static', NULL, 0, 'system',
        1, 1776861707, 1776861707),
       (50, 'static_local_path', 'text', '本地文件路径', 7, '', '静态文件本地位置 无需public', 'static', NULL, 0,
        'system', 1, 1776861707, 1776861707),
       (51, 'static_version', 'text', '前端资源版本号', 7, '', '适应前端资源缓存，默认上传云存储资源后自动设置版本号',
        '26.0423.423208', NULL, 0, 'system', 1, 1776861707, 1776861707);
/*!40000 ALTER TABLE `ha_system_config`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_config_group`
--

DROP TABLE IF EXISTS `ha_system_config_group`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_config_group`
(
    `id`       int(11) NOT NULL AUTO_INCREMENT,
    `title`    varchar(256) DEFAULT NULL COMMENT '名称',
    `sys_show` int(11)      DEFAULT '0' COMMENT '系统设置显示',
    `module`   varchar(255) DEFAULT '' COMMENT '归属模块 system为系统模块',
    `status`   int(11)      DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 8
  DEFAULT CHARSET = utf8mb4 COMMENT ='设置分组';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_config_group`
--

LOCK TABLES `ha_system_config_group` WRITE;
/*!40000 ALTER TABLE `ha_system_config_group`
    DISABLE KEYS */;
INSERT INTO `ha_system_config_group`
VALUES (1, '基础设置', 1, 'system', 1),
       (2, '会员设置', 1, 'system', 1),
       (3, '邮件设置', 1, 'system', 1),
       (4, '存储设置', 1, 'system', 1),
       (5, '短信设置', 1, 'system', 1),
       (6, 'IndexNow推送', 0, 'p_index_now', 1),
       (7, '前端资源', 1, 'system', 1);
/*!40000 ALTER TABLE `ha_system_config_group`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_event`
--

DROP TABLE IF EXISTS `ha_system_event`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_event`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name`        varchar(80)      NOT NULL DEFAULT '' COMMENT '钩子名称',
    `info`        text COMMENT '钩子描述',
    `status`      int(1)           NOT NULL DEFAULT '1' COMMENT '是否启用',
    `list`        int(11)          NOT NULL DEFAULT '0' COMMENT '排序值',
    `params_info` text,
    PRIMARY KEY (`id`),
    KEY `idx_site_event_name` (`name`),
    KEY `system_event_status_index` (`status`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 14
  DEFAULT CHARSET = utf8mb4 COMMENT ='接口(钩子)列表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_event`
--

LOCK TABLES `ha_system_event` WRITE;
/*!40000 ALTER TABLE `ha_system_event`
    DISABLE KEYS */;
INSERT INTO `ha_system_event`
VALUES (1, 'AppInit', '应用初始化标签位', 1, 0, NULL),
       (2, 'HttpRun', '应用开始标签位', 1, 0, NULL),
       (3, 'HttpEnd', '应用结束标签位', 1, 0, NULL),
       (4, 'LogWrite', '日志write方法标签位', 1, 0, NULL),
       (5, 'RouteLoaded', '路由加载完成', 1, 0, NULL),
       (6, 'LogRecord', '日志记录', 1, 0, NULL),
       (7, 'HomeIndex', '网站首页', 1, 0, '无'),
       (8, 'ContentAddBefore', '内容添加前', 1, 0, 'POST数据'),
       (9, 'ContentAddAfter', '内容添加后', 1, 0, 'POST数据,无数据回传'),
       (10, 'ContentEditBefore', '内容编辑前', 1, 0, 'POST数据'),
       (11, 'ContentEditAfter', '内容编辑后', 1, 0, 'POST数据,无数据回传'),
       (12, 'UserRegister', '用户注册后', 1, 0, '用户模型'),
       (13, 'UserLogin', '用户登录后', 1, 0, '用户模型');
/*!40000 ALTER TABLE `ha_system_event`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_event_listen`
--

DROP TABLE IF EXISTS `ha_system_event_listen`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_event_listen`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `event_key`   varchar(50)      NOT NULL COMMENT '所归属的接口关键字',
    `plugin_key`  varchar(50)               DEFAULT NULL COMMENT '所归属的插件关键字,也即目录名',
    `event_class` varchar(80)      NOT NULL COMMENT '钩子运行的类名',
    `info`        varchar(255)     NOT NULL COMMENT '此钩子插件能实现的功能描述',
    `status`      int(1)           NOT NULL DEFAULT '1' COMMENT '是否启用',
    `list`        int(11)          NOT NULL DEFAULT '0' COMMENT '执行的先后顺序',
    `author`      varchar(80)               DEFAULT NULL COMMENT '开发者',
    `author_url`  varchar(120)              DEFAULT NULL COMMENT '开发者网站',
    `version`     varchar(60)               DEFAULT NULL COMMENT '版本信息',
    PRIMARY KEY (`id`),
    KEY `idx_site_event_plugin_event_key` (`event_key`),
    KEY `idx_site_event_plugin_plugin_key` (`plugin_key`),
    KEY `idx_site_event_plugin_sort` (`list`),
    KEY `idx_site_event_plugin_status` (`status`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='事件监听';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_event_listen`
--

LOCK TABLES `ha_system_event_listen` WRITE;
/*!40000 ALTER TABLE `ha_system_event_listen`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_event_listen`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_feedback`
--

DROP TABLE IF EXISTS `ha_system_feedback`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_feedback`
(
    `id`           int(11)      NOT NULL AUTO_INCREMENT COMMENT '主键ID',
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
    `response`     text COMMENT '回复内容 (管理员对用户的回复)',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_user_id` (`id`),
    KEY `idx_handled_by` (`handled_by`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='举报/反馈表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_feedback`
--

LOCK TABLES `ha_system_feedback` WRITE;
/*!40000 ALTER TABLE `ha_system_feedback`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_feedback`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_icon`
--

DROP TABLE IF EXISTS `ha_system_icon`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_icon`
(
    `id`          int(11)      NOT NULL AUTO_INCREMENT,
    `path`        varchar(255) NOT NULL,
    `url`         varchar(255) NOT NULL,
    `title`       varchar(64)           DEFAULT NULL COMMENT '名称',
    `is_show`     int(1)       NOT NULL DEFAULT '0' COMMENT '是否后台引用显示',
    `name`        varchar(100) NOT NULL COMMENT '字体名称',
    `prefix`      varchar(50)  NOT NULL,
    `create_time` int(11)      NOT NULL DEFAULT '0',
    `update_time` int(11)      NOT NULL DEFAULT '0',
    `list`        int(11)               DEFAULT '0' COMMENT '排序',
    `status`      int(11)               DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 4
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_icon`
--

LOCK TABLES `ha_system_icon` WRITE;
/*!40000 ALTER TABLE `ha_system_icon`
    DISABLE KEYS */;
INSERT INTO `ha_system_icon`
VALUES (1, 'public/static/libs/layui/css/layui.css', '/static/libs/layui/css/layui.css', 'layui', 0, 'layui-icon',
        'layui-icon-', 1732974203, 1734425923, 15, 1),
       (3, 'public/static/libs/icon/icon.css', '/static/libs/icon/icon.css', '阿里自选', 0, 'iconfont', 'icon-',
        1734400900, 1735806355, 17, 1);
/*!40000 ALTER TABLE `ha_system_icon`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_jobs`
--

DROP TABLE IF EXISTS `ha_system_jobs`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_jobs`
(
    `id`             int(11) unsigned NOT NULL AUTO_INCREMENT,
    `queue`          varchar(255)        DEFAULT NULL,
    `payload`        longtext,
    `attempts`       tinyint(4) unsigned DEFAULT NULL,
    `reserve_time`   int(11) unsigned    DEFAULT NULL,
    `available_time` int(11) unsigned    DEFAULT NULL,
    `create_time`    int(11) unsigned    DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `queue` (`queue`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_jobs`
--

LOCK TABLES `ha_system_jobs` WRITE;
/*!40000 ALTER TABLE `ha_system_jobs`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_jobs`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_jobs_failed`
--

DROP TABLE IF EXISTS `ha_system_jobs_failed`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_jobs_failed`
(
    `id`         int(11) unsigned NOT NULL AUTO_INCREMENT,
    `connection` text,
    `queue`      text,
    `payload`    longtext,
    `exception`  longtext,
    `fail_time`  timestamp        NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_jobs_failed`
--

LOCK TABLES `ha_system_jobs_failed` WRITE;
/*!40000 ALTER TABLE `ha_system_jobs_failed`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_jobs_failed`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_links`
--

DROP TABLE IF EXISTS `ha_system_links`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_links`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `cid`         int(11)                   DEFAULT '0' COMMENT '分类id',
    `type`        int(11)                   DEFAULT '0' COMMENT '友链类型',
    `title`       varchar(255)              DEFAULT NULL COMMENT '网站标题',
    `url`         varchar(500)              DEFAULT NULL COMMENT '网站链接',
    `list`        int(11)                   DEFAULT '0' COMMENT '排序',
    `qq`          varchar(255)              DEFAULT NULL COMMENT '联系方式',
    `status`      int(11)                   DEFAULT '1' COMMENT '状态(1正常 0隐藏)',
    `start_time`  datetime                  DEFAULT NULL COMMENT '开始日期',
    `end_time`    datetime                  DEFAULT NULL COMMENT '结束日期',
    `is_long`     int(1)           NOT NULL DEFAULT '0',
    `create_time` int(10) unsigned          DEFAULT NULL,
    `update_time` int(10) unsigned          DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_site_link_cid` (`cid`),
    KEY `is_long` (`is_long`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb4 COMMENT ='友情链接';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_links`
--

LOCK TABLES `ha_system_links` WRITE;
/*!40000 ALTER TABLE `ha_system_links`
    DISABLE KEYS */;
INSERT INTO `ha_system_links`
VALUES (1, 1, 1, '星座之家', 'https://www.xingzuohome.com/', 0, '123789', 1, '2025-01-06 11:45:42',
        '2025-01-06 11:45:42', 0, 1736135142, 1736135142);
/*!40000 ALTER TABLE `ha_system_links`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_menu`
--

DROP TABLE IF EXISTS `ha_system_menu`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_menu`
(
    `id`     int(11) NOT NULL AUTO_INCREMENT,
    `pid`    int(11)      DEFAULT '0' COMMENT '父级栏目ID',
    `title`  varchar(64)  DEFAULT NULL COMMENT '菜单名称',
    `icon`   varchar(64)  DEFAULT NULL,
    `slot`   varchar(64)  DEFAULT '' COMMENT '菜单扩展标记 用于模块插入菜单',
    `node`   varchar(128) DEFAULT NULL COMMENT '节点',
    `params` varchar(512) DEFAULT NULL COMMENT '参数',
    `class`  int(11)      DEFAULT '1' COMMENT '类型 1节点 2链接',
    `list`   int(11)      DEFAULT '0' COMMENT '排序',
    `status` int(11)      DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`),
    KEY `system_menu_node_index` (`node`),
    KEY `system_menu_status_index` (`status`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 42
  DEFAULT CHARSET = utf8mb4 COMMENT ='系统菜单';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_menu`
--

LOCK TABLES `ha_system_menu` WRITE;
/*!40000 ALTER TABLE `ha_system_menu`
    DISABLE KEYS */;
INSERT INTO `ha_system_menu`
VALUES (1, 0, '系统管理', 'layui-icon layui-icon-set', 'system', '#', '', 1, 9000, 1),
       (2, 1, '设置管理', 'layui-icon layui-icon-set', 'system_config', '#', '', 1, 1000, 1),
       (3, 2, '系统设置', '', NULL, 'system/setting/index', '', 1, 800, 1),
       (4, 24, '后台菜单', '', NULL, 'system/menu/index', '', 1, 700, 1),
       (5, 0, '用户管理', 'layui-icon layui-icon-username', 'user', '#', '', 1, 1000, 1),
       (6, 5, '用户信息', 'layui-icon layui-icon-username', 'user_info', '#', '', 1, 900, 1),
       (7, 6, '用户资料', '', NULL, 'user/info/index', '', 1, 800, 1),
       (8, 24, '图标管理', '', NULL, 'system/icon/index', '', 1, 900, 1),
       (9, 2, '设置分组', '', NULL, 'system/configGroup/index', '', 1, 100, 1),
       (10, 0, '内容模块', 'layui-icon layui-icon-list', 'content', '#', '', 1, 11, 1),
       (12, 0, '扩展功能', 'layui-icon layui-icon-slider', 'ext', '#', '', 1, 11, 1),
       (13, 6, '用户权限', 'layui-icon layui-icon-vercode', NULL, 'user/auth/index', '', 1, 0, 1),
       (14, 24, '文件管理', 'layui-icon layui-icon-folder', NULL, 'system/attachment/index', '', 1, 0, 1),
       (15, 1, '事件管理', 'layui-icon layui-icon-rate-half', 'system_event', '#', '', 1, 800, 1),
       (16, 15, '事件设置', '', NULL, 'system/event/index', '', 1, 0, 1),
       (17, 15, '事件监听', '', NULL, 'system/eventListen/index', '', 1, 0, 1),
       (24, 1, '系统功能', 'layui-icon layui-icon-slider', 'system_ext', '#', '', 1, 900, 1),
       (25, 2, '设置字段', '', NULL, 'system/config/index', '', 1, 0, 1),
       (26, 24, '系统模块', '', NULL, 'system/module/index', '', 1, 0, 1),
       (27, 12, '系统扩展', 'layui-icon layui-icon-cols', 'ext_sys', '#', '', 1, 0, 1),
       (28, 27, '友情链接', '', NULL, 'system/links/index', '', 1, 0, 1),
       (29, 27, '投诉反馈', '', NULL, 'system/feedback/index', '', 1, 0, 1),
       (30, 5, '用户功能', 'layui-icon layui-icon-survey', 'user_ext', '#', '', 1, 0, 1),
       (31, 30, '用户收藏', '', NULL, 'user/fav/index', '', 1, 0, 1),
       (32, 6, '用户组', '', NULL, 'user/group/index', '', 1, 0, 1),
       (33, 6, '积分记录', '', NULL, 'user/PointsLog/index', '', 1, 0, 1),
       (34, 1, '系统日志', 'layui-icon layui-icon-tips', 'system_log', '#', '', 1, 0, 1),
       (35, 34, '操作日志', '', NULL, 'system/OperationLog/index', '', 1, 0, 1),
       (36, 1, '网站管理', 'layui-icon layui-icon-website', 'system_site', '', '#', 1, 700, 1),
       (37, 36, '网站菜单', '', NULL, 'system/nav/index', '', 1, 0, 1),
       (38, 24, '计划任务', 'layui-icon layui-icon-senior', '', 'system/task/index', '', 1, 0, 1),
       (39, 24, 'SiteMap', 'layui-icon layui-icon-senior', '', 'system/sitemap/index', '', 1, 0, 1),
       (40, 27, 'IndexNow', '', '', 'system/indexNow/index', '', 1, 100, 1),
       (41, 34, '蜘蛛日志', '', '', 'system/spider/index', '', 1, 100, 1);
/*!40000 ALTER TABLE `ha_system_menu`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_module`
--

DROP TABLE IF EXISTS `ha_system_module`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_module`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `title`       varchar(64) DEFAULT '' COMMENT '模块名称',
    `dir`         varchar(64) DEFAULT '' COMMENT '模块标记/目录',
    `type`        int(11)     DEFAULT '1' COMMENT '模块类型 1为系统模块 2插件',
    `is_copy`     int(11)     DEFAULT '0' COMMENT '是否允许复制',
    `status`      int(11)     DEFAULT NULL COMMENT '状态 1正常 0禁用',
    `create_time` int(11)     DEFAULT '0' COMMENT '创建时间',
    `update_time` int(11)     DEFAULT '0' COMMENT '修改时间',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb4 COMMENT ='系统模块';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_module`
--

LOCK TABLES `ha_system_module` WRITE;
/*!40000 ALTER TABLE `ha_system_module`
    DISABLE KEYS */;
INSERT INTO `ha_system_module`
VALUES (1, '系统核心', 'system', 1, 0, 1, 0, 0);
/*!40000 ALTER TABLE `ha_system_module`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_nav`
--

DROP TABLE IF EXISTS `ha_system_nav`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_nav`
(
    `id`     int(11) NOT NULL AUTO_INCREMENT,
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
    `status` int(11)      DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`),
    KEY `ha_system_nav_dir_index` (`dir`),
    KEY `ha_system_nav_pid_index` (`pid`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 10
  DEFAULT CHARSET = utf8mb4 COMMENT ='网站菜单导航';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_nav`
--

LOCK TABLES `ha_system_nav` WRITE;
/*!40000 ALTER TABLE `ha_system_nav`
    DISABLE KEYS */;
INSERT INTO `ha_system_nav`
VALUES (1, 0, '首页', 'layui-icon layui-icon-home', 'pc', '/', '_self', '', '', 2, '', 0, 1),
       (2, 0, '测试栏目', '', 'pc', '/', '_blank', '', '', 2, '', 0, 1),
       (3, 2, '测试子栏目', '', 'pc', '/', '_blank', '', '', 2, '', 0, 1),
       (4, 0, '个人信息', 'layui-icon layui-icon-username', 'pc_user', '#', '', '', '', 2, '', 10000, 1),
       (5, 4, '我的信息', '', 'pc_user', '', '', 'user/index/main', '', 1, '', 1000, 1),
       (6, 4, '修改资料', '', 'pc_user', '', '', 'user/index/profile', '', 1, '', 0, 1),
       (7, 4, '修改密码', '', 'pc_user', '', '', 'user/index/pass', '', 1, '', 0, 1),
       (8, 4, '积分记录', '', 'pc_user', '', '', 'user/points_log/index', '', 1, '', 0, 1),
       (9, 4, '登录记录', '', 'pc_user', '', '', 'user/login_log/index', '', 1, '', 0, 1);
/*!40000 ALTER TABLE `ha_system_nav`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_operation_log`
--

DROP TABLE IF EXISTS `ha_system_operation_log`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_operation_log`
(
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `uid`         int(10) unsigned NOT NULL COMMENT '管理员ID',
    `node`        varchar(255)     NOT NULL COMMENT '操作节点',
    `desc`        text COMMENT '操作描述',
    `ip`          varchar(45)      NOT NULL COMMENT '操作时的IP地址',
    `user_agent`  text COMMENT '操作时的User-Agent信息',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作时间戳',
    PRIMARY KEY (`id`),
    KEY `system_operation_log_admin_id_index` (`uid`),
    KEY `system_operation_log_create_time_index` (`create_time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='系统操作日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_operation_log`
--

LOCK TABLES `ha_system_operation_log` WRITE;
/*!40000 ALTER TABLE `ha_system_operation_log`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_operation_log`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_sitemap`
--

DROP TABLE IF EXISTS `ha_system_sitemap`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_sitemap`
(
    `id`          int(11) unsigned NOT NULL AUTO_INCREMENT,
    `module`      varchar(64)      NOT NULL COMMENT '模块',
    `class`       varchar(255)     DEFAULT NULL COMMENT '数据类型',
    `url`         varchar(2555)    NOT NULL COMMENT '生成url',
    `type`        varchar(255)     NOT NULL COMMENT '地图类型',
    `domain`      varchar(255)     DEFAULT '',
    `create_time` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_site_sitemap_module` (`module`),
    KEY `idx_site_sitemap_type` (`type`(191))
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_sitemap`
--

LOCK TABLES `ha_system_sitemap` WRITE;
/*!40000 ALTER TABLE `ha_system_sitemap`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_sitemap`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_spider_date`
--

DROP TABLE IF EXISTS `ha_system_spider_date`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_spider_date`
(
    `id`           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `date`         date                NOT NULL COMMENT '统计日期',
    `name`         varchar(255)        NOT NULL COMMENT '蜘蛛名称',
    `total_visits` int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '访问次数',
    `unique_urls`  int(11)                      DEFAULT '0' COMMENT '访问的不同 URL 数量',
    PRIMARY KEY (`id`),
    UNIQUE KEY `date_spider` (`date`, `name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_spider_date`
--

LOCK TABLES `ha_system_spider_date` WRITE;
/*!40000 ALTER TABLE `ha_system_spider_date`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_spider_date`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_spider_hourly`
--

DROP TABLE IF EXISTS `ha_system_spider_hourly`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_spider_hourly`
(
    `id`           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name`         varchar(50)         NOT NULL COMMENT '蜘蛛名称（如 Google、百度）',
    `date`         date                NOT NULL COMMENT '统计日期',
    `hour`         tinyint(3) unsigned NOT NULL COMMENT '统计小时（0~23）',
    `total_visits` int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '总访问次数',
    `unique_urls`  int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '访问的不同URL数量',
    PRIMARY KEY (`id`),
    KEY `idx_stat_date_spider` (`date`, `name`),
    KEY `idx_spider_name` (`name`),
    KEY `ha_system_spider_hourly_hour_index` (`hour`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='每小时蜘蛛抓取数据统计';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_spider_hourly`
--

LOCK TABLES `ha_system_spider_hourly` WRITE;
/*!40000 ALTER TABLE `ha_system_spider_hourly`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_spider_hourly`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_spider_logs`
--

DROP TABLE IF EXISTS `ha_system_spider_logs`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_spider_logs`
(
    `id`          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name`        varchar(128)        NOT NULL COMMENT '蜘蛛名称，如 Googlebot',
    `url`         varchar(512)        NOT NULL COMMENT '访问的 URL',
    `ip`          varchar(45)         NOT NULL COMMENT 'IP 地址',
    `module`      varchar(64) DEFAULT NULL COMMENT '模块',
    `user_agent`  text                NOT NULL COMMENT 'User-Agent 字符串',
    `create_time` int(11)     DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `ha_system_spider_logs_name_index` (`name`),
    KEY `ha_system_spider_logs_module_index` (`module`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_spider_logs`
--

LOCK TABLES `ha_system_spider_logs` WRITE;
/*!40000 ALTER TABLE `ha_system_spider_logs`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_spider_logs`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_tasks`
--

DROP TABLE IF EXISTS `ha_system_tasks`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_tasks`
(
    `id`          int(11)   NOT NULL AUTO_INCREMENT,
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
    `update_time` int(11)            DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `ha_system_tasks_list_index` (`list`),
    KEY `ha_system_tasks_next_time_index` (`next_time`),
    KEY `ha_system_tasks_status_index` (`status`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_tasks`
--

LOCK TABLES `ha_system_tasks` WRITE;
/*!40000 ALTER TABLE `ha_system_tasks`
    DISABLE KEYS */;
INSERT INTO `ha_system_tasks`
VALUES (1, 'indexNow推送', '46 */4 * * *', '\\app\\common\\task\\IndexNow', NULL, 0, 0, NULL, NULL, 1, NULL, 1776861707,
        1776861707);
/*!40000 ALTER TABLE `ha_system_tasks`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_tasks_log`
--

DROP TABLE IF EXISTS `ha_system_tasks_log`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_tasks_log`
(
    `id`            int(11)      NOT NULL AUTO_INCREMENT,
    `task_name`     varchar(255) NOT NULL,
    `task_id`       int(11)               DEFAULT '0',
    `status`        int(11)      NOT NULL DEFAULT '1',
    `output`        text,
    `error_message` text,
    `create_time`   int(11)               DEFAULT '0',
    `update_time`   int(11)               DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `ha_system_tasks_log_status_index` (`status`),
    KEY `ha_system_tasks_log_task_id_index` (`task_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_tasks_log`
--

LOCK TABLES `ha_system_tasks_log` WRITE;
/*!40000 ALTER TABLE `ha_system_tasks_log`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_tasks_log`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_system_upgrade_log`
--

DROP TABLE IF EXISTS `ha_system_upgrade_log`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_system_upgrade_log`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `module`      varchar(64)  DEFAULT NULL COMMENT '所属模块',
    `filename`    varchar(128) DEFAULT NULL,
    `create_time` int(11)      DEFAULT NULL COMMENT '升级日期',
    PRIMARY KEY (`id`),
    KEY `ha_system_upgrade_log_filename_index` (`filename`),
    KEY `ha_system_upgrade_log_module_index` (`module`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='升级日志';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_system_upgrade_log`
--

LOCK TABLES `ha_system_upgrade_log` WRITE;
/*!40000 ALTER TABLE `ha_system_upgrade_log`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_system_upgrade_log`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_auth`
--

DROP TABLE IF EXISTS `ha_user_auth`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_auth`
(
    `id`           int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `title`        varchar(64) DEFAULT NULL COMMENT '权限组',
    `desc`         text COMMENT '说明',
    `nodes`        longtext COMMENT '权限节点',
    `is_authorize` int(11)     DEFAULT '0' COMMENT '后台权限',
    `status`       int(11)     DEFAULT '1' COMMENT '状态',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb4 COMMENT ='权限组';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_auth`
--

LOCK TABLES `ha_user_auth` WRITE;
/*!40000 ALTER TABLE `ha_user_auth`
    DISABLE KEYS */;
INSERT INTO `ha_user_auth`
VALUES (1, '超级管理员', '超级管理员，拥有超多权限', '', 1, 1);
/*!40000 ALTER TABLE `ha_user_auth`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_fav`
--

DROP TABLE IF EXISTS `ha_user_fav`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_fav`
(
    `id`            int(11) unsigned    NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `uid`           int(11) unsigned    NOT NULL COMMENT '用户ID',
    `module`        varchar(50)         NOT NULL COMMENT '模块名称 (如文章、商品等)',
    `mid`           int(11) unsigned    NOT NULL COMMENT '模型ID (对应模块中的内容ID)',
    `content_id`    bigint(20) unsigned NOT NULL COMMENT '内容ID (具体收藏的内容ID)',
    `content_title` varchar(255)        NOT NULL COMMENT '内容标题',
    `content_url`   varchar(500)                 DEFAULT NULL COMMENT '内容链接 (可选)',
    `create_time`   int(11) unsigned             DEFAULT NULL COMMENT '收藏时间',
    `update_time`   int(11) unsigned             DEFAULT NULL COMMENT '更新时间',
    `status`        tinyint(1)          NOT NULL DEFAULT '1' COMMENT '状态 (1: 正常, 0: 已删除)',
    PRIMARY KEY (`id`),
    KEY `idx_uid` (`uid`),
    KEY `idx_module_mid` (`module`, `mid`),
    KEY `idx_content_id` (`content_id`),
    KEY `idx_status` (`status`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='收藏记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_fav`
--

LOCK TABLES `ha_user_fav` WRITE;
/*!40000 ALTER TABLE `ha_user_fav`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_user_fav`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_group`
--

DROP TABLE IF EXISTS `ha_user_group`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_group`
(
    `id`             int(10) unsigned    NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `name`           varchar(64)         NOT NULL COMMENT '用户组名称',
    `desc`           text COMMENT '用户组描述',
    `status`         tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态 (1: 正常, 0: 禁用)',
    `create_time`    int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '创建时间戳',
    `update_time`    int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '更新时间戳',
    `upgrade_points` int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '升级所需积分',
    `upgrade_day`    int(10) unsigned    NOT NULL DEFAULT '0' COMMENT '升级有效时长 (天)',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_group_name_uindex` (`name`),
    KEY `user_group_status_create_time_index` (`status`, `create_time`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 2
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户组表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_group`
--

LOCK TABLES `ha_user_group` WRITE;
/*!40000 ALTER TABLE `ha_user_group`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_user_group`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_group_upgrade_log`
--

DROP TABLE IF EXISTS `ha_user_group_upgrade_log`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_group_upgrade_log`
(
    `id`            int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `uid`           int(10) unsigned NOT NULL COMMENT '用户ID',
    `from_group_id` int(10) unsigned NOT NULL COMMENT '原用户组ID',
    `to_group_id`   int(10) unsigned NOT NULL COMMENT '目标用户组ID',
    `points_used`   int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用的积分',
    `duration`      int(10) unsigned NOT NULL DEFAULT '0' COMMENT '升级有效时长 (秒)',
    `valid_until`   int(10) unsigned NOT NULL DEFAULT '0' COMMENT '有效期截止时间戳',
    `create_time`   int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
    PRIMARY KEY (`id`),
    KEY `user_group_upgrade_log_user_id_index` (`uid`),
    KEY `user_group_upgrade_log_create_time_index` (`create_time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户组升级日志表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_group_upgrade_log`
--

LOCK TABLES `ha_user_group_upgrade_log` WRITE;
/*!40000 ALTER TABLE `ha_user_group_upgrade_log`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_user_group_upgrade_log`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_info`
--

DROP TABLE IF EXISTS `ha_user_info`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_info`
(
    `id`          int(11)          NOT NULL AUTO_INCREMENT COMMENT 'ID',
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
    `points`      int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户积分',
    `avatar`      varchar(500)              DEFAULT NULL COMMENT '头像',
    `describe`    text COMMENT '签名',
    `sex`         tinyint(4)                DEFAULT '0' COMMENT '性别',
    `create_time` int(11)                   DEFAULT '0',
    `update_time` int(11)                   DEFAULT '0',
    `birthday`    date                      DEFAULT NULL COMMENT '生日',
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_info_username_uindex` (`username`),
    UNIQUE KEY `user_info_mobile_uindex` (`mobile`),
    KEY `user_info_create_time_index` (`create_time`),
    KEY `user_info_last_time_index` (`last_time`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 10000
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_info`
--

LOCK TABLES `ha_user_info` WRITE;
/*!40000 ALTER TABLE `ha_user_info`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_user_info`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_login_log`
--

DROP TABLE IF EXISTS `ha_user_login_log`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_login_log`
(
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `ip`          varchar(64) DEFAULT '',
    `uid`         int(11)     DEFAULT '0' COMMENT '用户id',
    `create_time` int(11)     DEFAULT NULL COMMENT '登录时间',
    PRIMARY KEY (`id`),
    KEY `ha_user_login_log_create_time_index` (`create_time`),
    KEY `ha_user_login_log_uid_index` (`uid`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='登录日志';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_login_log`
--

LOCK TABLES `ha_user_login_log` WRITE;
/*!40000 ALTER TABLE `ha_user_login_log`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_user_login_log`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_oauth`
--

DROP TABLE IF EXISTS `ha_user_oauth`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_oauth`
(
    `id`          int(11)     NOT NULL AUTO_INCREMENT,
    `uid`         int(11)     NOT NULL COMMENT '用户id',
    `type`        varchar(64) NOT NULL COMMENT 'oauth类型',
    `openid`      varchar(64) NOT NULL COMMENT '唯一码',
    `create_time` int(11)     NOT NULL DEFAULT '0' COMMENT '创建日期',
    `update_time` int(11)     NOT NULL DEFAULT '0' COMMENT '更新日期',
    PRIMARY KEY (`id`),
    KEY `typeanduid` (`type`, `uid`),
    KEY `type` (`type`, `openid`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='快捷登录';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_oauth`
--

LOCK TABLES `ha_user_oauth` WRITE;
/*!40000 ALTER TABLE `ha_user_oauth`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_user_oauth`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ha_user_points_log`
--

DROP TABLE IF EXISTS `ha_user_points_log`;
/*!40101 SET @saved_cs_client = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ha_user_points_log`
(
    `id`          int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `uid`         int(10) unsigned NOT NULL COMMENT '用户ID',
    `points`      int(11)          NOT NULL DEFAULT '0' COMMENT '积分变动值 (正数为增加，负数为扣减)',
    `type`        varchar(32)      NOT NULL COMMENT '积分变动类型 (如：签到、消费、升级等)',
    `desc`        text COMMENT '积分变动描述',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间戳',
    PRIMARY KEY (`id`),
    KEY `user_points_log_user_id_index` (`uid`),
    KEY `user_points_log_create_time_index` (`create_time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COMMENT ='用户积分记录表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ha_user_points_log`
--

LOCK TABLES `ha_user_points_log` WRITE;
/*!40000 ALTER TABLE `ha_user_points_log`
    DISABLE KEYS */;
/*!40000 ALTER TABLE `ha_user_points_log`
    ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'yx_com'
--

--
-- Dumping routines for database 'yx_com'
--
/*!40103 SET TIME_ZONE = @OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE = @OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES = @OLD_SQL_NOTES */;

-- Dump completed on 2026-04-23  8:49:21

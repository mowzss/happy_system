create table ha_article_column
(
    id              int auto_increment
        primary key,
    pid             int           default 0  null comment '父栏目',
    mid             int           default 0  null comment '所属模型',
    title           varchar(128)  default '' null comment '名称',
    image           text                     null comment '栏目图片',
    icon            varchar(255)  default '' null,
    seo_title       varchar(2000) default '' null comment 'SEO标题',
    seo_keywords    varchar(200)  default '' null comment '关键词',
    seo_description text                     null comment '描述',
    list            int           default 0  null comment '排序',
    view_file       varchar(128)  default '' null comment '模板路径',
    status          int           default 1  null comment '状态',
    create_time     int           default 0  null comment '创建时间',
    update_time     int           default 0  null comment '更新时间'
)
    comment '分类表';

create index article_column_pid_index
    on ha_article_column (pid);

create index article_column_title_index
    on ha_article_column (title);

create table ha_article_content
(
    id          bigint unsigned auto_increment comment '主键ID'
        primary key,
    mid         smallint unsigned  default 0  not null comment '模型ID',
    cid         mediumint unsigned default 0  not null comment '栏目ID',
    title       varchar(256)       default '' not null comment '标题',
    uid         int unsigned       default 0  not null comment '用户ID',
    view        int unsigned       default 0  not null comment '浏览量',
    status      tinyint            default 1  not null comment '状态：0未审 1已审 2推荐',
    list        int unsigned       default 0  not null comment '排序值',
    create_time int unsigned       default 0  not null comment '创建时间',
    update_time int unsigned       default 0  not null comment '修改时间',
    delete_time int                           null
)
    comment '测试';

create table ha_article_content_1
(
    id          bigint unsigned auto_increment comment '主键ID'
        primary key,
    mid         smallint unsigned  default 0  not null comment '模型ID',
    cid         mediumint unsigned default 0  not null comment '栏目ID',
    title       varchar(256)       default '' not null comment '标题',
    is_pic      tinyint            default 0  not null comment '是否带组图',
    uid         int unsigned       default 0  not null comment '用户ID',
    view        int unsigned       default 0  not null comment '浏览量',
    status      tinyint            default 1  not null comment '状态：0未审 1已审 2推荐',
    replynum    int unsigned       default 0  not null comment '评论数',
    description text                          null comment '简介',
    list        int unsigned       default 0  not null comment '排序值',
    images      text                          not null comment '组图',
    keywords    varchar(500)       default '' not null comment '关键词',
    extend      text                          null comment '扩展字段',
    create_time int unsigned       default 0  not null comment '创建时间',
    update_time int unsigned       default 0  not null comment '修改时间',
    delete_time int unsigned       default 0  not null comment '软删除'
)
    comment '测试';

create table ha_article_content_1s
(
    id      bigint unsigned auto_increment comment '主键ID'
        primary key,
    content longtext null comment '内容'
)
    comment '测试';

create table ha_article_field
(
    id          int(11) unsigned auto_increment
        primary key,
    mid         int          default 0   null comment '所属模型',
    name        varchar(64)  default ''  null comment '字段名称',
    type        varchar(64)  default ''  null comment '字段类型',
    title       varchar(256) default ''  null comment '标签名称',
    options     text                     null,
    help        text                     null comment '表单说明',
    required    int          default 0   null comment '是否必填',
    list        int          default 100 null comment '排序',
    edit        int          default 1   null comment '是否能修改 1可以修改 0不能修改',
    extend      longtext                 null comment '扩展参数',
    status      int          default 1   null comment '状态',
    create_time int          default 0   null comment '创建时间',
    update_time int          default 0   null comment '更新时间',
    is_search   int                      null comment '是否搜索 1搜索 0不启动'
)
    comment '字段设计';

create table ha_article_model
(
    id     int auto_increment
        primary key,
    title  varchar(64)  default ''  null comment '名称',
    info   varchar(512) default ''  null comment '描述',
    list   int          default 100 null comment '排序',
    is_del int          default 1   null comment '可否删除'
);

create index article_model_title_index
    on ha_article_model (title);

create table ha_article_reply
(
    id          bigint unsigned auto_increment comment '主键ID'
        primary key,
    pid         int unsigned        default 0  not null comment '引用回复上级ID',
    aid         bigint unsigned     default 0  not null comment '内容页ID',
    ispic       tinyint(1) unsigned default 0  not null comment '是否带组图 (0:否, 1:是)',
    uid         int unsigned        default 0  not null comment '用户ID',
    agree       int unsigned        default 0  not null comment '支持数',
    disagree    int unsigned        default 0  not null comment '反对数',
    list        int unsigned        default 0  not null comment '排序值',
    picurl      varchar(255)        default '' not null comment '封面图URL',
    content     text                           not null comment '评论内容',
    reply_count mediumint unsigned  default 0  not null comment '回复数',
    phone_type  varchar(30)         default '' not null comment '发表来自什么手机',
    status      tinyint(1) unsigned default 1  not null comment '状态：1审核通过，0未审核',
    create_time int unsigned        default 0  not null comment '创建时间戳'
)
    comment '回复内容表';

create index idx_aid
    on ha_article_reply (aid);

create index idx_pid
    on ha_article_reply (pid);

create index idx_sort_order
    on ha_article_reply (list);

create index idx_status_create_time
    on ha_article_reply (status, create_time);

create index idx_uid
    on ha_article_reply (uid);

create table ha_article_tag
(
    id              int unsigned auto_increment
        primary key,
    title           varchar(256)       default '' not null comment '标题',
    view            mediumint unsigned default 0  not null comment '浏览量',
    count           int                default 0  not null comment '内容总数',
    status          tinyint            default 1  not null comment '状态：0未审 1已审 2推荐',
    list            int unsigned       default 0  not null comment '排序值',
    uid             int                default 1  not null,
    image           text                          null comment '封面图',
    seo_description mediumtext                    null comment '文章内容',
    seo_title       varchar(128)                  null comment 'SEO标题',
    seo_keyword     varchar(128)                  null comment '关键词',
    create_time     int unsigned                  null,
    update_time     int unsigned                  null
)
    comment 'tag';

create index count
    on ha_article_tag (count);

create index create_time
    on ha_article_tag (create_time);

create index list
    on ha_article_tag (list);

create index status
    on ha_article_tag (status);

create index update_time
    on ha_article_tag (update_time);

create index view
    on ha_article_tag (view);

create table ha_article_tag_info
(
    id  int auto_increment
        primary key,
    aid bigint(22)    null comment '内容id',
    tid int           null comment 'tag id',
    mid int default 0 not null comment '模型id'
)
    comment 'tag关联信息';

create index astro_tag_info_aid_index
    on ha_article_tag_info (aid);

create index astro_tag_info_tid_index
    on ha_article_tag_info (tid);

create index mid
    on ha_article_tag_info (mid);
INSERT INTO ha_article_model (id, title, info, list, is_del)
VALUES (-1, '栏目', '栏目模型，内置字段不可修改，仅支持扩展字段', -100, 0);
INSERT INTO ha_article_model (id, title, info, list, is_del)
VALUES (1, '文章模型', '', 100, 1);
INSERT INTO ha_article_field (id, mid, name, type, title, options, help, required, list, edit, extend, status,
                              create_time, update_time, is_search)
VALUES (1, 1, 'title', 'text', '标题', '', null, 1, 1000, 1,
        '{"field":{"type":"VARCHAR","length":"256","unsigned":"0","null":"0","default":"\'\'"},"search":{"is_open":"1","linq":"like"},"tables":{"is_show":"1","templet":"","switch":{"name":""},"edit":"0"},"add":{"is_show":"1"}}',
        1, 1735613014, 1735613014, null);
INSERT INTO ha_article_field (id, mid, name, type, title, options, help, required, list, edit, extend, status,
                              create_time, update_time, is_search)
VALUES (2, 1, 'keywords', 'text', '关键词', '', null, 0, 100, 1,
        '{"field":{"type":"VARCHAR","length":"2000","unsigned":"0","null":"0","default":"\'\'"},"search":{"is_open":"0","linq":""},"tables":{"is_show":"0","templet":"","switch":{"name":""},"edit":"0"},"add":{"is_show":"0"}}',
        1, 1735613014, 1735613014, null);
INSERT INTO ha_article_field (id, mid, name, type, title, options, help, required, list, edit, extend, status,
                              create_time, update_time, is_search)
VALUES (3, 1, 'description', 'textarea', '简介', '', null, 0, 100, 1,
        '{"field":{"type":"TEXT","length":"","unsigned":"0","null":"0","default":""},"search":{"is_open":"0","linq":""},"tables":{"is_show":"0","templet":"","switch":{"name":""},"edit":"0"},"add":{"is_show":"1"}}',
        1, 1735613014, 1735613014, null);
INSERT INTO ha_article_field (id, mid, name, type, title, options, help, required, list, edit, extend, status,
                              create_time, update_time, is_search)
VALUES (4, 1, 'content', 'editor', '内容', '', null, 1, 1, 1,
        '{"field":{"type":"LONGTEXT","length":"","unsigned":"0","null":"0","default":""},"search":{"is_open":"0","linq":""},"tables":{"is_show":"0","templet":"","switch":{"name":""},"edit":"0"},"add":{"is_show":"1"}}',
        1, 1735613014, 1735613014, null);
INSERT INTO ha_article_field (id, mid, name, type, title, options, help, required, list, edit, extend, status,
                              create_time, update_time, is_search)
VALUES (5, 1, 'images', 'images', '组图', '', null, 0, 80, 1,
        '{"field":{"type":"TEXT","length":"","unsigned":"0","null":"0","default":""},"search":{"is_open":"0","linq":""},"tables":{"is_show":"1","templet":"image","switch":{"name":""},"edit":"0"},"add":{"is_show":"1"}}',
        1, 1735613014, 1735662989, null);

create table if not exists ha_system_jobs
(
    id             int(11) unsigned auto_increment primary key,
    queue          varchar(255)        null,
    payload        longtext            null,
    attempts       tinyint(4) unsigned null,
    reserve_time   int(11) unsigned    null,
    available_time int(11) unsigned    null,
    create_time    int(11) unsigned    null
);

create index queue on ha_system_jobs (queue);

create table if not exists ha_system_jobs_failed
(
    id         int(11) unsigned auto_increment primary key,
    connection text                                null,
    queue      text                                null,
    payload    longtext                            null,
    exception  longtext                            null,
    fail_time  timestamp default CURRENT_TIMESTAMP null
);

create table if not exists ha_system_tasks
(
    id          int auto_increment primary key,
    title       char(50)                      null comment '任务名称',
    exptime     char(200) default '* * * * *' not null comment '任务周期',
    task        text                          null comment '任务命令',
    data        longtext                      null comment '附加参数',
    list        int       default 0           not null comment '排序',
    count       int       default 0           not null comment '执行次数',
    last_time   datetime                      null comment '最后执行时间',
    next_time   datetime                      null comment '下次执行时间',
    status      int(1)    default 1           not null comment '任务状态',
    output_msg  text                          null,
    create_time int                           null comment '创建时间',
    update_time int                           null comment '更新时间'
);

create index ha_system_tasks_list_index on ha_system_tasks (list);

create index ha_system_tasks_next_time_index on ha_system_tasks (next_time);

create index ha_system_tasks_status_index on ha_system_tasks (status);

create table if not exists ha_system_tasks_log
(
    id            int auto_increment primary key,
    task_name     varchar(255)  not null,
    task_id       int default 0 null,
    status        int default 1 not null,
    output        text          null,
    error_message text          null,
    create_time   int default 0 null,
    update_time   int default 0 null
);

create index ha_system_tasks_log_status_index on ha_system_tasks_log (status);

create index ha_system_tasks_log_task_id_index on ha_system_tasks_log (task_id);

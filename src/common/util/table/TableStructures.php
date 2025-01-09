<?php
declare (strict_types=1);

namespace app\common\util\table;

class TableStructures
{
    /**
     * 获取默认数据结构
     * @return array[]
     */
    public static function getTypeFields(): array
    {
        return [
            //基础表
            1 => [
                'id' => ['type' => 'BIGINT', 'length' => 20, 'unsigned' => true, 'auto_increment' => true, 'null' => false, 'default' => null, 'comment' => '主键ID',],
            ],
            //常用表
            2 => [
                'id' => ['type' => 'BIGINT', 'length' => 20, 'unsigned' => true, 'auto_increment' => true, 'null' => false, 'default' => null, 'comment' => '主键ID',],
                'list' => ['type' => 'INT', 'length' => 11, 'unsigned' => false, 'default' => 100, 'comment' => 'list排序'],
                'status' => ['type' => 'TINYINT', 'length' => 11, 'unsigned' => false, 'default' => 1, 'comment' => '状态'],
                'create_time' => ['type' => 'INT', 'length' => 11, 'unsigned' => true, 'default' => 0, 'comment' => '创建时间'],
                'update_time' => ['type' => 'INT', 'length' => 11, 'unsigned' => true, 'default' => 0, 'comment' => '更新时间'],
            ],
            //内容表
            3 => [
                'id' => ['type' => 'BIGINT', 'length' => 22, 'unsigned' => true, 'auto_increment' => true, 'null' => false, 'default' => null, 'comment' => '主键ID',],
                'mid' => ['type' => 'SMALLINT', 'length' => 4, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '模型ID',],
                'cid' => ['type' => 'MEDIUMINT', 'length' => 6, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '栏目ID',],
                'title' => ['type' => 'VARCHAR', 'length' => 256, 'null' => false, 'default' => '', 'comment' => '标题',],
                'is_pic' => ['type' => 'TINYINT', 'length' => 1, 'null' => false, 'default' => 0, 'comment' => '是否带组图',],
                'uid' => ['type' => 'INT', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '用户ID',],
                'view' => ['type' => 'INT', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '浏览量',],
                'status' => ['type' => 'TINYINT', 'length' => 1, 'null' => false, 'default' => 1, 'comment' => '状态：0未审 1已审 2推荐',],
                'replynum' => ['type' => 'INT', 'length' => 11, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '评论数',],
                'description' => ['type' => 'TEXT', 'null' => true, 'comment' => '简介',],
                'list' => ['type' => 'INT', 'length' => 10, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '排序值',],
                'images' => ['type' => 'TEXT', 'null' => true, 'comment' => '封面图',],
                'keywords' => ['type' => 'VARCHAR', 'length' => 500, 'null' => false, 'default' => '', 'comment' => '关键词',],
                'extend' => ['type' => 'TEXT', 'null' => true, 'comment' => '扩展字段',],
                'create_time' => ['type' => 'INT', 'length' => 10, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '创建时间',],
                'update_time' => ['type' => 'INT', 'length' => 10, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '修改时间',],
                'delete_time' => ['type' => 'INT', 'length' => 10, 'unsigned' => true, 'null' => false, 'default' => 0, 'comment' => '软删除',],
            ],
            //内容表
            4 => [
                'id' => ['type' => 'BIGINT', 'length' => 22, 'unsigned' => true, 'auto_increment' => true, 'null' => false, 'default' => null, 'comment' => '主键ID',],
                'content' => ['type' => 'LONGTEXT', 'null' => true, 'comment' => '内容',],
            ],
        ];
    }
}

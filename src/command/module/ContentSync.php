<?php

namespace app\command\module;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\db\exception\DbException;
use think\facade\Db;

class ContentSync extends Command
{
    /**
     * @var mixed|Argument
     */
    protected mixed $module;

    /**
     * 配置消息指令
     */
    protected function configure(): void
    {
        $this->setName('module:content_sync');
        $this->addArgument('module', Argument::OPTIONAL, '模块标记', 'article');
        $this->setHelp('模块内容同步,清理主表附表数据差异');
        $this->setDescription('模块内容同步,清理主表附表数据差异');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->module = $input->getArgument('module');
        $output->info('正在为您同步模块内容...');
        try {
            $this->ContentToContents();
            $this->ContentsToContent();
        } catch (DbException $e) {
            $output->error($e->getMessage());
        }

    }

    /**
     * 以主表内容为索引 清理附表
     * @return void
     * @throws DbException
     */
    private function ContentToContents(): void
    {
        $content_table = $this->module . '_content';
        $data = Db::name($content_table)->field('id,mid,title')->cursor();
        foreach ($data as $item) {
            $table = "{$content_table}_{$item['mid']}";
            $tables = "{$content_table}_{$item['mid']}s";
            $this->queryAndClear($item['id'], $table, [$tables, $content_table]);
            $this->queryAndClear($item['id'], $tables, [$table, $content_table]);
        }
    }

    /**
     * 以附表内容为索引清理主表
     * @return void
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function ContentsToContent(): void
    {
        $model_table = $this->module . '_model';
        $models = Db::name($model_table)->where('id', '>', 0)->select();
        foreach ($models as $model) {
            $content_mid_table = $this->module . '_content_' . $model['id'];
            $content_table = $this->module . '_content';
            $contents_table = $content_mid_table . 's';
            $data = Db::name($content_mid_table)->field('id')->cursor();
            foreach ($data as $item) {
                $this->queryAndClear($item['id'], $contents_table, [$content_table, $content_mid_table]);
                $this->queryAndClear($item['id'], $content_table, [$contents_table, $content_mid_table]);
            }
            $data = Db::name($contents_table)->field('id')->cursor();
            foreach ($data as $item) {
                $this->queryAndClear($item['id'], $content_mid_table, [$content_table, $contents_table]);
                $this->queryAndClear($item['id'], $content_table, [$content_table, $content_mid_table]);
            }

        }
    }

    /**
     * 查询并清理
     * @param int|string $id
     * @param string $table
     * @param string|array $tables
     * @return void
     * @throws DbException
     */
    private function queryAndClear(int|string $id, string $table, string|array $tables): void
    {
        $contents = Db::name($table)->findOrEmpty($id);
        if (empty($contents)) {
            if (is_array($tables)) {
                foreach ($tables as $table) {
                    Db::name($table)->where('id', $id)->delete();
                }
            } else {
                Db::name($tables)->where('id', $id)->delete();
            }
            $clear_table = implode($tables);
            $this->output->info("清理成功:{$id},{$table}无数据,已清理关联数据表{$clear_table}");
        }
    }
}

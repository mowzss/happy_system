<?php

namespace app\command\module;

use Overtrue\Pinyin\Pinyin;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class TagSpy extends Command
{
    /**
     * 配置消息指令
     */
    protected function configure(): void
    {
        $this->setName('module:tag-spy');
        $this->addArgument('module', Argument::REQUIRED, '模块名称，如 article');
        $this->addOption('force', 'f', Option::VALUE_NONE, '强制更新所有记录，包括已有tag_spy值的记录');
        $this->addOption('batch-size', 'b', Option::VALUE_REQUIRED, '每批处理的记录数量，默认100', 100);
        $this->setDescription('批量更新模块表中记录的tag首字母拼音缩写');
        $this->setHelp(
            '此命令用于批量更新指定模块表中记录的tag_spy字段（标题首字母拼音缩写）' . PHP_EOL .
            PHP_EOL .
            '<info>用法:</info>' . PHP_EOL .
            '  php think module:tag-spy <module> [options]' . PHP_EOL .
            PHP_EOL .
            '<info>参数:</info>' . PHP_EOL .
            '  module              模块名称，如 article, news, product 等' . PHP_EOL .
            PHP_EOL .
            '<info>选项:</info>' . PHP_EOL .
            '  -f, --force         强制更新所有记录，包括已有tag_spy值的记录' . PHP_EOL .
            '  -b, --batch-size=N  每批处理的记录数量，默认100' . PHP_EOL .
            PHP_EOL .
            '<info>示例:</info>' . PHP_EOL .
            '  php think module:tag-spy article                           # 更新article模块空的tag_spy字段' . PHP_EOL .
            '  php think module:tag-spy article --force                   # 强制更新article模块所有记录' . PHP_EOL .
            '  php think module:tag-spy product --batch-size=50           # 更新product模块，每批处理50条'
        );
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        $module = $input->getArgument('module');
        $force = $input->getOption('force');
        $batchSize = (int)$input->getOption('batch-size');

        if (empty($module)) {
            $output->error('请输入模块名称');
            return 1;
        }

        return $this->setUpdate($module, $output, $force);
    }

    /**
     * @param mixed $module
     * @param Output $output
     * @param mixed $force
     * @return int
     */
    private function setUpdate(mixed $module, Output $output, mixed $force): int
    {
        $table_name = $module . '_tag';

        // 检查表是否存在
        if (table_exists($table_name)) {
            $output->error($module . '模块表不存在');
            return 1;
        }

        try {
            $db = $this->app->db->name($table_name);

            // 先统计需要更新的记录数
            $countQuery = clone $db;
            $countQuery->field('id');
            if (!$force) {
                $countQuery->whereOr('spy', '')->whereOr('spy', null);
            }
            $totalCount = $countQuery->count();

            if ($totalCount === 0) {
                $output->info('没有需要更新的记录');
                return 0;
            }

            $output->info("总共找到 {$totalCount} 条需要更新的记录");

            // 构建查询条件
            $query = $db->field('id,title,spy');
            if (!$force) {
                $query->whereOr('spy', '')->whereOr('spy', null);
            }
            $updatedCount = 0;
            // 使用游标遍历，避免内存溢出
            foreach ($query->cursor() as $item) {
                $this->app->db->transaction(function () use ($output, &$updatedCount, $totalCount, $table_name, $item) {
                    $firstChar = mb_substr($item['title'], 0, 1, 'UTF-8');
                    $spy = Pinyin::abbr($firstChar);
                    $this->app->db->name($table_name)->update([
                        'id' => $item['id'], 'spy' => (string)$spy, 'title' => $item['title']
                    ]);
                    $updatedCount++;
                    $progress = round(($updatedCount / $totalCount) * 100, 2);
                    $output->info("已处理: {$updatedCount}/{$totalCount} ({$progress}%)");
                });

            }

            $output->info("模块 {$module} 的 tag_spy 更新完成，共更新 {$updatedCount} 条记录");
            return 0;

        } catch (\Exception $e) {
            $output->error('更新过程中发生错误: ' . $e->getMessage());
            return 1;
        }
    }
}

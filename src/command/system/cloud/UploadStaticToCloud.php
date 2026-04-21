<?php

namespace app\command\system\cloud;


use SplFileInfo;
use think\console\Input;
use think\console\Output;
use think\console\Command;
use think\facade\Filesystem;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use think\console\input\Option;
use app\model\system\SystemConfig;

class UploadStaticToCloud extends Command
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('cloud:upload-static')
            ->setDescription('使用 think-filesystem 上传静态文件到云存储（OSS/COS/本地等）')
            ->addOption('only-update-version', null, Option::VALUE_OPTIONAL, '仅更新静态资源版本号，不执行文件上传');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int
     * @throws \League\Flysystem\FilesystemException
     */
    protected function execute(Input $input, Output $output): int
    {
        // 检查是否设置了只更新版本号的选项
        $onlyUpdateVersion = $input->hasOption('only-update-version') && $input->getOption('only-update-version');

        if ($onlyUpdateVersion) {
            $output->info('🔄 仅执行版本号更新操作...');
            $this->update_static_version($output);
            $output->info('✅ 版本号更新完成！');
            return 0;
        }

        $localPath = public_path() . sys_config('static_local_path');
        $disk = sys_config('static_upload', 'local');
        $prefix = sys_config('static_prefix');

        if (!is_dir($localPath)) {
            $output->error("❌ 本地路径不存在: {$localPath}");
            return 1;
        }
        if ($disk === 'local') {
            $output->info('本地存储，无需上传');
            $this->update_static_version($output);
            return 0;
        }
        // 获取磁盘实例
        try {
            $fs = Filesystem::disk($disk);
        } catch (\Exception $e) {
            $output->error("❌ 无法加载磁盘 [{$disk}]，请检查 存储驱动配置信息");
            return 1;
        }

        // 扫描所有文件
        $files = $this->getAllFiles($localPath);
        if (empty($files)) {
            $output->warning("⚠️ 目录中无文件: {$localPath}");
            $this->update_static_version($output);
            return 0;
        }

        $output->info("📁 本地路径: {$localPath}");
        $output->info("☁️ 目标磁盘: {$disk}");
        $output->info("📂 远程前缀: " . ($prefix ?: '(根目录)'));
        $output->info("📤 共 " . count($files) . " 个文件，开始上传...");

        $success = 0;
        $fail = 0;

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($localPath) + 1));
            $remotePath = $prefix ? ($prefix . '/' . $relativePath) : $relativePath;

            try {
                // 使用 Flysystem 统一 API 上传
                $fs->writeStream($remotePath, fopen($file->getPathname(), 'r'));
                $output->writeln("<info>✓</info> {$remotePath}");
                $success++;
            } catch (\Exception $e) {
                $output->writeln("<error>✗ {$remotePath}</error> → " . $e->getMessage());
                $fail++;
            }
        }
        $output->newLine();
        $output->info("✅ 上传完成！成功: {$success} | 失败: {$fail}");
        $this->update_static_version($output);
        return $fail > 0 ? 1 : 0;
    }

    private function getAllFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file;
            }
        }
        return $files;
    }

    /**
     * 更新静态资源版本号
     * @param Output $output
     * @return void
     */
    private function update_static_version(Output $output): void
    {
        $systemConfig = new SystemConfig();
        if ($systemConfig->where('name', 'static_version')->update(['value' => date('y.md.isH')])) {
            $output->info("✅ 更新静态资源版本成功！");
        }
    }
}

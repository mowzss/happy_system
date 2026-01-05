<?php

namespace app\command\system\cloud;


use app\model\system\SystemConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Filesystem;

class UploadStaticToCloud extends Command
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('cloud:upload-static')
            ->setDescription('使用 think-filesystem 上传静态文件到云存储（OSS/COS/本地等）');
    }

    protected function execute(Input $input, Output $output)
    {
        $localPath = public_path() . sys_config('static_local_path');
        $disk = sys_config('static_upload', 'local');
        $prefix = sys_config('static_prefix');

        if (!is_dir($localPath)) {
            $output->error("❌ 本地路径不存在: {$localPath}");
            return 1;
        }
        if ($disk == 'local') {
            $output->info('本地存储，无需上传');
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
        $systemConfig = new SystemConfig();
        if ($systemConfig->where('name', 'static_version')->update(['value' => date('y.md.isH')])) {
            $output->info("✅ 更新静态资源版本成功！");
        }
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
}

<?php
declare (strict_types=1);

namespace app\model\system;

use think\Model;

class SystemAttachment extends Model
{
    /**
     * 删除具有相同uid和md5哈希值的冗余记录，只保留每组中最旧的一条记录。
     *
     * @return int 返回删除的记录数
     */
    public static function removeDuplicateUidMd5Records(): int
    {
        // 获取所有重复的uid+md5组合
        $duplicateCombinations = self::field('uid, md5')
            ->group('uid, md5')
            ->having('COUNT(*) > 1')
            ->select()
            ->toArray();

        if (empty($duplicateCombinations)) {
            return 0; // 没有重复的uid+md5组合
        }

        // 保留每组中最旧的一条记录（最小id），并删除其他记录
        $deleteCount = 0;
        foreach ($duplicateCombinations as $combination) {
            $uid = $combination['uid'];
            $md5 = $combination['md5'];

            // 构建子查询以获取需要保留的记录ID
            $subQuery = self::where(['uid' => $uid, 'md5' => $md5])
                ->order('id', 'asc') // 保留最早的记录
                ->limit(1)
                ->column('id');

            // 删除除了上述子查询结果外的所有记录
            $deleteCount += self::where(['uid' => $uid, 'md5' => $md5])
                ->whereNotIn('id', $subQuery)
                ->delete();
        }

        return $deleteCount;
    }
}

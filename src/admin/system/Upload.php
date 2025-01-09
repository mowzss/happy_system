<?php
declare (strict_types=1);

namespace app\admin\system;

use app\common\controllers\BaseAdmin;
use app\common\traits\UploadTraits;

/**
 * 上传系统
 */
class Upload extends BaseAdmin
{
    use UploadTraits;
}

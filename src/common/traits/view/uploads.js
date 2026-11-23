// 上传组件（模板层）：仅注入后端动态配置，逻辑由静态模块 uploads-core 实现。
// 由 UploadTraits::index() 渲染输出；保持 exports('uploads', Uploads) 接口不变。
layui.define(['jquery', 'uploadsCore'], function (exports) {
    "use strict";

    var $ = layui.jquery;
    var Uploads = layui.uploadsCore;

    // 直传配置（由后端注入，local 驱动时 enabled=false 走服务器中转）
    var DIRECT_CONFIG = JSON.parse('{$direct_config|raw}');

    // 注入动态默认配置（覆盖 core 中的静态占位值）
    $.extend(true, Uploads.prototype.defaults, {
        severUrl: "{:urls('save')}",
        signUrl: DIRECT_CONFIG.signUrl,
        registerUrl: DIRECT_CONFIG.registerUrl,
        direct: DIRECT_CONFIG.enabled,
        directMaxSize: DIRECT_CONFIG.maxSize,
        exts: JSON.parse('{$exts|raw|json_encode}').join('|'),
        mimes: JSON.parse('{$mimes|raw|json_encode}')
    });

    exports('uploads', Uploads);
});

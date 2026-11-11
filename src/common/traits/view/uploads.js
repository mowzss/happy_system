layui.define(['jquery', 'upload', 'layer', 'element', 'sortable'], function (exports) {
    var upload = layui.upload,
        sortable = layui.sortable,
        layer = layui.layer,
        $ = layui.$;

    // 直传配置（由后端注入，local 驱动时 enabled=false 走服务器中转）
    var DIRECT_CONFIG = JSON.parse('{$direct_config|raw}');

    // 上传组件构造函数
    function Uploads(options, callback) {
        this.config = $.extend(true, {}, this.defaults, options);
        this.elem = options.elem;
        this.callback = callback;
        this.init();
    }


    // 默认配置
    Uploads.prototype.defaults = {
        severUrl: "{:urls('save')}", // 服务器中转上传接口
        signUrl: DIRECT_CONFIG.signUrl, // 直传签名接口
        registerUrl: DIRECT_CONFIG.registerUrl, // 直传登记接口
        direct: DIRECT_CONFIG.enabled, // 是否启用浏览器直传
        directMaxSize: DIRECT_CONFIG.maxSize,
        multiple: false, // 是否允许多文件上传
        exts: JSON.parse('{$exts|raw|json_encode}').join('|'), // 允许的文件扩展名
        mimes: JSON.parse('{$mimes|raw|json_encode}'),
        sort: false, // 是否启用排序功能
        type: 'images', // 接受的文件类型: images, file, video, audio
        extra: false // 是否包含扩展信息
    };

    // 初始化方法
    Uploads.prototype.init = function () {
        this.render();
    };

    // 渲染方法
    Uploads.prototype.render = function () {
        this.config.field = $(this.elem).find('input').attr('name');
        //渲染上传容器
        this.initView()
        // 初始化上传组件（第三方存储优先走浏览器直传）
        this.initUploader();

        // 如果是多文件上传且启用了排序功能，则初始化排序
        if (this.config.multiple && this.config.sort) {
            this.initSortable();
        }

        // 根据文件类型渲染初始预览
        this.renderPreviews();

        // 监听删除事件（只绑定一次）
        $(this.elem).on('click', '.layui-images-show-item span.del, .layui-files-show-item span.del', (function (that) {
            return function (event) {
                that.deleteItem(event.currentTarget);
            };
        })(this));

        // 监听修改文件名事件
        $(this.elem).on('click', '.layui-files-show-item span.edit-name', (function (that) {
            return function (event) {
                that.editFileName(event.currentTarget);
            };
        })(this));
        this.preview();
    };
    //预览
    Uploads.prototype.preview = function () {
        var that = this;
        $(this.elem).on('click', '[data-preview]', (function () {
                var imageSrcs = [];
                $(that.elem).find('.layui-images-show-list img').each(function () {
                    var fullUrl = $(this).attr('src');
                    imageSrcs.push(fullUrl);
                });
                layer.photos({
                    photos: {
                        "data": imageSrcs.map(function (src, index) {
                            return {
                                "alt": "Image " + (index + 1),
                                "pid": index + 1, // 图片ID
                                "src": src,
                                "thumb": src // 缩略图，这里我们用原图代替
                            };
                        })
                    },
                    anim: 5 // 初始动画类型
                });

            })
        );
    }

    // 初始化上传组件（第三方存储优先走浏览器直传）
    Uploads.prototype.initUploader = function () {
        var that = this;
        if (!that.config.direct) {
            that.initUpload();
            return;
        }
        $.ajax({ url: that.config.signUrl, type: 'POST', dataType: 'json' })
            .done(function (res) {
                if (res && res.code === 0 && res.data && res.data.mode === 'direct') {
                    that.directInfo = res.data;
                    that.initDirect();
                } else {
                    that.initUpload();
                }
            })
            .fail(function () {
                that.initUpload();
            });
    };

    // 绑定直传文件选择
    Uploads.prototype.initDirect = function () {
        var that = this;
        $(that.elem).off('click.directUpload').on('click.directUpload', '[data-upload]', function (event) {
            event.preventDefault();
            that.getFileInput().click();
        });
        that.getFileInput();
    };

    // 获取（并复用）隐藏的文件选择框
    Uploads.prototype.getFileInput = function () {
        var that = this;
        if (that.$fileInput) return that.$fileInput;

        var accept = '';
        if (that.config.type === 'images') {
            accept = 'image/*';
        } else if (that.config.exts) {
            accept = '.' + String(that.config.exts).split('|').join(',.');
        }

        var $input = $('<input type="file" style="display:none">');
        if (that.config.multiple) {
            $input.attr('multiple', 'multiple');
        }
        if (accept) {
            $input.attr('accept', accept);
        }
        $input.on('change', function () {
            var files = Array.prototype.slice.call(this.files || []);
            this.value = '';
            files.forEach(function (file) {
                that.directUpload(file);
            });
        });

        that.$fileInput = $input.appendTo(that.elem);
        return $input;
    };

    // 浏览器直传单个文件
    Uploads.prototype.directUpload = function (file) {
        var that = this, info = that.directInfo;
        var ext = file.name.indexOf('.') > -1 ? file.name.split('.').pop().toLowerCase() : '';

        if (info.allowExts && info.allowExts.length && info.allowExts.indexOf(ext) === -1) {
            layer.msg('不允许的文件类型');
            return;
        }
        if (info.maxSize && file.size > info.maxSize) {
            layer.msg('文件大小超过限制');
            return;
        }

        var key = info.dir + that.randomName() + (ext ? '.' + ext : '');
        var formData = new FormData();
        if (info.driver === 'oss') {
            formData.append('key', key);
            formData.append('policy', info.policy);
            formData.append('OSSAccessKeyId', info.accessId);
            formData.append('signature', info.signature);
            formData.append('success_action_status', String(info.successActionStatus || 200));
        } else {
            formData.append('token', info.token);
            formData.append('key', key);
        }
        // 文件字段必须最后追加；文件名仅用于 multipart，OSS 对象名由 key 决定。
        // 避免中文/空格等特殊字符导致 OSS 无法识别文件 part（IncorrectNumberOfFilesInPOSTRequest）
        var safeName = 'upload_' + that.randomName() + (ext ? '.' + ext : '');
        formData.append('file', file, safeName);

        var xhr = new XMLHttpRequest();
        layer.load();
        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                that.registerDirect(key, file, ext);
            } else {
                layer.closeAll('loading');
                that.relayUpload(file);
            }
        };
        xhr.onerror = function () {
            layer.closeAll('loading');
            that.relayUpload(file);
        };
        xhr.open('POST', info.host, true);
        xhr.send(formData);
    };

    // 直传完成后登记附件
    Uploads.prototype.registerDirect = function (key, file, ext) {
        var that = this;
        $.ajax({
            url: that.directInfo.registerUrl,
            type: 'POST',
            dataType: 'json',
            data: { path: key, name: file.name, size: file.size, ext: ext }
        }).done(function (res) {
            layer.closeAll('loading');
            if (res && res.code === 0 && res.data) {
                that.onUploaded(res.data);
            } else {
                layer.msg((res && res.msg) || '上传失败');
            }
        }).fail(function () {
            layer.closeAll('loading');
            layer.msg('上传登记失败');
        });
    };

    // 直传失败时回退服务器中转（save 接口固定读取 file 字段）
    Uploads.prototype.relayUpload = function (file) {
        var that = this;
        var formData = new FormData();
        formData.append('file', file, file.name);

        var xhr = new XMLHttpRequest();
        layer.load();
        xhr.onload = function () {
            layer.closeAll('loading');
            var res = null;
            try {
                res = JSON.parse(xhr.responseText);
            } catch (e) {
                res = null;
            }
            if (res && res.code === 0 && res.data) {
                that.onUploaded(res.data);
            } else {
                layer.msg((res && res.msg) || '上传失败');
            }
        };
        xhr.onerror = function () {
            layer.closeAll('loading');
            layer.msg('上传失败');
        };
        xhr.open('POST', that.config.severUrl, true);
        xhr.send(formData);
    };

    // 上传成功后的统一处理（编辑器回调 + 界面回填）
    Uploads.prototype.onUploaded = function (data) {
        if (this.callback) {
            if (this.config.type === 'images') {
                this.callback(data.url); // 图片类型只传递 URL
            } else {
                this.callback(data.url, data.name); // 文件类型传递 URL 和文件名
            }
        }
        this.addItem(data);
    };

    // 随机文件名
    Uploads.prototype.randomName = function () {
        return Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    };

    // 初始化上传组件
    Uploads.prototype.initUpload = function () {
        var that = this;
        upload.render({
            elem: $(that.elem).find('[data-upload]'), // 选择文件按钮
            url: that.config.severUrl, // 上传接口
            multiple: that.config.multiple, // 是否允许多文件上传
            accept: that.config.type, // 接受的文件类型
            exts: that.config.exts, // 允许的文件后缀
            before: function (obj) { // 文件上传前
                layer.load(); // 显示加载动画
            },
            done: function (res, index, upload) { // 文件上传成功
                layer.closeAll('loading'); // 关闭加载动画
                if (res.code === 0) { // 假设返回code为0表示成功
                    that.onUploaded(res.data);
                } else {
                    layer.msg(res.msg || '上传失败');
                }
            },
            error: function () { // 文件上传失败
                layer.closeAll('loading');
                layer.msg('上传失败');
            }
        });
    };

    // 初始化排序
    Uploads.prototype.initSortable = function () {
        let that = this;
        new sortable($(that.elem).find('.layui-images-show-list, .layui-files-show-list')[0], {
            animation: 150,
            onEnd: function (evt) {
                that.updateOrder(evt.oldIndex, evt.newIndex);
            }
        });
    };

    // 渲染预览
    Uploads.prototype.renderPreviews = function () {
        // 如果是 wangEditor 模式，则不需要渲染初始预览
        if (this.callback) return;

        var Input = $(this.elem).find('input[name="' + this.config.field + '"]');
        var items = Input.val() ? Input.val().split(',') : [];

        if (items.length > 0) {
            items.forEach(function (item) {
                var parts = item.split('|'); // 假设存储格式为 "url" 或 "url|name|size"
                var url = parts[0];
                var name = parts[1] || '';
                var size = parseFloat(parts[2]) || 0; // 确保 size 是数字

                if (this.config.type === 'images') {
                    this.addItem({
                        url: url
                    }); // 图片类型只传递 url
                } else {
                    this.addItem({
                        url: url, name: name, size: size
                    }); // 文件类型传递所有信息
                }
            }, this);
        }
    };

    // 添加项（图片或文件）
    Uploads.prototype.addItem = function (data) {
        // 如果已经通过 callback 返回给 wangEditor，则不再执行原有的逻辑
        if (this.callback) return;

        var template = this.getTemplate(this.config.type, data.url, data.name, data.size);
        var containerSelector = this.config.type === 'images' ? '.layui-images-show-list' : '.layui-files-show-list';
        if (this.config.multiple === true) {
            $(this.elem).find(containerSelector).append(template);
        } else {
            $(this.elem).find(containerSelector).html(template);
        }
        // 更新隐藏域的值
        this.updateHiddenField(data);
    };
    //渲染容器
    Uploads.prototype.initView = function () {
        let that = this, viewHtml;
        if (that.config.type === 'images') {
            if (that.config.multiple === true) {
                viewHtml = ' <div class="layui-images-show">\n' +
                    '<div class="layui-images-show-list">\n' +
                    '</div>\n' +
                    '<div class="layui-images-show-item" data-upload>\n' +
                    '<em class="layui-icon layui-icon-upload"></em>\n' +
                    '</div>\n' +
                    '</div>'
            } else {
                viewHtml = '<div class="layui-images-show">\n' +
                    '<div class="layui-images-show-list">\n' +
                    '<div class="layui-images-show-item" data-upload>\n' +
                    '<em class="layui-icon layui-icon-upload"></em>\n' +
                    '</div>\n' +
                    '</div>\n' +
                    '</div>'
            }
        } else {
            viewHtml = '<a class="layui-btn layui-btn-primary" data-upload>' +
                '<em class="layui-icon layui-icon-upload"></em>上传文件</a>\n' +
                '<div class="layui-files-show">\n' +
                '<div class="layui-files-show-list">\n' +
                '</div>\n' +
                '</div>'
        }
        $(that.elem).append(viewHtml);
    }
    // 获取模板
    Uploads.prototype.getTemplate = function (type, url, name, size) {
        var that = this;
        switch (type) {
            case 'images':
                if (that.config.multiple === true) {
                    return '<div class="layui-images-show-item sortable-item" data-files-url="' + url + '">' +
                        '<span class="drag"><em class="iconfont icon-drag"></em></span>' +
                        '<span class="del"><em class="layui-icon layui-icon-close"></em></span>' +
                        '<img src="' + url + '" alt="">' +
                        '<div class="layui-images-show-item-but">' +
                        '<span class="layui-icon layui-icon-eye" title="预览" data-preview></span>' +
                        '</div>' +
                        '</div>';
                } else {
                    return '<div class="layui-images-show-item sortable-item" data-files-url="' + url + '">' +
                        '<span class="del"><em class="layui-icon layui-icon-close"></em></span>' +
                        '<img src="' + url + '" alt="">' +
                        '<div class="layui-images-show-item-but">' +
                        '<span class="layui-icon layui-icon-eye" title="预览" data-preview></span>' +
                        '</div>' +
                        '</div>';
                }
            default:
                var formattedSize = this.formatSize(size);
                return '<div class="layui-files-show-item sortable-item" data-files-url="' + url + '">' +
                    '<span class="drag"><em class="iconfont icon-drag"></em></span>' +
                    '<a class="files" href="' + url + '" target="_blank"><em class="layui-icon layui-icon-file"></em>' + name + '</a>' +
                    '<span class="file-size">(' + formattedSize + ')</span>' +
                    '<span class="edit-name"><em class="layui-icon layui-icon-edit" title="修改文件名"></em></span>' + // 添加修改按钮
                    '<span class="del"><em class="layui-icon layui-icon-close"></em></span>' +
                    '</div>';
        }
    };

    // 格式化文件大小
    Uploads.prototype.formatSize = function (bytes) {
        if (isNaN(bytes) || bytes === 0) return '0 B';
        var k = 1024,
            sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    // 更新隐藏域
    Uploads.prototype.updateHiddenField = function (data) {
        var Input = $(this.elem).find('input[name="' + this.config.field + '"]');
        var items = Input.val() ? Input.val().split(',') : [];
        var newItem;

        if (this.config.type === 'images') {
            newItem = [data.url].filter(Boolean).join('|'); // 图片类型只记录 url
        } else {
            newItem = [data.url, data.name, data.size].filter(Boolean).join('|'); // 文件类型记录 url, name, size
        }

        // 去重，避免重复添加相同的项目
        if (!items.includes(newItem)) {
            if (this.config.multiple) {
                items.push(newItem);
            } else {
                items = [newItem];
            }
        }

        Input.val(items.join(','));
    };

    // 删除项（图片或文件）
    Uploads.prototype.deleteItem = function (elem) {
        var $itemContainer = $(elem).parent(); // 获取容器
        var url = $itemContainer.data('files-url'); // 获取文件的 URL

        // 更新隐藏域的值
        this.removeHiddenField(url);

        if (this.config.type === 'images' && this.config.multiple === false) {
            var html = '<div class="layui-images-show-item" data-upload>\n' +
                '<em class="layui-icon layui-icon-upload"></em>\n' +
                '</div>';
            var $append = $itemContainer.parent();
            $itemContainer.remove();
            $append.append(html)
            this.initUploader()
        } else {
            // 移除整个子节点
            $itemContainer.remove();
        }

    };

    // 从隐藏域移除
    Uploads.prototype.removeHiddenField = function (url) {
        var Input = $(this.elem).find('input[name="' + this.config.field + '"]');
        var items = Input.val() ? Input.val().split(',') : [];

        // 过滤掉与被删除项目关联的项目，现在使用 URL 作为唯一标识符
        items = items.filter(function (item) {
            var parts = item.split('|');
            var itemUrl = parts[0]; // 使用 URL 作为唯一标识符
            return itemUrl !== url;
        });

        // 在单文件模式下，如果所有的项目都已被删除，则清空隐藏域
        if (!this.config.multiple) {
            Input.val('');
        } else {
            Input.val(items.join(','));
        }
    };

    // 修改文件名的方法
    Uploads.prototype.editFileName = function (elem) {
        var that = this;
        var $itemContainer = $(elem).parent(); // 获取容器
        var url = $itemContainer.data('files-url'); // 获取文件的 URL
        var $fileNameElement = $itemContainer.find('.files'); // 获取文件名元素
        var oldName = $fileNameElement.text().trim(); // 获取当前文件名

        // 弹出输入框，允许用户输入新的文件名
        layer.prompt({
            formType: 2,
            title: '请输入新的文件名',
            value: oldName,
            area: ['300px', '150px']
        }, function (newName, index) {
            if (newName.trim() !== '') {
                // 更新界面上显示的文件名
                $fileNameElement.text(newName);

                // 更新隐藏字段中的 name
                that.updateHiddenFieldName(url, newName);

                layer.close(index); // 关闭输入框
            } else {
                layer.msg('文件名不能为空');
            }
        });
    };

    // 更新隐藏字段中的文件名
    Uploads.prototype.updateHiddenFieldName = function (url, newName) {
        var Input = $(this.elem).find('input[name="' + this.config.field + '"]');
        var items = Input.val() ? Input.val().split(',') : [];

        // 找到对应的项目并更新 name
        items = items.map(function (item) {
            var parts = item.split('|');
            var itemUrl = parts[0]; // 使用 URL 作为唯一标识符
            if (itemUrl === url) {
                // 如果是文件类型，更新 name
                if (parts.length > 1) {
                    parts[1] = newName;
                }
            }
            return parts.filter(Boolean).join('|');
        });

        Input.val(items.join(','));
    };

    // 更新顺序
    Uploads.prototype.updateOrder = function (oldIndex, newIndex) {
        // 如果是 wangEditor 模式，则不需要处理排序
        if (this.callback) return;

        var Input = $(this.elem).find('input[name="' + this.config.field + '"]');
        var items = Input.val() ? Input.val().split(',') : [];
        var movedItem = items.splice(oldIndex, 1)[0];
        items.splice(newIndex, 0, movedItem);
        Input.val(items.join(','));
    };

    // 输出
    exports('uploads', Uploads);
});

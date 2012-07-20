

jQuery(function($) {
    pinion.$body
        .append('<script id="template-upload" type="text/html">{% $.each(o.files, function (index, file) { %}<tbody class="template-upload{%=(file.error&&\' ui-state-error\')||\'\'%}"><tr><td class="preview"></td><td class="name">{%=file.name%}</td><td class="size">{%=o.formatFileSize(file.size)%}</td>{% if (file.error) { %}<td class="error" colspan="2">Error: {%=pinion.backend.input.Fileuploader.fileUploadErrors[file.error]%}</td>{% } else if (o.files.valid && !index) { %}<td><div class="progress progress-success progress-striped active"><div class="bar" style="width:0%;"></div></div></td><td class="start">{% if (!o.options.autoUpload) { %}<button>Start</button>{% } %}</td>{% } else { %}<td colspan="2"></td>{% } %}<td class="cancel">{% if (!index) { %}<button>Cancel</button>{% } %}</td></tr></tbody>{% }); %}</script>')
        .append('<script id="template-download" type="text/html">{% $.each(o.files, function (index, file) { %}<tbody class="template-download{%=(file.error&&\' ui-state-error\')||\'\'%}"><tr>{% if (file.error) { %}<td></td><td class="name">{%=file.name%}</td><td class="size">{%=o.formatFileSize(file.size)%}</td><td class="error" colspan="2">Error: {%=pinion.backend.input.Fileuploader.fileUploadErrors[file.error]%}</td>{% } else { %}<td class="preview">{% if (file.thumbnail_url) { %}<a href="{%=file.url%}" title="{%=file.name%}" rel="gallery"><img src="{%=file.thumbnail_url%}"></a>{% } %}</td><td class="name"><a href="{%=file.url%}" title="{%=file.name%}" rel="{%=file.thumbnail_url&&\'gallery\'%}">{%=file.name%}</a></td><td class="size">{%=o.formatFileSize(file.size)%}</td><td colspan="2"></td>{% } %}<td class="delete"><button data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}">Delete</button><input type="checkbox" name="delete" value="1"></td></tr></tbody>{% }); %}</script>');
});

pinion.backend.input.Fileuploader = (function($) {
    
    var constr,
        $imagePreview,
        xOffset = 30,
        yOffset = 30,
        jsPath = pinion.php.modulesUrl+"/fileupload/backend/js/";
    
    constr = function() {
        var $span = $("<span>"+pinion.translate("add files")+" ...</span>"),
            $input = $("<input type='file' name='files[]' multiple />"),
            $label = $("<label class='fileinput-button'></label>")
                .append($span)
                .append($input),
            $submit = $("<button type='submit' class='start'>"+pinion.translate("start upload")+"</button>"),
            $reset = $("<button type='reset' class='cancel'>"+pinion.translate("cancel upload")+"</button>"),
            $delete = $("<button type='button' class='delete'>"+pinion.translate("delete selected")+"</button>"),
            $toggle = $("<input type='checkbox' class='toggle' />"),
            $progressbar = $("<div class='progress progress-success progress-striped active fade'><div class='bar' style='width:0%;'></div></div>"),
            $buttonbar = $("<div class='fileupload-buttonbar'></div>")
                .append($label)
                .append($submit)
                .append($reset)
                .append($delete)
                .append($toggle)
                .append($progressbar),
            $form = $("<form action='"+pinion.php.url+"' method='post' enctype='multipart/form-data'></form>")
                .append($buttonbar),
            $table = $("<table class='files'></table>"),
            $content = $("<div class='fileupload-content'></div>")
                .append($table);
            
            
        this.$element = $("<div class='pinion-backend-input-Fileuploader'></div>")
            .append($form)
            .append($content);
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.Fileuploader,
        defaultSettings: {
            files: null,
            temp: false
        },
        init: function() {
            var _this = this,
                options = {};
            
            _this.info.files = {};
            
            if(this.settings.files == "images") {
                options.acceptFileTypes = /(\.|\/)(gif|jpe?g|png)$/i;
            }
            
            pinion.require(jsPath+"all/all.js", function() {
                
                _this.$element
                    .fileupload($.extend({
                        dropZone: _this.$element,
                        //singleFileUploads: false,
                        autoUpload: true,
                        formData: {events: jQuery.toJSON([{
                                module: "fileupload",
                                event: "upload",
                                info: {
                                    temp: _this.settings.temp
                                }
                            }
                        ])},
                        done: function(event, data) {
                            var that = $(this).data('fileupload');
                            if(data.context) {
                                data.context.each(function(index) {
                                    var file = ($.isArray(data.result.result) &&
                                            data.result.result[index]) || {error: 'emptyResult'};
                                    if(file.error) {
                                        that._adjustMaxNumberOfFiles(1);
                                    }
                                    $(this).fadeOut(function () {
                                        that._renderDownload([file])
                                            .css('display', 'none')
                                            .replaceAll(this)
                                            .fadeIn(function () {
                                                // Fix for IE7 and lower:
                                                $(this).show();

                                                if(file.thumbnailBig_url !== undefined) {
                                                    // fire imageAdded event
                                                    _this.fire("imageAdded", {
                                                        id: file.id,
                                                        src: file.thumbnailBig_url
                                                    });
                                                }
                                                _this.fire("fileAdded", file);
                                                // add file to info object
                                                _this.info.files[file.name] = file;
                                                _this.setDirty();
                                            });
                                    });
                                });
                            } else {
                                var file = data.result.result;
                                that._renderDownload(file)
                                    .css('display', 'none')
                                    .appendTo($(this).find('.files'))
                                    .fadeIn(function () {
                                        // Fix for IE7 and lower:
                                        $(this).show();
                                        if(file.thumbnailBig_url !== undefined) {
                                            // fire imageAdded event
                                            _this.fire("imageAdded", {
                                                id: file.id,
                                                src: file.thumbnailBig_url
                                            });
                                        }
                                        _this.fire("fileAdded", file);
                                        // add file to info object
                                        _this.info.files[file.name] = file;
                                        _this.setDirty();
                                    });
                            }
                            pinion.ajaxCallback(data.result);
                        },
                        destroy: function(event, data) {
                            var that = $(this).data('fileupload'),
                                eventData = $.unserialize(data.url);

                            eventData = {
                                module: "fileupload",
                                event: "delete",
                                info: {
                                    file: eventData.file,
                                    dir: eventData.dir
                                }
                            };
                            // delete file from info object
                            if(_this.info.files[eventData.file] !== undefined) {
                                delete _this.info.files[eventData.file];
                            }
                            if(pinion.isEmpty(_this.info.files)) {
                                _this.setClean();
                            }

                            pinion.ajax(eventData, function() {
                                that._adjustMaxNumberOfFiles(1);
                                data.context.fadeOut(function () {
                                    $(this).remove();
                                });
                            });
                        }
                    }, options))
            });
            
            this.$element
                .on("mouseenter", "a img", function(event) {
                    var thumbBigSrc = this.src.split(".");
                    thumbBigSrc[thumbBigSrc.length - 2] += "Big";
                    thumbBigSrc = thumbBigSrc.join(".");
                    
                    $imagePreview = $("<p id='pinion-imagePreview'><img src='"+thumbBigSrc+"' alt='Image preview' /></p>")
                        .css("top",(event.pageY - xOffset) + "px")
			.css("left",(event.pageX + yOffset) + "px")
			.appendTo(pinion.$body)
                        .fadeIn("fast");
                })
                .on("mouseleave", "a img", function() {
                    $imagePreview.remove();
                })
                .on("mousemove", "a img", function(event) {
                    $imagePreview
                        .css("top",(event.pageY - xOffset) + "px")
                        .css("left",(event.pageX + yOffset) + "px");
                });	
                
            this.$warning = $("<div class='pinion-backend-warning'></div>").appendTo(this.$element);
        },
        validate: function() {
            var validators = this.settings.validators;
            if(validators.notEmpty) {
                var files = this.info.files,
                    hasFile = false;
                for(var i in files) {
                    hasFile = true;
                }
                if(!hasFile) {
                    return this.setInvalid("No file given");
                }
            }
            return this.setValid();
        }
    };
    
    return constr;
    
}(jQuery));


pinion.backend.input.SimpleImageUploader = (function($) {
    
    var constr,
        jsPath = pinion.php.modulesUrl+"/fileupload/backend/js/";
    
    constr = function(settings) {
        
        var text = settings.multiple ? "add files" : "add file",
            $span = $("<span>"+pinion.translate(text)+" ...</span>"),
            $input = $("<input type='file' name='files[]' multiple />"),
            $label = $("<label class='fileinput-button'></label>")
                .append($span)
                .append($input),
            $progressbar = $("<div class='progress progress-success progress-striped active fade'><div class='bar' style='width:0%;'></div></div>"),
            $buttonbar = $("<div class='fileupload-buttonbar'></div>")
                .append($label)
                .append($progressbar),
            $form = $("<form action='"+pinion.php.url+"' method='post' enctype='multipart/form-data'></form>")
                .append($buttonbar),
            $table = $("<table class='files'></table>"),
            $content = $("<div class='fileupload-content'></div>")
                .append($table);
            
            
        this.$element = $("<div class='pinion-backend-fileupload-SimpleImageUploader'></div>");
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        
        this.$fileUpload = $("<div class='pinion-fileupload'></div>")
            .append($form)
            .append($content)
            .appendTo(this.$inputWrapper);
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.SimpleImageUploader,
        defaultSettings: {
            multiple: false,
            temp: false
        },
        init: function() {
            var _this = this,
                options = {};
            
            // LABEL
            if(this.settings.label) {
                this.$label = $("<label class='pinion-label'>"+pinion.translate(this.settings.label)+"</label>").prependTo(this.$element);
            }    
            
            options.acceptFileTypes = /(\.|\/)(gif|jpe?g|png)$/i;
            
            if(!this.settings.multiple) {
                options.maxNumberOfFiles = 1;
            }
            
            pinion.require(jsPath+"all/all.js", function() {
                _this.$fileUpload
                    .fileupload($.extend({
                        dropZone: _this.$fileUpload,
                        autoUpload: true,
                        formData: {events: jQuery.toJSON([{
                                module: "fileupload",
                                event: "upload",
                                info: {
                                    temp: _this.settings.temp
                                }
                            }
                        ])},
                        done: function(event, data) {
                            var that = $(this).data('fileupload');
                            if (data.context) {
                                data.context.each(function(index) {
                                    var file = ($.isArray(data.result.result) &&
                                            data.result.result[index]) || {error: 'emptyResult'};
                                    if(file.error) {
                                        that._adjustMaxNumberOfFiles(1);
                                    }
                                    $(this).fadeOut(function () {
                                        var $tbody = that._renderDownload([file])
                                            .css('display', 'none')
                                            .replaceAll(this);

                                        if(!_this.settings.multiple) { 
                                            // set dirty
                                            _this.info[_this.settings.infoKey] = file.name;
                                            _this.setDirty();
                                            // Fix for IE7 and lower:
                                            var $clone = $tbody
                                                .show()
                                                .find("img")
                                                .clone();

                                            var $trashElement = $("<div class='pinion-icon-trash'></div>")
                                                .click(function() {
                                                    $tbody.find("button").click();
                                                    $imgElement.fadeOut(function() { 
                                                        $(this).remove(); 
                                                        _this.$fileUpload.show();
                                                    });
                                                });

                                            var $imgElement = $("<div class='pinion-SimpleImageUploader-image'></div>")
                                                .append($trashElement)
                                                .append($clone);

                                            _this.$fileUpload.hide().before($imgElement);

                                            // fire imageAdded event
                                            _this.fire("imageAdded", {
                                                id: file.id,
                                                src: file.thumbnailBig_url
                                            });
                                        } else {
                                            $tbody.fadeIn(function() { 
                                                $tbody.show();

                                                // fire imageAdded event
                                                _this.fire("imageAdded", {
                                                    id: file.id,
                                                    src: file.thumbnailBig_url
                                                });
                                            });
                                        }

                                    });
                                });
                            } else {
                                var $tbody = that._renderDownload(data.result.result)
                                    .css('display', 'none')
                                    .appendTo($(this).find('.files'));

                                    if(!_this.settings.multiple) { 
                                        // set dirty
                                        _this.info[_this.settings.infoKey] = file.name;
                                        _this.setDirty();
                                        // Fix for IE7 and lower:
                                        var $clone = $tbody
                                                .show()
                                                .find("img")
                                                .clone();

                                        var $trashElement = $("<div class='pinion-icon-trash'></div>")
                                            .click(function() {
                                                $tbody.find("button").click();
                                                $imgElement.fadeOut(function() { 
                                                    $(this).remove(); 
                                                    _this.$fileUpload.show();
                                                });
                                            });

                                        var $imgElement = $("<div class='pinion-SimpleImageUploader-image'></div>")
                                            .append($trashElement)
                                            .append($clone);

                                        _this.$fileUpload.hide().before($imgElement);

                                        // fire imageAdded event
                                        _this.fire("imageAdded", {
                                            id: data.result.result.id,
                                            src: data.result.result.thumbnailBig_url
                                        });
                                    } else {
                                        $tbody.fadeIn(function() { 
                                            $tbody.show();
                                            // fire imageAdded event
                                            _this.fire("imageAdded", {
                                                id: data.result.result.id,
                                                src: data.result.result.thumbnailBig_url
                                            });
                                        });
                                    }
                            }
                            pinion.ajaxCallback(data.result);
                        },
                        destroy: function(event, data) {
                            var that = $(this).data('fileupload'),
                                eventData = $.unserialize(data.url);

                            eventData = {
                                module: "fileupload",
                                event: "delete",
                                info: {
                                    file: eventData.file,
                                    dir: eventData.dir
                                }
                            };
                            data.context.remove();

                            pinion.ajax(eventData, function() {
                                that._adjustMaxNumberOfFiles(1);
                                // set clean
                                _this.setClean();
                            });
                        }
                    }, options));	
            });
        }
    };
    
    return constr;
    
}(jQuery));





pinion.backend.input.Fileuploader.fileUploadErrors = {
    '1': 'File exceeds upload_max_filesize (php.ini directive)',
    '2': 'File exceeds MAX_FILE_SIZE (HTML form directive)',
    '3': 'File was only partially uploaded',
    '4': 'No File was uploaded',
    '5': 'Missing a temporary folder',
    '6': 'Failed to write file to disk',
    '7': 'File upload stopped by extension',
    maxFileSize: 'File is too big',
    minFileSize: 'File is too small',
    acceptFileTypes: 'Filetype not allowed',
    maxNumberOfFiles: 'Max number of files exceeded',
    uploadedBytes: 'Uploaded bytes exceed file size',
    emptyResult: 'Empty file upload result'
};



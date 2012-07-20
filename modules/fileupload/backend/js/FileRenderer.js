

pinion.namespace("backend.renderer.FileRenderer");

pinion.backend.renderer.FileRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data,
            extension;
            
        extension = data.filename.split(".");
        extension = extension[extension.length - 1];
        
        this.$element = $("<div class='pinion-backend-renderer-FileRenderer'></div>")
            .append("<div class='pinion-backend-icon-rule'></div>")
        
        var $fileIcon = $("<div class='pinion-backend-icon-file'><div class='pinion-backend-file-extension-container'><div class='pinion-backend-file-extension'>"+extension+"</div></div></div>")
            .appendTo(this.$element);
        
        // TEXTWRAPPER
        var $textWrapper = $("<div class='pinion-textWrapper'></div>")
            .appendTo(this.$element);
        
        // UPDATE TEXTBOX
        var updateTextbox = this.addChild({
            type: "input",
            name: "UpdateTextbox",
            value: data.filename
        });
        updateTextbox.$element.appendTo($textWrapper);
        
        updateTextbox.on("change", function(element) {
            this.data.name = element.value;
        }, this);
            
        // FILE INFO    
        var $fileInfo = $("<div class='pinion-backend-file-info'></div>")
            .appendTo($textWrapper);
        $("<div class='pinion-backend-file-link'><div class='pinion-backend-icon-link'></div><div class='pinion-text'>"+pinion.translate("go to file")+"</div></div>")
            .click(function() {
                // todo go to file
            })
            .appendTo($fileInfo);
        $("<div class='pinion-backend-file-download'><div class='pinion-backend-icon-download'></div><div class='pinion-text'>"+pinion.translate("download file")+"</div></div>")
            .click(function() {
                // todo download file
            })
            .appendTo($fileInfo);
        
        if((!settings.isTrash && pinion.hasPermission("fileupload", "delete file")) || (settings.isTrash && pinion.hasPermission("fileupload", "delete trash file"))) {
            // IMAGE DELETE BUTTON
            pinion.data.Bar.call(this, [
                pinion.data.Delete.call(this, settings.data, function() {
                    _this.fadeOut(300, function() {
                        _this.info = {
                            dir: data.dir,
                            file: data.filename
                        };
                        _this.setDirty();
                    });
                })
            ]);
        }
        
        
        
        // CLOCK
        pinion.data.Timeinfo.call(this, settings.data);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.FileRenderer
    }
    
    return constr;
    
}(jQuery));




pinion.namespace("backend.renderer.ImageRenderer");

pinion.backend.renderer.ImageRenderer = (function($) {
    
    var constr,
        xOffset = 30,
        yOffset = 30,
        $imagePreview;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data,
            alt = data.alt ? data.alt : "",
            title = data.title ? data.title : "",
            extension,
            thumbSrc,
            thumbBigSrc;
            
        extension = data.filename.split(".");
        extension = extension[extension.length - 1];
        
        thumbSrc = pinion.php.modulesUrl+"/fileupload/files/images/edited/"+data.id+"/thumb."+extension;
        thumbBigSrc = pinion.php.modulesUrl+"/fileupload/files/images/edited/"+data.id+"/thumbBig."+extension;
        
        this.$element = $("<div class='pinion-backend-renderer-ImageRenderer'></div>");
        
        
        var $image = $("<img src='"+thumbSrc+"' alt='"+alt+"' alt='"+title+"' />")
            .mouseenter(function(event) {
                $imagePreview = $("<p id='pinion-imagePreview'><img src='"+ thumbBigSrc +"' alt='Image preview' /></p>")
                    .css("top",(event.pageY - xOffset) + "px")
                    .css("left",(event.pageX + yOffset) + "px")
                    .appendTo(pinion.$body)
                    .fadeIn("fast");
            })
            .mouseleave(function() {
                $imagePreview.remove();
            })
            .mousemove(function(event) {
                $imagePreview
                    .css("top",(event.pageY - xOffset) + "px")
                    .css("left",(event.pageX + yOffset) + "px");
            });
        
        $("<div class='pinion-image'></div>")
            .append($image)
            .appendTo(this.$element);
        
        // TEXTWRAPPER
        var $textWrapper = $("<div class='pinion-textWrapper'></div>")
            .appendTo(this.$element);
        
            if(pinion.hasPermission("fileupload", "delete file")) {
                // UPDATE TEXTBOX (FOR TITLE)
            var updateTextbox = this.addChild({
                type: "input",
                name: "UpdateTextbox",
                label: "title",
                value: title,
                events: settings.events
            }).$element.appendTo($textWrapper);

            updateTextbox.on("change", function(element) {
                this.data.name = element.value;
            }, this);

            // UPDATE TEXTBOX (FOR ALT)
            updateTextbox = this.addChild({
                type: "input",
                name: "UpdateTextbox",
                label: "alt",
                value: alt,
                events: settings.events
            }).$element.appendTo($textWrapper);

            updateTextbox.on("change", function(element) {
                this.data.name = element.value;
            }, this);
            
            if(pinion.hasPermission("fileupload", "rename file")) {
                // UPDATE TEXTBOX (FOR FILENAME)
                updateTextbox = this.addChild({
                    type: "input",
                    name: "UpdateTextbox",
                    label: "filename",
                    value: data.filename,
                    events: settings.events
                }).$element.appendTo($textWrapper);

                updateTextbox.on("change", function(element) {
                    this.data.name = element.value;
                }, this);
            } else {
                $textWrapper.append("<div class='pinion-image-filename'><div class='pinion-label'>filename</div><div class='pinion-text'>"+data.filename+"</div></div>");
            }
        } else {
            $textWrapper
                .append("<div class='pinion-image-title'><div class='pinion-label'>filename</div><div class='pinion-text'>"+title+"</div></div>")
                .append("<div class='pinion-image-alt'><div class='pinion-label'>filename</div><div class='pinion-text'>"+alt+"</div></div>")
                .append("<div class='pinion-image-filename'><div class='pinion-label'>filename</div><div class='pinion-text'>"+data.filename+"</div></div>");
        }
        
        
        if(pinion.hasPermission("fileupload", "delete file")) {
            // IMAGE DELETE BUTTON
            pinion.data.Bar.call(this, [
                pinion.data.Delete.call(this, settings.data, function() {
                    _this.info.deleted = true;
                    _this.fadeOut(300, function() {
                        _this.setDirty();
                    });
                })
            ]);
        }
        
        
        
        // INFO
        pinion.data.Info.call(this, ["Revision", "User", "Time"], data);
        
        settings.groupEvents = true;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.ImageRenderer,
        init: function() {
            this.info = {
                id: this.settings.data.id,
                file: this.settings.data.filename
            };
        },
        reset: function() {
            this.data.deleted = false;
            this.$element.show();
        }
    }
    
    return constr;
    
}(jQuery));


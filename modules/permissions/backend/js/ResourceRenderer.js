

pinion.backend.renderer.ResourceRenderer = (function($) {
    
    var constr,
        disableText,
        enableText;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data;
        
        
        if(data.allow == null) {
            data.isNew = true;
            this.isNew = true;
            data.active = false;
            data.allow = 1;
        } else {
            data.isNew = false;
            this.isNew = false;
            data.active = true;
        }
        
        this.$element = $("<div class='pinion-backend-renderer-ResourceRenderer'></div>")
            .click(function(event) {
                var $this = $(this);
                $(this).toggleClass("ui-selected");
                data.active = !data.active;
                
                if(_this.isNew) {
                    if(data.active) {
                        _this.setDirty();
                    } else {
                        _this.setClean();
                    }
                } else {
                    if(!data.active) {
                        _this.setDirty();
                    } else if(_this.initSettings.data.allow != data.allow) {
                        _this.setDirty();
                    } else {
                        _this.setClean();
                    }
                }
                
                event.preventDefault();
                return false;
            })
            .append("<div class='pinion-icon-allow'></div>");
        
        if(data.allow) {
            this.$element.addClass("pinion-allowed");
        }
        
        if(disableText === undefined) {
            disableText = pinion.translate("disable");
            enableText = pinion.translate("enable");
        }
        
        $("<div class='pinion-textWrapper'>")
            .append("<div class='pinion-moduleTitle'>"+settings.data.module+"</div>")
            .append("<div class='pinion-resource'>"+settings.data.permission+"</div>")
            .appendTo(this.$element);
        
        
        var $resourceOption = $("<div class='pinion-resourceOption'>");
            
        var $resourceOptionText = $("<div class='pinion-text'>"+(settings.data.allow ? disableText : enableText)+"</div>")
            .appendTo($resourceOption);
        
        // RENDERER BAR
        pinion.data.Bar.call(this, [
            $resourceOption
        ]);
            
        $("<div class='pinion-icon'></div>")
            .click(function(event) {
                data.allow = data.allow ? 0 : 1;
                
                if(data.allow) {
                    $resourceOptionText.text(disableText);
                    _this.$element.addClass("pinion-allowed");
                } else {
                    $resourceOptionText.text(enableText);
                    _this.$element.removeClass("pinion-allowed");
                }
                
                if(!_this.isNew && data.active) {
                    if(_this.initSettings.data.allow != data.allow) {
                        _this.setDirty();
                    } else {
                        _this.setClean();
                    }
                }
                event.preventDefault();
                return false;
            })
            .appendTo($resourceOption);
        
        if(!this.isNew) {
            this.$element.addClass("ui-selected");
            pinion.data.Timeinfo.call(this, data);
        }    
        
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.ResourceRenderer,
        init: function() {
            this.info = this.settings.data;
        },
        reset: function() {
            if(this.isNew) {
                this.$element.removeClass("ui-selected");
            } else {
                this.$element.addClass("ui-selected");
            }
            
            if(this.settings.data.allow) {
                this.$element.addClass("pinion-allowed");
            } else {
                this.$element.removeClass("pinion-allowed");
            }
        }
    }
    
    return constr;
    
}(jQuery));



pinion.backend.renderer.CommentRenderer = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var _this = this,
            data = settings.data;
        
        
        this.$element = $("<div class='pinion-backend-renderer-CommentRenderer'></div>");
        
        // TEXTWRAPPER
        var $textWrapper = $("<div class='pinion-textWrapper'></div>")
            .appendTo(this.$element);
            
        // INFOS
        $("<div class='pinion-comment-info'></div>")
            // USER
            .append("<div class='pinion-name'><span class='pinion-backend-icon-user'></span><span class='pinion-username'>"+data.name+"</span></div>")
            // TIME
            .append("<div class='pinion-time'><span class='pinion-backend-icon-clock'></span><span class='pinion-time-text'>"+data.created+"</span></div>")
            .append("<div class='pinion-mail'><span class='pinion-backend-icon-mail'></span><a href='mailto:"+data.email+"' class='pinion-mail-adress'>"+data.email+"</a></div>")
            .appendTo(this.$element);
        
        // COMMENT
        $("<div class='pinion-commentWrapper'><div class='pinion-comment-text'>"+data.text+"</div></div>")
            .appendTo(this.$element);
        
        var $activate = $("<div class='pinion-activate'><div class='pinion-icon'></div><div class='pinion-text'>"+pinion.translate("activate comment")+"</div></div>")
            .click(function() {
                if(_this.$element.hasClass("pinion-activated")) {
                    _this.setClean();
                } else {
                    _this.setDirty();
                }
                _this.$element.toggleClass("pinion-activated")
            });
        
        // RENDERER BAR
        var bar = [];
        
        if(pinion.hasPermission("comment", "approve comment")) {
            bar.push($activate);
        }
        if(pinion.hasPermission("comment", "delete comment")) {
            bar.push(pinion.data.Delete.call(this, data, function() {
                _this.info.deleted = true;
                _this.fadeOut(300, function() {
                    _this.setDirty();
                });
            }));
        }
        
        if(!pinion.isEmpty(bar)) {
            pinion.data.Bar.call(this, bar);
        }
        
        
        
        // INFO
        pinion.data.Info.call(this, ["Time"], data);
        
        // group events
        settings.groupEvents = true;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.CommentRenderer,
        init: function() {
            this.info.id = this.settings.data.id;
        },
        reset: function() {
            this.$element.removeClass("pinion-activated");
        }
    }
    
    return constr;
    
}(jQuery));


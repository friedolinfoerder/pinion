pinion.namespace("backend.renderer.UserRenderer");

pinion.backend.renderer.UserRenderer = (function($) {
    
    var constr;
    
    constr = function(settings) {
        var _this = this;
        
        this.$element = $("<div class='pinion-backend-renderer-UserRenderer'></div>");
        
        
        var $userPic = $("<div class='pinion-userPic'></div>")
            .appendTo(this.$element);
        
        if(settings.data.image) {
            $userPic.css("background-image", "url(data:image/jpg;base64,"+settings.data.image+")");
        } else {
            $userPic.css("background-image", "url("+settings.data.userPic+")");
        }
        
        var $textWrapper = $("<div class='pinion-textWrapper'>")
            .appendTo(this.$element);
        
        var $headlineWrapper = $("<div class='pinion-headlineWrapper'>")
            .append("<div class='pinion-username'>"+settings.data.username+"</div>")
            .appendTo($textWrapper);
            
        this.$rule = $("<div class='pinion-userRule' data-rule='"+settings.data.rule_id+"'><div class='pinion-backend-icon-rule'></div><span>"+settings.data.rule+"</span></div>")
            .appendTo($headlineWrapper);
        
        $("<div class='pinion-user'></div>")
            .append("<span class='pinion-fullName'>"+settings.data.firstname+" "+settings.data.lastname+"</span>")
            .append(pinion.$link(settings.data.email, "message", "Write message").addClass("pinion-mail"))
            .appendTo($textWrapper);
        
        
            
        var $onlineSign = $("<div class='pinion-onlineSign'>");
        
        this.$onlineIcon = $("<div class='pinion-backend-icon-online-grey'></div>").appendTo($onlineSign);
        this.$onlineText = $("<div class='pinion-text'>offline</div>").appendTo($onlineSign);
            
        if(pinion.php.user[settings.data.id] !== undefined || pinion.php.userid == settings.data.id) {
            this.$onlineIcon.addClass("pinion-online");
            this.$onlineText.text(pinion.translate("online"));
        }
        
        var $answer = pinion.$link("", "message", "Write message")
            .addClass("pinion-messageSign")
            .append("<div class='pinion-backend-icon-message-grey'></div>")
            .append("<div class='pinion-text'>"+pinion.translate("write message")+"</div>")
            .appendTo(this.$element);
        
        // RENDERER BAR
        pinion.data.Bar.call(this, [
            $onlineSign,
            $answer
        ]);
        
        // INFO
        pinion.data.Info.call(this, ["Time", "Revision", "User"], settings.data);
        
        this.$element
            .droppable({
                tolerance: "pointer",
                drop: function(event, ui) {
                    var newRuleId = ui.draggable.children().attr("data-id");
                    
                    _this.settings.data.rule = ui.draggable.find(".column-name").text();
                    _this.settings.data.rule_id = newRuleId;
                    
                    _this.changeRule();
                }
            });
    };
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.UserRenderer,
        init: function() {
            this.info = this.settings.data;
            
            pinion
                .on("userOnline", function(data) {
                    if(this.data.id == data.id) {
                        this.$onlineIcon.addClass("pinion-online");
                        this.$onlineText.text(pinion.translate("online"));
                    }
                }, this)
                .on("userOffline", function(data) {
                    if(this.data.id == data.id) {
                        this.$onlineIcon.removeClass("pinion-online");
                        this.$onlineText.text(pinion.translate("offline"));
                    }
                }, this);
            
        },
        changeRule: function() {
            this.$rule
                .attr("data-rule", this.settings.data.rule_id)
                .children("span")
                    .text(this.settings.data.rule);
                    
            if(this.initSettings.data.rule_id != this.settings.data.rule_id) {
                this.setDirty();
                this.$rule.addClass("dirty");
            } else {
                this.setClean();
                this.$rule.removeClass("dirty");
            }
        },
        reset: function() {
            this.changeRule();
        }
    };
    
    return constr;
    
}(jQuery));
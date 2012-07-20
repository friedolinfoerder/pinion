
pinion.backend.renderer.UserMessageRenderer = (function($) {
    
    var constr,
        answer;
    
    // public API -- constructor
    constr = function(settings, backend) {
        var data = settings.data;
        
        this.$element = $("<div class='pinion-backend-renderer-UserMessageRenderer'></div>")
            .click(function() {
                var $this = $(this);
                if(data.status == 1) {
                    pinion.ajax({
                        event: "read",
                        module: "message",
                        info: {id: data.id, title: data.title}
                    }, function() {
                        data.status = 2;
                        $this.removeClass("pinion-newMessage");
                    });
                }
                $messageWrapper.slideToggle(300);
            });
        
        if(data.status == 1) {
            this.$element.addClass("pinion-newMessage");
        }
        
        // INFOS
        $("<div class='pinion-message-info'></div>")
            // USER
            .append("<div class='pinion-user'><span class='pinion-backend-icon-user'></span><span class='pinion-username'>"+data.user+"</span></div>")
            // TIME
            .append("<div class='pinion-time'><span class='pinion-backend-icon-clock'></span><span class='pinion-time-text'>"+data.created+"</span></div>")
            // TITLE
            .append("<div class='pinion-subject'><span class='pinion-backend-icon-subject'></span><span class='pinion-subject-title'>"+data.title+"</span></div>")
            .appendTo(this.$element);
        
        // MESSAGE
        var $messageWrapper = $("<div class='pinion-messageWrapper'><div class='pinion-message-text'>"+data.text+"</div></div>")
            .hide()
            .appendTo(this.$element);
        
        
        // RENDERER BAR
        var bar = [];
        
        if(pinion.hasPermission("message", "write message to user")) {
            // ANSWER
            answer = answer || pinion.translate("answer");

            var $answer = $("<div class='pinion-messageSign'>")
                .append("<div class='pinion-backend-icon-message-grey'></div>")
                .append("<div class='pinion-text'>"+answer+"</div>")
                .appendTo(this.$element);
                
            bar.push($answer);
        }
        
        bar.push(pinion.data.Delete.call(this, data));
            
        
        pinion.data.Bar.call(this, bar);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.UserMessageRenderer
    }
    
    return constr;
    
}(jQuery));
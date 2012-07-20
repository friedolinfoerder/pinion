
pinion.namespace("modules.audio");

pinion.modules.audio.Preview = (function($) {
    
    var constr,
        modulesUrl = pinion.php.modulesUrl;
    
    constr = function(data) {
        var _this = this,
            player,
            hasLoaded = false,
            hovered = false;
        
        this.$element = $("<div>")
            .append("<div class='pinion-moduleIcon-audio'><img src='"+modulesUrl+"/audio/icon.png'></img></div>")
            .append("<div class='pinion-audio-filename'>"+data.filename+"</div>")
            .one("mouseenter", function() {
                player = audiojs.create($audio.get(0), {
                    loop: true,
                    preload: true,
                    autoplay: true,
                    loadStarted: function() {
                        hasLoaded = true;
                        if(!hovered) {
                            player.stop();
                        }
                    }
                });
            })
            .hover(function() {
                hovered = true;
                if(hasLoaded) {
                    player.play();
                }
            }, function() {
                hovered = false;
                if(hasLoaded) {
                    player.pause();
                }
            });
            
        var $audioContainer = $("<div class='pinion-audio-file'>")
            .appendTo(this.$element),
        $audio = $("<audio src='"+data.src+"'>")
            .appendTo($audioContainer);
    };
    
    constr.prototype = {
        constructor: pinion.modules.audio.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));

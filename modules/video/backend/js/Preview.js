
pinion.namespace("modules.video");

pinion.modules.video.Preview = (function($) {
    
    var constr,
        modulesUrl = pinion.php.modulesUrl;
    
    constr = function(data) {
        this.$element = $("<div>");
        
        // PREVIEW INFO
        $("<div class='pinion-preview-info'></div>")
//            .append("<div class='pinion-width'><div class='pinion-icon'></div><div class='pinion-text'>"+data.width+"px</div></div>")
//            .append("<div class='pinion-height'><div class='pinion-icon'></div><div class='pinion-text'>"+data.height+"px</div></div>")
            .appendTo(this.$element);
        
        var id = pinion.getId(),
            player = null,
            hovered = false,
            files = data.videofiles,
            path = data.path,
            $files = $("<ul class='pinion-video-files'>")
                .appendTo(this.$element)
                .boxMove({windowSize: 180, direction: "vertical"});
        
        var $videoContainer = $("<div class='pinion-video-file'>")
                .appendTo(this.$element),
            $video = $("<video class='video-js vjs-default-skin' id='"+id+"' width='240' height='180'>")
                .appendTo($videoContainer);
        
        // FILE LIST        
        for(var i = 0, length = files.length; i < length; i++) {
            var videofile = files[i].file;
            
            $video.append("<source src='"+data.path+videofile.filename+"' type='video/"+videofile.type+"'>");
        }
        
        this.$element
            .one("mouseenter", function() {
                _V_.options.flash.swf = modulesUrl+"/video/templates/js/videojs/video-js.swf";

                _V_(id, {
                    loop: true
                }, function() {
                    player = this;
                    if(hovered) {
                        player.play();
                    }
                });
            })
            .hover(function() {
                hovered = true;
                if(player) {
                    player.play();
                }
            }, function() {
                hovered = false;
                if(player) {
                    player.pause();
                }
            });

        
        // COUNT        
        $("<div class='pinion-video-file-count'></div>")
            .append("<div class='pinion-icon-file'></div>")
            .append("<div class='pinion-file-count'>"+files.length+"</div>") 
            .appendTo(this.$element);
    
        // FILE LIST        
        for(i = 0, length = files.length; i < length; i++) {
            videofile = files[i];
            
            $("<li class='pinion-video-filename'>"+videofile.file.filename+"</li>")
                .appendTo($files);
        }
        
            
    };
    
    constr.prototype = {
        constructor: pinion.modules.video.Preview,
        init: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));

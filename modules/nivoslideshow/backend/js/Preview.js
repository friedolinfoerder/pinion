
pinion.namespace("modules.nivoslideshow");

pinion.modules.nivoslideshow.Preview = (function($) {
    
    var constr,
        modulesUrl = pinion.php.modulesUrl;
    
    constr = function(data) {
        var images = data.images;
        
        this.$element = $("<div></div>");
        
        $("<div class='pinion-preview-info'></div>")
            .append("<div class='pinion-width'><div class='pinion-icon'></div><div class='pinion-text'>"+data.width+"px</div></div>")
            .append("<div class='pinion-height'><div class='pinion-icon'></div><div class='pinion-text'>"+data.height+"px</div></div>")
            .appendTo(this.$element);
        
        var $ul = $("<ul class='pinion-images'></ul>");
        
        for(var i = 0, length = images.length; i < length; i++) {
            var image = images[i],
                id = image.id,
                ext = image.filename.split(".").pop(),
                thumbSrc = modulesUrl+"/fileupload/files/images/edited/"+id+"/thumb."+ext;
            
            $("<li><img src='"+thumbSrc+"' /></li>").appendTo($ul);
        }
        
        $ul
            .appendTo(this.$element)
            .boxMove({windowSize: 240});
        
        $("<div class='pinion-count'>"+pinion.translate("images in this slideshow")+": "+length+"</div>").appendTo(this.$element);
    };
    
    return constr;
    
}(jQuery));
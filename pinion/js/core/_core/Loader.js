pinion.Loader = (function($) {
    
    var constr,
        url = pinion.php.url;
    
    constr = function(type, size) {
        var _this = this,
            size = size || 20,
            $canvas = $("<canvas width='"+size+"' heigth='"+size+"' class='pinion-Loader'></canvas>"),
            context = $canvas.get(0).getContext("2d"),
            rot = 0,
            img = new Image();
        
        if(typeof type != "string") {
            type = "default";
        }
        img.src = url+"pinion/assets/images/loader/loader_"+type+".png";
        img.onload = function() {
            _this.intervalID = setInterval(function() {
                context.clearRect(0, 0, size, size);
                context.save();
                context.translate(size/2, size/2);
                context.rotate(rot*Math.PI*2/8);
                context.drawImage(img, -size/2, -size/2);
                context.restore();

                rot++;
            }, 80);
        };
        this.$element = $canvas;
    };
    
    constr.prototype = {
        constructor: pinion.Loader,
        remove: function() {
            clearInterval(this.intervalID);
            this.$element.remove();
        }
    };
    
    return constr;
    
}(jQuery));
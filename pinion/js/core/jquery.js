

(function($) {
    
    $.unserialize = function(str) {
        var output = {};
        var strData = str.split("&");
        for(var i = 0, length = strData.length; i < length; i++) {
            var keyAndValue = strData[i].split("=");
            if(keyAndValue.length == 2) {
                output[keyAndValue[0]] = keyAndValue[1];
            }
            
        }
        return output;
    };
    
    $.fn.reverse = Array.prototype.reverse;
    
    $.fn.sortChildren = function(fn) {
        this.children().sort(fn).appendTo(this);
        
        return this;
    };
    
    $.fn.textRunner = (function() {
        
        var currentHover;
        
        return function() {
            return this
                .children(".text")
                    .wrap("<span class='textWrapper textRunner-title'></span>")
                    .end()
                .one("mouseenter", function() {
                    var $this = $(this),
                        $textWrapper = $this.children(".textWrapper").removeClass("textRunner-title"),
                        $text = $textWrapper.children(".text"),
                        textWidth = $text.width(),
                        textWrapperWidth = $textWrapper.width(),
                        mouseenter = function() {
                            currentHover = $this;

                            var diff = textWidth - textWrapperWidth,
                                moveText = function() {
                                    setTimeout(function() {
                                        if(currentHover === $this) {
                                            $text
                                                .animate({
                                                    "margin-left": -diff
                                                }, diff*50, "easeInOutSine", setToStart);
                                        }
                                    }, 500);
                                },
                                setToStart = function() {
                                    setTimeout(function() {
                                        if(currentHover === $this) {
                                            $text.css("margin-left", 0);
                                            moveText();
                                        }
                                    }, 1200);
                                };

                            moveText();

                        };
                        
                    if(textWidth > textWrapperWidth) {
                        $this
                            .hover(mouseenter, function() {
                                currentHover = null;
                                $(this).children(".textWrapper").children(".text").stop(true, false).css("margin-left", 0);
                            });
                        mouseenter();
                    } else {
                        $textWrapper.addClass("textRunner-title");
                    }
                });
        };
        
    }());
    
    $.fn.boxMove = (function() {
        
        var defaultOptions = {
            direction: "horizontal",
            windowSize: 100,
            wait: 1000
        };
        
        return function(options) {
            options = $.extend({}, defaultOptions, options);
            
            return this
                .wrap("<div class='pinion-"+options.direction+"-move'>")
                .one("mouseenter", function() {
                    var $this = $(this),
                        $parent = $this.parent(),
                        size = options.direction == "horizontal" ? $this.width() : $this.height(),
                        pos = options.direction == "horizontal" ? $parent.position().left : $parent.position().top,
                        windowSize = options.windowSize - pos,
                        diff = size - windowSize;
                    
                    if(diff > 0) {
                        var enter,
                            leave,
                            hovered = false,
                            wait = options.wait;
                            
                        if(options.direction == "horizontal") {
                            enter = function() {
                                hovered = true;
                                setTimeout(function() {
                                    if(hovered) {
                                        $this.animate({
                                            left: -diff
                                        }, diff*50, "linear", function() {
                                            setTimeout(function() {
                                                if(hovered) {
                                                    $this.animate({
                                                        left: 0
                                                    }, diff*50, "linear", enter);
                                                }
                                            }, wait);
                                        });
                                    }
                                }, wait);
                            };
                            leave = function() {
                                hovered = false;
                                $this
                                    .stop(true)
                                    .css("left", 0);
                            };
                        } else {
                            enter = function() {
                                hovered = true;
                                setTimeout(function() {
                                    if(hovered) {
                                        $this.animate({
                                            top: -diff
                                        }, diff*50, "linear", function() {
                                            setTimeout(function() {
                                                if(hovered) {
                                                    $this.animate({
                                                        top: 0
                                                    }, diff*50, "linear", enter);
                                                }
                                            }, wait);
                                        });
                                    }
                                }, wait);
                            };
                            leave = function() {
                                hovered = false;
                                $this
                                    .stop(true)
                                    .css("top", 0);
                            };
                        }
                        $this.hover(enter, leave);
                        enter();
                    }
                });
        };
        
        
        
        
    }());
    
}(jQuery));

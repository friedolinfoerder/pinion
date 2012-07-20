
pinion.namespace("modules.imageedit");

(function($) {
    
    var url = pinion.php.modulesUrl;
    
    pinion.modules.imageedit.edit = function(data) {

        $.colorbox({
            html: "<div id='imageedit-image-"+data.id+"' style='width: 850px; height: 850px'></div>",
            onComplete: function() {
                var $image = $("<img>")
                    .load(function() {
                        pinion.require([url+"/imageedit/templates/css/jquery.Jcrop.css", url+"/imageedit/templates/js/jquery.Jcrop.min.js"], function() {
                            
                            var $imageedit = $("#imageedit-image-"+data.id),
                                $imageeditParent = $imageedit.parent(),
                                coordinates = {w:0, h:0},
                                showCoords = function(c) {
                                    coordinates = c;
                                },
                                release = function() {
                                    coordinates = {w:0, h:0};
                                },
                                options = {
                                    onSelect: showCoords,
                                    onChange: showCoords,
                                    onRelease: release,
                                    boxWidth: 800,
                                    boxHeight: 800
                                },
                                setAspectRatio = function(ratio) {
                                    crop_api.setOptions({aspectRatio: ratio});
                                },
                                crop_api;
                                
                            $("<input type='submit' value='3:1' />")
                                .click(function() {
                                    setAspectRatio(3);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                            
                            $("<input type='submit' value='21:9' />")
                                .click(function() {
                                    setAspectRatio(21/9);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='submit' value='2:1' />")
                                .click(function() {
                                    setAspectRatio(2);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='submit' value='16:9' />")
                                .click(function() {
                                    setAspectRatio(16/9);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='submit' value='3:2' />")
                                .click(function() {
                                    setAspectRatio(3/2);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='submit' value='4:3' />")
                                .click(function() {
                                    setAspectRatio(4/3);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                            
                            $("<input type='submit' value='5:4' />")
                                .click(function() {
                                    setAspectRatio(5/4);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                            
                            $("<input type='submit' value='1:1' />")
                                .click(function() {
                                    setAspectRatio(1);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='text' />")
                                .appendTo($imageedit);
                                
                            $("<input type='text' />")
                                .appendTo($imageedit);
                            
                            $("<input type='submit' value='"+pinion.translate("own ratio")+"' />")
                                .click(function() {
                                    var $second = $(this).prev(),
                                        second = parseInt($second.val(), 10),
                                        $first = $second.prev(),
                                        first = parseInt($first.val(), 10);
                                    
                                    if(first > 0 && second > 0) {
                                        setAspectRatio(first/second);
                                    }
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='submit' value='"+pinion.translate("no ratio")+"' />")
                                .click(function() {
                                    setAspectRatio(null);
                                    
                                    return false;
                                })
                                .appendTo($imageedit);
                                
                                
                            $image
                                .Jcrop({
                                    onSelect: showCoords,
                                    onChange: showCoords,
                                    onRelease: release,
                                    boxWidth: 800,
                                    boxHeight: 800
                                }, function() {
                                    crop_api = this;
                                    this.ui.holder.css("margin", "auto");
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='submit' value='"+pinion.translate("overwrite file")+"' />")
                                .click(function() {
                                    if(coordinates.w > 0 && coordinates.h > 0) {
                                        pinion.ajax({
                                            event: "crop",
                                            module: "imageedit",
                                            info: $.extend({id: data.id, type: "overwrite"}, coordinates)
                                        });
                                    }

                                    return false;
                                })
                                .appendTo($imageedit);
                                
                            $("<input type='submit' value='"+pinion.translate("create new file")+"' />")
                                .click(function() {
                                    if(coordinates.w > 0 && coordinates.h > 0) {
                                        pinion.ajax({
                                            event: "crop",
                                            module: "imageedit",
                                            info: $.extend({id: data.id, type: "create"}, coordinates)
                                        });
                                    }

                                    return false;
                                })
                                .appendTo($imageedit);
                            
                        });
                    })
                    .attr("src", "&module=image&event=image&id="+data.id);
            }
        });
    };
} (jQuery));
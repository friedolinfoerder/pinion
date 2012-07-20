

pinion.namespace("modules.container");

pinion.modules.container.Preview = (function($) {
    
    var constr,
        phpModules = pinion.php.modules;
    
    constr = function(data) {
        this.$element = $("<div></div>");
        
        // PREVIEW LIST
        this.previews = [];
        
        var _this = this,
            structure = data.structure,
            elements = data.elements,
            loadPreview = function(index) {
                _this.$element.find(".pinion-element-preview").hide();
                _this.previews[index].show();
                $ulStructure.children().removeClass("pinion-active").eq(index).addClass("pinion-active");
            };
        
        var $structure = $("<div class='pinion-structure-info'><div class='pinion-structure-name'>"+structure.name+"</div></div>")
            .appendTo(this.$element);
        
        var $ulStructure = $("<ul class='pinion-container-structure'>")
            .on("mouseenter", "li", function() {
                loadPreview($(this).index());
            })
            .appendTo($structure)
            .boxMove({windowSize: 240});
            
        for(var i = 0, length = elements.length; i < length; i++) {
            var element = elements[i],
                module = phpModules[element.module];
            
            // list item
            $("<li class='pinion-structure-element'></li>")
                .append("<span class='pinion-module-icon'><img src='"+module.icon+"' /></span>")
                .append("<span class='pinion-module-name'>"+module.title+"</span>")
                .appendTo($ulStructure);
            
            // preview
            this.previews.push($("<div class='pinion-element-preview'><div class='pinion-previewPlaceholder'>"+module.title+": "+element.moduleid+"</div></div>")
                .one("mouseenter", {element: element}, function(event) {
                    var $this = $(this),
                        $placeholder = $this.children(".pinion-previewPlaceholder").html("");
                        
                    if($placeholder.length > 0) {
                        var dataElement = event.data.element,
                            moduleName = dataElement.module,
                            moduleId = dataElement.moduleid,
                            loader = new pinion.Loader("default-40px", 40);

                        $placeholder.append(loader.$element);

                        pinion.ajax({
                            module: moduleName,
                            event: "getPreview",
                            info: {
                                id: moduleId
                            }
                        }, function(data) {
                            loader.remove();
                            $this
                                .html("")
                                .append(pinion.preview(moduleName, data.data));
                        });
                    }
                })
                .appendTo(this.$element));
        }
        
        var $overview = $("<div class='pinion-element-preview pinion-active'>")
            .append($("<div class='pinion-container-overview'>")
                .append("<div class='pinion-preview-info'><div class='pinion-structure-name'>"+structure.name+"</div></div>")
                .append($ulStructure.clone())
            )
            .appendTo(this.$element);

        this.$element.hover(function() {
            $overview.hide();
            loadPreview(0);
        }, function() {
            _this.$element.find(".pinion-element-preview").hide();
            $overview.show();
        });
    };
    
    constr.prototype = {
        constructor: pinion.modules.container.Preview
    };
    
    return constr;
    
}(jQuery));

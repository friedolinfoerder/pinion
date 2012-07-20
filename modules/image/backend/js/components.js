
pinion.namespace("backend.image");

pinion.backend.image.ImageGrid = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function() {
        this.$element = $("<div class='pinion-backend-image-ImageGrid'></div>");
        this.$slider = $("<div class='pinion-slider'></div>").appendTo(this.$element);
        this.$warning = $("<div class='pinion-backend-warning'></div>").appendTo(this.$element);
        
        this.counter = 0;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.image.ImageGrid,
        defaultSettings: {
            selectable: true,
            selectedImages: [],
            size: 30
        },
        init: function() {
            
            var _this = this,
                $element = this.$element,
                selectedImages = this.settings.selectedImages;
            
            // cast the selected images ids to integer
            for(var i = 0, length = selectedImages.length; i < length; i++) {
                selectedImages[i] = parseInt(selectedImages[i], 10);
            }
            
            this.$ul = $("<ul class='grid'></ul>")
                .on("click", "img", function() {
                    var $this = $(this);
                    _this.fire("click", {
                        id: $this.attr("data-id"),
                        src: $this.attr("src")
                    });
                })
                .appendTo($element);
                
            
            this.$infoContainer = $("<div class='info'></div>").appendTo($element);
            
            this.$slider.slider({
                max: 100,
                min: 10,
                value: this.settings.size,
                slide: function(event, ui) {
                    _this.$element.find("li").css('font-size',ui.value);
                }
            });
            
            this.getImages();
        },
        getImages: function() {
            var _this = this;
            
            var images = this.settings.data,
                $ul = _this.$ul;

            for(var i = 0, length = images.length; i < length; i++) {
                var image = images[i],
                    id = image.id,
                    src = image.src;

                // create image
                var $img = $("<img data-id='"+id+"' />")
                    .load(function() {
                        var $this = $(this);
                        
                        $this
                            .css({
                                width: $this.width() / 100 + "em",
                                height: $this.height() / 100 + "em",
                                display: "inline"
                            })
                            .fadeIn(300);
                    })
                    .hide();

                // create list-item
                $("<li></li>")
                    .css("font-size", _this.$slider.slider("option", "value"))
                    .append($img)
                    .appendTo($ul);


                // set src
                $img.attr("src", src);

                // fire event
                _this.fire("imagesLoaded");
            }
            
            if(this.settings.selectable) {
                this.$ul
                    .selectable({
                        stop: function(event, ui) {
                            _this.findSelectedImages();
                        }
                    });
            }
        },
        findSelectedImages: function() {
            var $selected = this.$ul.children(".ui-selected"),
                length = $selected.length,
                images = [];
            if(length > 0) {
                this.$infoContainer.html(pinion.translate("You have selected")+" <b>"+length+"</b> "+(length == 1 ? pinion.translate("image") : pinion.translate("images"))+".");
                $selected.children("img").each(function() {
                    images.push(parseInt($(this).attr("data-id"), 10));
                });
            } else {
                this.$infoContainer.text("");
            }
            
            if(this.settings.selectedImages.length == images.length) {
                var dirty = false;
                for(var i = 0, length = images.length; i < length; i++) {
                    if(this.settings.selectedImages.indexOf(images[i]) == -1) {
                        dirty = true;
                        break;
                    }
                }
                if(dirty) {
                    this.info.images = images;
                    this.setDirty();
                } else {
                    this.setClean();
                }
            } else {
                this.info.images = images;
                this.setDirty();
            }

            this.fire("imagesSelected", {images:images});
        },
        selectImages: function(ids) {
            this.$element.find("li").removeClass("ui-selected");
            
            for(var i = 0, length = ids.length; i < length; i++) {
                this.$element.find("img[data-id="+ids[i]+"]").parent().addClass("ui-selected");
            }
            
            this.findSelectedImages();
        },
        validate: function() {
            var validators = this.validators;
            
            if(validators.selectOne) {
                var $selected = this.$ul.children(".ui-selected");
                if($selected.length !== 1) {
                    return this.setInvalid(pinion.translate("Please select one image"));
                }
            }
            
            return this.setValid();
        },
        reset: function() {
            this.selectImages(this.settings.selectedImages);
        }
    }
    return constr;
    
}(jQuery));



pinion.backend.image.ImageList = (function($) {
    
    var constr,
        idCount = 0;
    
    constr = function(settings) {
        var _this = this,
            images = settings.images;
        
        this.$element = $("<ul class='pinion-backend-image-ImageList'></ul>")
            .on("click", ".pinion-delete", function() {
                var $li = $(this).parent(),
                    id = $li.data("id");
                    
                $li.fadeOut(300, function() {
                    $li.remove();
                    _this.checkForChanges();
                });
                
                for(var i = images.length; i--; ) {
                    if(id == images[i].id) {
                        images.splice(i, 1);
                        break;
                    }
                }
            });
    };
    
    constr.prototype = {
        constructor: pinion.backend.image.ImageList,
        defaultSettings: {
            images: [],
            max: 0,
            height: 300
        },
        init: function() {
            
            
            var images = this.settings.images;
            for(var i = 0, length = images.length; i < length; i++) {
                var image = images[i];
                
                $("<li><div class='pinion-delete'></div><img src='"+image.src+"' height='"+this.settings.height+"' /></li>")
                    .data("id", image.id)
                    .appendTo(this.$element);
            }
        },
        addImage: function(src, id) {
            if(id === undefined) {
                id = idCount++;
            }
            this.settings.images.push({src: src, id: id});
                
            $("<li><div class='pinion-delete'></div><img src='"+src+"' height='"+this.settings.height+"' /></li>")
                .data("id", id)
                .appendTo(this.$element);
                
            this.checkForChanges();
        },
        checkForChanges: function() {
            var $children = this.$element.children("li"),
                initImages = this.initSettings.images,
                images = this.settings.images;
            
            var dirty = false;
            
            if(initImages.length != images.length) {
                dirty = true;
            } else {
                for(var i = initImages.length; i--; ) {
                    if(initImages[i].id != images[i].id) {
                        dirty = true;
                        break;
                    }
                }
            }
            if(dirty) {
                this.setDirty();
            } else {
                this.setClean();
            }
            this.info.images = images;
        },
        reset: function() {
            this.$element.children("li").remove();
            this.init();
        }
    };
    
    return constr;
    
}(jQuery));
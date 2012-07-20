pinion.backend.input.RadioSwitcher = (function($) {
    
    var constr,
        TextboxPrototype = pinion.backend.input.Textbox.prototype;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Switcher pinion-button'></div>");
        
        // label as infoKey
        TextboxPrototype.labelAsInfoKey(settings);
        
        this.$switcherList = $("<div class='pinion-backend-input-Switcher-switcherList'></div>")
            .hover(function() {
                _this.hovered = true;
            }, function() {
                _this.hovered = false;
                setTimeout(function() {
                    if(!_this.hovered) {
                        _this.$switcherList.detach();
                    }
                }, 250);
            });
            
        var _this = this,
            data = settings.data,
            length = data.length,
            translateValues = settings.translateValues;
            
        if(length > 0) {
            var description = null;
            if(settings.idAsDescription) {
                description = "id";
            }
            if(settings.description) {
                description = settings.description;
            } else {
                for(var name in data[0]) {
                    if(name == "id") continue;
                    description = name;
                    break;
                }
            }
            
            for(var i = 0; i < length; i++) {
                var d = data[i],
                    desc = translateValues ? pinion.translate(d[description]) : d[description],
                    $li = $("<li data-id='"+d.id+"'><input type='submit' value='"+desc+"' /></li>")
                        .click(function() {
                            var $this = $(this);
                            _this.info[_this.infoKey] = $this.attr("data-id");
                            $this
                                .addClass("pinion-active")
                                .siblings()
                                    .removeClass("pinion-active");
                        })
                        .appendTo(this.$switcherList);
                        
                if(i == 0) {
                    _this.info = {};
                    _this.info[settings.infoKey] = d.id;
                    $li.addClass("pinion-active pinion-first");
                }
                if(i == length - 1) {
                    $li.addClass("pinion-last");
                }
            }
        }
        
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.RadioSwitcher,
        defaultSettings: {
            idAsDescription: true,
            description: null,
            label: "switch",
            data: [],
            dataPosition: "left",
            translate: true,
            translateValues: true
        },
        init: function() {
            var _this = this,
                outerWidth = null,
                dataPosition = this.settings.dataPosition,
                label = this.settings.translate ? pinion.translate(this.settings.label) : this.settings.label;
            
            this.$button = $("<input type='submit' value='"+label+"' />")
                .click(function() {
                    if(_this.isDirty()) {
                        _this.$switcherList.detach();
                        _this.setClean();
                    } else {
                        var offset = _this.$element.offset(),
                            css = {};
                        
                        if(dataPosition == "right") {
                            css = {
                                top: offset.top,
                                left: "",
                                right: pinion.$document.width() - offset.left
                            };
                        } else {
                            if(outerWidth == null) {
                                outerWidth = _this.$button.outerWidth();
                            }
                            css = {
                                top: offset.top,
                                left: offset.left + outerWidth,
                                right: ""
                            };
                        }
                        _this.$switcherList
                            .css(css)
                            .appendTo(pinion.$body);
                        _this.setDirty();
                    }
                })
                .hover(function() {
                    _this.hovered = true;
                    if(_this.isDirty()) {
                        _this.$switcherList.appendTo(pinion.$body);
                    }
                }, function() {
                    _this.hovered = false;
                    setTimeout(function() {
                        if(!_this.hovered) {
                            _this.$switcherList.detach();
                        }
                    }, 250);
                })
                .appendTo(this.$element);
                
            this.$dirtyFlag.appendTo(this.$element);
            
            this.on("remove", function() {
                _this.$switcherList.remove();
            });
        },
        reset: function() {
            
        }
    };
    
    return constr;
    
}(jQuery));


pinion.backend.input.Radiobutton = (function($) {
    
    var constr;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Radiobutton'></div>");
        
        if(settings.bigger) {
            this.$element.addClass("pinion-biggerInput");
        }
        
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        
        // as default the infoKey is the same as the label
        if(settings.infoKey === undefined) {
            if(settings.label != "") {
                settings.infoKey = settings.label;
            } else {
                settings.infoKey = "value";
            }   
        }
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.Radiobutton,
        defaultSettings: {
            data: [],
            value: null,
            idAsDescription: true,
            bigger: false
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                data = settings.data;
            
            if(settings.label) {
                this.$label = $("<label class='pinion-label'>"+pinion.translate(settings.label)+"</label>").prependTo(this.$element);
            }
            
            var length = data.length;
            if(length > 0) {
                var description = null;
                if(settings.idAsDescription) {
                    description = "id";
                }
                for(var name in data[0]) {
                    if(name == "id") continue;
                    description = name;
                    break;
                }
                
                
                for(var i = 0; i < length; i++) {
                    var d = data[i];
                    var $wrapper = $("<div></div>");

                    var $radiobutton = $("<div class='pinion-radiobutton' data-id='"+d.id+"'><div class='pinion-backend-icon-check'></div></div>")
                        .click(function() {
                            var $this = $(this);

                            $this.parent().siblings().children(".pinion-radiobutton").removeClass("pinion-selected");
                            $this.addClass("pinion-selected");

                            var newValue = $this.attr("data-id");
                            settings.value = newValue;
                            _this.info[_this.settings.infoKey] = newValue;

                            if(_this.initSettings.value != newValue) {
                                _this.setDirty();
                            } else {
                                _this.setClean();
                            }
                        })
                        .appendTo($wrapper);

                    if(settings.value == d.id) {
                        $radiobutton.addClass("pinion-selected");
                    }

                    if(description) {
                        $("<div class='pinion-description'>"+d[description]+"</div>").appendTo($wrapper);
                    }

                    $wrapper.appendTo(this.$inputWrapper);
                }

                this.$warning = $("<div class='pinion-backend-warning'></div>").appendTo(this.$inputWrapper);

                this.$dirtyFlag.appendTo(this.$element);
            }
            
            
        },
        reset: function() {
            this.$element.find(".pinion-radiobutton").removeClass("pinion-selected");
            if(this.settings.value) {
                this.$element.find(".pinion-radiobutton[data-id='"+this.settings.value+"']");
            }
        }
    };
    
    return constr;
    
}(jQuery));
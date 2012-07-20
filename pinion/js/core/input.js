

pinion.namespace("backend.input");


pinion.backend.input.Textbox = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.build(settings);
        this.labelAsInfoKey(settings);
    };
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.input.Textbox,
        defaultSettings: {
            value: "",
            password: false,
            bigger: false
        },
        build: function(settings) {
            
            this.$element = $("<div class='pinion-backend-input-Textbox'></div>");
            
            if(settings.bigger) {
                this.$element.addClass("pinion-biggerInput");
            }
            settings.value = settings.value == null ? "" : settings.value;
            
            this.$input = $("<input type='"+(settings.password ? "password" : "text")+"' value='"+settings.value+"'></input>");
            this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;

            if(settings.value == null || settings.value == undefined) {
                settings.value = "";
            }
        },
        labelAsInfoKey: function(settings) {
            // as default the infoKey is the same as the label
            if(settings.infoKey === undefined) {
                if(settings.label != "") {
                    settings.infoKey = settings.label;
                } else {
                    settings.infoKey = "value";
                }   
            }
        },
        init: function() {
            var _this = this,
                settings = this.settings,
                dirty = settings.dirty;
            
            // LABEL
            if(this.settings.label) {
                this.$label = $("<label class='pinion-label'>"+pinion.translate(this.settings.label)+"</label>").prependTo(this.$element);
            }
            
            this.$input
                .keyup(function(event) {
                    if(event.which == 9) return; // return on key tab

                    var myTime = new Date().getTime();
                    _this.time = myTime;
                    setTimeout(function() {
                        if(myTime == _this.time) {
                            var val = _this.$input.val();
                            
                            _this.settings.value = val;
                            _this.info[_this.settings.infoKey] = val;
                            if(val !== _this.initSettings.value) {
                                _this.setDirty();
                            } else {
                                _this.setClean();
                            }
                        }
                    }, 500);
                })
                .appendTo(this.$inputWrapper);
            
            
            this.$dirtyFlag.appendTo(this.$element);
            
            this.$warning = $("<div class='pinion-backend-warning'></div>").appendTo(this.$inputWrapper);
            
            if(this.settings.validators.notEmpty !== undefined) {
                this.$element.addClass("required");
            } 
             
            if(this.validators.sameAs !== undefined) {
                var compareElement = this.backend.elements[this.validators.sameAs];

                compareElement.on("change", function() {
                    _this.validate();
                });
            }
        },
        val: function(text) {
            var $input = this.$input;
            
            if(text === undefined) {
                return $input.val();
            } else {
                $input.val(text).keyup();
                return this;
            }
        },
        validate: function(value) {
            var _this = this;
            
            if(value === undefined) {
                value = this.$input.val();
            }
            
            if(this.validators.notEmpty !== undefined) {
                if($.trim(value) === "") {
                    return this.setInvalid("This field is required");
                }
            }
            if(this.validators.file !== undefined) {
                if(!/^[\w]+$/.test(value)) {
                    return this.setInvalid("This field only accept the characters [0-9A-Za-z_]");
                }
            }
            if(this.validators.email !== undefined) {
                if(!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(value)) {
                    return this.setInvalid("This field must contain a valid email-address");
                }
            }
            if(this.validators.minChars !== undefined) {
                if(value.length < this.validators.minChars) {
                    return this.setInvalid("This field must contain "+this.validators.minChars+" chars at minimum");
                }
            }
            if(this.validators.maxChars !== undefined) {
                if(value.length > this.validators.minChars) {
                    return this.setInvalid("This field may contain "+this.validators.minChars+" chars at maximum");
                }
            }
            if(this.validators.onlyNumbers !== undefined) {
                if(!/^\d*$/.test(value)) {
                    return this.setInvalid("This field only takes numbers");
                }
            }
            if(this.validators.sameAs !== undefined) {
                var compareElement = this.backend.elements[this.validators.sameAs];
                
                if(compareElement.$input.val() != value) {
                    return this.setInvalid("This field don't match with '"+compareElement.settings.label+"'");
                }
            }
            if(this.validators.events !== undefined) {
                pinion.ajax(pinion.addInfo(this.validators.events, this.info), function(data) {
                    if(data.valid !== true) {
                        return _this.setInvalid(data.valid);
                    }
                    return _this.setValid();
                });
            } else {
                return this.setValid();
            }
        },
        reset: function() {
            this.$input.val(this.initSettings.value);
        }
    };
    
    
    return constr;
    
}(jQuery));






// AutoCompleteTextbox

pinion.backend.input.AutoCompleteTextbox = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.build(settings);
        this.labelAsInfoKey(settings);
    };
    
    pinion.inherit(constr, pinion.backend.input.Textbox);
    
    constr.prototype.defaultSettings = {
        value: "",
        data: [],
        bigger: false
    };
    constr.prototype.init = function() {
        constr.uber.init.call(this);
        
        this.addAutoComplete();
    };
    constr.prototype.addAutoComplete = function() {
        var _this = this;
        
        if(!Array.isArray(this.settings.data)) {
            pinion.ajax(this.settings.data, function(data) {
                _this.settings.data = data.data;
                _this.initSettings.data = data.data;
                
                _this.$input.autocomplete({
                    appendTo: _this.backend.$element,
                    source: _this.settings.data
                });
            });
        } else {
            _this.$input.autocomplete({
                appendTo: _this.backend.$element,
                source: _this.settings.data
            });
        }
        
        
    };
    
    
    return constr;
    
}(jQuery));









// TranslationTextbox

pinion.backend.input.TranslationTextbox = (function($) {
    
    var constr,
        supportedLanguages = pinion.php.supportedLanguages,
        mainLanguage = supportedLanguages[0],
        Textbox = pinion.backend.input.Textbox.prototype;
    
    // public API -- constructor
    constr = function(settings) {
        Textbox.build.call(this, settings);
        Textbox.labelAsInfoKey.call(this, settings);
        
        this.$element
            .removeClass("pinion-backend-input-Textbox")
            .addClass("pinion-backend-input-TranslationTextbox");
    };
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.input.TranslationTextbox,
        defaultSettings: {
            value: "",
            password: false,
            bigger: false
        },
        init: function() {
            
            // LABEL
            if(this.settings.label) {
                this.$label = $("<label class='pinion-label'>"+pinion.translate(this.settings.label)+"</label>").prependTo(this.$element);
            }
            
            this.createEditor("Textbox");
            
            
            this.$warning = $("<div class='pinion-backend-warning'></div>").appendTo(this.$inputWrapper);
        },
        createEditor: function(name) {
            var _this = this,
                settings = this.settings,
                value = settings.value,
                translated = false,
                editors = [];
                
            if(value && typeof value === "object") {
                translated = true;
                value = settings.value = value.translations;
            }
            
            // the start info has got the value as information
            this.info[settings.infoKey] = settings.value;
            
            var $ul = $("<ul></ul>")
                .on("click", "li", function() {
                    var $this = $(this),
                        lang = $this.attr("data-id");
                        
                    $this.toggleClass("pinion-active");
                    
                    if(editors[lang] === undefined) {
                        var value = "";
                        if(translated) { 
                            if(settings.value[lang] !== undefined) {
                                value = settings.value[lang];
                            }
                        } else if(lang == mainLanguage) {
                            value = settings.value;
                        }
                        editors[lang] = _this.addChild({
                            name: name,
                            type: "input",
                            language: lang,
                            value: value
                        }).on("dirty", function() {
                            if(translated) {
                                settings.value[this.settings.language] = this.settings.value;
                            } else {
                                if(mainLanguage == this.settings.language) {
                                    settings.value = this.settings.value;
                                } else {
                                    translated = true;
                                    var currentValue = settings.value;
                                    settings.value = {};
                                    settings.value[mainLanguage] = currentValue;
                                    settings.value[this.settings.language] = this.settings.value;
                                }
                            }
                            _this.info[settings.infoKey] = settings.value;
                        });
                        editors[lang].$element.append("<div class='pinion-flag pinion-flag-"+lang+"'></div>");
                    } else if($this.hasClass("pinion-active")) {
                        editors[lang].show();
                    } else {
                        editors[lang].hide();
                    }
                    
                })
                .appendTo(this.$inputWrapper);
            for(var i = 0, length = supportedLanguages.length; i < length; i++) {
                var lang = supportedLanguages[i];
                $("<li data-id='"+lang+"'><div class='pinion-flag pinion-flag-"+lang+"'></div></li>")
                    .appendTo($ul);
            }
            
            // create editor for main language
            $ul.children(":first").click();
        }
    };
    
    
    return constr;
    
}(jQuery));



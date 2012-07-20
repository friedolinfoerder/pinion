pinion.namespace("backend.BackendDisplay");

pinion.backend.BackendDisplay = (function($) {
    
    var constr,
        modules = pinion.php.modules;
    
    // public API -- constructor
    constr = function(backend) {
        var _this = this,
            loader = null,
            info = backend.info || {},
            elements = backend.elements,
            $outerContainer = jQuery("<div class='pinion-backend-elements'><div class='pinion-backend-background'></div></div>"),
            $contentContainer = jQuery("<div class='pinion-backend-innerElementsContainer'></div>").appendTo($outerContainer),
            $scrollContainer = jQuery("<div class='pinion-backend-scrollContainer'></div>").appendTo($contentContainer),
            $innerContainer = jQuery("<div class='pinion-backend-innerContainer'></div>").appendTo($scrollContainer),
            $staticBottomContainer,
            $headline,
            i,
            length;
            
        $.extend(pinion.backend.BackendDisplay.prototype, pinion.EventDispatcher.prototype);
        this.handlers = {};
        
        this.name = backend.name;
        
        var module = modules[this.name];
        
        this.title = module.title;
        this.icon = module.icon;
        
        this.mode = "backend";
        
        this.slideClose = function(callback) {
            
            pinion.activeBackend = null;
            
            $headline
                .animate({
                    left: -$headline.width() -100 + "px"
                }, 500);
            $outerContainer
                .css({
                    width: $outerContainer.width() + "px",
                    right: "auto"
                })
                .animate({
                    left: window.innerWidth + $headline.width() + "px"
                }, 500, function() {
                    _this.$element.hide();
                    callback();
                });
        };
        this.slideOpen = function() {
            
            _this.$element.show();
            
            pinion.activeBackend = this.name;
            
            // animate element container
            $headline
                .css({
                    left: -$headline.width() + "px" 
                })
                .animate({
                    left: 0
                }, 800);
            
            $outerContainer
                .css({
                    width: window.innerWidth - _this.leftPosition - parseInt($outerContainer.css("padding-left"), 10) - parseInt($outerContainer.css("padding-right"), 10) + "px",
                    right: "auto",
                    left: window.innerWidth + $headline.width() + "px"
                })
                .animate({
                    left: _this.leftPosition + "px"
                }, 800, function() {
                    $outerContainer.css({
                        right: 0,
                        width: "auto"
                    });
                    _this.fire("open");
                });
                
        },
        this.toPosition = function() {
            
            $headline.css("left", 0);
            $outerContainer.css("left", _this.leftPosition);
            
            _this.fire("open");
        }
        
        this.dirtyElements = {};
        this.validations = {};
        this.elements = {};
        this.indices = {};
        this.tabs = {
            left: []
        };
        
        
        
        // add backend area
        this.$element = $("<div></div>")
            .addClass("pinion-backend-BackendDisplay")
            .addClass("pinion-font")
            .append($outerContainer)
            .appendTo(pinion.$backend)
            .bind("dirty.pinion", function() {
                $headline.addClass("dirty");
            })
            .bind("clean.pinion", function() {
                $headline.removeClass("dirty");
            });
        
                                
        $headline = $("<h1>"+pinion.translate(this.title)+"</h1>")
            .append(new pinion.Resetter(this))
            .appendTo(this.$element);
            
        var $iconSpan = $("<span class='iconWrapper'><img src='"+this.icon+"' /></span>")
            .click(function() {
                pinion.addShortcut({
                    name:  _this.name,
                    title: _this.title,
                    icon:  _this.icon
                });
            })
            .appendTo($headline);
        pinion.registerHelp($iconSpan, pinion.translate("add module %s to shortcuts", "<i>"+this.title+"</i>"));
        
        var $closeButton = $("<div id='pinion-backend-closeButton' class='pinion-backend-button'></div>")
            .append("<div class='pinion-backend-icon-close'></div>")
            .click(function() {
                pinion.backend.Overlay.hide();
                pinion.ajax({
                    event: "closeBackend",
                    module: "backend"
                });
                delete pinion.backendDisplays[_this.name];
                _this.slideClose(function() {
                    _this.$element.remove();
                });
            })
            .appendTo($outerContainer);
        
        pinion.registerHelp($closeButton, pinion.translate("close module %s", "<i>"+this.title+"</i>"));
        
        this.$closeButton = $closeButton;
        
        var $minimizeButton = $("<div id='pinion-backend-minimizeButton' class='pinion-backend-button'></div>")
            .append("<div class='pinion-backend-icon-minimize'></div>")
            .click(function() {
                pinion.backend.Overlay.hide();
                pinion.hideBackend(_this.name);
            })
            .appendTo($outerContainer);
            
        pinion.registerHelp($minimizeButton, pinion.translate("minimize module %s", "<i>"+this.title+"</i>"));
        
        var $refreshButton = $("<div id='pinion-backend-refreshButton' class='pinion-backend-button'></div>")
            .append("<div class='pinion-backend-icon-refresh'></div>")
            .click(function() {
                var $icon = $(this).children(),
                    loader = new pinion.Loader("darkblue");
                // set the loader image and save the original image source
                
                $icon.hide().after(loader.$element);
                
                pinion.page("pinion/modules/"+_this.name+"/refresh", function() {
                    loader.remove();
                });
            })
            .appendTo($outerContainer);
        
        pinion.registerHelp($refreshButton, pinion.translate("refresh module %s", "<i>"+this.title+"</i>"));
        
        
        this.$outerContainer = $outerContainer;
        this.$childrenContainer = $innerContainer;
        this.$contentContainer = $contentContainer;
        
        // staticBottomContainer
        $staticBottomContainer = $("<div class='pinion-backend-staticBottomContainer'></div>")
            .appendTo($outerContainer);
        
        // create save button
        var $iconSave = $("<div class='pinion-backend-icon-save'></div>");
        this.$submitButton = $("<div class='pinion-backend-BackendDisplay-save'></div>")
            .append($iconSave)
            .append("<div class='pinion-backend-BackendDisplay-saveText'>save</div>")
            .click(function() {
                if(_this.$submitButton.hasClass("disabled")) return;
                
                // add loader
                loader = new pinion.Loader("darkblue");
                _this.$submitButton.addClass("pinion-loading")
                $iconSave
                    .append(loader.$element);
                
                _this.$submitButton.addClass("disabled");
                _this.save();
                    
                // remove loader
                _this.one("saveDone", function() {
                    _this.$submitButton.removeClass("pinion-loading");
                    loader.remove();
                });
            })
            .addClass("disabled")
            .appendTo($staticBottomContainer);
        
        this
            .on("invalid", function() {
                this.$submitButton.addClass("disabled");
                if(loader != null) {
                    this.$submitButton.removeClass("pinion-loading");
                    loader.remove();
                }
            })
            .on("dirty", function() {
                if(pinion.isEmpty(this.validations)) {
                    _this.$submitButton.removeClass("disabled");
                }
            })
            .on("clean", function() {
                _this.$submitButton.addClass("disabled");
            })
            .on("savePossible", function() {
                _this.$submitButton.removeClass("disabled");
            });
        
        // create cancel button
        $("<div class='pinion-backend-BackendDisplay-cancel'></div>")
            .append("<div class='pinion-backend-icon-close'></div>")
            .append("<div class='pinion-backend-BackendDisplay-cancelText'>cancel</div>")
            .click(function() {
                $closeButton.click();
            })
            .appendTo($staticBottomContainer);
        
        // create infoarea
        $("<div class='pinion-backend-BackendDisplay-infoarea'></div>")
            .appendTo($staticBottomContainer);
        
        // reset the drag connections
        pinion.drag.connections = {};
        
        
            
        // BackendDisplay has also an identifier and is part of the elements
        this.identifier = "BackendDisplay_"+backend.name;
        this.elements[this.identifier] = this;
        this.settings = $.extend(true, {}, this.globalElementSettings);
        this.backend = this;
        this.info = this.settings.info;
        this.parent = null;
        this.children = [];
        this._isDirty = false;
        this._isChanged = false;
        this._isValid = null;
        
        // loop through all elements
        this.addElements(elements, this);
        
        
        // loop through all tabs
        var tabsLeft = this.tabs.left;
        length = tabsLeft.length;
        if(length > 0) {
            var $tabsLeft = jQuery("<ul id='pinion-backend-tabs-left'></ul>").appendTo($outerContainer);
            this.$tabsLeft = $tabsLeft;
            
            var $tabOverlapHelper = $("<div class='pinion-backend-BackendDisplay-tabOverlapHelper'></div>");
            
            // create menu for left tabs
            for(i = 0; i < length; i++) {
                var tab = tabsLeft[i];
                var $li = $("<li data-title='"+tab.title.toLowerCase()+"'>"+pinion.translate(tab.title)+"</li>")
                    .click({element: tab.element, $element: tab.element.$element}, function(event) {
                        var $this = $(this);
                        
                        if(!$this.hasClass("active")) {
                            event.data.element.fire("openTab");
                        }
                        
                        $this
                            .addClass("active")
                            .siblings()
                                .removeClass("active");
                                
                        event.data.$element
                            .show()
                            .siblings(".pinion-backend-tab")
                                .hide();
                        
                        $tabOverlapHelper.css("top", $this.parent().position().top + $this.position().top + 1); // + 1 because of the border
                    })
                    .mouseenter(function() {
                        if(pinion.drag.active) {
                            $(this).click();
                            // refresh the dropzones one time
                            $(".pinion-draggable")
                                .sortable("refresh")
                                .sortable("refreshPositions");
                        }
                    })
                    .append(new pinion.Resetter(tab.element))
                    .appendTo($tabsLeft);
                
                
                tab.element.$tab = $li;
            }
            
            $tabOverlapHelper
                .height($li.height())
                .appendTo($outerContainer);
            
            
            if(info.tab) {
                // click on the desired tab
                $tabsLeft.children("[data-title='"+info.tab+"']").click();
            } else {
                // show first tab container
                $tabsLeft.children(":first").click();
            }
            
        }
        
        this.leftPosition = ($tabsLeft ? $tabsLeft.width() : 0) + 40;
        
            
        $("<div class='pinion-whiteGradient'></div>")
            .appendTo($staticBottomContainer);
    }
    
    // public API -- prototype
    pinion.inherit(constr, pinion.backend.ElementBase); // inherit from ElementBase
    
    constr.prototype.addTab = function(position, title, element) {
        this.tabs[position].push({
            title: title,
            element: element
        });
    };
    constr.prototype.openTab = function(title) {
        this.$tabsLeft.children("[data-title='"+title.toLowerCase()+"']").click();
    };
    
    return constr;
    
}(jQuery));


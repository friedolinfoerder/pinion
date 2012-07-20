
// create namespace
pinion.namespace("backend.group");


pinion.backend.plugins.singleDirty = function() {
    var children = this.children;
    for(var i = children.length; i--; ) {
        children[i]
            .on("dirty", function() {
                var siblings = this.siblings();
                for(var j = siblings.length; j--; ) {
                    siblings[j].resetElement();
                }
            });
    }
};


pinion.backend.group.MainTab = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.settings = settings;
        
        this.$element = $("<div class='pinion-backend-tab'></div>");
        
        backend.addTab("left", settings.title, this);
    };
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.MainTab,
        init: function() {
            this
                .on("dirty", function() {
                    this.$tab.addClass("dirty");
                })
                .on("clean", function() {
                    this.$tab.removeClass("dirty");
                })
                .on("invalid", function() {
                    this.$tab.addClass("invalid");
                })
                .on("valid", function() {
                    this.$tab.removeClass("invalid");
                });
        }
    }
    return constr;
    
}(jQuery));



pinion.backend.group.LazyMainTab = (function($) {
    
    var constr;
    
    constr = function(settings, backend) {
        this.settings = settings;
        
        this.$element = $("<div class='pinion-backend-tab'></div>");
        
        backend.addTab("left", settings.title, this);
        
        this.elements = [];
        this.hasLoaded = false;
        this.backendOpen = false;
        this.tabClicked = false;
    };
    
    // inherit from MainTab
    pinion.inherit(constr, pinion.backend.group.MainTab);
    
    constr.prototype.init = function() {
        // call init function from MainTab
        constr.uber.init.call(this);
        
        this.on("openTab", function() {
            this.tabClicked = true;
            if(this.backendOpen) {
                this.start();
            }
        });

        this.backend.on("open", function() {
            this.backendOpen = true;
            if(this.tabClicked) {
                this.start();
            }
        }, this);
    };
    constr.prototype.start = function() {
        this.backend.addElements(this.elements, this);

        // remove event listener
        this.backend.off("open", this);
        this.off("openTab");
    }
    
    return constr;
}(jQuery));


pinion.backend.group.TabGroup = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.$tabTitles = $("<ul class='pinion-backend-group-TabGroup-titles'></ul>");
        this.$childrenContainer = $("<div class='pinion-backend-group-TabGroup-tabs'></div>");
        
        this.$element = $("<div class='pinion-backend-group-TabGroup'></div>")
            .append(this.$childrenContainer);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.TabGroup,
        defaultSettings: {
            tabPosition: "top"
        },
        init: function() {
            var _this = this,
                first = true;
            for(var i = 0, length = this.children.length; i < length; i++) {
                var child = this.children[i];
                
                if(child instanceof pinion.backend.group.TitledSection) {
                    
                    this.addToGroup(child, first);  
                    first = false;
                }
            }
            
            this.on("childAdded", function(data) {
                this.addToGroup(data.element, true);
            });
            
            if(this.settings.tabPosition == "bottom") {
                this.$element.append(this.$tabTitles);
            } else {
                this.$element.prepend(this.$tabTitles);
            }
        },
        addToGroup: function(element, show) {
            var _this = this;
            
            var $li = $("<li></li>")
                .append(element.$h2)
                .appendTo(this.$tabTitles)
                .click({$element: element.$element.hide()}, function(event) {
                    var $this = $(this);

                    _this.fire("changeContent", {headline: $this.text()});

                    $this
                        .addClass("active")
                        .siblings()
                            .removeClass("active");

                    event.data.$element
                        .show()
                            .siblings(".pinion-backend-group-TitledSection")
                                .hide();
                });
                
            if(show) {
                $li.click();
            }
        }
    }
    return constr;
    
}(jQuery));




pinion.backend.group.LazyTabGroup = (function($) {
    
    var constr;
    
    constr = function(settings, backend) {
        this.settings = settings;
        
        this.$tabTitles = $("<ul class='pinion-backend-group-TabGroup-titles'></ul>");
        this.$childrenContainer = $("<div class='pinion-backend-group-TabGroup-tabs'></div>");
        
        this.$element = $("<div class='pinion-backend-group-TabGroup'></div>")
            .append(this.$tabTitles)
            .append(this.$childrenContainer);
        
        this.elements = [];
        this.tabElements = {};
        this.loadedTabs = {};
    };
    
    // inherit from TabGroup
    pinion.inherit(constr, pinion.backend.group.TabGroup);
    
    constr.prototype.init = function() {
        var _this = this;
        
        // find children
        this.findChildren();
        
        // load first tab
        if(!pinion.isEmpty(this.tabElements)) {
            this.loadTab(0);
        }
        
        
        // call init function from MainTab
        constr.uber.init.call(this);
        
        // add click handlers (for creating the elements)
        this.$tabTitles.children().each(function(index) {
            $(this).click({index: index}, function(event) {
                // load tab with the given index
                _this.loadTab(event.data.index);
            });
        });
    };
    constr.prototype.findChildren = function() {
        var elements = this.elements,
            element,
            index = -1,
            identifier = this.identifier,
            tabElements = {};
        
        for(var i = 0, length = elements.length; i < length; i++) {
            element = elements[i];
            if(element.parent == identifier) {
                // add child
                element = this.addChild(element);
                index++;
                tabElements["tab_"+index] = [];
            } else {
                // add settings to tab elements
                tabElements["tab_"+index].push(element);
            }
        }
        
        this.tabElements = tabElements;
    };
    constr.prototype.loadTab = function(index) {
        if(this.loadedTabs["tab_"+index] === undefined) {
            this.loadedTabs["tab_"+index] = true;
            this.backend.addElements(this.tabElements["tab_"+index], this.children[index]);
        }
    };
    
    return constr;
}(jQuery));




pinion.backend.group.SelectGroup = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        
        var $selectTitlesWrapper = $("<div class='pinion-backend-group-SelectGroup-titles'></div>");
        this.addLabel(settings, $selectTitlesWrapper);
        this.$selectTitles = $("<select></select>").appendTo($selectTitlesWrapper);
        this.$childrenContainer = $("<div class='pinion-backend-group-SelectGroup-tabs'></div>");
        
        this.$element = $("<div class='pinion-backend-group-SelectGroup'></div>")
            .append($selectTitlesWrapper)
            .append(this.$childrenContainer);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.SelectGroup,
        defaultSettings: {
            translate: true
        },
        addLabel: function(settings, $to) {
            if(settings.label) {
                var label = settings.translate ? pinion.translate(settings.label) : settings.label;
                $to.append("<div class='pinion-label'>"+label+"</div>");
            }
        },
        init: function() {
            var _this = this,
                first = true;
                
            this.$selectTitles
                .change(function() {
                    var $selected = $(this).children(":selected");
                    _this.fire("changeContent", {headline: $selected.val()});
                    $selected.data("$selectElement")
                        .show()
                            .siblings(".pinion-backend-group-TitledSection")
                                .hide();
                });
                
                
            for(var i = 0, length = this.children.length; i < length; i++) {
                var child = this.children[i];
                
                if(child instanceof pinion.backend.group.TitledSection) {
                    this.addToGroup(child, first);
                    first = false;
                }
            }
                
            this.on("childAdded", function(data) {
                this.addToGroup(data.element, true);
            });
        },
        addToGroup: function(element, show) {
            if(element instanceof pinion.backend.group.TitledSection) {
                var $option = $("<option></option>")
                    .text(element.$h2.hide().text())
                    .attr("selected", !!show) // add !! to make sure it's an boolean
                    .appendTo(this.$selectTitles)
                    .data("$selectElement", element.$element.hide());
                    
                if(show) {
                    this.$selectTitles.change();
                }
            }
        }
    }
    return constr;
    
}(jQuery));






pinion.backend.group.LazySelectGroup = (function($) {
    
    var constr,
        LazyTabGroup = pinion.backend.group.LazyTabGroup.prototype;
    
    constr = function(settings, backend) {
        this.settings = settings;
        
        var $selectTitlesWrapper = $("<div class='pinion-backend-group-SelectGroup-titles'>");
        this.addLabel(settings, $selectTitlesWrapper);
        this.$selectTitles = $("<select></select>").appendTo($selectTitlesWrapper);
        this.$childrenContainer = $("<div class='pinion-backend-group-SelectGroup-tabs'></div>");
        
        this.$element = $("<div class='pinion-backend-group-SelectGroup'></div>")
            .append($selectTitlesWrapper)
            .append(this.$childrenContainer);
        
        this.elements = [];
        this.tabElements = {};
        this.loadedTabs = {};
    };
    
    // inherit from SelectGroup
    pinion.inherit(constr, pinion.backend.group.SelectGroup);
    
    constr.prototype.init = function() {
        var _this = this;
        
        // find children
        LazyTabGroup.findChildren.call(this);
        
        // load first tab
        if(!pinion.isEmpty(this.tabElements)) {
            LazyTabGroup.loadTab.call(this, 0);
        }
        
        // call init function from MainTab
        constr.uber.init.call(this);
        
        // add select handler (for creating the elements)
        this.$selectTitles
            .change(function() {
                LazyTabGroup.loadTab.call(_this, $(this).children(":selected").index());
            });
    };
    
    return constr;
}(jQuery));





pinion.backend.group.Accordion = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        
        this.$element = $("<div class='pinion-backend-group-Accordion'></div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.Accordion,
        init: function() {
            var first = true;
            for(var i = 0, length = this.children.length; i < length; i++) {
                var child = this.children[i];
                if(child instanceof pinion.backend.group.TitledGroup) {
                    if(!first) {
                        child.close();
                    } else {
                        child.$element.addClass("active");
                        first = false;
                    }
                    
                    child.$h2.click(function() {
                        var $this = $(this);
                        var $parent = $this.parent();
                        if(!$parent.hasClass("active")) {
                            $parent
                                .addClass("active")
                                .siblings(".pinion-backend-group-TitledGroup")
                                    .filter(function() {
                                        return $(this).data("open");
                                    })
                                    .children("h2")
                                        .click()
                                        .end()
                                    .removeClass("active");
                        }
                    });
                }
            }
        }
    }
    return constr;
    
}(jQuery));





pinion.backend.group.LazyAccordion = (function($) {
    
    var constr,
        LazyTabGroup = pinion.backend.group.LazyTabGroup.prototype;
    
    constr = function(settings, backend) {
        this.$element = $("<div class='pinion-backend-group-Accordion'></div>");
        
        this.elements = [];
        this.tabElements = {};
        this.loadedTabs = {};
    };
    
    // inherit from Accordion
    pinion.inherit(constr, pinion.backend.group.Accordion);
    
    constr.prototype.init = function() {
        var _this = this;
        
        // find children
        LazyTabGroup.findChildren.call(this);
        
        // load first tab
        LazyTabGroup.loadTab.call(this, 0);
        
        // call init function from MainTab
        constr.uber.init.call(this);
        
        // add click handlers (for creating the elements)
        var children = this.children;
        for(var i = 0, length = children.length; i < length; i++) {
            children[i].$element.click({index: i}, function(event) {
                // load tab with the given index
                LazyTabGroup.loadTab.call(_this, event.data.index);
            });
        }
    };
    
    return constr;
}(jQuery));





pinion.backend.group.TitledSection = (function($) {
    
    var constr;

    
    // public API -- constructor
    constr = function(settings) {
        this.build(settings);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.TitledSection,
        defaultSettings: {
            translate: true
        },
        build: function(settings) {
            if(settings.title == null || settings.title == undefined) {
                settings.title = "";
            } else if(settings.translate) {
                settings.title = pinion.translate(settings.title.toString());
            }

            this.$h2 = $("<h2><span class='pinion-text'>"+settings.title+"</span></h2>");
            this.$element = $("<div class='pinion-backend-group-TitledSection'></div>")
                .append(this.$h2);

            this.$childrenContainer = $("<div></div>").appendTo(this.$element);
        },
        init: function() {
            this.$dirtyFlag.appendTo(this.$h2);
        }
    }
    return constr;
    
}(jQuery));




pinion.backend.group.Section = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-group-Section'></div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.Section,
        init: function() {
        }
    }
    return constr;
    
}(jQuery));






pinion.backend.group.Wrapper = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = settings.$element;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.Wrapper,
        init: function() {
            this.$dirtyFlag.remove();
        }
    }
    return constr;
    
}(jQuery));







pinion.backend.group.TitledGroup = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings) {
        this.build(settings);
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.TitledGroup,
        defaultSettings: {
            speed: 500,
            open: true,
            translate: true
        },
        build: function(settings) {
            var _this = this;
        
            this.$element = $("<div class='pinion-backend-group-TitledGroup expanded'></div>").data("open", true);


            this.$iconSpan = $("<span class='pinion-backend-icon-arrowDown-darkBlue'></span>")
                .hover(function() {
                        var $this = $(this);
                        if(_this.$element.hasClass("expanded")) {
                            $this.attr("class", "pinion-backend-icon-arrowRight-darkBlue");
                        } else {
                            $this.attr("class", "pinion-backend-icon-arrowDown-darkBlue");
                        }
                        $this.addClass("pinion-opacity50");
                    }, function() {
                        var $this = $(this);
                        if(_this.$element.hasClass("expanded")) {
                            $this.attr("class", "pinion-backend-icon-arrowDown-darkBlue");
                        } else {
                            $this.attr("class", "pinion-backend-icon-arrowRight-darkBlue");
                        }
                        $this.removeClass("pinion-opacity50");
                    });
            
            settings.title = settings.translate ? pinion.translate(settings.title) : settings.title;
            
            this.$h2 = $("<h2><span class='pinion-text'>"+settings.title+"</span></h2>")
                .click(function() {
                    _this.open();
                })
                .prepend(this.$iconSpan)
                .appendTo(this.$element);

            this.$childrenContainer = $("<div></div>").appendTo(this.$element);
        },
        init: function() {
            this.$dirtyFlag.appendTo(this.$h2);
            
            if(!this.settings.open) {
                this.close();
            }
        },
        open: function() {
            var _this = this,
                $h2 = this.$h2,
                $next = $h2.next();

            if(this.$element.data("open")) {
                this.$element.data("open", false);
                $next.slideUp(this.settings.speed, function() {
                    _this.$element.removeClass("expanded");
                    _this.$iconSpan.attr("class", "pinion-backend-icon-arrowRight-darkBlue");
                });
            } else {
                this.$element.addClass("expanded").data("open", true);
                this.$iconSpan.attr("class", "pinion-backend-icon-arrowDown-darkBlue");
                $next.slideDown(this.settings.speed);
            }
        },
        close: function() {
            this.$element.removeClass("expanded").data("open", false);
            this.$childrenContainer.hide();
            this.$iconSpan.attr("class", "pinion-backend-icon-arrowRight-darkBlue");
        }
    }
    return constr;
    
}(jQuery));




pinion.backend.group.LazyTitledGroup = (function() {
    
    var constr;
    
    constr = function(settings) {
        settings.open = false;
        
        this.build(settings);
        
        this.hasLoaded = false;
        this.elements = [];
    };
    
    // inherit from TitledGroup
    pinion.inherit(constr, pinion.backend.group.TitledGroup);
    
    constr.prototype.open = function() {
        if(!this.hasLoaded) {
            this.hasLoaded = true;
            this.backend.addElements(this.elements, this);
        }
        constr.uber.open.call(this);
    };
    
    
    return constr;
    
}());





pinion.backend.group.ColumnGroup = (function($) {
    
    var constr;

    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-group-ColumnGroup'></div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.ColumnGroup,
        defaultSettings: {
            splitting: []
        },
        init: function() {
            var numChildren = this.children.length;
            
            var splitting = this.settings.splitting;
            if(!Array.isArray(splitting)) {
                splitting = this.settings.splitting.split(",");
            }
            var splittingLength = splitting.length;
            var splittingAddition = 0;            
            for(var i = 0; i < numChildren; i++) {
                if(splittingLength > i) {
                    splitting[i] = parseFloat(splitting[i], 10);
                } else {
                    splitting[i] = 1;
                }
                splittingAddition += splitting[i];
            }
            for(i = 0; i < numChildren; i++) {
                $("<div class='pinion-backend-group-ColumnGroup-columnWrapper'></div>")
                    .width(Math.floor(100 * splitting[i] / splittingAddition) + "%")
                    .append(this.children[i].$element)
                    .appendTo(this.$element);
            }
        }
    }
    return constr;
    
}(jQuery));




pinion.backend.group.StepGroup = (function($) {
    
    var constr;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.$tabTitle = $("<div class='pinion-backend-group-StepGroup-title'></div>");
            
        this.$childrenContainer = $("<div class='pinion-backend-group-StepGroup-steps'></div>");
        
        this.$element = $("<div class='pinion-backend-group-StepGroup'></div>")
            .append(this.$childrenContainer);
            
        this.steps = [];
        this.$stepCount = $("<span class='pinion-step-count'>/0</span>");
        this.$toRight = $("<div class='pinion-backend-icon-arrowRight'></div>");
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.group.StepGroup,
        defaultSettings: {
            tabPosition: "top"
        },
        init: function() {
            var children = this.children,
                length = children.length;
                
            for(var i = 0; i < length; i++) {
                var child = children[i];
                
                // update clone at the last one
                this.addToGroup(child, (i == length - 1));
            }
            
            this.on("childAdded", function(data) {
                this.addToGroup(data.element, true);
            });
        },
        addToGroup: function(element, update) {
            var steps = this.steps;
            steps.push({
                $number: pinion.numberize(steps.length+1, 13),
                $h2: element.$h2.detach(),
                $content: element.$element.hide()
            });
            this.$stepCount.text("/"+steps.length);
            if(steps.length > 1) {
                this.$toRight.show();
            }
            
            if(steps.length == 1) {
                element.$element.show();
                
                var _this = this,
                    currentStep = 0,
                    $toLeft,
                    $toRight = this.$toRight,
                    $stepNumber,
                    $numberBox,
                    $tabTitle = this.$tabTitle;

                // STEP NUMBER AND STEP COUNT
                $numberBox = $("<div class='pinion-numberBox'></div>")
                    .append(steps[currentStep].$number);

                $stepNumber = $("<div class='pinion-step-number'></div>")
                    .append($numberBox)
                    .append(this.$stepCount);


                $toLeft = $("<div class='pinion-backend-icon-arrowLeft'></div>")
                    .click(function() {
                        if(currentStep == steps.length - 1) {
                            $toRight.show();
                        }

                        // detach elements
                        steps[currentStep].$h2.detach();
                        steps[currentStep].$number.detach();
                        // hide content
                        steps[currentStep].$content.hide();

                        currentStep--;
                        _this.fire("changeContent", {headline: steps[currentStep].$h2.text()});

                        if(currentStep == 0) {
                            $toLeft.hide();
                        }

                        // append elements
                        steps[currentStep].$h2.appendTo($tabTitle);
                        steps[currentStep].$number.appendTo($numberBox);
                        // show content
                        steps[currentStep].$content.show();

                        // refresh clone
                        _this.$tabTitleClone.remove();
                        _this.$tabTitleClone = $tabTitle
                            .clone(true)
                            .appendTo(_this.$element);
                    })
                    .hide();

                $toRight
                    .click(function() {
                        if(currentStep == 0) {
                            $toLeft.show();
                        }

                        // detach elements
                        steps[currentStep].$h2.detach();
                        steps[currentStep].$number.detach();
                        // hide content
                        steps[currentStep].$content.hide();

                        currentStep++;
                        _this.fire("changeContent", {headline: steps[currentStep].$h2.text()});

                        if(currentStep == steps.length - 1) {
                            $toRight.hide();
                        }

                        // append elements
                        steps[currentStep].$h2.appendTo($tabTitle);
                        steps[currentStep].$number.appendTo($numberBox);
                        // hide content
                        steps[currentStep].$content.show();

                        // refresh clone
                        _this.$tabTitleClone.remove();
                        _this.$tabTitleClone = $tabTitle
                            .clone(true)
                            .appendTo(_this.$element);
                    })
                    .hide();

                $tabTitle
                    .append($toLeft)
                    .append($toRight)
                    .append($stepNumber)
                    .append(steps[currentStep].$h2);

                // add clone 
                this.$tabTitleClone = $tabTitle
                    .clone(true)
                    .appendTo(_this.$element);
                
                if(this.settings.tabPosition == "bottom") {
                    this.$element.append($tabTitle);
                } else {
                    this.$element.prepend($tabTitle);
                }
            }
            
            if(update) {
                // refresh clone
                this.$tabTitleClone.remove();
                this.$tabTitleClone = this.$tabTitle
                    .clone(true)
                    .appendTo(this.$element);
            }
        }
    }
    return constr;
    
}(jQuery));
pinion.namespace("backend.renderer.ModuleRenderer");

pinion.backend.renderer.ModuleRenderer = (function($) {
    
    var constr,
        disableText,
        enableText,
        DISABLED = 0,
        ENABLED  = 1,
        USABLE   = 2,
        modules = {},
        phpModules = pinion.php.modules,
        _currentCheckingContainer = {};
    
    // public API -- constructor
    constr = function(settings, backend) {
        var data = settings.data;
        this.data = settings.data;
        this.translatedTitle = pinion.translate(this.data.title);
        
        // variables for checking dependencies
        this.name = data.name;
        this.checked = false;
        this.backReferences = {};
        this.dependencies = data.dependencies;
        this.requiredFrom = {};
        this.status = data.enabled ? ENABLED : DISABLED;
        
        modules[this.name] = this;
        
        if(disableText === undefined) {
            disableText = pinion.translate("disable");
            enableText = pinion.translate("enable");
        }
        
        var _this = this,
            phpModule = phpModules[data.name],
            moduleLink = pinion.php.pinionUrl+"/module/"+data.name;
        
        this.$element = $("<div class='pinion-backend-renderer-ModuleRenderer'></div>")
            .append("<div class='pinion-colorfield-left'></div>")
            .append("<div class='pinion-colorfield-right'></div>");
        
        $("<div class='pinion-moduleIcon'></div>")
            .css("background-image", "url("+phpModule.icon+")")
            .appendTo(this.$element);
            
        
        if(data.enabled) {
            this.$element.addClass("enabled");
        }
        if(data.usable) {
            this.$element.addClass("usable");
        }
            
        // textWrapper
        var $textWrapper = $("<div class='pinion-textWrapper'></div>")
            .appendTo(this.$element);
           
        // VERSION   
        var $version = $("<div class='pinion-moduleVersion'>"+data.version+"</div>");
        
        // UPDATE
        if(data.update) {
            $("<span class='pinion-newVersion'>"+pinion.translate("update to %s", data.update)+"</span>")
                .one("click", function() {
                    pinion.ajax({
                        event: "installModule",
                        module: "modulesinfo",
                        info: {"module identifier": data.name}
                    }, function(d) {
                        $version.html(data.update);
                    });
                })
                .appendTo($version);
        }
        
        $("<div class='pinion-headlineWrapper'></div>")
            .append("<a class='pinion-moduleTitle' href='"+moduleLink+"' target='_blank'>"+phpModule.title+"</a>")
            .append($version)
            .append("<a class='pinion-moduleName' href='"+moduleLink+"' target='_blank'>"+data.name+"</a>")
            .appendTo($textWrapper);
        
        var categoryText = data.category;
        if(categoryText == "no category") {
            categoryText = "category: "+pinion.translate(categoryText);
        }
        
        var $category = $("<div class='pinion-category'><div class='pinion-backend-icon-category'></div>"+pinion.translate(data.category)+"</div>").appendTo($textWrapper); 
        if(data.category == "no category") {
            $category.addClass("pinion-noCategory");
        }
        
        // AUTHOR
        if(data.author) {
            $("<div class='pinion-module-author'><div class='pinion-backend-icon-author'></div><span class='pinion-text'>"+data.author+"</span></div>")
                .appendTo($textWrapper);
        }
        
        var $dependencies = $("<div class='pinion-dependencies'></div>").appendTo($textWrapper);
        var noDependencies = true;

        this.$dependenciesUl = $("<ul></ul>");
        if(!Array.isArray(this.dependencies)) {
            for(var module in this.dependencies) {
                var depsInfo = this.dependencies[module];
                noDependencies = false;
                var $li = $("<li><a href='"+pinion.php.pinionUrl+"/modules/"+module+"' target='_blank'>"+module+"</a></li>")
                    .appendTo(this.$dependenciesUl);

                if(!depsInfo.exist) {
                    $li.addClass("unavailable");
                } else {
                    if(depsInfo.usable) {
                        $li.addClass("usable");
                    }
                    if(depsInfo.enabled) {
                        $li.addClass("enabled");
                    }
                }
            }
        } else {
            this.dependencies = {};
        }
        if(noDependencies) {
            $dependencies
                .append("<span class='pinion-noDeps'>"+pinion.translate("no dependencies")+"</span>");
        } else {
            $dependencies
                .append("<span>"+pinion.translate("depends on:")+"</span>")
                .append(this.$dependenciesUl);
        }
        
        if(this.data.description) {
            $textWrapper.append("<div class='pinion-moduleDescription'>"+pinion.translate(this.data.description)+"</div>");
        } else {
            $textWrapper.append("<div class='pinion-noDescription'>"+pinion.translate("no description available")+"</div>");
        }
        
        if(this.data.core) {
            $("<div class='pinion-coreSign'><span class='pinion-hide'>core</span></div>").appendTo(this.$element);
        } else {
            var enabled = this.data.enabled,
                installed = this.data.installed,
                allowed = false;
            if(!enabled && pinion.hasPermission("modulesinfo", "enable module") && (installed || pinion.hasPermission("modulesinfo", "install module"))) {
                allowed = true;
            } else if(enabled && pinion.hasPermission("modulesinfo", "disable module")) {
                allowed = true;
            }
            if(allowed) {
                var options = [],
                    $option = $("<a class='pinion-moduleOption'><div class='pinion-text'>"+(this.data.enabled ? disableText : enableText)+"</div><div class='pinion-icon'></div></a>")
                    .click(function() {
                        var $this = $(this);

                        _this.data.enabled = !_this.data.enabled;

                        $this.toggleClass("clicked");
                        _this.$element.toggleClass("enabled");

                        if(_this.data.enabled) {
                            $this.children(".pinion-text").text(disableText);
                        } else {
                            $this.children(".pinion-text").text(enableText);
                        }

                        if(_this.$element.hasClass("dirty")) {
                            _this.setClean();
                        } else {
                            _this.setDirty();
                            if(_this.data.enabled) {
                                _this.settings.events[0].event = "enableModule";
                            } else {
                                _this.settings.events[0].event = "disableModule";
                            }
                        }


                        // CHECK IF MODULE IS USABLE
                        _currentCheckingContainer = {};
                        // reset stati and check states of modules
                        for(var i in modules) {
                            modules[i].checked = false;
                            modules[i].backReferences = {};
                            modules[i].resetStatus();
                        }

                        // check dependencies (ported from php)
                        if(_this.data.enabled) {
                            for(i in modules) {
                                modules[i].check(_this);
                            }
                            _this.fire("enable");

                        } else {
                            _this.fire("disable");
                            _this.setUnusable(_this);
                        }

                    });

                options.push($option);
            }
            
                
            if(!enabled && installed && pinion.hasPermission("modulesinfo", "uninstall module")) {
                var $delete = pinion.data.Delete.call(this, settings.data, function() {
                    if(enabled) {
                        $option.click();
                    }
                    _this.settings.events[0].event = "uninstallModule";
                    _this.fadeOut(300, function() {
                        _this.setDirty();
                    });
                });
                options.push($delete);
            }
            
            if(!pinion.isEmpty(options)) {
                // RENDERER BAR
                pinion.data.Bar.call(this, options);
            }
        }
        
        pinion.data.Timeinfo.call(this, settings.data);
        
    }
    
       // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.ModuleRenderer,
        init: function() {
            
            this.parent.on("afterDataSet", "setDependencies", this);
            
            
            this.settings.events.unshift({
                module: "modulesinfo",
                info: {
                    modules: [this.data.name]
                }
            });
        },
        setDependencies: function() {
            var _this = this;
            
            for(var module in this.dependencies) {
                if(modules[module] !== undefined) {
                    this.dependencies[module] = modules[module];
                    modules[module].requiredFrom[this.name] = this;
                } else {
                    this.dependencies[module] = null;
                }
            }
            
            this.$dependenciesUl.children().each(function() {
                var $this = $(this);
                var module = $this.text();
                if(_this.dependencies[module] != null) {
                    _this.dependencies[module]
                        .on("enable", {$li: $this}, function(info, data) {data.$li.addClass("enabled");})
                        .on("disable", {$li: $this}, function(info, data) {data.$li.removeClass("enabled");})
                        .on("usable", {$li: $this}, function(info, data) {data.$li.addClass("usable");})
                        .on("unusable", {$li: $this}, function(info, data) {data.$li.removeClass("usable");});
                }
            });
        },
        hasDependency: function(moduleContainer) {
            return (this.dependencies[moduleContainer.name] !== undefined);
        },
        hasBackReference: function(moduleContainer) {
            return (this.backReferences[moduleContainer.name] !== undefined);
        },
        addBackReference: function(moduleContainer) {
            this.backReferences[moduleContainer] = moduleContainer;
        },
        startChecking: function() {
            _currentCheckingContainer[this.name] = true;
        },
        stopChecking: function() {
            delete _currentCheckingContainer[this.name];
        },
        isChecking: function() {
            return (_currentCheckingContainer[this.name] !== undefined);
        },
        setUsable: function(startElement) {
            this.status = USABLE;
            if(!this.$element.hasClass("usable")) {
                
                this.$element.addClass("usable");
                this.fire("usable");
                
                if(startElement != this) {
                    pinion.showMessage({
                        type:"success",
                        text: pinion.translate("<b>"+this.translatedTitle+" will work</b> now.")
                    });
                }
            }
        },
        setUnusable: function(startElement) {
            
            this.$element.removeClass("usable");
            this.fire("unusable");

            for(var module in this.requiredFrom) {
                var element = this.requiredFrom[module];
                if(element.$element.hasClass("usable")) {
                    // set all modules unusable, which are depends on this module
                    this.requiredFrom[module].setUnusable(startElement);
                    
                    pinion.showMessage({
                        type:"warning",
                        text: pinion.translate("<b>"+modules[module].translatedTitle+" will not work</b> anymore.")
                    });
                }

            }
        },
        resetStatus: function() {
            
            this.status = this.data.enabled ? ENABLED : DISABLED;
        },
        check: function(startElement) {
            // start the checking, so every other container knows, that this
            // container is checking now
            this.startChecking();
            
            if(this.status == DISABLED) {

                this.stopChecking();
                return false;
            } else if(this.checked) {
                
                this.stopChecking();
                return (this.status == USABLE);
            }
            
            this.checked = true;

            var backReferenceIsSet = false;
            
            for(var dependency in this.dependencies) {
                // difference from php (start)
                dependency = this.dependencies[dependency];
                // difference from php (end)
                if(dependency == null) {
                    // if the dependency does not exists, the module is unusable

                    this.stopChecking();
                    return false;
                } else if(dependency.isChecking()) {
                    // if the dependency is currently checking, this particular dependency is circular
                    // and so it is temporarly usable
                    // because it is only a temporarly state, you must reset the checked state
                    this.checked = false;
                    // continue with the next dependency
                    continue;
                } else if(dependency.hasDependency(this)) {
                    // only add backReference, when the dependency doesn't have
                    // this backReference
                    if(!dependency.hasBackReference(this)) {
                        // now add the backReference to the this moduleContainer
                        this.addBackReference(dependency);
                        // call this function recursively
                        if(!dependency.check(startElement)) {

                            this.stopChecking();
                            return false;
                        }
                    } else {
                        // if there is a backReference, avoid setting this
                        // moduleContainer directly to usable
                        backReferenceIsSet = true;
                    }
                // call this function recursively
                } else if(!dependency.check(startElement)) {

                    this.stopChecking();
                    return false;
                }
        
            }
            
            // iterate over all backReferences and make all backReferences usable,
            // because this moduleContainer is also usable
            for(var backReference in this.backReferences) {
                this.backReferences[backReference].setUsable(startElement);
            }
            // if there is no backReference set, the status can set directly to usable
            if(!backReferenceIsSet) {
                this.setUsable(startElement);
            }
            this.stopChecking();
            return true;
        }
    }
    
    return constr;
    
}(jQuery));



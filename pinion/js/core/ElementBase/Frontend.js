

jQuery(function($) {
    new pinion.Frontend().init();
    
});


pinion.Frontend = (function($) {
    
    var constr,
        phpContents = pinion.php.content,
        _this,
        inizializeChildren = function(elem) {        
            for(var i = 0, length = elem.children.length; i < length; i++) {
                var child = elem.children[i];
                
                // recursive call (children first)
                inizializeChildren(child);
                
                // call init function (if exists)
                if(child.init instanceof Function) {
                    child.init();
                }
                if(child.$warning) {
                    child.$warning.hide();
                }
            }
        };
    
    // public API -- constructor
    constr = function() {
        _this = this;
        
        this.contents = {};
        
        var loader,
            $iconSave = $("<div class='pinion-backend-icon-save'></div>"),
            $saveButton = $("<div id='pinion-Frontend-saveButton'></div>")
                .append($iconSave)
                .append("<span class='pinion-Frontend-saveText'>"+pinion.translate("save")+"</span>")
                .click(function() {
                    
                    if(_this.deletePage !== undefined) {
                        
                        // add loader
                        loader = new pinion.Loader("darkblue");
                        $saveButton
                            .addClass("pinion-loading");
                        $iconSave
                            .append(loader.$element);
                        
                        pinion.ajax({
                            module: "page",
                            event: "deletePage",
                            info: {
                                id: _this.deletePage
                            }
                        }, function() {
                            _this.setClean();
                            $saveButton.removeClass("pinion-loading");
                            loader.remove();
                        });
                        
                        return;
                    }
                    
                    var dirtyElements = _this.dirtyElements;
                    if(!pinion.isEmpty(dirtyElements)) {
                        
                        // collect ajax requests
                        pinion.ajax.collect = true;
                        
                        for(var module in dirtyElements) {
                            var dirtyElement = dirtyElements[module];
                            if(!dirtyElement._isDeleted && dirtyElement.saveClick instanceof Function) {
                                _this.dirtyElements[module].saveClick();
                            }
                        }
                        
                        // add loader
                        loader = new pinion.Loader("darkblue");
                        $saveButton
                            .addClass("pinion-loading");
                        $iconSave
                            .append(loader.$element);
                        
                        // remove loader
                        _this.one("clean", function() {
                            $saveButton.removeClass("pinion-loading");
                            loader.remove();
                        });
                    }
                    
                });
        
        this.$element = $("<div id='pinion-Frontend-saveButtonWrapper'></div>")
            .append("<div class='pinion-dragHandle'></div>")
            .append($saveButton)
            .append("<div class='pinion-dragHandle'></div>")
            .append(new pinion.Resetter(this))
            .draggable({
                handle: ".pinion-dragHandle",
                axis: "x",
                containment: "parent"
            })
            .css("position", "")
            .appendTo("body");
            
        pinion.Frontend.instance = _this;
    }
    
    constr.getClassName = function(moduleName) {
        var className = moduleName.substr(0, 1).toUpperCase()+moduleName.substr(1);
        if(!(pinion.backend.module[className] instanceof Function)) {
            className = "defaultModule";
        }
        return className;
    };
    constr.getModuleElement = function(moduleName, settings, $html, isNew) {
        if(settings === undefined) {
            settings = {};
        }
        
        if($html.is("a")) {
            // do not follow the link
            $html.click(function() {return false;});
        }
        
        var className = constr.getClassName(moduleName);
        
        _this.doInitialization = false;
        
        var module = _this.addElement($.extend({
            type: "module",
            moduleName: moduleName,
            name: className,
            content: {
                visible: false
            },
            data: {},
            vars: {},
            _isNew: (isNew === true)
        }, settings));
        
        module._isDeleted = false;
            
        _this.doInitialization = true;
        
        module.$contentElement = $html.first();
        pinion.Module.modulize(module);
        module.$contentElement.append(module.$element);
        module.$contentElement.data("element", module);
        
        $.extend(pinion.backend.module[className].prototype, pinion.backend.ElementBase.prototype);
        
        if(module.settings.content.id !== undefined) {
            _this.contents[module.settings.content.id] = module;
        }
        
        pinion.fire("createModule", module);
        
        return module;
    };
    constr.getContent = function(id) {
        return _this.contents[id];
    };
    
    // public API -- prototype
    pinion.inherit(constr, pinion.backend.ElementBase); // inherit from ElementBase
    $.extend(constr.prototype, pinion.EventDispatcher.prototype); // EventDispatcher mixin
    
    
    
    constr.prototype.init = function() {
        var _this = this;
        
        this.handlers = {};
        
        this.name = "FrontendDisplay";
        
        this.dirtyElements = {};
        this.validations = {};
        this.elements = {};
            
        // BackendDisplay has also an identifier and is part of the elements
        this.identifier = this.name;
        this.elements[this.identifier] = this;
        this.settings = $.extend(true, {}, this.globalElementSettings);
        this.backend = this;
        this.info = this.settings.info;
        this.parent = null;
        this.children = [];
        this._isDirty = false;
        this._isChanged = false;
        this._isValid = null;
        
            
        $(".cms").each(function() {
            
            var $this = $(this),
                classes = $this.attr("class"),
                matches = classes.match(/cms-(\d*)-([0-9A-Za-z_-]*)-([0-9A-Za-z_-]*)-(\d*)/);
                
            if(matches) {
                var contentId = matches[1],
                    areaName = matches[2],
                    moduleName = matches[3],
                    moduleId = matches[4],
                    content = pinion.php.content[contentId];
                
                content.html = $('<div>').append($this.clone()).remove().html();
                
                var module = constr.getModuleElement(moduleName, {
                    moduleId: moduleId,
                    contentId: contentId,
                    content: content.content,
                    data: content.data,
                    vars: content.vars
                }, $this);
            } else {
                matches = classes.match(/cms-area-([0-9A-Za-z_-]*)/);
                if(matches) {
                    var areaName = matches[1],
                        $placeholder = $this;
                        
                    new pinion.frontend.AreaPlaceholder($placeholder, areaName);
                    
                    pinion.fire("createArea", {area: areaName, $placeholder: $placeholder});
                }
            }
        });
        
        pinion.fire("frontendDone");
    };
    
    constr.replaceContent = function(id, html, identifier) {
        
        var oldElement;
        
        if(identifier === undefined) {
            oldElement = constr.getContent(id);
        } else {
            oldElement = _this.elements[identifier];
        }
        
        if(oldElement === undefined) {
            
            return false;
        }
        
        var $contentElement = oldElement.$contentElement,
            $newElementContent = $(html);
            
        _this.removeElement(oldElement);
        
        $contentElement
            .after($newElementContent)
            .remove();
        
        var phpContent = phpContents[id];
        
        var newElement = pinion.Frontend.getModuleElement(oldElement.settings.moduleName, {
            moduleId: phpContent.content.moduleid,
            contentId: id,
            content: phpContent.content,
            data: phpContent.data,
            vars: phpContent.vars
        }, $newElementContent.first());
        
        

        return newElement;
    };
    
    constr.removeContent = function(id) {
        
        var element = constr.getContent(id),
            $contentElement;
        
        if(element) {
            $contentElement = element.$contentElement;
            
            _this.removeElement(element);
            $contentElement.remove();
        }
        
        
    };
    
    
    
    return constr;
    
}(jQuery));



// create namespace 
pinion.namespace("frontend");



pinion.frontend.AreaPlaceholder = (function($) {
    
    var constr,
        FrontendBox = pinion.FrontendBox;
    
    constr = function($content, area) {
        
        this.content = {
            areaname: area
        };
        $content.data("element", this);
        
        // ADD ICON
        var $add = jQuery("<div class='pinion-backend-icon-add'></div>")
                .click(function() {
                    var $add = $(this);
                    if($add.hasClass("pinion-active")) {
                        FrontendBox.hide();
                    } else {
                        $add.addClass("pinion-active");
                        FrontendBox.show($add, pinion.frontend.$moduleMenu, {steps: 80});
                    }
                }),
            $addWrapper = $("<div class='pinion-frontend-AreaPlaceholder'></div>")
                .append($add)
                .appendTo($content);

        pinion.registerHelp($add, "Add a new content");

    };
    
    constr.prototype = {
        constructor: pinion.frontend.AreaPlaceholder
    }
    
    return constr;
    
}(jQuery));



pinion.frontend.$moduleMenu = (function($) {
    
    var modules = pinion.php.modules,
        $ul = $("<ul class='pinion-FrontendBox-modulelist'></ul>");
    
    $(function() {
        for(var i in modules) {
            var module = modules[i];

            if(module.isFrontend && pinion.hasPermission(i, "add")) {
                var $moduleBefore = $("<div class='pinion-addModule-before'><span class='pinion-frontend-menu-icon-arrow'></span></div>")
                        .click((function() {

                            var name = i;

                            return function() {
                                pinion.fire("addModule", {name: name, mode: "before"});
                            }
                        }())),
                    $moduleAfter = $("<div class='pinion-addModule-after'><span class='pinion-frontend-menu-icon-arrow'></span></div>")
                        .click((function() {

                            var name = i;

                            return function() {
                                pinion.fire("addModule", {name: name, mode: "after"});
                            }
                        }())),
                    $icon = $("<span class='icon'><img src='"+module.icon+"' /></span>")
                        .append($moduleBefore)
                        .append($moduleAfter),
                    $a = $("<a></a>")
                        .append($icon)
                        .append("<span class='text'>"+pinion.translate(module.title)+"</span>")
                        .textRunner();

                $("<li></li>")
                    .append($a)
                    .appendTo($ul);
            }
        }
    });
    
    return $ul;
    
}(jQuery));







pinion.frontend.RevisionList = (function($) {
    
    var currentModule,
        currentId,
        $revisionList = $("<ul class='pinion-frontend-revisions'></ul>")
            .on("click", "a", function() {

                pinion.FrontendBox.hide();
                pinion.ajax({
                    module: "page",
                    event: "changeRevision",
                    info: {
                        id: currentId,
                        module: currentModule,
                        revision: $(this).attr("data-id")
                    }
                });
            }),
        show = function(moduleName, moduleId) {
            
            // show loader
            pinion.FrontendBox.showLoader();
            
            currentModule = moduleName;
            currentId = moduleId;
            $revisionList.html("");
            
            pinion.ajax({
                module: "page",
                event: "revisions",
                info: {
                    module: currentModule,
                    id: currentId
                }
            }, function(data) {
                // hide loader
                pinion.FrontendBox.hideLoader();
                
                build(data.revisions);
            });

            return $revisionList.show();
        },
        hide = function() {
            return $revisionList.hide();
        },
        build = function(revisions) {
            for(var i = revisions.length; i--; ) {
                var revision = revisions[i],
                    date = new Date(revision.created*1000),
                    addZeros = function(num, length) {
                        num = num.toString();
                        
                        var numLength = num.length,
                            diff = length - numLength;
                            
                        while(diff--) {
                            num = "0"+num;
                        }
                        return num;
                    },
                    day = addZeros(date.getDate(), 2),
                    month = addZeros(date.getMonth(), 2),
                    year = date.getFullYear(),
                    hours = addZeros(date.getHours(), 2),
                    minutes = addZeros(date.getMinutes(), 2),
                    $li = $("<li></li>"),
                    $a = $("<a data-id='"+revision.revision+"'></a>")
                        .appendTo($li);
                
                $("<div class='pinion-numberBox'></div>")
                    .append(pinion.numberize(revision.revision))
                    .appendTo($a);
                $("<span class='pinion-date'>"+day+"."+month+"."+year+"</span>")
                    .appendTo($a);
                $("<span class='pinion-time'>"+hours+":"+minutes+"</span>")
                    .appendTo($a);
                
                $li.appendTo($revisionList);
            }
            pinion.FrontendBox.update();
        };
    
    return {
        show: show,
        hide: hide
    };
    
}(jQuery));







pinion.frontend.StyleList = (function($) {
    
    var currentModule,
        $styleList = $("<ul class='pinion-frontend-stylelist'></ul>")
            .on("click", "a", function() {
                var content = currentModule.content;

                pinion.FrontendBox.hide();
                pinion.ajax({
                    module: "page",
                    event: "changeStyle",
                    info: {
                        id: content.id,
                        template: $(this).attr("data-name")
                    }
                });
            }),
        show = function(module) {
            
            // show loader
            pinion.FrontendBox.showLoader();
            
            currentModule = module;
            $styleList.html("");
            
            pinion.ajax({
                module: "page",
                event: "getStyles",
                info: {
                    module: currentModule.content.module,
                    id: currentModule.content.moduleid
                }
            }, function(data) {
                // hide loader
                pinion.FrontendBox.hideLoader();
                
                build(data.data);
            });

            return $styleList.show();
        },
        hide = function() {
            return $styleList.hide();
        },
        build = function(styles) {
            for(var i = 0, length = styles.length; i < length; i++) {
                var style = styles[i],
                    styleName = style.name,
                    styleIcon = style.icon,
                    $li = $("<li></li>"),
                    $numberBox = $("<div class='pinion-numberBox'></div>")
                        .append(pinion.numberize(i+1, 13));
                       
                   var $styleIcon = $("<div class='pinion-style-icon'></div>");
                   
                   if(styleIcon) {
                       $styleIcon.css("background-image", "url("+styleIcon+")");
                   }
                          
                    $("<a data-name='"+styleName+"'></a>")
                        .append($numberBox)
                        .append($styleIcon)
                        .append("<span class='text'>"+styleName+"</span>")
                        .textRunner()
                        .appendTo($li);
                
                $li.appendTo($styleList);
            }
            pinion.FrontendBox.update();
        };
    
    return {
        show: show,
        hide: hide
    };
    
}(jQuery));



pinion.frontend.ContentList = (function($) {
    
    var currentModule,
        $styleList = $("<ul class='pinion-frontend-contentlist'></ul>")
            .on("click", "li.pinion-frontend-content", function() {

                pinion.ajax({
                    module: "page",
                    event: "changeContent",
                    info: {
                        identifier: currentModule.identifier,
                        areaname: currentModule.content.areaname,
                        id: $(this).attr("data-id"),
                        oldId: (currentModule.settings.moduleId !== undefined) ? currentModule.settings.moduleId : undefined,
                        module: currentModule.name
                    }
                });
                pinion.FrontendBox.hide();
            }),
        show = function(module) {
            
            // show loader
            pinion.FrontendBox.showLoader();
            
            currentModule = module;
            $styleList.html("");
            
            pinion.ajax({
                module: currentModule.name,
                event: "getContents",
                info: {}
            }, function(data) {
                
                // hide loader
                pinion.FrontendBox.hideLoader();
                
                build(data.data);
            });

            return $styleList.show();
        },
        hide = function() {
            return $styleList.hide();
        },
        build = function(contents) {
            var length = contents.length;
            if(length) {
                for(var i = 0; i < length; i++) {
                    var content = contents[i],
                        $numberBox = $("<div class='pinion-numberBox'></div>")
                            .append(pinion.numberize(content.id, 13)),
                        $li = $("<li class='pinion-frontend-content' data-id='"+content.id+"'></li>")
                            .append($numberBox);

                    pinion.preview(currentModule.name, content).appendTo($li);

                    $li.appendTo($styleList);
                }
                pinion.FrontendBox.update();
            } else {
                pinion.FrontendBox.update();
                $styleList.hide().after("<div class='pinion-noContent'><div class='pinion-icon'></div><div class='pinion-text'>"+pinion.translate("no contents")+"</div></div>");
            }
            
            
        };
    
    return {
        show: show,
        hide: hide
    };
    
}(jQuery));
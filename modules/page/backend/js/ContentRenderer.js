


pinion.backend.renderer.ContentRenderer = (function($) {
    
    var constr,
        visible,
        invisible,
        setVisible,
        setInvisible,
        area,
        templatePath,
        modules = pinion.php.modules,
        userid = pinion.php.userid,
        FrontendBox = pinion.FrontendBox,
        ModulePrototype = pinion.Module.prototype,
        minimizeHandler = ModulePrototype.getMinimizeHandler;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.$element = $("<div class='pinion-backend-renderer-ContentRenderer'></div>")
            .hover(function() {
                $(this).addClass("pinion-hover");
            }, function() {
                $(this).removeClass("pinion-hover");
            });
        
        // group events
        settings.groupEvents = true;
    }
    
    // public API -- prototype
    constr.prototype = {
        constructor: pinion.backend.renderer.ContentRenderer,
        init: function() {
            this.info.id = this.settings.data.id;
            
            var _this = this,
                hasEditor = false,
                settings = this.settings,
                settingsData = settings.data,
                data = settingsData.data,
                preview = settingsData.preview,
                content = settingsData.content,
                moduleName = content.module,
                module = modules[moduleName],
                hasOneIcon = false;

            if(!visible) {
                visible      = pinion.translate("visible");
                invisible    = pinion.translate("invisible");
                setVisible   = pinion.translate("set visible");
                setInvisible = pinion.translate("set invisible");
            }
            
            console.log(settingsData);
            
            // CONTENT
            var $content = $("<div></div>")
                .appendTo(this.$element);
                
            // EDIT
            var $edit = $("<div></div>")
                .hide()
                .appendTo(this.$element);
            
            // ICON BAR
            var $iconBarWrapper = $("<div class='pinion-Module-iconBarWrapper'></div>"),
                $iconBar = $("<div class='pinion-Module-iconBar'></div>");
            
            if(pinion.hasPermission(moduleName, "edit") || (pinion.hasPermission(moduleName, "edit own") && data.user_id == userid)) {
                hasOneIcon = true;
                var $editButton = $("<div class='pinion-backend-icon-edit'></div>")
                    .click(function() {
                        $content.hide();
                        $edit.show();
                        if(!hasEditor) {
                            _this.module = _this.addChild({
                                name: "AjaxWrapper",
                                type: "group",
                                $element: $edit,
                                data: {
                                    event: "getEditModule",
                                    module: "page",
                                    info: {
                                        id: data.id,
                                        module: moduleName
                                    }
                                }
                            });
                            hasEditor = true;
                        }
                    })
                    .appendTo($iconBar);
                    
                pinion.registerHelp($editButton, "Edit this content");
                
                var $revisionButton = $("<div class='pinion-backend-icon-revision'></div>")
                    .click(function() {
                        if($revisionButton.hasClass("pinion-active")) {
                            FrontendBox.hide();
                        } else {
                            FrontendBox.show($revisionButton, pinion.frontend.RevisionList.show(moduleName, data.id), {steps: 80});
                        }
                    })
                    .appendTo($iconBar);
                
                pinion.registerHelp($revisionButton, "Show revisions");
            }
            
            if(hasOneIcon) {
                // MINIMIZE BUTTON OF THE ICONBAR
                var $minimizeIconbarButton = $("<div class='pinion-Module-iconBar-arrow'></div>")
                    .click(minimizeHandler.call($minimizeIconbarButton))
                    .appendTo($iconBar);

                pinion.registerHelp($minimizeIconbarButton, "minimize/maximize this bar");

                // iconBarWrapper (for animation purposes)
                $iconBarWrapper
                    .append($iconBar)
                    .appendTo($content);
            }
            
            // EDIT BAR
            var $editBarWrapper = $("<div class='pinion-Module-editBarWrapper'></div>").appendTo($edit),
                $editBar = $("<div class='pinion-Module-editBar'></div>").appendTo($editBarWrapper);
                
            // CANCEL BUTTON    
            var $cancelButton = $("<div class='pinion-backend-icon-cancel'></div>")
                .click(function() {
                    // hide FrontendBox
                    FrontendBox.hide();
                    
                    // show content elements
                    $content.show();

                    // hide edit element
                    $edit.hide();

                    _this.resetElement(_this.module);

                })
                .appendTo($editBar);

            pinion.registerHelp($cancelButton, "cancel the edit mode");
            
            // MINIMIZE BUTTON OF THE EDITBAR
            var $minimizeButton = $("<div class='pinion-Module-editBar-arrow'></div>")
                .click(minimizeHandler.call($minimizeButton))
                .appendTo($editBar);

            pinion.registerHelp($minimizeButton, "minimize/maximize this bar");


            // PREVIEWWRAPPER
            var $previewWrapper = $("<div class='pinion-preview'></div>")
                .appendTo($content);

            // PREVIEW
            new pinion.modules[moduleName].Preview(preview).$element
                .addClass("pinion-modules-"+moduleName+"-Preview")
                .appendTo($previewWrapper);


            // TEXTWRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .appendTo($content);

            // ICON AND MODULE NAME
            $("<div class='pinion-module'><span class='pinion-module-icon'><img width='25px' src='"+module.icon+"'></img></span><span class='pinion-module-name'>"+module.title+"</span></div>")
                .appendTo($textWrapper);

            // VISIBILITY INFO
            var $visibilityInfo = $("<div class='pinion-backend-content-visibleInfo'><div class='pinion-icon'></div><div class='pinion-text'>"+visible+"</div></div>")
                .appendTo($textWrapper);
                
            // AREA
            $("<div class='pinion-backend-content-area'><div class='pinion-icon'></div><span class='pinion-area-name'>"+content.areaname+"</a></div>")
                .appendTo($textWrapper);

            // TEMPLATE
            $("<div class='pinion-backend-content-template'><div class='pinion-icon'></div><span class='pinion-templatePath'>"+content.templatepath+"</span></div>")
                .appendTo($textWrapper);

            
            

            // VISIBILITY


            var $visibility = $("<div class='pinion-content-changeVisibility'><div class='pinion-icon'></div><div class='pinion-text'>set invisible</div></div>")
                .click(function() {
                    data.visible = !data.visible;
                    _this.info.visible = data.visible;
                    _this.$element.toggleClass("pinion-invisible");
                    if(data.visible) {
                        $visibility.children(".pinion-text").text(setVisible);
                        $visibilityInfo.children(".pinion-text").text(invisible);
                    } else {
                        $visibility.children(".pinion-text").text(setInvisible);
                        $visibilityInfo.children(".pinion-text").text(visible);
                    }
                    if(data.visible == _this.initSettings.data.visible) {
                        visibilityWrapper.setClean();
                    } else {
                        visibilityWrapper.setDirty();
                    }
                })
                .appendTo(this.$element);

            var visibilityWrapper = this.addChild({
                name: "Wrapper",
                type: "group",
                $element: $visibility,
                groupEvents: true
            });
            
            // RENDERER BAR
            pinion.data.Bar.call(this, [
                $visibility,
                pinion.data.Delete.call(this, content, function() {
                    _this.info.deleted = true;
                    _this.fadeOut(300, function() {
                        _this.resetElement();
                        _this.setDirty();
                    });
                })    
            ]);

            if(!content.visible) {
                this.$element.addClass("pinion-invisible");
                $visibility.children(".pinion-text").text(setVisible);
                $visibilityInfo.children(".pinion-text").text(invisible);
            }

            // INFOS
            pinion.data.Info.call(this, ["Time", "Revision", "User"], content);
        },
        reset: function() {
            this.data.deleted = false;
            this.$element.show();
        }
    }
    
    return constr;
    
}(jQuery));


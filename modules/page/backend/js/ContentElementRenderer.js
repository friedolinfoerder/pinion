


pinion.backend.renderer.ContentElementRenderer = (function($) {
    
    var constr,
        modules = pinion.php.modules,
        FrontendBox = pinion.FrontendBox,
        userid = pinion.php.userid,
        ModulePrototype = pinion.Module.prototype,
        minimizeHandler = ModulePrototype.getMinimizeHandler;
    
    // public API -- constructor
    constr = function(settings, backend) {
        this.$element = $("<div class='pinion-backend-renderer-ContentElementRenderer'></div>")
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
        constructor: pinion.backend.renderer.ContentElementRenderer,
        init: function() {
            var _this = this,
                hasEditor = false,
                settings = this.settings,
                data = settings.data,
                moduleName = data.module,
                module = modules[moduleName],
                hasOneIcon = false;
            
            this.info.id = data.id;
            
            // CONTENT
            var $content = $("<div></div>")
                .appendTo(this.$element);
                
            // EDIT
            var $edit = $("<div></div>")
                .hide()
                .appendTo(this.$element);
            
            // ICON BAR
            var $iconBarWrapper = $("<div class='pinion-Module-iconBarWrapper'></div>"),
                $iconBar = $("<div class='pinion-Module-iconBar'></div>")
                .appendTo($content);
            
            if(pinion.hasPermission(moduleName, "edit") || (pinion.hasPermission(moduleName, "edit own") && data.user_id == userid)) {
                hasOneIcon = true;
                var $editButton = $("<div class='pinion-backend-icon-edit'></div>")
                    .click(function() {
                        $content.hide();
                        $edit.show();
                        if(!hasEditor) {
                            _this.addChild({
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
            new pinion.modules[moduleName].Preview(data).$element
                .addClass("pinion-modules-"+moduleName+"-Preview")
                .appendTo($previewWrapper);


            // TEXTWRAPPER
            var $textWrapper = $("<div class='pinion-textWrapper'></div>")
                .appendTo($content);

            // ICON AND MODULE NAME
            $("<div class='pinion-module'><span class='pinion-module-icon'><img width='25px' src='"+module.icon+"'></img></span><span class='pinion-module-name'>"+module.title+"</span></div>")
                .appendTo($textWrapper);

            // CONTENTS
            var $contents = $("<div class='pinion-contents'>")
                .appendTo($textWrapper);

            this.addChild({
                name: "AjaxTitledGroup",
                type: "group",
                title: "Contents",
                data: {
                    event: "getContentsOfElement",
                    module: "page",
                    info: {
                        module: data.module,
                        id: data.id
                    }
                }
            }).$element.appendTo($contents);

            // RENDERER BAR
            
            pinion.data.Bar.call(this, [
                pinion.data.Delete.call(this, data, function() {
                    _this.info.deleted = true;
                    _this.fadeOut(300, function() {
                        _this.resetElement();
                        _this.setDirty();
                    });
                })
            ]);
            

            // INFOS
            pinion.data.Info.call(this, ["Time", "Revision", "User"], data);
        },
        reset: function() {
            this.data.deleted = false;
            this.$element.show();
        }
    }
    
    return constr;
    
}(jQuery));


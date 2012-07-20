jQuery(function($) {
    pinion.$shortcuts = $("#pinion-backend-shortcuts");
    
    var $trashCan = pinion.$shortcuts.next().hide().droppable({
            accept: "#pinion-backend-shortcuts li",
            hoverClass: "pinion-dragHover",
            tolerance: "touch",
            drop: function(event, ui) {
                ui.draggable.remove();
            }
        }),
        addShortcutDomElement = function(shortcut) {
            var $shortcutLink = $("<li data-module='"+shortcut.name+"'><a href='pinion/modules/"+shortcut.name+"' title='"+pinion.translate(shortcut.title)+"'><span class='icon'><img src='"+shortcut.icon+"' style='width:25px' /></span></a></li>")
                .appendTo(pinion.$shortcuts)
                .children("a")
                    .data("inProgress", false)
                    .click(function() {
                        var $this = $(this);
                        if(!$this.data("inProgress")) {
                            $this.data("inProgress", true)
                            pinion.page($(this).attr("href"), function() {
                                $this.data("inProgress", false);
                            });
                            pinion.closeMenu();
                        }
                        return false;
                    });
                    
            pinion.registerHelp($shortcutLink, pinion.translate("open module %s", shortcut.title));
        },
        shortcutsAjax = function() {
            var event = {
                    event: "updateShortcuts",
                    module: "permissions",
                    info: {}
                },
                info = [];
                
            pinion.$shortcuts.children().each(function() {
                var $this = $(this);
                info.push('"'+$this.attr("data-module")+'"');
            });
            event.info.shortcuts = "["+info.join(",")+"]";

            pinion.ajax(event);
        }
    
    for(var i = 0, length = pinion.php.shortcuts.length; i < length; i++) {
        
        var shortcut = pinion.php.shortcuts[i];
        
        addShortcutDomElement(shortcut);
    }
    
    pinion.$shortcuts.sortable({
        delay: 250,
        helper: "clone",
        axis: "x",
        start: function() {
            $trashCan.show();
        },
        stop: function(event, ui) {
            $trashCan.hide();
        },
        update: shortcutsAjax
    });
    
    pinion.addShortcut = function(shortcut) {
        if(pinion.$shortcuts.children("[data-module="+shortcut.name+"]").length == 0) {
            addShortcutDomElement(shortcut);
            shortcutsAjax();
        }
    };
});
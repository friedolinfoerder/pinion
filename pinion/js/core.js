//"use strict";

pinion.$window = jQuery(window);
pinion.$document = jQuery(document);

jQuery(function($) {
    pinion.$head = $("head");
    pinion.$body = $("body");
});


// PINION DEBUG
pinion.DEBUG = true;

// modules namespace
pinion.modules = {};


if(typeof Array.isArray === "undefined") {
    Array.isArray = function(arg) {
        return Object.prototype.toString.call(arg) === "[object Array]";
    }
}
if(typeof Array.indexOf === "undefined") {
    Array.prototype.indexOf = function(elt /*, from*/) {
        var len = this.length;

        var from = Number(arguments[1]) || 0;
        from = (from < 0)
             ? Math.ceil(from)
             : Math.floor(from);
        if (from < 0) {
            from += len;
        }
          
        for(; from < len; from++) {
            if (from in this && this[from] === elt) {
                return from;
            }
        }
        return -1;
    };
}

// prototypal inheritance
if(typeof Object.create === "undefined") {
    Object.create = (function(parent, o) {
        var F = function() {};
        return function(parent, o) {
            F.prototype = parent;
            var f = new F();
            if(typeof o === "object") {
                for(var i in o) {
                    f[i] = o[i];
                }
            }
            return f;
        }
    }());
}

pinion.getId = (function() {
    var counter = 0;
    
    return function() {
        return (+new Date())+"_"+(counter++);
    }
}());

pinion.getIds = function(howMany) {
    var toReturn = [];
    for(var i = howMany; i--; ) {
        toReturn.push(pinion.getId());
    }
    return toReturn;
};


pinion.$ = function(obj) {
    var $elements = jQuery([]);
    if(Array.isArray(obj)) {
        for(var i = 0, length = obj.length; i < length; i++) {
            $elements = $elements.add(obj[i].$element);
        }
    } else if(typeof obj === "object") {
        $elements = obj.$element;
    }
    return $elements;
};



pinion.processArray = function(items, process, context, roundCallback, completeCallback) {
    var todo = items.concat(), // create a clone of the original
        length = items.length,
        start,
        status,
        func = function() {
            start = +new Date(),
            status = Math.round((length - todo.length) / length * 100);
            
            do {
                process.call(context, todo.shift());
            } while(todo.length > 0 && (+new Date() - start < 25));

            roundCallback(status);

            if(todo.length > 0) {
                setTimeout(func, 25);
            } else {
                if(completeCallback instanceof Function) {
                    completeCallback.call(context, items);
                } else if(completeCallback) {
                    context[completeCallback](items);
                }
            }
        };
    
    setTimeout(func, 25);
};

/**
 * @example pinion.require("style.css")
 * @example pinion.require("script.js")
 * @example pinion.require(["style.css", "script.js"], function() { alert("Hello") })
 */
pinion.require = (function() {
    
    var loaded = {},
        waiting = {};
    
    return function(jsOrCssFile, callback) {
        var isArray = false,
            copy = null;

        if(Array.isArray(jsOrCssFile)) {
            isArray = true;
            if(jsOrCssFile.length == 0) {
                // if the array is empty, call the callback and return
                if(callback instanceof Function) {
                    callback();
                }
                return;
            }
            copy = jsOrCssFile.concat();
            jsOrCssFile = copy.shift();
        }
        
        // check if the file is already loaded
        if(loaded[jsOrCssFile] !== undefined) {
            if(loaded[jsOrCssFile]) {
                if(isArray) {
                    // if it's an array, call this function recursively
                    pinion.require(copy, callback);
                } else if(callback instanceof Function) {
                    callback();
                }
            } else {
                waiting[jsOrCssFile].push(function() {
                    if(isArray) {
                        // if it's an array, call this function recursively
                        pinion.require(copy, callback);
                    } else if(callback instanceof Function) {
                        callback();
                    }
                });
            }
            
            return;
        }
        
        if(jsOrCssFile.split(".").pop() == "css") {
            console.info("stylesheet added: ", jsOrCssFile);
            
            pinion.$head.append('<link rel="stylesheet" href="'+jsOrCssFile+'" type="text/css"></link>');
            loaded[jsOrCssFile] = true;
            
            if(isArray) {
                // if it's an array, call this function recursively
                pinion.require(copy, callback);
            } else if(callback instanceof Function) {
                callback();
            }
            
            return;
        }
        
        // this file will be loaded
        loaded[jsOrCssFile] = false;
        waiting[jsOrCssFile] = [];

        var script = document.getElementsByTagName("script")[0],
            newJs = document.createElement("script");

        // IE
        newJs.onreadystatechange = function() {
            if(newJs.readyState === "loaded" || newJs.readyState === "complete") {
                newJs.onreadystatechange = null;
                newJs.onload = null;
                
                console.info("javascript file added: ", jsOrCssFile);

                if(isArray) {
                    // if it's an array, call this function recursively
                    pinion.require(copy, callback);
                } else if(callback instanceof Function) {
                    callback();
                }

                for(var i = waiting[jsOrCssFile].length; i--; ) {
                    // load files, which are waiting for this file
                    waiting[jsOrCssFile][i]();
                }

                loaded[jsOrCssFile] = true;
                delete waiting[jsOrCssFile];
            }
        };

        // others
        newJs.onload = function() {
            console.info("javascript file added: ", jsOrCssFile);
            
            if(isArray) {
                // if it's an array, call this function recursively
                pinion.require(copy, callback);
            } else if(callback instanceof Function) {
                callback();
            }
            
            for(var i = waiting[jsOrCssFile].length; i--; ) {
                // load files, which are waiting for this file
                waiting[jsOrCssFile][i]();
            }
            
            loaded[jsOrCssFile] = true;
            delete waiting[jsOrCssFile];
        };

        newJs.src = jsOrCssFile;
        script.parentNode.insertBefore(newJs, script);
    }
}());

pinion.length = function(obj) {
    if(Array.isArray(obj)) {
        return obj.length;
    } else {
        var size = 0,
            key;
        for(key in obj) {
            if(obj.hasOwnProperty(key)) {
                size++;
            }
        }
        return size;

    }
};

pinion.isEmpty = function(obj) {
    if(Array.isArray(obj)) {
        return (obj.length == 0);
    } else {
        for(var i in obj) {
            return false;
        }
        return true;
    }
};



pinion.sprintf = function(str) {
    if(arguments.length < 2) return false;
    
    var vars = str.match(/%([sdb]|[0-9]+\$[sdb])/g),
        counter = 1,
        types = {
            s: function(arg) {
                switch(typeof(arg)) {
                    case "number":return arg.toString();
                    case "boolean":return arg ? "true" : "false";
                }
                return arg;
            },
            d: function(arg) {
                switch(typeof(arg)) {
                    case "string":return parseInt(arg, 10);
                    case "boolean":return arg ? 1 : 0;
                }
                return arg;
            },
            b: function(arg) {
                return arg ? "true" : "false";
            }
        };
    
    for(var i = 0, length = vars.length; i < length; i++) {
        var type = vars[i].slice(-1);
        var matches = vars[i].match(/[0-9]+/);
        var index;
        if(matches) {
            index = matches[0].slice(-1);
        } else {
            index = counter++;
        }
        str = str.replace(vars[i], types[type](arguments[index]));
    }
    
    return str;
}


pinion.hasPermission = function(module, permission) {
    if(pinion.php.permissions[module] === undefined) {
        return false;
    }
    if(pinion.php.permissions[module][permission] === undefined) {
        return false;
    }
    return true;
};



pinion.numberize = function(number, size) {
    var $number = jQuery("<div class='pinion-number'></div>"),
        numberStr = number.toString(10),
        mapping = ["zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine"],
        sizes = [0, 40, 40, 26, 19, 16, 13],
        length = numberStr.length,
        size = size || sizes[length];
    
    for(var i = 0; i < length; i++) {
        var c = numberStr[i];
        
        $number.append("<span class='pinion-"+mapping[c]+"-"+size+"'><span>"+c+"</span></span>");
    }
    
    
    return $number;
};



pinion.translate = (function() {
    
    var translateBackend = function(word) {
        var lowerWord = word.toLowerCase();
        if(pinion.translations === undefined) {
            return word;
        } else if(pinion.translations[lowerWord] === undefined) {
            return word;
        } else if(pinion.translations[lowerWord][pinion.php.language] === undefined) {
            return word;
        } else {
            return pinion.translations[lowerWord][pinion.php.language];
        }
    };
    
    return function(word) {
        if(arguments.length > 1) {
            var args = Array.prototype.slice.call(arguments);
            var translateAll = false;
            if(typeof args[0] === "boolean") {
                translateAll = args.shift();
            }

            if(translateAll) {
                for(var i = 0, length = args.length; i < length; i++) {
                    args[i] = translateBackend(args[i]);
                } 
            } else {
                args[0] = translateBackend(args[0]);
            }
            return pinion.sprintf.apply(null, args);
        } else {
            return translateBackend(word);
        }
    };
}());


jQuery.noConflict();

var pinion = pinion || {};

pinion._uniqueCounter = 0;
pinion.uniqueID = function() {
    return pinion._uniqueCounter++;
}

pinion.date = {};

pinion.drag = {
    refresh: false,
    connections: {}
};

jQuery(function($) {
    pinion.$backend = $("#pinion-backend");
    pinion.$help = $("#pinion-help");
    pinion.$helpTextWrapper = pinion.$help.children();
    pinion.$helpText = pinion.$helpTextWrapper.children();
    var $contextHelp = pinion.$contextHelp = $("#pinion-contextHelp");
    
    pinion.$document.mousemove(function(event) {
        $contextHelp.css({
            left: event.pageX,
            top: event.pageY
        })
    });
});

jQuery(function($) {
    var $body = $(window);
    
    $body.scroll(function() {
        pinion.scroll.top = $body.scrollTop(),
        pinion.scroll.left = $body.scrollLeft()
    });
    
    pinion.scroll = {
        top: $body.scrollTop(),
        left: $body.scrollLeft()
    };
});

pinion.date.timezoneOffset = new Date().getTimezoneOffset()/60;

if(pinion.php === undefined || pinion.php.timezoneOffset === undefined || (pinion.php.timezoneOffset !== pinion.date.timezoneOffset)) {
    jQuery.post(pinion.php.url, {timezoneOffset: pinion.date.timezoneOffset});
}


pinion.inherit = (function() {
    var F = function() {};
    return function(C, P) {
        F.prototype = P.prototype;
        C.prototype = new F();
        C.uber = P.prototype;
        C.prototype.constructor = C;
    }
}());


pinion.namespace = function(namespace) {
    var parts = namespace.split("."),
        parent = pinion,
        i;
        
    // strip redundant leading global
    if(parts[0] === "pinion") {
        parts = parts.slice(1);
    }
    
    for(i = 0; i < parts.length; i += 1) {
        // create a property if it doesn't exists
        if(typeof parent[parts[i]] === "undefined") {
            parent[parts[i]] = {};
        }
        parent = parent[parts[i]];
    }
    return parent;
};

pinion.namespace("pinion.backend.module");

pinion.backend.plugins = {};
pinion.backend.usePlugins = (function() {
    
    var allPlugins = pinion.backend.plugins;
    
    return function() {
        var plugins = this.settings.plugins;
        
        for(var i = plugins.length; i--; ) {
            allPlugins[plugins[i]].call(this);
        }
    };
}());

pinion.ajax = (function($) {
    
    var cachedArguments = {events: [], callbacks: []},
        ajaxCount = 0,
        ajaxCallback = function(data) {
            var messages = data.messages;
            if(messages !== undefined) {
                for(var i = messages.length; i--; ) {
                    var msg = messages[i];
                    msg.server = true;
                    pinion.showMessage(msg);
                }
            }
            if(data.restart !== undefined) {
                window.location.reload();
                return;
            }
            if(data.backend !== undefined) {
                if(pinion.backendDisplays[data.backend.name] !== undefined) {
                    pinion.refreshBackend(data.backend);
                } else {
                    pinion.showBackend(data.backend);
                }
            }
            
            if(data.content !== undefined) {
                // require files and add content
                
                var files = data.files || [];
                
                pinion.require(files, function() {
                    var phpContent = pinion.php.content,
                        content = data.content,
                        currentContent;

                    for(var id in content) {
                        currentContent = content[id];

                        if(currentContent.remove) {
                            pinion.Frontend.removeContent(id);
                            continue;
                        }
                        if(phpContent[id] === undefined) {
                            phpContent[id] = {};
                        }
                        phpContent[id].vars = currentContent.vars;
                        phpContent[id].data = currentContent.data;
                        phpContent[id].content = currentContent.content;
                        
                        pinion.Frontend.replaceContent(id, currentContent.html, currentContent.identifier);
                    }
                });
            } else if(data.files) {
                // require files
                pinion.require(data.files);
            }
            var download = data.download;
            if(download !== undefined) {
                for(var i = download.length; i--; ) {
                    window.open(download[i], "_blank");
                }
            }
            
            var eval = data.eval;
            if(typeof eval === "string") {
                new Function(eval).call(data);
            } else if(Array.isArray(eval)) {
                for(var i = 0, length = eval.length; i < length; i++) {
                    new Function(eval[i]).call(data);
                }
            }
        },
        processAjax = function() {
            var data = {events: $.extend(true, [], cachedArguments.events)},
                callbacks = $.extend(true, [], cachedArguments.callbacks),
                callback = function(d) {
                    ajaxCallback(d);
                    for(var i = 0, length = callbacks.length; i < length; i++) {
                        var fn = callbacks[i];
                        fn(d);
                    }
                };

            cachedArguments = {events: [], callbacks: []};

            if(data.events.length == 0) {
                callback({});
                return false;
            }


            return $.ajax({
                type: "post",
                data: $.toJSON(data),
                dataType: "json",
                success: callback,
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR, textStatus, errorThrown);
                    pinion.showMessage({
                        type: "error",
                        text: pinion.translate("An error on the server occured.")
                    });
                }
            });
        };
    
    pinion.ajaxCallback = ajaxCallback;
    
    return function(data, callback) {
        if(Array.isArray(data)) {
            cachedArguments.events = cachedArguments.events.concat(data);
        } else if(typeof data === "object") {
            cachedArguments.events.push(data);
        } else {
            throw Error("'data' is not a valid event-source.");
        }

        if(callback instanceof Function) {
            cachedArguments.callbacks.push(callback);
        } 
        
        
        if(pinion.ajax.collect) {
            var count = ++ajaxCount;
        
            setTimeout(function() {
                if(count == ajaxCount) {
                    processAjax();
                    pinion.ajax.collect = false;
                } else {
                    return false;
                }
            }, 100);
        } else {
            processAjax();
        }
        
        
        
        
    };
}(jQuery));

pinion.ajax.collect = false;




pinion.activeBackend = null;

pinion.$document.keydown(function(event) {
    var code = event.which,
        esc = 27;
    
    // close the active backend with the key escape    
    if(event.which == esc) {
        if(pinion.activeBackend) {
            pinion.backendDisplays[pinion.activeBackend].$closeButton.click();
            return false;
        }
    }
});

pinion.backendDisplays = {};

pinion.showBackend = function(backendData) {
    
    // don't show a backend twice
    if(pinion.activeBackend) {
        if(typeof backendData === "string") {
            if(backendData === pinion.activeBackend) return;
        } else {
            if(backendData.name === pinion.activeBackend) return;
        }
    }
    
    // hide FrontendBox
    pinion.FrontendBox.hide();
    
    // show overlay
    pinion.backend.Overlay.show();
    
    var type = 0;
    
    if(typeof backendData === "string") {
        if(pinion.backendDisplays[backendData] !== undefined) {
            type = 1;
            jQuery("#pinion-backend-minimizeLinks").children("[data-module="+backendData+"]")
                .animate({"margin-top": 50}, 400, function() {
                    jQuery(this).remove();
                });
        }
    } else if(pinion.backendDisplays[backendData.name] !== undefined) {
        type = 2;
        jQuery("#pinion-backend-minimizeLinks").children("[data-module="+backendData.name+"]")
            .animate({"margin-top": 50}, 400, function() {
                jQuery(this).remove();
            });
    }
    
    pinion.hideBackend(function() {
        if(type == 1) {
            pinion.backendDisplays[backendData].slideOpen();
        } else if(type == 2) {
            pinion.backendDisplays[backendData.name].slideOpen();
        } else {
            pinion.backendDisplays[backendData.name] = new pinion.backend.BackendDisplay(backendData);
            pinion.backendDisplays[backendData.name].slideOpen();
        }
    });
};

pinion.hideBackend = function(callback) {
    
    if(pinion.activeBackend != null) {
        var activeBackend = pinion.activeBackend;
        pinion.backendDisplays[pinion.activeBackend].slideClose(function() {
            var module = pinion.backendDisplays[activeBackend];
            jQuery("<li data-module='"+module.backend.name+"'><a href='pinion/modules/"+module.backend.name+"'><span class='icon'><img src='"+module.backend.icon+"' width='25px' /></span><span class='text'>"+pinion.translate(module.backend.title)+"</span></a></li>")
                .appendTo("#pinion-backend-minimizeLinks")
                .css("margin-top", 50)
                .animate({"margin-top": 9}, 400)
                .children("a")
                    .click(function() {
                        var $this = jQuery(this);
                        if($this.data("inProgress") === undefined) {
                            $this.data("inProgress", true);
                            
                            pinion.page($this.attr("href"));
                        }
                        return false;
                    })
                    .textRunner();
            
            if(callback instanceof Function) {
                callback();
            }
        });
    } else {
        if(callback instanceof Function) {
            callback();
        }
    }
};

pinion.refreshBackend = function(backendData) {
    var oldModule = pinion.backendDisplays[backendData.name];
    oldModule.$element.remove();
    
    // if no tab is set, set it to the current tab
    if(backendData.info === undefined) {
        backendData.info = {};
    }
    if(backendData.info.tab === undefined) {
        var $tabsLeft = pinion.backendDisplays[backendData.name].$tabsLeft;
        if($tabsLeft) {
            backendData.info.tab = $tabsLeft.children(".active").attr("data-title");
        }
    }
    
    pinion.backendDisplays[backendData.name] = new pinion.backend.BackendDisplay(backendData);
    pinion.backendDisplays[backendData.name].toPosition();
    
    pinion.Resetter.hide();
}

pinion.messages = jQuery.cookie("pinion.messages");
pinion.messages = pinion.messages ? jQuery.evalJSON(pinion.messages) : [];

pinion.showMessage = (function($) {
    
    $.cookie("pinion.messages")
    
    var modules = pinion.php.modules,
        messages = pinion.messages,
        successSound = new Audio(pinion.php.url+"pinion/assets/sounds/beep.wav"),
        $messages,
        $logo,
        inAction = false,
        defaults = {
            type: "info",
            server: false,
            icon: false
        },
        addMessage = function(msg) {
            messages.push(msg);
            // 100 messages at a maximum
            while(messages.length > 100) {
                messages.shift();
            }
            $.cookie("pinion.messages", $.toJSON(messages), {expires: 30});
        },
        animateMessage = function() {
            var $children = $messages.children();
            if($children.length < 2) {
                inAction = false;
                return;
            }
            
            
            $children.last()
                .prependTo($messages)
                .css("margin-top", "-33px")
                .animate({
                    "margin-top": 0
                }, 500, function() {
                    var $this = $(this);
                    var $next;
                    
                    if($this.is($logo)) {
                        $next = $this.next();
                        addMessage($next.data("msg"));
                        $next.remove();
                    } else {
                        // play message sound
                        successSound.play();
                        
                        if(!$this.next().is($logo)) {
                            $next = $this.next();
                            addMessage($next.data("msg"));
                            $next.remove();
                        }
                    }
                    // repeat call (the speed depends on the number of messages in the queue)
                    setTimeout(animateMessage, 2000 - $children.length*50);
                });
                
            
            
        };
    
    $(function() {
        $messages = $("#pinion-messages");
        $logo = $messages.children("#pinion-logo");
    });
    
    return function(msg) {
        if(typeof msg === "string") {
            msg = {text: msg};
        }
        msg = $.extend({}, defaults, msg);
        
        var icon = (msg.module) ? "<span class='icon'><img src='"+modules[msg.module].icon+"' width='25px' /></span>" : "";
        
        pinion.$link(icon+msg.text, "message", "system messages")
            .addClass(msg.type)
            .addClass("pinion-message")
            .data("msg", msg)
            .insertAfter($logo);
        
        if(!inAction) {
            inAction = true;
            setTimeout(animateMessage, 300);
        }
    };
}(jQuery));

pinion.registerHelp = function($element, context, full) {
    if(context != null) {
        pinion.registerContextHelp($element, context);
    }
    if(full != null) {
        pinion.registerFullHelp($element, full);
    }
};

pinion.registerFullHelp = (function() {
    
    var currentHelp = null,
        open = false;
    
    return function($element, text) {
        
        $element.hover(function() {
            
            var elementHelp = (+new Date());
            currentHelp = elementHelp;
            
            if(!open) {
                setTimeout(function() {
                    if(currentHelp == elementHelp) {
                        open = true;
                        pinion.$helpText.html(text);
                        var currentHeight = pinion.$helpTextWrapper.outerHeight();
                        pinion.$help.animate({height: currentHeight}, 300); 
                    }
                }, 1000);
            } else {
                pinion.$helpText.html(text);
                var currentHeight = pinion.$helpTextWrapper.outerHeight();
                pinion.$help.css({height: currentHeight});
            }
            
            
        }, function() {
            var elementHelp = (+new Date());
            setTimeout(function() {
                if(elementHelp > currentHelp) {
                    open = false;
                    currentHelp = null;
                    pinion.$help
                        .animate({
                            height: 0
                        }, 300);
                }
            }, 500);
        });
    };
    
}());

pinion.registerContextHelp = (function() {
    
    var currentHelp = null,
        open = false;
    
    return function($element, text) {
        
        $element.hover(function() {
            
            var elementHelp = (+new Date());
            currentHelp = elementHelp;
            
            if(!open) {
                setTimeout(function() {
                    if(currentHelp == elementHelp) {
                        open = true;
                        pinion.$contextHelp
                            .html(text)
                            .fadeIn(300);
                    }
                }, 500);
            } else {
                pinion.$contextHelp.html(text);
            }
            
            
        }, function() {
            var elementHelp = (+new Date());
            setTimeout(function() {
                if(elementHelp > currentHelp) {
                    open = false;
                    currentHelp = null;
                    pinion.$contextHelp
                        .fadeOut(300);
                }
            }, 300);
        });
    };
    
}());



pinion.addInfo = function(events, info) {
    
    if(Array.isArray(events)) {
        var newEvents = jQuery.extend(true, [], events);
        for(var i = 0, length = newEvents.length; i < length; i++) {
            var event = newEvents[i];
            for(var j in info) {
                event.info[j] = info[j];
            }
        }
    } else {
        newEvents = jQuery.extend(true, {}, events);
        for(var j in info) {
            newEvents.info[j] = info[j];
        }
    }
    
    return newEvents;
};




pinion.page = function(url, callback) {
    var parts = url.split("/"),
        postData = {},
        moduleParts,
        module,
        tab;
    
    if(parts[0] === "pinion") {
        if(parts[1] === "modules") {
            
            moduleParts = parts[2].split("#");
            module = moduleParts[0];
            if(moduleParts.length > 1) {
                tab = moduleParts[1];
            }
            
            if(pinion.backendDisplays[module] !== undefined && !(parts.length > 3 && parts[3] == "refresh")) {
                
                pinion.showBackend(module);
                if(tab) {
                    pinion.backendDisplays[module].openTab(tab);
                }
                
                if(callback instanceof Function) {
                    callback();
                }

                return true;
            }
            
            postData = {
                module: module,
                event: "backend"
            };
            if(tab) {
                postData.info = {tab: tab.toLowerCase()};
            }
            pinion.ajax(postData, callback);
        }
    }
    
    return true;
};


jQuery("a.pinion-link").live("click", function() {
    var $this = jQuery(this);
    pinion.page($this.attr("href"), $this.data("callback"));
    return false;
});


pinion.$link = function(html, module, tab, callback) {
    if(tab instanceof Function) {
        return pinion.$link(html, module, null, tab);
    }
    tab = tab ? "#"+tab : "";
    if(pinion.hasPermission(module, "backend")) {
        var $element = jQuery("<a class='pinion-link' href='pinion/modules/"+module+tab+"'>"+html+"</a>");
        if(callback) {
            $element.data("callback", callback);
        }
        return $element;
    } else {
        return jQuery("<span>"+html+"</span>");
    }
    
};


pinion.preview = function(moduleName, content) {
    return new pinion.modules[moduleName].Preview(content).$element.addClass("pinion-modules-"+moduleName+"-Preview");
};


pinion.EventDispatcher = (function() {
    
    var constr;
    
    constr = function() {};
    
    constr.prototype = {
        /**
         * @param string          event
         * @param object          data
         * @param function|string callback
         * @param context         object
         * 
         * @example on(event, callback)
         * @example on(event, callback, context)
         * @example on(event, data, callback)
         * @example on(event, data, callback, context)
         */
        on: function(event, data, callback, context) {
            if(this.handlers[event] === undefined) {
                this.handlers[event] = [];
            }
            if(data instanceof Function || typeof data === "string") {
                context = callback || this;
                callback = data;
                data = {};
            }
            this.handlers[event].push({
                data: data,
                callback: callback, 
                context: context || this
            });
            return this;
        },
        one: function(event, data, callback, context) {
            if(this.handlers[event] === undefined) {
                this.handlers[event] = [];
            }
            if(data instanceof Function || typeof data === "string") {
                context = callback || this;
                callback = data;
                data = {};
            }
            this.handlers[event].push({
                data: data,
                callback: callback, 
                context: context || this,
                one: true
            });
            return this;
        },
        /**
         * @param string          event
         * @param function|string callback
         * @param object          context
         * 
         * @example off()
         * @example off(event)
         * @example off(event, callback)
         * @example off(event, callback, context)
         * @example off(event, context)
         */
        off: function(event, callback, context) {
            var i,
                length,
                handler;
            
            if(event === undefined) {
                // off()
                this.handlers = {};
            } else if(this.handlers[event] !== undefined) {
                if(callback === undefined) {
                    // off(event)
                    delete this.handlers[event];
                } else if(context === undefined) {
                    if(typeof callback === "string") {
                        // off(event, callback)
                        for(i = this.handlers[event].length; i--; ) {
                            handler = this.handlers[event][i];
                            if(handler.callback === callback) {
                                this.handlers[event].splice(i, 1);
                            }
                        }
                    } else {
                        // off(event, context)
                        context = callback;
                        for(i = this.handlers[event].length; i--; ) {
                            handler = this.handlers[event][i];
                            if(handler.context === context) {
                                this.handlers[event].splice(i, 1);
                            }
                        }
                    }
                } else {
                    // off(event, callback, context)
                    for(i = this.handlers[event].length; i--; ) {
                        handler = this.handlers[event][i];
                        if(handler.callback === callback && handler.context === context) {
                            this.handlers[event].splice(i, 1);
                        }
                    }
                }
            }
            return this;
        },
        hasOn: function(event, context) {
            if(context === undefined) {
                context = this;
            }
            if(this.handlers[event] === undefined) {
                return false;
            }
            for(var i = this.handlers[event].length; i--; ) {
                var handler = this.handlers[event][i];
                if(context === handler.context) {
                    return true;
                }
            }
        },
        /**
         * @param string event
         * @param object info
         * 
         * @example fire(event)
         * @example fire(event, info)
         */
        fire: function(event, info) {
            var toReturn = true;
            if(this.handlers[event] !== undefined) {
                for(var i = this.handlers[event].length; i--; ) {
                    var handler = this.handlers[event][i],
                        context = handler.context,
                        data = handler.data,
                        one = handler.one;
                    
                    var completeInfo = jQuery.extend({sender: this}, info);
                    
                    if(handler.callback instanceof Function) {
                        if(handler.callback.call(context, completeInfo, data) === false) {
                            toReturn = false;
                        }
                    } else if(typeof handler.callback === "string") {
                        if(context[handler.callback](completeInfo, data) === false) {
                            toReturn = false;
                        }
                    }
                    
                    if(one) {
                        delete this.handlers[event].splice(i, 1);
                    }
                }
            }
            return toReturn;
        },
        fireRecursive: function(event, info) {
            this.fire(event, info);
            
            for(var i = this.children.length; i--; ) {
                var child = this.children[i];
                
                child.fireRecursive(event, info);
            }
        }
    };
    
    return constr;
    
}());

pinion.handlers = {};
jQuery.extend(pinion, pinion.EventDispatcher.prototype);
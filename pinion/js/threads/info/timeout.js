pinion.threads.thread = (function($) {
    
    var interval,
        processAnswer = function(json) {
            var events = [];    
            if(json.user !== undefined) {
                for(var i in json.user) {
                    if(pinion.php.user[i] === undefined) {
                        events.push({
                           name: "userOnline",
                           data: json.user[i]
                        });
                    }
                }
                for(i in pinion.php.user) {
                    if(json.user[i] === undefined) {
                        events.push({
                           name: "userOffline",
                           data: {
                               id: pinion.php.user[i]
                           }
                        });
                    }
                }
            }
            if(json.messages != null) {
                for(i = 0, length = json.messages.length; i < length; i++) {
                    events.push({
                       name: "message",
                       data: json.messages[i]
                    });
                }
            }
            
            
            if(events.length > 0) {
                this.receiveFunction({events: events});
            }
        },
        start = function() {
            var _this = this;
            
            interval = setInterval(function() {
                $.ajax({
                    url: pinion.php.url,
                    data: $.toJSON({
                        events: [{
                            module: "permissions",
                            event: "update",
                            info: {}
                        }]
                    }),
                    type: "post",
                    dataType: "json",
                    success: function(data) {
                        processAnswer.call(_this, data);
                    }
                });
            }, pinion.php.updateInterval*1000);
        },
        stop = function() {
            clearInterval(interval);
        };
    
        
    return {
        start: start,
        stop: stop
    };
    
}(jQuery));
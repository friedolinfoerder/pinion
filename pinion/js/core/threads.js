
pinion.threads = (function($) {
    
    var threads = {},
        hasWebWorker = false,
        add = function(threadPath, data, receiveFunction) {
            if(data instanceof Function) {
                receiveFunction = data;
                data = {};
            }
            data.cmd = "start";
            
            try {
                $.Hive.create({
                    worker: threadPath+'/worker.js',
                    receive: receiveFunction
                });

                var infoThread = $.Hive.get(0);
                
                threads[threadPath] = infoThread;
                
                infoThread.send(data);
                hasWebWorker = true;
            } catch(error) {
                pinion.require(threadPath+"/timeout.js", function() {
                    threads[threadPath] = pinion.threads.thread;
                    
                    threads[threadPath].receiveFunction = receiveFunction;
                    threads[threadPath].start();
                });
                threads[threadPath] = threadPath;
            }
            
        },
        remove = function(threadPath) {
            if(threads[threadPath] === undefined) {
                return false;
            }
            
            if(hasWebWorker) {
                threads[threadPath].send({cmd:"stop"});
                $.Hive.destroy(threads[threadPath].id);
            } else {
                threads[threadPath].stop();
                $("script[src='"+threadPath+"/timeout.js']").remove();
            }
            
            delete threads[threadPath];
            
            return true;
        };
    
    return {
        threads: threads,
        add: add,
        remove: remove
    };
    
}(jQuery));


jQuery(function($) {
    
    pinion.threads.add(pinion.php.url+"pinion/js/threads/info", {
        user: pinion.php.user,
        url: pinion.php.url,
        data: $.toJSON({
            events: [{
                module: "permissions",
                event: "update"
            }]
        }),
        updateInterval: pinion.php.updateInterval
    }, function(data) {
        for(var i in data.events) {
            var event = data.events[i];
            if(event.name === "userOnline") {
                pinion.showMessage(pinion.translate(false, "The user %s is now online", "<b>"+event.data.username+"</b>"));
                pinion.php.user[event.data.id] = event.data;
            }
            if(event.name === "userOffline") {
                pinion.showMessage(pinion.translate(false, "The user %s is now offline", "<b>"+event.data.username+"</b>"));
                delete pinion.php.user[event.data.id];
            }
            if(event.name === "message") {
                pinion.showMessage(event.data);
            }
            if(event.name === "files") {
                pinion.require(event.data);
            }
            pinion.fire(event.name, event.data);
        }
        
    });
    
});


// IMPORT POLLEN
importScripts('../../../../modules/jquery/templates/js/plugins/jquery.hive.pollen.js');

var interval,
    internalData,
    processAnswer = function(data) {
        var events = [],
            json = data.json,
            i,
            length;
   
        if(json.user !== undefined) {
            for(i in json.user) {
                var user = json.user[i];
                if(internalData.user[i] === undefined) {
                    events.push({
                       name: "userOnline",
                       data: json.user[i]
                    });
                    internalData.user[i] = user;
                }
            }
            for(i in internalData.user) {
                if(json.user[i] === undefined) {
                    events.push({
                       name: "userOffline",
                       data: {
                           id: internalData.user[i].id,
                           username: internalData.user[i].username
                       }
                    });
                    delete internalData.user[i];
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
        if(json.files != null) {
            events.push({
                name: "files",
                data: json.files
            });
        }
        
        if(events.length > 0) {
            $.send({events: events});
        }
    },
    start = function(data) {
        
        internalData = data;
        interval = setInterval(function() {
            $.ajax.post({
                url: data.url,
                data: data.data,
                success: processAnswer
            });
        }, internalData.updateInterval*1000);
    },
    stop = function(data) {
        clearInterval(interval);
    };


$(function(data) {
    switch(data.cmd) {
        case("start"):
            start(data);
            break;
        case("stop"):
            stop(data);
            break;
    }
});
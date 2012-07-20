

pinion.on("create.container.containerAddModuleButton", function(data) {
    var element = data.element,
        templateSelector = element.prev(),
        slider = templateSelector.prev(),
        selector = slider.prev();
        
    selector.on("change", function(data) {
        templateSelector.setData({
            event: "getStyles",
            module: "page",
            info: {
                module: selector.info.module
            }
        })
    });
        
    element.click(function() {
         
        if(selector.isDirty()) {
            this.getElement("structureList").addData([{
                id: +new Date(),
                moduleid: selector.settings.value,
                module: selector.val(),
                "how many": slider.settings.value || pinion.translate("any"),
                template: templateSelector.settings.value
            }]);
            this.parent.resetElement();
        }
    });
});
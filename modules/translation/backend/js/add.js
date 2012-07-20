

pinion.on("create.translation.addTranslation", function(data) {
    data.element.click(function() {
        var language = this.getElement("language"),
            word = this.getElement("word"),
            translation = this.getElement("translation");
            
        if(language.isDirty() && word.isDirty() && translation.isDirty()) {
            this.getElement("wordFinder").addData([{
                language: language.settings.value,
                word: word.settings.value,
                translation: translation.settings.value
            }]);
            this.getElement("translationInput").resetElement();
        }
    });
});
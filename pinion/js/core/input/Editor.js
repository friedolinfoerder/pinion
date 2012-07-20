pinion.backend.input.Editor = (function($) {
    
    var constr,
        pageUrl = pinion.php.url,
        lang = pinion.php.lang;
    
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-Editor'></div>");
        
        if(settings.bigger) {
            this.$element.addClass("pinion-biggerInput");
        }
        
        if(settings.label) {
            this.$label = $("<label class='pinion-label'>"+settings.label+"</label>").appendTo(this.$element);
        }
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
        // here comes the dom element, which will be replaced by a ckeditor
        this.$editor = $("<textarea class=''></textarea>").appendTo(this.$inputWrapper);
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.Editor,
        defaultSettings: {
            value: "",
            infoKey: "value",
            CKOptions: {}
        },
        init: function() {
            var _this = this;
            
            pinion.require([pageUrl+"pinion/js/ckeditor/ckeditor.js", pageUrl+"pinion/js/ckeditor/adapters/jquery.js"], function() {
                
                // the start info has got the value as information
                _this.info[_this.settings.infoKey] = _this.settings.value;
                
                // initialize the editor with a timeout because chrome
                // doesn't work right without it
                setTimeout(function() {
                    _this.$editor
                        .ckeditor(function(event) {
                            this.on("change", function(event) {
                                var myTime = new Date().getTime();
                                _this.time = myTime;
                                setTimeout(function() {
                                    if(myTime == _this.time) {
                                        var val = _this.$editor.val();

                                        _this.settings.value = val;
                                        _this.info[_this.settings.infoKey] = val;


                                        if(val !== _this.initSettings.value) {
                                            _this.setDirty();
                                        } else {
                                            _this.setClean();
                                        }

                                    }
                                }, 500);
                            });
                            this.setData(_this.settings.value);
                        }, $.extend({
                            language: lang,
                            baseHref: pageUrl,
                            dialog_backgroundCoverColor: '#1A292B',
                            dialog_backgroundCoverOpacity: 0.7,
                            startupFocus: true,
                            width: "100%",
                            resize_minWidth: 300,
                            scayt_autoStartup: true,
                            // scayt_sLang ="en_US",
                            scayt_uiTabs: '0,1,0',
                            skin: 'pinion',
                            toolbar_Backend: [
                                {name: 'undo',        items : [ 'Undo','Redo' ]},
                                {name: 'scayt',       items : [ 'Scayt' ]},
                                {name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord' ]},
                                {name: 'editing',     items : [ 'Find' ]},
                                {name: 'document',    items : [ 'Source' ]},
                                '/',
                                {name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ]},
                                {name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Blockquote']},
                                {name: 'insert',      items : [ 'SpecialChar','Table','HorizontalRule' ]},
                                {name: 'links',       items : [ 'Link' ]}
                            ],
                            toolbar_Frontend: [
                                {name: 'undo',        items : [ 'Undo','Redo' ]},
                                {name: 'scayt',       items : [ 'Scayt' ]},
                                {name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord' ]},
                                {name: 'editing',     items : [ 'Find' ]},
                                {name: 'tools',       items : [ 'Maximize' ]},
                                {name: 'document',    items : [ 'Source' ]},
                                '/',
                                {name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ]},
                                {name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Blockquote']},
                                {name: 'insert',      items : [ 'SpecialChar','Table','HorizontalRule' ]},
                                {name: 'links',       items : [ 'Link' ]}
                            ],
                            toolbar: "Backend"
                        }, _this.settings.CKOptions));
                });
            });
            
            // workaround for sortable-ckeditor bug (ckeditor freezes on jQuery UI Reorder):
            // update the value after dragging the parent module
            _this.backend
                .on("positionChanged", function() {
                    this.$editor.val(this.info[this.settings.infoKey]);
                }, _this)
                .on("remove", function() {
                    this.$editor.ckeditorGet().destroy();
                }, _this);
        },
        reset: function() {
            this.$editor.val(this.initSettings.value);
        }
    };
    
    return constr;
    
}(jQuery));



// TranslationEditor

pinion.backend.input.TranslationEditor = (function($) {
    
    var constr,
        supportedLanguages = pinion.php.supportedLanguages,
        mainLanguage = supportedLanguages[0],
        TranslationTextbox = pinion.backend.input.TranslationTextbox.prototype;
    
    // public API -- constructor
    constr = function(settings) {
        this.$element = $("<div class='pinion-backend-input-TranslationEditor'></div>");
        
        if(settings.bigger) {
            this.$element.addClass("pinion-biggerInput");
        }
        
        if(settings.label) {
            this.$label = $("<label class='pinion-label'>"+settings.label+"</label>").appendTo(this.$element);
        }
        this.$inputWrapper = settings.label ? $("<div class='pinion-backend-inputwrapper'></div>").appendTo(this.$element) : this.$element;
    };
    
    constr.prototype = {
        constructor: pinion.backend.input.TranslationEditor,
        defaultSettings: {
            value: "",
            infoKey: "value",
            bigger: false
        },
        init: function() {
            TranslationTextbox.createEditor.call(this, "Editor");
        }
    };
    
    
    return constr;
    
}(jQuery));
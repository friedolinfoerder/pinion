<?php
/**
 * Module Translation
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/translation
 */

namespace modules\translation;

use \pinion\modules\Module;
use \pinion\events\Event;
use \pinion\files\classwriter\PHP_Class_Writer;
use \pinion\general\Registry;
use \pinion\access\Connector;

class Translation extends Module {
    
    protected $_backendTranslationCache = array();
    protected $_hasSetbackendTranslation = false;
    protected $_backendTranslations = array();
    
    public $languages = array(
        "aa" => "Afar",
        "ab" => "Abchasisch",
        "af" => "Afrikaans",
        "am" => "Amharisch",
        "ar" => "Arabisch",
        "as" => "Assamesisch",
        "ay" => "Aymara",
        "az" => "Aserbaidschanisch",
        "ba" => "Baschkirisch",
        "be" => "Belorussisch",
        "bg" => "Bulgarisch",
        "bh" => "Biharisch",
        "bi" => "Bislamisch",
        "bn" => "Bengalisch",
        "bo" => "Tibetanisch",
        "br" => "Bretonisch",
        "ca" => "Katalanisch",
        "co" => "Korsisch",
        "cs" => "Tschechisch",
        "cy" => "Walisisch",
        "da" => "Dänisch",
        "de" => "Deutsch",
        "dz" => "Dzongkha, Bhutani",
        "el" => "Griechisch",
        "en" => "Englisch",
        "eo" => "Esperanto",
        "es" => "Spanisch",
        "et" => "Estnisch",
        "eu" => "Baskisch",
        "fa" => "Persisch",
        "fi" => "Finnisch",
        "fj" => "Fiji",
        "fo" => "Färöisch",
        "fr" => "Französisch",
        "fy" => "Friesisch",
        "ga" => "Irisch",
        "gd" => "Schottisches Gälisch",
        "gl" => "Galizisch",
        "gn" => "Guarani",
        "gu" => "Gujaratisch",
        "ha" => "Haussa",
        "he" => "Hebräisch",
        "hi" => "Hindi",
        "hr" => "Kroatisch",
        "hu" => "Ungarisch",
        "hy" => "Armenisch",
        "ia" => "Interlingua",
        "id" => "Indonesisch",
        "ie" => "Interlingue",
        "ik" => "Inupiak",
        "is" => "Isländisch",
        "it" => "Italienisch",
        "iu" => "Inuktitut (Eskimo)",
        "iw" => "Hebräisch (veraltet, nun: he)",
        "ja" => "Japanisch",
        "ji" => "Jiddish (veraltet, nun: yi)",
        "jv" => "Javanisch",
        "ka" => "Georgisch",
        "kk" => "Kasachisch",
        "kl" => "Kalaallisut (Grönländisch)",
        "km" => "Kambodschanisch",
        "kn" => "Kannada",
        "ko" => "Koreanisch",
        "ks" => "Kaschmirisch",
        "ku" => "Kurdisch",
        "ky" => "Kirgisisch",
        "la" => "Lateinisch",
        "ln" => "Lingala",
        "lo" => "Laotisch",
        "lt" => "Litauisch",
        "lv" => "Lettisch",
        "mg" => "Malagasisch",
        "mi" => "Maorisch",
        "mk" => "Mazedonisch",
        "ml" => "Malajalam",
        "mn" => "Mongolisch",
        "mo" => "Moldavisch",
        "mr" => "Marathi",
        "ms" => "Malaysisch",
        "mt" => "Maltesisch",
        "my" => "Burmesisch",
        "na" => "Nauruisch",
        "ne" => "Nepalesisch",
        "nl" => "Holländisch",
        "no" => "Norwegisch",
        "oc" => "Okzitanisch",
        "om" => "Oromo",
        "or" => "Oriya",
        "pa" => "Pundjabisch",
        "pl" => "Polnisch",
        "ps" => "Paschtu",
        "pt" => "Portugiesisch",
        "qu" => "Quechua",
        "rm" => "Rätoromanisch",
        "rn" => "Kirundisch",
        "ro" => "Rumänisch",
        "ru" => "Russisch",
        "rw" => "Kijarwanda",
        "sa" => "Sanskrit",
        "sd" => "Zinti",
        "sg" => "Sango",
        "sh" => "Serbokroatisch (veraltet)",
        "si" => "Singhalesisch",
        "sk" => "Slowakisch",
        "sl" => "Slowenisch",
        "sm" => "Samoanisch",
        "sn" => "Schonisch",
        "so" => "Somalisch",
        "sq" => "Albanisch",
        "sr" => "Serbisch",
        "ss" => "Swasiländisch",
        "st" => "Sesothisch",
        "su" => "Sudanesisch",
        "sv" => "Schwedisch",
        "sw" => "Suaheli",
        "ta" => "Tamilisch",
        "te" => "Tegulu",
        "tg" => "Tadschikisch",
        "th" => "Thai",
        "ti" => "Tigrinja",
        "tk" => "Turkmenisch",
        "tl" => "Tagalog",
        "tn" => "Sezuan",
        "to" => "Tongaisch",
        "tr" => "Türkisch",
        "ts" => "Tsongaisch",
        "tt" => "Tatarisch",
        "tw" => "Twi",
        "ug" => "Uigur",
        "uk" => "Ukrainisch",
        "ur" => "Urdu",
        "uz" => "Usbekisch",
        "vi" => "Vietnamesisch",
        "vo" => "Volapük",
        "wo" => "Wolof",
        "xh" => "Xhosa",
        "yi" => "Jiddish",
        "yo" => "Joruba",
        "za" => "Zhuang",
        "zh" => "Chinesisch",
        "zu" => "Zulu"
    );
    
    public function install() {
        
        $this->data
            ->createDataStorage("backend", array(
                "language"    => array("type" => "varchar", "length" => 10, "translatable" => false),
                "word"        => array("type" => "varchar", "length" => 500, "translatable" => false),
                "translation" => array("type" => "varchar", "length" => 500, "translatable" => false)
            ))
            ->createDataStorage("frontend", array(
                "language"    => array("type" => "varchar", "length" => 10, "translatable" => false),
                "translation" => array("type" => "text", "translatable" => false),
                "frontend"
            ));
    }
    
    public function init() {
        if($this->identity) {
            $this->response->addJs("../../backendTranslation.js", "module:translation");
            $this->_backendTranslations = BackendTranslation::$translations;
        }
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "add translation",
            // TODO "edit translation",
            // TODO "delete translation",
            "export language",
            "edit translation",
            "share language with pinion"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            $this->addEventListener("hasWord");
            
            
        
            if($this->hasPermission("add translation")) {
                $this
                    ->addEventListener("addTranslationWords")
                    ->addEventListener("addTranslationFiles")
                    ->addEventListener("addTranslationDownloads");
            }
            if($this->hasPermission("export language"))                 $this->addEventListener("exportLanguage");
            if($this->hasPermission("share language with pinion"))      $this->addEventListener("shareLanguage");
            if($this->hasPermission("edit translation"))   $this->addEventListener("editTranslations");
            
            $this->response->addEventListener("flush", "writeTranslationArray", $this);
            $this->response->addEventListener("flushInfos", "writeTranslationArray", $this);
        }
        
    }
    
    public function editTranslations(Event $event) {
        $translations = $event->getInfo("translations");
        
        $translationArray = array();
        foreach($translations as $translation) {
            if(!isset($translationArray[$translation["language"]])) {
                $translationArray[$translation["language"]] = array();
            }
            $translationArray[$translation["language"]][$translation["word"]] = $translation["translation"];
        }
        
        $count = 0;
        foreach($translationArray as $language => $array) {
            $this->setBackendTranslation($language, $array);
            $count += count($array);
        }
        
        $this->response->addSuccess($this, $this->translate("%d translations updated", $count));
    }
    
    public function shareLanguage(Event $event) {
        $language = $event->getInfo("language");
        if(!Connector::isConnected()) {
            return $this->response->addWarning($this, $this->translate("Could not connect to %s", PINION_URL));
        }
        Connector::post(PINION_URL."/shareLanguage.php", array(
            "language" => $language,
            "translations" => $this->build($language, "json")
        ));
        
        $this->response->addSuccess($this, $this->translate(true, "Thank you for sharing your current %s translations", $this->languages[$language]));
    }
    
    public function exportLanguage(Event $event) {
        $language = $event->getInfo("language");
        $type = $event->getInfo("type", "json");
        
        if($this->request->isAjax()) {
            $this->response->addInfo("download", SITE_URL."&module=translation&event=exportLanguage&language=$language&type=$type");
        } else {
            $ext = ($type == "php") ? "php.txt" : $type;
            header('content-type: text/json; charset=utf-8');
            header("content-disposition: attachment; filename={$language}_".time().".$ext");
            
            
            die($this->build($language, $type));
        }
    }
    
    protected function build($language, $type) {
        $all = $this->data->all("backend", array("conditions" => array("language = ?", $language), "select" => "word, translation", "order" => "word ASC"));
        
        $translations = array();
        foreach($all as $one) {
            $translations[$one->word] = $one->translation;
        }
        $out = "";
        $type = strtoupper($type);
        if(method_exists($this, "build$type")) {
            $out = $this->{"build$type"}($translations);
        }
        return $out;
    }
    
    protected function buildJSON($translations) {
        $out = "{\n";
        $array = array();
        foreach($translations as $word => $translation) {
            $array[] = "\"".str_replace("\"", "\\\"", $word)."\": \"".str_replace("\"", "\\\"", $translation)."\"";
        }
        $out .= "    ".join(",\n    ", $array)."\n}";
        return $out;
    }
    
    protected function buildXML($translations) {
        $out = "<root>\n";
        foreach($translations as $word => $translation) {
            $out .= "    <item>\n        <word>$word</word>\n        <translation>$translation</translation>\n    </item>\n";
        }
        $out .= "</root>";
        return $out;
    }
    
    protected function buildCSV($translations) {
        $out = array();
        foreach($translations as $word => $translation) {
            $out[] = "$word => $translation";
        }
        return join("\n", $out);
    }
    
    protected function buildPHP($translations) {
        $out = "<?php\nreturn array(\n";
        foreach($translations as $word => $translation) {
            $word = str_replace("\"", "\\\"", $word);
            $word = str_replace("\$", "\\\$", $word);
            $translation = str_replace("\"", "\\\"", $translation);
            $translation = str_replace("\$", "\\\$", $translation);
            $out .= "    \"$word\" => \"$translation\",\n";
        }
        $out .= ");\n?>";
        return $out;
    }
    
    public function hasWord(Event $event) {
        $words = $this->data->find_all_by_word("backend", $event->getInfo("word"));
        $this->response->setInfo("words", $words);
        foreach($words as $word) {
            $this->response->addWarning($this, $this->translate('%1$s is already translated to %2$s in %3$s', "<b>".$word->word."</b>", "<b>".$word->translation."</b>", "<b>".$word->language."</b>"));
        }
        $this->response->setInfo("valid", true);
    }
    
    public function addTranslationWords(Event $event) {
        $data = $event->getInfo("data");
        
        $languages = array();
        foreach($data as $word) {
            if(!isset($languages[$word["language"]])) {
                $languages[$word["language"]] = array();
            }
            $languages[$word["language"]][$word["word"]] = $word["translation"];
        }
        foreach($languages as $language => $translations) {
            $this->setBackendTranslation($language, $translations);
        }
        
        $this->response->addSuccess($this, $this->translate("%d translations saved", count($data)));
    }
    
    public function addTranslationFiles(Event $event) {
        $files = $event->getInfo("files");
        $filePath = $this->module("fileupload")->getFilesPath();
        
        foreach($files as $name => $file) {
            $lang = explode("_", $name);
            $lang = $lang[0];
            $this->setBackendTranslationFile($lang, "$filePath/temp/$name");
        }
    }
    
    public function addTranslationDownloads(Event $event) {
        $languages = $event->getInfo("languages");
        
        foreach($languages as $language) {
            $translations = Connector::getInfo("translation/".$language);
            $this->setBackendTranslation($language, $translations);
        }
    }
    
    public function setFrontendTranslation($translationId, $translations) {
        $new = false;
        if(is_null($translationId)) {
            $translationMainRow = $this->data->create("frontend", array(
                "language"    => "---",
                "translation" => "---"
            ));
            $translationId = $translationMainRow->id;

            $new = true;
        }

        foreach($translations as $language => $translation) {
            $updated = false;
            if(!$new) {
                $translationData = $this->data->find("frontend", array("conditions" => array("language = ? AND frontend_id = ?", $language, $translationId)));
                if($translationData) {
                    if($translationData->translation !== $translation) {
                        $translationData->translation = $translation;
                        $translationData->save();
                    }
                    $updated = true;
                }
            }

            if(!$updated) {
                $this->data->create("frontend", array(
                    "language"    => $language,
                    "translation" => $translation,
                    "frontend_id" => $translationId
                ));
            }
        }
        return $translationId;
    }
    
    public function setBackendTranslationFile($language, $filePath) {
        if(!is_file($filePath)) return false;
            
        $matches = array();
        preg_match("/\.(.*)$/", $filePath, $matches);
        $extension = strtolower($matches[1]);
        $translations = array();
        
        if($extension == "php") {
            // php file
            $translations = include $filePath;
        } else {
            // csv, xml or json file
            $translations = $this->parseBackendTranslations(file_get_contents($filePath), $extension);
        }
        return $this->setBackendTranslation($language, $translations);
    }
    
    protected function parseBackendTranslations($content, $extension) {
        $extension = strtoupper($extension);
        $translations = array();
        
        if(method_exists($this, "parse$extension")) {
            $translations = $this->{"parse$extension"}($content);
        }
        
        return $translations;
    }
    
    protected function parseCSV($content) {
        $translations = array();
        $chars = "=>";
        
        $content = explode("\n", $content);
        foreach($content as $line) {
            $line = explode($chars, $line);
            if(count($line) == 2) {
                $translations[trim($line[0])] = trim($line[1]);
            } 
        }
        return $translations;
    }
    
    protected function parseXML($content) {
        $translations = array();
        
        $xml = simplexml_load_string($content);
        if($xml instanceof \SimpleXMLElement) {
            $children = $xml->children();
            
            foreach($children as $child) {
                $translations[$child->word->__toString()] = $child->translation->__toString();
            }
        }
        return $translations;
    }
    
    protected function parseJSON($content) {
        return json_decode($content, true);
    }
    
    /**
     * Sets the translation for the given words
     * 
     * @param string $language
     * @param array $translations The words with their translations.
     * 
     * @return Translation Fluent interface
     */
    public function setBackendTranslation($language, array $translations) {
        $this->_hasSetbackendTranslation = true;
        
        foreach($translations as $word => $translation) {
            $trans = $this->data->find("backend", array("conditions" => array("language = ? AND word = ?", $language, $word)));

            if($trans) {
                $trans->translation = $translation;
                $trans->save();
            } else {
                $this->data->create("backend", array(
                    "language"    => $language,
                    "word"        => strtolower($word),
                    "translation" => $translation
                ));
            }
        }
        
        // fluent interface
        return $this;
    }
    
    
    /**
     * Method, which translates a word from the backend.
     * 
     * @param string The word, which should be translated.
     * @return string Returns the translated word. If there is no language set or no translation available, it will return the given word.
     */
    public function translateBackend($word) {
        $lowerWord = strtolower($word);
        if(!isset($this->_backendTranslations[$lowerWord])) {
            return $word;
        } elseif(!isset($this->_backendTranslations[$lowerWord][$this->identity->language])) {
            return $word;
        } else {
            return $this->_backendTranslations[$lowerWord][$this->identity->language];
        }
    }
    
    public function defineBackend() {
        
        
        $languageData = array();
        foreach($this->languages as $short => $large) {
            if($short == "en") continue;
            
            $languageData[] = array(
                "id" => $short,
                "name" => $large
            );
        }
        
        $langs = $this->data->all("backend", array("select" => "id, language", "group" => "language"));
        $existingLanguageData = array();
        foreach($langs as $lang) {
            $lang = $lang->language;
            $existingLanguageData[] = array(
                "id" => $lang,
                "language" => $this->languages[$lang]
            );
        }
        
        
        // FRONTEND
        $frontendTranslations = $this->data->all("frontend", array("conditions" => array("frontend_id IS NULL")));
        $frontendTranslationsData = $this->data->getAttributes($frontendTranslations, array(
            "children"
        ));
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Frontend"))
                ->list("Finder", array(
                    "data" => $frontendTranslationsData,
                    "renderer" => "TranslationRenderer"
                ));
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Backend"))
                ->startGroup("LazySelectGroup", array(
                    "label" => "language"
                ));
                
                foreach($langs as $lang) {
                    $this->framework
                        ->startGroup("TitledSection", array(
                            "title" => $this->languages[$lang->language]
                        ))
                            ->list("DataPager", array(
                                "data" => $this->data->getAttributes($this->data->all("backend", array("order" => "word ASC"))),
                                "display" => array(
                                    "renderer" => array(
                                        "name" => "WordRenderer",
                                        "events" => array("event" => "editTranslations"),
                                        "groupEvents" => true
                                    ),
                                    "groupEvents" => "translations"
                                )
                            ))
                            ->end();
                }
         
        if($this->hasPermission("add translation")) {
            // ADD LANGUAGE
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Add"))
                    ->startGroup("LazyTitledGroup", array("title" => "Add word by word"))
                        ->startGroup("TitledSection", array("title" => $this->translate("Input"), "identifier" => "translationInput", "validate" => "all"))
                            ->list("Selector", array(
                                "identifier" => "language",
                                "label" => "language",
                                "data" => $languageData,
                                "validators" => array(
                                    "notEmpty" => true
                                )
                            ))
                            ->input("Textbox", array(
                                "identifier" => "word",
                                "label" => "word",
                                "validators" => array(
                                    "notEmpty" => true,
                                    "events" => array(
                                        "event" => "hasWord"
                                    )
                                )
                            ))
                            ->input("Textbox", array(
                                "identifier" => "translation",
                                "label" => "translation",
                                "validators" => array(
                                    "notEmpty" => true
                                )
                            ))
                            ->input("Button", array(
                                "identifier" => "addTranslation",
                                "label" => "add"
                            ))
                            ->end()
                        ->startGroup("TitledSection", array("title" => $this->translate("Translations")))
                            ->list("Finder", array(
                                "identifier" => "wordFinder",
                                "renderer" => "WordRenderer",
                                "selectable" => false,
                                "events" => array(
                                    "event" => "addTranslationWords"
                                )
                            ))
                            ->end()
                        ->end()
                    ->startGroup("LazyTitledGroup", array(
                        "title" => "Add words with own files"
                    ))
                        ->input("Fileuploader", array(
                            "temp" => true,
                            "events" => array(
                                "event" => "addTranslationFiles"
                            )
                        ))
                        ->end()
                    ->startGroup("LazyTitledGroup", array(
                        "translate" => false,
                        "title" => $this->translate("Add words with files from %s", PINION_URL)
                    ));

            if(Connector::isConnected()) {
                $serverLanguages = Connector::getInfo("translation");
                
                $serverLanguagesData = array();
                foreach($serverLanguages as $serverLanguage) {
                    $serverLanguagesData[] = array("id" => $serverLanguage, "language" => $this->languages[$serverLanguage]);
                } 
                
                $this->framework
                    ->list("Finder", array(
                        "data" => $serverLanguagesData,
                        "selectable" => true,
                        "multiple" => true,
                        "infoKey" => "languages",
                        "events" => array(
                            "event" => "addTranslationDownloads"
                        )
                    ));
            } else {
                $this->framework->html("SimpleHtml", array("html" => "You have no connection to ".PINION_URL));
            }
        }        
        
        
        
                    
        
        if($this->hasPermission("export language")) {
            // EXPORT LANGUAGE
            $this->framework
                ->startGroup("LazyMainTab", array(
                    "title" => "Export language",
                    "groupEvents" => true
                ))
                    ->list("Selector", array(
                        "data" => $existingLanguageData,
                        "label" => "language",
                        "events" => array(
                            "event" => "exportLanguage"
                        ),
                        "validators" => array(
                            "notEmpty" => true
                        )
                    ))
                    ->list("Selector", array(
                        "data" => array(
                            array("id" => "json"),
                            array("id" => "xml"),
                            array("id" => "csv"),
                            array("id" => "php")
                        ),
                        "noEmptyValue" => true,
                        "label" => "type",
                        "events" => array(
                            "event" => "exportLanguage"
                        )
                    ));
         }
         
         
         if($this->hasPermission("share language with pinion")) {
            // SHARE LANGUAGE
            $this->framework
                ->startGroup("LazyMainTab", array(
                    "title" => "Share language with pinion",
                    "groupEvents" => true
                ))
                    ->list("Selector", array(
                        "data" => $existingLanguageData,
                        "label" => "language",
                        "events" => array(
                            "event" => "shareLanguage"
                        ),
                        "validators" => array(
                            "notEmpty" => true
                        )
                    ));
         }
    }
    
    
    public function writeTranslationArray() {
        if($this->_hasSetbackendTranslation) {
            $backendTranslations = $this->data->all("backend");
        
            $translationAttributes = array();
            foreach($backendTranslations as $backendTranslation) {
                $word = $backendTranslation->word;
                $word = str_replace("'", "\\'", $word);
                
                $translation = $backendTranslation->translation;
                $translation = str_replace("'", "\\'", $translation);
                
                if(!isset($translationAttributes[$word])) {
                    $translationAttributes[$word] = array();
                }
                if(!isset($translationAttributes[$word][$backendTranslation->language])) {
                    $translationAttributes[$word][$backendTranslation->language] = array();
                }
                $translationAttributes[$word][$backendTranslation->language] = $translation;
            }
            
            $classWriter = new PHP_Class_Writer();
                $classWriter
                    ->setClass("BackendTranslation")
                    ->setNamespace(__NAMESPACE__)
                    ->addAttribute("translations", "static", $translationAttributes)
                    ->save(__DIR__);

            file_put_contents(__DIR__."/backendTranslation.js", "pinion.translations = ".json_encode($translationAttributes).";");
        }
    }
    
    
    public function getTranslator() {
        return new LanguageSwitcher();
    }
}

?>

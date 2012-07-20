<?php
/**
 * Module Settings
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/settings
 */

namespace modules\settings;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;
use \pinion\general\Registry;

class Settings extends Module {
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            if($this->hasPermission("delete revisions"))        $this->addEventListener("deleteRevisions");
            if($this->hasPermission("edit site name"))          $this->addEventListener("editSitename");
            if($this->hasPermission("edit timezone"))           $this->addEventListener("editTimezone");
            if($this->hasPermission("edit language"))           $this->addEventListener("editLanguage");
            if($this->hasPermission("edit date formats"))       $this->addEventListener("editDateformats");
            if($this->hasPermission("edit maintenance mode"))   $this->addEventListener("editMaintenance");
        }
    }
    
    public function editDateformats(Event $event) {
        $data = $event->getInfo("dateformats");
        
        $formats = array();
        foreach($data as $d) {
            $id = $d["id"];
            unset($d["id"]);
            $formats[$id] = $d;
        }
        Registry::setDateFormats($formats);
        
        $this->response->addSuccess($this, "edited date formats");
    }
    
    public function editLanguage(Event $event) {
        $language = $event->getInfo("language");
        
        Registry::setLanguage($language);
    }
    
    public function editTimezone(Event $event) {
        $timezone = $event->getInfo("timezone");
        
        Registry::setTimezone($timezone);
    }
    
    public function editSitename(Event $event) {
        $sitename = $event->getInfo("sitename");
        
        Registry::setSiteName($sitename);
    }
    
    public function editMaintenance(Event $event) {
        $maintenance = $event->getInfo("maintenance");
        
        if($maintenance) {
            Registry::enterMaintenanceMode();
        } else {
            Registry::exitMaintenanceMode();
        }
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "delete revisions",
            "edit site name",
            "edit timezone",
            "edit language",
            "edit date formats",
            "edit maintenance mode"
        ));
    }
    
    public function deleteRevisions(Event $event) {
        $models = new \DirectoryIterator(MODELS_PATH);
        foreach($models as $model) {
            if($model->isFile()) {
                $class = MODELS_NAMESPACE.$model->getBasename(".php");
                if($class::hasRevisions()) {
                    $class::massDelete("instance_id IS NOT NULL");
                }
            }
            
        }
    }
    
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Settings"));
        if($this->hasPermission("edit site name")) {
            $this->framework
                ->input("UpdateTextbox", array(
                        "label" => "site name",
                        "infoKey" => "sitename",
                        "value" => Registry::getSiteName(),
                        "events" => array(
                            "event" => "editSitename"
                        )
                    ));
        }
        if($this->hasPermission("edit timezone")) {
            $this->framework
                ->list("UpdateSelector", array(
                    "label" => "timezone",
                    "value" => Registry::getTimezone(),
                    "data" => array(        
                        array("id" => "Africa/Abidjan"),
                        array("id" => "Africa/Accra"),
                        array("id" => "Africa/Addis_Ababa"),
                        array("id" => "Africa/Algiers"),
                        array("id" => "Africa/Asmara"),
                        array("id" => "Africa/Asmera"),
                        array("id" => "Africa/Bamako"),
                        array("id" => "Africa/Bangui"),
                        array("id" => "Africa/Banjul"),
                        array("id" => "Africa/Bissau"),
                        array("id" => "Africa/Blantyre"),
                        array("id" => "Africa/Brazzaville"),
                        array("id" => "Africa/Bujumbura"),
                        array("id" => "Africa/Cairo"),
                        array("id" => "Africa/Casablanca"),
                        array("id" => "Africa/Ceuta"),
                        array("id" => "Africa/Conakry"),
                        array("id" => "Africa/Dakar"),
                        array("id" => "Africa/Dar_es_Salaam"),
                        array("id" => "Africa/Djibouti"),
                        array("id" => "Africa/Douala"),
                        array("id" => "Africa/El_Aaiun"),
                        array("id" => "Africa/Freetown"),
                        array("id" => "Africa/Gaborone"),
                        array("id" => "Africa/Harare"),
                        array("id" => "Africa/Johannesburg"),
                        array("id" => "Africa/Juba"),
                        array("id" => "Africa/Kampala"),
                        array("id" => "Africa/Khartoum"),
                        array("id" => "Africa/Kigali"),
                        array("id" => "Africa/Kinshasa"),
                        array("id" => "Africa/Lagos"),
                        array("id" => "Africa/Libreville"),
                        array("id" => "Africa/Lome"),
                        array("id" => "Africa/Luanda"),
                        array("id" => "Africa/Lubumbashi"),
                        array("id" => "Africa/Lusaka"),
                        array("id" => "Africa/Malabo"),
                        array("id" => "Africa/Maputo"),
                        array("id" => "Africa/Maseru"),
                        array("id" => "Africa/Mbabane"),
                        array("id" => "Africa/Mogadishu"),
                        array("id" => "Africa/Monrovia"),
                        array("id" => "Africa/Nairobi"),
                        array("id" => "Africa/Ndjamena"),
                        array("id" => "Africa/Niamey"),
                        array("id" => "Africa/Nouakchott"),
                        array("id" => "Africa/Ouagadougou"),
                        array("id" => "Africa/Porto-Novo"),
                        array("id" => "Africa/Sao_Tome"),
                        array("id" => "Africa/Timbuktu"),
                        array("id" => "Africa/Tripoli"),
                        array("id" => "Africa/Tunis"),
                        array("id" => "Africa/Windhoek"),

                        array("id" => "America/Adak"),
                        array("id" => "America/Anchorage"),
                        array("id" => "America/Anguilla"),
                        array("id" => "America/Antigua"),
                        array("id" => "America/Araguaina"),
                        array("id" => "America/Argentina/Buenos_Aires"),
                        array("id" => "America/Argentina/Catamarca"),
                        array("id" => "America/Argentina/ComodRivadavia"),
                        array("id" => "America/Argentina/Cordoba"),
                        array("id" => "America/Argentina/Jujuy"),
                        array("id" => "America/Argentina/La_Rioja"),
                        array("id" => "America/Argentina/Mendoza"),
                        array("id" => "America/Argentina/Rio_Gallegos"),
                        array("id" => "America/Argentina/Salta"),
                        array("id" => "America/Argentina/San_Juan"),
                        array("id" => "America/Argentina/San_Luis"),
                        array("id" => "America/Argentina/Tucuman"),
                        array("id" => "America/Argentina/Ushuaia"),
                        array("id" => "America/Aruba"),
                        array("id" => "America/Asuncion"),
                        array("id" => "America/Atikokan"),
                        array("id" => "America/Atka"),
                        array("id" => "America/Bahia"),
                        array("id" => "America/Bahia_Banderas"),
                        array("id" => "America/Barbados"),
                        array("id" => "America/Belem"),
                        array("id" => "America/Belize"),
                        array("id" => "America/Blanc-Sablon"),
                        array("id" => "America/Boa_Vista"),
                        array("id" => "America/Bogota"),
                        array("id" => "America/Boise"),
                        array("id" => "America/Buenos_Aires"),
                        array("id" => "America/Cambridge_Bay"),
                        array("id" => "America/Campo_Grande"),
                        array("id" => "America/Cancun"),
                        array("id" => "America/Caracas"),
                        array("id" => "America/Catamarca"),
                        array("id" => "America/Cayenne"),
                        array("id" => "America/Cayman"),
                        array("id" => "America/Chicago"),
                        array("id" => "America/Chihuahua"),
                        array("id" => "America/Coral_Harbour"),
                        array("id" => "America/Cordoba"),
                        array("id" => "America/Costa_Rica"),
                        array("id" => "America/Creston"),
                        array("id" => "America/Cuiaba"),
                        array("id" => "America/Curacao"),
                        array("id" => "America/Danmarkshavn"),
                        array("id" => "America/Dawson"),
                        array("id" => "America/Dawson_Creek"),
                        array("id" => "America/Denver"),
                        array("id" => "America/Detroit"),
                        array("id" => "America/Dominica"),
                        array("id" => "America/Edmonton"),
                        array("id" => "America/Eirunepe"),
                        array("id" => "America/El_Salvador"),
                        array("id" => "America/Ensenada"),
                        array("id" => "America/Fort_Wayne"),
                        array("id" => "America/Fortaleza"),
                        array("id" => "America/Glace_Bay"),
                        array("id" => "America/Godthab"),
                        array("id" => "America/Goose_Bay"),
                        array("id" => "America/Grand_Turk"),
                        array("id" => "America/Grenada"),
                        array("id" => "America/Guadeloupe"),
                        array("id" => "America/Guatemala"),
                        array("id" => "America/Guayaquil"),
                        array("id" => "America/Guyana"),
                        array("id" => "America/Halifax"),
                        array("id" => "America/Havana"),
                        array("id" => "America/Hermosillo"),
                        array("id" => "America/Indiana/Indianapolis"),
                        array("id" => "America/Indiana/Knox"),
                        array("id" => "America/Indiana/Marengo"),
                        array("id" => "America/Indiana/Petersburg"),
                        array("id" => "America/Indiana/Tell_City"),
                        array("id" => "America/Indiana/Vevay"),
                        array("id" => "America/Indiana/Vincennes"),
                        array("id" => "America/Indiana/Winamac"),
                        array("id" => "America/Indianapolis"),
                        array("id" => "America/Inuvik"),
                        array("id" => "America/Iqaluit"),
                        array("id" => "America/Jamaica"),
                        array("id" => "America/Jujuy"),
                        array("id" => "America/Juneau"),
                        array("id" => "America/Kentucky/Louisville"),
                        array("id" => "America/Kentucky/Monticello"),
                        array("id" => "America/Knox_IN"),
                        array("id" => "America/Kralendijk"),
                        array("id" => "America/La_Paz"),
                        array("id" => "America/Lima"),
                        array("id" => "America/Los_Angeles"),
                        array("id" => "America/Louisville"),
                        array("id" => "America/Lower_Princes"),
                        array("id" => "America/Maceio"),
                        array("id" => "America/Managua"),
                        array("id" => "America/Manaus"),
                        array("id" => "America/Marigot"),
                        array("id" => "America/Martinique"),
                        array("id" => "America/Matamoros"),
                        array("id" => "America/Mazatlan"),
                        array("id" => "America/Mendoza"),
                        array("id" => "America/Menominee"),
                        array("id" => "America/Merida"),
                        array("id" => "America/Metlakatla"),
                        array("id" => "America/Mexico_City"),
                        array("id" => "America/Miquelon"),
                        array("id" => "America/Moncton"),
                        array("id" => "America/Monterrey"),
                        array("id" => "America/Montevideo"),
                        array("id" => "America/Montreal"),
                        array("id" => "America/Montserrat"),
                        array("id" => "America/Nassau"),
                        array("id" => "America/New_York"),
                        array("id" => "America/Nipigon"),
                        array("id" => "America/Nome"),
                        array("id" => "America/Noronha"),
                        array("id" => "America/North_Dakota/Beulah"),
                        array("id" => "America/North_Dakota/Center"),
                        array("id" => "America/North_Dakota/New_Salem"),
                        array("id" => "America/Ojinaga"),
                        array("id" => "America/Panama"),
                        array("id" => "America/Pangnirtung"),
                        array("id" => "America/Paramaribo"),
                        array("id" => "America/Phoenix"),
                        array("id" => "America/Port-au-Prince"),
                        array("id" => "America/Port_of_Spain"),
                        array("id" => "America/Porto_Acre"),
                        array("id" => "America/Porto_Velho"),
                        array("id" => "America/Puerto_Rico"),
                        array("id" => "America/Rainy_River"),
                        array("id" => "America/Rankin_Inlet"),
                        array("id" => "America/Recife"),
                        array("id" => "America/Regina"),
                        array("id" => "America/Resolute"),
                        array("id" => "America/Rio_Branco"),
                        array("id" => "America/Rosario"),
                        array("id" => "America/Santa_Isabel"),
                        array("id" => "America/Santarem"),
                        array("id" => "America/Santiago"),
                        array("id" => "America/Santo_Domingo"),
                        array("id" => "America/Sao_Paulo"),
                        array("id" => "America/Scoresbysund"),
                        array("id" => "America/Shiprock"),
                        array("id" => "America/Sitka"),
                        array("id" => "America/St_Barthelemy"),
                        array("id" => "America/St_Johns"),
                        array("id" => "America/St_Kitts"),
                        array("id" => "America/St_Lucia"),
                        array("id" => "America/St_Thomas"),
                        array("id" => "America/St_Vincent"),
                        array("id" => "America/Swift_Current"),
                        array("id" => "America/Tegucigalpa"),
                        array("id" => "America/Thule"),
                        array("id" => "America/Thunder_Bay"),
                        array("id" => "America/Tijuana"),
                        array("id" => "America/Toronto"),
                        array("id" => "America/Tortola"),
                        array("id" => "America/Vancouver"),
                        array("id" => "America/Virgin"),
                        array("id" => "America/Whitehorse"),
                        array("id" => "America/Winnipeg"),
                        array("id" => "America/Yakutat"),
                        array("id" => "America/Yellowknife"),

                        array("id" => "Antarctica/Casey"),
                        array("id" => "Antarctica/Davis"),
                        array("id" => "Antarctica/DumontDUrville"),
                        array("id" => "Antarctica/Macquarie"),
                        array("id" => "Antarctica/Mawson"),
                        array("id" => "Antarctica/McMurdo"),
                        array("id" => "Antarctica/Palmer"),
                        array("id" => "Antarctica/Rothera"),
                        array("id" => "Antarctica/South_Pole"),
                        array("id" => "Antarctica/Syowa"),
                        array("id" => "Antarctica/Vostok"),

                        array("id" => "Arctic/Longyearbyen"),

                        array("id" => "Asia/Aden"),
                        array("id" => "Asia/Almaty"),
                        array("id" => "Asia/Amman"),
                        array("id" => "Asia/Anadyr"),
                        array("id" => "Asia/Aqtau"),
                        array("id" => "Asia/Aqtobe"),
                        array("id" => "Asia/Ashgabat"),
                        array("id" => "Asia/Ashkhabad"),
                        array("id" => "Asia/Baghdad"),
                        array("id" => "Asia/Bahrain"),
                        array("id" => "Asia/Baku"),
                        array("id" => "Asia/Bangkok"),
                        array("id" => "Asia/Beirut"),
                        array("id" => "Asia/Bishkek"),
                        array("id" => "Asia/Brunei"),
                        array("id" => "Asia/Calcutta"),
                        array("id" => "Asia/Choibalsan"),
                        array("id" => "Asia/Chongqing"),
                        array("id" => "Asia/Chungking"),
                        array("id" => "Asia/Colombo"),
                        array("id" => "Asia/Dacca"),
                        array("id" => "Asia/Damascus"),
                        array("id" => "Asia/Dhaka"),
                        array("id" => "Asia/Dili"),
                        array("id" => "Asia/Dubai"),
                        array("id" => "Asia/Dushanbe"),
                        array("id" => "Asia/Gaza"),
                        array("id" => "Asia/Harbin"),
                        array("id" => "Asia/Hebron"),
                        array("id" => "Asia/Ho_Chi_Minh"),
                        array("id" => "Asia/Hong_Kong"),
                        array("id" => "Asia/Hovd"),
                        array("id" => "Asia/Irkutsk"),
                        array("id" => "Asia/Istanbul"),
                        array("id" => "Asia/Jakarta"),
                        array("id" => "Asia/Jayapura"),
                        array("id" => "Asia/Jerusalem"),
                        array("id" => "Asia/Kabul"),
                        array("id" => "Asia/Kamchatka"),
                        array("id" => "Asia/Karachi"),
                        array("id" => "Asia/Kashgar"),
                        array("id" => "Asia/Kathmandu"),
                        array("id" => "Asia/Katmandu"),
                        array("id" => "Asia/Kolkata"),
                        array("id" => "Asia/Krasnoyarsk"),
                        array("id" => "Asia/Kuala_Lumpur"),
                        array("id" => "Asia/Kuching"),
                        array("id" => "Asia/Kuwait"),
                        array("id" => "Asia/Macao"),
                        array("id" => "Asia/Macau"),
                        array("id" => "Asia/Magadan"),
                        array("id" => "Asia/Makassar"),
                        array("id" => "Asia/Manila"),
                        array("id" => "Asia/Muscat"),
                        array("id" => "Asia/Nicosia"),
                        array("id" => "Asia/Novokuznetsk"),
                        array("id" => "Asia/Novosibirsk"),
                        array("id" => "Asia/Omsk"),
                        array("id" => "Asia/Oral"),
                        array("id" => "Asia/Phnom_Penh"),
                        array("id" => "Asia/Pontianak"),
                        array("id" => "Asia/Pyongyang"),
                        array("id" => "Asia/Qatar"),
                        array("id" => "Asia/Qyzylorda"),
                        array("id" => "Asia/Rangoon"),
                        array("id" => "Asia/Riyadh"),
                        array("id" => "Asia/Saigon"),
                        array("id" => "Asia/Sakhalin"),
                        array("id" => "Asia/Samarkand"),
                        array("id" => "Asia/Seoul"),
                        array("id" => "Asia/Shanghai"),
                        array("id" => "Asia/Singapore"),
                        array("id" => "Asia/Taipei"),
                        array("id" => "Asia/Tashkent"),
                        array("id" => "Asia/Tbilisi"),
                        array("id" => "Asia/Tehran"),
                        array("id" => "Asia/Tel_Aviv"),
                        array("id" => "Asia/Thimbu"),
                        array("id" => "Asia/Thimphu"),
                        array("id" => "Asia/Tokyo"),
                        array("id" => "Asia/Ujung_Pandang"),
                        array("id" => "Asia/Ulaanbaatar"),
                        array("id" => "Asia/Ulan_Bator"),
                        array("id" => "Asia/Urumqi"),
                        array("id" => "Asia/Vientiane"),
                        array("id" => "Asia/Vladivostok"),
                        array("id" => "Asia/Yakutsk"),
                        array("id" => "Asia/Yekaterinburg"),
                        array("id" => "Asia/Yerevan"),

                        array("id" => "Atlantic/Azores"),
                        array("id" => "Atlantic/Bermuda"),
                        array("id" => "Atlantic/Canary"),
                        array("id" => "Atlantic/Cape_Verde"),
                        array("id" => "Atlantic/Faeroe"),
                        array("id" => "Atlantic/Faroe"),
                        array("id" => "Atlantic/Jan_Mayen"),
                        array("id" => "Atlantic/Madeira"),
                        array("id" => "Atlantic/Reykjavik"),
                        array("id" => "Atlantic/South_Georgia"),
                        array("id" => "Atlantic/St_Helena"),
                        array("id" => "Atlantic/Stanley"),

                        array("id" => "Australia/ACT"),
                        array("id" => "Australia/Adelaide"),
                        array("id" => "Australia/Brisbane"),
                        array("id" => "Australia/Broken_Hill"),
                        array("id" => "Australia/Canberra"),
                        array("id" => "Australia/Currie"),
                        array("id" => "Australia/Darwin"),
                        array("id" => "Australia/Eucla"),
                        array("id" => "Australia/Hobart"),
                        array("id" => "Australia/LHI"),
                        array("id" => "Australia/Lindeman"),
                        array("id" => "Australia/Lord_Howe"),
                        array("id" => "Australia/Melbourne"),
                        array("id" => "Australia/North"),
                        array("id" => "Australia/NSW"),
                        array("id" => "Australia/Perth"),
                        array("id" => "Australia/Queensland"),
                        array("id" => "Australia/South"),
                        array("id" => "Australia/Sydney"),
                        array("id" => "Australia/Tasmania"),
                        array("id" => "Australia/Victoria"),
                        array("id" => "Australia/West"),
                        array("id" => "Australia/Yancowinna"),

                        array("id" => "Europe/Amsterdam"),
                        array("id" => "Europe/Andorra"),
                        array("id" => "Europe/Athens"),
                        array("id" => "Europe/Belfast"),
                        array("id" => "Europe/Belgrade"),
                        array("id" => "Europe/Berlin"),
                        array("id" => "Europe/Bratislava"),
                        array("id" => "Europe/Brussels"),
                        array("id" => "Europe/Bucharest"),
                        array("id" => "Europe/Budapest"),
                        array("id" => "Europe/Chisinau"),
                        array("id" => "Europe/Copenhagen"),
                        array("id" => "Europe/Dublin"),
                        array("id" => "Europe/Gibraltar"),
                        array("id" => "Europe/Guernsey"),
                        array("id" => "Europe/Helsinki"),
                        array("id" => "Europe/Isle_of_Man"),
                        array("id" => "Europe/Istanbul"),
                        array("id" => "Europe/Jersey"),
                        array("id" => "Europe/Kaliningrad"),
                        array("id" => "Europe/Kiev"),
                        array("id" => "Europe/Lisbon"),
                        array("id" => "Europe/Ljubljana"),
                        array("id" => "Europe/London"),
                        array("id" => "Europe/Luxembourg"),
                        array("id" => "Europe/Madrid"),
                        array("id" => "Europe/Malta"),
                        array("id" => "Europe/Mariehamn"),
                        array("id" => "Europe/Minsk"),
                        array("id" => "Europe/Monaco"),
                        array("id" => "Europe/Moscow"),
                        array("id" => "Europe/Nicosia"),
                        array("id" => "Europe/Oslo"),
                        array("id" => "Europe/Paris"),
                        array("id" => "Europe/Podgorica"),
                        array("id" => "Europe/Prague"),
                        array("id" => "Europe/Riga"),
                        array("id" => "Europe/Rome"),
                        array("id" => "Europe/Samara"),
                        array("id" => "Europe/San_Marino"),
                        array("id" => "Europe/Sarajevo"),
                        array("id" => "Europe/Simferopol"),
                        array("id" => "Europe/Skopje"),
                        array("id" => "Europe/Sofia"),
                        array("id" => "Europe/Stockholm"),
                        array("id" => "Europe/Tallinn"),
                        array("id" => "Europe/Tirane"),
                        array("id" => "Europe/Tiraspol"),
                        array("id" => "Europe/Uzhgorod"),
                        array("id" => "Europe/Vaduz"),
                        array("id" => "Europe/Vatican"),
                        array("id" => "Europe/Vienna"),
                        array("id" => "Europe/Vilnius"),
                        array("id" => "Europe/Volgograd"),
                        array("id" => "Europe/Warsaw"),
                        array("id" => "Europe/Zagreb"),
                        array("id" => "Europe/Zaporozhye"),
                        array("id" => "Europe/Zurich"),

                        array("id" => "Indian/Antananarivo"),
                        array("id" => "Indian/Chagos"),
                        array("id" => "Indian/Christmas"),
                        array("id" => "Indian/Cocos"),
                        array("id" => "Indian/Comoro"),
                        array("id" => "Indian/Kerguelen"),
                        array("id" => "Indian/Mahe"),
                        array("id" => "Indian/Maldives"),
                        array("id" => "Indian/Mauritius"),
                        array("id" => "Indian/Mayotte"),
                        array("id" => "Indian/Reunion"),

                        array("id" => "Pacific/Apia"),
                        array("id" => "Pacific/Auckland"),
                        array("id" => "Pacific/Chatham"),
                        array("id" => "Pacific/Chuuk"),
                        array("id" => "Pacific/Easter"),
                        array("id" => "Pacific/Efate"),
                        array("id" => "Pacific/Enderbury"),
                        array("id" => "Pacific/Fakaofo"),
                        array("id" => "Pacific/Fiji"),
                        array("id" => "Pacific/Funafuti"),
                        array("id" => "Pacific/Galapagos"),
                        array("id" => "Pacific/Gambier"),
                        array("id" => "Pacific/Guadalcanal"),
                        array("id" => "Pacific/Guam"),
                        array("id" => "Pacific/Honolulu"),
                        array("id" => "Pacific/Johnston"),
                        array("id" => "Pacific/Kiritimati"),
                        array("id" => "Pacific/Kosrae"),
                        array("id" => "Pacific/Kwajalein"),
                        array("id" => "Pacific/Majuro"),
                        array("id" => "Pacific/Marquesas"),
                        array("id" => "Pacific/Midway"),
                        array("id" => "Pacific/Nauru"),
                        array("id" => "Pacific/Niue"),
                        array("id" => "Pacific/Norfolk"),
                        array("id" => "Pacific/Noumea"),
                        array("id" => "Pacific/Pago_Pago"),
                        array("id" => "Pacific/Palau"),
                        array("id" => "Pacific/Pitcairn"),
                        array("id" => "Pacific/Pohnpei"),
                        array("id" => "Pacific/Ponape"),
                        array("id" => "Pacific/Port_Moresby"),
                        array("id" => "Pacific/Rarotonga"),
                        array("id" => "Pacific/Saipan"),
                        array("id" => "Pacific/Samoa"),
                        array("id" => "Pacific/Tahiti"),
                        array("id" => "Pacific/Tarawa"),
                        array("id" => "Pacific/Tongatapu"),
                        array("id" => "Pacific/Truk"),
                        array("id" => "Pacific/Wake"),
                        array("id" => "Pacific/Wallis"),
                        array("id" => "Pacific/Yap")
                    ),
                    "events" => array(
                        "event" => "editTimezone"
                    )
                ));
        }
        
        if($this->hasPermission("edit language")) {
            $languageData = array();
            foreach($this->module("translation")->languages as $short => $large) {

                $languageData[] = array(
                    "id" => $short,
                    "name" => $large
                );
            }

            $this->framework
                ->list("UpdateSelector", array(
                    "label" => "main language",
                    "infoKey" => "language",
                    "value" => Registry::getLanguage(),
                    "data" => $languageData,
                    "events" => array(
                        "event" => "editLanguage"
                    )
                ));
        }
        
        $languages = Registry::getDateFormats();
        $languagesData = array();
        foreach($languages as $id => $dateFormats) {
            $languagesData[] = array("id" => $id, "language" => $this->module("translation")->languages[$id], "formats" => $dateFormats);
        }
        
        $this->framework
            ->list("Finder", array(
                "data" => $languagesData,
                "label" => "date formats",
                "scrollable" => true,
                "renderer" => array(
                    "name" => "DateFormatRenderer",
                    "events" => array(
                        "event" => "editDateformats"
                    ),
                    "groupEvents" => true
                ),
                "groupEvents" => "dateformats"
            ));
        
        
        if($this->hasPermission("edit maintenance mode")) {

            $this->framework
                ->input("Checkbox", array(
                    "label" => "maintenance mode",
                    "infoKey" => "maintenance",
                    "value" => Registry::inMaintenanceMode(),
                    "events" => array(
                        "event" => "editMaintenance"
                    )
                ));
        }
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Revisions"));
        if($this->hasPermission("delete revisions")) {
            $this->framework
                ->input("Switcher", array(
                        "label" => "delete revisions",
                        "events" => array(
                            "event" => "deleteRevisions"
                        )
                    ));
        }
        
        $this->framework 
            ->startGroup("LazyMainTab", array("title" => "System"));
            
    }
    
    
}


?>
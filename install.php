<?php

$version = parse_ini_file("pinion/info.ini", true);
$version = $version["version"];

$corePackage = array(
    "settings",
    "permissions",
    "translation",
    "jquery",
    "jqueryui",
    "page",
    "backend",
    "message",
    "modulesinfo",
    "fileupload",
    "image"
);

$modulePackage = array_merge($corePackage, array(
    "cache",
    "container",
    "development",
    "code",
    "headline",
    "menu",
    "news",
    "nivoslideshow",
    "template",
    "textfield",
    "comment",
    "googleanalytics",
    "search",
    "googleplusone",
    "downloader",
    "video",
    "audio",
    "link",
    "youtube",
    "facebooklike",
    "colorbox"
));

if(isset($_POST["backgroundColor"], $_POST["headerColor"], $_POST["footerColor"], $_POST["headerTextColor"], $_POST["footerTextColor"])) {
    include_once "pinion/php/include/paths.inc.php";
    include_once "pinion/php/include/autoload.inc.php";
    
    pinion\files\classwriter\INI_Writer::writeWithArray(array(
        "variables" => array(
            "pageBackground" => array(
                "value" => $_POST["backgroundColor"],
                "editor" => "input.ColorPicker"
            ),
            "headerBackground" => array(
                "value" => $_POST["headerColor"],
                "editor" => "input.ColorPicker"
            ),
            "footerBackground" => array(
                "value" => $_POST["footerColor"],
                "editor" => "input.ColorPicker"
            ),
            "headerText" => array(
                "value" => $_POST["headerTextColor"],
                "editor" => "input.ColorPicker"
            ),
            "footerText" => array(
                "value" => $_POST["footerTextColor"],
                "editor" => "input.ColorPicker"
            )
        )
    ), APPLICATION_PATH."templates/basic/page/info.ini", true);
    die();
} elseif(isset($_POST["install"])) {
    $module = $_POST["install"];
    
    include_once "pinion/php/include/paths.inc.php";
    include_once "pinion/php/include/autoload.inc.php";
    include_once "pinion/php/include/db.inc.php";
    
    $dbModule = \pinion\data\models\Pinion_module::find_by_name($module);
    $dbModule->enabled = true;
    $dbModule->save();
    
    session_start();
    $_SESSION["refresh"] = true;
    
    die();
} elseif(isset($_POST["sendmail"], $_POST["admin_email"], $_POST["admin_firstname"], $_POST["admin_lastname"], $_POST["admin_username"], $_POST["admin_password"])) {
    include_once "pinion/php/include/paths.inc.php";
    include_once "pinion/php/include/autoload.inc.php";
    $mail = new Zend_Mail("UTF-8");
    $mail
        ->setBodyText("Hello {$_POST["admin_firstname"]} {$_POST["admin_lastname"]},\n\nyou have created the following account:\nUsername: {$_POST["admin_username"]}\nPassword: {$_POST["admin_password"]}")
        ->setFrom('friedolin.foerder@pinion-cms.org', 'pinion cms')
        ->addTo($_POST["admin_email"], "{$_POST["admin_firstname"]} {$_POST["admin_lastname"]}")
        ->setSubject('thank you for installing pinion')
        ->send();
    
    die();
}

//// usage
use pinion\data\database\DBActiveRecordAdminister;
use pinion\files\classwriter\PHP_Class_Writer;

if($request->hasPostParameter("database", "username", "password", "host")) {
    $driver = "mysql";
    $database = trim($request->getPostParameter("database"));
    $username = trim($request->getPostParameter("username"));
    $password = trim($request->getPostParameter("password"));
    $host = trim($request->getPostParameter("host"));
    $tablePrefix = trim($request->getPostParameter("tableprefix"));

    if(empty($database)) {
        die('{"error": {"en": "Please type in the database name", "de": "Bitte gib den Namen der Datenbank an"}}');
    } elseif(empty($username)) {
        die('{"error": {"en": "Please type in the username", "de": "Bitte gib den Benutzernamen ein"}}');
    } elseif(empty($host)) {
        die('{"error": {"en": "Please type in the host", "de": "Bitte gib den Host ein"}}');
    } else {
        
        $pdo = new PDO("$driver:host=$host", $username, $password);
        $pdo->query("CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci") or die('{"error": {"en": "There is no database connection. Please check your parameters.", "de": "Es besteht keine Datenbankverbindung. Bitte überprüfe deine Angaben"}}');
    
        $salt = uniqid("", true);
        
        pinion\files\classwriter\INI_Writer::writeWithArray(array(
            "database" => array(
                "driver" => "mysql",
                "host" => $host,
                "name" => $database,
                "username" => $username,
                "password" => $password,
                "prefix" => $tablePrefix,
                "salt" => $salt
            )
        ), APPLICATION_PATH."pinion/php/data/database/database.ini", true);
        

        $modelNamespace = "pinion\\data\\models";

        $classWriter = new PHP_Class_Writer();
        $classWriter
            ->setNamespace($modelNamespace)
            ->setUses(array("\\pinion\\data\\database\\ActiveRecord"));

        $administer = new DBActiveRecordAdminister($classWriter, "pinion/php/data/models", "\\$modelNamespace\\", "pinion/php/data/database/database.ini");
        $administer
            ->createDataStorage("pinion_module", array(
                "name"             => array("type" => "varchar", "length" => 100, "translatable" => false),
                "title"            => array("type" => "varchar", "length" => 100, "isNull" => true),
                "description"      => array("type" => "varchar", "length" => 500, "isNull" => true),
                "enabled"          => array("type" => "boolean"),
                "usable"           => array("type" => "boolean"),
                "installed"        => array("type" => "boolean"),
                "core"             => array("type" => "boolean"),
                "dependencies"     => array("type" => "varchar", "length" => 500, "translatable" => false),
                "replacement"      => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "category"         => array("type" => "varchar", "length" => 100, "translatable" => false),
                "version"          => array("type" => "varchar"),
                "author"           => array("type" => "varchar", "length" => 100, "isNull" => true, "translatable" => false),
                "user_id"          => array("type" => "int", "isNull" => true)
            ), array(
                "users" => false
            ), "pinion")
            ->createDataStorage("pinion_setting", array(
                "headrevision" => array("type" => "int"),
                "maintenance"  => array("type" => "boolean"),
                "sitename"     => array("type" => "varchar", "length" => 100),
                "template"     => array("type" => "varchar", "length" => 100, "translatable" => false),
                "language"     => array("type" => "varchar", "length" => 10, "translatable" => false),
                "translations" => array("type" => "varchar", "length" => 500, "translatable" => false),
                "dateformat"   => array("type" => "varchar", "length" => 500, "translatable" => false),
                "timezone"     => array("type" => "varchar", "length" => 30, "translatable" => false),
                "user_id"      => array("type" => "int", "isNull" => true)
            ), array(
                "users" => false
            ), "pinion")
            ->updateClasses();
        
        $tempLanguages = $request->getPostParameter("languages");
        $languages = array();
        if(is_array($tempLanguages)) {
            foreach($tempLanguages as $language) {
                $languages[$language] = array(
                    "default" => "m/d/Y H:i"
                );
            }
        }
        $languages = json_encode($languages);
        $sitename = $request->getPostParameter("sitename");
        $timezone = $request->getPostParameter("timezone");
        $language = $request->getPostParameter("language");
        
        // add standard settings
        $time = time();
        $pdo
            ->exec('INSERT INTO `'.$database.'`.`'.$tablePrefix.'pinion_setting` (
                `revision`,
                `deleted`,
                `headrevision`,
                `maintenance`,
                `sitename`,
                `template`,
                `language`,
                `translations`,
                `dateformat`,
                `timezone`,
                `updated`,
                `created`
            ) VALUES (
                0,
                0,
                0,
                0,
                \''.$sitename.'\',
                \'basic\',
                \''.$language.'\',
                \''.$languages.'\',
                \''.json_encode(array("default" => "m/d/Y H:i")).'\',
                \''.$timezone.'\',
                '.$time.',
                '.$time.'
            );');

        // refresh modules
        $_SESSION["refresh"] = true; // you must not start the session, because the user was logged out
        die('{}');
    }
}


?>
<html>
    <head>
        <title>pinion installation</title>
        <meta charset="utf-8" />
        <style>@import url("http://fonts.googleapis.com/css?family=Asap:700italic,700,400italic,400");
.colorpicker {
  width:356px;
  height:176px;
  overflow:hidden;
  position:absolute;
  background:url(pinion/assets/images/colorpicker/pinion_colorpicker_background.png);
  font-family:Arial, Helvetica, sans-serif;
  display:none;
}
.colorpicker input {
  background-color:transparent;
  border:1px solid transparent;
  position:absolute;
  font-size:10px;
  font-family:Arial, Helvetica, sans-serif;
  color:#898989;
  top:4px;
  right:11px;
  text-align:right;
  margin:0;
  padding:0;
  height:11px;
  line-height:11px;
  cursor:text;
}
.colorpicker .colorpicker_color {
  width:150px;
  height:150px;
  left:14px;
  top:13px;
  position:absolute;
  background:#ff0000;
  overflow:hidden;
  cursor:crosshair;
}
.colorpicker .colorpicker_color div {
  position:absolute;
  top:0;
  left:0;
  width:150px;
  height:150px;
  background:url(pinion/assets/images/colorpicker/pinion_colorpicker_overlay.png);
}
.colorpicker .colorpicker_color div div {
  position:absolute;
  top:0;
  left:0;
  width:11px;
  height:11px;
  overflow:hidden;
  background:url(pinion/assets/images/colorpicker/pinion_colorpicker_select.gif);
  margin:-5px 0 0 -5px;
}
.colorpicker .colorpicker_hue {
  position:absolute;
  top:13px;
  left:171px;
  width:35px;
  height:150px;
  cursor:n-resize;
}
.colorpicker .colorpicker_hue div {
  position:absolute;
  width:35px;
  height:9px;
  overflow:hidden;
  background:url(pinion/assets/images/colorpicker/pinion_colorpicker_indic.png) left top;
  margin:-4px 0 0 0;
  left:0px;
}
.colorpicker .colorpicker_new_color {
  position:absolute;
  width:60px;
  height:30px;
  left:213px;
  top:13px;
  background:#ff0000;
}
.colorpicker .colorpicker_current_color {
  position:absolute;
  width:60px;
  height:30px;
  left:283px;
  top:13px;
  background:#ff0000;
}
.colorpicker .colorpicker_hex {
  position:absolute;
  width:72px;
  height:22px;
  background:url(pinion/assets/images/colorpicker/pinion_colorpicker_hex.png) top;
  left:212px;
  top:142px;
}
.colorpicker .colorpicker_hex input { right:6px; }
.colorpicker .colorpicker_hex.colorpicker_focus { background-position:bottom; }
.colorpicker .colorpicker_field {
  height:22px;
  width:62px;
  background-position:top;
  position:absolute;
}
.colorpicker .colorpicker_field span {
  position:absolute;
  width:12px;
  height:22px;
  overflow:hidden;
  top:0;
  right:0;
  cursor:n-resize;
}
.colorpicker .colorpicker_field.colorpicker_rgb_r {
  background-image:url(pinion/assets/images/colorpicker/pinion_colorpicker_rgb_r.png);
  top:52px;
  left:212px;
}
.colorpicker .colorpicker_field.colorpicker_rgb_g {
  background-image:url(pinion/assets/images/colorpicker/pinion_colorpicker_rgb_g.png);
  top:82px;
  left:212px;
}
.colorpicker .colorpicker_field.colorpicker_rgb_b {
  background-image:url(pinion/assets/images/colorpicker/pinion_colorpicker_rgb_b.png);
  top:112px;
  left:212px;
}
.colorpicker .colorpicker_field.colorpicker_hsb_h {
  background-image:url(pinion/assets/images/colorpicker/pinion_colorpicker_hsb_h.png);
  top:52px;
  left:282px;
}
.colorpicker .colorpicker_field.colorpicker_hsb_s {
  background-image:url(pinion/assets/images/colorpicker/pinion_colorpicker_hsb_s.png);
  top:82px;
  left:282px;
}
.colorpicker .colorpicker_field.colorpicker_hsb_b {
  background-image:url(pinion/assets/images/colorpicker/pinion_colorpicker_hsb_b.png);
  top:112px;
  left:282px;
}
.colorpicker .colorpicker_focus { background-position:center; }
.colorpicker .colorpicker_slider { background-position:bottom; }
.colorpicker .colorpicker_submit {
  position:absolute;
  width:22px;
  height:22px;
  background:url(pinion/assets/images/colorpicker/pinion_colorpicker_submit.png) top;
  left:322px;
  top:142px;
  overflow:hidden;
}
.colorpicker .colorpicker_submit.colorpicker_focus { background-position:bottom; }
body {
  background-color:#185859;
  background:url("pinion/assets/images/backgrounds/noise-bg.png");
}
#pinion-start {
  position:absolute;
  top:0;
  left:0;
  width:100%;
  height:100%;
  background:url("pinion/assets/images/backgrounds/noise-bg.png");
  z-index:1;
}
#pinion-start #pinion-start-logo {
  position:absolute;
  top:50%;
  left:50%;
  margin-left:-78px;
  margin-top:-170px;
  width:184px;
  height:95px;
  background:url('pinion/assets/images/pinion-logo-white.png');
}
#pinion-start #welcome-text {
  position:absolute;
  width:500px;
  left:50%;
  margin-left:-250px;
  text-align:center;
  top:50%;
  margin-top:-60px;
  font-family:'Asap', sans-serif;
  font-weight:700;
  font-style:italic;
  font-size:21px;
  color:#edf2f2;
}
#pinion-start ul#pinion-start-languages {
  position:absolute;
  width:256px;
  left:50%;
  padding:0;
  margin:0;
  margin-left:-128px;
  text-align:center;
  top:50%;
  color:#edf2f2;
  list-style:none;
}
#pinion-start ul#pinion-start-languages li {
  cursor:pointer;
  float:left;
}
#pinion-start ul#pinion-start-languages li .pinion-lang-icon {
  width:128px;
  height:128px;
}
#pinion-start ul#pinion-start-languages li .pinion-lang-icon.en { background:url("pinion/assets/images/icons/pinion-flag-big-en.png"); }
#pinion-start ul#pinion-start-languages li .pinion-lang-icon.de { background:url("pinion/assets/images/icons/pinion-flag-big-de.png"); }
#pinion-start ul#pinion-start-languages li .pinion-lang-title {
  font-family:Tahoma, Arial, Helvetica, sans-serif;
  font-size:11px;
  color:#edf2f2;
}
#pinion-start #pinion-start-bottom {
  position:fixed;
  left:0;
  bottom:0;
  z-index:-1;
  width:100%;
  height:40px;
  background:url("pinion/assets/images/backgrounds/noise-bg-bottom.png");
}
#language-link {
  position:fixed;
  right:20px;
  bottom:20px;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
  font-size:11px;
  color:#8c9b9b;
  text-decoration:none;
}
#language-link:hover { color:#edf2f2; }
#pinion-install {
  position:absolute;
  top:20%;
  left:50%;
  margin-left:-350px;
  margin-top:-60px;
  padding-bottom:100px;
  width:700px;
  min-height:300px;
  color:#1a292b;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
}
#pinion-install > #pinion-logo {
  position:absolute;
  top:0px;
  left:20px;
  width:184px;
  height:95px;
  background:url('pinion/assets/images/pinion-logo-white.png');
}
#pinion-install > #pinion-version {
  position:absolute;
  top:71px;
  left:203px;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
  font-size:11px;
  color:#edf2f2;
}
#pinion-install > .pinion-box {
  width:100%;
  padding-bottom:25px;
  background:#edf2f2;
  position:relative;
  display:block;
}
#pinion-install > .pinion-box > ol#pinion-menu {
  position:absolute;
  top:-47px;
  right:50px;
}
#pinion-install > .pinion-box > ol#pinion-menu li {
  display:block;
  margin-left:10px;
  cursor:pointer;
  float:left;
  white-space:nowrap;
  text-align:right;
  padding:0px 10px;
  background:#185859;
  -webkit-border-top-left-radius:5px;
  -webkit-border-top-right-radius:5px;
  -khtml-border-top-left-radius:5px;
  -khtml-border-top-right-radius:5px;
  -moz-border-radius-topleft:5px;
  -moz-border-radius-topright:5px;
  border-top-left-radius:5px;
  border-top-right-radius:5px;
  border-left:1px solid #8c9b9b;
  border-top:1px solid #8c9b9b;
  border-right:1px solid #8c9b9b;
}
#pinion-install > .pinion-box > ol#pinion-menu li span.step {
  line-height:30px;
  font-size:14px;
  font-weight:bold;
  color:#edf2f2;
}
#pinion-install > .pinion-box > ol#pinion-menu li span.step-title {
  display:none;
  position:absolute;
  bottom:0;
  left:-165px;
  text-align:right;
  line-height:30px;
  font-weight:bold;
  color:#edf2f2;
  font-size:12px;
  width:200px;
}
#pinion-install > .pinion-box > ol#pinion-menu li:hover span.step-title { display:block; }
#pinion-install > .pinion-box > ol#pinion-menu li:hover, #pinion-install > .pinion-box > ol#pinion-menu li.active { background:#edf2f2; }
#pinion-install > .pinion-box > ol#pinion-menu li:hover span.step, #pinion-install > .pinion-box > ol#pinion-menu li.active span.step { color:#185859; }
#pinion-install > .pinion-box > h1.pinion-installation {
  position:absolute;
  top:10px;
  right:100px;
  font-family:'Asap', sans-serif;
  font-weight:700;
  font-style:italic;
  font-size:40px;
  color:#185859;
}
#pinion-install > .pinion-box > #pinion-install-page-user, #pinion-install > .pinion-box > #pinion-install-page-site, #pinion-install > .pinion-box > #pinion-install-page-database, #pinion-install > .pinion-box > #pinion-install-page-install, #pinion-install > .pinion-box > #pinion-install-page-complete {
  margin-top:120px;
  padding-top:100px;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
}
#pinion-install > .pinion-box > #pinion-install-page-user > h2, #pinion-install > .pinion-box > #pinion-install-page-site > h2, #pinion-install > .pinion-box > #pinion-install-page-database > h2, #pinion-install > .pinion-box > #pinion-install-page-install > h2, #pinion-install > .pinion-box > #pinion-install-page-complete > h2 {
  font-family:'Asap', sans-serif;
  font-weight:700;
  font-style:italic;
  font-size:28px;
  color:#185859;
  margin-left:50px;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > div, #pinion-install > .pinion-box > #pinion-install-page-site > form > div, #pinion-install > .pinion-box > #pinion-install-page-database > form > div, #pinion-install > .pinion-box > #pinion-install-page-install > form > div, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div {
  clear:both;
  margin-left:50px;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > label, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > label, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > label, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > label, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > label {
  width:125px;
  font-style:italic;
  font-size:13px;
  color:#185859;
  margin-bottom:10px;
  float:left;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-multipleSelect {
  float:left;
  -webkit-border-radius:5px;
  -khtml-border-radius:5px;
  -moz-border-radius:5px;
  border-radius:5px;
  border:1px solid ;
  width:225px;
  background:#ffffff;
  margin-bottom:3px;
  padding-left:3px;
  padding-right:0;
  line-height:15px;
  margin-bottom:10px;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-input:hover, #pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-select:hover, #pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-multipleSelect:hover, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-input:hover, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-select:hover, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-multipleSelect:hover, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-input:hover, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-select:hover, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-multipleSelect:hover, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-input:hover, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-select:hover, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-multipleSelect:hover, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-input:hover, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-select:hover, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-multipleSelect:hover { background:#f5f5f5; }
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-input:focus, #pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-select:focus, #pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-multipleSelect:focus, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-input:focus, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-select:focus, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-multipleSelect:focus, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-input:focus, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-select:focus, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-multipleSelect:focus, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-input:focus, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-select:focus, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-multipleSelect:focus, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-input:focus, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-select:focus, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-multipleSelect:focus {
  border:1px solid #1a292b;
  background:#f9f9f9;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-input, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-input { cursor:text; }
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-select, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-select { cursor:default; }
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .pinion-install-multipleSelect, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .pinion-install-multipleSelect {
  height:150px;
  cursor:default;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > div > .description, #pinion-install > .pinion-box > #pinion-install-page-site > form > div > .description, #pinion-install > .pinion-box > #pinion-install-page-database > form > div > .description, #pinion-install > .pinion-box > #pinion-install-page-install > form > div > .description, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div > .description {
  padding-left:10px;
  font-style:italic;
  font-size:13px;
  color:#8c9b9b;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > div:hover > .description, #pinion-install > .pinion-box > #pinion-install-page-site > form > div:hover > .description, #pinion-install > .pinion-box > #pinion-install-page-database > form > div:hover > .description, #pinion-install > .pinion-box > #pinion-install-page-install > form > div:hover > .description, #pinion-install > .pinion-box > #pinion-install-page-complete > form > div:hover > .description { color:#1a292b; }
#pinion-install > .pinion-box > #pinion-install-page-user > form > .pinion-backend-input-Button, #pinion-install > .pinion-box > #pinion-install-page-site > form > .pinion-backend-input-Button, #pinion-install > .pinion-box > #pinion-install-page-database > form > .pinion-backend-input-Button, #pinion-install > .pinion-box > #pinion-install-page-install > form > .pinion-backend-input-Button, #pinion-install > .pinion-box > #pinion-install-page-complete > form > .pinion-backend-input-Button {
  height:17px;
  width:600px;
  padding:0 50px;
  margin-left:0px;
  border-top:1px solid #8c9b9b;
  background:#edf2f2;
}
#pinion-install > .pinion-box > #pinion-install-page-user > form > .pinion-backend-input-Button > input, #pinion-install > .pinion-box > #pinion-install-page-site > form > .pinion-backend-input-Button > input, #pinion-install > .pinion-box > #pinion-install-page-database > form > .pinion-backend-input-Button > input, #pinion-install > .pinion-box > #pinion-install-page-install > form > .pinion-backend-input-Button > input, #pinion-install > .pinion-box > #pinion-install-page-complete > form > .pinion-backend-input-Button > input {
  margin:12px 10px 20px 0;
  -webkit-border-radius:5px;
  -khtml-border-radius:5px;
  -moz-border-radius:5px;
  border-radius:5px;
  border:1px solid #8c9b9b;
  cursor:pointer;
  font-weight:900;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
  color:#1a292b;
  padding:5px;
  display:inline-block;
}
#pinion-install > .pinion-box > #pinion-install-page-user > #pinion-install-module, #pinion-install > .pinion-box > #pinion-install-page-site > #pinion-install-module, #pinion-install > .pinion-box > #pinion-install-page-database > #pinion-install-module, #pinion-install > .pinion-box > #pinion-install-page-install > #pinion-install-module, #pinion-install > .pinion-box > #pinion-install-page-complete > #pinion-install-module {
  font-style:italic;
  font-size:13px;
  color:#185859;
  margin-left:50px;
}
#pinion-install > .pinion-box > #pinion-install-page-install > #pinion-install-progressbar {
  -webkit-border-radius:5px;
  -khtml-border-radius:5px;
  -moz-border-radius:5px;
  border-radius:5px;
  border:1px solid #8c9b9b;
  background:#ffffff;
  width:600px;
  height:20px;
  position:relative;
  margin-left:50px;
  margin-top:25px;
  margin-bottom:25px;
}
#pinion-install > .pinion-box > #pinion-install-page-install > #pinion-install-progressbar > .progress {
  position:absolute;
  top:0;
  left:0;
  height:100%;
  background:#1a292b;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list {
  list-style:none;
  padding-left:50px;
  padding-bottom:25px;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li {
  height:30px;
  width:590px;
  padding:5px;
  background:#ffffff;
  margin-bottom:1px;
  border:1px solid #8c9b9b;
  position:relative;
  clear:both;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li:after {
  content:".";
  display:block;
  clear:both;
  visibility:hidden;
  line-height:0;
  height:0;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li > .pinion-module-icon {
  width:30px;
  height:30px;
  overflow:hidden;
  float:left;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li > .pinion-module-icon > img {
  width:30px;
  margin-top:-30px;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li > .pinion-module-name {
  font-weight:900;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
  color:#1a292b;
  font-size:14px;
  line-height:30px;
  float:left;
  padding-left:10px;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li > .pinion-module-status {
  position:absolute;
  right:5px;
  top:5px;
  width:30px;
  height:30px;
  background:#8c9b9b;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li.installed > .pinion-module-status {
  background:url('pinion/assets/images/icons/pinion-icons.png');
  background-position:-70px -140px;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li.notInstalled > .pinion-module-status {
  background:url('pinion/assets/images/icons/pinion-icons.png');
  background-position:-180px -140px;
}
#pinion-install > .pinion-box > #pinion-install-page-install > ul#pinion-install-list > li.installNow > .pinion-module-status { background:#ffffff; }
#pinion-install > .pinion-box > #pinion-install-page-complete > a#backendLink {
  text-decoration:none;
  font-size:13px;
  margin:12px 0px 20px 50px;
  -webkit-border-radius:5px;
  -khtml-border-radius:5px;
  -moz-border-radius:5px;
  border-radius:5px;
  border:1px solid #8c9b9b;
  cursor:pointer;
  font-weight:900;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
  color:#1a292b;
  padding:5px;
  display:inline-block;
}
#pinion-install > .pinion-box > #message {
  position:absolute;
  right:22px;
  bottom:22px;
  color:#e1604b;
  font-family:Tahoma, Arial, Helvetica, sans-serif;
  font-size:12px;
  font-weight:900;
}
#pinion-install-bottom {
  position:fixed;
  left:0;
  bottom:0;
  z-index:-1;
  width:100%;
  height:40px;
  background:url("pinion/assets/images/backgrounds/noise-bg-bottom.png");
}
</style>
        <link rel="stylesheet" type="text/css" href="pinion/js/colorpicker/css/colorpicker.css"></link>
    </head>
    <body>
        <div id="pinion-start">
            <div id="pinion-start-logo"></div>
            <div id="welcome-text" class="translated" data-lang-de="Willkommen bei der Installation von pinion<br />Bitte wähle die Sprache für die Installation aus:">
                Welcome to the installation of pinion<br />Please choose the language for the installation:
            </div>
            <ul id="pinion-start-languages">
                <li data-lang="en">
                    <div class="pinion-lang-icon en"></div>
                    <div class="pinion-lang-title translated" data-lang-de="Englisch">english</div>
                </li>
                <li data-lang="de">
                    <div class="pinion-lang-icon de"></div>
                    <div class="pinion-lang-title translated" data-lang-de="Deutsch">german</div>
                </li>
            </ul>
            <div id="pinion-start-bottom"></div>
        </div>
        <a id="language-link" href="#" class="translated" data-lang-de="Zur Sprachauswahl">Go to language selection</a>
        <div id="pinion-install">
            <div id="pinion-logo"></div>
            <div id="pinion-version"><?php print $version ?></div>
            <div class="pinion-box">
                <ol id="pinion-menu">
                    <li data-id="pinion-install-page-user" class="step1 active"><span class="step">1</span><span class="step-title translated" data-lang-de="Informationen über den Benutzer">User information</span></li>
                    <li data-id="pinion-install-page-site" class="step2"><span class="step">2</span><span class="step-title translated" data-lang-de="Informationen über die Seite">Site information</span></li>
                    <li data-id="pinion-install-page-database" class="step3"><span class="step">3</span><span class="step-title translated" data-lang-de="Informationen über die Datenbank">Database information</span></li>
                    <li data-id="pinion-install-page-install" class="step4"><span class="step">4</span><span class="step-title translated" data-lang-de="Installation">Installation</span></li>
                    <li data-id="pinion-install-page-complete" class="step5"><span class="step">5</span><span class="step-title translated" data-lang-de="Geh zu deiner Seite">Go to your Site</span></li>         
                </ol>
                <h1 class="pinion-installation translated">pinion installation</h1>
                <div id="pinion-install-page-user">
                    <h2 class="translated" data-lang-de="1. Informationen über den Benutzer">1. User information</h2>
                    <form id="userForm" method="post">
                        <div>
                            <label class="translated" data-lang-de="Vorname">firstname</label><input class="pinion-install-input" type="text" name="firstname" value=""></input>
                            <span class="description translated" data-lang-de="Gib deinen Vornamen ein">Type in your firstname</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Nachname">lastname</label><input class="pinion-install-input" type="text" name="lastname" value=""></input>
                            <span class="description translated" data-lang-de="Gib deinen Nachnamen ein">Type in your lastname</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Email">email</label><input class="pinion-install-input" type="text" name="email" value=""></input>
                            <span class="description translated" data-lang-de="Gib deine Email-Adresse ein">Type in your email address</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Benutzername">username</label><input class="pinion-install-input" type="text" name="username" value=""></input>
                            <span class="description translated" data-lang-de="Gib deinen Benutzernamen ein">Type in your username</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Passwort">password</label><input class="pinion-install-input" type="password" name="password" value=""></input>
                            <span class="description translated" data-lang-de="Gib ein Passwort ein">Type in your password</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Passwort-Bestätigung">confirm password</label><input class="pinion-install-input" type="password" name="confirmpassword" value=""></input>
                            <span class="description translated" data-lang-de="Bestätige dein Passwort">Confirm your password</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Sprache">language</label><select class="pinion-install-select" type="text" name="language">
                                <option value="aa">Afar</option>
                                <option value="ab">Abchasisch</option>
                                <option value="af">Afrikaans</option>
                                <option value="am">Amharisch</option>
                                <option value="ar">Arabisch</option>
                                <option value="as">Assamesisch</option>
                                <option value="ay">Aymara</option>
                                <option value="az">Aserbaidschanisch</option>
                                <option value="ba">Baschkirisch</option>
                                <option value="be">Belorussisch</option>
                                <option value="bg">Bulgarisch</option>
                                <option value="bh">Biharisch</option>
                                <option value="bi">Bislamisch</option>
                                <option value="bn">Bengalisch</option>
                                <option value="bo">Tibetanisch</option>
                                <option value="br">Bretonisch</option>
                                <option value="ca">Katalanisch</option>
                                <option value="co">Korsisch</option>
                                <option value="cs">Tschechisch</option>
                                <option value="cy">Walisisch</option>
                                <option value="da">Dänisch</option>
                                <option value="de">Deutsch</option>
                                <option value="dz">Dzongkha, Bhutani</option>
                                <option value="el">Griechisch</option>
                                <option value="en" selected="selected">Englisch</option>
                                <option value="eo">Esperanto</option>
                                <option value="es">Spanisch</option>
                                <option value="et">Estnisch</option>
                                <option value="eu">Baskisch</option>
                                <option value="fa">Persisch</option>
                                <option value="fi">Finnisch</option>
                                <option value="fj">Fiji</option>
                                <option value="fo">Färöisch</option>
                                <option value="fr">Französisch</option>
                                <option value="fy">Friesisch</option>
                                <option value="ga">Irisch</option>
                                <option value="gd">Schottisches Gälisch</option>
                                <option value="gl">Galizisch</option>
                                <option value="gn">Guarani</option>
                                <option value="gu">Gujaratisch</option>
                                <option value="ha">Haussa</option>
                                <option value="he">Hebräisch</option>
                                <option value="hi">Hindi</option>
                                <option value="hr">Kroatisch</option>
                                <option value="hu">Ungarisch</option>
                                <option value="hy">Armenisch</option>
                                <option value="ia">Interlingua</option>
                                <option value="id">Indonesisch</option>
                                <option value="ie">Interlingue</option>
                                <option value="ik">Inupiak</option>
                                <option value="is">Isländisch</option>
                                <option value="it">Italienisch</option>
                                <option value="iu">Inuktitut (Eskimo)</option>
                                <option value="ja">Japanisch</option>
                                <option value="jv">Javanisch</option>
                                <option value="ka">Georgisch</option>
                                <option value="kk">Kasachisch</option>
                                <option value="kl">Kalaallisut (Grönländisch)</option>
                                <option value="km">Kambodschanisch</option>
                                <option value="kn">Kannada</option>
                                <option value="ko">Koreanisch</option>
                                <option value="ks">Kaschmirisch</option>
                                <option value="ku">Kurdisch</option>
                                <option value="ky">Kirgisisch</option>
                                <option value="la">Lateinisch</option>
                                <option value="ln">Lingala</option>
                                <option value="lo">Laotisch</option>
                                <option value="lt">Litauisch</option>
                                <option value="lv">Lettisch</option>
                                <option value="mg">Malagasisch</option>
                                <option value="mi">Maorisch</option>
                                <option value="mk">Mazedonisch</option>
                                <option value="ml">Malajalam</option>
                                <option value="mn">Mongolisch</option>
                                <option value="mo">Moldavisch</option>
                                <option value="mr">Marathi</option>
                                <option value="ms">Malaysisch</option>
                                <option value="mt">Maltesisch</option>
                                <option value="na">Nauruisch</option>
                                <option value="ne">Nepalesisch</option>
                                <option value="nl">Holländisch</option>
                                <option value="no">Norwegisch</option>
                                <option value="oc">Okzitanisch</option>
                                <option value="om">Oromo</option>
                                <option value="or">Oriya</option>
                                <option value="pa">Pundjabisch</option>
                                <option value="pl">Polnisch</option>
                                <option value="ps">Paschtu</option>
                                <option value="pt">Portugiesisch</option>
                                <option value="qu">Quechua</option>
                                <option value="rm">Rätoromanisch</option>
                                <option value="rn">Kirundisch</option>
                                <option value="ro">Rumänisch</option>
                                <option value="ru">Russisch</option>
                                <option value="rw">Kijarwanda</option>
                                <option value="sa">Sanskrit</option>
                                <option value="sd">Zinti</option>
                                <option value="sg">Sango</option>
                                <option value="si">Singhalesisch</option>
                                <option value="sk">Slowakisch</option>
                                <option value="sl">Slowenisch</option>
                                <option value="sm">Samoanisch</option>
                                <option value="sn">Schonisch</option>
                                <option value="so">Somalisch</option>
                                <option value="sq">Albanisch</option>
                                <option value="sr">Serbisch</option>
                                <option value="ss">Swasiländisch</option>
                                <option value="st">Sesothisch</option>
                                <option value="su">Sudanesisch</option>
                                <option value="sv">Schwedisch</option>
                                <option value="sw">Suaheli</option>
                                <option value="ta">Tamilisch</option>
                                <option value="te">Tegulu</option>
                                <option value="tg">Tadschikisch</option>
                                <option value="th">Thai</option>
                                <option value="ti">Tigrinja</option>
                                <option value="tk">Turkmenisch</option>
                                <option value="tl">Tagalog</option>
                                <option value="tn">Sezuan</option>
                                <option value="to">Tongaisch</option>
                                <option value="tr">Türkisch</option>
                                <option value="ts">Tsongaisch</option>
                                <option value="tt">Tatarisch</option>
                                <option value="tw">Twi</option>
                                <option value="ug">Uigur</option>
                                <option value="uk">Ukrainisch</option>
                                <option value="ur">Urdu</option>
                                <option value="uz">Usbekisch</option>
                                <option value="vi">Vietnamesisch</option>
                                <option value="vo">Volapük</option>
                                <option value="wo">Wolof</option>
                                <option value="xh">Xhosa</option>
                                <option value="yi">Jiddish</option>
                                <option value="yo">Joruba</option>
                                <option value="za">Zhuang</option>
                                <option value="zh">Chinesisch</option>
                                <option value="zu">Zul</option>
                            </select>
                            <span class="description translated" data-lang-de="Wähle deine Sprache aus">Select your language</span>
                        </div>
                        <div class="pinion-backend-input-Button">
                            <input type="submit" value="next step"></input>
                        </div>
                    </form>         
                </div>
                <div id="pinion-install-page-site" style="display: none">
                    <h2 class="translated" data-lang-de="2. Informationen über die Seite">2. Site information</h2>
                    <form id="siteForm" method="post">
                        <div>
                            <label class="translated" data-lang-de="Name der Seite">sitename</label><input class="pinion-install-input" type="text" name="sitename" value=""></input>
                            <span class="description translated" data-lang-de="Gib den Namen der Seite ein">Type in the name of your site</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Zeitzone">timezone</label><select class="pinion-install-select" type="text" name="timezone">
                                <option label="Africa" disabled="disabled">Africa</option>
                                    <option>Africa/Abidjan</option>
                                    <option>Africa/Accra</option>
                                    <option>Africa/Addis_Ababa</option>
                                    <option>Africa/Algiers</option>
                                    <option>Africa/Asmara</option>
                                    <option>Africa/Asmera</option>
                                    <option>Africa/Bamako</option>
                                    <option>Africa/Bangui</option>
                                    <option>Africa/Banjul</option>
                                    <option>Africa/Bissau</option>
                                    <option>Africa/Blantyre</option>
                                    <option>Africa/Brazzaville</option>
                                    <option>Africa/Bujumbura</option>
                                    <option>Africa/Cairo</option>
                                    <option>Africa/Casablanca</option>
                                    <option>Africa/Ceuta</option>
                                    <option>Africa/Conakry</option>
                                    <option>Africa/Dakar</option>
                                    <option>Africa/Dar_es_Salaam</option>
                                    <option>Africa/Djibouti</option>
                                    <option>Africa/Douala</option>
                                    <option>Africa/El_Aaiun</option>
                                    <option>Africa/Freetown</option>
                                    <option>Africa/Gaborone</option>
                                    <option>Africa/Harare</option>
                                    <option>Africa/Johannesburg</option>
                                    <option>Africa/Juba</option>
                                    <option>Africa/Kampala</option>
                                    <option>Africa/Khartoum</option>
                                    <option>Africa/Kigali</option>
                                    <option>Africa/Kinshasa</option>
                                    <option>Africa/Lagos</option>
                                    <option>Africa/Libreville</option>
                                    <option>Africa/Lome</option>
                                    <option>Africa/Luanda</option>
                                    <option>Africa/Lubumbashi</option>
                                    <option>Africa/Lusaka</option>
                                    <option>Africa/Malabo</option>
                                    <option>Africa/Maputo</option>
                                    <option>Africa/Maseru</option>
                                    <option>Africa/Mbabane</option>
                                    <option>Africa/Mogadishu</option>
                                    <option>Africa/Monrovia</option>
                                    <option>Africa/Nairobi</option>
                                    <option>Africa/Ndjamena</option>
                                    <option>Africa/Niamey</option>
                                    <option>Africa/Nouakchott</option>
                                    <option>Africa/Ouagadougou</option>
                                    <option>Africa/Porto-Novo</option>
                                    <option>Africa/Sao_Tome</option>
                                    <option>Africa/Timbuktu</option>
                                    <option>Africa/Tripoli</option>
                                    <option>Africa/Tunis</option>
                                    <option>Africa/Windhoek</option>
                                    
                                    <option>America/Adak</option>
                                    <option>America/Anchorage</option>
                                    <option>America/Anguilla</option>
                                    <option>America/Antigua</option>
                                    <option>America/Araguaina</option>
                                    <option>America/Argentina/Buenos_Aires</option>
                                    <option>America/Argentina/Catamarca</option>
                                    <option>America/Argentina/ComodRivadavia</option>
                                    <option>America/Argentina/Cordoba</option>
                                    <option>America/Argentina/Jujuy</option>
                                    <option>America/Argentina/La_Rioja</option>
                                    <option>America/Argentina/Mendoza</option>
                                    <option>America/Argentina/Rio_Gallegos</option>
                                    <option>America/Argentina/Salta</option>
                                    <option>America/Argentina/San_Juan</option>
                                    <option>America/Argentina/San_Luis</option>
                                    <option>America/Argentina/Tucuman</option>
                                    <option>America/Argentina/Ushuaia</option>
                                    <option>America/Aruba</option>
                                    <option>America/Asuncion</option>
                                    <option>America/Atikokan</option>
                                    <option>America/Atka</option>
                                    <option>America/Bahia</option>
                                    <option>America/Bahia_Banderas</option>
                                    <option>America/Barbados</option>
                                    <option>America/Belem</option>
                                    <option>America/Belize</option>
                                    <option>America/Blanc-Sablon</option>
                                    <option>America/Boa_Vista</option>
                                    <option>America/Bogota</option>
                                    <option>America/Boise</option>
                                    <option>America/Buenos_Aires</option>
                                    <option>America/Cambridge_Bay</option>
                                    <option>America/Campo_Grande</option>
                                    <option>America/Cancun</option>
                                    <option>America/Caracas</option>
                                    <option>America/Catamarca</option>
                                    <option>America/Cayenne</option>
                                    <option>America/Cayman</option>
                                    <option>America/Chicago</option>
                                    <option>America/Chihuahua</option>
                                    <option>America/Coral_Harbour</option>
                                    <option>America/Cordoba</option>
                                    <option>America/Costa_Rica</option>
                                    <option>America/Creston</option>
                                    <option>America/Cuiaba</option>
                                    <option>America/Curacao</option>
                                    <option>America/Danmarkshavn</option>
                                    <option>America/Dawson</option>
                                    <option>America/Dawson_Creek</option>
                                    <option>America/Denver</option>
                                    <option>America/Detroit</option>
                                    <option>America/Dominica</option>
                                    <option>America/Edmonton</option>
                                    <option>America/Eirunepe</option>
                                    <option>America/El_Salvador</option>
                                    <option>America/Ensenada</option>
                                    <option>America/Fort_Wayne</option>
                                    <option>America/Fortaleza</option>
                                    <option>America/Glace_Bay</option>
                                    <option>America/Godthab</option>
                                    <option>America/Goose_Bay</option>
                                    <option>America/Grand_Turk</option>
                                    <option>America/Grenada</option>
                                    <option>America/Guadeloupe</option>
                                    <option>America/Guatemala</option>
                                    <option>America/Guayaquil</option>
                                    <option>America/Guyana</option>
                                    <option>America/Halifax</option>
                                    <option>America/Havana</option>
                                    <option>America/Hermosillo</option>
                                    <option>America/Indiana/Indianapolis</option>
                                    <option>America/Indiana/Knox</option>
                                    <option>America/Indiana/Marengo</option>
                                    <option>America/Indiana/Petersburg</option>
                                    <option>America/Indiana/Tell_City</option>
                                    <option>America/Indiana/Vevay</option>
                                    <option>America/Indiana/Vincennes</option>
                                    <option>America/Indiana/Winamac</option>
                                    <option>America/Indianapolis</option>
                                    <option>America/Inuvik</option>
                                    <option>America/Iqaluit</option>
                                    <option>America/Jamaica</option>
                                    <option>America/Jujuy</option>
                                    <option>America/Juneau</option>
                                    <option>America/Kentucky/Louisville</option>
                                    <option>America/Kentucky/Monticello</option>
                                    <option>America/Knox_IN</option>
                                    <option>America/Kralendijk</option>
                                    <option>America/La_Paz</option>
                                    <option>America/Lima</option>
                                    <option>America/Los_Angeles</option>
                                    <option>America/Louisville</option>
                                    <option>America/Lower_Princes</option>
                                    <option>America/Maceio</option>
                                    <option>America/Managua</option>
                                    <option>America/Manaus</option>
                                    <option>America/Marigot</option>
                                    <option>America/Martinique</option>
                                    <option>America/Matamoros</option>
                                    <option>America/Mazatlan</option>
                                    <option>America/Mendoza</option>
                                    <option>America/Menominee</option>
                                    <option>America/Merida</option>
                                    <option>America/Metlakatla</option>
                                    <option>America/Mexico_City</option>
                                    <option>America/Miquelon</option>
                                    <option>America/Moncton</option>
                                    <option>America/Monterrey</option>
                                    <option>America/Montevideo</option>
                                    <option>America/Montreal</option>
                                    <option>America/Montserrat</option>
                                    <option>America/Nassau</option>
                                    <option>America/New_York</option>
                                    <option>America/Nipigon</option>
                                    <option>America/Nome</option>
                                    <option>America/Noronha</option>
                                    <option>America/North_Dakota/Beulah</option>
                                    <option>America/North_Dakota/Center</option>
                                    <option>America/North_Dakota/New_Salem</option>
                                    <option>America/Ojinaga</option>
                                    <option>America/Panama</option>
                                    <option>America/Pangnirtung</option>
                                    <option>America/Paramaribo</option>
                                    <option>America/Phoenix</option>
                                    <option>America/Port-au-Prince</option>
                                    <option>America/Port_of_Spain</option>
                                    <option>America/Porto_Acre</option>
                                    <option>America/Porto_Velho</option>
                                    <option>America/Puerto_Rico</option>
                                    <option>America/Rainy_River</option>
                                    <option>America/Rankin_Inlet</option>
                                    <option>America/Recife</option>
                                    <option>America/Regina</option>
                                    <option>America/Resolute</option>
                                    <option>America/Rio_Branco</option>
                                    <option>America/Rosario</option>
                                    <option>America/Santa_Isabel</option>
                                    <option>America/Santarem</option>
                                    <option>America/Santiago</option>
                                    <option>America/Santo_Domingo</option>
                                    <option>America/Sao_Paulo</option>
                                    <option>America/Scoresbysund</option>
                                    <option>America/Shiprock</option>
                                    <option>America/Sitka</option>
                                    <option>America/St_Barthelemy</option>
                                    <option>America/St_Johns</option>
                                    <option>America/St_Kitts</option>
                                    <option>America/St_Lucia</option>
                                    <option>America/St_Thomas</option>
                                    <option>America/St_Vincent</option>
                                    <option>America/Swift_Current</option>
                                    <option>America/Tegucigalpa</option>
                                    <option>America/Thule</option>
                                    <option>America/Thunder_Bay</option>
                                    <option>America/Tijuana</option>
                                    <option>America/Toronto</option>
                                    <option>America/Tortola</option>
                                    <option>America/Vancouver</option>
                                    <option>America/Virgin</option>
                                    <option>America/Whitehorse</option>
                                    <option>America/Winnipeg</option>
                                    <option>America/Yakutat</option>
                                    <option>America/Yellowknife</option>
                                    
                                    <option>Antarctica/Casey</option>
                                    <option>Antarctica/Davis</option>
                                    <option>Antarctica/DumontDUrville</option>
                                    <option>Antarctica/Macquarie</option>
                                    <option>Antarctica/Mawson</option>
                                    <option>Antarctica/McMurdo</option>
                                    <option>Antarctica/Palmer</option>
                                    <option>Antarctica/Rothera</option>
                                    <option>Antarctica/South_Pole</option>
                                    <option>Antarctica/Syowa</option>
                                    <option>Antarctica/Vostok </option>
                                    
                                    <option>Arctic/Longyearbyen</option>
                                    
                                    <option>Asia/Aden</option>
                                    <option>Asia/Almaty</option>
                                    <option>Asia/Amman</option>
                                    <option>Asia/Anadyr</option>
                                    <option>Asia/Aqtau</option>
                                    <option>Asia/Aqtobe</option>
                                    <option>Asia/Ashgabat</option>
                                    <option>Asia/Ashkhabad</option>
                                    <option>Asia/Baghdad</option>
                                    <option>Asia/Bahrain</option>
                                    <option>Asia/Baku</option>
                                    <option>Asia/Bangkok</option>
                                    <option>Asia/Beirut</option>
                                    <option>Asia/Bishkek</option>
                                    <option>Asia/Brunei</option>
                                    <option>Asia/Calcutta</option>
                                    <option>Asia/Choibalsan</option>
                                    <option>Asia/Chongqing</option>
                                    <option>Asia/Chungking</option>
                                    <option>Asia/Colombo</option>
                                    <option>Asia/Dacca</option>
                                    <option>Asia/Damascus</option>
                                    <option>Asia/Dhaka</option>
                                    <option>Asia/Dili</option>
                                    <option>Asia/Dubai</option>
                                    <option>Asia/Dushanbe</option>
                                    <option>Asia/Gaza</option>
                                    <option>Asia/Harbin</option>
                                    <option>Asia/Hebron</option>
                                    <option>Asia/Ho_Chi_Minh</option>
                                    <option>Asia/Hong_Kong</option>
                                    <option>Asia/Hovd</option>
                                    <option>Asia/Irkutsk</option>
                                    <option>Asia/Istanbul</option>
                                    <option>Asia/Jakarta</option>
                                    <option>Asia/Jayapura</option>
                                    <option>Asia/Jerusalem</option>
                                    <option>Asia/Kabul</option>
                                    <option>Asia/Kamchatka</option>
                                    <option>Asia/Karachi</option>
                                    <option>Asia/Kashgar</option>
                                    <option>Asia/Kathmandu</option>
                                    <option>Asia/Katmandu</option>
                                    <option>Asia/Kolkata</option>
                                    <option>Asia/Krasnoyarsk</option>
                                    <option>Asia/Kuala_Lumpur</option>
                                    <option>Asia/Kuching</option>
                                    <option>Asia/Kuwait</option>
                                    <option>Asia/Macao</option>
                                    <option>Asia/Macau</option>
                                    <option>Asia/Magadan</option>
                                    <option>Asia/Makassar</option>
                                    <option>Asia/Manila</option>
                                    <option>Asia/Muscat</option>
                                    <option>Asia/Nicosia</option>
                                    <option>Asia/Novokuznetsk</option>
                                    <option>Asia/Novosibirsk</option>
                                    <option>Asia/Omsk</option>
                                    <option>Asia/Oral</option>
                                    <option>Asia/Phnom_Penh</option>
                                    <option>Asia/Pontianak</option>
                                    <option>Asia/Pyongyang</option>
                                    <option>Asia/Qatar</option>
                                    <option>Asia/Qyzylorda</option>
                                    <option>Asia/Rangoon</option>
                                    <option>Asia/Riyadh</option>
                                    <option>Asia/Saigon</option>
                                    <option>Asia/Sakhalin</option>
                                    <option>Asia/Samarkand</option>
                                    <option>Asia/Seoul</option>
                                    <option>Asia/Shanghai</option>
                                    <option>Asia/Singapore</option>
                                    <option>Asia/Taipei</option>
                                    <option>Asia/Tashkent</option>
                                    <option>Asia/Tbilisi</option>
                                    <option>Asia/Tehran</option>
                                    <option>Asia/Tel_Aviv</option>
                                    <option>Asia/Thimbu</option>
                                    <option>Asia/Thimphu</option>
                                    <option>Asia/Tokyo</option>
                                    <option>Asia/Ujung_Pandang</option>
                                    <option>Asia/Ulaanbaatar</option>
                                    <option>Asia/Ulan_Bator</option>
                                    <option>Asia/Urumqi</option>
                                    <option>Asia/Vientiane</option>
                                    <option>Asia/Vladivostok</option>
                                    <option>Asia/Yakutsk</option>
                                    <option>Asia/Yekaterinburg</option>
                                    <option>Asia/Yerevan</option>
                                    
                                    <option>Atlantic/Azores</option>
                                    <option>Atlantic/Bermuda</option>
                                    <option>Atlantic/Canary</option>
                                    <option>Atlantic/Cape_Verde</option>
                                    <option>Atlantic/Faeroe</option>
                                    <option>Atlantic/Faroe</option>
                                    <option>Atlantic/Jan_Mayen</option>
                                    <option>Atlantic/Madeira</option>
                                    <option>Atlantic/Reykjavik</option>
                                    <option>Atlantic/South_Georgia</option>
                                    <option>Atlantic/St_Helena</option>
                                    <option>Atlantic/Stanley</option>
                                    
                                    <option>Australia/ACT</option>
                                    <option>Australia/Adelaide</option>
                                    <option>Australia/Brisbane</option>
                                    <option>Australia/Broken_Hill</option>
                                    <option>Australia/Canberra</option>
                                    <option>Australia/Currie</option>
                                    <option>Australia/Darwin</option>
                                    <option>Australia/Eucla</option>
                                    <option>Australia/Hobart</option>
                                    <option>Australia/LHI</option>
                                    <option>Australia/Lindeman</option>
                                    <option>Australia/Lord_Howe</option>
                                    <option>Australia/Melbourne</option>
                                    <option>Australia/North</option>
                                    <option>Australia/NSW</option>
                                    <option>Australia/Perth</option>
                                    <option>Australia/Queensland</option>
                                    <option>Australia/South</option>
                                    <option>Australia/Sydney</option>
                                    <option>Australia/Tasmania</option>
                                    <option>Australia/Victoria</option>
                                    <option>Australia/West</option>
                                    <option>Australia/Yancowinna</option>
                                    
                                    <option>Europe/Amsterdam</option>
                                    <option>Europe/Andorra</option>
                                    <option>Europe/Athens</option>
                                    <option>Europe/Belfast</option>
                                    <option>Europe/Belgrade</option>
                                    <option selected="selected">Europe/Berlin</option>
                                    <option>Europe/Bratislava</option>
                                    <option>Europe/Brussels</option>
                                    <option>Europe/Bucharest</option>
                                    <option>Europe/Budapest</option>
                                    <option>Europe/Chisinau</option>
                                    <option>Europe/Copenhagen</option>
                                    <option>Europe/Dublin</option>
                                    <option>Europe/Gibraltar</option>
                                    <option>Europe/Guernsey</option>
                                    <option>Europe/Helsinki</option>
                                    <option>Europe/Isle_of_Man</option>
                                    <option>Europe/Istanbul</option>
                                    <option>Europe/Jersey</option>
                                    <option>Europe/Kaliningrad</option>
                                    <option>Europe/Kiev</option>
                                    <option>Europe/Lisbon</option>
                                    <option>Europe/Ljubljana</option>
                                    <option>Europe/London</option>
                                    <option>Europe/Luxembourg</option>
                                    <option>Europe/Madrid</option>
                                    <option>Europe/Malta</option>
                                    <option>Europe/Mariehamn</option>
                                    <option>Europe/Minsk</option>
                                    <option>Europe/Monaco</option>
                                    <option>Europe/Moscow</option>
                                    <option>Europe/Nicosia</option>
                                    <option>Europe/Oslo</option>
                                    <option>Europe/Paris</option>
                                    <option>Europe/Podgorica</option>
                                    <option>Europe/Prague</option>
                                    <option>Europe/Riga</option>
                                    <option>Europe/Rome</option>
                                    <option>Europe/Samara</option>
                                    <option>Europe/San_Marino</option>
                                    <option>Europe/Sarajevo</option>
                                    <option>Europe/Simferopol</option>
                                    <option>Europe/Skopje</option>
                                    <option>Europe/Sofia</option>
                                    <option>Europe/Stockholm</option>
                                    <option>Europe/Tallinn</option>
                                    <option>Europe/Tirane</option>
                                    <option>Europe/Tiraspol</option>
                                    <option>Europe/Uzhgorod</option>
                                    <option>Europe/Vaduz</option>
                                    <option>Europe/Vatican</option>
                                    <option>Europe/Vienna</option>
                                    <option>Europe/Vilnius</option>
                                    <option>Europe/Volgograd</option>
                                    <option>Europe/Warsaw</option>
                                    <option>Europe/Zagreb</option>
                                    <option>Europe/Zaporozhye</option>
                                    <option>Europe/Zurich</option>
                                    
                                    <option>Indian/Antananarivo</option>
                                    <option>Indian/Chagos</option>
                                    <option>Indian/Christmas</option>
                                    <option>Indian/Cocos</option>
                                    <option>Indian/Comoro</option>
                                    <option>Indian/Kerguelen</option>
                                    <option>Indian/Mahe</option>
                                    <option>Indian/Maldives</option>
                                    <option>Indian/Mauritius</option>
                                    <option>Indian/Mayotte</option>
                                    <option>Indian/Reunion</option>
                                    
                                    <option>Pacific/Apia</option>
                                    <option>Pacific/Auckland</option>
                                    <option>Pacific/Chatham</option>
                                    <option>Pacific/Chuuk</option>
                                    <option>Pacific/Easter</option>
                                    <option>Pacific/Efate</option>
                                    <option>Pacific/Enderbury</option>
                                    <option>Pacific/Fakaofo</option>
                                    <option>Pacific/Fiji</option>
                                    <option>Pacific/Funafuti</option>
                                    <option>Pacific/Galapagos</option>
                                    <option>Pacific/Gambier</option>
                                    <option>Pacific/Guadalcanal</option>
                                    <option>Pacific/Guam</option>
                                    <option>Pacific/Honolulu</option>
                                    <option>Pacific/Johnston</option>
                                    <option>Pacific/Kiritimati</option>
                                    <option>Pacific/Kosrae</option>
                                    <option>Pacific/Kwajalein</option>
                                    <option>Pacific/Majuro</option>
                                    <option>Pacific/Marquesas</option>
                                    <option>Pacific/Midway</option>
                                    <option>Pacific/Nauru</option>
                                    <option>Pacific/Niue</option>
                                    <option>Pacific/Norfolk</option>
                                    <option>Pacific/Noumea</option>
                                    <option>Pacific/Pago_Pago</option>
                                    <option>Pacific/Palau</option>
                                    <option>Pacific/Pitcairn</option>
                                    <option>Pacific/Pohnpei</option>
                                    <option>Pacific/Ponape</option>
                                    <option>Pacific/Port_Moresby</option>
                                    <option>Pacific/Rarotonga</option>
                                    <option>Pacific/Saipan</option>
                                    <option>Pacific/Samoa</option>
                                    <option>Pacific/Tahiti</option>
                                    <option>Pacific/Tarawa</option>
                                    <option>Pacific/Tongatapu</option>
                                    <option>Pacific/Truk</option>
                                    <option>Pacific/Wake</option>
                                    <option>Pacific/Wallis</option>
                                    <option>Pacific/Yap</option>
                            </select>
                            <span class="description translated" data-lang-de="Wähle die Zeitzone deiner Seite aus">Select the timezone of your site</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Sprache">language</label><select class="pinion-install-select" type="text" name="language">
                                <option value="aa">Afar</option>
                                <option value="ab">Abchasisch</option>
                                <option value="af">Afrikaans</option>
                                <option value="am">Amharisch</option>
                                <option value="ar">Arabisch</option>
                                <option value="as">Assamesisch</option>
                                <option value="ay">Aymara</option>
                                <option value="az">Aserbaidschanisch</option>
                                <option value="ba">Baschkirisch</option>
                                <option value="be">Belorussisch</option>
                                <option value="bg">Bulgarisch</option>
                                <option value="bh">Biharisch</option>
                                <option value="bi">Bislamisch</option>
                                <option value="bn">Bengalisch</option>
                                <option value="bo">Tibetanisch</option>
                                <option value="br">Bretonisch</option>
                                <option value="ca">Katalanisch</option>
                                <option value="co">Korsisch</option>
                                <option value="cs">Tschechisch</option>
                                <option value="cy">Walisisch</option>
                                <option value="da">Dänisch</option>
                                <option value="de">Deutsch</option>
                                <option value="dz">Dzongkha, Bhutani</option>
                                <option value="el">Griechisch</option>
                                <option value="en" selected="selected">Englisch</option>
                                <option value="eo">Esperanto</option>
                                <option value="es">Spanisch</option>
                                <option value="et">Estnisch</option>
                                <option value="eu">Baskisch</option>
                                <option value="fa">Persisch</option>
                                <option value="fi">Finnisch</option>
                                <option value="fj">Fiji</option>
                                <option value="fo">Färöisch</option>
                                <option value="fr">Französisch</option>
                                <option value="fy">Friesisch</option>
                                <option value="ga">Irisch</option>
                                <option value="gd">Schottisches Gälisch</option>
                                <option value="gl">Galizisch</option>
                                <option value="gn">Guarani</option>
                                <option value="gu">Gujaratisch</option>
                                <option value="ha">Haussa</option>
                                <option value="he">Hebräisch</option>
                                <option value="hi">Hindi</option>
                                <option value="hr">Kroatisch</option>
                                <option value="hu">Ungarisch</option>
                                <option value="hy">Armenisch</option>
                                <option value="ia">Interlingua</option>
                                <option value="id">Indonesisch</option>
                                <option value="ie">Interlingue</option>
                                <option value="ik">Inupiak</option>
                                <option value="is">Isländisch</option>
                                <option value="it">Italienisch</option>
                                <option value="iu">Inuktitut (Eskimo)</option>
                                <option value="ja">Japanisch</option>
                                <option value="jv">Javanisch</option>
                                <option value="ka">Georgisch</option>
                                <option value="kk">Kasachisch</option>
                                <option value="kl">Kalaallisut (Grönländisch)</option>
                                <option value="km">Kambodschanisch</option>
                                <option value="kn">Kannada</option>
                                <option value="ko">Koreanisch</option>
                                <option value="ks">Kaschmirisch</option>
                                <option value="ku">Kurdisch</option>
                                <option value="ky">Kirgisisch</option>
                                <option value="la">Lateinisch</option>
                                <option value="ln">Lingala</option>
                                <option value="lo">Laotisch</option>
                                <option value="lt">Litauisch</option>
                                <option value="lv">Lettisch</option>
                                <option value="mg">Malagasisch</option>
                                <option value="mi">Maorisch</option>
                                <option value="mk">Mazedonisch</option>
                                <option value="ml">Malajalam</option>
                                <option value="mn">Mongolisch</option>
                                <option value="mo">Moldavisch</option>
                                <option value="mr">Marathi</option>
                                <option value="ms">Malaysisch</option>
                                <option value="mt">Maltesisch</option>
                                <option value="na">Nauruisch</option>
                                <option value="ne">Nepalesisch</option>
                                <option value="nl">Holländisch</option>
                                <option value="no">Norwegisch</option>
                                <option value="oc">Okzitanisch</option>
                                <option value="om">Oromo</option>
                                <option value="or">Oriya</option>
                                <option value="pa">Pundjabisch</option>
                                <option value="pl">Polnisch</option>
                                <option value="ps">Paschtu</option>
                                <option value="pt">Portugiesisch</option>
                                <option value="qu">Quechua</option>
                                <option value="rm">Rätoromanisch</option>
                                <option value="rn">Kirundisch</option>
                                <option value="ro">Rumänisch</option>
                                <option value="ru">Russisch</option>
                                <option value="rw">Kijarwanda</option>
                                <option value="sa">Sanskrit</option>
                                <option value="sd">Zinti</option>
                                <option value="sg">Sango</option>
                                <option value="si">Singhalesisch</option>
                                <option value="sk">Slowakisch</option>
                                <option value="sl">Slowenisch</option>
                                <option value="sm">Samoanisch</option>
                                <option value="sn">Schonisch</option>
                                <option value="so">Somalisch</option>
                                <option value="sq">Albanisch</option>
                                <option value="sr">Serbisch</option>
                                <option value="ss">Swasiländisch</option>
                                <option value="st">Sesothisch</option>
                                <option value="su">Sudanesisch</option>
                                <option value="sv">Schwedisch</option>
                                <option value="sw">Suaheli</option>
                                <option value="ta">Tamilisch</option>
                                <option value="te">Tegulu</option>
                                <option value="tg">Tadschikisch</option>
                                <option value="th">Thai</option>
                                <option value="ti">Tigrinja</option>
                                <option value="tk">Turkmenisch</option>
                                <option value="tl">Tagalog</option>
                                <option value="tn">Sezuan</option>
                                <option value="to">Tongaisch</option>
                                <option value="tr">Türkisch</option>
                                <option value="ts">Tsongaisch</option>
                                <option value="tt">Tatarisch</option>
                                <option value="tw">Twi</option>
                                <option value="ug">Uigur</option>
                                <option value="uk">Ukrainisch</option>
                                <option value="ur">Urdu</option>
                                <option value="uz">Usbekisch</option>
                                <option value="vi">Vietnamesisch</option>
                                <option value="vo">Volapük</option>
                                <option value="wo">Wolof</option>
                                <option value="xh">Xhosa</option>
                                <option value="yi">Jiddish</option>
                                <option value="yo">Joruba</option>
                                <option value="za">Zhuang</option>
                                <option value="zh">Chinesisch</option>
                                <option value="zu">Zul</option>
                            </select>
                            <span class="description translated" data-lang-de="Wähle die Sprache deiner Seite aus">Select the language of the site</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Übersetzungen">translations</label><select class="pinion-install-multipleSelect" type="text" name="languages" multiple="multiple">
                                <option value="aa">Afar</option>
                                <option value="ab">Abchasisch</option>
                                <option value="af">Afrikaans</option>
                                <option value="am">Amharisch</option>
                                <option value="ar">Arabisch</option>
                                <option value="as">Assamesisch</option>
                                <option value="ay">Aymara</option>
                                <option value="az">Aserbaidschanisch</option>
                                <option value="ba">Baschkirisch</option>
                                <option value="be">Belorussisch</option>
                                <option value="bg">Bulgarisch</option>
                                <option value="bh">Biharisch</option>
                                <option value="bi">Bislamisch</option>
                                <option value="bn">Bengalisch</option>
                                <option value="bo">Tibetanisch</option>
                                <option value="br">Bretonisch</option>
                                <option value="ca">Katalanisch</option>
                                <option value="co">Korsisch</option>
                                <option value="cs">Tschechisch</option>
                                <option value="cy">Walisisch</option>
                                <option value="da">Dänisch</option>
                                <option value="de">Deutsch</option>
                                <option value="dz">Dzongkha, Bhutani</option>
                                <option value="el">Griechisch</option>
                                <option value="en">Englisch</option>
                                <option value="eo">Esperanto</option>
                                <option value="es">Spanisch</option>
                                <option value="et">Estnisch</option>
                                <option value="eu">Baskisch</option>
                                <option value="fa">Persisch</option>
                                <option value="fi">Finnisch</option>
                                <option value="fj">Fiji</option>
                                <option value="fo">Färöisch</option>
                                <option value="fr">Französisch</option>
                                <option value="fy">Friesisch</option>
                                <option value="ga">Irisch</option>
                                <option value="gd">Schottisches Gälisch</option>
                                <option value="gl">Galizisch</option>
                                <option value="gn">Guarani</option>
                                <option value="gu">Gujaratisch</option>
                                <option value="ha">Haussa</option>
                                <option value="he">Hebräisch</option>
                                <option value="hi">Hindi</option>
                                <option value="hr">Kroatisch</option>
                                <option value="hu">Ungarisch</option>
                                <option value="hy">Armenisch</option>
                                <option value="ia">Interlingua</option>
                                <option value="id">Indonesisch</option>
                                <option value="ie">Interlingue</option>
                                <option value="ik">Inupiak</option>
                                <option value="is">Isländisch</option>
                                <option value="it">Italienisch</option>
                                <option value="iu">Inuktitut (Eskimo)</option>
                                <option value="ja">Japanisch</option>
                                <option value="jv">Javanisch</option>
                                <option value="ka">Georgisch</option>
                                <option value="kk">Kasachisch</option>
                                <option value="kl">Kalaallisut (Grönländisch)</option>
                                <option value="km">Kambodschanisch</option>
                                <option value="kn">Kannada</option>
                                <option value="ko">Koreanisch</option>
                                <option value="ks">Kaschmirisch</option>
                                <option value="ku">Kurdisch</option>
                                <option value="ky">Kirgisisch</option>
                                <option value="la">Lateinisch</option>
                                <option value="ln">Lingala</option>
                                <option value="lo">Laotisch</option>
                                <option value="lt">Litauisch</option>
                                <option value="lv">Lettisch</option>
                                <option value="mg">Malagasisch</option>
                                <option value="mi">Maorisch</option>
                                <option value="mk">Mazedonisch</option>
                                <option value="ml">Malajalam</option>
                                <option value="mn">Mongolisch</option>
                                <option value="mo">Moldavisch</option>
                                <option value="mr">Marathi</option>
                                <option value="ms">Malaysisch</option>
                                <option value="mt">Maltesisch</option>
                                <option value="na">Nauruisch</option>
                                <option value="ne">Nepalesisch</option>
                                <option value="nl">Holländisch</option>
                                <option value="no">Norwegisch</option>
                                <option value="oc">Okzitanisch</option>
                                <option value="om">Oromo</option>
                                <option value="or">Oriya</option>
                                <option value="pa">Pundjabisch</option>
                                <option value="pl">Polnisch</option>
                                <option value="ps">Paschtu</option>
                                <option value="pt">Portugiesisch</option>
                                <option value="qu">Quechua</option>
                                <option value="rm">Rätoromanisch</option>
                                <option value="rn">Kirundisch</option>
                                <option value="ro">Rumänisch</option>
                                <option value="ru">Russisch</option>
                                <option value="rw">Kijarwanda</option>
                                <option value="sa">Sanskrit</option>
                                <option value="sd">Zinti</option>
                                <option value="sg">Sango</option>
                                <option value="si">Singhalesisch</option>
                                <option value="sk">Slowakisch</option>
                                <option value="sl">Slowenisch</option>
                                <option value="sm">Samoanisch</option>
                                <option value="sn">Schonisch</option>
                                <option value="so">Somalisch</option>
                                <option value="sq">Albanisch</option>
                                <option value="sr">Serbisch</option>
                                <option value="ss">Swasiländisch</option>
                                <option value="st">Sesothisch</option>
                                <option value="su">Sudanesisch</option>
                                <option value="sv">Schwedisch</option>
                                <option value="sw">Suaheli</option>
                                <option value="ta">Tamilisch</option>
                                <option value="te">Tegulu</option>
                                <option value="tg">Tadschikisch</option>
                                <option value="th">Thai</option>
                                <option value="ti">Tigrinja</option>
                                <option value="tk">Turkmenisch</option>
                                <option value="tl">Tagalog</option>
                                <option value="tn">Sezuan</option>
                                <option value="to">Tongaisch</option>
                                <option value="tr">Türkisch</option>
                                <option value="ts">Tsongaisch</option>
                                <option value="tt">Tatarisch</option>
                                <option value="tw">Twi</option>
                                <option value="ug">Uigur</option>
                                <option value="uk">Ukrainisch</option>
                                <option value="ur">Urdu</option>
                                <option value="uz">Usbekisch</option>
                                <option value="vi">Vietnamesisch</option>
                                <option value="vo">Volapük</option>
                                <option value="wo">Wolof</option>
                                <option value="xh">Xhosa</option>
                                <option value="yi">Jiddish</option>
                                <option value="yo">Joruba</option>
                                <option value="za">Zhuang</option>
                                <option value="zh">Chinesisch</option>
                                <option value="zu">Zul</option>
                            </select>
                            <span class="description translated" data-lang-de="Wähle die Übersetzungen für deine Sprache aus">Select the translations for your site</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Seiten-Hintergrund">site-color</label><input class="pinion-install-input pinion-install-colorpicker" type="text" name="pagecolor" value="#ffffff"></input>
                            <span class="description translated" data-lang-de="Wähle die Farbe für den Hintergrund deiner Seite">Select the color of the background of the site</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Header-Farbe">header-color</label><input class="pinion-install-input pinion-install-colorpicker" type="text" name="headercolor" value="#185859"></input>
                            <span class="description translated" data-lang-de="Wähle die Farbe für den Hintergrund deines Headers">Select the color of the background of the header</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Footer-Farbe">footer-color</label><input class="pinion-install-input pinion-install-colorpicker" type="text" name="footercolor" value="#185859"></input>
                            <span class="description translated" data-lang-de="Wähle die Farbe für den Hintergrund deines Headers">Select the color of the background of the footer</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Header-Textfarbe">header-text-color</label><input class="pinion-install-input pinion-install-colorpicker" type="text" name="headertextcolor" value="#ffffff"></input>
                            <span class="description translated" data-lang-de="Wähle die Farbe für den Text im Headers">Select the color of the text in the header</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Footer-Textfarbe">footer-text-color</label><input class="pinion-install-input pinion-install-colorpicker" type="text" name="footertextcolor" value="#ffffff"></input>
                            <span class="description translated" data-lang-de="Wähle die Farbe für den Text im Footer">Select the color of the text in the footer</span>
                        </div>
                        <div class="pinion-backend-input-Button">
                            <input type="button" value="previous step" class="pinion-previousStep"></input>
                            <input type="submit" value="next step"></input>
                        </div>
                    </form>         
                </div>
                <div id="pinion-install-page-database" style="display: none">
                    <h2 data-lang-de="3. Informationen über die Datenbank">3. Database information</h2>
                    <form id="databaseForm" method="post">
                        <div>
                            <label class="translated" data-lang-de="Datenbank">database</label><input class="pinion-install-input" type="text" name="database" value=""></input>
                            <span class="description translated" data-lang-de="Gib den Namen der Datenbank ein">Type in the name of the database</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Benutzername">username</label><input class="pinion-install-input" type="text" name="username" value=""></input>
                            <span class="description translated" data-lang-de="Gib den Benutzernamen der Datenbank ein">Type in the username for the database</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Passwort">password</label><input class="pinion-install-input" type="password" name="password"></input>
                            <span class="description translated" data-lang-de="Gib das Passwort der Datenbank ein">Type in the password for the database</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Host">host</label><input class="pinion-install-input" type="text" name="host" value="localhost"></input>
                            <span class="description translated" data-lang-de="Gib den Host der Datenbank ein">Type in the host of the database</span>
                        </div>
                        <div>
                            <label class="translated" data-lang-de="Tabellen-Präfix">table prefix</label><input class="pinion-install-input" type="text" name="tableprefix" value=""></input>
                            <span class="description translated" data-lang-de="Wenn du Tabellen wie z.B. 'cms_' willst, kannst du optional einen Tabellen-Präfix eingeben">If you want to have tables like "cms_", you can optionally type in a table prefix</span>
                        </div>
                        <div class="pinion-backend-input-Button">
                            <input type="button" value="previous step" class="pinion-previousStep"></input>
                            <input type="submit" value="install"></input>
                        </div>
                    </form>
                </div>
                <div id="pinion-install-page-install" style="display: none">
                    <h2 class="translated" data-lang-de="4. Installation">4. Installation</h2>
                    <div id="pinion-install-module" class="translated" data-lang-de="Installiere Module">install modules</div>
                    <div id="pinion-install-progressbar">
                        <div class="progress"></div>
                    </div>
                    <ul id="pinion-install-list">
                        <?php
                            foreach($modulePackage as $module) {
                                $li = "<li class='notInstalled'>";
                                if(file_exists(MODULES_PATH."$module/info.ini")) {
                                    $settings = @parse_ini_file(MODULES_PATH."$module/info.ini", true);
                                    if(isset($settings["title"])) {
                                        $title = $settings["title"];
                                    } else {
                                        $title = $module;
                                    }
                                } else {
                                    $title = $module;
                                }
                                if(file_exists(MODULES_PATH."$module/icon.png")) {
                                    $icon = MODULES_URL."/$module/icon.png";
                                } else {
                                    $icon = SITE_URL."pinion/assets/images/icons/defaultModuleIcon.png";
                                }
                                $li .= "<div class='pinion-module-icon'><img src='$icon'></img></div>";
                                $li .= "<div class='pinion-module-name'>$title</div>";
                                $li .= "<div class='pinion-module-status'></div>";
                                $li .= "</li>";
                                
                                print $li;
                            }
                        ?>
                    </ul>
                </div>
                <div id="pinion-install-page-complete" style="display: none">
                    <h2 class="translated" data-lang-de="5. Geh zu deiner Seite">5. Go to your Site</h2>
                    <div id="pinion-install-module" class="translated" data-lang-de="Installation abgeschlossen">installation complete</div>
                    <a href="#" id="backendLink" class="translated" data-lang-de="Geh zum Backend">go to backend</a>
                </div>
                <div id="message"></div>
                <script>
        /*! jQuery v1.7.1 jquery.com | jquery.org/license */
        (function(a,b){function cy(a){return f.isWindow(a)?a:a.nodeType===9?a.defaultView||a.parentWindow:!1}function cv(a){if(!ck[a]){var b=c.body,d=f("<"+a+">").appendTo(b),e=d.css("display");d.remove();if(e==="none"||e===""){cl||(cl=c.createElement("iframe"),cl.frameBorder=cl.width=cl.height=0),b.appendChild(cl);if(!cm||!cl.createElement)cm=(cl.contentWindow||cl.contentDocument).document,cm.write((c.compatMode==="CSS1Compat"?"<!doctype html>":"")+"<html><body>"),cm.close();d=cm.createElement(a),cm.body.appendChild(d),e=f.css(d,"display"),b.removeChild(cl)}ck[a]=e}return ck[a]}function cu(a,b){var c={};f.each(cq.concat.apply([],cq.slice(0,b)),function(){c[this]=a});return c}function ct(){cr=b}function cs(){setTimeout(ct,0);return cr=f.now()}function cj(){try{return new a.ActiveXObject("Microsoft.XMLHTTP")}catch(b){}}function ci(){try{return new a.XMLHttpRequest}catch(b){}}function cc(a,c){a.dataFilter&&(c=a.dataFilter(c,a.dataType));var d=a.dataTypes,e={},g,h,i=d.length,j,k=d[0],l,m,n,o,p;for(g=1;g<i;g++){if(g===1)for(h in a.converters)typeof h=="string"&&(e[h.toLowerCase()]=a.converters[h]);l=k,k=d[g];if(k==="*")k=l;else if(l!=="*"&&l!==k){m=l+" "+k,n=e[m]||e["* "+k];if(!n){p=b;for(o in e){j=o.split(" ");if(j[0]===l||j[0]==="*"){p=e[j[1]+" "+k];if(p){o=e[o],o===!0?n=p:p===!0&&(n=o);break}}}}!n&&!p&&f.error("No conversion from "+m.replace(" "," to ")),n!==!0&&(c=n?n(c):p(o(c)))}}return c}function cb(a,c,d){var e=a.contents,f=a.dataTypes,g=a.responseFields,h,i,j,k;for(i in g)i in d&&(c[g[i]]=d[i]);while(f[0]==="*")f.shift(),h===b&&(h=a.mimeType||c.getResponseHeader("content-type"));if(h)for(i in e)if(e[i]&&e[i].test(h)){f.unshift(i);break}if(f[0]in d)j=f[0];else{for(i in d){if(!f[0]||a.converters[i+" "+f[0]]){j=i;break}k||(k=i)}j=j||k}if(j){j!==f[0]&&f.unshift(j);return d[j]}}function ca(a,b,c,d){if(f.isArray(b))f.each(b,function(b,e){c||bE.test(a)?d(a,e):ca(a+"["+(typeof e=="object"||f.isArray(e)?b:"")+"]",e,c,d)});else if(!c&&b!=null&&typeof b=="object")for(var e in b)ca(a+"["+e+"]",b[e],c,d);else d(a,b)}function b_(a,c){var d,e,g=f.ajaxSettings.flatOptions||{};for(d in c)c[d]!==b&&((g[d]?a:e||(e={}))[d]=c[d]);e&&f.extend(!0,a,e)}function b$(a,c,d,e,f,g){f=f||c.dataTypes[0],g=g||{},g[f]=!0;var h=a[f],i=0,j=h?h.length:0,k=a===bT,l;for(;i<j&&(k||!l);i++)l=h[i](c,d,e),typeof l=="string"&&(!k||g[l]?l=b:(c.dataTypes.unshift(l),l=b$(a,c,d,e,l,g)));(k||!l)&&!g["*"]&&(l=b$(a,c,d,e,"*",g));return l}function bZ(a){return function(b,c){typeof b!="string"&&(c=b,b="*");if(f.isFunction(c)){var d=b.toLowerCase().split(bP),e=0,g=d.length,h,i,j;for(;e<g;e++)h=d[e],j=/^\+/.test(h),j&&(h=h.substr(1)||"*"),i=a[h]=a[h]||[],i[j?"unshift":"push"](c)}}}function bC(a,b,c){var d=b==="width"?a.offsetWidth:a.offsetHeight,e=b==="width"?bx:by,g=0,h=e.length;if(d>0){if(c!=="border")for(;g<h;g++)c||(d-=parseFloat(f.css(a,"padding"+e[g]))||0),c==="margin"?d+=parseFloat(f.css(a,c+e[g]))||0:d-=parseFloat(f.css(a,"border"+e[g]+"Width"))||0;return d+"px"}d=bz(a,b,b);if(d<0||d==null)d=a.style[b]||0;d=parseFloat(d)||0;if(c)for(;g<h;g++)d+=parseFloat(f.css(a,"padding"+e[g]))||0,c!=="padding"&&(d+=parseFloat(f.css(a,"border"+e[g]+"Width"))||0),c==="margin"&&(d+=parseFloat(f.css(a,c+e[g]))||0);return d+"px"}function bp(a,b){b.src?f.ajax({url:b.src,async:!1,dataType:"script"}):f.globalEval((b.text||b.textContent||b.innerHTML||"").replace(bf,"/*$0*/")),b.parentNode&&b.parentNode.removeChild(b)}function bo(a){var b=c.createElement("div");bh.appendChild(b),b.innerHTML=a.outerHTML;return b.firstChild}function bn(a){var b=(a.nodeName||"").toLowerCase();b==="input"?bm(a):b!=="script"&&typeof a.getElementsByTagName!="undefined"&&f.grep(a.getElementsByTagName("input"),bm)}function bm(a){if(a.type==="checkbox"||a.type==="radio")a.defaultChecked=a.checked}function bl(a){return typeof a.getElementsByTagName!="undefined"?a.getElementsByTagName("*"):typeof a.querySelectorAll!="undefined"?a.querySelectorAll("*"):[]}function bk(a,b){var c;if(b.nodeType===1){b.clearAttributes&&b.clearAttributes(),b.mergeAttributes&&b.mergeAttributes(a),c=b.nodeName.toLowerCase();if(c==="object")b.outerHTML=a.outerHTML;else if(c!=="input"||a.type!=="checkbox"&&a.type!=="radio"){if(c==="option")b.selected=a.defaultSelected;else if(c==="input"||c==="textarea")b.defaultValue=a.defaultValue}else a.checked&&(b.defaultChecked=b.checked=a.checked),b.value!==a.value&&(b.value=a.value);b.removeAttribute(f.expando)}}function bj(a,b){if(b.nodeType===1&&!!f.hasData(a)){var c,d,e,g=f._data(a),h=f._data(b,g),i=g.events;if(i){delete h.handle,h.events={};for(c in i)for(d=0,e=i[c].length;d<e;d++)f.event.add(b,c+(i[c][d].namespace?".":"")+i[c][d].namespace,i[c][d],i[c][d].data)}h.data&&(h.data=f.extend({},h.data))}}function bi(a,b){return f.nodeName(a,"table")?a.getElementsByTagName("tbody")[0]||a.appendChild(a.ownerDocument.createElement("tbody")):a}function U(a){var b=V.split("|"),c=a.createDocumentFragment();if(c.createElement)while(b.length)c.createElement(b.pop());return c}function T(a,b,c){b=b||0;if(f.isFunction(b))return f.grep(a,function(a,d){var e=!!b.call(a,d,a);return e===c});if(b.nodeType)return f.grep(a,function(a,d){return a===b===c});if(typeof b=="string"){var d=f.grep(a,function(a){return a.nodeType===1});if(O.test(b))return f.filter(b,d,!c);b=f.filter(b,d)}return f.grep(a,function(a,d){return f.inArray(a,b)>=0===c})}function S(a){return!a||!a.parentNode||a.parentNode.nodeType===11}function K(){return!0}function J(){return!1}function n(a,b,c){var d=b+"defer",e=b+"queue",g=b+"mark",h=f._data(a,d);h&&(c==="queue"||!f._data(a,e))&&(c==="mark"||!f._data(a,g))&&setTimeout(function(){!f._data(a,e)&&!f._data(a,g)&&(f.removeData(a,d,!0),h.fire())},0)}function m(a){for(var b in a){if(b==="data"&&f.isEmptyObject(a[b]))continue;if(b!=="toJSON")return!1}return!0}function l(a,c,d){if(d===b&&a.nodeType===1){var e="data-"+c.replace(k,"-$1").toLowerCase();d=a.getAttribute(e);if(typeof d=="string"){try{d=d==="true"?!0:d==="false"?!1:d==="null"?null:f.isNumeric(d)?parseFloat(d):j.test(d)?f.parseJSON(d):d}catch(g){}f.data(a,c,d)}else d=b}return d}function h(a){var b=g[a]={},c,d;a=a.split(/\s+/);for(c=0,d=a.length;c<d;c++)b[a[c]]=!0;return b}var c=a.document,d=a.navigator,e=a.location,f=function(){function J(){if(!e.isReady){try{c.documentElement.doScroll("left")}catch(a){setTimeout(J,1);return}e.ready()}}var e=function(a,b){return new e.fn.init(a,b,h)},f=a.jQuery,g=a.$,h,i=/^(?:[^#<]*(<[\w\W]+>)[^>]*$|#([\w\-]*)$)/,j=/\S/,k=/^\s+/,l=/\s+$/,m=/^<(\w+)\s*\/?>(?:<\/\1>)?$/,n=/^[\],:{}\s]*$/,o=/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,p=/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,q=/(?:^|:|,)(?:\s*\[)+/g,r=/(webkit)[ \/]([\w.]+)/,s=/(opera)(?:.*version)?[ \/]([\w.]+)/,t=/(msie) ([\w.]+)/,u=/(mozilla)(?:.*? rv:([\w.]+))?/,v=/-([a-z]|[0-9])/ig,w=/^-ms-/,x=function(a,b){return(b+"").toUpperCase()},y=d.userAgent,z,A,B,C=Object.prototype.toString,D=Object.prototype.hasOwnProperty,E=Array.prototype.push,F=Array.prototype.slice,G=String.prototype.trim,H=Array.prototype.indexOf,I={};e.fn=e.prototype={constructor:e,init:function(a,d,f){var g,h,j,k;if(!a)return this;if(a.nodeType){this.context=this[0]=a,this.length=1;return this}if(a==="body"&&!d&&c.body){this.context=c,this[0]=c.body,this.selector=a,this.length=1;return this}if(typeof a=="string"){a.charAt(0)!=="<"||a.charAt(a.length-1)!==">"||a.length<3?g=i.exec(a):g=[null,a,null];if(g&&(g[1]||!d)){if(g[1]){d=d instanceof e?d[0]:d,k=d?d.ownerDocument||d:c,j=m.exec(a),j?e.isPlainObject(d)?(a=[c.createElement(j[1])],e.fn.attr.call(a,d,!0)):a=[k.createElement(j[1])]:(j=e.buildFragment([g[1]],[k]),a=(j.cacheable?e.clone(j.fragment):j.fragment).childNodes);return e.merge(this,a)}h=c.getElementById(g[2]);if(h&&h.parentNode){if(h.id!==g[2])return f.find(a);this.length=1,this[0]=h}this.context=c,this.selector=a;return this}return!d||d.jquery?(d||f).find(a):this.constructor(d).find(a)}if(e.isFunction(a))return f.ready(a);a.selector!==b&&(this.selector=a.selector,this.context=a.context);return e.makeArray(a,this)},selector:"",jquery:"1.7.1",length:0,size:function(){return this.length},toArray:function(){return F.call(this,0)},get:function(a){return a==null?this.toArray():a<0?this[this.length+a]:this[a]},pushStack:function(a,b,c){var d=this.constructor();e.isArray(a)?E.apply(d,a):e.merge(d,a),d.prevObject=this,d.context=this.context,b==="find"?d.selector=this.selector+(this.selector?" ":"")+c:b&&(d.selector=this.selector+"."+b+"("+c+")");return d},each:function(a,b){return e.each(this,a,b)},ready:function(a){e.bindReady(),A.add(a);return this},eq:function(a){a=+a;return a===-1?this.slice(a):this.slice(a,a+1)},first:function(){return this.eq(0)},last:function(){return this.eq(-1)},slice:function(){return this.pushStack(F.apply(this,arguments),"slice",F.call(arguments).join(","))},map:function(a){return this.pushStack(e.map(this,function(b,c){return a.call(b,c,b)}))},end:function(){return this.prevObject||this.constructor(null)},push:E,sort:[].sort,splice:[].splice},e.fn.init.prototype=e.fn,e.extend=e.fn.extend=function(){var a,c,d,f,g,h,i=arguments[0]||{},j=1,k=arguments.length,l=!1;typeof i=="boolean"&&(l=i,i=arguments[1]||{},j=2),typeof i!="object"&&!e.isFunction(i)&&(i={}),k===j&&(i=this,--j);for(;j<k;j++)if((a=arguments[j])!=null)for(c in a){d=i[c],f=a[c];if(i===f)continue;l&&f&&(e.isPlainObject(f)||(g=e.isArray(f)))?(g?(g=!1,h=d&&e.isArray(d)?d:[]):h=d&&e.isPlainObject(d)?d:{},i[c]=e.extend(l,h,f)):f!==b&&(i[c]=f)}return i},e.extend({noConflict:function(b){a.$===e&&(a.$=g),b&&a.jQuery===e&&(a.jQuery=f);return e},isReady:!1,readyWait:1,holdReady:function(a){a?e.readyWait++:e.ready(!0)},ready:function(a){if(a===!0&&!--e.readyWait||a!==!0&&!e.isReady){if(!c.body)return setTimeout(e.ready,1);e.isReady=!0;if(a!==!0&&--e.readyWait>0)return;A.fireWith(c,[e]),e.fn.trigger&&e(c).trigger("ready").off("ready")}},bindReady:function(){if(!A){A=e.Callbacks("once memory");if(c.readyState==="complete")return setTimeout(e.ready,1);if(c.addEventListener)c.addEventListener("DOMContentLoaded",B,!1),a.addEventListener("load",e.ready,!1);else if(c.attachEvent){c.attachEvent("onreadystatechange",B),a.attachEvent("onload",e.ready);var b=!1;try{b=a.frameElement==null}catch(d){}c.documentElement.doScroll&&b&&J()}}},isFunction:function(a){return e.type(a)==="function"},isArray:Array.isArray||function(a){return e.type(a)==="array"},isWindow:function(a){return a&&typeof a=="object"&&"setInterval"in a},isNumeric:function(a){return!isNaN(parseFloat(a))&&isFinite(a)},type:function(a){return a==null?String(a):I[C.call(a)]||"object"},isPlainObject:function(a){if(!a||e.type(a)!=="object"||a.nodeType||e.isWindow(a))return!1;try{if(a.constructor&&!D.call(a,"constructor")&&!D.call(a.constructor.prototype,"isPrototypeOf"))return!1}catch(c){return!1}var d;for(d in a);return d===b||D.call(a,d)},isEmptyObject:function(a){for(var b in a)return!1;return!0},error:function(a){throw new Error(a)},parseJSON:function(b){if(typeof b!="string"||!b)return null;b=e.trim(b);if(a.JSON&&a.JSON.parse)return a.JSON.parse(b);if(n.test(b.replace(o,"@").replace(p,"]").replace(q,"")))return(new Function("return "+b))();e.error("Invalid JSON: "+b)},parseXML:function(c){var d,f;try{a.DOMParser?(f=new DOMParser,d=f.parseFromString(c,"text/xml")):(d=new ActiveXObject("Microsoft.XMLDOM"),d.async="false",d.loadXML(c))}catch(g){d=b}(!d||!d.documentElement||d.getElementsByTagName("parsererror").length)&&e.error("Invalid XML: "+c);return d},noop:function(){},globalEval:function(b){b&&j.test(b)&&(a.execScript||function(b){a.eval.call(a,b)})(b)},camelCase:function(a){return a.replace(w,"ms-").replace(v,x)},nodeName:function(a,b){return a.nodeName&&a.nodeName.toUpperCase()===b.toUpperCase()},each:function(a,c,d){var f,g=0,h=a.length,i=h===b||e.isFunction(a);if(d){if(i){for(f in a)if(c.apply(a[f],d)===!1)break}else for(;g<h;)if(c.apply(a[g++],d)===!1)break}else if(i){for(f in a)if(c.call(a[f],f,a[f])===!1)break}else for(;g<h;)if(c.call(a[g],g,a[g++])===!1)break;return a},trim:G?function(a){return a==null?"":G.call(a)}:function(a){return a==null?"":(a+"").replace(k,"").replace(l,"")},makeArray:function(a,b){var c=b||[];if(a!=null){var d=e.type(a);a.length==null||d==="string"||d==="function"||d==="regexp"||e.isWindow(a)?E.call(c,a):e.merge(c,a)}return c},inArray:function(a,b,c){var d;if(b){if(H)return H.call(b,a,c);d=b.length,c=c?c<0?Math.max(0,d+c):c:0;for(;c<d;c++)if(c in b&&b[c]===a)return c}return-1},merge:function(a,c){var d=a.length,e=0;if(typeof c.length=="number")for(var f=c.length;e<f;e++)a[d++]=c[e];else while(c[e]!==b)a[d++]=c[e++];a.length=d;return a},grep:function(a,b,c){var d=[],e;c=!!c;for(var f=0,g=a.length;f<g;f++)e=!!b(a[f],f),c!==e&&d.push(a[f]);return d},map:function(a,c,d){var f,g,h=[],i=0,j=a.length,k=a instanceof e||j!==b&&typeof j=="number"&&(j>0&&a[0]&&a[j-1]||j===0||e.isArray(a));if(k)for(;i<j;i++)f=c(a[i],i,d),f!=null&&(h[h.length]=f);else for(g in a)f=c(a[g],g,d),f!=null&&(h[h.length]=f);return h.concat.apply([],h)},guid:1,proxy:function(a,c){if(typeof c=="string"){var d=a[c];c=a,a=d}if(!e.isFunction(a))return b;var f=F.call(arguments,2),g=function(){return a.apply(c,f.concat(F.call(arguments)))};g.guid=a.guid=a.guid||g.guid||e.guid++;return g},access:function(a,c,d,f,g,h){var i=a.length;if(typeof c=="object"){for(var j in c)e.access(a,j,c[j],f,g,d);return a}if(d!==b){f=!h&&f&&e.isFunction(d);for(var k=0;k<i;k++)g(a[k],c,f?d.call(a[k],k,g(a[k],c)):d,h);return a}return i?g(a[0],c):b},now:function(){return(new Date).getTime()},uaMatch:function(a){a=a.toLowerCase();var b=r.exec(a)||s.exec(a)||t.exec(a)||a.indexOf("compatible")<0&&u.exec(a)||[];return{browser:b[1]||"",version:b[2]||"0"}},sub:function(){function a(b,c){return new a.fn.init(b,c)}e.extend(!0,a,this),a.superclass=this,a.fn=a.prototype=this(),a.fn.constructor=a,a.sub=this.sub,a.fn.init=function(d,f){f&&f instanceof e&&!(f instanceof a)&&(f=a(f));return e.fn.init.call(this,d,f,b)},a.fn.init.prototype=a.fn;var b=a(c);return a},browser:{}}),e.each("Boolean Number String Function Array Date RegExp Object".split(" "),function(a,b){I["[object "+b+"]"]=b.toLowerCase()}),z=e.uaMatch(y),z.browser&&(e.browser[z.browser]=!0,e.browser.version=z.version),e.browser.webkit&&(e.browser.safari=!0),j.test(" ")&&(k=/^[\s\xA0]+/,l=/[\s\xA0]+$/),h=e(c),c.addEventListener?B=function(){c.removeEventListener("DOMContentLoaded",B,!1),e.ready()}:c.attachEvent&&(B=function(){c.readyState==="complete"&&(c.detachEvent("onreadystatechange",B),e.ready())});return e}(),g={};f.Callbacks=function(a){a=a?g[a]||h(a):{};var c=[],d=[],e,i,j,k,l,m=function(b){var d,e,g,h,i;for(d=0,e=b.length;d<e;d++)g=b[d],h=f.type(g),h==="array"?m(g):h==="function"&&(!a.unique||!o.has(g))&&c.push(g)},n=function(b,f){f=f||[],e=!a.memory||[b,f],i=!0,l=j||0,j=0,k=c.length;for(;c&&l<k;l++)if(c[l].apply(b,f)===!1&&a.stopOnFalse){e=!0;break}i=!1,c&&(a.once?e===!0?o.disable():c=[]:d&&d.length&&(e=d.shift(),o.fireWith(e[0],e[1])))},o={add:function(){if(c){var a=c.length;m(arguments),i?k=c.length:e&&e!==!0&&(j=a,n(e[0],e[1]))}return this},remove:function(){if(c){var b=arguments,d=0,e=b.length;for(;d<e;d++)for(var f=0;f<c.length;f++)if(b[d]===c[f]){i&&f<=k&&(k--,f<=l&&l--),c.splice(f--,1);if(a.unique)break}}return this},has:function(a){if(c){var b=0,d=c.length;for(;b<d;b++)if(a===c[b])return!0}return!1},empty:function(){c=[];return this},disable:function(){c=d=e=b;return this},disabled:function(){return!c},lock:function(){d=b,(!e||e===!0)&&o.disable();return this},locked:function(){return!d},fireWith:function(b,c){d&&(i?a.once||d.push([b,c]):(!a.once||!e)&&n(b,c));return this},fire:function(){o.fireWith(this,arguments);return this},fired:function(){return!!e}};return o};var i=[].slice;f.extend({Deferred:function(a){var b=f.Callbacks("once memory"),c=f.Callbacks("once memory"),d=f.Callbacks("memory"),e="pending",g={resolve:b,reject:c,notify:d},h={done:b.add,fail:c.add,progress:d.add,state:function(){return e},isResolved:b.fired,isRejected:c.fired,then:function(a,b,c){i.done(a).fail(b).progress(c);return this},always:function(){i.done.apply(i,arguments).fail.apply(i,arguments);return this},pipe:function(a,b,c){return f.Deferred(function(d){f.each({done:[a,"resolve"],fail:[b,"reject"],progress:[c,"notify"]},function(a,b){var c=b[0],e=b[1],g;f.isFunction(c)?i[a](function(){g=c.apply(this,arguments),g&&f.isFunction(g.promise)?g.promise().then(d.resolve,d.reject,d.notify):d[e+"With"](this===i?d:this,[g])}):i[a](d[e])})}).promise()},promise:function(a){if(a==null)a=h;else for(var b in h)a[b]=h[b];return a}},i=h.promise({}),j;for(j in g)i[j]=g[j].fire,i[j+"With"]=g[j].fireWith;i.done(function(){e="resolved"},c.disable,d.lock).fail(function(){e="rejected"},b.disable,d.lock),a&&a.call(i,i);return i},when:function(a){function m(a){return function(b){e[a]=arguments.length>1?i.call(arguments,0):b,j.notifyWith(k,e)}}function l(a){return function(c){b[a]=arguments.length>1?i.call(arguments,0):c,--g||j.resolveWith(j,b)}}var b=i.call(arguments,0),c=0,d=b.length,e=Array(d),g=d,h=d,j=d<=1&&a&&f.isFunction(a.promise)?a:f.Deferred(),k=j.promise();if(d>1){for(;c<d;c++)b[c]&&b[c].promise&&f.isFunction(b[c].promise)?b[c].promise().then(l(c),j.reject,m(c)):--g;g||j.resolveWith(j,b)}else j!==a&&j.resolveWith(j,d?[a]:[]);return k}}),f.support=function(){var b,d,e,g,h,i,j,k,l,m,n,o,p,q=c.createElement("div"),r=c.documentElement;q.setAttribute("className","t"),q.innerHTML="   <link/><table></table><a href='/a' style='top:1px;float:left;opacity:.55;'>a</a><input type='checkbox'/>",d=q.getElementsByTagName("*"),e=q.getElementsByTagName("a")[0];if(!d||!d.length||!e)return{};g=c.createElement("select"),h=g.appendChild(c.createElement("option")),i=q.getElementsByTagName("input")[0],b={leadingWhitespace:q.firstChild.nodeType===3,tbody:!q.getElementsByTagName("tbody").length,htmlSerialize:!!q.getElementsByTagName("link").length,style:/top/.test(e.getAttribute("style")),hrefNormalized:e.getAttribute("href")==="/a",opacity:/^0.55/.test(e.style.opacity),cssFloat:!!e.style.cssFloat,checkOn:i.value==="on",optSelected:h.selected,getSetAttribute:q.className!=="t",enctype:!!c.createElement("form").enctype,html5Clone:c.createElement("nav").cloneNode(!0).outerHTML!=="<:nav></:nav>",submitBubbles:!0,changeBubbles:!0,focusinBubbles:!1,deleteExpando:!0,noCloneEvent:!0,inlineBlockNeedsLayout:!1,shrinkWrapBlocks:!1,reliableMarginRight:!0},i.checked=!0,b.noCloneChecked=i.cloneNode(!0).checked,g.disabled=!0,b.optDisabled=!h.disabled;try{delete q.test}catch(s){b.deleteExpando=!1}!q.addEventListener&&q.attachEvent&&q.fireEvent&&(q.attachEvent("onclick",function(){b.noCloneEvent=!1}),q.cloneNode(!0).fireEvent("onclick")),i=c.createElement("input"),i.value="t",i.setAttribute("type","radio"),b.radioValue=i.value==="t",i.setAttribute("checked","checked"),q.appendChild(i),k=c.createDocumentFragment(),k.appendChild(q.lastChild),b.checkClone=k.cloneNode(!0).cloneNode(!0).lastChild.checked,b.appendChecked=i.checked,k.removeChild(i),k.appendChild(q),q.innerHTML="",a.getComputedStyle&&(j=c.createElement("div"),j.style.width="0",j.style.marginRight="0",q.style.width="2px",q.appendChild(j),b.reliableMarginRight=(parseInt((a.getComputedStyle(j,null)||{marginRight:0}).marginRight,10)||0)===0);if(q.attachEvent)for(o in{submit:1,change:1,focusin:1})n="on"+o,p=n in q,p||(q.setAttribute(n,"return;"),p=typeof q[n]=="function"),b[o+"Bubbles"]=p;k.removeChild(q),k=g=h=j=q=i=null,f(function(){var a,d,e,g,h,i,j,k,m,n,o,r=c.getElementsByTagName("body")[0];!r||(j=1,k="position:absolute;top:0;left:0;width:1px;height:1px;margin:0;",m="visibility:hidden;border:0;",n="style='"+k+"border:5px solid #000;padding:0;'",o="<div "+n+"><div></div></div>"+"<table "+n+" cellpadding='0' cellspacing='0'>"+"<tr><td></td></tr></table>",a=c.createElement("div"),a.style.cssText=m+"width:0;height:0;position:static;top:0;margin-top:"+j+"px",r.insertBefore(a,r.firstChild),q=c.createElement("div"),a.appendChild(q),q.innerHTML="<table><tr><td style='padding:0;border:0;display:none'></td><td>t</td></tr></table>",l=q.getElementsByTagName("td"),p=l[0].offsetHeight===0,l[0].style.display="",l[1].style.display="none",b.reliableHiddenOffsets=p&&l[0].offsetHeight===0,q.innerHTML="",q.style.width=q.style.paddingLeft="1px",f.boxModel=b.boxModel=q.offsetWidth===2,typeof q.style.zoom!="undefined"&&(q.style.display="inline",q.style.zoom=1,b.inlineBlockNeedsLayout=q.offsetWidth===2,q.style.display="",q.innerHTML="<div style='width:4px;'></div>",b.shrinkWrapBlocks=q.offsetWidth!==2),q.style.cssText=k+m,q.innerHTML=o,d=q.firstChild,e=d.firstChild,h=d.nextSibling.firstChild.firstChild,i={doesNotAddBorder:e.offsetTop!==5,doesAddBorderForTableAndCells:h.offsetTop===5},e.style.position="fixed",e.style.top="20px",i.fixedPosition=e.offsetTop===20||e.offsetTop===15,e.style.position=e.style.top="",d.style.overflow="hidden",d.style.position="relative",i.subtractsBorderForOverflowNotVisible=e.offsetTop===-5,i.doesNotIncludeMarginInBodyOffset=r.offsetTop!==j,r.removeChild(a),q=a=null,f.extend(b,i))});return b}();var j=/^(?:\{.*\}|\[.*\])$/,k=/([A-Z])/g;f.extend({cache:{},uuid:0,expando:"jQuery"+(f.fn.jquery+Math.random()).replace(/\D/g,""),noData:{embed:!0,object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000",applet:!0},hasData:function(a){a=a.nodeType?f.cache[a[f.expando]]:a[f.expando];return!!a&&!m(a)},data:function(a,c,d,e){if(!!f.acceptData(a)){var g,h,i,j=f.expando,k=typeof c=="string",l=a.nodeType,m=l?f.cache:a,n=l?a[j]:a[j]&&j,o=c==="events";if((!n||!m[n]||!o&&!e&&!m[n].data)&&k&&d===b)return;n||(l?a[j]=n=++f.uuid:n=j),m[n]||(m[n]={},l||(m[n].toJSON=f.noop));if(typeof c=="object"||typeof c=="function")e?m[n]=f.extend(m[n],c):m[n].data=f.extend(m[n].data,c);g=h=m[n],e||(h.data||(h.data={}),h=h.data),d!==b&&(h[f.camelCase(c)]=d);if(o&&!h[c])return g.events;k?(i=h[c],i==null&&(i=h[f.camelCase(c)])):i=h;return i}},removeData:function(a,b,c){if(!!f.acceptData(a)){var d,e,g,h=f.expando,i=a.nodeType,j=i?f.cache:a,k=i?a[h]:h;if(!j[k])return;if(b){d=c?j[k]:j[k].data;if(d){f.isArray(b)||(b in d?b=[b]:(b=f.camelCase(b),b in d?b=[b]:b=b.split(" ")));for(e=0,g=b.length;e<g;e++)delete d[b[e]];if(!(c?m:f.isEmptyObject)(d))return}}if(!c){delete j[k].data;if(!m(j[k]))return}f.support.deleteExpando||!j.setInterval?delete j[k]:j[k]=null,i&&(f.support.deleteExpando?delete a[h]:a.removeAttribute?a.removeAttribute(h):a[h]=null)}},_data:function(a,b,c){return f.data(a,b,c,!0)},acceptData:function(a){if(a.nodeName){var b=f.noData[a.nodeName.toLowerCase()];if(b)return b!==!0&&a.getAttribute("classid")===b}return!0}}),f.fn.extend({data:function(a,c){var d,e,g,h=null;if(typeof a=="undefined"){if(this.length){h=f.data(this[0]);if(this[0].nodeType===1&&!f._data(this[0],"parsedAttrs")){e=this[0].attributes;for(var i=0,j=e.length;i<j;i++)g=e[i].name,g.indexOf("data-")===0&&(g=f.camelCase(g.substring(5)),l(this[0],g,h[g]));f._data(this[0],"parsedAttrs",!0)}}return h}if(typeof a=="object")return this.each(function(){f.data(this,a)});d=a.split("."),d[1]=d[1]?"."+d[1]:"";if(c===b){h=this.triggerHandler("getData"+d[1]+"!",[d[0]]),h===b&&this.length&&(h=f.data(this[0],a),h=l(this[0],a,h));return h===b&&d[1]?this.data(d[0]):h}return this.each(function(){var b=f(this),e=[d[0],c];b.triggerHandler("setData"+d[1]+"!",e),f.data(this,a,c),b.triggerHandler("changeData"+d[1]+"!",e)})},removeData:function(a){return this.each(function(){f.removeData(this,a)})}}),f.extend({_mark:function(a,b){a&&(b=(b||"fx")+"mark",f._data(a,b,(f._data(a,b)||0)+1))},_unmark:function(a,b,c){a!==!0&&(c=b,b=a,a=!1);if(b){c=c||"fx";var d=c+"mark",e=a?0:(f._data(b,d)||1)-1;e?f._data(b,d,e):(f.removeData(b,d,!0),n(b,c,"mark"))}},queue:function(a,b,c){var d;if(a){b=(b||"fx")+"queue",d=f._data(a,b),c&&(!d||f.isArray(c)?d=f._data(a,b,f.makeArray(c)):d.push(c));return d||[]}},dequeue:function(a,b){b=b||"fx";var c=f.queue(a,b),d=c.shift(),e={};d==="inprogress"&&(d=c.shift()),d&&(b==="fx"&&c.unshift("inprogress"),f._data(a,b+".run",e),d.call(a,function(){f.dequeue(a,b)},e)),c.length||(f.removeData(a,b+"queue "+b+".run",!0),n(a,b,"queue"))}}),f.fn.extend({queue:function(a,c){typeof a!="string"&&(c=a,a="fx");if(c===b)return f.queue(this[0],a);return this.each(function(){var b=f.queue(this,a,c);a==="fx"&&b[0]!=="inprogress"&&f.dequeue(this,a)})},dequeue:function(a){return this.each(function(){f.dequeue(this,a)})},delay:function(a,b){a=f.fx?f.fx.speeds[a]||a:a,b=b||"fx";return this.queue(b,function(b,c){var d=setTimeout(b,a);c.stop=function(){clearTimeout(d)}})},clearQueue:function(a){return this.queue(a||"fx",[])},promise:function(a,c){function m(){--h||d.resolveWith(e,[e])}typeof a!="string"&&(c=a,a=b),a=a||"fx";var d=f.Deferred(),e=this,g=e.length,h=1,i=a+"defer",j=a+"queue",k=a+"mark",l;while(g--)if(l=f.data(e[g],i,b,!0)||(f.data(e[g],j,b,!0)||f.data(e[g],k,b,!0))&&f.data(e[g],i,f.Callbacks("once memory"),!0))h++,l.add(m);m();return d.promise()}});var o=/[\n\t\r]/g,p=/\s+/,q=/\r/g,r=/^(?:button|input)$/i,s=/^(?:button|input|object|select|textarea)$/i,t=/^a(?:rea)?$/i,u=/^(?:autofocus|autoplay|async|checked|controls|defer|disabled|hidden|loop|multiple|open|readonly|required|scoped|selected)$/i,v=f.support.getSetAttribute,w,x,y;f.fn.extend({attr:function(a,b){return f.access(this,a,b,!0,f.attr)},removeAttr:function(a){return this.each(function(){f.removeAttr(this,a)})},prop:function(a,b){return f.access(this,a,b,!0,f.prop)},removeProp:function(a){a=f.propFix[a]||a;return this.each(function(){try{this[a]=b,delete this[a]}catch(c){}})},addClass:function(a){var b,c,d,e,g,h,i;if(f.isFunction(a))return this.each(function(b){f(this).addClass(a.call(this,b,this.className))});if(a&&typeof a=="string"){b=a.split(p);for(c=0,d=this.length;c<d;c++){e=this[c];if(e.nodeType===1)if(!e.className&&b.length===1)e.className=a;else{g=" "+e.className+" ";for(h=0,i=b.length;h<i;h++)~g.indexOf(" "+b[h]+" ")||(g+=b[h]+" ");e.className=f.trim(g)}}}return this},removeClass:function(a){var c,d,e,g,h,i,j;if(f.isFunction(a))return this.each(function(b){f(this).removeClass(a.call(this,b,this.className))});if(a&&typeof a=="string"||a===b){c=(a||"").split(p);for(d=0,e=this.length;d<e;d++){g=this[d];if(g.nodeType===1&&g.className)if(a){h=(" "+g.className+" ").replace(o," ");for(i=0,j=c.length;i<j;i++)h=h.replace(" "+c[i]+" "," ");g.className=f.trim(h)}else g.className=""}}return this},toggleClass:function(a,b){var c=typeof a,d=typeof b=="boolean";if(f.isFunction(a))return this.each(function(c){f(this).toggleClass(a.call(this,c,this.className,b),b)});return this.each(function(){if(c==="string"){var e,g=0,h=f(this),i=b,j=a.split(p);while(e=j[g++])i=d?i:!h.hasClass(e),h[i?"addClass":"removeClass"](e)}else if(c==="undefined"||c==="boolean")this.className&&f._data(this,"__className__",this.className),this.className=this.className||a===!1?"":f._data(this,"__className__")||""})},hasClass:function(a){var b=" "+a+" ",c=0,d=this.length;for(;c<d;c++)if(this[c].nodeType===1&&(" "+this[c].className+" ").replace(o," ").indexOf(b)>-1)return!0;return!1},val:function(a){var c,d,e,g=this[0];{if(!!arguments.length){e=f.isFunction(a);return this.each(function(d){var g=f(this),h;if(this.nodeType===1){e?h=a.call(this,d,g.val()):h=a,h==null?h="":typeof h=="number"?h+="":f.isArray(h)&&(h=f.map(h,function(a){return a==null?"":a+""})),c=f.valHooks[this.nodeName.toLowerCase()]||f.valHooks[this.type];if(!c||!("set"in c)||c.set(this,h,"value")===b)this.value=h}})}if(g){c=f.valHooks[g.nodeName.toLowerCase()]||f.valHooks[g.type];if(c&&"get"in c&&(d=c.get(g,"value"))!==b)return d;d=g.value;return typeof d=="string"?d.replace(q,""):d==null?"":d}}}}),f.extend({valHooks:{option:{get:function(a){var b=a.attributes.value;return!b||b.specified?a.value:a.text}},select:{get:function(a){var b,c,d,e,g=a.selectedIndex,h=[],i=a.options,j=a.type==="select-one";if(g<0)return null;c=j?g:0,d=j?g+1:i.length;for(;c<d;c++){e=i[c];if(e.selected&&(f.support.optDisabled?!e.disabled:e.getAttribute("disabled")===null)&&(!e.parentNode.disabled||!f.nodeName(e.parentNode,"optgroup"))){b=f(e).val();if(j)return b;h.push(b)}}if(j&&!h.length&&i.length)return f(i[g]).val();return h},set:function(a,b){var c=f.makeArray(b);f(a).find("option").each(function(){this.selected=f.inArray(f(this).val(),c)>=0}),c.length||(a.selectedIndex=-1);return c}}},attrFn:{val:!0,css:!0,html:!0,text:!0,data:!0,width:!0,height:!0,offset:!0},attr:function(a,c,d,e){var g,h,i,j=a.nodeType;if(!!a&&j!==3&&j!==8&&j!==2){if(e&&c in f.attrFn)return f(a)[c](d);if(typeof a.getAttribute=="undefined")return f.prop(a,c,d);i=j!==1||!f.isXMLDoc(a),i&&(c=c.toLowerCase(),h=f.attrHooks[c]||(u.test(c)?x:w));if(d!==b){if(d===null){f.removeAttr(a,c);return}if(h&&"set"in h&&i&&(g=h.set(a,d,c))!==b)return g;a.setAttribute(c,""+d);return d}if(h&&"get"in h&&i&&(g=h.get(a,c))!==null)return g;g=a.getAttribute(c);return g===null?b:g}},removeAttr:function(a,b){var c,d,e,g,h=0;if(b&&a.nodeType===1){d=b.toLowerCase().split(p),g=d.length;for(;h<g;h++)e=d[h],e&&(c=f.propFix[e]||e,f.attr(a,e,""),a.removeAttribute(v?e:c),u.test(e)&&c in a&&(a[c]=!1))}},attrHooks:{type:{set:function(a,b){if(r.test(a.nodeName)&&a.parentNode)f.error("type property can't be changed");else if(!f.support.radioValue&&b==="radio"&&f.nodeName(a,"input")){var c=a.value;a.setAttribute("type",b),c&&(a.value=c);return b}}},value:{get:function(a,b){if(w&&f.nodeName(a,"button"))return w.get(a,b);return b in a?a.value:null},set:function(a,b,c){if(w&&f.nodeName(a,"button"))return w.set(a,b,c);a.value=b}}},propFix:{tabindex:"tabIndex",readonly:"readOnly","for":"htmlFor","class":"className",maxlength:"maxLength",cellspacing:"cellSpacing",cellpadding:"cellPadding",rowspan:"rowSpan",colspan:"colSpan",usemap:"useMap",frameborder:"frameBorder",contenteditable:"contentEditable"},prop:function(a,c,d){var e,g,h,i=a.nodeType;if(!!a&&i!==3&&i!==8&&i!==2){h=i!==1||!f.isXMLDoc(a),h&&(c=f.propFix[c]||c,g=f.propHooks[c]);return d!==b?g&&"set"in g&&(e=g.set(a,d,c))!==b?e:a[c]=d:g&&"get"in g&&(e=g.get(a,c))!==null?e:a[c]}},propHooks:{tabIndex:{get:function(a){var c=a.getAttributeNode("tabindex");return c&&c.specified?parseInt(c.value,10):s.test(a.nodeName)||t.test(a.nodeName)&&a.href?0:b}}}}),f.attrHooks.tabindex=f.propHooks.tabIndex,x={get:function(a,c){var d,e=f.prop(a,c);return e===!0||typeof e!="boolean"&&(d=a.getAttributeNode(c))&&d.nodeValue!==!1?c.toLowerCase():b},set:function(a,b,c){var d;b===!1?f.removeAttr(a,c):(d=f.propFix[c]||c,d in a&&(a[d]=!0),a.setAttribute(c,c.toLowerCase()));return c}},v||(y={name:!0,id:!0},w=f.valHooks.button={get:function(a,c){var d;d=a.getAttributeNode(c);return d&&(y[c]?d.nodeValue!=="":d.specified)?d.nodeValue:b},set:function(a,b,d){var e=a.getAttributeNode(d);e||(e=c.createAttribute(d),a.setAttributeNode(e));return e.nodeValue=b+""}},f.attrHooks.tabindex.set=w.set,f.each(["width","height"],function(a,b){f.attrHooks[b]=f.extend(f.attrHooks[b],{set:function(a,c){if(c===""){a.setAttribute(b,"auto");return c}}})}),f.attrHooks.contenteditable={get:w.get,set:function(a,b,c){b===""&&(b="false"),w.set(a,b,c)}}),f.support.hrefNormalized||f.each(["href","src","width","height"],function(a,c){f.attrHooks[c]=f.extend(f.attrHooks[c],{get:function(a){var d=a.getAttribute(c,2);return d===null?b:d}})}),f.support.style||(f.attrHooks.style={get:function(a){return a.style.cssText.toLowerCase()||b},set:function(a,b){return a.style.cssText=""+b}}),f.support.optSelected||(f.propHooks.selected=f.extend(f.propHooks.selected,{get:function(a){var b=a.parentNode;b&&(b.selectedIndex,b.parentNode&&b.parentNode.selectedIndex);return null}})),f.support.enctype||(f.propFix.enctype="encoding"),f.support.checkOn||f.each(["radio","checkbox"],function(){f.valHooks[this]={get:function(a){return a.getAttribute("value")===null?"on":a.value}}}),f.each(["radio","checkbox"],function(){f.valHooks[this]=f.extend(f.valHooks[this],{set:function(a,b){if(f.isArray(b))return a.checked=f.inArray(f(a).val(),b)>=0}})});var z=/^(?:textarea|input|select)$/i,A=/^([^\.]*)?(?:\.(.+))?$/,B=/\bhover(\.\S+)?\b/,C=/^key/,D=/^(?:mouse|contextmenu)|click/,E=/^(?:focusinfocus|focusoutblur)$/,F=/^(\w*)(?:#([\w\-]+))?(?:\.([\w\-]+))?$/,G=function(a){var b=F.exec(a);b&&(b[1]=(b[1]||"").toLowerCase(),b[3]=b[3]&&new RegExp("(?:^|\\s)"+b[3]+"(?:\\s|$)"));return b},H=function(a,b){var c=a.attributes||{};return(!b[1]||a.nodeName.toLowerCase()===b[1])&&(!b[2]||(c.id||{}).value===b[2])&&(!b[3]||b[3].test((c["class"]||{}).value))},I=function(a){return f.event.special.hover?a:a.replace(B,"mouseenter$1 mouseleave$1")};
        f.event={add:function(a,c,d,e,g){var h,i,j,k,l,m,n,o,p,q,r,s;if(!(a.nodeType===3||a.nodeType===8||!c||!d||!(h=f._data(a)))){d.handler&&(p=d,d=p.handler),d.guid||(d.guid=f.guid++),j=h.events,j||(h.events=j={}),i=h.handle,i||(h.handle=i=function(a){return typeof f!="undefined"&&(!a||f.event.triggered!==a.type)?f.event.dispatch.apply(i.elem,arguments):b},i.elem=a),c=f.trim(I(c)).split(" ");for(k=0;k<c.length;k++){l=A.exec(c[k])||[],m=l[1],n=(l[2]||"").split(".").sort(),s=f.event.special[m]||{},m=(g?s.delegateType:s.bindType)||m,s=f.event.special[m]||{},o=f.extend({type:m,origType:l[1],data:e,handler:d,guid:d.guid,selector:g,quick:G(g),namespace:n.join(".")},p),r=j[m];if(!r){r=j[m]=[],r.delegateCount=0;if(!s.setup||s.setup.call(a,e,n,i)===!1)a.addEventListener?a.addEventListener(m,i,!1):a.attachEvent&&a.attachEvent("on"+m,i)}s.add&&(s.add.call(a,o),o.handler.guid||(o.handler.guid=d.guid)),g?r.splice(r.delegateCount++,0,o):r.push(o),f.event.global[m]=!0}a=null}},global:{},remove:function(a,b,c,d,e){var g=f.hasData(a)&&f._data(a),h,i,j,k,l,m,n,o,p,q,r,s;if(!!g&&!!(o=g.events)){b=f.trim(I(b||"")).split(" ");for(h=0;h<b.length;h++){i=A.exec(b[h])||[],j=k=i[1],l=i[2];if(!j){for(j in o)f.event.remove(a,j+b[h],c,d,!0);continue}p=f.event.special[j]||{},j=(d?p.delegateType:p.bindType)||j,r=o[j]||[],m=r.length,l=l?new RegExp("(^|\\.)"+l.split(".").sort().join("\\.(?:.*\\.)?")+"(\\.|$)"):null;for(n=0;n<r.length;n++)s=r[n],(e||k===s.origType)&&(!c||c.guid===s.guid)&&(!l||l.test(s.namespace))&&(!d||d===s.selector||d==="**"&&s.selector)&&(r.splice(n--,1),s.selector&&r.delegateCount--,p.remove&&p.remove.call(a,s));r.length===0&&m!==r.length&&((!p.teardown||p.teardown.call(a,l)===!1)&&f.removeEvent(a,j,g.handle),delete o[j])}f.isEmptyObject(o)&&(q=g.handle,q&&(q.elem=null),f.removeData(a,["events","handle"],!0))}},customEvent:{getData:!0,setData:!0,changeData:!0},trigger:function(c,d,e,g){if(!e||e.nodeType!==3&&e.nodeType!==8){var h=c.type||c,i=[],j,k,l,m,n,o,p,q,r,s;if(E.test(h+f.event.triggered))return;h.indexOf("!")>=0&&(h=h.slice(0,-1),k=!0),h.indexOf(".")>=0&&(i=h.split("."),h=i.shift(),i.sort());if((!e||f.event.customEvent[h])&&!f.event.global[h])return;c=typeof c=="object"?c[f.expando]?c:new f.Event(h,c):new f.Event(h),c.type=h,c.isTrigger=!0,c.exclusive=k,c.namespace=i.join("."),c.namespace_re=c.namespace?new RegExp("(^|\\.)"+i.join("\\.(?:.*\\.)?")+"(\\.|$)"):null,o=h.indexOf(":")<0?"on"+h:"";if(!e){j=f.cache;for(l in j)j[l].events&&j[l].events[h]&&f.event.trigger(c,d,j[l].handle.elem,!0);return}c.result=b,c.target||(c.target=e),d=d!=null?f.makeArray(d):[],d.unshift(c),p=f.event.special[h]||{};if(p.trigger&&p.trigger.apply(e,d)===!1)return;r=[[e,p.bindType||h]];if(!g&&!p.noBubble&&!f.isWindow(e)){s=p.delegateType||h,m=E.test(s+h)?e:e.parentNode,n=null;for(;m;m=m.parentNode)r.push([m,s]),n=m;n&&n===e.ownerDocument&&r.push([n.defaultView||n.parentWindow||a,s])}for(l=0;l<r.length&&!c.isPropagationStopped();l++)m=r[l][0],c.type=r[l][1],q=(f._data(m,"events")||{})[c.type]&&f._data(m,"handle"),q&&q.apply(m,d),q=o&&m[o],q&&f.acceptData(m)&&q.apply(m,d)===!1&&c.preventDefault();c.type=h,!g&&!c.isDefaultPrevented()&&(!p._default||p._default.apply(e.ownerDocument,d)===!1)&&(h!=="click"||!f.nodeName(e,"a"))&&f.acceptData(e)&&o&&e[h]&&(h!=="focus"&&h!=="blur"||c.target.offsetWidth!==0)&&!f.isWindow(e)&&(n=e[o],n&&(e[o]=null),f.event.triggered=h,e[h](),f.event.triggered=b,n&&(e[o]=n));return c.result}},dispatch:function(c){c=f.event.fix(c||a.event);var d=(f._data(this,"events")||{})[c.type]||[],e=d.delegateCount,g=[].slice.call(arguments,0),h=!c.exclusive&&!c.namespace,i=[],j,k,l,m,n,o,p,q,r,s,t;g[0]=c,c.delegateTarget=this;if(e&&!c.target.disabled&&(!c.button||c.type!=="click")){m=f(this),m.context=this.ownerDocument||this;for(l=c.target;l!=this;l=l.parentNode||this){o={},q=[],m[0]=l;for(j=0;j<e;j++)r=d[j],s=r.selector,o[s]===b&&(o[s]=r.quick?H(l,r.quick):m.is(s)),o[s]&&q.push(r);q.length&&i.push({elem:l,matches:q})}}d.length>e&&i.push({elem:this,matches:d.slice(e)});for(j=0;j<i.length&&!c.isPropagationStopped();j++){p=i[j],c.currentTarget=p.elem;for(k=0;k<p.matches.length&&!c.isImmediatePropagationStopped();k++){r=p.matches[k];if(h||!c.namespace&&!r.namespace||c.namespace_re&&c.namespace_re.test(r.namespace))c.data=r.data,c.handleObj=r,n=((f.event.special[r.origType]||{}).handle||r.handler).apply(p.elem,g),n!==b&&(c.result=n,n===!1&&(c.preventDefault(),c.stopPropagation()))}}return c.result},props:"attrChange attrName relatedNode srcElement altKey bubbles cancelable ctrlKey currentTarget eventPhase metaKey relatedTarget shiftKey target timeStamp view which".split(" "),fixHooks:{},keyHooks:{props:"char charCode key keyCode".split(" "),filter:function(a,b){a.which==null&&(a.which=b.charCode!=null?b.charCode:b.keyCode);return a}},mouseHooks:{props:"button buttons clientX clientY fromElement offsetX offsetY pageX pageY screenX screenY toElement".split(" "),filter:function(a,d){var e,f,g,h=d.button,i=d.fromElement;a.pageX==null&&d.clientX!=null&&(e=a.target.ownerDocument||c,f=e.documentElement,g=e.body,a.pageX=d.clientX+(f&&f.scrollLeft||g&&g.scrollLeft||0)-(f&&f.clientLeft||g&&g.clientLeft||0),a.pageY=d.clientY+(f&&f.scrollTop||g&&g.scrollTop||0)-(f&&f.clientTop||g&&g.clientTop||0)),!a.relatedTarget&&i&&(a.relatedTarget=i===a.target?d.toElement:i),!a.which&&h!==b&&(a.which=h&1?1:h&2?3:h&4?2:0);return a}},fix:function(a){if(a[f.expando])return a;var d,e,g=a,h=f.event.fixHooks[a.type]||{},i=h.props?this.props.concat(h.props):this.props;a=f.Event(g);for(d=i.length;d;)e=i[--d],a[e]=g[e];a.target||(a.target=g.srcElement||c),a.target.nodeType===3&&(a.target=a.target.parentNode),a.metaKey===b&&(a.metaKey=a.ctrlKey);return h.filter?h.filter(a,g):a},special:{ready:{setup:f.bindReady},load:{noBubble:!0},focus:{delegateType:"focusin"},blur:{delegateType:"focusout"},beforeunload:{setup:function(a,b,c){f.isWindow(this)&&(this.onbeforeunload=c)},teardown:function(a,b){this.onbeforeunload===b&&(this.onbeforeunload=null)}}},simulate:function(a,b,c,d){var e=f.extend(new f.Event,c,{type:a,isSimulated:!0,originalEvent:{}});d?f.event.trigger(e,null,b):f.event.dispatch.call(b,e),e.isDefaultPrevented()&&c.preventDefault()}},f.event.handle=f.event.dispatch,f.removeEvent=c.removeEventListener?function(a,b,c){a.removeEventListener&&a.removeEventListener(b,c,!1)}:function(a,b,c){a.detachEvent&&a.detachEvent("on"+b,c)},f.Event=function(a,b){if(!(this instanceof f.Event))return new f.Event(a,b);a&&a.type?(this.originalEvent=a,this.type=a.type,this.isDefaultPrevented=a.defaultPrevented||a.returnValue===!1||a.getPreventDefault&&a.getPreventDefault()?K:J):this.type=a,b&&f.extend(this,b),this.timeStamp=a&&a.timeStamp||f.now(),this[f.expando]=!0},f.Event.prototype={preventDefault:function(){this.isDefaultPrevented=K;var a=this.originalEvent;!a||(a.preventDefault?a.preventDefault():a.returnValue=!1)},stopPropagation:function(){this.isPropagationStopped=K;var a=this.originalEvent;!a||(a.stopPropagation&&a.stopPropagation(),a.cancelBubble=!0)},stopImmediatePropagation:function(){this.isImmediatePropagationStopped=K,this.stopPropagation()},isDefaultPrevented:J,isPropagationStopped:J,isImmediatePropagationStopped:J},f.each({mouseenter:"mouseover",mouseleave:"mouseout"},function(a,b){f.event.special[a]={delegateType:b,bindType:b,handle:function(a){var c=this,d=a.relatedTarget,e=a.handleObj,g=e.selector,h;if(!d||d!==c&&!f.contains(c,d))a.type=e.origType,h=e.handler.apply(this,arguments),a.type=b;return h}}}),f.support.submitBubbles||(f.event.special.submit={setup:function(){if(f.nodeName(this,"form"))return!1;f.event.add(this,"click._submit keypress._submit",function(a){var c=a.target,d=f.nodeName(c,"input")||f.nodeName(c,"button")?c.form:b;d&&!d._submit_attached&&(f.event.add(d,"submit._submit",function(a){this.parentNode&&!a.isTrigger&&f.event.simulate("submit",this.parentNode,a,!0)}),d._submit_attached=!0)})},teardown:function(){if(f.nodeName(this,"form"))return!1;f.event.remove(this,"._submit")}}),f.support.changeBubbles||(f.event.special.change={setup:function(){if(z.test(this.nodeName)){if(this.type==="checkbox"||this.type==="radio")f.event.add(this,"propertychange._change",function(a){a.originalEvent.propertyName==="checked"&&(this._just_changed=!0)}),f.event.add(this,"click._change",function(a){this._just_changed&&!a.isTrigger&&(this._just_changed=!1,f.event.simulate("change",this,a,!0))});return!1}f.event.add(this,"beforeactivate._change",function(a){var b=a.target;z.test(b.nodeName)&&!b._change_attached&&(f.event.add(b,"change._change",function(a){this.parentNode&&!a.isSimulated&&!a.isTrigger&&f.event.simulate("change",this.parentNode,a,!0)}),b._change_attached=!0)})},handle:function(a){var b=a.target;if(this!==b||a.isSimulated||a.isTrigger||b.type!=="radio"&&b.type!=="checkbox")return a.handleObj.handler.apply(this,arguments)},teardown:function(){f.event.remove(this,"._change");return z.test(this.nodeName)}}),f.support.focusinBubbles||f.each({focus:"focusin",blur:"focusout"},function(a,b){var d=0,e=function(a){f.event.simulate(b,a.target,f.event.fix(a),!0)};f.event.special[b]={setup:function(){d++===0&&c.addEventListener(a,e,!0)},teardown:function(){--d===0&&c.removeEventListener(a,e,!0)}}}),f.fn.extend({on:function(a,c,d,e,g){var h,i;if(typeof a=="object"){typeof c!="string"&&(d=c,c=b);for(i in a)this.on(i,c,d,a[i],g);return this}d==null&&e==null?(e=c,d=c=b):e==null&&(typeof c=="string"?(e=d,d=b):(e=d,d=c,c=b));if(e===!1)e=J;else if(!e)return this;g===1&&(h=e,e=function(a){f().off(a);return h.apply(this,arguments)},e.guid=h.guid||(h.guid=f.guid++));return this.each(function(){f.event.add(this,a,e,d,c)})},one:function(a,b,c,d){return this.on.call(this,a,b,c,d,1)},off:function(a,c,d){if(a&&a.preventDefault&&a.handleObj){var e=a.handleObj;f(a.delegateTarget).off(e.namespace?e.type+"."+e.namespace:e.type,e.selector,e.handler);return this}if(typeof a=="object"){for(var g in a)this.off(g,c,a[g]);return this}if(c===!1||typeof c=="function")d=c,c=b;d===!1&&(d=J);return this.each(function(){f.event.remove(this,a,d,c)})},bind:function(a,b,c){return this.on(a,null,b,c)},unbind:function(a,b){return this.off(a,null,b)},live:function(a,b,c){f(this.context).on(a,this.selector,b,c);return this},die:function(a,b){f(this.context).off(a,this.selector||"**",b);return this},delegate:function(a,b,c,d){return this.on(b,a,c,d)},undelegate:function(a,b,c){return arguments.length==1?this.off(a,"**"):this.off(b,a,c)},trigger:function(a,b){return this.each(function(){f.event.trigger(a,b,this)})},triggerHandler:function(a,b){if(this[0])return f.event.trigger(a,b,this[0],!0)},toggle:function(a){var b=arguments,c=a.guid||f.guid++,d=0,e=function(c){var e=(f._data(this,"lastToggle"+a.guid)||0)%d;f._data(this,"lastToggle"+a.guid,e+1),c.preventDefault();return b[e].apply(this,arguments)||!1};e.guid=c;while(d<b.length)b[d++].guid=c;return this.click(e)},hover:function(a,b){return this.mouseenter(a).mouseleave(b||a)}}),f.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error contextmenu".split(" "),function(a,b){f.fn[b]=function(a,c){c==null&&(c=a,a=null);return arguments.length>0?this.on(b,null,a,c):this.trigger(b)},f.attrFn&&(f.attrFn[b]=!0),C.test(b)&&(f.event.fixHooks[b]=f.event.keyHooks),D.test(b)&&(f.event.fixHooks[b]=f.event.mouseHooks)}),function(){function x(a,b,c,e,f,g){for(var h=0,i=e.length;h<i;h++){var j=e[h];if(j){var k=!1;j=j[a];while(j){if(j[d]===c){k=e[j.sizset];break}if(j.nodeType===1){g||(j[d]=c,j.sizset=h);if(typeof b!="string"){if(j===b){k=!0;break}}else if(m.filter(b,[j]).length>0){k=j;break}}j=j[a]}e[h]=k}}}function w(a,b,c,e,f,g){for(var h=0,i=e.length;h<i;h++){var j=e[h];if(j){var k=!1;j=j[a];while(j){if(j[d]===c){k=e[j.sizset];break}j.nodeType===1&&!g&&(j[d]=c,j.sizset=h);if(j.nodeName.toLowerCase()===b){k=j;break}j=j[a]}e[h]=k}}}var a=/((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^\[\]]*\]|['"][^'"]*['"]|[^\[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?((?:.|\r|\n)*)/g,d="sizcache"+(Math.random()+"").replace(".",""),e=0,g=Object.prototype.toString,h=!1,i=!0,j=/\\/g,k=/\r\n/g,l=/\W/;[0,0].sort(function(){i=!1;return 0});var m=function(b,d,e,f){e=e||[],d=d||c;var h=d;if(d.nodeType!==1&&d.nodeType!==9)return[];if(!b||typeof b!="string")return e;var i,j,k,l,n,q,r,t,u=!0,v=m.isXML(d),w=[],x=b;do{a.exec(""),i=a.exec(x);if(i){x=i[3],w.push(i[1]);if(i[2]){l=i[3];break}}}while(i);if(w.length>1&&p.exec(b))if(w.length===2&&o.relative[w[0]])j=y(w[0]+w[1],d,f);else{j=o.relative[w[0]]?[d]:m(w.shift(),d);while(w.length)b=w.shift(),o.relative[b]&&(b+=w.shift()),j=y(b,j,f)}else{!f&&w.length>1&&d.nodeType===9&&!v&&o.match.ID.test(w[0])&&!o.match.ID.test(w[w.length-1])&&(n=m.find(w.shift(),d,v),d=n.expr?m.filter(n.expr,n.set)[0]:n.set[0]);if(d){n=f?{expr:w.pop(),set:s(f)}:m.find(w.pop(),w.length===1&&(w[0]==="~"||w[0]==="+")&&d.parentNode?d.parentNode:d,v),j=n.expr?m.filter(n.expr,n.set):n.set,w.length>0?k=s(j):u=!1;while(w.length)q=w.pop(),r=q,o.relative[q]?r=w.pop():q="",r==null&&(r=d),o.relative[q](k,r,v)}else k=w=[]}k||(k=j),k||m.error(q||b);if(g.call(k)==="[object Array]")if(!u)e.push.apply(e,k);else if(d&&d.nodeType===1)for(t=0;k[t]!=null;t++)k[t]&&(k[t]===!0||k[t].nodeType===1&&m.contains(d,k[t]))&&e.push(j[t]);else for(t=0;k[t]!=null;t++)k[t]&&k[t].nodeType===1&&e.push(j[t]);else s(k,e);l&&(m(l,h,e,f),m.uniqueSort(e));return e};m.uniqueSort=function(a){if(u){h=i,a.sort(u);if(h)for(var b=1;b<a.length;b++)a[b]===a[b-1]&&a.splice(b--,1)}return a},m.matches=function(a,b){return m(a,null,null,b)},m.matchesSelector=function(a,b){return m(b,null,null,[a]).length>0},m.find=function(a,b,c){var d,e,f,g,h,i;if(!a)return[];for(e=0,f=o.order.length;e<f;e++){h=o.order[e];if(g=o.leftMatch[h].exec(a)){i=g[1],g.splice(1,1);if(i.substr(i.length-1)!=="\\"){g[1]=(g[1]||"").replace(j,""),d=o.find[h](g,b,c);if(d!=null){a=a.replace(o.match[h],"");break}}}}d||(d=typeof b.getElementsByTagName!="undefined"?b.getElementsByTagName("*"):[]);return{set:d,expr:a}},m.filter=function(a,c,d,e){var f,g,h,i,j,k,l,n,p,q=a,r=[],s=c,t=c&&c[0]&&m.isXML(c[0]);while(a&&c.length){for(h in o.filter)if((f=o.leftMatch[h].exec(a))!=null&&f[2]){k=o.filter[h],l=f[1],g=!1,f.splice(1,1);if(l.substr(l.length-1)==="\\")continue;s===r&&(r=[]);if(o.preFilter[h]){f=o.preFilter[h](f,s,d,r,e,t);if(!f)g=i=!0;else if(f===!0)continue}if(f)for(n=0;(j=s[n])!=null;n++)j&&(i=k(j,f,n,s),p=e^i,d&&i!=null?p?g=!0:s[n]=!1:p&&(r.push(j),g=!0));if(i!==b){d||(s=r),a=a.replace(o.match[h],"");if(!g)return[];break}}if(a===q)if(g==null)m.error(a);else break;q=a}return s},m.error=function(a){throw new Error("Syntax error, unrecognized expression: "+a)};var n=m.getText=function(a){var b,c,d=a.nodeType,e="";if(d){if(d===1||d===9){if(typeof a.textContent=="string")return a.textContent;if(typeof a.innerText=="string")return a.innerText.replace(k,"");for(a=a.firstChild;a;a=a.nextSibling)e+=n(a)}else if(d===3||d===4)return a.nodeValue}else for(b=0;c=a[b];b++)c.nodeType!==8&&(e+=n(c));return e},o=m.selectors={order:["ID","NAME","TAG"],match:{ID:/#((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,CLASS:/\.((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,NAME:/\[name=['"]*((?:[\w\u00c0-\uFFFF\-]|\\.)+)['"]*\]/,ATTR:/\[\s*((?:[\w\u00c0-\uFFFF\-]|\\.)+)\s*(?:(\S?=)\s*(?:(['"])(.*?)\3|(#?(?:[\w\u00c0-\uFFFF\-]|\\.)*)|)|)\s*\]/,TAG:/^((?:[\w\u00c0-\uFFFF\*\-]|\\.)+)/,CHILD:/:(only|nth|last|first)-child(?:\(\s*(even|odd|(?:[+\-]?\d+|(?:[+\-]?\d*)?n\s*(?:[+\-]\s*\d+)?))\s*\))?/,POS:/:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^\-]|$)/,PSEUDO:/:((?:[\w\u00c0-\uFFFF\-]|\\.)+)(?:\((['"]?)((?:\([^\)]+\)|[^\(\)]*)+)\2\))?/},leftMatch:{},attrMap:{"class":"className","for":"htmlFor"},attrHandle:{href:function(a){return a.getAttribute("href")},type:function(a){return a.getAttribute("type")}},relative:{"+":function(a,b){var c=typeof b=="string",d=c&&!l.test(b),e=c&&!d;d&&(b=b.toLowerCase());for(var f=0,g=a.length,h;f<g;f++)if(h=a[f]){while((h=h.previousSibling)&&h.nodeType!==1);a[f]=e||h&&h.nodeName.toLowerCase()===b?h||!1:h===b}e&&m.filter(b,a,!0)},">":function(a,b){var c,d=typeof b=="string",e=0,f=a.length;if(d&&!l.test(b)){b=b.toLowerCase();for(;e<f;e++){c=a[e];if(c){var g=c.parentNode;a[e]=g.nodeName.toLowerCase()===b?g:!1}}}else{for(;e<f;e++)c=a[e],c&&(a[e]=d?c.parentNode:c.parentNode===b);d&&m.filter(b,a,!0)}},"":function(a,b,c){var d,f=e++,g=x;typeof b=="string"&&!l.test(b)&&(b=b.toLowerCase(),d=b,g=w),g("parentNode",b,f,a,d,c)},"~":function(a,b,c){var d,f=e++,g=x;typeof b=="string"&&!l.test(b)&&(b=b.toLowerCase(),d=b,g=w),g("previousSibling",b,f,a,d,c)}},find:{ID:function(a,b,c){if(typeof b.getElementById!="undefined"&&!c){var d=b.getElementById(a[1]);return d&&d.parentNode?[d]:[]}},NAME:function(a,b){if(typeof b.getElementsByName!="undefined"){var c=[],d=b.getElementsByName(a[1]);for(var e=0,f=d.length;e<f;e++)d[e].getAttribute("name")===a[1]&&c.push(d[e]);return c.length===0?null:c}},TAG:function(a,b){if(typeof b.getElementsByTagName!="undefined")return b.getElementsByTagName(a[1])}},preFilter:{CLASS:function(a,b,c,d,e,f){a=" "+a[1].replace(j,"")+" ";if(f)return a;for(var g=0,h;(h=b[g])!=null;g++)h&&(e^(h.className&&(" "+h.className+" ").replace(/[\t\n\r]/g," ").indexOf(a)>=0)?c||d.push(h):c&&(b[g]=!1));return!1},ID:function(a){return a[1].replace(j,"")},TAG:function(a,b){return a[1].replace(j,"").toLowerCase()},CHILD:function(a){if(a[1]==="nth"){a[2]||m.error(a[0]),a[2]=a[2].replace(/^\+|\s*/g,"");var b=/(-?)(\d*)(?:n([+\-]?\d*))?/.exec(a[2]==="even"&&"2n"||a[2]==="odd"&&"2n+1"||!/\D/.test(a[2])&&"0n+"+a[2]||a[2]);a[2]=b[1]+(b[2]||1)-0,a[3]=b[3]-0}else a[2]&&m.error(a[0]);a[0]=e++;return a},ATTR:function(a,b,c,d,e,f){var g=a[1]=a[1].replace(j,"");!f&&o.attrMap[g]&&(a[1]=o.attrMap[g]),a[4]=(a[4]||a[5]||"").replace(j,""),a[2]==="~="&&(a[4]=" "+a[4]+" ");return a},PSEUDO:function(b,c,d,e,f){if(b[1]==="not")if((a.exec(b[3])||"").length>1||/^\w/.test(b[3]))b[3]=m(b[3],null,null,c);else{var g=m.filter(b[3],c,d,!0^f);d||e.push.apply(e,g);return!1}else if(o.match.POS.test(b[0])||o.match.CHILD.test(b[0]))return!0;return b},POS:function(a){a.unshift(!0);return a}},filters:{enabled:function(a){return a.disabled===!1&&a.type!=="hidden"},disabled:function(a){return a.disabled===!0},checked:function(a){return a.checked===!0},selected:function(a){a.parentNode&&a.parentNode.selectedIndex;return a.selected===!0},parent:function(a){return!!a.firstChild},empty:function(a){return!a.firstChild},has:function(a,b,c){return!!m(c[3],a).length},header:function(a){return/h\d/i.test(a.nodeName)},text:function(a){var b=a.getAttribute("type"),c=a.type;return a.nodeName.toLowerCase()==="input"&&"text"===c&&(b===c||b===null)},radio:function(a){return a.nodeName.toLowerCase()==="input"&&"radio"===a.type},checkbox:function(a){return a.nodeName.toLowerCase()==="input"&&"checkbox"===a.type},file:function(a){return a.nodeName.toLowerCase()==="input"&&"file"===a.type},password:function(a){return a.nodeName.toLowerCase()==="input"&&"password"===a.type},submit:function(a){var b=a.nodeName.toLowerCase();return(b==="input"||b==="button")&&"submit"===a.type},image:function(a){return a.nodeName.toLowerCase()==="input"&&"image"===a.type},reset:function(a){var b=a.nodeName.toLowerCase();return(b==="input"||b==="button")&&"reset"===a.type},button:function(a){var b=a.nodeName.toLowerCase();return b==="input"&&"button"===a.type||b==="button"},input:function(a){return/input|select|textarea|button/i.test(a.nodeName)},focus:function(a){return a===a.ownerDocument.activeElement}},setFilters:{first:function(a,b){return b===0},last:function(a,b,c,d){return b===d.length-1},even:function(a,b){return b%2===0},odd:function(a,b){return b%2===1},lt:function(a,b,c){return b<c[3]-0},gt:function(a,b,c){return b>c[3]-0},nth:function(a,b,c){return c[3]-0===b},eq:function(a,b,c){return c[3]-0===b}},filter:{PSEUDO:function(a,b,c,d){var e=b[1],f=o.filters[e];if(f)return f(a,c,b,d);if(e==="contains")return(a.textContent||a.innerText||n([a])||"").indexOf(b[3])>=0;if(e==="not"){var g=b[3];for(var h=0,i=g.length;h<i;h++)if(g[h]===a)return!1;return!0}m.error(e)},CHILD:function(a,b){var c,e,f,g,h,i,j,k=b[1],l=a;switch(k){case"only":case"first":while(l=l.previousSibling)if(l.nodeType===1)return!1;if(k==="first")return!0;l=a;case"last":while(l=l.nextSibling)if(l.nodeType===1)return!1;return!0;case"nth":c=b[2],e=b[3];if(c===1&&e===0)return!0;f=b[0],g=a.parentNode;if(g&&(g[d]!==f||!a.nodeIndex)){i=0;for(l=g.firstChild;l;l=l.nextSibling)l.nodeType===1&&(l.nodeIndex=++i);g[d]=f}j=a.nodeIndex-e;return c===0?j===0:j%c===0&&j/c>=0}},ID:function(a,b){return a.nodeType===1&&a.getAttribute("id")===b},TAG:function(a,b){return b==="*"&&a.nodeType===1||!!a.nodeName&&a.nodeName.toLowerCase()===b},CLASS:function(a,b){return(" "+(a.className||a.getAttribute("class"))+" ").indexOf(b)>-1},ATTR:function(a,b){var c=b[1],d=m.attr?m.attr(a,c):o.attrHandle[c]?o.attrHandle[c](a):a[c]!=null?a[c]:a.getAttribute(c),e=d+"",f=b[2],g=b[4];return d==null?f==="!=":!f&&m.attr?d!=null:f==="="?e===g:f==="*="?e.indexOf(g)>=0:f==="~="?(" "+e+" ").indexOf(g)>=0:g?f==="!="?e!==g:f==="^="?e.indexOf(g)===0:f==="$="?e.substr(e.length-g.length)===g:f==="|="?e===g||e.substr(0,g.length+1)===g+"-":!1:e&&d!==!1},POS:function(a,b,c,d){var e=b[2],f=o.setFilters[e];if(f)return f(a,c,b,d)}}},p=o.match.POS,q=function(a,b){return"\\"+(b-0+1)};for(var r in o.match)o.match[r]=new RegExp(o.match[r].source+/(?![^\[]*\])(?![^\(]*\))/.source),o.leftMatch[r]=new RegExp(/(^(?:.|\r|\n)*?)/.source+o.match[r].source.replace(/\\(\d+)/g,q));var s=function(a,b){a=Array.prototype.slice.call(a,0);if(b){b.push.apply(b,a);return b}return a};try{Array.prototype.slice.call(c.documentElement.childNodes,0)[0].nodeType}catch(t){s=function(a,b){var c=0,d=b||[];if(g.call(a)==="[object Array]")Array.prototype.push.apply(d,a);else if(typeof a.length=="number")for(var e=a.length;c<e;c++)d.push(a[c]);else for(;a[c];c++)d.push(a[c]);return d}}var u,v;c.documentElement.compareDocumentPosition?u=function(a,b){if(a===b){h=!0;return 0}if(!a.compareDocumentPosition||!b.compareDocumentPosition)return a.compareDocumentPosition?-1:1;return a.compareDocumentPosition(b)&4?-1:1}:(u=function(a,b){if(a===b){h=!0;return 0}if(a.sourceIndex&&b.sourceIndex)return a.sourceIndex-b.sourceIndex;var c,d,e=[],f=[],g=a.parentNode,i=b.parentNode,j=g;if(g===i)return v(a,b);if(!g)return-1;if(!i)return 1;while(j)e.unshift(j),j=j.parentNode;j=i;while(j)f.unshift(j),j=j.parentNode;c=e.length,d=f.length;for(var k=0;k<c&&k<d;k++)if(e[k]!==f[k])return v(e[k],f[k]);return k===c?v(a,f[k],-1):v(e[k],b,1)},v=function(a,b,c){if(a===b)return c;var d=a.nextSibling;while(d){if(d===b)return-1;d=d.nextSibling}return 1}),function(){var a=c.createElement("div"),d="script"+(new Date).getTime(),e=c.documentElement;a.innerHTML="<a name='"+d+"'/>",e.insertBefore(a,e.firstChild),c.getElementById(d)&&(o.find.ID=function(a,c,d){if(typeof c.getElementById!="undefined"&&!d){var e=c.getElementById(a[1]);return e?e.id===a[1]||typeof e.getAttributeNode!="undefined"&&e.getAttributeNode("id").nodeValue===a[1]?[e]:b:[]}},o.filter.ID=function(a,b){var c=typeof a.getAttributeNode!="undefined"&&a.getAttributeNode("id");return a.nodeType===1&&c&&c.nodeValue===b}),e.removeChild(a),e=a=null}(),function(){var a=c.createElement("div");a.appendChild(c.createComment("")),a.getElementsByTagName("*").length>0&&(o.find.TAG=function(a,b){var c=b.getElementsByTagName(a[1]);if(a[1]==="*"){var d=[];for(var e=0;c[e];e++)c[e].nodeType===1&&d.push(c[e]);c=d}return c}),a.innerHTML="<a href='#'></a>",a.firstChild&&typeof a.firstChild.getAttribute!="undefined"&&a.firstChild.getAttribute("href")!=="#"&&(o.attrHandle.href=function(a){return a.getAttribute("href",2)}),a=null}(),c.querySelectorAll&&function(){var a=m,b=c.createElement("div"),d="__sizzle__";b.innerHTML="<p class='TEST'></p>";if(!b.querySelectorAll||b.querySelectorAll(".TEST").length!==0){m=function(b,e,f,g){e=e||c;if(!g&&!m.isXML(e)){var h=/^(\w+$)|^\.([\w\-]+$)|^#([\w\-]+$)/.exec(b);if(h&&(e.nodeType===1||e.nodeType===9)){if(h[1])return s(e.getElementsByTagName(b),f);if(h[2]&&o.find.CLASS&&e.getElementsByClassName)return s(e.getElementsByClassName(h[2]),f)}if(e.nodeType===9){if(b==="body"&&e.body)return s([e.body],f);if(h&&h[3]){var i=e.getElementById(h[3]);if(!i||!i.parentNode)return s([],f);if(i.id===h[3])return s([i],f)}try{return s(e.querySelectorAll(b),f)}catch(j){}}else if(e.nodeType===1&&e.nodeName.toLowerCase()!=="object"){var k=e,l=e.getAttribute("id"),n=l||d,p=e.parentNode,q=/^\s*[+~]/.test(b);l?n=n.replace(/'/g,"\\$&"):e.setAttribute("id",n),q&&p&&(e=e.parentNode);try{if(!q||p)return s(e.querySelectorAll("[id='"+n+"'] "+b),f)}catch(r){}finally{l||k.removeAttribute("id")}}}return a(b,e,f,g)};for(var e in a)m[e]=a[e];b=null}}(),function(){var a=c.documentElement,b=a.matchesSelector||a.mozMatchesSelector||a.webkitMatchesSelector||a.msMatchesSelector;if(b){var d=!b.call(c.createElement("div"),"div"),e=!1;try{b.call(c.documentElement,"[test!='']:sizzle")}catch(f){e=!0}m.matchesSelector=function(a,c){c=c.replace(/\=\s*([^'"\]]*)\s*\]/g,"='$1']");if(!m.isXML(a))try{if(e||!o.match.PSEUDO.test(c)&&!/!=/.test(c)){var f=b.call(a,c);if(f||!d||a.document&&a.document.nodeType!==11)return f}}catch(g){}return m(c,null,null,[a]).length>0}}}(),function(){var a=c.createElement("div");a.innerHTML="<div class='test e'></div><div class='test'></div>";if(!!a.getElementsByClassName&&a.getElementsByClassName("e").length!==0){a.lastChild.className="e";if(a.getElementsByClassName("e").length===1)return;o.order.splice(1,0,"CLASS"),o.find.CLASS=function(a,b,c){if(typeof b.getElementsByClassName!="undefined"&&!c)return b.getElementsByClassName(a[1])},a=null}}(),c.documentElement.contains?m.contains=function(a,b){return a!==b&&(a.contains?a.contains(b):!0)}:c.documentElement.compareDocumentPosition?m.contains=function(a,b){return!!(a.compareDocumentPosition(b)&16)}:m.contains=function(){return!1},m.isXML=function(a){var b=(a?a.ownerDocument||a:0).documentElement;return b?b.nodeName!=="HTML":!1};var y=function(a,b,c){var d,e=[],f="",g=b.nodeType?[b]:b;while(d=o.match.PSEUDO.exec(a))f+=d[0],a=a.replace(o.match.PSEUDO,"");a=o.relative[a]?a+"*":a;for(var h=0,i=g.length;h<i;h++)m(a,g[h],e,c);return m.filter(f,e)};m.attr=f.attr,m.selectors.attrMap={},f.find=m,f.expr=m.selectors,f.expr[":"]=f.expr.filters,f.unique=m.uniqueSort,f.text=m.getText,f.isXMLDoc=m.isXML,f.contains=m.contains}();var L=/Until$/,M=/^(?:parents|prevUntil|prevAll)/,N=/,/,O=/^.[^:#\[\.,]*$/,P=Array.prototype.slice,Q=f.expr.match.POS,R={children:!0,contents:!0,next:!0,prev:!0};f.fn.extend({find:function(a){var b=this,c,d;if(typeof a!="string")return f(a).filter(function(){for(c=0,d=b.length;c<d;c++)if(f.contains(b[c],this))return!0});var e=this.pushStack("","find",a),g,h,i;for(c=0,d=this.length;c<d;c++){g=e.length,f.find(a,this[c],e);if(c>0)for(h=g;h<e.length;h++)for(i=0;i<g;i++)if(e[i]===e[h]){e.splice(h--,1);break}}return e},has:function(a){var b=f(a);return this.filter(function(){for(var a=0,c=b.length;a<c;a++)if(f.contains(this,b[a]))return!0})},not:function(a){return this.pushStack(T(this,a,!1),"not",a)},filter:function(a){return this.pushStack(T(this,a,!0),"filter",a)},is:function(a){return!!a&&(typeof a=="string"?Q.test(a)?f(a,this.context).index(this[0])>=0:f.filter(a,this).length>0:this.filter(a).length>0)},closest:function(a,b){var c=[],d,e,g=this[0];if(f.isArray(a)){var h=1;while(g&&g.ownerDocument&&g!==b){for(d=0;d<a.length;d++)f(g).is(a[d])&&c.push({selector:a[d],elem:g,level:h});g=g.parentNode,h++}return c}var i=Q.test(a)||typeof a!="string"?f(a,b||this.context):0;for(d=0,e=this.length;d<e;d++){g=this[d];while(g){if(i?i.index(g)>-1:f.find.matchesSelector(g,a)){c.push(g);break}g=g.parentNode;if(!g||!g.ownerDocument||g===b||g.nodeType===11)break}}c=c.length>1?f.unique(c):c;return this.pushStack(c,"closest",a)},index:function(a){if(!a)return this[0]&&this[0].parentNode?this.prevAll().length:-1;if(typeof a=="string")return f.inArray(this[0],f(a));return f.inArray(a.jquery?a[0]:a,this)},add:function(a,b){var c=typeof a=="string"?f(a,b):f.makeArray(a&&a.nodeType?[a]:a),d=f.merge(this.get(),c);return this.pushStack(S(c[0])||S(d[0])?d:f.unique(d))},andSelf:function(){return this.add(this.prevObject)}}),f.each({parent:function(a){var b=a.parentNode;return b&&b.nodeType!==11?b:null},parents:function(a){return f.dir(a,"parentNode")},parentsUntil:function(a,b,c){return f.dir(a,"parentNode",c)},next:function(a){return f.nth(a,2,"nextSibling")},prev:function(a){return f.nth(a,2,"previousSibling")},nextAll:function(a){return f.dir(a,"nextSibling")},prevAll:function(a){return f.dir(a,"previousSibling")},nextUntil:function(a,b,c){return f.dir(a,"nextSibling",c)},prevUntil:function(a,b,c){return f.dir(a,"previousSibling",c)},siblings:function(a){return f.sibling(a.parentNode.firstChild,a)},children:function(a){return f.sibling(a.firstChild)},contents:function(a){return f.nodeName(a,"iframe")?a.contentDocument||a.contentWindow.document:f.makeArray(a.childNodes)}},function(a,b){f.fn[a]=function(c,d){var e=f.map(this,b,c);L.test(a)||(d=c),d&&typeof d=="string"&&(e=f.filter(d,e)),e=this.length>1&&!R[a]?f.unique(e):e,(this.length>1||N.test(d))&&M.test(a)&&(e=e.reverse());return this.pushStack(e,a,P.call(arguments).join(","))}}),f.extend({filter:function(a,b,c){c&&(a=":not("+a+")");return b.length===1?f.find.matchesSelector(b[0],a)?[b[0]]:[]:f.find.matches(a,b)},dir:function(a,c,d){var e=[],g=a[c];while(g&&g.nodeType!==9&&(d===b||g.nodeType!==1||!f(g).is(d)))g.nodeType===1&&e.push(g),g=g[c];return e},nth:function(a,b,c,d){b=b||1;var e=0;for(;a;a=a[c])if(a.nodeType===1&&++e===b)break;return a},sibling:function(a,b){var c=[];for(;a;a=a.nextSibling)a.nodeType===1&&a!==b&&c.push(a);return c}});var V="abbr|article|aside|audio|canvas|datalist|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video",W=/ jQuery\d+="(?:\d+|null)"/g,X=/^\s+/,Y=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/ig,Z=/<([\w:]+)/,$=/<tbody/i,_=/<|&#?\w+;/,ba=/<(?:script|style)/i,bb=/<(?:script|object|embed|option|style)/i,bc=new RegExp("<(?:"+V+")","i"),bd=/checked\s*(?:[^=]|=\s*.checked.)/i,be=/\/(java|ecma)script/i,bf=/^\s*<!(?:\[CDATA\[|\-\-)/,bg={option:[1,"<select multiple='multiple'>","</select>"],legend:[1,"<fieldset>","</fieldset>"],thead:[1,"<table>","</table>"],tr:[2,"<table><tbody>","</tbody></table>"],td:[3,"<table><tbody><tr>","</tr></tbody></table>"],col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],area:[1,"<map>","</map>"],_default:[0,"",""]},bh=U(c);bg.optgroup=bg.option,bg.tbody=bg.tfoot=bg.colgroup=bg.caption=bg.thead,bg.th=bg.td,f.support.htmlSerialize||(bg._default=[1,"div<div>","</div>"]),f.fn.extend({text:function(a){if(f.isFunction(a))return this.each(function(b){var c=f(this);c.text(a.call(this,b,c.text()))});if(typeof a!="object"&&a!==b)return this.empty().append((this[0]&&this[0].ownerDocument||c).createTextNode(a));return f.text(this)},wrapAll:function(a){if(f.isFunction(a))return this.each(function(b){f(this).wrapAll(a.call(this,b))});if(this[0]){var b=f(a,this[0].ownerDocument).eq(0).clone(!0);this[0].parentNode&&b.insertBefore(this[0]),b.map(function(){var a=this;while(a.firstChild&&a.firstChild.nodeType===1)a=a.firstChild;return a}).append(this)}return this},wrapInner:function(a){if(f.isFunction(a))return this.each(function(b){f(this).wrapInner(a.call(this,b))});return this.each(function(){var b=f(this),c=b.contents();c.length?c.wrapAll(a):b.append(a)})},wrap:function(a){var b=f.isFunction(a);return this.each(function(c){f(this).wrapAll(b?a.call(this,c):a)})},unwrap:function(){return this.parent().each(function(){f.nodeName(this,"body")||f(this).replaceWith(this.childNodes)}).end()},append:function(){return this.domManip(arguments,!0,function(a){this.nodeType===1&&this.appendChild(a)})},prepend:function(){return this.domManip(arguments,!0,function(a){this.nodeType===1&&this.insertBefore(a,this.firstChild)})},before:function(){if(this[0]&&this[0].parentNode)return this.domManip(arguments,!1,function(a){this.parentNode.insertBefore(a,this)});if(arguments.length){var a=f.clean(arguments);a.push.apply(a,this.toArray());return this.pushStack(a,"before",arguments)}},after:function(){if(this[0]&&this[0].parentNode)return this.domManip(arguments,!1,function(a){this.parentNode.insertBefore(a,this.nextSibling)});if(arguments.length){var a=this.pushStack(this,"after",arguments);a.push.apply(a,f.clean(arguments));return a}},remove:function(a,b){for(var c=0,d;(d=this[c])!=null;c++)if(!a||f.filter(a,[d]).length)!b&&d.nodeType===1&&(f.cleanData(d.getElementsByTagName("*")),f.cleanData([d])),d.parentNode&&d.parentNode.removeChild(d);return this},empty:function()
        {for(var a=0,b;(b=this[a])!=null;a++){b.nodeType===1&&f.cleanData(b.getElementsByTagName("*"));while(b.firstChild)b.removeChild(b.firstChild)}return this},clone:function(a,b){a=a==null?!1:a,b=b==null?a:b;return this.map(function(){return f.clone(this,a,b)})},html:function(a){if(a===b)return this[0]&&this[0].nodeType===1?this[0].innerHTML.replace(W,""):null;if(typeof a=="string"&&!ba.test(a)&&(f.support.leadingWhitespace||!X.test(a))&&!bg[(Z.exec(a)||["",""])[1].toLowerCase()]){a=a.replace(Y,"<$1></$2>");try{for(var c=0,d=this.length;c<d;c++)this[c].nodeType===1&&(f.cleanData(this[c].getElementsByTagName("*")),this[c].innerHTML=a)}catch(e){this.empty().append(a)}}else f.isFunction(a)?this.each(function(b){var c=f(this);c.html(a.call(this,b,c.html()))}):this.empty().append(a);return this},replaceWith:function(a){if(this[0]&&this[0].parentNode){if(f.isFunction(a))return this.each(function(b){var c=f(this),d=c.html();c.replaceWith(a.call(this,b,d))});typeof a!="string"&&(a=f(a).detach());return this.each(function(){var b=this.nextSibling,c=this.parentNode;f(this).remove(),b?f(b).before(a):f(c).append(a)})}return this.length?this.pushStack(f(f.isFunction(a)?a():a),"replaceWith",a):this},detach:function(a){return this.remove(a,!0)},domManip:function(a,c,d){var e,g,h,i,j=a[0],k=[];if(!f.support.checkClone&&arguments.length===3&&typeof j=="string"&&bd.test(j))return this.each(function(){f(this).domManip(a,c,d,!0)});if(f.isFunction(j))return this.each(function(e){var g=f(this);a[0]=j.call(this,e,c?g.html():b),g.domManip(a,c,d)});if(this[0]){i=j&&j.parentNode,f.support.parentNode&&i&&i.nodeType===11&&i.childNodes.length===this.length?e={fragment:i}:e=f.buildFragment(a,this,k),h=e.fragment,h.childNodes.length===1?g=h=h.firstChild:g=h.firstChild;if(g){c=c&&f.nodeName(g,"tr");for(var l=0,m=this.length,n=m-1;l<m;l++)d.call(c?bi(this[l],g):this[l],e.cacheable||m>1&&l<n?f.clone(h,!0,!0):h)}k.length&&f.each(k,bp)}return this}}),f.buildFragment=function(a,b,d){var e,g,h,i,j=a[0];b&&b[0]&&(i=b[0].ownerDocument||b[0]),i.createDocumentFragment||(i=c),a.length===1&&typeof j=="string"&&j.length<512&&i===c&&j.charAt(0)==="<"&&!bb.test(j)&&(f.support.checkClone||!bd.test(j))&&(f.support.html5Clone||!bc.test(j))&&(g=!0,h=f.fragments[j],h&&h!==1&&(e=h)),e||(e=i.createDocumentFragment(),f.clean(a,i,e,d)),g&&(f.fragments[j]=h?e:1);return{fragment:e,cacheable:g}},f.fragments={},f.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(a,b){f.fn[a]=function(c){var d=[],e=f(c),g=this.length===1&&this[0].parentNode;if(g&&g.nodeType===11&&g.childNodes.length===1&&e.length===1){e[b](this[0]);return this}for(var h=0,i=e.length;h<i;h++){var j=(h>0?this.clone(!0):this).get();f(e[h])[b](j),d=d.concat(j)}return this.pushStack(d,a,e.selector)}}),f.extend({clone:function(a,b,c){var d,e,g,h=f.support.html5Clone||!bc.test("<"+a.nodeName)?a.cloneNode(!0):bo(a);if((!f.support.noCloneEvent||!f.support.noCloneChecked)&&(a.nodeType===1||a.nodeType===11)&&!f.isXMLDoc(a)){bk(a,h),d=bl(a),e=bl(h);for(g=0;d[g];++g)e[g]&&bk(d[g],e[g])}if(b){bj(a,h);if(c){d=bl(a),e=bl(h);for(g=0;d[g];++g)bj(d[g],e[g])}}d=e=null;return h},clean:function(a,b,d,e){var g;b=b||c,typeof b.createElement=="undefined"&&(b=b.ownerDocument||b[0]&&b[0].ownerDocument||c);var h=[],i;for(var j=0,k;(k=a[j])!=null;j++){typeof k=="number"&&(k+="");if(!k)continue;if(typeof k=="string")if(!_.test(k))k=b.createTextNode(k);else{k=k.replace(Y,"<$1></$2>");var l=(Z.exec(k)||["",""])[1].toLowerCase(),m=bg[l]||bg._default,n=m[0],o=b.createElement("div");b===c?bh.appendChild(o):U(b).appendChild(o),o.innerHTML=m[1]+k+m[2];while(n--)o=o.lastChild;if(!f.support.tbody){var p=$.test(k),q=l==="table"&&!p?o.firstChild&&o.firstChild.childNodes:m[1]==="<table>"&&!p?o.childNodes:[];for(i=q.length-1;i>=0;--i)f.nodeName(q[i],"tbody")&&!q[i].childNodes.length&&q[i].parentNode.removeChild(q[i])}!f.support.leadingWhitespace&&X.test(k)&&o.insertBefore(b.createTextNode(X.exec(k)[0]),o.firstChild),k=o.childNodes}var r;if(!f.support.appendChecked)if(k[0]&&typeof (r=k.length)=="number")for(i=0;i<r;i++)bn(k[i]);else bn(k);k.nodeType?h.push(k):h=f.merge(h,k)}if(d){g=function(a){return!a.type||be.test(a.type)};for(j=0;h[j];j++)if(e&&f.nodeName(h[j],"script")&&(!h[j].type||h[j].type.toLowerCase()==="text/javascript"))e.push(h[j].parentNode?h[j].parentNode.removeChild(h[j]):h[j]);else{if(h[j].nodeType===1){var s=f.grep(h[j].getElementsByTagName("script"),g);h.splice.apply(h,[j+1,0].concat(s))}d.appendChild(h[j])}}return h},cleanData:function(a){var b,c,d=f.cache,e=f.event.special,g=f.support.deleteExpando;for(var h=0,i;(i=a[h])!=null;h++){if(i.nodeName&&f.noData[i.nodeName.toLowerCase()])continue;c=i[f.expando];if(c){b=d[c];if(b&&b.events){for(var j in b.events)e[j]?f.event.remove(i,j):f.removeEvent(i,j,b.handle);b.handle&&(b.handle.elem=null)}g?delete i[f.expando]:i.removeAttribute&&i.removeAttribute(f.expando),delete d[c]}}}});var bq=/alpha\([^)]*\)/i,br=/opacity=([^)]*)/,bs=/([A-Z]|^ms)/g,bt=/^-?\d+(?:px)?$/i,bu=/^-?\d/,bv=/^([\-+])=([\-+.\de]+)/,bw={position:"absolute",visibility:"hidden",display:"block"},bx=["Left","Right"],by=["Top","Bottom"],bz,bA,bB;f.fn.css=function(a,c){if(arguments.length===2&&c===b)return this;return f.access(this,a,c,!0,function(a,c,d){return d!==b?f.style(a,c,d):f.css(a,c)})},f.extend({cssHooks:{opacity:{get:function(a,b){if(b){var c=bz(a,"opacity","opacity");return c===""?"1":c}return a.style.opacity}}},cssNumber:{fillOpacity:!0,fontWeight:!0,lineHeight:!0,opacity:!0,orphans:!0,widows:!0,zIndex:!0,zoom:!0},cssProps:{"float":f.support.cssFloat?"cssFloat":"styleFloat"},style:function(a,c,d,e){if(!!a&&a.nodeType!==3&&a.nodeType!==8&&!!a.style){var g,h,i=f.camelCase(c),j=a.style,k=f.cssHooks[i];c=f.cssProps[i]||i;if(d===b){if(k&&"get"in k&&(g=k.get(a,!1,e))!==b)return g;return j[c]}h=typeof d,h==="string"&&(g=bv.exec(d))&&(d=+(g[1]+1)*+g[2]+parseFloat(f.css(a,c)),h="number");if(d==null||h==="number"&&isNaN(d))return;h==="number"&&!f.cssNumber[i]&&(d+="px");if(!k||!("set"in k)||(d=k.set(a,d))!==b)try{j[c]=d}catch(l){}}},css:function(a,c,d){var e,g;c=f.camelCase(c),g=f.cssHooks[c],c=f.cssProps[c]||c,c==="cssFloat"&&(c="float");if(g&&"get"in g&&(e=g.get(a,!0,d))!==b)return e;if(bz)return bz(a,c)},swap:function(a,b,c){var d={};for(var e in b)d[e]=a.style[e],a.style[e]=b[e];c.call(a);for(e in b)a.style[e]=d[e]}}),f.curCSS=f.css,f.each(["height","width"],function(a,b){f.cssHooks[b]={get:function(a,c,d){var e;if(c){if(a.offsetWidth!==0)return bC(a,b,d);f.swap(a,bw,function(){e=bC(a,b,d)});return e}},set:function(a,b){if(!bt.test(b))return b;b=parseFloat(b);if(b>=0)return b+"px"}}}),f.support.opacity||(f.cssHooks.opacity={get:function(a,b){return br.test((b&&a.currentStyle?a.currentStyle.filter:a.style.filter)||"")?parseFloat(RegExp.$1)/100+"":b?"1":""},set:function(a,b){var c=a.style,d=a.currentStyle,e=f.isNumeric(b)?"alpha(opacity="+b*100+")":"",g=d&&d.filter||c.filter||"";c.zoom=1;if(b>=1&&f.trim(g.replace(bq,""))===""){c.removeAttribute("filter");if(d&&!d.filter)return}c.filter=bq.test(g)?g.replace(bq,e):g+" "+e}}),f(function(){f.support.reliableMarginRight||(f.cssHooks.marginRight={get:function(a,b){var c;f.swap(a,{display:"inline-block"},function(){b?c=bz(a,"margin-right","marginRight"):c=a.style.marginRight});return c}})}),c.defaultView&&c.defaultView.getComputedStyle&&(bA=function(a,b){var c,d,e;b=b.replace(bs,"-$1").toLowerCase(),(d=a.ownerDocument.defaultView)&&(e=d.getComputedStyle(a,null))&&(c=e.getPropertyValue(b),c===""&&!f.contains(a.ownerDocument.documentElement,a)&&(c=f.style(a,b)));return c}),c.documentElement.currentStyle&&(bB=function(a,b){var c,d,e,f=a.currentStyle&&a.currentStyle[b],g=a.style;f===null&&g&&(e=g[b])&&(f=e),!bt.test(f)&&bu.test(f)&&(c=g.left,d=a.runtimeStyle&&a.runtimeStyle.left,d&&(a.runtimeStyle.left=a.currentStyle.left),g.left=b==="fontSize"?"1em":f||0,f=g.pixelLeft+"px",g.left=c,d&&(a.runtimeStyle.left=d));return f===""?"auto":f}),bz=bA||bB,f.expr&&f.expr.filters&&(f.expr.filters.hidden=function(a){var b=a.offsetWidth,c=a.offsetHeight;return b===0&&c===0||!f.support.reliableHiddenOffsets&&(a.style&&a.style.display||f.css(a,"display"))==="none"},f.expr.filters.visible=function(a){return!f.expr.filters.hidden(a)});var bD=/%20/g,bE=/\[\]$/,bF=/\r?\n/g,bG=/#.*$/,bH=/^(.*?):[ \t]*([^\r\n]*)\r?$/mg,bI=/^(?:color|date|datetime|datetime-local|email|hidden|month|number|password|range|search|tel|text|time|url|week)$/i,bJ=/^(?:about|app|app\-storage|.+\-extension|file|res|widget):$/,bK=/^(?:GET|HEAD)$/,bL=/^\/\//,bM=/\?/,bN=/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,bO=/^(?:select|textarea)/i,bP=/\s+/,bQ=/([?&])_=[^&]*/,bR=/^([\w\+\.\-]+:)(?:\/\/([^\/?#:]*)(?::(\d+))?)?/,bS=f.fn.load,bT={},bU={},bV,bW,bX=["*/"]+["*"];try{bV=e.href}catch(bY){bV=c.createElement("a"),bV.href="",bV=bV.href}bW=bR.exec(bV.toLowerCase())||[],f.fn.extend({load:function(a,c,d){if(typeof a!="string"&&bS)return bS.apply(this,arguments);if(!this.length)return this;var e=a.indexOf(" ");if(e>=0){var g=a.slice(e,a.length);a=a.slice(0,e)}var h="GET";c&&(f.isFunction(c)?(d=c,c=b):typeof c=="object"&&(c=f.param(c,f.ajaxSettings.traditional),h="POST"));var i=this;f.ajax({url:a,type:h,dataType:"html",data:c,complete:function(a,b,c){c=a.responseText,a.isResolved()&&(a.done(function(a){c=a}),i.html(g?f("<div>").append(c.replace(bN,"")).find(g):c)),d&&i.each(d,[c,b,a])}});return this},serialize:function(){return f.param(this.serializeArray())},serializeArray:function(){return this.map(function(){return this.elements?f.makeArray(this.elements):this}).filter(function(){return this.name&&!this.disabled&&(this.checked||bO.test(this.nodeName)||bI.test(this.type))}).map(function(a,b){var c=f(this).val();return c==null?null:f.isArray(c)?f.map(c,function(a,c){return{name:b.name,value:a.replace(bF,"\r\n")}}):{name:b.name,value:c.replace(bF,"\r\n")}}).get()}}),f.each("ajaxStart ajaxStop ajaxComplete ajaxError ajaxSuccess ajaxSend".split(" "),function(a,b){f.fn[b]=function(a){return this.on(b,a)}}),f.each(["get","post"],function(a,c){f[c]=function(a,d,e,g){f.isFunction(d)&&(g=g||e,e=d,d=b);return f.ajax({type:c,url:a,data:d,success:e,dataType:g})}}),f.extend({getScript:function(a,c){return f.get(a,b,c,"script")},getJSON:function(a,b,c){return f.get(a,b,c,"json")},ajaxSetup:function(a,b){b?b_(a,f.ajaxSettings):(b=a,a=f.ajaxSettings),b_(a,b);return a},ajaxSettings:{url:bV,isLocal:bJ.test(bW[1]),global:!0,type:"GET",contentType:"application/x-www-form-urlencoded",processData:!0,async:!0,accepts:{xml:"application/xml, text/xml",html:"text/html",text:"text/plain",json:"application/json, text/javascript","*":bX},contents:{xml:/xml/,html:/html/,json:/json/},responseFields:{xml:"responseXML",text:"responseText"},converters:{"* text":a.String,"text html":!0,"text json":f.parseJSON,"text xml":f.parseXML},flatOptions:{context:!0,url:!0}},ajaxPrefilter:bZ(bT),ajaxTransport:bZ(bU),ajax:function(a,c){function w(a,c,l,m){if(s!==2){s=2,q&&clearTimeout(q),p=b,n=m||"",v.readyState=a>0?4:0;var o,r,u,w=c,x=l?cb(d,v,l):b,y,z;if(a>=200&&a<300||a===304){if(d.ifModified){if(y=v.getResponseHeader("Last-Modified"))f.lastModified[k]=y;if(z=v.getResponseHeader("Etag"))f.etag[k]=z}if(a===304)w="notmodified",o=!0;else try{r=cc(d,x),w="success",o=!0}catch(A){w="parsererror",u=A}}else{u=w;if(!w||a)w="error",a<0&&(a=0)}v.status=a,v.statusText=""+(c||w),o?h.resolveWith(e,[r,w,v]):h.rejectWith(e,[v,w,u]),v.statusCode(j),j=b,t&&g.trigger("ajax"+(o?"Success":"Error"),[v,d,o?r:u]),i.fireWith(e,[v,w]),t&&(g.trigger("ajaxComplete",[v,d]),--f.active||f.event.trigger("ajaxStop"))}}typeof a=="object"&&(c=a,a=b),c=c||{};var d=f.ajaxSetup({},c),e=d.context||d,g=e!==d&&(e.nodeType||e instanceof f)?f(e):f.event,h=f.Deferred(),i=f.Callbacks("once memory"),j=d.statusCode||{},k,l={},m={},n,o,p,q,r,s=0,t,u,v={readyState:0,setRequestHeader:function(a,b){if(!s){var c=a.toLowerCase();a=m[c]=m[c]||a,l[a]=b}return this},getAllResponseHeaders:function(){return s===2?n:null},getResponseHeader:function(a){var c;if(s===2){if(!o){o={};while(c=bH.exec(n))o[c[1].toLowerCase()]=c[2]}c=o[a.toLowerCase()]}return c===b?null:c},overrideMimeType:function(a){s||(d.mimeType=a);return this},abort:function(a){a=a||"abort",p&&p.abort(a),w(0,a);return this}};h.promise(v),v.success=v.done,v.error=v.fail,v.complete=i.add,v.statusCode=function(a){if(a){var b;if(s<2)for(b in a)j[b]=[j[b],a[b]];else b=a[v.status],v.then(b,b)}return this},d.url=((a||d.url)+"").replace(bG,"").replace(bL,bW[1]+"//"),d.dataTypes=f.trim(d.dataType||"*").toLowerCase().split(bP),d.crossDomain==null&&(r=bR.exec(d.url.toLowerCase()),d.crossDomain=!(!r||r[1]==bW[1]&&r[2]==bW[2]&&(r[3]||(r[1]==="http:"?80:443))==(bW[3]||(bW[1]==="http:"?80:443)))),d.data&&d.processData&&typeof d.data!="string"&&(d.data=f.param(d.data,d.traditional)),b$(bT,d,c,v);if(s===2)return!1;t=d.global,d.type=d.type.toUpperCase(),d.hasContent=!bK.test(d.type),t&&f.active++===0&&f.event.trigger("ajaxStart");if(!d.hasContent){d.data&&(d.url+=(bM.test(d.url)?"&":"?")+d.data,delete d.data),k=d.url;if(d.cache===!1){var x=f.now(),y=d.url.replace(bQ,"$1_="+x);d.url=y+(y===d.url?(bM.test(d.url)?"&":"?")+"_="+x:"")}}(d.data&&d.hasContent&&d.contentType!==!1||c.contentType)&&v.setRequestHeader("Content-Type",d.contentType),d.ifModified&&(k=k||d.url,f.lastModified[k]&&v.setRequestHeader("If-Modified-Since",f.lastModified[k]),f.etag[k]&&v.setRequestHeader("If-None-Match",f.etag[k])),v.setRequestHeader("Accept",d.dataTypes[0]&&d.accepts[d.dataTypes[0]]?d.accepts[d.dataTypes[0]]+(d.dataTypes[0]!=="*"?", "+bX+"; q=0.01":""):d.accepts["*"]);for(u in d.headers)v.setRequestHeader(u,d.headers[u]);if(d.beforeSend&&(d.beforeSend.call(e,v,d)===!1||s===2)){v.abort();return!1}for(u in{success:1,error:1,complete:1})v[u](d[u]);p=b$(bU,d,c,v);if(!p)w(-1,"No Transport");else{v.readyState=1,t&&g.trigger("ajaxSend",[v,d]),d.async&&d.timeout>0&&(q=setTimeout(function(){v.abort("timeout")},d.timeout));try{s=1,p.send(l,w)}catch(z){if(s<2)w(-1,z);else throw z}}return v},param:function(a,c){var d=[],e=function(a,b){b=f.isFunction(b)?b():b,d[d.length]=encodeURIComponent(a)+"="+encodeURIComponent(b)};c===b&&(c=f.ajaxSettings.traditional);if(f.isArray(a)||a.jquery&&!f.isPlainObject(a))f.each(a,function(){e(this.name,this.value)});else for(var g in a)ca(g,a[g],c,e);return d.join("&").replace(bD,"+")}}),f.extend({active:0,lastModified:{},etag:{}});var cd=f.now(),ce=/(\=)\?(&|$)|\?\?/i;f.ajaxSetup({jsonp:"callback",jsonpCallback:function(){return f.expando+"_"+cd++}}),f.ajaxPrefilter("json jsonp",function(b,c,d){var e=b.contentType==="application/x-www-form-urlencoded"&&typeof b.data=="string";if(b.dataTypes[0]==="jsonp"||b.jsonp!==!1&&(ce.test(b.url)||e&&ce.test(b.data))){var g,h=b.jsonpCallback=f.isFunction(b.jsonpCallback)?b.jsonpCallback():b.jsonpCallback,i=a[h],j=b.url,k=b.data,l="$1"+h+"$2";b.jsonp!==!1&&(j=j.replace(ce,l),b.url===j&&(e&&(k=k.replace(ce,l)),b.data===k&&(j+=(/\?/.test(j)?"&":"?")+b.jsonp+"="+h))),b.url=j,b.data=k,a[h]=function(a){g=[a]},d.always(function(){a[h]=i,g&&f.isFunction(i)&&a[h](g[0])}),b.converters["script json"]=function(){g||f.error(h+" was not called");return g[0]},b.dataTypes[0]="json";return"script"}}),f.ajaxSetup({accepts:{script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"},contents:{script:/javascript|ecmascript/},converters:{"text script":function(a){f.globalEval(a);return a}}}),f.ajaxPrefilter("script",function(a){a.cache===b&&(a.cache=!1),a.crossDomain&&(a.type="GET",a.global=!1)}),f.ajaxTransport("script",function(a){if(a.crossDomain){var d,e=c.head||c.getElementsByTagName("head")[0]||c.documentElement;return{send:function(f,g){d=c.createElement("script"),d.async="async",a.scriptCharset&&(d.charset=a.scriptCharset),d.src=a.url,d.onload=d.onreadystatechange=function(a,c){if(c||!d.readyState||/loaded|complete/.test(d.readyState))d.onload=d.onreadystatechange=null,e&&d.parentNode&&e.removeChild(d),d=b,c||g(200,"success")},e.insertBefore(d,e.firstChild)},abort:function(){d&&d.onload(0,1)}}}});var cf=a.ActiveXObject?function(){for(var a in ch)ch[a](0,1)}:!1,cg=0,ch;f.ajaxSettings.xhr=a.ActiveXObject?function(){return!this.isLocal&&ci()||cj()}:ci,function(a){f.extend(f.support,{ajax:!!a,cors:!!a&&"withCredentials"in a})}(f.ajaxSettings.xhr()),f.support.ajax&&f.ajaxTransport(function(c){if(!c.crossDomain||f.support.cors){var d;return{send:function(e,g){var h=c.xhr(),i,j;c.username?h.open(c.type,c.url,c.async,c.username,c.password):h.open(c.type,c.url,c.async);if(c.xhrFields)for(j in c.xhrFields)h[j]=c.xhrFields[j];c.mimeType&&h.overrideMimeType&&h.overrideMimeType(c.mimeType),!c.crossDomain&&!e["X-Requested-With"]&&(e["X-Requested-With"]="XMLHttpRequest");try{for(j in e)h.setRequestHeader(j,e[j])}catch(k){}h.send(c.hasContent&&c.data||null),d=function(a,e){var j,k,l,m,n;try{if(d&&(e||h.readyState===4)){d=b,i&&(h.onreadystatechange=f.noop,cf&&delete ch[i]);if(e)h.readyState!==4&&h.abort();else{j=h.status,l=h.getAllResponseHeaders(),m={},n=h.responseXML,n&&n.documentElement&&(m.xml=n),m.text=h.responseText;try{k=h.statusText}catch(o){k=""}!j&&c.isLocal&&!c.crossDomain?j=m.text?200:404:j===1223&&(j=204)}}}catch(p){e||g(-1,p)}m&&g(j,k,m,l)},!c.async||h.readyState===4?d():(i=++cg,cf&&(ch||(ch={},f(a).unload(cf)),ch[i]=d),h.onreadystatechange=d)},abort:function(){d&&d(0,1)}}}});var ck={},cl,cm,cn=/^(?:toggle|show|hide)$/,co=/^([+\-]=)?([\d+.\-]+)([a-z%]*)$/i,cp,cq=[["height","marginTop","marginBottom","paddingTop","paddingBottom"],["width","marginLeft","marginRight","paddingLeft","paddingRight"],["opacity"]],cr;f.fn.extend({show:function(a,b,c){var d,e;if(a||a===0)return this.animate(cu("show",3),a,b,c);for(var g=0,h=this.length;g<h;g++)d=this[g],d.style&&(e=d.style.display,!f._data(d,"olddisplay")&&e==="none"&&(e=d.style.display=""),e===""&&f.css(d,"display")==="none"&&f._data(d,"olddisplay",cv(d.nodeName)));for(g=0;g<h;g++){d=this[g];if(d.style){e=d.style.display;if(e===""||e==="none")d.style.display=f._data(d,"olddisplay")||""}}return this},hide:function(a,b,c){if(a||a===0)return this.animate(cu("hide",3),a,b,c);var d,e,g=0,h=this.length;for(;g<h;g++)d=this[g],d.style&&(e=f.css(d,"display"),e!=="none"&&!f._data(d,"olddisplay")&&f._data(d,"olddisplay",e));for(g=0;g<h;g++)this[g].style&&(this[g].style.display="none");return this},_toggle:f.fn.toggle,toggle:function(a,b,c){var d=typeof a=="boolean";f.isFunction(a)&&f.isFunction(b)?this._toggle.apply(this,arguments):a==null||d?this.each(function(){var b=d?a:f(this).is(":hidden");f(this)[b?"show":"hide"]()}):this.animate(cu("toggle",3),a,b,c);return this},fadeTo:function(a,b,c,d){return this.filter(":hidden").css("opacity",0).show().end().animate({opacity:b},a,c,d)},animate:function(a,b,c,d){function g(){e.queue===!1&&f._mark(this);var b=f.extend({},e),c=this.nodeType===1,d=c&&f(this).is(":hidden"),g,h,i,j,k,l,m,n,o;b.animatedProperties={};for(i in a){g=f.camelCase(i),i!==g&&(a[g]=a[i],delete a[i]),h=a[g],f.isArray(h)?(b.animatedProperties[g]=h[1],h=a[g]=h[0]):b.animatedProperties[g]=b.specialEasing&&b.specialEasing[g]||b.easing||"swing";if(h==="hide"&&d||h==="show"&&!d)return b.complete.call(this);c&&(g==="height"||g==="width")&&(b.overflow=[this.style.overflow,this.style.overflowX,this.style.overflowY],f.css(this,"display")==="inline"&&f.css(this,"float")==="none"&&(!f.support.inlineBlockNeedsLayout||cv(this.nodeName)==="inline"?this.style.display="inline-block":this.style.zoom=1))}b.overflow!=null&&(this.style.overflow="hidden");for(i in a)j=new f.fx(this,b,i),h=a[i],cn.test(h)?(o=f._data(this,"toggle"+i)||(h==="toggle"?d?"show":"hide":0),o?(f._data(this,"toggle"+i,o==="show"?"hide":"show"),j[o]()):j[h]()):(k=co.exec(h),l=j.cur(),k?(m=parseFloat(k[2]),n=k[3]||(f.cssNumber[i]?"":"px"),n!=="px"&&(f.style(this,i,(m||1)+n),l=(m||1)/j.cur()*l,f.style(this,i,l+n)),k[1]&&(m=(k[1]==="-="?-1:1)*m+l),j.custom(l,m,n)):j.custom(l,h,""));return!0}var e=f.speed(b,c,d);if(f.isEmptyObject(a))return this.each(e.complete,[!1]);a=f.extend({},a);return e.queue===!1?this.each(g):this.queue(e.queue,g)},stop:function(a,c,d){typeof a!="string"&&(d=c,c=a,a=b),c&&a!==!1&&this.queue(a||"fx",[]);return this.each(function(){function h(a,b,c){var e=b[c];f.removeData(a,c,!0),e.stop(d)}var b,c=!1,e=f.timers,g=f._data(this);d||f._unmark(!0,this);if(a==null)for(b in g)g[b]&&g[b].stop&&b.indexOf(".run")===b.length-4&&h(this,g,b);else g[b=a+".run"]&&g[b].stop&&h(this,g,b);for(b=e.length;b--;)e[b].elem===this&&(a==null||e[b].queue===a)&&(d?e[b](!0):e[b].saveState(),c=!0,e.splice(b,1));(!d||!c)&&f.dequeue(this,a)})}}),f.each({slideDown:cu("show",1),slideUp:cu("hide",1),slideToggle:cu("toggle",1),fadeIn:{opacity:"show"},fadeOut:{opacity:"hide"},fadeToggle:{opacity:"toggle"}},function(a,b){f.fn[a]=function(a,c,d){return this.animate(b,a,c,d)}}),f.extend({speed:function(a,b,c){var d=a&&typeof a=="object"?f.extend({},a):{complete:c||!c&&b||f.isFunction(a)&&a,duration:a,easing:c&&b||b&&!f.isFunction(b)&&b};d.duration=f.fx.off?0:typeof d.duration=="number"?d.duration:d.duration in f.fx.speeds?f.fx.speeds[d.duration]:f.fx.speeds._default;if(d.queue==null||d.queue===!0)d.queue="fx";d.old=d.complete,d.complete=function(a){f.isFunction(d.old)&&d.old.call(this),d.queue?f.dequeue(this,d.queue):a!==!1&&f._unmark(this)};return d},easing:{linear:function(a,b,c,d){return c+d*a},swing:function(a,b,c,d){return(-Math.cos(a*Math.PI)/2+.5)*d+c}},timers:[],fx:function(a,b,c){this.options=b,this.elem=a,this.prop=c,b.orig=b.orig||{}}}),f.fx.prototype={update:function(){this.options.step&&this.options.step.call(this.elem,this.now,this),(f.fx.step[this.prop]||f.fx.step._default)(this)},cur:function(){if(this.elem[this.prop]!=null&&(!this.elem.style||this.elem.style[this.prop]==null))return this.elem[this.prop];var a,b=f.css(this.elem,this.prop);return isNaN(a=parseFloat(b))?!b||b==="auto"?0:b:a},custom:function(a,c,d){function h(a){return e.step(a)}var e=this,g=f.fx;this.startTime=cr||cs(),this.end=c,this.now=this.start=a,this.pos=this.state=0,this.unit=d||this.unit||(f.cssNumber[this.prop]?"":"px"),h.queue=this.options.queue,h.elem=this.elem,h.saveState=function(){e.options.hide&&f._data(e.elem,"fxshow"+e.prop)===b&&f._data(e.elem,"fxshow"+e.prop,e.start)},h()&&f.timers.push(h)&&!cp&&(cp=setInterval(g.tick,g.interval))},show:function(){var a=f._data(this.elem,"fxshow"+this.prop);this.options.orig[this.prop]=a||f.style(this.elem,this.prop),this.options.show=!0,a!==b?this.custom(this.cur(),a):this.custom(this.prop==="width"||this.prop==="height"?1:0,this.cur()),f(this.elem).show()},hide:function(){this.options.orig[this.prop]=f._data(this.elem,"fxshow"+this.prop)||f.style(this.elem,this.prop),this.options.hide=!0,this.custom(this.cur(),0)},step:function(a){var b,c,d,e=cr||cs(),g=!0,h=this.elem,i=this.options;if(a||e>=i.duration+this.startTime){this.now=this.end,this.pos=this.state=1,this.update(),i.animatedProperties[this.prop]=!0;for(b in i.animatedProperties)i.animatedProperties[b]!==!0&&(g=!1);if(g){i.overflow!=null&&!f.support.shrinkWrapBlocks&&f.each(["","X","Y"],function(a,b){h.style["overflow"+b]=i.overflow[a]}),i.hide&&f(h).hide();if(i.hide||i.show)for(b in i.animatedProperties)f.style(h,b,i.orig[b]),f.removeData(h,"fxshow"+b,!0),f.removeData(h,"toggle"+b,!0);d=i.complete,d&&(i.complete=!1,d.call(h))}return!1}i.duration==Infinity?this.now=e:(c=e-this.startTime,this.state=c/i.duration,this.pos=f.easing[i.animatedProperties[this.prop]](this.state,c,0,1,i.duration),this.now=this.start+(this.end-this.start)*this.pos),this.update();return!0}},f.extend(f.fx,{tick:function(){var a,b=f.timers,c=0;for(;c<b.length;c++)a=b[c],!a()&&b[c]===a&&b.splice(c--,1);b.length||f.fx.stop()},interval:13,stop:function(){clearInterval(cp),cp=null},speeds:{slow:600,fast:200,_default:400},step:{opacity:function(a){f.style(a.elem,"opacity",a.now)},_default:function(a){a.elem.style&&a.elem.style[a.prop]!=null?a.elem.style[a.prop]=a.now+a.unit:a.elem[a.prop]=a.now}}}),f.each(["width","height"],function(a,b){f.fx.step[b]=function(a){f.style(a.elem,b,Math.max(0,a.now)+a.unit)}}),f.expr&&f.expr.filters&&(f.expr.filters.animated=function(a){return f.grep(f.timers,function(b){return a===b.elem}).length});var cw=/^t(?:able|d|h)$/i,cx=/^(?:body|html)$/i;"getBoundingClientRect"in c.documentElement?f.fn.offset=function(a){var b=this[0],c;if(a)return this.each(function(b){f.offset.setOffset(this,a,b)});if(!b||!b.ownerDocument)return null;if(b===b.ownerDocument.body)return f.offset.bodyOffset(b);try{c=b.getBoundingClientRect()}catch(d){}var e=b.ownerDocument,g=e.documentElement;if(!c||!f.contains(g,b))return c?{top:c.top,left:c.left}:{top:0,left:0};var h=e.body,i=cy(e),j=g.clientTop||h.clientTop||0,k=g.clientLeft||h.clientLeft||0,l=i.pageYOffset||f.support.boxModel&&g.scrollTop||h.scrollTop,m=i.pageXOffset||f.support.boxModel&&g.scrollLeft||h.scrollLeft,n=c.top+l-j,o=c.left+m-k;return{top:n,left:o}}:f.fn.offset=function(a){var b=this[0];if(a)return this.each(function(b){f.offset.setOffset(this,a,b)});if(!b||!b.ownerDocument)return null;if(b===b.ownerDocument.body)return f.offset.bodyOffset(b);var c,d=b.offsetParent,e=b,g=b.ownerDocument,h=g.documentElement,i=g.body,j=g.defaultView,k=j?j.getComputedStyle(b,null):b.currentStyle,l=b.offsetTop,m=b.offsetLeft;while((b=b.parentNode)&&b!==i&&b!==h){if(f.support.fixedPosition&&k.position==="fixed")break;c=j?j.getComputedStyle(b,null):b.currentStyle,l-=b.scrollTop,m-=b.scrollLeft,b===d&&(l+=b.offsetTop,m+=b.offsetLeft,f.support.doesNotAddBorder&&(!f.support.doesAddBorderForTableAndCells||!cw.test(b.nodeName))&&(l+=parseFloat(c.borderTopWidth)||0,m+=parseFloat(c.borderLeftWidth)||0),e=d,d=b.offsetParent),f.support.subtractsBorderForOverflowNotVisible&&c.overflow!=="visible"&&(l+=parseFloat(c.borderTopWidth)||0,m+=parseFloat(c.borderLeftWidth)||0),k=c}if(k.position==="relative"||k.position==="static")l+=i.offsetTop,m+=i.offsetLeft;f.support.fixedPosition&&k.position==="fixed"&&(l+=Math.max(h.scrollTop,i.scrollTop),m+=Math.max(h.scrollLeft,i.scrollLeft));return{top:l,left:m}},f.offset={bodyOffset:function(a){var b=a.offsetTop,c=a.offsetLeft;f.support.doesNotIncludeMarginInBodyOffset&&(b+=parseFloat(f.css(a,"marginTop"))||0,c+=parseFloat(f.css(a,"marginLeft"))||0);return{top:b,left:c}},setOffset:function(a,b,c){var d=f.css(a,"position");d==="static"&&(a.style.position="relative");var e=f(a),g=e.offset(),h=f.css(a,"top"),i=f.css(a,"left"),j=(d==="absolute"||d==="fixed")&&f.inArray("auto",[h,i])>-1,k={},l={},m,n;j?(l=e.position(),m=l.top,n=l.left):(m=parseFloat(h)||0,n=parseFloat(i)||0),f.isFunction(b)&&(b=b.call(a,c,g)),b.top!=null&&(k.top=b.top-g.top+m),b.left!=null&&(k.left=b.left-g.left+n),"using"in b?b.using.call(a,k):e.css(k)}},f.fn.extend({position:function(){if(!this[0])return null;var a=this[0],b=this.offsetParent(),c=this.offset(),d=cx.test(b[0].nodeName)?{top:0,left:0}:b.offset();c.top-=parseFloat(f.css(a,"marginTop"))||0,c.left-=parseFloat(f.css(a,"marginLeft"))||0,d.top+=parseFloat(f.css(b[0],"borderTopWidth"))||0,d.left+=parseFloat(f.css(b[0],"borderLeftWidth"))||0;return{top:c.top-d.top,left:c.left-d.left}},offsetParent:function(){return this.map(function(){var a=this.offsetParent||c.body;while(a&&!cx.test(a.nodeName)&&f.css(a,"position")==="static")a=a.offsetParent;return a})}}),f.each(["Left","Top"],function(a,c){var d="scroll"+c;f.fn[d]=function(c){var e,g;if(c===b){e=this[0];if(!e)return null;g=cy(e);return g?"pageXOffset"in g?g[a?"pageYOffset":"pageXOffset"]:f.support.boxModel&&g.document.documentElement[d]||g.document.body[d]:e[d]}return this.each(function(){g=cy(this),g?g.scrollTo(a?f(g).scrollLeft():c,a?c:f(g).scrollTop()):this[d]=c})}}),f.each(["Height","Width"],function(a,c){var d=c.toLowerCase();f.fn["inner"+c]=function(){var a=this[0];return a?a.style?parseFloat(f.css(a,d,"padding")):this[d]():null},f.fn["outer"+c]=function(a){var b=this[0];return b?b.style?parseFloat(f.css(b,d,a?"margin":"border")):this[d]():null},f.fn[d]=function(a){var e=this[0];if(!e)return a==null?null:this;if(f.isFunction(a))return this.each(function(b){var c=f(this);c[d](a.call(this,b,c[d]()))});if(f.isWindow(e)){var g=e.document.documentElement["client"+c],h=e.document.body;return e.document.compatMode==="CSS1Compat"&&g||h&&h["client"+c]||g}if(e.nodeType===9)return Math.max(e.documentElement["client"+c],e.body["scroll"+c],e.documentElement["scroll"+c],e.body["offset"+c],e.documentElement["offset"+c]);if(a===b){var i=f.css(e,d),j=parseFloat(i);return f.isNumeric(j)?j:i}return this.css(d,typeof a=="string"?a:a+"px")}}),a.jQuery=a.$=f,typeof define=="function"&&define.amd&&define.amd.jQuery&&define("jquery",[],function(){return f})})(window);
                </script>
                
                <script>
                    var modules = <?php print json_encode($modulePackage) ?>,
                        pinion = {
                            php: {
                                url: "<?php print SITE_URL ?>"
                            }
                        };
                </script>
                <script src="pinion/js/colorpicker/colorpicker.js" type="text/javascript"></script>
                <script type="text/javascript">var site = {},
    user = {},
    count = 0,
    done = false,
    $message = $("#message"),
    $progress = $(".progress"),
    $menu = $("#pinion-menu"),
    $list = $("#pinion-install-list"),
    $installListItems = $list.children(),
    install = function() {
        if(count < modules.length) {
            var module = modules[count],
                loader = new pinion.Loader("darkblue-30px", 30),
                outerAjaxCounter = 0,
                innerAjaxCounter = 0,
                outerAjax = function() {
                    outerAjaxCounter++;
                    if(outerAjaxCounter < 50) {
                        $.ajax({
                            url: "install.php",
                            type: "post",
                            dataType: "html",
                            data: {install: module},
                            success: innerAjax,
                            error: function() {
                                setTimeout(outerAjax, 500, module, loader);
                            }
                        });
                    } else {
                        innerAjax();
                    }
                },
                innerAjax = function() {
                    innerAjaxCounter++;
                    if(innerAjaxCounter < 50) {
                        $.ajax({
                            url: "index.php",
                            type: "post",
                            dataType: "json",
                            data: user,
                            success: innerAjaxCallback,
                            error: function() {
                                setTimeout(innerAjax, 500);
                            }
                        });
                    } else {
                        innerAjaxCallback();
                        $installListItems
                            .eq(count-1)
                                .removeClass("installed")
                                .addClass("notInstalled");
                    }
                },
                innerAjaxCallback = function() {
                    loader.remove();
                    $installListItems
                        .eq(count)
                            .removeClass("installNow")
                            .addClass("installed")
                            //.appendTo($list); // move the newest to the top

                    count++;
                    $progress.animate({
                        width: count/modules.length*100+"%"
                    }, 300);
                    install();
                };

            $("#pinion-install-module").html("install module <b>"+module+"</b> ("+(count+1)+"/"+modules.length+")");
            $installListItems
                .eq(count)
                    .removeClass("notInstalled")
                    .addClass("installNow")
                    .children(".pinion-module-status")
                        .append(loader.$element);


            outerAjax();
        } else {
            $("#pinion-install-module").html("<b>"+modules.length+" modules installed</b>");
            if(user.admin_email != "") {
                // send mail to user
                $.post("install.php", $.extend({sendmail: true}, user));
            }
            setTimeout(function() {
                $("#pinion-install-page-install").hide();
                $menu.children().removeClass("active").filter("[data-id='pinion-install-page-complete']").addClass("active");
                $("#pinion-install-page-complete").show();
                done = true;
            }, 1500);
        }
    };

$(".pinion-previousStep").click(function(event) {
    var $activeMenuChild = $menu.children(".active");
    $("#"+$activeMenuChild.attr("data-id")).hide();
    $activeMenuChild = $activeMenuChild.removeClass("active").prev().addClass("active");
    $("#"+$activeMenuChild.attr("data-id")).show();

    event.stopPropagation();
    return false;
});

$menu.children().click(function() {
    var $activeMenuChild = $menu.children(".active"),
        activeIndex = $activeMenuChild.index(),
        $this = $(this),
        index = $this.index();
        
    if(activeIndex < 3 && index < activeIndex) {
        $("#"+$activeMenuChild.attr("data-id")).hide();
        $activeMenuChild.removeClass("active");
        $activeMenuChild = $this.addClass("active");
        $("#"+$activeMenuChild.attr("data-id")).show();
    }
    return false;
});

$("#userForm").submit(function() {
    var $this = $(this);
    user = {
        admin_firstname: $.trim($this.find("[name=firstname]").val()),
        admin_lastname: $.trim($this.find("[name=lastname]").val()),
        admin_email: $.trim($this.find("[name=email]").val()),
        admin_language: $this.find("[name=language]").val(),
        admin_username: $.trim($this.find("[name=username]").val()),
        admin_password: $.trim($this.find("[name=password]").val())
    };
    
    if(user.admin_firstname == "") {
        var msgs = {
            en: "Please type in your firstname",
            de: "Bitte gib deinen Vornamen ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if(user.admin_lastname == "") {
        var msgs = {
            en: "Please type in your lastname",
            de: "Bitte gib deinen Nachnamen ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if(user.admin_email == "") {
        var msgs = {
            en: "Please type in your email-address",
            de: "Bitte gib deine Email-Adresse ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if(!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(user.admin_email)) {
        var msgs = {
            en: "Please type in a valid email-address",
            de: "Bitte gib eine gültige Email-Adresse ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if(user.admin_username == "") {
        var msgs = {
            en: "Please type in your username",
            de: "Bitte gib deinen Benutzernamen ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if(user.admin_password == "") {
        var msgs = {
            en: "Please type in your password",
            de: "Bitte gib dein Passwort ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if($.trim($this.find("[name=confirmpassword]").val()) == "") {
        var msgs = {
            en: "Please confirm your password",
            de: "Bitte bestätige dein Passwort"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if($.trim($this.find("[name=confirmpassword]").val()) != user.admin_password) {
        var msgs = {
            en: "The confirmation of the password is not correct",
            de: "Die Bestätigung des Passworts war nicht korrekt"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else {
        $message.hide();
        $("#pinion-install-page-user").hide();
        $menu.children().removeClass("active").filter("[data-id='pinion-install-page-site']").addClass("active");
        $("#pinion-install-page-site").show();
    }
    
    return false;
});

$("#siteForm").submit(function() {
    var $this = $(this);
    site = {
        sitename: $.trim($this.find("[name=sitename]").val()),
        timezone: $this.find("[name=timezone]").val(),
        language: $this.find("[name=language]").val(),
        languages: $this.find("[name=languages]").val()
    };
    if(site.sitename == "") {
        var msgs = {
            en: "Please type in the name of your site",
            de: "Bitte gib den Namen der Seite ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else {
        $message.hide();
        $("#pinion-install-page-site").hide();
        $menu.children().removeClass("active").filter("[data-id='pinion-install-page-database']").addClass("active");
        $("#pinion-install-page-database").show();
        
        // make an ajax to write the info.ini for the template
        $.ajax({
            url: "install.php",
            type: "post",
            data: {
                backgroundColor: $this.find("[name=pagecolor]").val(),
                headerColor: $this.find("[name=headercolor]").val(),
                footerColor: $this.find("[name=footercolor]").val(),
                headerTextColor: $this.find("[name=headertextcolor]").val(),
                footerTextColor: $this.find("[name=footertextcolor]").val(),
            }
        });
    }
    
    return false;
});

$("#databaseForm").submit(function() {
    var $this = $(this),
        database = $.trim($this.find("[name=database]").val()),
        host = $.trim($this.find("[name=host]").val());
    
    if(database == "") {
        var msgs = {
            en: "Please type in the name of the database",
            de: "Bitte gib den Namen der Datenbank ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else if(host == "") {
        var msgs = {
            en: "Please type in the host of the database",
            de: "Bitte gib den Host der Datenbank ein"
        };
        $message
            .text(msgs[currentLang])
            .show();
    } else {
        $message.hide();
        var outerAjaxCounter = 0,
            innerAjaxCounter = 0,
            outerAjax = function() {
                outerAjaxCounter++;
                if(outerAjaxCounter < 50) {
                    $.ajax({
                        url: "index.php?logout", // log out the user
                        type: "post",
                        dataType: "json",
                        data: $.extend(site, {
                            database:    database,
                            username:    $.trim($this.find("[name=username]").val()),
                            password:    $.trim($this.find("[name=password]").val()),
                            host:        host,
                            tableprefix: $.trim($this.find("[name=tableprefix]").val())
                        }),
                        success: function(data) {
                            if(data.error === undefined) {
                                $message.hide();
                                $("#pinion-install-page-database").hide();
                                $menu.children().removeClass("active").filter("[data-id='pinion-install-page-install']").addClass("active");
                                $("#pinion-install-page-install").show();
                                innerAjax();
                            } else {
                                $message
                                    .text(data.error[currentLang])
                                    .show();
                            }
                        },
                        error: function() {
                            setTimeout(outerAjax, 500);
                        }
                    });
                } else {
                    $("#pinion-install-page-database").hide();
                    $menu.children().removeClass("active").filter("[data-id='pinion-install-page-install']").addClass("active");
                    $("#pinion-install-page-install").show();
                    innerAjax();
                }
            },
            innerAjax = function() {
                innerAjaxCounter++;
                if(innerAjaxCounter < 10) {
                    $.ajax({
                        url: "index.php",
                        type: "post",
                        success: install,
                        error: innerAjax
                    });
                } else {
                    setTimeout(install, 500);
                }
            };

        // create database.ini
        outerAjax();
    }
    
    return false;
});



$("#backendLink").click(function() {
    $.post("index.php", {username: user.admin_username, password: user.admin_password}, function() {
        window.location.reload();
    });
    return false;
});

$(".pinion-install-colorpicker").each(function() {
    var $this = $(this);
    $this.ColorPicker({
	color: $this.val(),
	onShow: function(colpkr) {
            $(colpkr).fadeIn(500);
            return false;
	},
	onHide: function(colpkr) {
            $(colpkr).fadeOut(500);
            return false;
	},
	onChange: function(hsb, hex, rgb) {
            $this
                .val("#"+hex)
                .css("background", "#"+hex);
	}
    }).css("background", $this.val());
});

// TRANSLATION
var currentLang = "en";
var translate = (function() {
    
    var initialized = false,
        $elements = null;
    
    return function(lang) {
        if(!initialized) {
            if(lang != "en") {
                $elements = $(".translated");
                $elements.each(function() {
                    var $this = $(this);
                    $this.attr("data-lang-en", $this.html());
                    $this.html($this.attr("data-lang-"+lang));
                });
                currentLang = lang;
                initialized = true;
            }
        } else if(currentLang != lang) {
            $elements.each(function() {
                var $this = $(this);
                $this.html($this.attr("data-lang-"+lang));
            });
            currentLang = lang;
        }
    };
}());

$("#pinion-start-languages li").click(function() {
    translate($(this).attr("data-lang"));
    $("#pinion-start").fadeOut(300);
});

$("#language-link").click(function() {
    $("#pinion-start").fadeIn(300);
});</script>
                <script src="pinion/js/core/_core/Loader.js" type="text/javascript"></script>
            </div>
        </div>
        <div id="pinion-install-bottom"></div>
    </body>
</html>
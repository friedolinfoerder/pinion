<?php 
    foreach($get("languages") as $language) {
        if($language == $get("active")) {
            $class = " class='active'";
        } else {
            $class = "";
        }
        print "<a href='".CURRENT_URL."&lang=$language'$class>$language</a>";
    }
?>
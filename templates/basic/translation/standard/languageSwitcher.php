<!--<b>Language Switcher</b><br />-->
<?php 
    /*foreach($get("languages") as $language) {
        if($language == $get("active")) {
            $class = " class='active'";
        } else {
            $class = "";
        }
        print "<a href='".CURRENT_URL."&lang=$language'$class>$language</a><br />";
    }*/
?>


<div class="language-switcher">
    <ul class="language-flags">
<?php 
    foreach($get("languages") as $language) {
        if($language == $get("active")) {
            $class = " class='active'";
        } else {
            $class = "";
        }
        print "<li><a href='".CURRENT_URL."&lang=$language' class='pinion-flag pinion-flag-$language' $class></a></li>";
    }
?>
    </ul>
</div>



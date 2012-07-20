<?php print "<?php" ?>

/**
 * Module Video
 *
 * @author <?php print $author ?> 
 */
namespace modules\<?php print $namespace ?>;

use \pinion\modules\Module;
use \pinion\modules\FrontendModule;
use \pinion\events\Event;
use \pinion\general\TemplateBuilder;

class <?php print $classname ?> extends <?php print $type ?> {
    
    
<?php
    $tab = "    ";

    if($code_install) {
        $code_install = str_replace("\n", "\n{$tab}{$tab}", $code_install);
        print "{$tab}public function install() {\n";
        print "{$tab}{$tab}$code_install\n";
        print "{$tab}}\n\n\n";
    }

    if($code_uninstall) {
        $code_uninstall = str_replace("\n", "\n{$tab}{$tab}", $code_uninstall);
        print "{$tab}public function uninstall() {\n";
        print "{$tab}{$tab}$code_uninstall\n";
        print "{$tab}}\n\n\n";
    }
    
    if($code_init) {
        $code_init = str_replace("\n", "\n{$tab}{$tab}", $code_init);
        print "{$tab}public function init() {\n";
        print "{$tab}{$tab}$code_init\n";
        print "{$tab}}\n\n\n";
    }
    
    if(isset($events)) {
        print "{$tab}public function addListener() {\n";
        print "{$tab}{$tab}parent::addListener();\n\n";
        if(isset($events["all"])) {
            foreach($events["all"] as $event) {
                if(isset($event["permission"])) {
                    print "{$tab}{$tab}if(\$this->hasPermission(\"{$event["permission"]}\")) ";
                }
                print "{$tab}{$tab}\$this->addEventListener(\"{$event["event"]}\");\n";
            }
            print "\n";
        }
        if(isset($events["loggedIn"])) {
            print "{$tab}{$tab}if(\$this->identity) {\n";
            foreach($events["loggedIn"] as $event) {
                if(isset($event["permission"])) {
                    print "{$tab}{$tab}{$tab}if(\$this->hasPermission(\"{$event["permission"]}\")) ";
                }
                print "{$tab}{$tab}{$tab}\$this->addEventListener(\"{$event["event"]}\");\n";
            }
            print "{$tab}{$tab}}\n";
        }
        print "{$tab}}\n\n\n";
    }
    
    if(isset($resources)) {
        print "{$tab}public function getResources() {\n";
        print "{$tab}{$tab}return array_merge(parent::getResources(), array(\n";
        foreach($resources as $resource) {
            print "{$tab}{$tab}{$tab}\"$resource\",\n";
        }
        print "{$tab}{$tab}));\n";
        print "{$tab}}\n\n\n";
    }
    
    // event functions
    if(isset($events["all"])) {
        foreach($events["all"] as $event) {
            print "{$tab}public function {$event["event"]}(Event \$event) {\n";
            if(isset($event["code"])) {
                $code = str_replace("\n", "\n{$tab}{$tab}", $event["code"]);
                print "{$tab}{$tab}$code\n";
            } else {
                print "{$tab}{$tab}\n";
            }
            print "{$tab}}\n\n\n";
        }
    }
    
    if(isset($events["loggedIn"])) {
        foreach($events["loggedIn"] as $event) {
            print "{$tab}public function {$event["event"]}(Event \$event) {\n";
            if(isset($event["code"])) {
                $code = str_replace("\n", "\n{$tab}{$tab}", $event["code"]);
                print "{$tab}{$tab}$code\n";
            } else {
                print "{$tab}{$tab}\n";
            }
            print "{$tab}}\n\n\n";
        }
    }
    
?>
    public function defineBackend() {
        parent::defineBackend();
        
        $this->framework
            ->text("Headline", array("text" => "This is the backend area of the module \"<?php print $title ?>\""));
            
    }
    
    
    <?php if($type == "FrontendModule"): ?>
    public function add(Event $event) {
        
        // the new data
        $data = array();
        
        
        
        // fill the data with information from the event...
        
        
        
        // create the data storage
        $<?php print $namespace; ?> = $this->data->create($this->name, $data);
        
        
        // give the event and the storage data to the page,
        // so the content can be linked with the page
        $this->module("page")->setModuleContentMapping($event, $<?php print $namespace; ?>);
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s added", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    
    
    public function edit(Event $event) {
        
        // get the id from the event
        $id = $event->getInfo("id");
        
        
        // get the data storage
        $<?php print $namespace; ?> = $this->data->find($this->name, $id);
        
        
        
        // update the data storage...
        
        
        
        // save the data storage
        $<?php print $namespace; ?>->save();
        
        
        // dispatch the content event of the page
        // to update the contents on the page
        $this->module("page")->dispatchEvent("content", array("data" => $<?php print $namespace; ?>));
        
        
        // add a success message
        $this->response->addSuccess($this, $this->translate("%s edited", "<b>".$this->translate($this->information["title"])."</b>"));
    }
    <?php endif; ?>
    
}


<?php print "?>" ?>
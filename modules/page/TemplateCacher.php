<?php

class TemplateCacher {
    
    protected $cachePath;
    protected $backendGenerator;
    
    public function __construct($path) {
        $this->cachePath = $path;
        $this->backendGenerator = new BackendTemplateGenerator();
    }
    
    public function getBackendFile($cachePath, $modulename, $moduleid, $contentid, $path) {
        $newContent = "";
        
        //$content = $this->backendGenerator->getFileContent($path);
        $fileContents = file_get_contents($path);
        $md5 = md5($fileContents);
        
        $cacheMd5Path = $cachePath."/{$moduleid}_$md5.php";
        
        if(! file_exists($cachePath)) {
            mkdir($cachePath);
            $newContent = $this->backendGenerator->getFileContent($fileContents);
            file_put_contents($cacheMd5Path, $newContent);
        } elseif(! file_exists($cacheMd5Path)) {
            $cacheDir = new DirectoryIterator($cachePath);
            foreach($cacheDir as $file) {
                $timeTillDeleting = 60*60*24*10; // 10 days
                if($file->isFile() && ($file->getMTime() + $timeTillDeleting) < time()) {
                    unlink($file->getRealPath());
                }
            }
            $newContent = $this->backendGenerator->getFileContent($fileContents, $modulename, $moduleid, $contentid);
            file_put_contents($cacheMd5Path, $newContent);
        }
        
        return $cacheMd5Path;
        
    }
}

?>

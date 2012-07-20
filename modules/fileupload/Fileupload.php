<?php
/**
 * Module Fileupload
 * 
 * PHP version 5.3
 * 
 * @author  Friedolin Förder <friedolin.foerder@pinion-cms.org>
 * @license MIT License
 * @link    http://www.pinion-cms.org/module/fileupload
 */

namespace modules\fileupload;

use \pinion\modules\Module;
use \pinion\events\Event;
use \pinion\files\DirectoryRearranger;

class Fileupload extends Module {
    
    private $options;
    private $dirs = array(
        "image"       => "images",
        "application" => "documents",
        "video"       => "video",
        "audio"       => "audio"
    );
    
    public function getFilesPath() {
        return $this->data->filesPath();
    }
    
    public function getFilesUrl() {
        return $this->data->filesUrl();
    }
    
    public function install() {
        $this->data
            ->createDataStorage("file", array(
                "filename" => array("type" => "varchar", "length" => 500, "translatable" => false),
                "directory" => array("type" => "varchar", "length" => 500, "translatable" => false)
            ));
        
        DirectoryRearranger::create($this->data->filesPath()."deleted");
        DirectoryRearranger::create($this->data->filesPath()."deleted/others");
        DirectoryRearranger::create($this->data->filesPath()."others");
        DirectoryRearranger::create($this->data->filesPath()."temp");
        foreach($this->dirs as $dir) {
            DirectoryRearranger::create($this->data->filesPath().$dir);
            DirectoryRearranger::create($this->data->filesPath()."deleted/".$dir);
        }
        DirectoryRearranger::create($this->data->filesPath()."images/edited");
        DirectoryRearranger::create($this->data->filesPath()."deleted/images/edited");
    }
    
    public function init() {
        
        $this->options = array(
            'script_url' => SITE_URL,
            'upload_dir' => $this->data->filesPath(),
            'upload_url' => $this->data->filesUrl(),
            'param_name' => 'files',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            'accept_file_types' => '/.+$/i',
            'max_number_of_files' => null,
            // Set the following option to false to enable non-multipart uploads:
            'discard_aborted_uploads' => true
        );
        if($this->request->hasRequestParameter("options")) {
            $this->options = array_replace_recursive($this->options, $this->request->getRequestParameter("options"));
        }
    }
    
    public function getResources() {
        return array_merge(parent::getResources(), array(
            "upload file",
            // TODO "rename file", 
            // TODO "restore file", 
            "delete file",
            "delete trash file"
        ));
    }
    
    public function addListener() {
        parent::addListener();
        
        if($this->identity) {
            if($this->request->isAjax()) {
                if($this->hasPermission("upload file"))         $this->addEventListener("upload");
                if($this->hasPermission("delete file"))         $this->addEventListener("delete");
                if($this->hasPermission("delete trash file"))   $this->addEventListener("deleteTrash");
            }
            $this->addEventListener("getFiles");
        }
        
    }
    
    public function getFiles(Event $event) {
        $options = array("order" => "created desc");
        $start = $event->getInfo("start");
        $end = $event->getInfo("end");
        
        $options["offset"] = $start;
        $options["limit"] = $end - $start;
        
        $files = $this->data->all("file", $options);
        
        $this->response->setInfo("data", $this->data->getAttributes($files));
        
        if($start == 0) {
            $this->response->setInfo("dataLength", $this->data->count("file"));
        }
    }
    
    private function get_file_object($file_name) {
        $file_path = $this->options['upload_dir'].$file_name;
        if (is_file($file_path) && $file_name[0] !== '.') {
            $file = new stdClass();
            $file->name = $file_name;
            $file->size = filesize($file_path);
            $file->url = $this->options['upload_url'].rawurlencode($file->name);
            foreach($this->options['image_versions'] as $version => $options) {
                if (is_file($options['upload_dir'].$file_name)) {
                    $file->{$version.'_url'} = $options['upload_url']
                        .rawurlencode($file->name);
                }
            }
            $file->delete_url = $this->options['script_url'].'&module=fileupload&file='.rawurlencode($file->name).'&dir='.$dirPath.'&event=delete';
            $file->delete_type = 'GET';
            return $file;
        }
        return null;
    }
    
    private function get_file_objects() {
        return array_values(array_filter(array_map(
            array($this, 'get_file_object'),
            scandir($this->options['upload_dir'])
        )));
    }
    
    private function has_error($uploaded_file, $file, $error) {
        if ($error) {
            return $error;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            return 'acceptFileTypes';
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = filesize($uploaded_file);
        } else {
            $file_size = $_SERVER['CONTENT_LENGTH'];
        }
        if ($this->options['max_file_size'] && (
                $file_size > $this->options['max_file_size'] ||
                $file->size > $this->options['max_file_size'])
            ) {
            return 'maxFileSize';
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            return 'minFileSize';
        }
        if (is_int($this->options['max_number_of_files']) && (
                count($this->get_file_objects()) >= $this->options['max_number_of_files'])
            ) {
            return 'maxNumberOfFiles';
        }
        return $error;
    }
    
    private function trim_file_name($name, $type) {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $file_name = trim(basename(stripslashes($name)), ".\x00..\x20");
        // Add missing file extension for known image types:
        if (strpos($file_name, '.') === false &&
            preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $file_name .= '.'.$matches[1];
        }
        return $file_name;
    }
    
    private function handle_file_upload($uploaded_file, $name, $size, $type, $error, $temp) {
        $file = new \stdClass();
        $file->name = $this->trim_file_name($name, $type);
        $file->size = intval($size);
        $file->type = $type;
        $error = $this->has_error($uploaded_file, $file, $error);
        if (!$error && $file->name) {
            if($temp) {
                $dirPath = "temp";
            } else {
                $dirPath = explode("/", $type);
                $dirPath = $dirPath[0];
                $dirPath = isset($this->dirs[$dirPath]) ? $this->dirs[$dirPath] : "others";
            }
            
            $file_path = $this->options['upload_dir'].$dirPath."/".$file->name;
            
            $append_file = !$this->options['discard_aborted_uploads'] &&
                is_file($file_path) && $file->size > filesize($file_path);
            clearstatcache();
            if($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            
            
            $file_size = filesize($file_path);
            if($file_size === $file->size) {
                $file->url = $this->options['upload_url'].$dirPath."/".$file->name;
                
                if($dirPath != "temp") {
                    $dbFile = $this->data->create("file", array(
                        "filename" => $file->name,
                        "directory" => $dirPath
                    ));

                    if($dirPath == "images") {
                        // add to db
                        $information = $this->module("image")->addImage($dbFile->id);
                        $file->thumbnail_url = $information["thumb"];
                        $file->thumbnailBig_url = $information["thumbBig"];
                        $file->id = $information["id"];
                    }
                }
            } elseif($this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
            $file->size = $file_size;
            $file->delete_url = $this->options['script_url'].'&module=fileupload&file='.$file->name.'&dir='.$dirPath.'&event=delete';
            $file->delete_type = 'GET';
        } else {
            $file->error = $error;
        }
        $this->response->addSuccess($this, $this->translate("The file %s has been uploaded", "\"<i>{$file->name}</i>\""));
        return $file;
    }
    
    public function upload(Event $event) {
        $temp = $event->getInfo("temp");
        
        $upload = isset($_FILES[$this->options['param_name']]) ? $_FILES[$this->options['param_name']] : null;
        $info = array();
        if($upload && is_array($upload['tmp_name'])) {
            foreach($upload['tmp_name'] as $index => $value) {
                $info[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
                    isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
                    isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
                    $upload['error'][$index],
                    $temp
                );
            }
        } elseif ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
            $info[] = $this->handle_file_upload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : (isset($upload['name']) ? isset($upload['name']) : null),
                isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : (isset($upload['size']) ? isset($upload['size']) : null),
                isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : (isset($upload['type']) ? isset($upload['type']) : null),
                isset($upload['error']) ? $upload['error'] : null,
                $temp
            );
        }
        $this->response->setInfo("result", $info);
    }
    
    public function delete(Event $event) {
        $file = $event->getInfo("file");
        $dir = $event->getInfo("dir");
        $path = $this->data->filesPath()."$dir/$file";
        
        if(is_file($path) && $file{0} !== ".") {
            copy($path, $this->data->filesPath()."deleted/$dir/$file");
            unlink($path);
        }
        $dbFile = $this->data->find_by_filename("file", $file);
        if($dbFile) {
            if($dir == "images") {
                $image = $this->module("image")->data->find("file", $dbFile->id);
                if($image) {
                    $id = $image->id;
                    if(is_dir($this->data->filesPath()."images/edited/$id")) {
                        DirectoryRearranger::move($this->data->filesPath()."images/edited/$id", $this->data->filesPath()."deleted/images/edited/$id");
                    }
                    $image->delete();
                }
            }
            $dbFile->delete();
        }
        
        
        $this->response->addSuccess($this, $this->translate("The file %s has been deleted", "\"<i>$file</i>\""));
    }
    
    public function deleteTrash(Event $event) {
        $file = $event->getInfo("file");
        $dir = $event->getInfo("dir");
        $path = $this->data->filesPath()."deleted/$dir/$file";
        
        if(is_file($path) && $file{0} !== ".") {
            unlink($path);
        }
        $dbFile = $this->data->find("file", array("conditions" => array("filename = ? AND deleted = 1", $file)));
        if($dbFile) {
            if($dir == "images") {
            $image = $this->module("image")->data->find("file", $dbFile->id);
                if($image) {
                    $id = $image->id;
                    if(is_dir($this->data->filesPath()."deleted/images/edited/$id")) {
                        DirectoryRearranger::remove($this->data->filesPath()."deleted/images/edited/$id");
                    }
                    $image->delete(true);
                }
            }
            $dbFile->delete(true);
        }
        
        
        $this->response->addSuccess($this, $this->translate("The file %s has been completely deleted", "\"<i>$file</i>\""));
    }
    
    public function menu() {
        return array(
            "upload file" => "upload"
        );
    }
    
    
    public function defineBackend() {
        
        // get time
        $time = time();
        
        $olderThan = 60*60;
        // delete temp files (if they are older than $olderThan seconds)
        $tempDir = new \DirectoryIterator($this->data->filesPath()."temp");
        foreach($tempDir as $file) {
            if($file->isFile()) {
                $mTime = $file->getMTime();
                // if the file is older than $olderThan seconds, delete it
                if($time > $mTime + $olderThan) {
                    unlink($this->data->filesPath()."temp/".$file->getFilename());
                }
            }
        }
        
        
        
        $dirsData = array();
        
        foreach($this->dirs as $dir) {
            
            $files = new \DirectoryIterator($this->data->filesPath().$dir);
            $trashFiles = new \DirectoryIterator($this->data->filesPath()."deleted/".$dir);
            
            $dirsData[$dir] = array();
            $dirsTrashData[$dir] = array();
            
            foreach($files as $file) {
                if($file->isFile()) {
                    $mTime = $file->getMTime();
                    $dirsData[$dir][] = array(
                        "filename" => $file->getFilename(),
                        "dir" => $dir,
                        "updated" => $mTime,
                        "created" => $mTime
                    );
                }
            }
            
            foreach($trashFiles as $file) {
                if($file->isFile()) {
                    $mTime = $file->getMTime();
                    $dirsTrashData[$dir][] = array(
                        "filename" => $file->getFilename(),
                        "dir" => $dir,
                        "updated" => $mTime,
                        "created" => $mTime
                    );
                }
            }
        }
        
        if($this->hasPermission("upload file")) {
            $this->framework
                ->startGroup("LazyMainTab", array("title" => "Upload"))
                    ->input("Fileuploader");
        }
        
        
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Files"))
                ->startGroup("TabGroup");
                    foreach($dirsData as $title => $data) {
                        $this->framework
                            ->startGroup("TitledSection", array("title" => $title))
                                ->list("Finder", array(
                                    "renderer" => array(
                                        "name" => "FileRenderer",
                                        "events" => array(
                                            "module" => "fileupload",
                                            "event" => "delete"
                                        ),
                                        "isTrash" => false
                                    ),
                                    "data" => $data,
                                    "selectable" => false
                                ))
                                ->end();
                    }
                    
                    
        $this->framework
            ->startGroup("LazyMainTab", array("title" => "Trash"))
                ->startGroup("LazyTabGroup");
                    foreach($dirsTrashData as $title => $data) {
                        $this->framework
                            ->startGroup("TitledSection", array("title" => $title))
                                ->list("Finder", array(
                                    "renderer" => array(
                                        "name" => "FileRenderer",
                                        "events" => array(
                                            "module" => "fileupload",
                                            "event" => "deleteTrash"
                                        ),
                                        "isTrash" => true
                                    ),
                                    "data" => $data,
                                    "selectable" => false
                                ))
                                ->end();
                    }
    }
    
}

?>

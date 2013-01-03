<?php 
// originally written by max.fechner@gmail.com

 

class FileSystem {

    public $extensionCT = array(
        'svg'=>'image/svg+xml',
        'gif'=>'image/gif',
        'png'=>'image/png',
        'jpg'=>'image/jpg',
        'pdf'=>'application/pdf'
    );
    public $binaries = array('png','jpg','gif','pdf');
    
    
    
	function setContentType($fileName, $attachment=false){    
        $pi = pathinfo($fileName);
        $ct = $this->extensionCT[$pi['extension']];
        if($ct){
          header('Content-type: '.$ct);
          if($attachment){ header("Content-Disposition: attachment; filename='{$pi['basename']}'"); }
        }
    }
    
	function log($p){ echo "<pre>"; print_r($p); echo "</pre>"; }
    
    
    
	function asArray(){
    	return array('host'=>$this->host, 'username'=>$this->user, 'password'=>$this->pass);
    }
    
    
    // test for trailing SLASH -->> FOLDER
    function isFolder($name){
    	return $name[strlen($name)-1] == '/';
    }
    
    
    // returns a clean path
    public function clean($fileName){
    	return str_replace('//','/',$fileName);
    }
    
    // returns the extension of a fileName... missing in php
    public function extension($fileName){
        $pi = pathinfo($fileName); 
        return $pi['extension'];
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    // LIST folders recursively
    function ls($folder='/', $depth=1, $filter=''){  
    	return $this->lsRec($folder, $depth, $filter);
    }
    
    public function lsFlat($folder, $depth=1, $filter=''){
//    	if($depth==''){$depth=1;}
    	if($depth==0){return array();}
        $ret = array();
    	$ls = $this->ls1($folder, $filter);
        foreach($ls as $name=>$size){
        	$path = str_replace('//','/',$folder.'/'.$name);
        	if(is_array($size)){
//            	$ret[$path.'/'] = 'F';
            	$ret[] = $path.'/';
                $sub = $this->lsFlat($path, $depth-1, $filter);
            	foreach($sub as $v){ $ret[] = $v; }
//            	foreach($sub as $k=>$v){
//                	$ret[$k] = $v;
//                }
            } else {
            	$ret[] = $path;
            }
        }
        return $ret;
    }
    
    public function lsRec($folder, $depth=1, $filter=''){
//    	if($depth==''){$depth=1;}
    	if($depth==0){return array();}
    	$ls = $this->ls1($folder, $filter);
        foreach($ls as $name=>$size){
        	if(is_array($size)){
            	$ls[$name] = $this->lsRec($folder.'/'.$name, $depth-1, $filter);
            } 
        }
        return $ls;
    }
    
    
    
    
    
    
    // create file or folder
    public function create($ff){
    	if($this->isFolder($ff)){ 
        	return $this->createFolder($ff); 
        } else {
        	return $this->saveFile($ff);
        }
    
	} 
    
    public function save($fileName, $content='', $append=false){
    	if($append){ 
        	return $this->appendFile($fileName, $content); 
        } else {
        	return $this->saveFile($fileName, $content);
        }
    }
    
    
    
    // duplicate file or folder
//	function duplicate($file){ // incomplete
//    	$dst = $file.'2';
//    	if( !$this->exists($dst) ) {
//            $this->copy($file, $dst);
//        }
//    }
	
    
    
    
    
    
    
    // COPY files & folders, recursively
    // if $dst is empty, a duplicate is created
    public function copy($src, $dst=''){ 
    	$ls = $this->lsFlat($src,10); // up to 10 levels of recursion
        array_unshift($ls, $src);
        print_r($ls);
        foreach($ls as $item=>$meta){
	    	$to = str_replace($src,'',$item);
            echo $to."\n";
        	if($meta=='F'){ echo "copy Folder $dst/$to\n";
            	$this->createFolder($dst.'/'.$to);
            } else { echo "copy File $dst/$to\n";
            	$this->copyFile($item, $dst.'/'.$to);
            }
        }
    }
    
    
    
    
    
	// REMOVE files & folders recursively
    public function remove($name){
    	$ls = $this->lsFlat($name,10); // up to 10 levels of recursion
        $ls = array_reverse($ls);
        foreach($ls as $item=>$meta){
        	if($meta=='F'){
            	$this->removeFolder($item);
            } else {
            	$this->removeFile($item);
            }
        }
        $this->removeFolder($name);
        $this->removeFile($name);
    }
    
    
   
    
}
























// folders ALWAYS have a trailing "/"


/*
Each Sub-Class has to provide the following public methods

ls
load
save
delete

move
copy
exists
info


*/












    // can copy full folder-trees
    // can combine multiple files into one (if $dst is not a folder)
//    function copy($src, $dst) { 
//    	$oSrc = $this->isFolder($src) ? $src : dirname($src);
//    	if(!is_array($src)){
//        	if($this->isFolder($src)) { $src = $this->ls($src,5); }
//            else{ $src = array(basename($src)); }
//        }
////        print_r($src);
//        $agg = '';
//        foreach($src as $item){
//        	if($this->isFolder($item)){ continue; }
//            if($this->isFolder($dst)){
//            	echo "copy $oSrc$item --- $dst$item<br/>"; flush();
//	            $this->handle->copy($oSrc.$item, $dst.$item);
//            } else {
//            	echo "aggregate $oSrc$item<br/>"; flush();
//            	$agg .= $this->handle->load($oSrc.$item);
//            }
//        }
//        if($agg){
//            echo "save agg $dst<br/>";
//        	$this->handle->save($dst, $agg);
//        }
//    } 
 








//	function ls($folder='/',$depth=1, $filter=''){
//    	$folder = str_replace('//','/',$folder.'/');
//        $folder = trim($folder);
//    	$list = $this->sortLS($this->handle->ls($folder, $depth, $filter));
//        if($folder != '/'){ // remove the source-folder-part from the return-array
//            foreach ($list as $k=>$v){
////            	echo $folder.'  - '.$v;
//                $list[$k] = str_replace(ltrim($folder,'/'),'',ltrim($v,'/'));
//            }
//        }
//        return $list;
//    }
//    function sortLS($list){  // sort by extension... folders first in list
//        $files = array();
//        $folders = array();
//        foreach($list as $file){
//            if($this->isFolder($file)){ $folders[] = $file; continue; }
//            $pi = pathinfo($file);
//            $files[$pi['extension']][] = $file;
//        }
//        $files2 = array();
//        foreach($files as $ext=>$lst){
//            sort($lst);
//            $files2 = array_merge($files2,$lst);
//        }
//        sort($folders);
//        return array_merge($folders,$files2);
//    }
    
    
    
    
   
    
    
    
    
    
//	function delete($ff){ // delete file or folder, given as string or array
//    	$base = $ff;
//    	if(!is_array($ff)){
//        	if(substr($ff,-1)=='/'){
//                $ff = $this->handle->ls($ff,5);
//            } else {
//            	$ff = array($ff);
//            }
//        }
//        $folders = array();
//        foreach($ff as $file){
//            if($this->isFolder($file)){
//                $folders[] = $file;
//                continue;
//            }
////            echo "<br/>remove file: $file<br/>";
//            $this->handle->rm($file);
//        }
//        foreach(array_reverse($folders) as $folder){
////            echo "remove folder: $folder<br/>";
//            $this->handle->rmdir($base.$folder);
//        }
//        $this->handle->rmdir($base);
//        return true;
//    }










//    function recurseCopy($src,$dst) { 
//        $context = stream_context_create(array('ftp' => array('overwrite' => true)));
//        $dir = opendir($src); 
//        @mkdir($dst,0777,true); 
//        while(false !== ( $file = readdir($dir)) ) { 
//            if (( $file != '.' ) && ( $file != '..' )) { 
//                if ( is_dir($src . '/' . $file) ) { 
//                    recurse_copy($src . '/' . $file,$dst . '/' . $file); 
//                } 
//                else { 
//                  echo $src.'/'.$file."<br/>\n";
//                  flush();
//            file_put_contents($dst.'/'.$file,file_get_contents($src.'/'.$file),0,$context);
//            chmod($dst.'/'.$file,0777);
//    
//    /*                 copy($src.'/'.$file,$dst.'/'.$file, $context);  */
//                } 
//            } 
//        } 
//        closedir($dir); 
//    } 


    //    function fileType($fileName){
//      if(is_dir($fileName)) {return "dir";}
//      else{return extension($fileName);}
//    }
//    function extension($fileName){
//    	$tmp = pathinfo ($fileName); 
//        return trim($tmp['extension']);
//    }

        

?>
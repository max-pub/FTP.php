<?php
// originally written by max.fechner@gmail.com


class FTP extends FileSystem {



	// connect to server on object creation
    function __construct($host, $user, $pass='', $folder='/'){
    	$tmp = explode(':',$host);
    	$this->host = $tmp[0];
        $this->port = $tmp[1]?$tmp[1]:21;
        $this->user = $user;
        $this->pass = $pass;
        $this->folder = $folder;
        $this->string = (trim('ftp://'.urlencode($user).':'.$pass.'@'.$host.'/'.trim($folder,'/'),'/').'/');
        $this->con = @ftp_connect($this->host,$this->port); 
        $this->login = @ftp_login($this->con, $user, $pass);
        @ftp_chdir($this->con, $folder);
//        echo ftp_pwd($this->con);
//		echo 'folder: '.$this->folder;
        @ftp_pasv($this->con,true);
    }
     
    
    // close connection on object destruction
    function __destruct() {
        @ftp_close($this->con);
    }
    
    
    
    
    
    
    
    
    // test server-response, user-credentials, read/write - access
    public function status(){
        if($this->con) { $ret['server'] = true; } else { $ret['server'] = false; }
        if($this->login) { $ret['auth'] = true; } else { $ret['auth'] = false; }
        
        $ret['sys'] = ftp_systype($this->con);
        $ret['serverDir'] = @ftp_pwd($this->con);
          
        // READ TEST 
        $dir = ftp_rawlist($this->con, $this->folder);
        if($dir){ $ret['read'] = true; } else{ $ret['read'] = false; }
        
        // WRITE TEST
        $suc = @ftp_mkdir($this->con,'.codev-testFolder'); 
        $suc = @ftp_rmdir($this->con,'.codev-testFolder');
        if($suc){ $ret['write'] = true; } else{ $ret['write'] = false; }
        
        return $ret;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    // LOAD a file
	public function load($fileName){
    	$fileName = trim($fileName,'/');
    	$tmp = tmpfile();
        $mode = FTP_ASCII; if(in_array($this->extension($fileName), $this->binaries)){ $mode = FTP_BINARY; }
        ftp_fget($this->con, $tmp, $fileName, $mode); 
        return stream_get_contents($tmp, -1, 0);
//    	return file_get_contents($this->string.$fileName);
    }    
    
    
    
    
    
    
    
    
    
    // SAVE a file
	public function saveFile($fileName, $content=''){
    	$fileName = trim($fileName,'/');
        @mkdir($this->string.dirname($fileName),0777, true);
    	$tmp = tmpfile();
        fwrite($tmp, $content);
        fseek($tmp, 0);
        $mode = FTP_ASCII; if(in_array($this->extension($fileName), $this->binaries)){ $mode = FTP_BINARY; }
        $suc = ftp_fput($this->con, $fileName, $tmp, $mode); 
        return strlen($content);
    }
    // append to a file... is this necessary? has to be re-written!
    public function appendFile($fileName, $content){
    	$pi = pathinfo($fileName);
        @mkdir($this->string.$pi['dirname'], 0777, true);
        $bytes = file_put_contents($fileName, $content, FILE_APPEND);
        return $bytes;
    }
    
    
    
    
    
    
    
    
    // CREATE a folder
	public function createFolder ($folder) {
		if( substr_count($folder,'/')>1 ) {$rec = true;} else {$rec = false;} // stupid php behaviour
        return @mkdir($this->string.$folder, 0777, $rec); 
    }
    
   
    
    
    
    public function removeFile ($fileName){  // remove a file
        return ftp_delete ( $this->con , $this->clean($this->folder.'/'.$fileName) );
    }
	public function removeFolder ($fileName) { // remove an empty directory
        return ftp_rmdir($this->con, $this->clean($this->folder.'/'.$fileName) ); 
    }
    
    
    
    
    
    
    
    
    
    public function copyFile ($src, $dst){ // can copy files
    	return $this->save($dst,  $this->load($src));
    }
    
    
    
    
    
    
    
    
    
    // MOVE
    public function move ($src, $dst){ // can move/rename files & folders
    	echo trim($src,'/').' - '. trim($dst,'/');
    	return ftp_rename($this->con, trim($src,'/'), trim($dst,'/'));
    }
    
    
    
    
    
    
    
    
    // ACCESS - rights 
    // SET: $mode consists of three octet numbers
    // GET: if $mode is omitted, the current access-rights are returned
    public function access ($fileName, $mode=''){
    	if($mode) {
        	return @ftp_chmod ( $this->con ,octdec($mode) ,$fileName );
        } else {
        	$stat = stat($this->string.$fileName);
            return sprintf("%o", ($stat["mode"] & 000777));
        }
    }
    
    
    
    
    
    
    // META-INFO, returns size, access-rights, time of change of a file
    public function info($fileName){
    	$ret['change'] = filemtime($this->string.$fileName);
    	$ret['size'] = filesize($this->string.$fileName);
    	$ret['access'] = '777';
        return $ret;
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function ls1($folder, $filter=''){
//    	echo 'list1: '.$this->folder.'/'.$folder.'<br/>';
    	if(!$folder){return array();}
        $list = ftp_rawlist($this->con, "-a ".$this->folder.'/'.$folder."");
        if(!$list){return array();}
        $ret = array();
        foreach($list as $line){
            $tmp = $this->rawLineParser($line);
            $file = $tmp[2];
            if ($file=='.' or $file=='..'){continue;}
            if($filter[0]=='!'){ // filter out unwanted files... starting with "!"
                if(strpos($file, str_replace('!','',$filter) ) !== false){ continue; }//return array(); }
            }
            $cmp = trim(str_replace($this->folder,'',$file),'/ ');
            if($folder==$cmp){ continue; }
//            echo "TEST: $folder  --  $cmp -- ".$file;
            if($tmp[0]){ $ret[$file] = array(); }
            else{ $ret[$file] = $tmp[1]; }
        }
//        print_r($ret);
        return $ret;
    }
    
    // UNIX & WindowsNT servers have different folder-listing-outputs... those need to be parsed...
    private function rawLineParser($dirline){
        if(ereg("([-dl])[rwxst-]{9}",substr($dirline,0,10))) { 
            $systyp = "UNIX"; 
        } else { $systyp = "Windows_NT"; }
        if(substr($dirline,0,5) == "total") { 
            $dirinfo[0] = -1; 
        } elseif($systyp=="Windows_NT") { 
            if(ereg("[-0-9]+ *[0-9:]+[PA]?M? +<DIR> {10}(.*)",$dirline,$regs)) { 
                $dirinfo[0] = 1; 
                $dirinfo[1] = 0; 
                $dirinfo[2] = $regs[1]; 
            } elseif(ereg("[-0-9]+ *[0-9:]+[PA]?M? +([0-9]+) (.*)",$dirline,$regs)) { 
                $dirinfo[0] = 0; 
                $dirinfo[1] = $regs[1]; 
                $dirinfo[2] = $regs[2]; 
            } 
        } elseif($systyp=="UNIX") { 
            if(ereg("([-d])[rwxst-]{9}.* ([0-9]*) [a-zA-Z]+ [0-9: ]*[0-9] (.+)",$dirline,$regs)) { 
                if($regs[1]=="d")    $dirinfo[0] = 1; 
                $dirinfo[1] = $regs[2]; 
                $dirinfo[2] = $regs[3]; 
            } 
        } 
         
        if(($dirinfo[2]==".")||($dirinfo[2]=="..")) $dirinfo[0]=0; 
        
        return $dirinfo;
        // array -> 0 = switch, directory or not 
        // array -> 1 = filesize (if dir =0) 
        // array -> 2 = filename or dirname  
    }
     
     
     
     
     
    
}


//function handleError($errno, $errstr, $errfile, $errline, array $errcontext){
//    // error was suppressed with the @-operator
//    if (0 === error_reporting()) {
//        return false;
//    }
//
//    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
//}
//set_error_handler('handleError');





// OLD CH-MOD
//        $path = $ftp['folder'].$file;
//        
//        $fullSting = $ftpString.$file;
//        
//        if($_REQUEST['val']){
//             
//            echo "<pre>";
//            
//            
//            $fullSting = "ftp://f006617c:pW2yr8dNsdorNzXm@codev.it/demos.css";
//    
//            echo chmod($ftpString.$path, "0".$_REQUEST['val']);
//            print_r($ftp);
//             
//              
//            $mode = octdec( str_pad($_REQUEST['val'],4,'0',STR_PAD_LEFT) );
//            $conn_id = ftp_connect($ftp['server']);
//            $login_result = ftp_login($conn_id, $ftp['username'], $ftp['password']);
//            
//             Zugriffsrechte von $dateiname auf 644 ändern
//            if (ftp_chmod($conn_id, $mode, $path) !== false) {
//             echo "Zugriffsrechte der Datei $dateiname auf $mode geändert\n";
//            } else {
//             echo "Änderung der Zugriffsrechte fehlgeschlagen\n";
//            } 
//            
//             Verbindung schließen
//            ftp_close($conn_id);
//            
//            clearstatcache();
//            echo substr(sprintf('%o', fileperms($ftpString.$path)), -4);
//    
//    
//        } 
//        $stat= stat($fullSting);
//    
//        $mode = $stat['mode'];
//        
//        if ($mode & 0100000) {
//             file
//            echo "file";
//        }
//        if ($mode & 040000) {
//             folder
//            echo "folder";
//        } 
//           
//        $tmp = array('size'=>$stat['size'], 'permissions'=>substr(sprintf('%o', fileperms($fullSting)), -4) ); 
//        echo json_encode($tmp);






// OLD LS-REC
//    	if($folder[0]=='/'){ $folder = substr($folder,1); }
//		$folder = './'.$folder;
//		$folder = trim($folder,'/').'/';
//        if($folder=='/'){ $folder=''; }
//        $folder = str_replace('-',"\-",$folder);
//		if($folder[0]=='-'){$folder = "\\".$folder;}
//        echo '<br/>list: '.$folder.'<br/>';
//    	if(strpos($folder,$this->folder)!==0){$folder = $this->folder.'/'.$folder;} 
//		echo "FILTER $filter<br/>";
//        echo "load $folder<br/>";
//        $list = ftp_rawlist($this->con, "-a ".$folder."");
//        if(!$list){$list = array();}
//        print_r($list);
//        $folders = array();
//        $files = array();
//        foreach($list as $line){
//            $tmp = $this->rawLineParser($line);
//            $file = $tmp[2];
//            if ($file=='.' or $file=='..'){continue;}
//            if($filter[0]=='!'){  filter out unwanted files... starting with "!"
//                if(strpos($file, str_replace('!','',$filter) ) !== false){ continue; }return array(); }
//            }
//            if($tmp[0]){ $folders[] = $folder.'/'.$file.'/'; }
//            else{ $files[] = $folder.'/'.$file; }
//        }
//        
//        $ret = array_merge($folders,$files);
//        
//        if($depth>1){
//            foreach($folders as $f){
//		        echo "rec-load $f<br/>";
//                $ret = array_merge($ret, $this->ls($f, $depth-1, $filter) );
//            }
//        }
//        sort($ret);
//        foreach($ret as $k=>$v){  clean up
//	        $ret[$k] = str_replace('','/',ltrim($v,'/'));
//        }
//        return $ret;

?>
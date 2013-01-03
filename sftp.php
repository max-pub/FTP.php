<?php

class SFTP {
    function __construct($host,$user,$pass='',$folder='/'){
    	$this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->folder = $folder;
        $this->ssh = ssh2_connect ( $host );
        $this->login = ssh2_auth_password ( $this->ssh , $user, $pass );
        $this->con = ssh2_sftp($this->ssh);
        $this->string = "ssh2.sftp://{$this->con}/$folder/";
    } 
     
    // test server-response, user-credentials, read/write - access
    function testConnection(){
        if($this->con) { $ret['server'] = true; } else { $ret['server'] = false; }
        if($this->login) { $ret['auth'] = true; } else { $ret['auth'] = false; }
        
        return $ret;
    }
    
    
    function __destruct() {
    }
    
    // load a file
    function load($fileName){
		return file_get_contents($this->string.$fileName);
    }

    // save a file
    function save($fileName, $content=''){
		return file_put_contents($this->string.$fileName, $content);
    
    }
    
    
    
    
    // create a new directory
	function mkdir ($folder) {
		if( substr_count($folder,'/')>1 ) {$rec = true;} else {$rec = false;} // stupid php behaviour
        return @mkdir($this->string.$folder, 0777, $rec); 
    }
    
    
    
    
    
    // remove an empty directory
	function rmdir ($fileName) {
        return rmdir($this->string.$fileName); 
    }
    
    
    
    
    
	
    // remove a file
    function rm($fileName){
        return unlink ( $this->string.$fileName );
    }
    
    
    
    
	function ls($folder='/', $depth=1, $filter=''){
		// echo "\nopen $folder\n\n";
        $ret = array();
        // echo $this->string.$folder;
        $handle = opendir($this->string.$folder);
        // List all the files
        while (false !== ($file = readdir($handle))) {
            if ($file=='.' or $file=='..'){continue;}
	        // echo $this->string.$folder.$file;
	        if(is_dir($this->string.$folder.$file)){
				$ret[] = $folder.$file.'/';
	        } else {
		        $ret[]=$folder.$file;
	        }
        }
        closedir($handle);
        if($depth>1){
            foreach($ret as $f){
            	if($f[strlen($f)-1] != '/'){continue;}
                $ret = array_merge($ret, $this->ls($f, $depth-1, $filter) );
            }
        }
        foreach($ret as $k=>$v){ // clean up
	        $ret[$k] = str_replace('//','/',$v);
        }
        return $ret;
    }
}
?>
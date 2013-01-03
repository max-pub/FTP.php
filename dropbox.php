<?php

//$self = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

session_start();

class Dropbox extends FileSystem { 



	// connect to server on object creation
    function __construct($key, $secret, $full=false){
    	$this->key = $key;
        $this->secret = $secret;
        $this->full = $full;
        $this->oauth = new OAuthConsumer($key, $secret, NULL);
        $this->oauth->setMethod();
        $this->auth = false;
    }
    
    
    
    // close connection on object destruction
    function __destruct() {
    }
      
    
    
    function connect(){
        if(!$_COOKIE['dropboxAccessToken']){ 
            if(!$_GET['oauth_token']){
                $this->requestToken();
            } else {
                $this->accessToken();
            }
        } else {
        	$this->useToken();
        }
    }
    
    function disconnect(){
        setcookie('dropboxUID', '', time()+60*60*24*30, '/');
        setcookie('dropboxAccessToken', '', time()+60*60*24*30, '/');
        setcookie('dropboxAccessTokenSecret', '', time()+60*60*24*30, '/');
    }
    
    function requestToken(){
        $reqTok = $this->oauth->token("https://api.dropbox.com/1/oauth/request_token");
        $callBack = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?oauth_token_secret=".$reqTok['oauth_token_secret'];
        header("Location: https://www.dropbox.com/1/oauth/authorize?oauth_token={$reqTok['oauth_token']}&oauth_callback=$callBack");
    }
    
    
    function accessToken(){
        $this->oauth->setToken($_GET['oauth_token'], $_GET['oauth_token_secret']);
        $accTok = $this->oauth->token("https://api.dropbox.com/1/oauth/access_token");
        //$res = $oauth->getLastResponse();
        setcookie('dropboxUID',$accTok['uid'], time()+60*60*24*30, '/');
        setcookie('dropboxAccessToken',$accTok['oauth_token'], time()+60*60*24*30, '/');
        setcookie('dropboxAccessTokenSecret',$accTok['oauth_token_secret'], time()+60*60*24*30, '/');
        print_r($_GET);
//        $this->auth = true;
    }
    
    function useToken(){
        $this->oauth->setToken($_COOKIE['dropboxAccessToken'],$_COOKIE['dropboxAccessTokenSecret']);
//        echo $_COOKIE['dropboxAccessToken'].' --- '.$_COOKIE['dropboxAccessTokenSecret'];
    	$this->auth = true;
    }
    
    
    function accountInfo(){
        echo $this->oauth->request("https://api.dropbox.com/1/account/info");
    }
    
    function ls1($folder='/'){
    	if(!$this->auth){ return false; }
        $urlFolder = str_replace(' ','%20',$folder);
        $ls = $this->oauth->request("https://api.dropbox.com/1/metadata/sandbox".$urlFolder); 
        $ls = json_decode($ls);
//		echo "<pre>";print_r($ls);echo "</pre>";
        $ret = array();
        foreach($ls->contents as $item){
        	if($item->is_dir){
                $ret[] = str_replace($folder,'',$item->path).'/';
            } else {
                $ret[] = str_replace($folder,'',$item->path);
            }
        }
        return $ret;
    }
    
    
    function share($item){
        $itemUrl = str_replace(' ','%20',$item); 
        $res = $this->oauth->request("https://api.dropbox.com/1/shares/sandbox".$itemUrl);
        $ret = json_decode($res);
//        return $ret->url;
        $page = file_get_contents($ret->url);
        preg_match ( '@(https://dl.dropbox.*?)"@i', $page, $matches );
//        //print_r($matches);
//        echo $matches[1].$br;;
        $link = str_replace("https:","http:",$matches[1]);
    	return $link;
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
        $headers = array('Content-type'=>'application/x-www-form-urlencoded'
                        ,'Content-length'=>strlen($content)
                        ,'Connection'=>'close');
        $headers = array('Content-type'=>'text/plain');
        
        try{ $suc = $this->oauth->fetch("https://api-content.dropbox.com/1/files_put/sandbox/ordner/hello.txt", $content, OAUTH_HTTP_METHOD_PUT, $headers); }
        catch(Exception $e){echo $e;}
        echo 'file-upload-status: '.$suc;
        $res = json_decode($oauth->getLastResponse());
        print_r($res);
        
//        $suc = $this->oauth->put("https://api-content.dropbox.com/1/files_put/sandbox/ordner/test.txt", 'arschkacke', $headers); 
//        echo 'file-upload-status: '; print_r($suc);
        return $suc;
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
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
//    public function ls1($folder, $filter=''){
//    }
    
     
     
    
}

?>
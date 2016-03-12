<?php

//$self = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

session_start();

class Box extends FileSystem { 




	// connect to server on object creation
    function __construct($key, $full=false){
    	$this->key = $key;
        $this->full = $full;
        $this->token = "";
        $this->oauth = new OAuthConsumer($key, $secret, NULL);
        $this->oauth->setMethod();
        $this->auth = false;
    }
    
    
    
    // close connection on object destruction
    function __destruct() {
    }
      
    
    
    function connect(){
        if(!$_COOKIE['boxAccessToken']){ 
            if(!$_GET['auth_token']){
                $this->requestToken();
            } else {
                $this->accessToken();
            }
        } else {
        	$this->useToken();
        }
    }
    
    function requestToken(){
        $ticketrequest = $this->oauth->request("https://www.box.com/api/1.0/rest?action=get_ticket&api_key=".$this->key);
        $ticketobj = simplexml_load_string($ticketrequest);
        $ticket = $ticketobj->ticket[0];
//        $callBack = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?oauth_token_secret=".$reqTok['oauth_token_secret'];
        header("Location: https://www.box.com/api/1.0/auth/".$ticket);
    }
    
    
    function accessToken(){
    	$boxToken = $_GET['auth_token'];
//        $this->oauth->setToken($_GET['oauth_token'], $_GET['oauth_token_secret']);
//        $accTok = $this->oauth->token("https://www.box.com/api/1.0/rest?action=get_auth_token&api_key={your api key}&ticket={your ticket}");
        //$res = $oauth->getLastResponse();
        setcookie('boxAccessToken',$boxToken, time()+60*60*24*30, '/');
//        $this->auth = true;
    }
    
    function useToken(){
//        $this->oauth->setToken($_COOKIE['boxAccessToken'],$_COOKIE['boxAccessTokenSecret']);
//        echo $_COOKIE['dropboxAccessToken'].' --- '.$_COOKIE['dropboxAccessTokenSecret'];
//    	$this->auth = true;
        
        $this->token = $_COOKIE['boxAccessToken'];
    }
    
    
    function accountInfo(){
//        echo $this->oauth->request("https://api.dropbox.com/1/account/info");
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.box.com/api/2.0/folders/0");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, "TRUE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: BoxAuth api_key=".$this->key."&auth_token=".$this->token));
        print_r(curl_exec($ch));
        curl_close;
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
        $data = "yeah man first file on dropbox und so! noch mehr zeugSSSS";
        $headers = array('Content-type'=>'application/x-www-form-urlencoded'
                        ,'Content-length'=>strlen($data)
                        ,'Connection'=>'close');
        $headers = array('Content-type'=>'text/plain');
        $suc = $oauth->put("https://api-content.dropbox.com/1/files_put/sandbox/ordner/hello.txt", $data, $headers); 
        echo 'file-upload-status: '; print_r($suc);
        
//    	$fileName = trim($fileName,'/');
//        @mkdir($this->string.dirname($fileName),0777, true);
//    	$tmp = tmpfile();
//        fwrite($tmp, $content);
//        fseek($tmp, 0);
//        $mode = FTP_ASCII; if(in_array($this->extension($fileName), $this->binaries)){ $mode = FTP_BINARY; }
//        return ftp_fput($this->con, $fileName, $tmp, $mode); 
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
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
//    public function ls1($folder, $filter=''){
//    }
    
     
     
    
}

?>
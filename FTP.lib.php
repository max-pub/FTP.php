<?PHP
//git.max.pub


class FTP {



    // connect to server on object creation
    function __construct($host, $user, $pass=''){
        $tmp = explode(':',$host);
//        list($this->host,$this->port) = explode(':',$host);
        $this->host = $tmp[0];
        if(count($tmp)>1) $this->port = $tmp[1]; else $this->port = 21;
        $this->user = $user;
        $this->pass = $pass;
        $this->login = null;

        $this->connection = @ftp_connect($this->host,$this->port);
        if($this->connection) {
            ftp_pasv($this->connection, true);
            $this->login = @ftp_login($this->connection, $user, $pass);
        }
        $this->connectionString = trim('ftp://'.urlencode($user).':'.$pass.'@'.$host,'/').'/';
    }




    // close connection on object destruction
    function __destruct() {
        @ftp_close($this->connection);
    }




    public function status(){
        $status = array(
            'host' => $this->host,
            'connected' => $this->connection ? true: false,
            'user' => $this->user,
            'authenticated' => $this->login ? true: false,
        );
        if($this->connection){
            $status['system'] = ftp_systype($this->connection);
            if($this->login)
                $status['folder'] = $this->connection ? ftp_pwd($this->connection) : null;
        }
        return $status;
    }
//            'system' => $this->system,



    // test read/write - access
    public function test(){
        // READ TEST
        $dir = ftp_rawlist($this->connection, './');
        if($dir){ $ret['read'] = true; } else{ $ret['read'] = false; }

        // WRITE TEST
        $suc = @ftp_mkdir($this->connection,'TestFolder.FTP.php');
        $suc = @ftp_rmdir($this->connection,'TestFolder.FTP.php');
        if($suc){ $ret['write'] = true; } else{ $ret['write'] = false; }

        return $ret;
    }





    public function ls($folder){ // LIST
        if(!$folder){return array();}
        $list = ftp_rawlist($this->connection, "-a ".$folder);
        if(!$list){return array();}
        $return = array();
        foreach($list as $line){
            $item = $this->_RawListLineParser($line);
            if(!$item) continue;
            if($item['name']=='.') continue;
            if($item['name']=='..') continue;
            if($item['type']=='folder') unset($item['size']);
//            echo "<br/>";
//            print_r($item);
            $return[] = $item;
        }
        return $return;
    }

    private function _RawListLineParser($line){
        $chunks = preg_split("/\s+/", $line,9);
        list($access, $number, $user, $group, $size, $month, $day, $time, $name) = $chunks;
        $type = $access[0] === 'd' ? 'folder' : 'file';
        return array(
            'name'      => $name,
            'size'      => $type=='folder' ? 0 : $size,
            'modified'  => date('Y-m-d H:i:s', strtotime($month.' '.$day.' '.$time)),
            'user'      => $user,
            'group'     => $group,
            'access'    => $access,
            'type'      => $type
            );
    }






    public function LOAD($fileName){
        $fileName = trim($fileName,'/');
        $tmp = tmpfile();
        $mode = FTP_ASCII;
//        if(in_array($this->extension($fileName), $this->binaries))
//            $mode = FTP_BINARY;
        ftp_fget($this->con, $tmp, $fileName, $mode);
        return stream_get_contents($tmp, -1, 0);
//        return file_get_contents($this->string.$fileName);
    }






    public function SAVE($fileName, $content=''){
        $fileName = trim($fileName,'/');
        @mkdir($this->string.dirname($fileName),0777, true);
        $tmp = tmpfile();
        fwrite($tmp, $content);
        fseek($tmp, 0);
        $mode = FTP_ASCII;
//        if(in_array($this->extension($fileName), $this->binaries))
//            $mode = FTP_BINARY;
        $suc = ftp_fput($this->con, $fileName, $tmp, $mode);
        return strlen($content);
    }



    public function MOVE ($src, $dst){ // can move/rename files & folders
        echo trim($src,'/').' - '. trim($dst,'/');
        return ftp_rename($this->con, trim($src,'/'), trim($dst,'/'));
    }


    // delete files & folders
    public function KILL($item){
    }
    public function removeFile ($fileName){  // remove a file
        return ftp_delete ( $this->con , $fileName );
    }
    public function removeFolder ($fileName) { // remove an empty directory
        return ftp_rmdir($this->con, $fileName );
    }





    // CREATE a folder
    public function mkdir ($folder) {
//        if( substr_count($folder,'/')>1 ) {$rec = true;} else {$rec = false;} // stupid php behaviour
//        return @mkdir($this->connectionString.$folder, 0777, $rec);
//        return @mkdir($this->connectionString.'/'.$folder, 0777);
        return ftp_mkdir($this->connection, $folder);
    }







    // just a shortcut of LOAD + SAVE
    public function copy ($src, $dst){
        return $this->SAVE($dst,  $this->LOAD($src));
    }








    // ACCESS - rights
    // SET: $mode consists of three octet numbers
    // GET: if $mode is omitted, the current access-rights are returned
    public function access ($fileName, $mode=''){
        if($mode) {
            return @ftp_chmod ( $this->connection ,octdec($mode) ,$fileName );
        } else {
            $stat = stat($this->connectionString.$fileName);
            return sprintf("%o", ($stat["mode"] & 000777));
        }
    }




//        $item['type'] = $chunks[0]{0} === 'd' ? 'folder' : 'file';
//        array_splice($chunks, 0, 8);
//        $item['name'] = implode(" ", $chunks);

//        $items[implode(" ", $chunks)] = $item;
//        return $items;
//            $file = $tmp[2];
//            if ($file=='.' or $file=='..'){continue;}
//            $cmp = trim(str_replace($this->folder,'',$file),'/ ');
//            if($folder==$cmp){ continue; }
//            echo "TEST: $folder  --  $cmp -- ".$file;
//            if($tmp[0]){ $ret[$file] = array(); }
//            else{ $ret[$file] = $tmp[1]; }


//   function listDetailed($resource, $directory = '.') {
//        if (is_array($children = @ftp_rawlist($resource, $directory))) {
//            $items = array();
//
//            foreach ($children as $child) {
//                $chunks = preg_split("/\s+/", $child);
//                list($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time']) = $chunks;
//                $item['type'] = $chunks[0]{0} === 'd' ? 'directory' : 'file';
//                array_splice($chunks, 0, 8);
//                $items[implode(" ", $chunks)] = $item;
//            }
//
//            return $items;
//        }
//   }



//    // UNIX & WindowsNT servers have different folder-listing-outputs... those need to be parsed...
//    private function _RawListLineParser2($dirline){
//        if(preg_match("([-dl])[rwxst-]{9}",substr($dirline,0,10))) {
//            $systyp = "UNIX";
//        } else { $systyp = "Windows_NT"; }
//        echo 'sys:'.$sytyp;return;
//
//        if(substr($dirline,0,5) == "total") {
//            $dirinfo[0] = -1;
//        } elseif($systyp=="Windows_NT") {
//            if(ereg("[-0-9]+ *[0-9:]+[PA]?M? +<DIR> {10}(.*)",$dirline,$regs)) {
//                $dirinfo[0] = 1;
//                $dirinfo[1] = 0;
//                $dirinfo[2] = $regs[1];
//            } elseif(ereg("[-0-9]+ *[0-9:]+[PA]?M? +([0-9]+) (.*)",$dirline,$regs)) {
//                $dirinfo[0] = 0;
//                $dirinfo[1] = $regs[1];
//                $dirinfo[2] = $regs[2];
//            }
//        } elseif($systyp=="UNIX") {
//            if(ereg("([-d])[rwxst-]{9}.* ([0-9]*) [a-zA-Z]+ [0-9: ]*[0-9] (.+)",$dirline,$regs)) {
//                if($regs[1]=="d")    $dirinfo[0] = 1;
//                $dirinfo[1] = $regs[2];
//                $dirinfo[2] = $regs[3];
//            }
//        }
//
//        if(($dirinfo[2]==".")||($dirinfo[2]=="..")) $dirinfo[0]=0;
//
//        return $dirinfo;
//        // array -> 0 = switch, directory or not
//        // array -> 1 = filesize (if dir =0)
//        // array -> 2 = filename or dirname
//    }





}

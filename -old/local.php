<?
class LocalFS extends FileSystem{


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

}
?>
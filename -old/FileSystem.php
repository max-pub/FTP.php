<?
class FileSystem {
	
    function __construct($baseFolder){
    	$this->baseFolder = realpath($baseFolder).'/';
    }

    function accessString($p){
        $ts=array(
          0140000=>'ssocket',
          0120000=>'llink',
          0100000=>'-file',
          0060000=>'bblock',
          0040000=>'ddir',
          0020000=>'cchar',
          0010000=>'pfifo'
        );
        $t=decoct($p & 0170000); // File Encoding Bit
        $str =(array_key_exists(octdec($t),$ts))?$ts[octdec($t)]{0}:'u';
        $str.=(($p&0x0100)?'r':'-').(($p&0x0080)?'w':'-');
        $str.=(($p&0x0040)?(($p&0x0800)?'s':'x'):(($p&0x0800)?'S':'-'));
        $str.=(($p&0x0020)?'r':'-').(($p&0x0010)?'w':'-');
        $str.=(($p&0x0008)?(($p&0x0400)?'s':'x'):(($p&0x0400)?'S':'-'));
        $str.=(($p&0x0004)?'r':'-').(($p&0x0002)?'w':'-');
        $str.=(($p&0x0001)?(($p&0x0200)?'t':'x'):(($p&0x0200)?'T':'-'));
        return $str;
    }
    function fileInfo($file){
        $stat = stat($file);
        $name = pathinfo($file);
        $info['file'] = $name['basename'];
        $info['fullpath'] = substr(realpath($file),0,-strlen($info['file']));
        $info['path'] = substr($info['fullpath'], strlen($this->baseFolder));//$_GET['list'];
        if(!$info['path']) $info['path'] = '';
        $info['level'] = substr_count($info['path'],'/');
        $info['baseFolder'] = $this->baseFolder;
        $info['name'] = $name['filename'];
        if(!is_dir($file)){
//            if(is_executable($file)) $info['flags'][] = 'executable';
            $info['extension'] = $name['extension'];
            $info['size'] = $stat['size'];
            if($stat['size']>1024*1024) $info['sizeString'] = round($stat['size']/1024/1024).' M';
            else if($stat['size']>1024) $info['sizeString'] = round($stat['size']/1024).' K';
            else $info['sizeString'] = $stat['size'].' B';
            
        }
    //	$info['size'] = filesize($file);
        $info['type'] = filetype($file);
        $info['blocksize'] = $stat['blksize'];
        $info['blocks'] = $stat['blocks'];
        $info['links'] = $stat['nlink'];
        $info['inode'] = $stat['ino'];
        $info['accessCode'] = substr(sprintf('%o', $stat['mode']), -4);
        $info['accessString'] = $this->accessString($stat['mode']);
        $owner = posix_getpwuid($stat['uid']);
        $info['owner'] = $owner['name'];
        $group = posix_getgrgid($stat['gid']);
        $info['group'] = $group['name'];
        $info['accessed'] = date('Y-m-d H:i:s',$stat['atime']);
        $info['modified'] = date('Y-m-d H:i:s',$stat['mtime']);
        $info['created'] = date('Y-m-d H:i:s',$stat['ctime']);
        
    //    $info['process'] = posix_getpwuid(posix_geteuid());
    //    $info['procgr'] = posix_getgrgid(posix_getegid());
        
    //    if(is_readable($file)) $info['flags'][] = 'readable';
    //    if(is_writable($file)) $info['flags'][] = 'writeable';
    //    if(is_dir($file)) $info['flags'][] = 'directory';
    //    if(is_file($file)) $info['flags'][] = 'file';
    //    if(is_link($file)) $info['flags'][] = 'link';
        return $info;
    }
    
    
    function ls($folder){
        $files = scandir($this->baseFolder.$folder);
        foreach($files as $file)
            if( ($file != '.') && ($file != '..') )
            	$out[] = $this->fileInfo($this->baseFolder.$folder.'/'.$file);
    	return $out;
    }
}



?>

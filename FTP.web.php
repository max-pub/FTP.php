<?
header('Content-Type: Application/JSON');
error_reporting(E_ALL);
//phpinfo(); exit();



//$list = scandir('ftp://'.urlencode($_GET['user']).':'.$_GET['pass'].'@'.$_GET['host'].'/'); //print_r($list); //exit;

include("FTP.lib.php");

$ftp = new FTP($_REQUEST['host'], $_REQUEST['user'], $_REQUEST['pass']);


switch($_REQUEST['do']){
    default:
    case 'status':
        $out = $ftp->status();
        break;
    case 'list':
        $out = $ftp->ls($_REQUEST['folder']);
        break;
    case 'mkdir':
        $out = $ftp->mkdir($_REQUEST['name']);
        break;
}

echo json_encode($out);

//
//if(isset($_REQUEST['status'])){
//    echo json_encode($ftp->status());
//}
//
//if(isset($_REQUEST['list'])){
//    echo json_encode($ftp->ls($_REQUEST['list']));
//}
//
//if(isset($_REQUEST['mkdir'])){
//    echo json_encode($ftp->mkdir($_REQUEST['mkdir']));
//}


?>

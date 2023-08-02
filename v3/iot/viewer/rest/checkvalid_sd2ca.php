<?php
include "../randgen.php";

if(isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
}

class CheckValid {
    protected $ikey;
    var $rtn = array();
    var $serverkey;
    var $id_sd = -1;
    
    function __construct() {
        include("../opendb.php");
    }
   
    function __destruct() {
        include("../closedb.php");
    }
       
    //------------------------------------------
    // 기본 인증 방법 
    //------------------------------------------
    function check_validkey($chkkey) {
        mysql_select_db('seciotdb') or die('Could not select database');
        $query = "SELECT smartdevice_id FROM smartdevices WHERE validation_key='" . $chkkey . "'";
        $result = mysql_query($query) or die('Query failed: ' . mysql_error());
        
        $cnt=0;
        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
            $id = $row[0];
            $cnt++;
        }
        return ( $cnt > 0 ? true:false );
    }
    
    function iotdevices($chkkey) {
        $d0 = array();
        $d1 = array();
        $sensor_name = '';
        $sensor_image = '';
        $sensor_sn = '';
        
        mysql_select_db('seciotdb') or die('Could not select database');
        $query = "SELECT smartdevice_id FROM smartdevices WHERE validation_key='" . $chkkey . "'";
        $result = mysql_query($query) or die('Query failed: ' . mysql_error());
        $cnt=0;
        
        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
            $id = $row[0];
        }
        $this->id_sd = $id;
              
        // For debugging
        // $d1 = array( "cnt" => $cnt);
        // array_push($d0, $d1);
        // $d1 = array( "query" => $query);
        // array_push($d0, $d1);
        // $d1 = array( "id_sd" => $id_sd);
        // array_push($d0, $d1);
          
        $query = "SELECT sensor_name, mqtt_topicid, sensor_sn FROM sensors WHERE smartdevice_id='" . $id . "'";
        $result = mysql_query($query) or die('Query failed: ' . mysql_error());

        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
            $sensor_name = $row[0];
            $mqtt_topicid = $row[1];
            $sensor_sn = $row[2];
            
            $d1 = array("sensor_name" => $sensor_name, "mqtt_topicid" => $mqtt_topicid, "sensor_sn" => $sensor_sn );
            array_push($d0, $d1);
        }
        return $d0;
    }
    
    function validation_basic() {
        //$this->serverkey = $this->encrypt_basic();
        $chkkey = $_SERVER["HTTP_KETI_TOKEN"];    
        if( $this->check_validkey( $chkkey ) ) {
            
            $rtn["is_valid"] = true;
            $rtn["randval"] = randgen(32);
            $iotdevices = $this->iotdevices( $chkkey );
            $rtn["dbg3"] =$iotdevies;
            $rtn["iotdevices"] = json_encode($iotdevices);
            $rtn["dbg2"] = json_encode($iotdevices);

            // 센서들의 인증 상태를 초기화하도록 DB갱신 
            // 정상 작동하는 코드임, 임시로 끔 (2018.08.09)
            $sql = "UPDATE sensors SET sensor_status='". false ."' WHERE smartdevice_id='". strval($this->id_sd) ."' ";
            // 
            // 여기 고칠 것 !!!!!!
            // TODO, 임시로 smartdevice_id = 1에서 끄도록 수정
            $sql = "UPDATE sensors SET sensor_status='". false ."' WHERE smartdevice_id='". strval(1) ."' ";




            $result = mysql_query($sql) or die('Query failed: ' . mysql_error());
                        
            // 인증을 위한 난수값을 DB에 설정  
            $sql = "UPDATE smartdevices SET randnum='". $rtn["randval"] ."' WHERE validation_key='". $chkkey ."' ";
            $result = mysql_query($sql) or die('Query failed: ' . mysql_error());
            
        } else{
            $rtn["is_valid"] = false;
            $rtn["randval"] = -1;
            $iotdevices = array(
                array(
                "model" => "(sample) iphone 18 a",
                "name" => "ios",
                "share" => 57.56,
                ),
                array(
                "model" => "(sampl) iphone 18 b",
                "name" => "ios",
                "share" => 57.56,
                )
            );
            
            $rtn["iotdevices"] = json_encode($iotdevices);
        }
        
        $rtn["dbg1"] = $chkkey;
        
        return $rtn;
    }
    
    //------------------------------------------
    // Time To Live 기능 추가된 인증방법 : NEXT VERSION
    //------------------------------------------
    function encrypt_ttl() {
        $key = $this->ikey;
        $m = round(date("i"));
        for($i=0;$i<$m;$i++) {
            //$key = hash("sha256", $key);
            $key = hash("sha1", $key);
        }
        //$subkey = hash("sha256", $key);
        $subkey = hash("sha1", $key);

        $rtn["key"] = $key;
        $rtn["skey"] = $skey;
        return $rtn;
    }
    
    function validation_ttl() {
        $chk = $this->encrypt_ttl();
        return ($chk["key"]==$_SERVER["HTTP_KETI_TOKEN"] || $chk["skey"]==$_SERVER["HTTP_KETI_TOKEN"]?true:false);
    }
}

$cv = new CheckValid();
echo json_encode($cv->validation_basic());
?>

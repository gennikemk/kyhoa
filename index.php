<?php 
$DEFINE = TRUE;
require("include/config.php");
;
echo '';
$VersionNewiMC = file_get_contents("backend/version.info");
if ($VersionNewiMC == "v1.0") {
$NewConfig["topup_system"] = array(
"system"=>"tmtopup",
"tmtopup"=>array(
"passkey"=>$Config['site']['tmtopup_keypass'],
"uid"=>$Config['site']['tmtopup_uid'],
),
"tmpay"=>array(
"merchant_id"=>"0",
),
);
$NewConfig["feature"] = array(
"skin"=>"no",
);
$NewConfig["site"] = array(
"title"=>$Config['site']['title'],
"short_title"=>$Config['site']['short_title'],
"baseurl"=>$Config['site']['baseurl'],
"installed"=>$Config['site']['installed'],
);
$ConfigiMC->UpdateArrayConfig("topup_system",$NewConfig["topup_system"]);
$ConfigiMC->UpdateArrayConfig("feature",$NewConfig["feature"]);
$ConfigiMC->UpdateArrayConfig("site",$NewConfig["site"]);
@mysqli_query($conn,"ALTER TABLE `shop_topup` CHANGE  `status`  `status` ENUM(  'success',  'invaild',  'used',  'pending' ) NOT NULL DEFAULT  'pending';");
@mysqli_query($conn,"ALTER TABLE `shop_topup` ADD  `topup_with` ENUM(  'tmtopup',  'tmpay' ) NOT NULL DEFAULT  'tmtopup' AFTER  `status` ;");
file_put_contents("backend/version.info","v1.1");
DisplayMSG("success","Update system v1.0 to v1.1 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.1") {
@mysqli_query($conn,"ALTER TABLE  `shop_topup` ADD  `topup_with` ENUM(  'tmtopup',  'tmpay' ) NOT NULL DEFAULT  'tmtopup' AFTER  `status` ;");
@mysqli_query($conn,"ALTER TABLE  `".$Config["minecraft"]["authme"]["database"] ."` ADD  `forgot_pwd_question` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `point` , ADD  `forgot_pwd_answer` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER  `forgot_pwd_question` ;");
@mysqli_query($conn,"ALTER TABLE  `".$Config["minecraft"]["authme"]["database"] ."` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci; ;");
$NewConfig["forgot_pwd"] = array(
"forgot_pwd"=>array(
"ชื่อแม่ของฉัน","ชื่อพ่อของฉัน","สถานที่เกิดของฉัน","ชื่อดาราคนโปรดของฉัน","ยี่ห้อคอมพิวเตอร์ของฉัน","อาหารจานโปรดของฉัน","เลขบัตรประชาชน",
),
);
$ConfigiMC->UpdateArrayConfig("feature",$NewConfig["forgot_pwd"]);
file_put_contents("backend/version.info","v1.2");
DisplayMSG("success","Update system v1.1 to v1.2 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.2") {
$InsertTable = "CREATE TABLE IF NOT EXISTS `shop_redeem` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `type` enum('point','item') NOT NULL DEFAULT 'point',
  `code` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `status` enum('pending','used','times') CHARACTER SET latin1 NOT NULL DEFAULT 'pending',
  `used_data` text,
  `time_create` int(25) DEFAULT NULL,
  `time_update` int(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
@mysqli_query($conn,$InsertTable);
$NewConfigTopup = array(
"20"=>"20",
"50"=>$Config["topup"]["50"],
"90"=>$Config["topup"]["90"],
"150"=>$Config["topup"]["150"],
"300"=>$Config["topup"]["300"],
"500"=>$Config["topup"]["500"],
"1000"=>$Config["topup"]["1000"],
);
$ConfigiMC->UpdateArrayConfig("topup",$NewConfigTopup);
$NewConfigTopupEvent = array(
"event"=>"false",
"20"=>array("date_start"=>"","date_end"=>"","point"=>"","item"=>"","desc"=>""),
"50"=>array("date_start"=>"","date_end"=>"","point"=>"","item"=>"","desc"=>""),
"90"=>array("date_start"=>"","date_end"=>"","point"=>"","item"=>"","desc"=>""),
"150"=>array("date_start"=>"","date_end"=>"","point"=>"","item"=>"","desc"=>""),
"300"=>array("date_start"=>"","date_end"=>"","point"=>"","item"=>"","desc"=>""),
"500"=>array("date_start"=>"","date_end"=>"","point"=>"","item"=>"","desc"=>""),
"1000"=>array("date_start"=>"","date_end"=>"","point"=>"","item"=>"","desc"=>""),
);
$ConfigiMC->UpdateArrayConfig("topup_event",$NewConfigTopupEvent);
file_put_contents("backend/version.info","v1.3");
DisplayMSG("success","Update system v1.2 to v1.3 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.3") {
@mysqli_query($conn,"ALTER TABLE  `shop_item` ADD  `server_id` INT( 10 ) NOT NULL DEFAULT  '0' AFTER  `cat_id` ;");
@mysqli_query($conn,"ALTER TABLE  `".$Config["minecraft"]["authme"]["database"] ."` ADD  `random_data` VARCHAR( 50 ) NULL DEFAULT NULL AFTER  `point` ;");
@mysqli_query($conn,"ALTER TABLE  `shop_history` CHANGE  `type`  `type` ENUM(  'buyitem',  'random' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;");
$ConfigiMC->UpdateConfig("feature","bungeecord","no");
$NewConfigBungeeCord = array(
array("id"=>"lobby","name"=>"Lobby Server","ip"=>"127.0.0.1","query_port"=>25566,"rcon_port"=>25576,"rcon_password"=>"NewiMC1234"),
array("id"=>"game","name"=>"Game Server","ip"=>"127.0.0.1","query_port"=>25567,"rcon_port"=>25577,"rcon_password"=>"NewiMC1234"),
);
$ConfigiMC->UpdateConfig("feature","bungeecord_server",$NewConfigBungeeCord);
$NewConfigBungeeCord_default = array(
"ip"=>"127.0.0.1",
"query_port"=>25565
);
$ConfigiMC->UpdateConfig("feature","bungeecord_default",$NewConfigBungeeCord_default);
$ConfigiMC->UpdateConfig("feature","random","no");
$NewConfigRandom = array(
"setting"=>array("type"=>"time","point_value"=>10,"time_value"=>300,"server"=>"default"),
"data"=>array(
array("percentage"=>25,"type"=>"point","value"=>array(10,50)),
array("percentage"=>20,"type"=>"point","value"=>array(50,100)),
array("percentage"=>5,"type"=>"point","value"=>array(100,150)),
array("percentage"=>10,"type"=>"command","value"=>array("give <player> 265 10","give <player> 266 5")),
array("percentage"=>10,"type"=>"command","value"=>array("give <player> 265 32","give <player> 266 15")),
array("percentage"=>10,"type"=>"command","value"=>array("give <player> 265 64","give <player> 266 32")),
array("percentage"=>5,"type"=>"command","value"=>array("give <player> 266 16","give <player> 264 16")),
array("percentage"=>5,"type"=>"command","value"=>array("give <player> 266 32","give <player> 264 32")),
array("percentage"=>5,"type"=>"command","value"=>array("give <player> 266 64","give <player> 264 64")),
array("percentage"=>5,"type"=>"command","value"=>array("give <player> 266 64","give <player> 264 64","give <player> 276 1")),
),
);
$ConfigiMC->UpdateConfig("feature","random_setting",$NewConfigRandom);
file_put_contents("backend/version.info","v1.4");
DisplayMSG("success","Update system v1.3 to v1.4 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.4") {
file_put_contents("backend/version.info","v1.5");
DisplayMSG("success","Update system v1.4 to v1.5 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.5") {
file_put_contents("backend/version.info","v1.6");
DisplayMSG("success","Update system v1.5 to v1.6 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.6") {
@mysqli_query($conn,"CREATE TABLE IF NOT EXISTS `shop_truewallet` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `claim_by` varchar(50) DEFAULT NULL,
  `claim_on` datetime DEFAULT NULL,
  `claim_ip` varchar(25) DEFAULT NULL,
  `transaction_id` bigint(20) DEFAULT NULL,
  `transfer_date` varchar(20) DEFAULT NULL,
  `transfer_phone` varchar(10) DEFAULT NULL,
  `transfer_amount` double(64,2) DEFAULT NULL,
  `log_value` varchar(255) DEFAULT NULL,
  `hash` varchar(50) DEFAULT NULL,
  `time_create` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");
$NewConfigTruewallet = array(
"function"=>"no",
"phone"=>"0000000000",
"server"=>"default",
"email"=>"wallet_email@email.com",
"password"=>"wallet_password",
"data"=>array(
10 =>array("details"=>"TrueWallet 10฿","point"=>15,"command"=>NULL),
50 =>array("details"=>"TrueWallet 50฿","point"=>75,"command"=>NULL),
100 =>array("details"=>"TrueWallet 100฿ + Diamond x10","point"=>150,"command"=>array("give <player> 264 10")),
300 =>array("details"=>"TrueWallet 300฿ + Diamond x32","point"=>450,"command"=>array("give <player> 264 32")),
500 =>array("details"=>"TrueWallet 500฿ + Diamond x64","point"=>750,"command"=>array("give <player> 264 64")),
1000 =>array("details"=>"TrueWallet 1,000฿ + Diamond x64 + Diamond Sword x3","point"=>750,"command"=>array("give <player> 264 64","give <player> 276 3")),
),
);
$ConfigiMC->UpdateArrayConfig("truewallet",$NewConfigTruewallet);
file_put_contents("backend/version.info","v1.7");
DisplayMSG("success","Update system v1.6 to v1.7 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.7") {
$ItemQuery = @mysqli_query($conn,"SELECT * FROM `shop_item`");
while ($ItemArr = mysqli_fetch_assoc($ItemQuery)) {
if (!is_array(json_decode($ItemArr["value"],TRUE))) {
$NewValue = "";
if ($ItemArr["type"] == "item") {
$NewValue = array("give <player> ".$ItemArr["value"] ." ".$ItemArr["count"]);
}else {
$NewValue = array($ItemArr["value"]);
}
@mysqli_query($conn,"UPDATE `shop_item` SET `value`='".json_encode($NewValue) ."' WHERE `id`='".$ItemArr["id"] ."' LIMIT 1;");
}
}
sleep(1);
@mysqli_query($conn,"ALTER TABLE `shop_item` DROP `type`;");
@mysqli_query($conn,"ALTER TABLE `shop_item` CHANGE  `value`  `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;");
file_put_contents("backend/version.info","v1.8");
DisplayMSG("success","Update system v1.7 to v1.8 success.","index.php");
exit();
}
if ($VersionNewiMC == "v1.8") {
$NewConfigSetting = array(
"member_register"=>"yes",
"member_forgot"=>"yes",
"online_check"=>"yes",
"site"=>"no"
);
$ConfigiMC->UpdateConfig("site","function",$NewConfigSetting);
$UpdateSQL = "ALTER TABLE  `shop_truewallet` CHANGE  `claim_on`  `claim_on` DATETIME NULL DEFAULT NULL , CHANGE  `time_create`  `time_create` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ;";
@mysqli_query($conn,$UpdateSQL);
file_put_contents("backend/version.info","v1.9");
DisplayMSG("success","Update system v1.8 to v1.9 success.","index.php");
exit();
}
;
echo '';
if ($_SESSION["installed"] == "true") {
@unlink("install.php");
@unlink("installed.php");
$_SESSION["installed"] = "";
}else {
if ($Config["site"]["installed"] == "false") {
if (file_exists("install.php")) {
header("Location: install.php");
}
}
}
;
echo '';
require_once('include/class.license.php');
if ($_GET["action"] == "license") :
$domain = str_replace('www.','',$_SERVER['HTTP_HOST']);
$ip = $_SERVER['SERVER_ADDR'];
if ($_POST["do"] == "register") {
if (file_exists("newimc.key")) {
if (!@unlink("newimc.key")) {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถ Delete Key ได้, กรุณาลบไฟล์ imc.key ออกจากระบบ")));
}
}
$license = new GamerXP_License("New_iMC");
$registerinfo = $license->registerkey($_POST["key"]);
if ($registerinfo['info'] == 'success') {
$ConfigiMC->UpdateConfig("license","key",$_POST["key"]);
exit(json_encode(array("result"=>true,"msg"=>"ลงทะเบียน Register Key นี้เรียบร้อยแล้ว <br> กรุณา Refresh หน้านี้ใหม่")));
}elseif ($registerinfo['info'] == 'expire') {
exit(json_encode(array("result"=>false,"msg"=>"Register Key นี้้หมดอายุการใช้งานไปแล้ว")));
}elseif ($registerinfo['info'] == 'ip_lock') {
exit(json_encode(array("result"=>false,"msg"=>"IP <strong>'".$ip ."'</strong> นี้ไม่ถูกต้อง")));
}elseif ($registerinfo['info'] == 'domain_lock') {
exit(json_encode(array("result"=>false,"msg"=>"Domain <strong>'".$domain ."' </strong> นี้ไม่ถูกต้อง")));
}elseif ($registerinfo['info'] == 'license_invaild') {
exit(json_encode(array("result"=>false,"msg"=>"Register Key <strong>'".$_POST["key"] ."'</strong> นี้ไม่ถูกต้อง")));
}
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถดำเนินการต่อไปได้ (ERROR: ".$registerinfo['info'] .")")));
}
exit();
endif;
;
echo '';
if ($_GET["do"] == "playeronline") :
$ServerQuery = ServerQuery();
echo json_encode($ServerQuery,JSON_UNESCAPED_UNICODE);
exit();
endif;
if ($Config["site"]["function"]["site"] == "yes") {
DisplayMSG("danger","Website is not available right now.<br>เว็บไซต์ไม่พร้อมให้บริการในขณะนี้");
}
;
echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>';
echo $Config['site']['title'];
echo '</title>
';
include("pages/inc.meta.php");
;
echo '<link rel="stylesheet" href="dist/css/bootstrap.css?v=';
echo $VersionNewiMC;
echo '">
<link rel="stylesheet" href="dist/css/style.css?v=';
echo $VersionNewiMC;
echo '">
<script src="dist/js/jquery.min.js?v=';
echo $VersionNewiMC;
echo '"></script>
<script src="dist/js/bootstrap.min.js?v=';
echo $VersionNewiMC;
echo '"></script>
<script src="dist/js/smoothscroll.js?v=';
echo $VersionNewiMC;
echo '"></script>
<script src="dist/js/newimc.js?v=';
echo $VersionNewiMC;
echo '"></script>
</head>

<body>
';
$menu = "home";
include("pages/template.header.php");
;
echo '
<div class="container panel-imc">
	<div class="col-lg-8">
        <div id="carousel-imc-generic" class="carousel slide hidden-md hidden-sm hidden-xs" data-ride="carousel" data-interval="10000">
          <!-- Indicators -->
          <ol class="carousel-indicators">
';
$SlideArr = json_decode(file_get_contents("backend/slides/slides.json"),TRUE);
$i = 0;
foreach ($SlideArr as $Key =>$Slide) {
;
echo '            <li data-target="#carousel-imc-generic" data-slide-to="';
echo $i;
echo '"';
if ($i == 0) {
echo' class="active"';
};
echo '></li>
';
$i++;
};
echo '          </ol>
        
          <!-- Wrapper for slides -->
          <div class="carousel-inner" role="listbox">
';
$i = 0;
foreach ($SlideArr as $Key =>$Slide) {
;
echo '            <div class="item';
if ($i == 0) {
echo' active';
};
echo '">
              <img src="backend/slides/';
echo $Key;
echo '" alt="';
echo $Slide['title'];
echo '" class="img-responsive">
              <div class="carousel-caption">
                <div class="caption-title">';
echo $Slide['title'];
echo '</div>
                <div class="caption-title-small">';
echo $Slide['desc'];
echo '</div>
              </div><!-- .carousel-caption -->
            </div>
';
$i++;
};
echo '          </div>
        
          <!-- Controls -->
          <a class="left carousel-control" href="#carousel-imc-generic" role="button" data-slide="prev">
            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
          </a>
          <a class="right carousel-control" href="#carousel-imc-generic" role="button" data-slide="next">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
          </a>
        </div>
    </div><!-- .left -->
    <div class="col-lg-4">
';
$CenterData = getFrontend("center");
foreach ($CenterData as $Key =>$Data) {
;
echo '    	<div class="panel-box list">
			<div class="panel-box-header"> ';
echo $Data[0];
echo ' </div>
        	';
echo CenterTemplate($Data[1],$Data);
echo '        </div>
';
};
echo '    </div><!-- .right -->
</div>
<div class="container panel-imc-page"><div class="row">
	<div class="col-md-3 col-md-push-9">
';
include("pages/template.right.php");
;
echo '    </div><!-- .right -->
    <div class="col-md-9 col-md-pull-3">
';
$ContentData = getFrontend("content");
foreach ($ContentData as $Key =>$Data) {
echo ContentTemplate($Data[2],$Data);
}
;
echo '    </div><!-- .left -->
    
</div></div>
';
$FOOTERKey = "MjM4o7Qa3zcR5Jxi";
require_once("pages/template.footer.php");
;
echo '
<div class="modal" id="itemshop-details">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
      <div class="row">
        <div class="col-md-5">
			<div class="product-picture"><img class="img-responsive"></div>
			<div class="product-title hidden-sm hidden-md hidden-xs"></div>
        </div>
        <div class="col-md-7">
        	<div class="item-title"></div>
            <div class="item-desc"></div>
        </div>
      </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-buyitem btn-info">Buy</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

</body>
</html>'; ?>
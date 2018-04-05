<?php 
$DEFINE = TRUE;
require("include/config.php");
$VersionNewiMC = file_get_contents("backend/version.info");
;
echo '';
require_once('include/class.license.php');
;
echo '';
if ($_GET["do"] == "truewallet") :
if ($Config['truewallet']['function'] == 'no') {
exit("FUNCTION_NOT_TRUE_ON");
}
if ($_POST["txid"] == "") {
exitJSON((array("result"=>false,"msg"=>"กรุณากรอก Transaction ID (เลขที่อ้างอิง) ","focus"=>"txid")));
}
if (!is_numeric($_POST["txid"])) {
exitJSON((array("result"=>false,"msg"=>"กรุณากรอก เลขที่อ้างอิง เฉพาะตัวเลขเท่านั้น ","focus"=>"txid")));
}
set_time_limit(60);
$WalletCheck = WalletCheck($_POST["txid"],$UserInfo);
if ($WalletCheck[0] == true) {
exitJSON((array("result"=>true,"msg"=>"ทำรายการสำเร็จด้วย <br><b>".$WalletCheck[1] ."</b><br><br>ยอดที่แจ้งโอนเงิน: Wallet <b>".$WalletCheck[2] ."฿</b>")));
}else {
exitJSON((array("result"=>false,"msg"=>$WalletCheck[1],"focus"=>"txid")));
}
exit();
endif;
if ($_GET["do"] == "random") :
if ($Config['feature']['random'] == 'no') {
exit("FUNCTION_NOT_TRUE_ON");
}
if ($_POST["action"] == "start_random") {
if ($Config["feature"]["random"] == "yes") {
$ConfigRandom = $Config["feature"]["random"];
$RandomData = $Config["feature"]["random_setting"]["data"];
$SettingRandom = $Config["feature"]["random_setting"]["setting"];
$Random = false;
if ($SettingRandom["type"] == "point") {
$Sumpoint = $UserInfo["point"] -$SettingRandom["point_value"];
if ($Sumpoint <0) {
exit(json_encode(array("result"=>false,"msg"=>"ยอดคงเหลือของ Point ของคุณไม่เพียงพอ")));
}else {
$Random = true;
}
}else {
$Time = time() -$UserInfo["random_data"];
$UseTime = secondsToTime($SettingRandom["time_value"]);
if ($Time >$SettingRandom["time_value"]) {
$Random = true;
}else {
exit(json_encode(array("result"=>false,"msg"=>"เวลาของคุณยังไม่ถึงกำหนดให้สุ่มได้")));
}
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่ได้เปิดระบบ Random ระบบสุ่ม")));
}
if ($Random) {
$Pickup = RandomArray($RandomData);
$doTransaction = false;
$doCommand = false;
$RandomDetails = (($Pickup["details"] == "") ?"": $Pickup["details"]);
if ($Pickup["type"] == "point") {
$GivePoint = rand($Pickup["value"][0],$Pickup["value"][1]);
$UpdatePoint = mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `point`=(`point`+".$GivePoint .") WHERE `id`='".$UserInfo["id"] ."' LIMIT 1;");
$doTransaction = true;
}else {
if ($Config["feature"]["bungeecord"] == "yes") {
$SelectServer = $Config["feature"]["bungeecord_server"][$SettingRandom["server"]];
$RCONSetting = array("ip"=>$SelectServer["ip"],"rcon_port"=>$SelectServer["rcon_port"],"rcon_password"=>$SelectServer["rcon_password"]);
$ServerTEXT = '<b>[#'.$SettingRandom["server"] .']</b> '.$SelectServer["name"] .' <small>('.$SelectServer["id"] .')</small>';
$ServerQuery = ServerQuery(array("ip"=>$SelectServer["ip"],"port"=>$SelectServer["query_port"]));
}else {
$RCONSetting = array();
$ServerTEXT = "<b>[MAIN]</b> เซิฟเวอร์หลัก";
$ServerQuery = ServerQuery();
}
if (!$ServerQuery['status']) {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (Server Query) <br>".$ServerTEXT)));
}
if ($Config["site"]["function"]["online_check"] == "yes") {
$CheckPlayerOnline = CheckPlayerOnline($ServerQuery);
if ($CheckPlayerOnline['status'] == "offline") {
exit(json_encode(array("result"=>false,"msg"=>"ตัวละครของคุณไม่อยู่ภายในเกม <br>".$ServerTEXT)));
}elseif ($CheckPlayerOnline['status'] == "failed") {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (Server Query)<br>".$ServerTEXT)));
}
}
foreach ($Pickup["value"] as $CommandID =>$CommandData) {
$Command[] = str_replace("<player>",$UserInfo['username'],$CommandData);
}
if ($Config['minecraft']['rcon']['tell_msg'] != "false") {
$CommandTell = "tell ".$UserInfo['username'] ." Random gived.";
$Command[] = $CommandTell;
}
if ($Config["feature"]["bungeecord"] == "yes") {
$ServerRCON = ServerRCON($Command,$RCONSetting);
if ($ServerRCON["status"]) {
$doCommand = true;
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (RCON)<br>".$ServerTEXT)));
}
}else {
if ($Config['minecraft']['command'] == "rcon") {
$ServerRCON = ServerRCON($Command);
if ($ServerRCON["status"]) {
$doCommand = true;
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (RCON)")));
}
}else {
$ws = new Websend($Config['minecraft']['websend']['ip'],$Config['minecraft']['websend']['port'],$Config['minecraft']['websend']['password']);
if ($ws->connect()) {
if (is_array($Command)) {
foreach ($Command as $Key =>$CMD) {
$ws->doCommandAsConsole($CMD);
}
}else {
$ws->doCommandAsConsole($Command);
}
$ws->disconnect();
$doCommand = true;
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (WebSend)")));
}
}
}
}
if (($doTransaction and $SettingRandom["type"] == 'point') or ( $doCommand and $SettingRandom["type"] == "point")) {
$UpdatePoint = mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `point`=(`point`-".$SettingRandom["point_value"] .") WHERE `id`='".$UserInfo["id"] ."' LIMIT 1;");
$NewPoint = mysqli_fetch_assoc(mysqli_query($conn,"SELECT `point` FROM `".$Config['minecraft']['authme']['database'] ."` WHERE `id`='".$UserInfo["id"] ."' LIMIT 1;"));
if ($doTransaction) {
$ShopLog = "Random ได้รับ ".number_format($GivePoint,2) ." Point (โดยใช้ ".number_format($SettingRandom["point_value"],2) ." Point)";
$Output = array("use"=>"point","type"=>"point","result_details"=>$RandomDetails,"result_data"=>number_format($GivePoint,2),"newpoint"=>number_format($NewPoint["point"],2));
}
if ($doCommand) {
$ShopLog = "Random ได้ส่งคำสั่งไปยัง ".$ServerTEXT ." (โดยใช้ ".number_format($SettingRandom["point_value"],2) ." Point)";
$Output = array("use"=>"point","type"=>"command","result_details"=>$RandomDetails,"result_data"=>$ServerTEXT,"newpoint"=>number_format($NewPoint["point"],2));
}
ShopHistory("random",$ShopLog);
}else {
$UpdateTime = mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `random_data`='".time() ."' WHERE `id`='".$UserInfo["id"] ."' LIMIT 1;");
$NewPoint = mysqli_fetch_assoc(mysqli_query($conn,"SELECT `point` FROM `".$Config['minecraft']['authme']['database'] ."` WHERE `id`='".$UserInfo["id"] ."' LIMIT 1;"));
if ($doTransaction) {
$ShopLog = "Random ได้รับ ".number_format($GivePoint,2) ." Point (โดยใช้เวลาต่อครั้ง)";
$Output = array("use"=>"time","type"=>"point","result_details"=>$RandomDetails,"result_data"=>number_format($GivePoint,2),"newpoint"=>number_format($NewPoint["point"],2));
}
if ($doCommand) {
$ShopLog = "Random ได้ส่งคำสั่งไปยัง ".$ServerTEXT ." (โดยใช้เวลาต่อครั้ง)";
$Output = array("use"=>"time","type"=>"command","result_details"=>$RandomDetails,"result_data"=>$ServerTEXT,"newpoint"=>number_format($NewPoint["point"],2));
}
ShopHistory("random",$ShopLog);
}
$OutputArr = array("result"=>true,"random_time"=>rand(500,2000),"msg"=>"Start random");
$OutputFin = array_merge($OutputArr,$Output);
exit(json_encode($OutputFin));
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถดำเนินรายการต่อไปได้")));
}
}
exit();
endif;
if ($_GET["do"] == "redeem") :
if ($_POST["code"] != "") {
$RedeemCode = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `shop_redeem` WHERE `code`='".mysqli_real_escape_string($conn,$_POST["code"]) ."' LIMIT 1;"));
if ($RedeemCode) {
if ($RedeemCode["status"] == 'used') {
exit(json_encode(array("result"=>false,"msg"=>"Redeem Code <b>&quot;".$RedeemCode["code"] ."&quot;</b> นี้ถูกใช้งานไปแล้ว")));
}else {
if ($RedeemCode["type"] == "point") {
if ($RedeemCode["status"] == "pending") {
$UpdatePoint = mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `point`=(`point`+".$RedeemCode["value"] .") WHERE `id`='".$UserInfo["id"] ."' LIMIT 1;");
$UpdateRedeem = mysqli_query($conn,"UPDATE `shop_redeem` SET `status`='used', `used_data`='".$UserInfo["id"] ."', `time_update`='".time() ."' WHERE `id`='".$RedeemCode["id"] ."' LIMIT 1; ");
$_SESSION["redeem_msg"] = "คุณได้รับ <b>".number_format($RedeemCode["value"],2) ." Point</b> จาก  <b>".$RedeemCode["code"] ."</b> เรียบร้อยแล้ว ";
exit(json_encode(array("result"=>true,"msg"=>"Success, Update member POINT (One-Time)")));
}else {
if ($RedeemCode["used_data"] != "") {
$UsedData = json_decode($RedeemCode["used_data"],TRUE);
if (is_array($UsedData)) {
foreach ($UsedData as $MemberID =>$Date) {
if ($MemberID == $UserInfo["id"]) {
exit(json_encode(array("result"=>false,"msg"=>"คุณได้ใช้ Redeem Code นี้ไปแล้ว, ไม่สามารถใช้ซ้ำได้อีกครั้ง")));
}
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถดึงรายการผู้ใช้ที่เติมรหัสนี้ไปแล้วได้, กรุณาติดต่อผู้ดูแลระบบเพิ่อสอบถาม")));
}
}
$UsedData[$UserInfo["id"]] = time();
$UpdatePoint = mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `point`=(`point`+".$RedeemCode["value"] .") WHERE `id`='".$UserInfo["id"] ."' LIMIT 1;");
$UpdateRedeem = mysqli_query($conn,"UPDATE `shop_redeem` SET `used_data`='".json_encode($UsedData) ."', `time_update`='".time() ."' WHERE `id`='".$RedeemCode["id"] ."' LIMIT 1; ");
$_SESSION["redeem_msg"] = "คุณได้รับ <b>".number_format($RedeemCode["value"],2) ." Point</b> จาก  <b>".$RedeemCode["code"] ."</b> เรียบร้อยแล้ว ";
exit(json_encode(array("result"=>true,"msg"=>"Success, Update member POINT (Times)")));
}
}else {
if ($Config["feature"]["bungeecord"] == "yes") {
exit(json_encode(array("result"=>false,"msg"=>"เนื่องจาก ".$Config["site"]["short_title"] ." ได้เปิดทำงานหลายเซิฟเวอร์ไว้, จึงไม่สามารถใช้ Redeem Code นี้ได้")));
}
$SendCommand = false;
if ($RedeemCode["status"] == "times") {
if ($RedeemCode["used_data"] != "") {
$UsedData = json_decode($RedeemCode["used_data"],TRUE);
if (is_array($UsedData)) {
foreach ($UsedData as $MemberID =>$Date) {
if ($MemberID == $UserInfo["id"]) {
exit(json_encode(array("result"=>false,"msg"=>"คุณได้ใช้ Redeem Code นี้ไปแล้ว, ไม่สามารถใช้ซ้ำได้อีกครั้ง")));
}
}
$SendCommand = true;
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถดึงรายการผู้ใช้ที่เติมรหัสนี้ไปแล้วได้, กรุณาติดต่อผู้ดูแลระบบเพิ่อสอบถาม")));
}
}else {
$SendCommand = true;
}
}else {
$SendCommand = true;
}
if ($Config["site"]["function"]["online_check"] == "yes") {
$ServerQuery = ServerQuery();
if (!$ServerQuery['status']) {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (Server Query)")));
}
$CheckPlayerOnline = CheckPlayerOnline($ServerQuery);
if ($CheckPlayerOnline['status'] == "offline") {
exit(json_encode(array("result"=>false,"msg"=>"ตัวละครของคุณไม่อยู่ภายในเกม, เนื่องจาก Redeem Code นี้ของจะเข้าตัวละคร")));
}elseif ($CheckPlayerOnline['status'] == "failed") {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (Server Query)")));
}
}
if ($SendCommand) {
$UsedData[$UserInfo["id"]] = time();
if ($RedeemCode["status"] == "times") {
$UpdateRedeem = mysqli_query($conn,"UPDATE `shop_redeem` SET `used_data`='".json_encode($UsedData) ."', `time_update`='".time() ."' WHERE `id`='".$RedeemCode["id"] ."' LIMIT 1; ");
}else {
$UpdateRedeem = mysqli_query($conn,"UPDATE `shop_redeem` SET `used_data`='".$UserInfo["id"] ."', `status`='used', `time_update`='".time() ."' WHERE `id`='".$RedeemCode["id"] ."' LIMIT 1; ");
}
$Command[] = str_replace("<player>",$UserInfo['username'],$RedeemCode["value"]);
$Command[] = "tell ".$UserInfo['username'] ." Give item with Redeem Code (".$RedeemCode["code"] .")";
if ($Config['minecraft']['command'] == "rcon") {
$ServerRCON = ServerRCON($Command);
if ($ServerRCON["status"]) {
$_SESSION["redeem_msg"] = "คุณได้รับสินค้าจาก <b>".$RedeemCode["code"] ."</b> เรียบร้อยแล้ว, กรุณาตรวจสอบที่ตัวละครของคุณ ";
exit(json_encode(array("result"=>true,"msg"=>"Success, Update member COMMAND")));
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (RCON)")));
}
}else {
$ws = new Websend($Config['minecraft']['websend']['ip'],$Config['minecraft']['websend']['port'],$Config['minecraft']['websend']['password']);
if ($ws->connect()) {
foreach ($Command as $Key =>$CMD) {
$ws->doCommandAsConsole($CMD);
}
$ws->disconnect();
$_SESSION["redeem_msg"] = "คุณได้รับสินค้าจาก <b>".$RedeemCode["code"] ."</b> เรียบร้อยแล้ว, กรุณาตรวจสอบที่ตัวละครของคุณ ";
exit(json_encode(array("result"=>true,"msg"=>"Success, Update member COMMAND")));
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเชื่อมต่อกับเซิฟเวอร์เกมได้ (WebSend)")));
}
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถทำรายการได้เนื่องจาก Command ไม่สำเร็จเป็น Array")));
}
}
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่พบ Redeem Code นี้ในระบบฐานข้อมูล")));
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"กรุณากรอก Redeem Code")));
}
exit();
endif;
if ($_GET["do"] == "skin") :
if (!$isAuth) {
DisplayMSG("danger","กรุณาเข้าสู่ระบบสมาชิกก่อนอัพโหลดสกิน");
exit();
}
$fileElementName = 'fileToUpload';
if (!empty($_FILES[$fileElementName]['error'])) {
switch ($_FILES[$fileElementName]['error']) {
case '1': $error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
break;
case '2': $error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
break;
case '3': $error = 'The uploaded file was only partially uploaded';
break;
case '4': $error = 'No file was uploaded.';
break;
case '6': $error = 'Missing a temporary folder';
break;
case '7': $error = 'Failed to write file to disk';
break;
case '8': $error = 'File upload stopped by extension';
break;
case '999':
default: $error = 'No error code avaiable';
}
}elseif (empty($_FILES['fileToUpload']['tmp_name']) ||$_FILES['fileToUpload']['tmp_name'] == 'none') {
$error = 'กรุณาอัพโหลดไฟล์ที่ต้องการ';
}else {
$fileParts = pathinfo($_FILES[$fileElementName]['name']);
if (strtolower($fileParts['extension']) != "png") {
@unlink($_FILES[$fileElementName]['tmp_name']);
DisplayMSG("danger","ชนิดของไฟล์ไม่ถูกต้อง, เฉพาะ .png เท่านั้น");
}
if (filesize($_FILES[$fileElementName]['tmp_name']) >2048576) {
@unlink($_FILES[$fileElementName]['tmp_name']);
DisplayMSG("danger","ขนาดไฟล์ใหญ่เกิน 2MB");
}
$target = "backend/skins/".strtolower($UserInfo['username']) .".png";
move_uploaded_file($_FILES[$fileElementName]['tmp_name'],$target);
DisplayMSG("success","อัพโหลดสกินเรียบร้อยแล้ว (Upload skin success.)","member.php?page=skin");
}
DisplayMSG("danger","ERROR: ".$error);
exit();
endif;
if ($_GET["do"] == "main_password") :
if (!$isAuth) {
DisplayMSG("danger","กรุณาเข้าสู่ระบบสมาชิกก่อนเปลี่ยนรหัสผ่าน");
exit();
}
if (empty($_POST["old_password"])) {
DisplayMSG("danger","กรุณากรอก Current Password ");
exit();
}
if (empty($_POST["password"])) {
DisplayMSG("danger","กรุณากรอก Password ");
exit();
}
if (empty($_POST["password2"])) {
DisplayMSG("danger","กรุณากรอก Confirm Password ");
exit();
}
if ($_POST["password"] != $_POST["password2"]) {
DisplayMSG("danger","กรุณากรอก Password ทั้งสองให้ถูกต้อง ");
exit();
}
if (strlen($_POST["password"]) <4) {
DisplayMSG("danger","กรุณากรอก Password มากกว่า 4 ตัวอักษร");
exit();
}
$EasyPassword = array("1234","123456","654321","123456789","1234567","12345678","password","000000","111111","222222","333333","444444","555555","iloveyou","qwerty","qweasd","qweasdzxc","qwedsazxc");
if (in_array($_POST["password"],$EasyPassword)) {
DisplayMSG("danger","กรุณากรอกรหัสผ่านที่มีความยากกว่านี้ ");
exit();
}
$chkold = PasswordHash($_POST["old_password"],$UserInfo['password']);
if ($Config['minecraft']['authme']['hash'] == "SHA256") {
$newpassword = AuthMeSha256($_POST["password"]);
}else if ($Config['minecraft']['authme']['hash'] == "MD5") {
$newpassword = md5($_POST["password"]);
}
$Check = TotalDB("`".$Config['minecraft']['authme']['database'] ."` WHERE `username`='".$UserInfo["username"] ."' LIMIT 1");
if ($Check >0 and $chkold) {
mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `password`='".$newpassword ."' WHERE `id`='".$UserInfo['id'] ."' LIMIT 1; ");
DisplayMSG("success","เปลี่ยนรหัสผ่านเรียบร้อยแล้ว <br> <small>กรุณาเข้าสู่ระบบสมาชิกอีกครั้ง</small>","member.php?page=profile");
exit();
}else {
DisplayMSG("danger","รหัสผ่านเก่าไม่ถูกต้อง, กรุณากลับไปแก้ไข");
exit();
}
exit();
endif;
if ($_GET["do"] == "change_profile") :
if (!$isAuth) {
DisplayMSG("danger","กรุณาเข้าสู่ระบบสมาชิก");
exit();
}
if (empty($_POST["email"])) {
DisplayMSG("danger","กรุณากรอก Email ");
exit();
}
if (!filter_var($_POST["email"],FILTER_VALIDATE_EMAIL)) {
DisplayMSG("danger","กรุณากรอกรูปแบบของ Email ให้ถูกต้อง");
exit();
}
$CheckEmail = mysqli_fetch_assoc(mysqli_query($conn,"SELECT `email` FROM `".$Config['minecraft']['authme']['database'] ."` WHERE `email`='".mysqli_real_escape_string($conn,$_POST["email"]) ."' AND `id`!='".$UserInfo["id"] ."' LIMIT 1; "));
if ($CheckEmail) {
if ($CheckEmail["email"] == $UserInfo["email"]) {
DisplayMSG("danger","Email <b>".$_POST["email"] ."</b> นี้เป็นของคุณอยู่แล้ว");
exit();
}else {
DisplayMSG("danger","Email <b>".$_POST["email"] ."</b> นี้มีระบบแล้ว, กรุณาเปลี่ยนใหม่");
exit();
}
}else {
mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `email`='".mysqli_real_escape_string($conn,$_POST["email"]) ."' WHERE `id`='".$UserInfo['id'] ."' LIMIT 1; ");
DisplayMSG("success","เปลี่ยน Email ของคุณเรียบร้อยแล้ว","member.php?page=profile");
exit();
}
exit();
endif;
if ($_GET["do"] == "forgot") :
if ($Config["site"]["function"]["member_forgot"] == "no") {
DisplayMSG("danger","ระบบปิดระบบลืมรหัสผ่าน");
}
if ($isAuth) {
DisplayMSG("danger","กรุณาออกจากระบบสมาชิก");
exit();
}
if (empty($_POST["username"])) {
DisplayMSG("danger","กรุณากรอก Username ");
exit();
}
if (empty($_POST["forgot_answer"])) {
DisplayMSG("danger","กรุณากรอก คำตอบ ");
exit();
}
$Check = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `".$Config['minecraft']['authme']['database'] ."` WHERE `username`='".mysqli_real_escape_string($conn,$_POST["username"]) ."' LIMIT 1; "));
if ($Check) {
if ($Check["forgot_pwd_question"] == "") {
DisplayMSG("danger","ผู้ใช้งานนี้ยังไม่ถูกตั้งคำถามในกรณีลืมรหัสผ่าน <br><small>กรุณาติดต่อผู้ดูแลเว็บไซต์เพื่อขอเปลี่ยนรหัสผ่านใหม่ด้วยตนเอง<small>");
exit();
}
if ($_SESSION["forgot"]) {
$ForgotTime = $_SESSION["forgot"];
if ($ForgotTime >5) {
DisplayMSG("danger","คุณได้ใช้ระบบลืมรหัสติดต่อกัน 5 ครั้ง, กรุณารอสักครู่");
exit();
}
$_SESSION["forgot"] = $ForgotTime +1;
}else {
$_SESSION["forgot"] = 1;
}
if ($Check["forgot_pwd_answer"] != mysqli_real_escape_string($conn,$_POST["forgot_answer"])) {
DisplayMSG("danger","คำตอบไม่ถูกต้อง");
exit();
}
$newpass = random_str('6','number');
if ($Config['minecraft']['authme']['hash'] == "SHA256") {
$newpassword = AuthMeSha256($newpass);
}else if ($Config['minecraft']['authme']['hash'] == "MD5") {
$newpassword = md5($newpass);
}
mysqli_query($conn,"UPDATE `".$Config['minecraft']['authme']['database'] ."` SET `password`='".$newpassword ."' WHERE `id`='".$Check['id'] ."' LIMIT 1 ");
DisplayMSG("success","รหัสผ่านใหม่สำหรับคุณคือ <strong style='font-size:150%;'>".$newpass ."</strong><br> <small>กรุณาเข้าสู่ระบบเปลี่ยนรหัสผ่านใหม่</small>","member.php");
exit();
}else {
DisplayMSG("danger","ไม่พบ Username นี้ในระบบ");
exit();
}
exit();
endif;
if ($_GET["do"] == "login") :
if (empty($_POST["username"])) {
DisplayMSG("danger","กรุณากรอก Username ");
exit();
}
if (empty($_POST["password"])) {
DisplayMSG("danger","กรุณากรอก Password ");
exit();
}
if ($_SESSION["login"]) {
$LoginTime = $_SESSION["login"];
if ($LoginTime >5) {
DisplayMSG("danger","คุณเข้าสู่ระบบติดต่อกัน 5 ครั้ง, กรุณารอสักครู่");
exit();
}
$_SESSION["login"] = $LoginTime +1;
}else {
$_SESSION["login"] = 1;
}
if ($Member->doLogin($_POST["username"],$_POST["password"])) {
if ($_POST["return"] == "") {
header("Location: member.php");
}else {
header("Location: ".$_POST["return"]);
}
exit();
}else {
DisplayMSG("danger","Username หรือ Password ไม่ถูกต้อง");
exit();
}
exit();
endif;
if ($_GET["do"] == "logout") :
$Member->doLogout();
header("Location: member.php");
exit();
endif;
if ($_GET["do"] == "register") :
if ($Config["site"]["function"]["member_register"] == "no") {
DisplayMSG("danger","ระบบปิดการสมัครสมาชิก");
}
if ($isAuth) {
DisplayMSG("danger","กรุณาออกจากระบบสมาชิก");
exit();
}
if (empty($_POST["username"])) {
DisplayMSG("danger","กรุณากรอก Username ");
exit();
}
if (empty($_POST["password"])) {
DisplayMSG("danger","กรุณากรอก Password ");
exit();
}
if (empty($_POST["password2"])) {
DisplayMSG("danger","กรุณากรอก Re-Password ");
exit();
}
if (empty($_POST["email"])) {
DisplayMSG("danger","กรุณากรอก Email ");
exit();
}
if (empty($_POST["forgot_question"])) {
DisplayMSG("danger","กรุณากรอก Question (Forgot Password) ");
exit();
}
if (empty($_POST["forgot_answer"])) {
DisplayMSG("danger","กรุณากรอก Answer (Forgot Password) ");
exit();
}
if (empty($_POST["captcha"])) {
DisplayMSG("danger","กรุณากรอก Captcha ");
exit();
}
if (empty($_POST["captcha_id"])) {
DisplayMSG("danger","กรุณากรอก Captcha ID ");
exit();
}
if ($_POST["password"] != $_POST["password2"]) {
DisplayMSG("danger","กรุณากรอก Password ทั้งสองให้ถูกต้อง");
exit();
}
if (strlen($_POST["password"]) <4) {
DisplayMSG("danger","กรุณากรอก Password มากกว่า 4 ตัวอักษร");
exit();
}
if (!filter_var($_POST["email"],FILTER_VALIDATE_EMAIL)) {
DisplayMSG("danger","กรุณากรอกรูปแบบของ Email ให้ถูกต้อง");
exit();
}
if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬-]/',$_POST["username"])) {
DisplayMSG("danger","ไม่อนุญาตให้ใช้ตัวอักษรพิเศษเป็น Username <br> เช่น \  / [ ' ^ £ $ % & * ( ) } { @ # ~ ? > < > , | = + ¬ - ]");
exit();
}
if (!preg_match('/^[a-zA-Z0-9\_]*$/',$_POST["username"])) {
DisplayMSG("danger","อนุญาตให้ใช้ตัวอักษร  a-z A-Z 0-9 สำหรับ Username เท่านั้น");
exit();
}
$EasyPassword = array("123456","654321","123456789","1234567","12345678","password","000000","111111","222222","333333","444444","555555","iloveyou","qwerty","qweasd","qweasdzxc","qwedsazxc");
if (in_array($_POST["password"],$EasyPassword)) {
DisplayMSG("danger","กรุณากรอกรหัสผ่านที่มีความยากกว่านี้ ");
exit();
}
$Captcha = $_SESSION["captcha_".$_POST["captcha_id"]];
if ($Captcha == "") {
DisplayMSG("danger","ERROR: Captcha (ID: ".$_POST["captcha_id"] .") not found");
exit();
}
if ($Captcha != $_POST["captcha"]) {
DisplayMSG("danger","กรุณากรอกรหัส Captcha ตรงตามตัวเลขภาพ");
exit();
}
$CheckUsername = @mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `".$Config['minecraft']['authme']['database'] ."` WHERE `username`='".mysqli_real_escape_string($conn,$_POST["username"]) ."' LIMIT 1; "));
if ($CheckUsername) {
DisplayMSG("danger","Username <strong>&quot;".$_POST["username"] ."&quot; </strong> มีผู้ใช้งานอื่นใช้งานไปแล้ว");
exit();
}
if ($Config['minecraft']['authme']['hash'] == "SHA256") {
$newpassword = AuthMeSha256($_POST["password"]);
}else if ($Config['minecraft']['authme']['hash'] == "MD5") {
$newpassword = md5($_POST["password"]);
}
$SQLInsert = "INSERT INTO `".$Config['minecraft']['authme']['database'] ."` SET `username`='".mysqli_real_escape_string($conn,$_POST["username"]) ."' , `password`= '".$newpassword ."', `email`='".mysqli_real_escape_string($conn,$_POST["email"]) ."' ";
if ($_POST["forgot_question"] == "your_question") {
$SQLInsert .= " ,`forgot_pwd_question`='".mysqli_real_escape_string($conn,$_POST["forgot_your_question"]) ."' , `forgot_pwd_answer`='".mysqli_real_escape_string($conn,$_POST["forgot_answer"]) ."' ";
}else {
$SQLInsert .= " ,`forgot_pwd_question`='".mysqli_real_escape_string($conn,$_POST["forgot_question"]) ."' , `forgot_pwd_answer`='".mysqli_real_escape_string($conn,$_POST["forgot_answer"]) ."' ";
}
mysqli_query($conn,$SQLInsert);
DisplayMSG("success","สมัครสมาชิกเรียบร้อยแล้ว, คุณสามารถเข้าสู่ระบบได้ทันที","member.php");
exit();
exit();
endif;
if ($_GET["do"] == "refill") :
if (!$isAuth) {
exit(json_encode(array("result"=>false,"msg"=>"กรุณาเข้าสู่ระบบสมาชิกก่อนทำรายการดังกล่าว")));
}
if ($_POST["password"] == "") {
exit(json_encode(array("result"=>false,"msg"=>"กรุณากรอกรหัสบัตรทรูมันนี่ 14 หลัก")));
}
if (!is_numeric($_POST["password"])) {
exit(json_encode(array("result"=>false,"msg"=>"กรุณากรอกรหัสบัตรทรูมันนี่เฉพาะตัวเลขเท่านั้น")));
}
if (strlen($_POST["password"]) <14) {
exit(json_encode(array("result"=>false,"msg"=>"กรุณากรอกรหัสบัตรทรูมันนี่ให้ครบ 14 หลัก")));
}
$PASSWORD = mysqli_real_escape_string($conn,$_POST["password"]);
if ($_POST["action"] == "refill") {
$LastTX = mysqli_fetch_assoc(mysqli_query($conn,"SELECT `status` FROM `shop_topup` WHERE `username`='".$UserInfo['username'] ."' AND `status`='pending' AND `time` > ".(time() -3600) ." ORDER BY `id` DESC LIMIT 1; "));
if ($LastTX) {
exit(json_encode(array("result"=>false,"msg"=>"รหัสบัตรเติมเงินเก่ายังไม่ถูกดำเนินการกรุณารอสักครู่")));
}
$RESPURL = $Config['site']['baseurl'] ."backend/tmpay.php";
$TMPAY = tmn_refill($PASSWORD,$Config['topup_system']['tmpay']['merchant_id'],$RESPURL);
if ($TMPAY['result']) {
if ($TMPAY['status'] == 'SUCCEED') {
$SQL = "INSERT INTO `shop_topup` SET `username`='".$UserInfo['username'] ."', `transaction_id`='".$TMPAY['data'] ."', `password`='".$PASSWORD ."', `time`='".time() ."', `ip`='".$_SERVER["REMOTE_ADDR"] ."', `status`='pending', `topup_with`='tmpay' ";
mysqli_query($conn,$SQL);
exit(json_encode(array("result"=>true,"msg"=>"Success connecting to TMPAY.")));
}elseif ($TMPAY['status'] == 'ERROR') {
if ($TMPAY['data'] == 'INVALID_MERCHANT_ID') {
exit(json_encode(array("result"=>false,"msg"=>"TMPAY: รหัสร้านค้าไม่ถูกต้อง หรือไม่พบในระบบ")));
}
if ($TMPAY['data'] == 'INVALID_PASSWORD') {
exit(json_encode(array("result"=>false,"msg"=>"TMPAY: รูปแบบรหัสบัตรเงินสดไม่ถูกต้อง")));
}
if ($TMPAY['data'] == 'INVALID_RESP_URL') {
exit(json_encode(array("result"=>false,"msg"=>"TMPAY: รูปแบบ URL ตอบกลับไม่ถูกต้อง")));
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"Fail do not process. (End TMPAY)")));
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่สามารถเติมเงินกับระบบ TMPAY ได้, กรุณาลองใหม่อีกครั้ง")));
}
}
if ($_POST["action"] == "check_status") {
$TXArr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM `shop_topup` WHERE `password`='".$PASSWORD ."' AND `username`='".$UserInfo['username'] ."' ORDER BY `id` DESC LIMIT 1; "));
if ($TXArr) {
if ($TXArr['status'] == 'success') {
if ($Config["topup_event"]["event"] == "true") {
if ($Config['topup_event'][floatval($TXArr['amount'])]["point"] != "") {
$Point = $Config['topup_event'][floatval($TXArr['amount'])]["point"];
}else {
$Point = $Config['topup'][floatval($TXArr['amount'])];
}
}else {
$Point = $Config['topup'][floatval($TXArr['amount'])];
}
$MyPoint = ($Point +$UserInfo['point']);
exit(json_encode(array("result"=>true,"time"=>time(),"status"=>$TXArr['status'],"amount"=>$TXArr['amount'],"point"=>number_format($Point),"mypoint"=>number_format($MyPoint))));
}else {
exit(json_encode(array("result"=>true,"time"=>time(),"status"=>$TXArr['status'])));
}
}else {
exit(json_encode(array("result"=>false,"msg"=>"ไม่พบรหัสบัตรเงินสดทรูมันนี่นี้ในระบบฐานข้อมูล")));
}
}
exit();
endif;
if ($_GET["do"] == "forgot_pwd") :
if (!$isAuth) {
DisplayMSG("danger","กรุณาเข้าสู่ระบบสมาชิกก่อนทำรายการดังกล่าว");
}
if ($_POST["forgot_question"] == "") {
DisplayMSG("danger","กรุณาเลือกคำถามที่ต้องการ");
}
if ($_POST["forgot_answer"] == "") {
DisplayMSG("danger","กรุณาตอบคำถามที่ตั้ง");
}
if ($_POST["forgot_pwd"] == "") {
DisplayMSG("danger","กรุณากรอกรหัสผ่าน");
}
if (!PasswordHash($_POST["forgot_pwd"],$UserInfo['password'])) {
DisplayMSG("danger","รหัสผ่านไม่ถูกต้อง");
}
$QuestionArray = array();
$forgot_your_question = mysqli_real_escape_string($conn,$_POST["forgot_your_question"]);
$forgot_question = mysqli_real_escape_string($conn,$_POST["forgot_question"]);
$forgot_answer = mysqli_real_escape_string($conn,$_POST["forgot_answer"]);
if ($_POST["forgot_question"] == "your_question") {
if ($_POST["forgot_your_question"] == "") {
DisplayMSG("danger","กรุณาตั้งคำถามที่ต้องการ");
}
$QuestionArray = array("question"=>$forgot_your_question,"answer"=>$forgot_answer);
}else {
$QuestionArray = array("question"=>$forgot_question,"answer"=>$forgot_answer);
}
$Query = mysqli_query($conn,"UPDATE `".$Config["minecraft"]["authme"]["database"] ."` SET `forgot_pwd_question`='".$QuestionArray["question"] ."', `forgot_pwd_answer`='".$QuestionArray["answer"] ."' WHERE `id`='".$UserInfo["id"] ."' LIMIT 1; ");
if ($Query) {
DisplayMSG("success","บันทึกข้อมูลระบบลืมรหัสผ่านเรียบร้อยแล้ว","member.php?page=profile");
}else {
DisplayMSG("danger","ไม่สามารถ Query ข้อมูลได้<br>".mysqli_error($conn));
}
exit();
endif;
$PageArray = array(
"refill"=>array("title"=>'Refill',"small"=>"เติมเงินผ่าน TrueMoney"),
"refill_history"=>array("title"=>'Refill <span class="colorblue">History</span>',"small"=>"ประวัติการเติมเงินบัตรทรูมันนี่"),
"truewallet"=>array("title"=>'TrueWallet',"small"=>"เติมเงินผ่าน TrueWallet"),
"register"=>array("title"=>'Register',"small"=>"สมัครสมาชิก"),
"profile"=>array("title"=>'<span class="colorblue">'.($UserInfo['username']) .'</span> Profile',"small"=>"จัดการข้อมูลส่วนตัว"),
"forgot"=>array("title"=>'Forgot Password',"small"=>"ระบบลืมรหัสผ่าน"),
"shop_history"=>array("title"=>'Shop <span class="colorblue">History</span>',"small"=>"ประวัติการทำรายการร้านค้า"),
"random"=>array("title"=>'Random',"small"=>"สุ่มของ"),
"redeem"=>array("title"=>'Redeem Code',"small"=>"แลกรหัสสินค้า"),
);
if ($Config['feature']['skin'] == 'yes') {
$PageArray['skin'] = array("title"=>"Skin","small"=>"ระบบสกิน");
}
if ($_GET["page"] == "") {
$PageArr = array("title"=>'Member',"small"=>"ระบบสมาชิก");
}else {
$PageArr = $PageArray[$_GET["page"]];
}
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
if ($_GET["page"] == "") {
$menu = "member";
}else {
$menu = $_GET["page"];
}include("pages/template.header.php");
;
echo '<div class="container panel-imc-page "><div class="row">
    <div class="col-lg-9">
	<div class="panel-imc-title">';
echo $PageArr['title'];
echo ' <small>';
echo $PageArr['small'];
echo '</small></div>
	<div class="panel-imc-content panel-imc-box">
';
if ($_GET["page"] == "") :
;
echo '';
if ($isAuth) {
;
echo '		<div class="col-lg-5 member-user"  style="margin-bottom:15px;">
        	<div class="avatar"><img src="https://minotar.net/body/';
echo $UserInfo['username'];
echo '/90.png"></div>
            <div class="details">
            	<div class="title">';
echo strtoupper($UserInfo['username']);
echo '</div>
                <div class="email">';
echo $UserInfo['email'];
echo '</div>
                <div class="point">';
echo number_format($UserInfo['point'],2,'.',',');
echo '</div>
                <div class="extra_point">Point</div>
				';
if ($Config['feature']['skin'] == 'yes') {
;
echo '				<a class="btn btn-block btn-default" href="member.php?page=skin"><i class="fa fa-upload"></i> Upload Skin</a>
				';
}else {
;
echo '				<a class="btn btn-block btn-default" href="member.php?page=profile"><i class="fa fa-edit"></i> Edit profile</a>
				';
};
echo '            </div>
        </div><!-- .left -->
        <div class="col-lg-7 member-btn">
        	<a class="btn btn-block btn-default" href="member.php?page=refill">Refill <div class="small-text">เติมเงิน</div> </a>
        	<a class="btn btn-block btn-default" style="margin-top:0;" href="shop.php">Itemshop <div class="small-text">ไอเท็มชอป</div> </a>
            
        	<a class="btn btn-block btn-default" href="member.php?page=redeem">Redeem Code <div class="small-text">แลกรหัสสินค้า</div> </a>
        	<a class="btn btn-block btn-default" href="member.php?page=shop_history">History <div class="small-text">ประวัติร้านค้า</div> </a>		
			
        </div><!-- .right--> 
		<div class="clearfix"></div>
		
';
if ($UserInfo["forgot_pwd_question"] == "") {
;
echo '	<hr>
	<div class="caption-title">ตั้งคำถามสำหรับกรณีลืมรหัสผ่าน</div>
	<p class="font12">เพื่อความปลอดภัยของข้อมูลผู้ใช้งานกรุณาตั้งคำถาม และคำตอบเพื่อในกรณีที่ท่านลืมรหัสผ่านเข้าสู่ระบบ ท่านสามารถใช้ตอบคำถามที่ท่านได้ตั้งไว้เพื่อใช้ในการลืมรหัสผ่านได้ และหากท่านยังไม่ได้ตั้งในส่วนนี้จะไม่สามารถใช้ระบบลืมรหัสผ่านได้</p>
	
	<form class="form-horizontal" action="member.php?do=forgot_pwd" method="post">
	  <div class="form-group">
		<label for="forgot_question" class="col-sm-3 control-label">Question <div class="smalltext">คำถาม</div></label>
		<div class="col-sm-4">
			<select class="form-control" name="forgot_question" id="forgot_question">
';
foreach ($Config["feature"]["forgot_pwd"] as $ID =>$Value) {
;
echo '				<option value="';
echo $Value;
echo '">';
echo $Value;
echo '</option>
';
};
echo '				<option value="your_question">-- ตั้งคำถามเอง --</option>
			</select>
		</div>
	  </div>
	  <div class="form-group" id="your_question" style="display:none;">
		<label for="forgot_question_your" class="col-sm-3 control-label">Your Question <div class="smalltext">คำถาม (ตั้งเอง)</div></label>
		<div class="col-sm-4">
		  <input type="text" class="form-control" id="forgot_your_question" name="forgot_your_question" placeholder="คำถามสำหรับตั้งเอง" maxlength="100">
		</div>
	  </div>
	  <div class="form-group">
		<label for="forgot_answer" class="col-sm-3 control-label">Answer <div class="smalltext">คำตอบ</div></label>
		<div class="col-sm-4">
		  <input type="text" class="form-control" id="forgot_answer" name="forgot_answer" placeholder="คำตอบ" maxlength="100" require>
		</div>
	  </div>
	  <div class="form-group">
		<label for="forgot_pwd" class="col-sm-3 control-label">Password <div class="smalltext">รหัสผ่าน</div></label>
		<div class="col-sm-4">
		  <input type="password" class="form-control" id="forgot_pwd" name="forgot_pwd" placeholder="Password" require>
		</div>
	  </div>
	  <div class="form-group">
		<div class="col-sm-offset-3 col-sm-9">
		  <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> บันทึกข้อมูล</button>
		  <div class="smalltext">* นี้เป็นการบันทึกข้อมูลครั้งแรกสำหรับผู้ที่ยังไม่เคยถูกตั้ง</div>
		</div>
	  </div>
	</form>	
<script>
$("#forgot_question").change(function(){
	$Value = $(this).val();
	if($Value=="your_question") {
		$("#your_question").show()
		$("#forgot_your_question").focus();
	} else {
		$("#your_question").hide();
		$("#forgot_answer").focus();
	}
});
</script>
';
};
echo '		
';
}else {
;
echo '                <form class="form-horizontal" action="member.php?do=login" method="post">
                <input type="hidden" name="return" value="';
echo $_GET["return"];
echo '">
                  <fieldset>
                    <div class="form-group">
                      <label for="username" class="col-lg-4 control-label control-sealplus">Username <div class="smalltext">ชื่อผู้ใช้งาน (6-10 ตัวอักษร)</div></label>
                      <div class="col-lg-4">
                        <input type="text" class="form-control" name="username" id="username" placeholder="Username" required >
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="password" class="col-lg-4 control-label control-sealplus">Password <div class="smalltext">รหัสผ่าน</div></label>
                      <div class="col-lg-4">
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required >
                      </div>
                    </div>
                    <div class="form-group">
                      <div class="col-lg-8 col-lg-offset-4">
                        <button type="submit" class="btn btn-default"><i class="fa fa-sign-in"></i> เข้าสู่ระบบ</button>
                        <a href="member.php?page=forgot" class="btn btn-xs btn-warning">ลืมรหัสผ่าน</a>
                        <div style="margin-top:5px;"><a href="member.php?page=register" class="btn btn-xs btn-info">สมัครสมาชิก</a></div>
                      </div>
                    </div>
                  </fieldset>
                </form>
';
};
echo '';
endif;
;
echo '    ';
if ($_GET["page"]) {
$memberPage = "pages/member.".$_GET["page"] .".php";
if (file_exists($memberPage)) {
include($memberPage);
}else {
header("Location: member.php");
exit();
}
}
;
echo '    </div>
    </div>
    <div class="col-lg-3">
    	';
include("pages/template.right.php");
;
echo '    </div>
</div></div>

';
$FOOTERKey = "MjM4o7Qa3zcR5Jxi";
require_once("pages/template.footer.php");
;
echo '</body>
</html>'; ?>
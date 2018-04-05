<?php 
$DEFINE=TRUE;require("include/config.php");$VersionNewiMC = file_get_contents("backend/version.info");;echo '';
require_once('include/class.license.php');
;echo '';
require_once("pages/backend.action.php");
$PageArray = array(
"home"=>array("small"=>"ระบบจัดการเบื้องหลัง","title"=>"Backend","icon"=>"home"),
"setting"=>array("small"=>"ตั้งค่าระบบ","title"=>"Setting","icon"=>"cog"),
"feature"=>array("small"=>"ตั้งค่าระบบพิเศษ","title"=>"Feature","icon"=>"plug"),
"truewallet"=>array("small"=>"ตั้งค่า TrueWallet","title"=>"TrueWallet API","icon"=>"connectdevelop"),
"frontend"=>array("small"=>"[ตกแต่ง] แก้ไขหน้าแรก (Front-end)","title"=>"Front-end","icon"=>"sitemap"),
"customization"=>array("small"=>"[ตกแต่ง] แก้ไขรูปภาพ (Images)","title"=>"Customization","icon"=>"picture-o"),
"pages"=>array("small"=>"[ตกแต่ง] จัดการหน้า (Pages)","title"=>"Pages","icon"=>"desktop"),
"member"=>array("small"=>"จัดการสมาชิก","title"=>"Member","icon"=>"users"),
"item"=>array("small"=>"จัดการสินค้า","title"=>"Item","icon"=>"shopping-cart"),
"item_category"=>array("small"=>"จัดการหมวดหมู่สินค้า","title"=>"Category","icon"=>"truck"),
"item_order"=>array("small"=>"จัดเรียงลำดับสินค้า","title"=>"Item order","icon"=>"bars"),
"redeem"=>array("small"=>"แลกรหัสสินค้า","title"=>"Redeem Code","icon"=>"barcode"),
"history"=>array("small"=>"ประวัติรายการ","title"=>"History","icon"=>"list"),
"random"=>array("small"=>"ตั้งค่าระบบสุ่ม","title"=>"Random","icon"=>"random"),
);
if($_GET["page"]=="") {
$PageArr = array("title"=>'Backend',"small"=>"ระบบจัดการเบื้องหลัง");
}else {
$PageArr  = $PageArray[$_GET["page"]];
}
;echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Backend - ';echo $Config['site']['title'];echo '</title>
';include("pages/inc.meta.php");;echo '<link rel="stylesheet" href="dist/css/bootstrap.css?v=';echo $VersionNewiMC;echo '">
<link rel="stylesheet" href="dist/css/backend.css?v=';echo $VersionNewiMC;echo '">
<link rel="stylesheet" href="dist/css/style.css?v=';echo $VersionNewiMC;echo '">
<script src="dist/js/jquery.min.js?v=';echo $VersionNewiMC;echo '"></script>
<script src="dist/js/bootstrap.min.js?v=';echo $VersionNewiMC;echo '"></script>
<script src="dist/js/smoothscroll.js?v=';echo $VersionNewiMC;echo '"></script>
<script src="dist/js/newimc.js?v=';echo $VersionNewiMC;echo '"></script>
</head>

<body>
';include("pages/template.header.php");;echo '
<div class="container panel-imc">
    <div class="col-md-3 col-md-push-9">
   	';$backendRight=true;include("pages/template.right.php");;echo '    </div><!-- .right -->

	<div class="col-md-9 col-md-pull-3">
';if($backendAuth) {;echo '	<div class="panel-imc-title"><i class="fa fa-';echo $PageArr["icon"];echo '"></i> ';echo $PageArr['title'];echo ' <small>';echo $PageArr['small'];echo '</small></div>
	<div class="panel-imc-content panel-imc-box">
';
if($_GET["page"]) {
$memberPage = "pages/backend.".$_GET["page"].".php";
if(file_exists($memberPage)) {
include($memberPage);
}else {
header("Location: backend.php?page=home");
exit();
}
}else {
include("pages/backend.home.php");
}
;echo '
	</div>
';}else {
;echo '	<div class="panel-imc-title">Backend <small>เข้าสู่ระบบจัดการเบื้องหลัง</small></div>
	<div class="panel-imc-content panel-imc-box">
        <form action="backend.php?do=login" method="post" style="width:200px; margin:0 auto;">
            <input type="hidden" name="return" value="';if(!$_GET["return"]) {echo $_SERVER['PHP_SELF'];}else{echo $_GET["return"];};echo '">
            <div class="panel-form-control"><input type="password" name="password" id="password_login" placeholder="Password" class="form-control"></div>
            <div class="panel-form-control"><button type="submit" class="btn btn-block btn-danger"><i class="fa fa-sign-in"></i> เข้าสู่ระบบ</button> </div>
        </form>
   	</div>
';}
;echo '    </div>
</div></div>
';$FOOTERKey="MjM4o7Qa3zcR5Jxi";require_once("pages/template.footer.php");;echo '</body>
</html>'; ?>
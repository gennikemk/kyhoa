<?php 
$DEFINE = TRUE;
require("include/config.php");
$VersionNewiMC = file_get_contents("backend/version.info");
;
echo '';
require_once('include/class.license.php');
;
echo '';
if ($_POST["do"] == "buyitem") :
if (!$isAuth) {
exit(json_encode(array("result"=>false,"msg"=>"Xin vui lòng đăng nhập hoặc đăng ký.")));
}
$ItemArr = ItemArr($_POST["itemid"]);
if (!$ItemArr) {
exit(json_encode(array("result"=>false,"msg"=>"Sản phẩm này không còn trong cơ sở dữ liệu")));
}
$UserPoint = $UserInfo['point'] -$ItemArr['price'];
if ($UserPoint <0) {
exit(json_encode(array("result"=>false,"msg"=>"Số dư của bạn không đủ cho sản phẩm này")));
}
if ($Config["feature"]["bungeecord"] == "yes") {
$SelectServer = $Config["feature"]["bungeecord_server"][$ItemArr["server_id"]];
$RCONSetting = array("ip"=>$SelectServer["ip"],"rcon_port"=>$SelectServer["rcon_port"],"rcon_password"=>$SelectServer["rcon_password"]);
$ServerTEXT = '<b>[#'.$ItemArr["server_id"] .']</b> '.$SelectServer["name"] .' <small>('.$SelectServer["id"] .')</small>';
$ServerQuery = ServerQuery(array("ip"=>$SelectServer["ip"],"port"=>$SelectServer["query_port"]));
}else {
$RCONSetting = array();
$ServerTEXT = "";
$ServerQuery = ServerQuery();
}
if ($Config["site"]["function"]["online_check"] == "yes") {
if (!$ServerQuery['status']) {
exit(json_encode(array("result"=>false,"msg"=>"Không thể kết nối với máy chủ trò chơi (Server Query) <br>".$ServerTEXT)));
}
$CheckPlayerOnline = CheckPlayerOnline($ServerQuery);
if ($CheckPlayerOnline['status'] == "offline") {
exit(json_encode(array("result"=>false,"msg"=>"Nhân vật của bạn không phải là bên trong trò chơi <br>".$ServerTEXT)));
}elseif ($CheckPlayerOnline['status'] == "failed") {
exit(json_encode(array("result"=>false,"msg"=>"Không thể kết nối với máy chủ trò chơi (Server Query)<br>".$ServerTEXT)));
}
}
$Command = array();
foreach (json_decode($ItemArr["value"]) as $DataID =>$DataValue) {
$Command[] = str_replace("<player>",$UserInfo['username'],$DataValue);
}
if ($Config['minecraft']['rcon']['tell_msg'] != "false") {
$CommandTell = str_replace("<item>",$ItemArr['name'],$Config['minecraft']['rcon']['tell_msg']);
$CommandTell = str_replace("<count>",$ItemArr['count'],$CommandTell);
$CommandTell = "tell ".$UserInfo['username'] ." ".$CommandTell;
$Command[] = $CommandTell;
}
if ($Config["feature"]["bungeecord"] == "yes") {
$ServerRCON = ServerRCON($Command,$RCONSetting);
if ($ServerRCON["status"]) {
mysqli_query($conn,"UPDATE `shop_item` SET `buycount`=(`buycount`+1) WHERE `id`='".$ItemArr['id'] ."' LIMIT 1; ");
$ShopLog = "Mua hàng <strong>".$ItemArr['name'] ." x".$ItemArr['count'] ."</strong> ด้วย <strong>".number_format($ItemArr['price'],2,'.',',') ." Point </strong> <br> ไปยังเซิฟเวอร์  ".$ServerTEXT;
ShopHistory("buyitem",$ShopLog);
UserPoint($ItemArr['price']);
exit(json_encode(array("result"=>true,"msg"=>$ShopLog,"newpoint"=>number_format($UserPoint,2,'.',','))));
}else {
exit(json_encode(array("result"=>false,"msg"=>"Không thể kết nối với máy chủ trò chơi (RCON)<br>".$ServerTEXT)));
}
}
if ($Config['minecraft']['command'] == "rcon") {
$ServerRCON = ServerRCON($Command);
if ($ServerRCON["status"]) {
mysqli_query($conn,"UPDATE `shop_item` SET `buycount`=(`buycount`+1) WHERE `id`='".$ItemArr['id'] ."' LIMIT 1; ");
$ShopLog = "Mua hàng <strong>".$ItemArr['name'] ." x".$ItemArr['count'] ."</strong> Với <strong>".number_format($ItemArr['price'],2,'.',',') ." Point </strong>";
ShopHistory("buyitem",$ShopLog);
UserPoint($ItemArr['price']);
exit(json_encode(array("result"=>true,"msg"=>$ShopLog,"newpoint"=>number_format($UserPoint,2,'.',','))));
}else {
exit(json_encode(array("result"=>false,"msg"=>"Không thể kết nối với máy chủ trò chơi (RCON)")));
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
mysqli_query($conn,"UPDATE `shop_item` SET `buycount`=(`buycount`+1) WHERE `id`='".$ItemArr['id'] ."' LIMIT 1; ");
$ShopLog = "Mua hàng <strong>".$ItemArr['name'] ." x".$ItemArr['count'] ."</strong> Với <strong>".number_format($ItemArr['price'],2,'.',',') ." Point </strong>";
ShopHistory("buyitem",$ShopLog);
UserPoint($ItemArr['price']);
exit(json_encode(array("result"=>true,"msg"=>$ShopLog,"newpoint"=>number_format($UserPoint,2,'.',','))));
}else {
exit(json_encode(array("result"=>false,"msg"=>"Không thể kết nối với máy chủ trò chơi (WebSend)")));
}
}
exit(json_encode(array("result"=>false,"msg"=>"End of process (Buyitem)")));
exit();
endif;
if ($_POST["do"] == "getitem") :
if ($_POST["itemid"] == "") {
exit(json_encode(array("result"=>false,"msg"=>"Vui lòng chọn sản phẩm bạn muốn")));
}
$ItemArr = ItemArr($_POST["itemid"]);
if (!$ItemArr) {
exit(json_encode(array("result"=>false,"msg"=>"Sản phẩm này không còn trong cơ sở dữ liệu")));
}
if ($ItemArr["count"] == 0) {
$ItemArr["count"] = 1;
}
unset($ItemArr['order'],$ItemArr['cat_id'],$ItemArr['value'],$ItemArr['type']);
$ArrayOutput = array("result"=>true);
$ItemArr["desc"] = $ItemArr["desc"] ."<div class=\"buycount-panel\">Đã được mua <b>".$ItemArr["buycount"] ."</b> Thời gian";
$ArrayOutput = array_merge($ArrayOutput,$ItemArr);
exit(json_encode($ArrayOutput));
exit(json_encode(array("result"=>false,"msg"=>"End last process (1)")));
endif;
$CategoryArr = array();
if ($Config["feature"]["bungeecord"] == "yes") {
foreach ($Config["feature"]["bungeecord_server"] as $ServerID =>$ServerData) {
$ServerData_NEW = array(
"id"=>$ServerID,
"name"=>$ServerData["name"],
"title"=>""
);
$CategoryArr[$ServerID] = $ServerData_NEW;
}
}else {
$Category = mysqli_query($conn,"SELECT * FROM `shop_category` ORDER BY `order` ASC ");
$CategoryArr = array();
while ($CatArr = mysqli_fetch_assoc($Category)) {
$CategoryArr[] = $CatArr;
}
}
if (isset($_GET["search"]) and $_GET["search"] == "") {
header("Location: shop.php");
exit();
}
if ($Config["site"]["function"]["site"] == "yes") {
DisplayMSG("danger","Website is not available right now.<br>Trang web hiện không có sẵn");
}
;
echo '<!doctype html>
<html><head>
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
<script>
';
if ($Config["feature"]["bungeecord"] == "yes") {
;
echo 'var $category = [';
for ($count = 0;$count <= count($CategoryArr) -1;$count++) {
echo '"category_'.$count .'",';
};
echo '];
';
}else {
;
echo 'var $category = [';
foreach ($CategoryArr as $CatNum =>$CatData) {
echo '"category_'.$CatData["id"] .'",';
};
echo '];
';
};
echo '</script>
</head>

<body>
';
$menu = "itemshop";
include("pages/template.header.php");
;
echo '<div class="modal" id="itemshop-details">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
      <div class="row">
        <div class="col-md-5">
			<div class="product-picture"><img class="img-responsive" style="max-width:100%;max-height:100%;"></div>
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
<div class="container panel-imc-page "><div class="row">
	<div class="col-md-3 col-md-push-9">
    	';
include("pages/template.right.php");
;
echo '    </div>
    <div class="col-md-9 col-md-pull-3">
	<div class="panel-imc-title">Itemshop <small class="hidden-sm">Các mục</small>
		<div class="pull-right col-lg-5 text-right" style="margin-right:-15px;">
			<form id="search-form" method="get" action="';
echo $_SERVER["PHP_SELF"];
echo '">
				<input type="text" class="form-control" name="search" id="search" placeholder="Search Tìm kiếm sản phẩm" ';
echo(($_GET["search"] == "") ?"": " value=\"".$_GET["search"] ."\"");
;
echo ' >
				<button type="submit" class="btn btn-sm btn-info"><i class="fa fa-search"></i> Tìm kiếm</button>
			</form>
		</div>
	</div>        
        <div class="panel-imc-content panel-imc-box">
';
if (isset($_GET["search"])) {
;
echo '			<div class="category-tab"> <a style="cursor:default;">
				<i class="fa fa-search" style="font-size:14px;"></i> <small>Bạn đang tìm kiếm</small> ';
echo $_GET["search"];
echo '				<a href="';
echo $_SERVER["PHP_SELF"];
echo '" style="color:red;"><i class="fa fa-times" title="Huỷ tìm kiếm"></i> Huỷ tìm kiếm</a>
			</a>
			</div>
				
			<div class="product-panel" style="position:relative;">
                <ul class="product-list">
';
$ItemQuery = mysqli_query($conn,"SELECT * FROM `shop_item` WHERE `name` LIKE '%".mysqli_real_escape_string($conn,$_GET["search"]) ."%' OR `desc` LIKE '%".mysqli_real_escape_string($conn,$_GET["search"]) ."%' ORDER BY `id` DESC ");
while ($Item = mysqli_fetch_assoc($ItemQuery)) {
echo ProductList($Item,$Item["cat_id"]);
}
;
echo '				</ul>
			</div>
			
			<div class="category-tab"> <a style="cursor:default;">
				<i class="fa fa-search" style="font-size:14px;"></i> <small>Bạn đang tìm kiếm</small> ';
echo $_GET["search"];
echo '				<a href="';
echo $_SERVER["PHP_SELF"];
echo '" style="color:red;"><i class="fa fa-times" title="Huỷ tìm kiếm"></i> Huỷ tìm kiếm</a>
			</a>
			</div>

';
}else {
;
echo '            <div class="category-tab">
                ';
if ($Config["feature"]["bungeecord"] == "no") {
;
echo '<a class="tab-a active" rel="all">All Item <small>Tất cả sản phẩm</small> </a>';
};
echo '';
foreach ($CategoryArr as $ID =>$Cat) {
;
echo ' 
                <a class="tab-a tab-category';
echo($Config["feature"]["bungeecord"] == "yes"AND $ID == 0) ?" active": "";
;
echo '" rel="category_';
echo $Cat['id'];
echo '">';
echo $Cat['name'];
echo ' <small>';
echo $Cat['title'];
echo '</small> </a>
';
};
echo ' 
            </div>
            <div class="product-panel" style="position:relative;">
                <ul class="product-list">
';
if ($Config["feature"]["bungeecord"] == "yes") {
foreach ($CategoryArr as $ServerID =>$ServerData) {
$ItemQuery = mysqli_query($conn,"SELECT * FROM `shop_item` WHERE `server_id`='".$ServerID ."' ORDER BY `order` ASC ");
while ($Item = mysqli_fetch_assoc($ItemQuery)) {
echo ProductList($Item,$Item["server_id"]);
}
}
}else {
$CountCat = 1;
$CategoryQuery = mysqli_query($conn,"SELECT * FROM `shop_category` ORDER BY `order` ASC ");
while ($CatArr = mysqli_fetch_array($CategoryQuery)) {
$ItemQuery = mysqli_query($conn,"SELECT * FROM `shop_item` WHERE `cat_id`='".$CatArr['id'] ."' ORDER BY `order` ASC ");
while ($Item = mysqli_fetch_assoc($ItemQuery)) {
echo ProductList($Item,$Item["cat_id"]);
}
}
$CountCat++;
}
;
echo '                </ul>
             </div>
            <div class="category-tab">
                ';
if ($Config["feature"]["bungeecord"] == "no") {
;
echo '<a class="tab-a active" rel="all">All Item <small>สินค้าทั้งหมด</small> </a>';
};
echo '';
foreach ($CategoryArr as $ID =>$Cat) {
;
echo ' 
                <a class="tab-a tab-category';
echo($Config["feature"]["bungeecord"] == "yes"AND $ID == 0) ?" active": "";
;
echo '" rel="category_';
echo $Cat['id'];
echo '">';
echo $Cat['name'];
echo ' <small>';
echo $Cat['title'];
echo '</small> </a>
';
};
echo ' 
            </div>
';
}
;
echo '        </div>
    </div>
    
</div></div>

';
$FOOTERKey = "MjM4o7Qa3zcR5Jxi";
require_once("pages/template.footer.php");
;
echo '</body>
</html>'; ?>

<?php
require('../conf/config.php3');
require('../lib/functions.php3');
?>
<html>
<?php

if (is_file("../lib/sql/drivers/$config[sql_type]/functions.php3"))
	include_once("../lib/sql/drivers/$config[sql_type]/functions.php3");
else{
	echo <<<EOM
<title>User Statistics</title>
<meta http-equiv="Content-Type" content="text/html; charset=$config[general_charset]">
<link rel="stylesheet" href="style.css">
</head>
<body bgcolor="#80a040" background="images/greenlines1.gif" link="black" alink="black">
<center>
<b>Could not include SQL library functions. Aborting</b>
</body>
</html>
EOM;
	exit();
}

if ($start == '' && $stop == ''){
	$now = time();
	$stop = date($config[sql_date_format],$now);
	$now -= 604800;
	$start = date($config[sql_date_format],$now);
}
$pagesize = ($pagesize) ? $pagesize : 10;
$limit = ($pagesize == 'all') ? '' : "LIMIT $pagesize";
$selected[$pagesize] = 'selected';
$order = ($order) ? $order : $config[general_accounting_info_order];
if ($order != 'desc' && $order != 'asc')
	$order = 'desc';
if ($sortby != '')
	$order_attr = ($sortby == 'num') ? 'ConnNum' : 'ConnTotDuration';
else
	$order_attr = 'ConnNum';
if ($server != '' && $server != 'all')
	$server_str = "AND NASIPAddress = '$server'";
$login_str = ($login) ? "AND UserName = '$login' " : '';

$selected[$order] = 'selected';
$selected[$sortby] = 'selected';

?>

<head>
<title>User Statistics</title>
<link rel="stylesheet" href="style.css">
</head>
<body bgcolor="#80a040" background="images/greenlines1.gif" link="black" alink="black">
<center>
<table border=0 width=550 cellpadding=0 cellspacing=0>
<tr valign=top>
<td align=center><img src="images/title2.gif"></td>
</tr>
</table>
<table border=0 width=400 cellpadding=0 cellspacing=2>
</table>
<br>
<table border=0 width=840 cellpadding=1 cellspacing=1>
<tr valign=top>
<td width=65%></td>
<td bgcolor="black" width=35%>
	<table border=0 width=100% cellpadding=2 cellspacing=0>
	<tr bgcolor="#907030" align=right valign=top><th>
	<font color="white">User Statistics</font>&nbsp;
	</th></tr>
	</table>
</td></tr>
<tr bgcolor="black" valign=top><td colspan=2>
	<table border=0 width=100% cellpadding=12 cellspacing=0 bgcolor="#ffffd0" valign=top>
	<tr><td>
<?php
echo <<<EOM
<b>$start</b> up to <b>$stop</b>
EOM;
?>

<p>
	<table border=1 bordercolordark=#ffffe0 bordercolorlight=#000000 width=100% cellpadding=2 cellspacing=0 bgcolor="#ffffe0" valign=top>
	<tr bgcolor="#d0ddb0">
	<th>#</th><th>login</th><th>date</th><th>server</th><th>connections number</th><th>connections duration</th><th>upload</th><th>download</th>
	</tr>

<?php
$link = @da_sql_pconnect($config);
if ($link){
	$search = @da_sql_query($link,$config,
	"SELECT * FROM $config[sql_total_accounting_table]
	WHERE AcctDate >= '$start' AND AcctDate <= '$stop' $server_str $login_str
	ORDER BY $order_attr $order $limit;");

	if ($search){
		while( $row = @da_sql_fetch_array($search,$config) ){
			$num++;
			$acct_login = $row[UserName];
			if ($acct_login == '')
				$acct_login = '-';
			else
				$acct_login = "<a href=\"user_admin.php3?login=$acct_login\" title=\"Edit user $acct_login\">$acct_login</a>";
			$acct_time = $row[ConnTotDuration];
			$acct_time = time2str($acct_time);
			$acct_conn_num = $row[ConnNum];
			$acct_date = $row[AcctDate];
			$acct_upload = $row[InputOctets];
			$acct_download = $row[OutputOctets];
			$acct_upload = bytes2str($acct_upload);
			$acct_download = bytes2str($acct_download);
			$acct_server = $da_name_cache[$row[NASIPAddress]];
			if (!isset($acct_server)){
				$acct_server = @gethostbyaddr($row[NASIPAddress]);
				if (!isset($da_name_cache) && $config[general_use_session] == 'yes'){
					$da_name_cache[$row[NASIPAddress]] = $acct_server;
					session_register('da_name_cache');
				}
				else
					$da_name_cache[$row[NASIPAddress]] = $acct_server;
			}
			if ($acct_server == '')
				$acct_server = '-';
			echo <<<EOM
			<tr align=center bgcolor="white">
				<td>$num</td>
				<td>$acct_login</td>
				<td>$acct_date</td>
				<td>$acct_server</td>
				<td>$acct_conn_num</td>
				<td>$acct_time</td>
				<td>$acct_upload</td>
				<td>$acct_download</td>
			</tr>
EOM;
		}
	}
}
echo <<<EOM
	</table>
<tr><td>
<hr>
<tr><td align="left">
	<form action="user_stats.php3" method="post" name="master">
	<table border=0>
		<tr valign="bottom">
			<td><small><b>start time</td><td><small><b>stop time</td><td><small><b>pagesize</td><td><b>sort by</td><td><b>order</td>
	<tr valign="middle"><td>
<input type="hidden" name="show" value="0">
<input type="text" name="start" size="11" value="$start"></td>
<td><input type="text" name="stop" size="11" value="$stop"></td>
<td><select name="pagesize">
<option $selected[5] value="5" >05
<option $selected[10] value="10">10
<option $selected[15] value="15">15
<option $selected[20] value="20">20
<option $selected[40] value="40">40
<option $selected[80] value="80">80
<option $selected[all] value="all">all
</select>
</td>
<td>
<select name="sortby">
<option $selected[num] value="num">connections number
<option $selected[time] value="time">connections duration
</select>
</td>
<td><select name="order">
<option $selected[asc] value="asc">ascending
<option $selected[desc] value="desc">descending
</select>
</td>
EOM;
?>

<td><input type="submit" class=button value="show"></td></tr>
<tr><td>
<b>On Access Server:</b>
</td>
<td><b>User</b></td></tr>
<tr><td>
<select name="server">
<?php
while(1){
	$i++;
	$name = 'nas' . $i . '_name';
	if ($config[$name] == ''){
		$i--;
		break;
	}
	$name_ip = 'nas' . $i . '_ip';
	if ($server == $config[$name_ip])
		echo "<option selected value=\"$config[$name_ip]\">$config[$name]\n";
	else
		echo "<option value=\"$config[$name_ip]\">$config[$name]\n";
}
if ($server == '' || $server == 'all')
	echo "<option selected value=\"all\">all\n";
?>
</select>
</td>
<td><input type="text" name="login" size="11" value="<?php echo $login ?>"></td>
</tr>
</table></td></tr></form>
</table>
</tr>
</table>
</body>
</html>

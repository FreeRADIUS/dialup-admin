<?php
require('../conf/config.php3');
require('../lib/attrshow.php3');
if (!isset($usage_summary)){
	echo <<<EOM
<html>
<head>
<META HTTP-EQUIV="Refresh" CONTENT="50">
<meta http-equiv="Content-Type" content="text/html; charset=$config[general_charset]">
<title>User Finger Facility</title>
<link rel="stylesheet" href="style.css">
</head>
EOM;
}

if ($config[general_decode_normal_attributes] == 'yes'){
	if (is_file("../lib/lang/$config[general_prefered_lang]/utf8.php3"))
		include_once("../lib/lang/$config[general_prefered_lang]/utf8.php3");
	else
		include_once('../lib/lang/default/utf8.php3');
	$k = init_decoder();
	$decode_normal = 1;
}
require('../lib/functions.php3');
require("../lib/$config[general_lib_type]/functions.php3");

if (is_file("../lib/sql/drivers/$config[sql_type]/functions.php3"))
	include_once("../lib/sql/drivers/$config[sql_type]/functions.php3");
else{
	echo <<<EOM
<body bgcolor="#80a040" background="images/greenlines1.gif" link="black" alink="black">
<center>
<b>Could not include SQL library functions. Aborting</b>
</body>
</html>
EOM;
	exit();
}

$date = strftime('%A, %e %B %Y, %T %Z');

$sql_extra_query = '';
if ($config[sql_accounting_extra_query] != '')
	$sql_extra_query = sql_xlat($config[sql_accounting_extra_query],$login,$config);

$link = @da_sql_pconnect($config);
$link2 = connect2db($config);
$tot_in = $tot_rem = 0;
if ($link){
	$h = 21;
	$servers_num = 0;
	foreach($nas_list as $nas){
		$num = 0;

		if ($server != ''){
			if ($nas[name] == $server)
				$servers_num++;
			else
				continue;
		}
		else
			$servers_num++;
		$name_data = $nas[ip];
		$community_data = $nas[community];
		$server_name[$servers_num] = $nas[name];
		$server_model[$servers_num] = $nas[model];
		if ($config[general_ld_library_path] != '')
			putenv("LD_LIBRARY_PATH=$config[general_ld_library_path]");
		$extra = "";
		if ($config[$finger_type] != 'database' && $config[general_finger_type] == 'snmp'){
			if ($config[$nas_type] == '')
				$nas_type = $config[general_nas_type];
			else
				$nas_type = $nas[type];
			if ($nas_type == '')
				$nas_type = 'cisco';

			$users=exec("$config[general_snmpfinger_bin] $name_data $community_data $nas_type");
			if (strlen($users))
				$extra = "AND username IN ($users)";
		}
		$search = @da_sql_query($link,$config,
		"SELECT DISTINCT username,acctstarttime,framedipaddress,callingstationid
		FROM $config[sql_accounting_table] WHERE
		acctstoptime IS NULL AND nasipaddress = '$name_data' $extra $sql_extra_query
		GROUP BY username ORDER BY acctstarttime;");
		if ($search){
			$now = time();
			while($row = @da_sql_fetch_array($search,$config)){
				$num++;
				$h += 21;
				$user = $row['username'];
				$finger_info[$servers_num][$num]['ip'] = $row['framedipaddress'];
				if ($finger_info[$servers_num][$num]['ip'] == '')
					$finger_info[$servers_num][$num]['ip'] = '-';
				$session_time = $row['acctstarttime'];
				$session_time = date2timediv($session_time,$now);
				$finger_info[$servers_num][$num]['session_time'] = time2strclock($session_time);
				$finger_info[$servers_num][$num]['user'] = $user;
				$finger_info[$servers_num][$num]['callerid'] = $row['callingstationid'];
				if ($finger_info[$servers_num][$num]['callerid'] == '')
					$finger_info[$servers_num][$num]['callerid'] = '-';
				if ($user_info["$user"] == ''){
					$user_info["$user"] = get_user_info($link2,$user,$config,$decode_normal,$k);
					if ($user_info["$user"] == '' || $user_info["$user"] == ' ')
						$user_info["$user"] = 'Unknown User';
				}
			}
			$height[$servers_num] = $h;
		}
		$server_loggedin[$servers_num] = $num;
		$server_rem[$servers_num] = ($config[$portnum]) ? ($config[$portnum] - $num) : 'unknown';
		$tot_in += $num;
		if (is_numeric($server_rem[$servers_num]))
			$tot_rem += $server_rem[$servers_num];
	}
}
else
	echo "<b>Could not connect to SQL database</b><br>\n";
if (isset($usage_summary)){
	echo "Online: $tot_in Free: $tot_rem\n";
	exit();
}
?>

<body bgcolor="#80a040" background="images/greenlines1.gif" link="black" alink="black">
<center>
<table border=0 width=550 cellpadding=0 cellspacing=0>
<tr valign=top>
<td align=center><img src="images/title2.gif"></td>
</tr>
</table>
<br>
<table border=0 width=540 cellpadding=1 cellspacing=1>
<tr valign=top>
<td width=340></td>
<td bgcolor="black" width=200>
	<table border=0 width=100% cellpadding=2 cellspacing=0>
	<tr bgcolor="#907030" align=right valign=top><th>
	<font color="white">Online Users</font>&nbsp;
	</th></tr>
	</table>
</td></tr>
<tr bgcolor="black" valign=top><td colspan=2>
	<table border=0 width=100% cellpadding=12 cellspacing=0 bgcolor="#ffffd0" valign=top>
	<tr><td>
<?php
echo <<<EOM
	<b>$date</b>
EOM;
	for($j = 1; $j <= $servers_num; $j++){
		echo <<<EOM
<p>
	<table width=100% cellpadding=0 height=30><tr>
	<th align=left>$server_name[$j]<br><font color="green">$server_model[$j]</font></th><th align=right><font color="red">$server_loggedin[$j] users connected</font></th><th><font color="green">$server_rem[$j] $config[general_caption_finger_free_lines]</font></th>
	</tr>
	</table>
	<div height="$height[$j]" style="height:$height[$j]">
	<table border=1 bordercolordark=#ffffe0 bordercolorlight=#000000 width=100% cellpadding=2 cellspacing=0 bgcolor="#ffffe0" valign=top>
	<tr bgcolor="#d0ddb0">
	<th>#</th><th>user</th>
EOM;
	if ($acct_attrs['uf'][4] != '')	echo "<th>" . $acct_attrs[uf][4] . "</th>\n";
	if ($acct_attrs['uf'][9] != '') echo "<th>" . $acct_attrs[uf][9] . "</th>\n";
echo <<<EOM
	<th>name</th><th>duration</th>
	</tr>
EOM;
	for( $k = 1; $k <= $server_loggedin[$j]; $k++){
		$user = $finger_info[$j][$k][user];
		if ($user == '')
			$user = '&nbsp;';
		$time = $finger_info[$j][$k][session_time];
		$ip = $finger_info[$j][$k][ip];
		$cid = $finger_info[$j][$k][callerid];
		$inf = $user_info[$user];
		echo <<<EOM
	<tr align=center>
	<td>$k</td><td><a href="user_admin.php3?login=$user" title="Edit User $user">$user</a></td>
EOM;
if ($acct_attrs['uf'][4] != '') echo "<td>$ip</td>\n";
if ($acct_attrs['uf'][9] != '') echo "<td>$cid</td>\n";
echo <<<EOM
<td>$inf</td><td>$time</td>
	</tr>
EOM;
	}

	echo <<<EOM
	</table>
	</div>
EOM;
}
?>
	</td></tr>
	</table>
</td></tr>
</table>
<p>
</html>

<?php
#Read ldap attribute map
if (!isset($attrmap)){
	$ARR = file("$config[general_ldap_attrmap]");
	foreach($ARR as $val){
		$val=chop($val);
		if (ereg('^[[:space:]]*#',$val) || ereg('^[[:space:]]*$',$val))
			continue;
		list(,$key,$v,$g)=split('[[:space:]]+',$val);
		$v = strtolower($v);
		$attrmap["$key"]=$v;
		$attrmap[generic]["$key"]=$g;
	}
	$ARR = file("$config[general_extra_ldap_attrmap]");
	foreach($ARR as $val){
		$val=chop($val);
		if (ereg('^[[:space:]]*#',$val) || ereg('^[[:space:]]*$',$val))
			continue;
		list(,$key,$v,$g)=split('[[:space:]]+',$val);
		$v = strtolower($v);
		$attrmap["$key"]=$v;
		$attrmap[generic]["$key"]=$g;
	}
	if ($config[general_use_session] == 'yes')
		session_register('attrmap');
}
?>

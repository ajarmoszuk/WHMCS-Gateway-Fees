<?php

if (!defined("WHMCS")) die("This file cannot be accessed directly");

function gateway_fees_config()
{
	$configarray = array(
		"name" => "Gateway Fees for WHMCS",
		"description" => "Add fees based on the gateway being used.",
		"version" => "1.0.1",
		"author" => "Open Source"
	);
	$result = select_query("tblpaymentgateways", "", "", "", "");
	while ($data = mysql_fetch_array($result)) {
		$configarray['fields']["fee_1_" . $data['gateway']] = array(
			"FriendlyName" => $data['gateway'],
			"Type" => "text",
			"Default" => "0.00",
			"Description" => "$"
		);
		$configarray['fields']["fee_2_" . $data['gateway']] = array(
			"FriendlyName" => $data['gateway'],
			"Type" => "text",
			"Default" => "0.00",
			"Description" => "%<br />"
		);
	}

	return $configarray;
}

function gateway_fees_activate()
{
	$result = mysql_query('select * from tblpaymentgateways group by gateway');
	while ($data = mysql_fetch_array($result)) {
		$query2 = "insert into `tbladdonmodules` (module,setting,value) value ('gateway_fees','fee_1_" . $data['gateway'] . "','0.00' )";
		$result2 = mysql_query($query2);
		$query3 = "insert into `tbladdonmodules` (module,setting,value) value ('gateway_fees','fee_2_" . $data['gateway'] . "','0.00' )";
		$result3 = mysql_query($query3);
	}
}

?>

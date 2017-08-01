<?

include_once("ip_functions.inc");
include_once("ipm_functions.inc");
include_once('types_macro.inc');
include_once('errors.inc');

if (isset($ipm_argv['is_ipm_register']) && $ipm_argv['is_ipm_register'])
{
  ipm_register_rule('ip_add', 'ip', 'rule-420'); // "Create Device in AVAYA IDE");
  return;
}

//
// Gathering basics
//

$ide_Name=$ipm_argv['ip_name'];
$ide_Class=$ipm_argv['ip_class_name'];
$ide_IP_Addr=ip_addr_to_hostaddr($ipm_argv['ip_addr']);
$ide_MAC_Addr=$ipm_argv['ip_mac_addr'];

// 
// Fetching Subnet Name and Vlan OID from Subnet OID
//

$query='select vlmvlan_id,subnet_name from subnet where oid='.$ipm_argv['subnet_id'];
$o = ipm_query_sql2($query);
while ($line = ipm_fetch_result($o))
{
  $ide_Subnet_Name=$line[subnet_name];
  
  //
  // Fetching Vlan ID and Vlan Name
  //

  $query2='select vlmvlan_vlan_id,vlmvlan_name from vlmvlan where oid='.$line[vlmvlan_id];
  $o2 = ipm_query_sql2($query2);
  $line2= ipm_fetch_result($o2);

  $ide_vlanID=$line2[vlmvlan_vlan_id];
  $ide_vlanName=$line2[vlmvlan_name];
}

//
// Printing debug
//

syslog(LOG_INFO,"Ajout/Edition IPAM - Device");
syslog(LOG_INFO,"  Nom        : ".$ide_Name);
syslog(LOG_INFO,"  Classe     : ".$ide_Class);
syslog(LOG_INFO,"  Adresse IP : ".$ide_IP_Addr);
syslog(LOG_INFO,"  MAC        : ".$ide_MAC_Addr);
syslog(LOG_INFO,"  Subnet     : ".$ide_Subnet_Name);
syslog(LOG_INFO,"  VLAN Name  : ".$ide_vlanName);
syslog(LOG_INFO,"  VLAN ID    : ".$ide_vlanID);

//
// Push to AVAYA ID Engine Manager
//

// URL for API entry point

$url = "https://192.168.220.5/GuestManager/api/devices";

// XML Body

$post_string = '<?xml version="1.0" encoding="UTF-8"?>
<Device>
<provisioningGroupName>IPAM</provisioningGroupName>
<macAddress>'.$ide_MAC_Addr.'</macAddress>
<name>'.$ide_Name.'</name>
<type>pc</type>
<vlanLabel>Bureautique</vlanLabel>
<vlanId>'.$ide_vlanID.'</vlanId>
</Device>';

// Building HTTP Headers

$header  = "POST /GuestManager/api/devices HTTP/1.1 \r\n";
$header .= "Host: 192.168.220.5 \r\n";
$header .= "Accept: application/xml \r\n";
$header .= "Accept-Language: null \r\n";
$header .= "Accept-Encoding: gzip, deflate \r\n";
$header .= "api-version: v1.1.0 \r\n";
$header .= "Content-type: application/xml \r\n";
$header .= "Authorization: Basic U19BUFBfSE9UU1BPVF9SRVNUOml6bHFHZVFyRXkxSVJ1S29sVXlN \r\n";
$header .= "Content-length: ".strlen($post_string)." \r\n";
$header .= "Content-transfer-encoding: text \r\n";
$header .= "Connection: keep-alive \r\n\r\n"; 
$header .= $post_string;

syslog(LOG_INFO,"--->> POST AYAVA IDE    : ".$header);

// Sending POST request

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 4);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $header);

$data = curl_exec($ch); 
syslog(LOG_INFO,"Curl Error    : ".$data);
if(curl_errno($ch))
{
	syslog(LOG_INFO,"Curl Error    : ".curl_error($ch));
	syslog(LOG_INFO,"Curl Error    : ".$data);
	print curl_error($ch);
}
else
    curl_close($ch);
?>

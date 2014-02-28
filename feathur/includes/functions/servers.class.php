<?php
class Server extends CPHPDatabaseRecordClass {

	public $table_name = "servers";
	public $id_field = "id";
	public $fill_query = "SELECT * FROM servers WHERE `id` = :Id";
	public $verify_query = "SELECT * FROM servers WHERE `id` = :Id";
	public $query_cache = 1;
	
	public $prototype = array(
		'string' => array(
			'Name' => "name",
			'User' => "user",
			'IPAddress' => "ip_address",
			'Key' => "key",
			'Type' => "type",
			"URL" => "url",
			"StatusType" => "status_type",
			"LoadAlert" => "load_alert",
			"RAMAlert" => "ram_alert",
			"HardDiskAlert" => "hard_disk_alert",
			"HardwareUptime" => "hardware_uptime",
			"TotalMemory" => "total_memory",
			"FreeMemory" => "free_memory",
			"LoadAverage" => "load_average",
			"HardDiskFree" => "hard_disk_free",
			"HardDiskTotal" => "hard_disk_total",
			"Bandwidth" => "bandwidth",
			"Port" => "port",
			"Location" => "location",
			"VolumeGroup" => "volume_group",
			"QEMUPath" => "qemu_path",
		),
		'numeric' => array(
			"Password" => "password",
			"LastCheck" => "last_check",
			"PreviousCheck" => "previous_check",
			"UpSince" => "up_since",
			"DownSince" => "down_since",
			"AlertAfter" =>	"alert_after",
			"LoadAlert" => "load_alert",
			"RAMAlert" => "ram_alert",
			"HardDiskAlert" => "hard_disk_alert",
			"DisplayMemory" => "display_memory",
			"DisplayLoad" => "display_load",
			"DisplayHardDisk" => "display_hard_disk",
			"DisplayNetworkUptime" => "display_network_uptime",
			"DisplayHardwareUptime" => "display_hardware_uptime",
			"DisplayLocation" => "display_location",
			"DisplayHistory" => "display_history",
			"DisplayStatistics" => "display_statistics",
			"DisplayHistoryLink" => "display_hs",
			"DisplayBandwidth" => "display_bandwidth",
			"DisplayHS" => "display_hs",
			"HardwareUptime" => "hardware_uptime",
			"TotalMemory" => "total_memory",
			"FreeMemory" => "free_memory",
			"LoadAverage" => "load_average",
			"HardDiskFree" => "hard_disk_free",
			"HardDiskTotal" => "hard_disk_total",
			"Bandwidth" => "bandwidth",
			"LastBandwidth" => "last_bandwidth",
			"BandwidthTimestamp" => "bandwidth_timestamp",
			"ContainerBandwidth" => "container_bandwidth",
		),
		'boolean' => array(
			"Status" => "status",
			"StatusWarning" => "status_warning",
		),
	);
	
	public static function server_connect($sServer, $sAPI = 0){
		$sSSH = new Net_SSH2($sServer->sIPAddress);
		
		if($sServer->sPassword == 0){
			$sKey = new Crypt_RSA();
			$sKey->loadKey(file_get_contents('/var/feathur/data/keys/'.$sServer->sKey));
		} else {
			$sKey = file_get_contents('/var/feathur/data/keys'.$sServer->sKey);
		}
		try {
			if (!$sSSH->login($sServer->sUser, $sKey)) {
				if(!empty($sAPI)){
					return $sResult = array("result" => 'Unable to connect to the host node, please contact customer serivce.');
				}
				echo json_encode(array("result" => 'Unable to connect to the host node, please contact customer serivce.'));
				die();
			} else {
				$sSSH->setTimeout(30);
				return $sSSH;
			}
		} catch (Exception $e) { 
			if(!empty($sAPI)){
				return $sResult = array("result" => 'Unable to connect to the host node, please contact customer serivce.');
			}
			echo json_encode(array("result" => 'Unable to connect to the host node, please contact customer serivce.'));
			die();
		}
	}
}

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
	
	public function Connect($sAPI = 0)
	{
		/* TODO: Needs clean-up. */
		$sSSH = new Net_SSH2($this->sIPAddress);
		
		if($this->sPassword == 0){
			$sKey = new Crypt_RSA();
			$sKey->loadKey(file_get_contents('/var/feathur/data/keys/'.$this->sKey));
		} else {
			$sKey = file_get_contents('/var/feathur/data/keys'.$this->sKey);
		}
		try {
			if (!$sSSH->login($this->sUser, $sKey)) {
				throw new ConnectionException("Unable to connect to the host node, please contact customer serivce.");
			} else {
				$sSSH->setTimeout(30);
				return $sSSH;
			}
		} catch (Exception $e) { 
			throw new ConnectionException("Unable to connect to the host node, please contact customer serivce.");
		}
	}
	
	public function RequireIPs($amount)
	{
		$total_available = 0;
		
		try {
			$sBlocks = Block::CreateFromQuery("SELECT * FROM server_blocks WHERE `server_id` = :ServerId AND `ipv6` = 0", array("ServerId" => $this->sId));
		} catch (NotFoundException $e) { throw new IpSpaceException("The selected server does not have any IP blocks assigned."); }
		
		foreach($sBlocks as $sBlock)
		{
			try {
				$sIpAddresses = IP::CreateFromQuery("SELECT * FROM ipaddresses WHERE `block_id` = :BlockId AND `vps_id` = 0", array("BlockId" => $sBlock->sId));
			} catch (NotFoundException $e) { continue; /* 0 available in this block, move on to next block. */ }
			
			$total_available += count($sIpAddresses);
		}
		
		if($total_available < $amount)
		{
			throw new IpSpaceException("There are not enough IP addresses available on the selected server.");
		}
	}
	
	/* Deprecated methods below... */
	
	public static function server_connect($sServer, $sAPI = 0){
		/* DEPRECATED, only for compatibility. TODO: Log deprecation warning when used. */
		try
		{
			$sServer->Connect($sAPI);
		}
		catch (ConnectionException $e)
		{
			if(!empty($sAPI))
			{
				return array("result" => $e->getMessage());
			}
			
			echo(json_encode($e->getMessage()));
			die();
		}
	}
}

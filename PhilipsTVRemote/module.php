<?
class PhilipsTVRemote extends IPSModule
{
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyString("MAC", "00:00:00:00:00:00");
		
		
		// Profile anlegen
		$this->RegisterProfileInteger("PhilipsTVRemote.Volume", "Music", "", "", 0, 60, 1);
		
		// Status Variablen anlegen
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		
		$this->RegisterVariableBoolean("State", "Status", "~Switch", 10);
		$this->EnableAction("State");

		$this->RegisterVariableString("Menulanguage", "Menü-Sprache", "", 10);
		$this->RegisterVariableString("Name", "TV-Typ", "", 10);
		$this->RegisterVariableString("Country", "Land", "", 10);

		$this->RegisterVariableInteger("Volume", "Volume", "PhilipsTVRemote.Volume", 10);
		$this->EnableAction("Volume");
		
		$this->RegisterVariableBoolean("Mute", "Mute", "~Switch", 10);
		$this->EnableAction("Mute");
	}
	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "Erforderlich für Wake-On-LAN");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "MAC", "caption" => "MAC");
		
 		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "caption" => "Test Center"); 
		$arrayElements[] = array("type" => "TestCenter", "name" => "TestCenter");
		
		
		
		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	} 
	
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$this->GetSystemData();
			$this->GetAudioData();
			
			
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			
		}	   
	}
	
	
	
	public function RequestAction($Ident, $Value) 
	{
  		If ($this->ReadPropertyBoolean("Open") == true) {
			$IP = $this->ReadPropertyString("IPAddress");
			switch($Ident) {
				case "State":
					$this->SetValue($Ident, $Value);
					If ($Value == false) {
						// On
						$MAC = $this->ReadPropertyString("MAC");
		
						if (filter_var($MAC, FILTER_VALIDATE_MAC)) {
			 				$this->WakeOnLAN();
						} 
						else {
							//$this->Send_Key("26");
						}
					}
					elseif ($Value == true) {
						// Off
						//$this->Send_Key("26");
					}
					
					break;	
				case "Mute":
					$this->SetState('http://'.$IP.':1925/6/audio/volume', 'muted', $Value);
					$this->GetAudioData();
					break;
				case "Mute":
					$this->SetState('http://'.$IP.':1925/6/audio/volume', 'current', $Value);
					$this->GetAudioData();
					break;
				default:
				    throw new Exception("Invalid Ident");
			}
		}
	}

	public function SetState(String $URL, String $Key, String $Value)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$data = array($Key => $Value);
			// encoding the request data as JSON which will be sent in POST
			$encodedData = json_encode($data);
			// initiate curl with the url to send request
			$curl = curl_init($URL);
			// return CURL response
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			// Send request data using POST method
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
			// Data conent-type is sent as JSON
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			curl_setopt($curl, CURLOPT_POST, true);
			// Curl POST the JSON data to send the request
			curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedData);
			// execute the curl POST request and send data
			$Result = curl_exec($curl);
			curl_close($curl);

			If ($Result === false) {
				$this->SendDebug("GetState", "Fehler beim Daten-Update", 0);
				return($Result);
			}
			else {
				$this->SetValueWhenChanged("LastUpdate", time() );
				return($Result);
			}
		}
	}

	
	public function GetState(String $URL)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $URL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$Result = curl_exec($ch);
			curl_close($ch);

			If ($Result === false) {
				$this->SendDebug("GetState", "Fehler beim Daten-Update", 0);
				return($Result);
			}
			else {
				$this->SetValueWhenChanged("LastUpdate", time() );
				return($Result);
			}
		}
	}


	
	private function GetSystemData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IP = $this->ReadPropertyString("IPAddress");
			$URL = 'http://'.$IP.':1925/6/system';

			$Result = $this->GetState($URL);
			If ($Result === false) {
				exit;
			}
			else {
				$Data = json_decode($Result);
				$this->SetValueWhenChanged("Menulanguage", $Data->{'menulanguage'});
				$this->SetValueWhenChanged("Name", $Data->{'name'});
                    		$this->SetValueWhenChanged("Country", $Data->{'country'});
			}
		}
	}
	
	private function GetAudioData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IP = $this->ReadPropertyString("IPAddress");
			$URL = 'http://'.$IP.':1925/6/audio/volume';

			$Result = $this->GetState($URL);
			If ($Result === false) {
				exit;
			}
			else {
				$Data = json_decode($Result);
				$this->SetValueWhenChanged("Volume", $Data->{'current'});
				$this->SetValueWhenChanged("Mute", bolval($Data->{'muted'}));
			}
		}
	}

	private function GetSourcesData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IP = $this->ReadPropertyString("IPAddress");
			$URL = 'http://'.$IP.':1925/6/sources';

			$Result = $this->GetState($URL);
			If ($Result === false) {
				exit;
			}
			else {
				$Data = json_decode($Result);
				
			}
		}
	}

	private function GetCurrentSourcesData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IP = $this->ReadPropertyString("IPAddress");
			$URL = 'http://'.$IP.':1925/6/sources/current';

			$Result = $this->GetState($URL);
			If ($Result === false) {
				exit;
			}
			else {
				$Data = json_decode($Result);
				
			}
		}
	}

	private function SetValueWhenChanged($Ident, $Value)
    	{
        	if ($this->GetValue($Ident) != $Value) {
            		$this->SetValue($Ident, $Value);
        	}
    	}    
	
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 100)) {
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
		      	$result = true;
		}
		else {
			IPS_LogMessage("PhilipsTVRemote","IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			$this->SendDebug("ConnectionTest", "IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!", 0);
			$this->SetValue("State", false);
			
			$MAC = $this->ReadPropertyString("MAC");
		
			if (filter_var($MAC, FILTER_VALIDATE_MAC)) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
			} 
			else {
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
		}
	return $result;
	}
	
	private function WakeOnLAN()
	{
    		$mac = $this->ReadPropertyString("MAC");
		
		$broadcast = "255.255.255.255";
		$mac_array = preg_split('#:#', $mac);
    		$hwaddr = '';

    		foreach($mac_array AS $octet)
    		{
        		$hwaddr .= chr(hexdec($octet));
    		}

    		// Create Magic Packet
    		$packet = '';
    		for ($i = 1; $i <= 6; $i++)
    		{
        		$packet .= chr(255);
    		}

    		for ($i = 1; $i <= 16; $i++)
    		{
        		$packet .= $hwaddr;
    		}

    		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    		if ($sock)
    		{
        		$options = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);

        		if ($options >=0) 
        		{    
            			$e = socket_sendto($sock, $packet, strlen($packet), 0, $broadcast, 7);
            			socket_close($sock);
        		}    
    		}
	}
	
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);    
	}    
	
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}
}
?>

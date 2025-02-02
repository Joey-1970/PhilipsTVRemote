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
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		
		$this->RegisterVariableBoolean("State", "Status", "~Switch", 10);
		$this->EnableAction("State");

		$this->RegisterVariableString("Menulanguage", "Menü-Sprache", "~Textbox", 10);
		
		
		
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
			$this->ConnectionTest();
			
			
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
				
				default:
				    throw new Exception("Invalid Ident");
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
				return;
			}
			else {
				$this->SendDebug("GetState", $Result, 0);
                    		$Data = json_decode($Result);
				$this->SetValueWhenChanged("xclk", $Data->{'xclk'});
				$this->SetValueWhenChanged("framesize", $Data->{'framesize'});
                    		$this->SetValueWhenChanged("quality", $Data->{'quality'});
				$this->SetValueWhenChanged("brightness", $Data->{'brightness'});
				$this->SetValueWhenChanged("contrast", $Data->{'contrast'});
				$this->SetValueWhenChanged("saturation", $Data->{'saturation'});
				$this->SetValueWhenChanged("special_effect", $Data->{'special_effect'});
				$this->SetValueWhenChanged("awb", $Data->{'awb'});
				$this->SetValueWhenChanged("awb_gain", $Data->{'awb_gain'});
				$this->SetValueWhenChanged("wb_mode", $Data->{'wb_mode'});
				$this->SetValueWhenChanged("aec", $Data->{'aec'});
				$this->SetValueWhenChanged("aec2", $Data->{'aec2'});
				$this->SetValueWhenChanged("aec_value", $Data->{'aec_value'});
				$this->SetValueWhenChanged("ae_level", $Data->{'ae_level'});
				$this->SetValueWhenChanged("agc", $Data->{'agc'});
				$this->SetValueWhenChanged("agc_gain", $Data->{'agc_gain'});
				$this->SetValueWhenChanged("gainceiling", $Data->{'gainceiling'});
				$this->SetValueWhenChanged("bpc", $Data->{'bpc'});
				$this->SetValueWhenChanged("wpc", $Data->{'wpc'});
				$this->SetValueWhenChanged("raw_gma", $Data->{'raw_gma'});
				$this->SetValueWhenChanged("lenc", $Data->{'lenc'});
				$this->SetValueWhenChanged("hmirror", $Data->{'hmirror'});
				If (isset($Data->{'vflip'})) { // Die Variable wird nicht immer mitgeliefert
					$this->SetValueWhenChanged("vflip", $Data->{'vflip'});
				}
				$this->SetValueWhenChanged("dcw", $Data->{'dcw'});
				$this->SetValueWhenChanged("colorbar", $Data->{'colorbar'});
				$this->SetValueWhenChanged("led_intensity", $Data->{'led_intensity'});

				$this->SetValueWhenChanged("LastUpdate", time() );
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
				this->SetValueWhenChanged("Menulanguage", $Data->{'menulanguage'});
				$this->SetValueWhenChanged("Name", $Data->{'name'});
                    		$this->SetValueWhenChanged("Country", $Data->{'country'});
			}
		}
	}
	
	public function SetState(String $Variable, int $Value)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$IP = $this->ReadPropertyString("IPAddress");
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://'.$IP.'/control?var='.$Variable.'&val='.$Value);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$Result = curl_exec($ch);
			curl_close($ch);
			
			If ($Result === false) {
				$this->SendDebug("SetState", "Fehler beim Status-Update", 0);
			}
			$this->GetState();
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

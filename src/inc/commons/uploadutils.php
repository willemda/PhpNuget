<?php
require_once(dirname(__FILE__)."/utils.php");
require_once(dirname(__FILE__)."/url.php");

class UploadUtils
{
    var $destinationDir;
	var $allowAll;
    
    public function __construct($destinationDir,$allowedExts,$maxSize,$allowAll=false) 
    {
        $this->initialize($destinationDir,$allowedExts,$maxSize,$allowAll=false);
    }
    
    public function UploadUtils($destinationDir,$allowedExts,$maxSize,$allowAll=false)
    {
        $this->initialize($destinationDir,$allowedExts,$maxSize,$allowAll);
    }
	
    function initialize($destinationDir,$allowedExts,$maxSize,$allowAll=false)
    {
        $this->destinationDir = $destinationDir;
		$this->allowedExts = $allowedExts;
		$this->maxSize = $maxSize;
		$this->allowAll = $allowAll;
    }
	
	public static function IsUploadRequest()
	{
		if("post"!=UrlUtils::RequestMethod()) return false;
		return sizeof($_FILES)>0;
	}
    
    function Upload($fileId = "file") { 
		$files = $_FILES;
		$isRealFile = true;
		if(!array_key_exists($fileId,$files)){
			$files = HttpUtils::RawRequest();
			$isRealFile = false;
		}
	
        $guid = Utils::NewGuid();
        $toret = array(); 
        $toret["hasError"] = false; 
		$toret["errorCode"] = null; 
        $toret["errorMessage"] = ""; 
		$toret["name"]=$files[$fileId]["name"]; 
        if(array_key_exists("mime",$files[$fileId]))$toret["mime"] = $files[$fileId]["type"]; 
        if(array_key_exists("size",$files[$fileId]))$toret["sizeBytes"] = $files[$fileId]["size"]; 
        $exploded  = explode(".", $toret["name"]);
        $extension = end($exploded);
        
        if ( $toret["sizeBytes"] >= $this->maxSize){
            $toret["hasError"] = true;
            $toret["errorMessage"] = "Max size is '".$this->maxSize."' bytes. File size is '".$toret["sizeBytes"]."'.";
        }else if ( $this->allowAll<=0 && !in_array($extension, $this->allowedExts)){
            $toret["hasError"] = true;
            $toret["errorMessage"] = "Extension '".$extension."' not allowed. ".
                "The allowed ones are '".implode(", ",$this->allowedExts)."'";
        }else {
          if (array_key_exists("error",$files[$fileId]) && $files[$fileId]["error"] > 0){
			//TODO Error translations http://php.net/manual/en/features.file-upload.errors.php
            $toret["hasError"] = true;
            $toret["errorCode"]= $files[$fileId]["error"];
          }else{
            $toret["tmpName"] = $files[$fileId]["tmp_name"];
            
            if (file_exists($this->destinationDir."/" . $guid)){
                unlink ($this->destinationDir."/" . $guid);
            }
            $toret["destination"]=$this->destinationDir."/" . $guid;
			
			if($isRealFile){
				move_uploaded_file($toret["tmpName"],$toret["destination"]);
			}else{
				rename($toret["tmpName"],$toret["destination"]);
			}
          }
        }
		
		if($toret["hasError"]){
			unlink($toret["tmpName"]);
		}
        return $toret;
    }
}
/*

UPLOAD_ERR_OK

    Value: 0; There is no error, the file uploaded with success.
UPLOAD_ERR_INI_SIZE

    Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
UPLOAD_ERR_FORM_SIZE

    Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
UPLOAD_ERR_PARTIAL

    Value: 3; The uploaded file was only partially uploaded.
UPLOAD_ERR_NO_FILE

    Value: 4; No file was uploaded.
UPLOAD_ERR_NO_TMP_DIR

    Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.
UPLOAD_ERR_CANT_WRITE

    Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.
UPLOAD_ERR_EXTENSION

    Value: 8; A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.

	*/
?>
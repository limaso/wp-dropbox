<?php
/*************************************************************
*  Dropbox Connection V. 0.4
*  www.individual-it.net/Software.html
**************************************************************/
/*
    This file is part of Dropbox Connection.

    Dropbox Connection is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Dropbox Connection is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Dropbox Connection; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
    
    This Software ist bases on Dropbox Uploader version 1.1.3 written by Jaka Jancar
    [jaka@kubje.org] [http://jaka.kubje.org/]
     
*/

class dropboxConnection {
	
    protected $email;
    protected $password;
    protected $caCertSourceType = self::CACERT_SOURCE_SYSTEM;
    const CACERT_SOURCE_SYSTEM = 0;
    const CACERT_SOURCE_FILE = 1;
    const CACERT_SOURCE_DIR = 2;
    protected $caCertSource;
    protected $loggedIn = false;
    protected $cookies = array();
    protected $_dropBoxURL = "https://www.dropbox.com";
    protected $_dropBoxDlURL="https://dl-web.dropbox.com";
    
    /**
     * Constructor
     *
     * @param string $email
     * @param string|null $password
     */
    public function dropboxConnection($email, $password) {
        // Check requirements
        if (!extension_loaded('curl'))
            throw new Exception('DropboxUploader requires the cURL extension.');
        
        $this->email = $email;
        $this->password = $password;
    }
    
    
    public function setCaCertificateFile($file)
    {
        $this->caCertSourceType = self::CACERT_SOURCE_FILE;
        $this->caCertSource = $file;
    }
    
    public function setCaCertificateDir($dir)
    {
        $this->caCertSourceType = self::CACERT_SOURCE_DIR;
        $this->caCertSource = $dir;
    }
    
    public function upload($filename, $remoteDir='/') {
        if (!file_exists($filename) or !is_file($filename) or !is_readable($filename))
            throw new Exception("File '$filename' does not exist or is not readable.");
        
        if (!is_string($remoteDir))
            throw new Exception("Remote directory must be a string, is ".gettype($remoteDir)." instead.");

        if (preg_match("/.+\.\..+/",$remoteDir))
            throw new Exception("Remote directory is impossible");
        

        
        if (!$this->loggedIn)
            $this->login();
        
        $data = $this->request($this->_dropBoxURL.'/home');
        $token = $this->extractToken($data, $this->_dropBoxDlURL.'/upload');
        
        $data = $this->request($this->_dropBoxDlURL.'/upload', true, array('plain'=>'yes', 'file'=>'@'.$filename, 'dest'=>$remoteDir, 't'=>$token));
        if (strpos($data, 'HTTP/1.1 302 FOUND') === false)
            throw new Exception('Upload failed!');
    }
   
   //return all sub-directories in the $remoteDir
    public function getdirs($remoteDir='/') {


		  $directory_names=array();

		 if (preg_match("/\.\./",$remoteDir))
            throw new Exception("Remote directory is impossible");


		 if (preg_match("/.+\.\..+/",$remoteDir))
            throw new Exception("Remote directory is impossible");


        if (!is_string($remoteDir))
            throw new Exception("Remote directory must be a string, is ".gettype($remoteDir)." instead.");
        
        if (!$this->loggedIn)
            $this->login();
        
        $data = $this->request($this->_dropBoxURL.'/browse_plain/'.$remoteDir.'?no_js=true');

        preg_match_all ( '/<div.*details-filename.*>(.*?)<\/div>/', $data, $file_array );
         
		  foreach ( $file_array[0] as  $file_name )
  			{
  			 $file_name = explode('</a>', $file_name);
  			 $file_name = spliti('<a href="\/.*true">', $file_name[0]);
  		  	
  		  	 if ($file_name[1]!='')
  			 array_push($directory_names, $file_name[1]);
 
			}  
				
			return $directory_names;
    }

   //return all files in the $remoteDir
    public function getfiles($remoteDir='/') {

		 if (preg_match("/.+\.\..+/",$remoteDir))
            throw new Exception("Remote directory is impossible");

		  $file_names=array();
        if (!is_string($remoteDir))
            throw new Exception("Remote directory must be a string, is ".gettype($remoteDir)." instead.");
        
        if (!$this->loggedIn)
            $this->login();
        
        $data = $this->request($this->_dropBoxURL.'/browse_plain/'.$remoteDir.'?no_js=true');
        

        
        preg_match_all ( '/<div.*details-filename.*>(.*?)<\/div>/', $data, $file_array );
         
		  foreach ( $file_array[0] as  $file_name )
  			{
  			 $href = explode('</a>', $file_name);
  			 $file_name = spliti('<a href=".*dl.*">', $href[0]);

  		
  			  if ($file_name[1]!='')
  			  {
	  			  $href = spliti('<a href=".*w=', $href[0]);
	  			  $href = explode('">', $href[1]);
	  			  array_push($file_names, array($file_name[1],$href[0]));
  			  }
 
			}  
				
			return $file_names;
    }
       
    //read the file-data and return it   
    public function getfile($remoteFile='/',$w) {

		  $file_names=array();

		 if (preg_match("/.+\.\..+/",$remoteFile))
            throw new Exception("Remote directory is impossible");  
        if (!is_string($remoteFile))
            throw new Exception("Remote directory must be a string, is ".gettype($remoteDir)." instead.");
        if (!preg_match('(^[a-z0-9]+$)',$w))
            throw new Exception("impossible w-string");

                
        
        if (!$this->loggedIn)
            $this->login();
        
        $data = $this->request($this->_dropBoxDlURL.'/get/'.$remoteFile.'?w='.$w);
        preg_match ( '/Content-Type: .+\/.+/', $data, $content_type );        

		          
        
        $data=substr(stristr($data, "\r\n\r\n"),4);
        return array("data"=>$data,"content_type"=>$content_type[0]);
       
    }
            
   
    
    protected function login() {
        $data = $this->request($this->_dropBoxURL.'/login');
        $token = $this->extractToken($data, '/login');
        
        $data = $this->request($this->_dropBoxURL.'/login', true, array('login_email'=>$this->email, 'login_password'=>$this->password, 't'=>$token));
        
        if (stripos($data, 'location: /home') === false)
            throw new Exception('Login unsuccessful.');
        
        $this->loggedIn = true;
    }

    protected function request($url, $post=false, $postData=array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        switch ($this->caCertSourceType) {
            case self::CACERT_SOURCE_FILE:
                curl_setopt($ch, CURLOPT_CAINFO, $this->caCertSource);
                break;
            case self::CACERT_SOURCE_DIR:
                curl_setopt($ch, CURLOPT_CAPATH, $this->caCertSource);
                break;
        }
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, $post);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        
        // Send cookies
        $rawCookies = array();
        foreach ($this->cookies as $k=>$v)
            $rawCookies[] = "$k=$v";
        $rawCookies = implode(';', $rawCookies);
        curl_setopt($ch, CURLOPT_COOKIE, $rawCookies);
        
        $data = curl_exec($ch);
        
        if ($data === false)
            throw new Exception('Cannot execute request: '.curl_error($ch));
        
        // Store received cookies
        preg_match_all('/Set-Cookie: ([^=]+)=(.*?);/i', $data, $matches, PREG_SET_ORDER);
        foreach ($matches as $match)
            $this->cookies[$match[1]] = $match[2];
        
        curl_close($ch);
        
        return $data;
    }

    protected function extractToken($html, $formAction) {
        if (!preg_match('/<form [^>]*'.preg_quote($formAction, '/').'[^>]*>.*?(<input [^>]*name="t" [^>]*value="(.*?)"[^>]*>).*?<\/form>/is', $html, $matches) || !isset($matches[2]))
            throw new Exception("Cannot extract token! (form action=$formAction)");
        return $matches[2];
    }

}



//-------------------------------------------------------------


//arth2o.com
class dropbox extends DropboxConnection{
	
	//db connection
	function dropbox(){
		self::dropboxConnection(DROPBOX_EMAIL, DROPBOX_PASSWORD);
	}
	
	//getDropBox Directories
	public final function dirList($dbdir, $sub_dir=""){
		$sub_dir = $this->_subdir($sub_dir);
		$directories = $this->getdirs($dbdir."/".$sub_dir);
		return $directories;
	}
	
	//getDropBox Directories -R
	/*
	 * @return array()
	 * array (
  				'folder' => 
  							array (
    								'sub-folder' => false, //or array
    					    )
      )
	 * */
	public final function allDirList($dbdir, $sub_dir=""){
		
		$sub_dir = $this->_subdir($sub_dir);
		$directories = $this->getdirs($dbdir."/".$sub_dir);
		foreach($directories as $key=>$directory){
			
			$ret = false;
			$ret = $this->allDirList($dbdir."/".$directory, "");
			if(sizeof($ret)<1){
				$ret = false;				
			}
			$directories[$directory]=$ret;
			unset($directories[$key]);
		}
		return $directories;
	}
	
	
	//getDropBox Directories and Files array
	public final function allDirFiles($dbdir, $sub_dir=""){
		
		$sub_dir = $this->_subdir($sub_dir);
		$directories = $this->getdirs($dbdir."/".$sub_dir);
		foreach($directories as $key=>$directory){
			
			$ret = false;
			$ret = $this->allDirList($dbdir."/".$directory, "");
			if(sizeof($ret)<1){
				$ret = false;
				$ret = 	$this->fileList($dbdir, $directory);
				if(sizeof($ret)<1){
					$ret = false;
				}
			}
			$directories[$directory]=$ret;
			unset($directories[$key]);
		}
		return $directories;
	}
	
	
	public final function fileList($dbdir, $sub_dir=""){
		return $this->getfiles($dbdir."/".$sub_dir); 
	}
	
	
	//subdir function
	private final function _subdir($sub_dir){
		while (preg_match('/\/$/', $sub_dir)){
   			$sub_dir=substr($sub_dir,0,mb_strlen($sub_dir)-1);
   		}
   		return $sub_dir;
	}
}




/*
 * 
require ('dropboxConnection.php');

#config
define("DROPBOX_EMAIL", 	"");
define("DROPBOX_PASSWORD", 	"");
define("DROP_BOX_DIR", 		"/");

try {
	$dropbox = new dropbox();
	
	//prarent folder
	$directories = $dropbox->dirList($dbdir, $sub_dir);print_r($directories);
	
	//folder files
	//$files=$dropbox->dirList($dbdir."/".$directories[0]);print_r($files);
	
	//req folders
	//$dirs = $dropbox->allDirList($dbdir, $sub_dir);//print_r($dirs);
	//file_put_contents("./t.php", var_export($dirs, 1));
	
	//ulpload file
	//$dropbox->upload("./teszt_dropbox.txt", "/folder//");
	
 
} catch(Exception $e) {
        echo '<span style="color: red">Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
}
exit;

 * 
 * */

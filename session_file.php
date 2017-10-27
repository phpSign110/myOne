<?php if ( ! defined('AB_SDK_WORK_DIR')) exit('No direct script access allowed');
//文件内存操作类，适用于AP端
class SessionFile {
	public $_f; //文件句柄
	private $_file_path="/tmp/sess"; // ac /tmp/portal_sess/
	public $session_ttl = 900; //seconds

	public function __construct($file_path = '/tmp/sess'){
		$this->_file_path = $file_path;
		if(!is_dir($this->_file_path)){
			mkdir($this->_file_path,777,TRUE);
		}
		//删除旧文件
		$this->delete_files($this->_file_path);
	}

	public function setex($key, $ttl, $data){
		$_file = $this->_file_path. DIRECTORY_SEPARATOR . $key;
		$contents = array(
				'time'		=> time(),
				'ttl'		=> $ttl,			
				'data'		=> $data
			);
		
		if ($this->write_file($_file, serialize($contents)))
		{
			return TRUE;			
		}
		return FALSE;
	}

	public function expire($key, $ttl){
		$_file = $this->_file_path. DIRECTORY_SEPARATOR . $key;
		if ( ! file_exists($_file))
		{
			return FALSE;
		}
		
		$data = $this->read_file($_file);
		$data = unserialize($data);
		
		$data['time'] = time();
		$data['ttl'] = $ttl;
		
		if ($this->write_file($_file, serialize($data)))
		{
			return TRUE;			
		}
		return FALSE;
	}

	public function get($key){
		$_file = $this->_file_path. DIRECTORY_SEPARATOR . $key;

		if ( ! file_exists($_file))
		{
			return FALSE;
		}
		
		$data = $this->read_file($_file);
		$data = unserialize($data);
		
		if (time() >  $data['time'] + $data['ttl'])
		{
			unlink($_file);
			return FALSE;
		}
		
		return $data['data'];
	}

	public function ttl($key) {
		$_file = $this->_file_path. DIRECTORY_SEPARATOR . $key;

		if ( ! file_exists($_file))
		{
			return FALSE;
		}

		$data = $this->read_file($_file);
		$data = unserialize($data);

		$ttl = time() - $data['time'];
		if ($ttl < 0 || $ttl >= $data['ttl']) {
			return FALSE;
		} else {
			return $data['ttl'] - $ttl;
		}
	}

	private function write_file($path, $data, $mode = 'wb')
	{
		if ( ! $fp = @fopen($path, $mode))
		{
			return FALSE;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);

		return TRUE;
	}

	private function read_file($file)
	{
		if ( ! file_exists($file))
		{
			return FALSE;
		}

		if (function_exists('file_get_contents'))
		{
			return file_get_contents($file);
		}

		if ( ! $fp = @fopen($file, FOPEN_READ))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);

		$data = '';
		if (filesize($file) > 0)
		{
			$data =& fread($fp, filesize($file));
		}

		flock($fp, LOCK_UN);
		fclose($fp);

		return $data;
	}

	private function delete_files($path, $del_dir = FALSE, $level = 0)
	{
		// Trim the trailing slash
		$path = rtrim($path, DIRECTORY_SEPARATOR);

		if ( ! $current_dir = @opendir($path))
		{
			return FALSE;
		}

		while (FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename != "." and $filename != "..")
			{
				if (is_dir($path.DIRECTORY_SEPARATOR.$filename))
				{
					// Ignore empty folders
					if (substr($filename, 0, 1) != '.')
					{
						$this->delete_files($path.DIRECTORY_SEPARATOR.$filename, $del_dir, $level + 1);
					}
				}
				else
				{
					$_file = $path.DIRECTORY_SEPARATOR.$filename;
					$_mtime = filemtime($_file);
					$_ts = time();
					if(($_mtime + $this->session_ttl) < $_ts){
						unlink($_file);
					}
				}
			}
		}
		@closedir($current_dir);

		return TRUE;
	}
}
<?php defined('AB_SDK_WORK_DIR') OR exit('No direct script access allowed');

class PortalSession
{
	public $session_ttl = 3600; //seconds
	
	private $_SESSION; // session存储数据
	private $_is_ac = FALSE;
	private $_sess_argv;
	private $_session_id = '';
	private $_session_id_key = '';
	private $_sess_obj ; //空对象
	private $_sess_data = array();
	private $_session_prefix = 'x_sid_';
	private $_session_mixed_key = 'fuu44qvr1bqpq2l0gaaiapci7t4bw2dt';

	public function __construct(){
		$this->_SESSION = array();

		if(function_exists('portal_client_chap_auth_mac')){
			$this->_is_ac = TRUE;
		}

		if($this->_is_ac){
			$_redis = new Redis();
			$_ports = array('16380','6379');
			$_conn_failed = TRUE;
			// 对portal使用的redis设备进行初始化
			$host = '127.0.0.1';
			if(file_exists('/build/irest_service_ver')){ //acs
				$host = 'session.acs.abloomy.com.cn';
			}
			foreach($_ports as $port){
				$ret = false;
				try
				{
					$ret = $_redis->connect($host, $port, 30);
				}catch (RedisException $e)
				{
					continue; 
				} 
				if($ret){ // connect success
					$_conn_failed = FALSE;
					break;
				}
			}
			if($_conn_failed){ //redis all failed, use file
				$this->_sess_obj = new SessionFile('/tmp/portal_sess');
			}else{
				$this->_sess_obj = $_redis;
			}
		}else{

			if(is_dir('/tmp/session')){
				$this->_sess_obj = new SessionFile();
			}else{
				$this->_sess_obj = new SessionFile(AB_SDK_WORK_DIR.'/sess');
			}
		}
	}

	// 加载SESSION数据，支持参数全部为空的情况
	public function start($portalReqData=array(), $sess_id=''){
		if(empty($portalReqData)){
			$argv = $_REQUEST;
		}else{
			$argv = (array)($portalReqData);
		}
		
		$sessIdData = array(
			// 'wlanacip' => isset($argv['wlanacip'])?$argv['wlanacip']:'',
			// 'ssid' => isset($argv['ssid'])?$argv['ssid']:'',
			// 'wlanuserip' => isset($argv['wlanuserip'])?$argv['wlanuserip']:'',
			'vslanusermac' => isset($argv['vslanusermac'])?$argv['vslanusermac']:'',
			// 'vslanuserip' => isset($argv['vslanuserip'])?$argv['vslanuserip']:'',
			// 'apmac' => isset($argv['apmac'])?$argv['apmac']:'',
			// 'vslanacmac' => isset($argv['vslanacmac'])?$argv['vslanacmac']:''
		);

		$this->_sess_argv = $sessIdData;
		// 根据用户参数，计算SESSION ID
		$sess_id_flag = false;
		if(empty($sess_id)){
			$this->_session_id = md5(json_encode($sessIdData).$this->_session_mixed_key);
		}else{ //初始化固定session_id数据
			$this->_session_id = $sess_id;
			$sess_id_flag = true;
		}
		//
		$this->_session_id_key = $this->_session_prefix.$this->_session_id;
		$cacheData = $this->_sess_obj->get($this->_session_id_key);
		// init data
		if(empty($cacheData)){
			$this->_sess_data = array();
			$this->_sess_data['ctime'] = time(); //创建时间
			if($sess_id_flag){ // no data
				return FALSE;
			}
		}else{
			$this->_sess_data = (array)json_decode($cacheData,TRUE);
			$this->_sess_obj->expire($this->_session_id_key, $this->session_ttl);
		}

		return TRUE;
	}

	public function add($data = array()){
		if(empty($data) || !is_array($data)) {
			return FALSE;
		}
		foreach($data as $k => $v){
			$this->_sess_data[$k] = $v;
		}
		
		$this->_sess_data['mtime'] = time(); //修改时间

		$this->_sess_obj->setex($this->_session_id_key, 
			$this->session_ttl,
			json_encode($this->_sess_data)); 
	}

	public function del($data = array()){
		if(empty($data) || !is_array($data)) {
			return FALSE;
		}
		foreach($data as $k){
			if(isset($this->_sess_data[$k])){
				unset($this->_sess_data[$k]);
			}
		}
		
		$this->_sess_data['mtime'] = time(); //修改时间

		$this->_sess_obj->setex($this->_session_id_key, 
			$this->session_ttl,
			json_encode($this->_sess_data));
	}

	public function get($k=''){
		if(empty($k)){
			return $this->_sess_data;
		}

		return isset($this->_sess_data[$k])?
				$this->_sess_data[$k]:array();
	}
	
	public function session_id(){
		return $this->_session_id;
	}

	// 获取session_id已过的时间
	public function used_session_ttl() {
		$key = $this->_session_prefix.$this->_session_id;
		$ttl = $this->_sess_obj->ttl($key);
		$used_ttl = $this->session_ttl - $ttl;
		return $used_ttl;
	}
}
?>
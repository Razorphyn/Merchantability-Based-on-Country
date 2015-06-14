<?php
	class Razorphyn_Country_Helper_Data extends Mage_Core_Helper_Abstract{
		
		public function isAdmin(){//seems ok
			if(array_key_exists('adminhtml', $_COOKIE)){
				
				$locaXml = Mage::getBaseDir('etc').DS.'local.xml';
				$xml = new DOMDocument();
				$xml->load($locaXml);
				$xpath = new DOMXPath($xml);
				$entry = $xpath->query("//session_save");
				
				foreach($entry as $ent){
				  $saveMethod = trim($ent->nodeValue);
				}

				$saveMethod = (!empty($saveMethod)) ? trim($saveMethod):'files';				

				if(Mage::getConfig()->getModuleConfig('Cm_RedisSession')->is('active', 'true') && $saveMethod=='db'){
					$entry = $xpath->query("//redis_session");
					if($entry->length>0)
						$saveMethod='redis';
				}
				
				switch ($saveMethod) {
					case 'db':
						$read = Mage::getModel('core/resource')->getConnection('core_read');
						$query = $read->select()->from(Mage::getSingleton('core/resource')->getTableName('core/session'))
												->where('session_id=?',$_COOKIE['adminhtml'])
												->limit(1);
						$sessionDb = $read->fetchAll($query);
						if(count($sessionDb)==0)
							return false;

						$session_data = $sessionDb[0]['session_data'];
						break;

					case 'files':
						
						$session_path=Mage::getBaseDir('session').DS.'sess_'.$_COOKIE['adminhtml'];
						if(!is_file($session_path))
							return false;

						$session_data = file_get_contents($session_path);
						break;
						
					case 'memcached':	
					case 'memcache':
						if(!isset($session_path)){
							$entry = $xpath->query("//session_save_path");
							foreach($entry as $ent){
							  $session_path = $ent->nodeValue;
							}
						}
						$timeout=null;
						if(strpos($session_path,'?')){
							$session_path=(explode('?',$session_path,2));
							$host_port=$session_path[0];
							
							preg_match('@timeout=([0-9]+)@',$session_path[1],$match);
							$timeout= (isset($match[1]))? $match[1]:null;
						}

						$host_port=explode(':',$host_port);
						$index=count($host_port)-1;
						$port= $host_port[$index];
						unset($host_port[$index]);
						$host=implode(':',$host_port);
						
						if($saveMethod=='memcache'){
							$m = new Memcache();
							$m = memcache_connect($host, $port,$timeout);
							if(!$m){
								echo "Can't connect to Memcache server";
								return false;
							}
						}
						else if($saveMethod=='memcached'){
							$m = new Memcached();
							$m->addServer($host, $port);
						}
						
						$session_data= $m->get($_COOKIE['adminhtml']);

						break;

					case 'redis'://Not tested - probably doesn't work
						$session_data = Mage::getModel('redissession/session')->read($_COOKIE['adminhtml']);
						if(!is_string($session_data))
							$session_data=serialize($session_data);
						break;
				}

				if(isset($session_data) && strpos($session_data,'Mage_Admin_Model_User'))
					return true;
				
				return false;
			}
			return false;
		}
	}
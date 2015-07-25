<?php

function check_alnum($str, $permit = FALSE)
{
    if($permit)
    {
        $str = str_replace($permit, '', $str);
    }
    return ctype_alnum($str);
}

class Sso
{
    function sso_auth()
    {
        $auth_check = TRUE;
        $non_check_uri = array('remote', 'auth');              
        
        if(isset($_SERVER['PATH_INFO']))
        {
            $path_info = $_SERVER['PATH_INFO'];
            
            foreach($non_check_uri as $uri)
            {
                if(strpos($path_info, $uri) === 1)
                {
                    $auth_check = FALSE;
                    break;
                }
            }
        }
        
        if( ! extension_loaded('haes'))
        {
            if (phpversion() >= '5.0') {
                @dl('haes.so');
            } else {
                @dl(HOME_PATH.'../approval/lib/haes.so');
            }        
        }        
        
        $this->_load_global_config();
        
        if($auth_check)
        {
            $this->_check_auth();
            $this->_load_timezone_config($GLOBALS['_HANBIRO_GW']['ID']);            
        }        
    }
    
    function _check_auth()
    {        
        if ((isset($_COOKIE['HANBIRO_GW']) && !empty($_COOKIE['HANBIRO_GW'])) || (isset($_COOKIE['CLIENT_GW']) && !empty($_COOKIE['CLIENT_GW']))) 
        {
            if (isset($_COOKIE['CLIENT_GW']) && !empty($_COOKIE['CLIENT_GW'])) 
            {
                $_CRYPT_USER_ = haes_decrypt($_COOKIE['CLIENT_GW']);
                setcookie("HANBIRO_GW", '', 0, '/');
            } else 
            {
                $_CRYPT_USER_ = haes_decrypt($_COOKIE['HANBIRO_GW']);
            }
            list($GLOBALS['_HANBIRO_GW']['ID'],$GLOBALS['_HANBIRO_GW']['DOMAIN'],$GLOBALS['_HANBIRO_GW']['TIME'], $GLOBALS['_HANBIRO_GW']['USB'], $GLOBALS['_HANBIRO_GW']['PWD_CHECK']) = explode(',', $_CRYPT_USER_);
            if ( ! check_alnum($GLOBALS['_HANBIRO_GW']['ID'], array('_','.','-')) 
                OR ! check_alnum($GLOBALS['_HANBIRO_GW']['DOMAIN'], '.')) 
            {
                $auth_valid = FALSE;
            } else 
            {
                $auth_valid = TRUE;
                $GLOBALS['_HANBIRO_GW']['EMAIL'] = sprintf("%s@%s", $GLOBALS['_HANBIRO_GW']['ID'], $GLOBALS['_HANBIRO_GW']['DOMAIN']);
            }
        } else 
        {
            $auth_valid = FALSE;
        }
        
        if( ! $auth_valid)
        {
            $output = json_encode(array('error'=>'Need HANBIRO_GW Value'));
            exit($output);
        }                
    }    
    
    function _load_global_config()
    {
        if(file_exists(sprintf('%sglobal_%s.ini', CONF_PATH, HTTP_HOST))) 
        {
            $conf = config::parse(CONF_PATH.'global_'.HTTP_HOST.'.ini');
        }else
        {
            $conf = config::parse(CONF_PATH.'global.ini');
        }
        
        if(!isset($conf['eapproval']['sewon']))$conf['eapproval']['sewon'] = false;
        if(!isset($conf['eapproval']['useGroupBox']))$conf['eapproval']['useGroupBox'] = true;
        if(!isset($conf['eapproval']['useGroupLine']))$conf['eapproval']['useGroupLine'] = false;
        if(!isset($conf['eapproval']['endDocNo']))$conf['eapproval']['endDocNo'] = false;        
        
        $GLOBALS['GLOBAL_CONF'] = $conf;        
        $cn = 0;
        
        if(isset($conf['global']['server']))
        {
            $global_server = array();        
            $temp = explode(',', $conf['global']['server']);
            foreach($temp as $dom) {
                $dom = trim($dom);
                $entry = $conf[$dom];
                if(!$entry) continue;
                if($entry['hostname'] == HTTP_HOST) $cn = $entry['id'];
                $global_server[$dom] = $entry;
            }
            $GLOBALS['GLOBAL_SERVER'] = $global_server;            
        }
        
        define('CN', $cn);
    }
    
    function _load_timezone_config($id)
    {                
        $conf = config::parse(CONF_PATH."timezone_{$id}.ini");
        if(!$conf['default']['time_zone'] && $GLOBALS['GLOBAL_CONF']['timezone']) {
            $conf = array('default' => $GLOBALS['GLOBAL_CONF']['timezone']);
        }
        if(!$conf['default']['time_zone']) {
            $conf = array('default' => array(
                'time_zone' 	=> '+0900',
                'date_format' 	=> 'Y/m/d H:i:s',
                'country_zone' 	=> 'Asia/Seoul'
            ));
        }
        $GLOBALS['TIMEZONE_CONF'] = $conf;
    }    
}

class config {
	function split(&$sData, $xDeli = '=', $nCut = 0, $aComment = array(';', '#'), $aQuote = array('\'', '"', '`')) {
		$aDeli = is_array($xDeli) ? $xDeli : array($xDeli);
		$aResult = array();
		
		$sOpen = '';
        $sBuf = '';
		$nLen = strlen($sData);
		for($i=0;$i<$nLen;$i++) {
			
			if($sData[$i] == '\\') {
				if($i + 1 < $nLen) {
					$sSlash = $sData[$i+1];
					if($sSlash == "\r") {
						if($i + 2 < $nLen) {
							if($sData[$i+2] == "\n") {
								$sBuf .= "\n";
								$i += 2;
								continue;
							}
						}
					}
					if($sSlash == "\n") {
						$sBuf .= "\n";
						$i++;
						continue;
					} else if($sSlash == 'u') {
						$nSlash = 5;
					} else if($sSlash == 'U') {
						$nSlash = 9;
					} else if($sSlash == 'x') {
						$nSlash = 3;
					} else if($sSlash >= '0' || $sSlash <= '9') {
						$nSlash = 1;
						if($i + 2 < $nLen) {
							if($sData[$i + 2] >= '0' && $sData[$i + 2] <= '9') {
								$nSlash = 2;  
								if($i + 3 < $nLen) {
									if($sData[$i + 3] >= '0' && $sData[$i + 3] <= '9')$nSlash = 3;  
								}
							}
						} 
					} else {
						$nSlash = 1;
					}
					
					if($i + $nSlash >= $nLen) {
						// 처리할수 없는 난감한 상황 발생..
						$i = $nLen;
						continue;
					}
					$sSlash = substr($sData, $i, $nSlash + 1);
					$i += $nSlash;

					eval("\$sBuf .= \"$sSlash\";");
				}
				continue;
			}
			
			if($sOpen) {
				if($sOpen == $sData[$i]) {
					$sOpen = '';
				}
				$sBuf .= $sData[$i];
				continue;
			}
			if(in_array($sData[$i], $aQuote)) {
				$sOpen = $sData[$i];
				$sBuf .= $sData[$i];
				continue;
			}
			
			if($nCut == 0 || $nCut > count($aResult)) {
				if(in_array($sData[$i], $aDeli)) {
					$sBuf = trim($sBuf);
					if($sBuf != '') {
						$aResult[] = $sBuf;
						$sBuf = '';
					}
					continue;
				}
			}
			
			if(in_array($sData[$i], $aComment)) {
				// 엔터 까지 진행하고 .. 짜르기
				while(++$i < $nLen) {
					if($sData[$i] == "\r") {
						if($sData[$i+1] == "\n") {
							$i+=2;
							break;
						}
					}
					if($sData[$i] == "\n") {
						$i++;
						break;
					}
				}
				break;
			}
			
			if($sData[$i] == "\r") {
				if($sData[$i+1] == "\n") {
					$i+=2;
					break;
				}
			}
			if($sData[$i] == "\n") {
				$i++;
				break;
			}
			$sBuf .= $sData[$i];
		}
		$sBuf = trim($sBuf);
		if($sBuf != '') {
			$aResult[] = $sBuf;
		}
		
		foreach($aResult as $nKey => $sValue) {
			$sValue = trim($sValue);
			if(in_array($sValue[0], $aQuote)) {
				$sValue = substr($sValue, 1, -1);
			}
			$aResult[$nKey] = $sValue;
		}
		
		$sData = substr($sData, $i);
		return $aResult;
	}
	
	function parse($sFile, $sSection = 'default', $nCut = 1) {
		if(!is_file($sFile))return false;
		$sConfig = file_get_contents($sFile);
		while($sConfig) {
			$aResult = config::split($sConfig, '=', $nCut);
			if(count($aResult) == 0)continue;
			if(ereg('^\[(.*)\]$', $aResult[0], $aMatch)) {
				$sSection = $aMatch[1];
				continue;
			}
			
            if(isset($aConf[$sSection][$aResult[0]]))
            {
                if(is_array($aConf[$sSection][$aResult[0]]))
                {
                    $aConf[$sSection][$aResult[0]][] = $aResult[1];
                }else
                {
                    $aConf[$sSection][$aResult[0]] = array($aConf[$sSection][$aResult[0]], $aResult[1]);                
                }
            }else
            {
                if(isset($aResult[1]))
                {
                    $aConf[$sSection][$aResult[0]] = $aResult[1];                
                }
            }

		}
		return $aConf;
	}
	
	function write($aConf, $sFile, $sLine = "\n", $sDeli = ' = ', $sQuote = '"') {
		$rConf = fopen($sFile, "w");
		$bFirst = true;
		foreach($aConf as $sSection => $aSecConfig) {
			if(!$bFirst)fputs($rConf, sprintf("\n"));
			$bFirst = false;
			
			if($sSection)fputs($rConf, sprintf("[%s]\n", $sSection));
			foreach($aSecConfig as $sKey => $xValue) {
				if(is_array($xValue)) {
					foreach($xValue as $sValue) {
						$sValue = str_replace("\n", $sLine, $sValue);
						fputs($rConf, sprintf("%s%s%s%s%s\n", $sKey, $sDeli, $sQuote, $sValue, $sQuote));	
					}
				} else {
					$xValue = str_replace("\n", $sLine, $xValue);
					fputs($rConf, sprintf("%s%s%s%s%s\n", $sKey, $sDeli, $sQuote, $xValue, $sQuote));			
				}
			}
		}
		fclose($rConf);
	}
}
?>

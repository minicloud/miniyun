<?php
 /** 
* HttpClient
* @author weird <weird@sina.com>
*/
class HttpClient {
    const PROXY_NONE = 0;
    const PROXY_SOCKS4 = 1;
    const PROXY_SOCKS5 = 2;
    const PROXY_HTTP = 4;
    //请求送头
    private $_require_header=array();
    //请求Cookie信息
    private $_require_cookie=array();
    //回发头
    private $_response_header=array();
    //回发数据
    private $_response_body='';
    //回发Cookie信息
    private $_response_cookie = array();
    //回发状态码
    private $_response_status;
    //请求Uri
    private $_require_uri;
    //代理方式
    private $_proxy_type = HttpClient::PROXY_NONE;
    //代理服务器
    private $_proxy_host;
    //代理认证用户名
    private $_proxy_user;
    //代理认证密码
    private $_proxy_pass;
	//cookie持久访问
	private $_keep_cookie = true;

    private $_error;

    private $_mimes = array(
            'gif' => 'image/gif',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'jpeg' => 'image/jpeg',
            'pjpg' => 'image/pjpg',
            'jpg' => 'image/jpeg',
            'tif' => 'image/tiff',
            'htm' => 'text/html',
            'css' => 'text/css',
            'html' => 'text/html',
            'txt' => 'text/plain',
            'gz' => 'application/x-gzip',
            'tgz' => 'application/x-gzip',
            'tar' => 'application/x-tar',
            'zip' => 'application/zip',
            'hqx' => 'application/mac-binhex40',
            'doc' => 'application/msword',
            'pdf' => 'application/pdf',
            'ps' => 'application/postcript',
            'rtf' => 'application/rtf',
            'dvi' => 'application/x-dvi',
            'latex' => 'application/x-latex',
            'swf' => 'application/x-shockwave-flash',
            'tex' => 'application/x-tex',
            'mid' => 'audio/midi',
            'au' => 'audio/basic',
            'mp3' => 'audio/mpeg',
            'ram' => 'audio/x-pn-realaudio',
            'ra' => 'audio/x-realaudio',
            'rm' => 'audio/x-pn-realaudio',
            'wav' => 'audio/x-wav',
            'wma' => 'audio/x-ms-media',
            'wmv' => 'video/x-ms-media',
            'mpg' => 'video/mpeg',
            'mpga' => 'video/mpeg',
            'wrl' => 'model/vrml',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo'
    );
    public function set_header($k,$v) {
        $this->_require_header[$k] = $v;
    }
	
	public function remove_header($k) {
        unset($this->_require_header);
    }

    public function set_cookie($k,$v) {
        $this->_require_cookie[$k] =$v;
    }

    //设置多个Cookie或者Cookie字符串
    public function set_cookies($v) {
        $this->_require_cookie = array_merge($this->_require_cookie, is_array($v) ? $v : $this->cookie_str2arr($v));
    }

    public function get_body() {
        return $this->_response_body;
    }

    public function get_header() {
        return $this->_response_header;
    }

    public function get_cookie() {
        return $this->_response_cookie;
    }

    public function get_status() {
        return $this->_response_status;
    }

    public function set_proxy($h,$t=HttpClient::PROXY_HTTP,$u='',$p='') {
        $this->_proxy_host = $h;
        $this->_proxy_type = $t;
        if($u != '') {
            $this->_proxy_user = $u;
            $this->_proxy_pass = $p;
        }
    }

	public function keep_cookie($v){
		$this->_keep_cookie = $v;
	}
    public function HttpClient() {
        $this->init_require();
        $this->init_response();
    }

    //将Cookie字符串转化为数组形式
    private function cookie_str2arr($str) {
        $ret = array();
        $cookies = explode(';', $str);
        $ext = array('path','expires','domain','httponly','');
        if(count($cookies)) {
            foreach($cookies as $cookie) {
				$cookie = trim($cookie);
				$arr = explode('=', $cookie);
				$value = implode('=',array_slice($arr,1,count($arr)));;
				$ret[trim($arr[0])] = $value;
			  }
        }
        return $ret;
    }
    //初始化请求数据
    private function init_require() {
        $this->_require_header = array(
                'Accept'=>'Accept: */*',
                'Accept-Language'=>'zh-cn',
                'User-Agent'=>'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3',
                'Connection'=>'close');
        $this->_require_cookie = array();
    }

    //初始化回发数据
    private function init_response() {
		$this->remove_header('Content-Type');
        $this->_response_header = array();
        $this->_response_body = '';
        $this->_response_status = 0;
		if(!$this->_keep_cookie){
			$this->_response_cookie = array();
		}
    }

    //发送请求
    private function send($method,$data='') {
        $matches = parse_url($this->_require_uri);
        !isset($matches['host']) && $matches['host'] = '';
        !isset($matches['path']) && $matches['path'] = '';
        !isset($matches['query']) && $matches['query'] = '';
        !isset($matches['port']) && $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = $matches['port'] ? $matches['port']: 80;
        $this->_require_header['Host']= $host.($port == 80 ? '' :(':'.$port));

        if(!isset($this->_require_header['Referer']))  $this->_require_header['Referer'] = $this->_require_uri;

        $sock = socket_create(AF_INET,SOCK_STREAM, SOL_TCP);
        if(!$sock) {
            $this->_error = socket_last_error();
        }

        socket_set_option($sock,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>10, "usec"=>0 ) );
        socket_set_option($sock,SOL_SOCKET,SO_SNDTIMEO,array("sec"=>10, "usec"=>0 ) );

        if( isset($this->_proxy_type) &&  $this->_proxy_type !=  HttpClient::PROXY_NONE ) {
            list ($proxy_host,$proxy_port) = explode(':',$this->_proxy_host);
            if(!isset($proxy_port)) $proxy_port = 80;

            if(!@socket_connect($sock,$proxy_host,$proxy_port)) {
                $this->_error = "Cann't connect to {$host}:{$port}";
                return false;
            }
            $host_ip = gethostbyname($host);
            switch($this->_proxy_type) {
                case HttpClient::PROXY_SOCKS4:
                    socket_write($sock, chr(4).chr(1).pack('nN', $port,ip2long($host_ip)).'HttpClient'.chr(0));
                    $buf = socket_read($sock,2,PHP_BINARY_READ);
                    if(ord(substr($buf,-1)) != 90) {
                        $this->_error = "Request to {$host}:{$port} rejected or failed";
						socket_close($sock);
                        return false;
                    }
                    break;
                case HttpClient::PROXY_SOCKS5:
                //step1
                    $auth_method = empty($this->_proxy_user) ? 1 : 2;
                    socket_write($sock, chr(5).chr(1).chr($auth_method));
                    $buf = socket_read($sock,2,PHP_BINARY_READ);
                    if(substr($buf,-1) != 0x00) {
                        $this->_error ="Request to {$host}:{$port} rejected or failed";
						socket_close($sock);
                        return false;
                    }
                    //auth
                    if($auth_method == 2) {
						socket_write($sock, chr(1).chr(strlen($this->_proxy_user)).$this->_proxy_user.chr(strlen($this->_proxy_pass)).$this->_proxy_pass);
                        $buf = socket_read($sock,2,PHP_BINARY_READ);
                        if(substr($buf,-1) != 0x00) {
                            $this->_error = "authentication  failed";
							socket_close($sock);
                            return false;
                        }
                    }
                    //step2
					//使用代理的dns服务器
					socket_write($sock, pack("C5", 0x05, 0x01, 0x00, 0x03, strlen($host)).$host.pack("n", $port));
                    $buf = socket_read($sock,2,PHP_BINARY_READ);
					$response = unpack("Cversion/Cresult", $buf);
					if($response['result'] != 0 ) {
                        $this->_error ="Request to {$host}:{$port} rejected or failed";
						socket_close($sock);
                        return false;
                    }
                    break;
                case HttpClient::PROXY_HTTP:
					$path = $this->_require_uri;
                    $this->_require_header['Proxy-Connection'] = 'Close';
                    if(!empty($this->_proxy_user)) {
                        $this->_require_header['Proxy-Authorization'] = 'Basic '.base64_encode($this->_proxy_user.':'.$this->_proxy_pass);
                    }
                    break;
            }

        }else {
            if(!socket_connect($sock,$host,$port)) {
                $this->_error = "Cann't connect to {$host}:{$port}";
                return false;
            }
        }
		
        //send data
        $_method = strtoupper($method)." {$path} HTTP/1.0\r\n";
        $data = $_method.$this->create_header()."\r\n".$data;

        socket_write($sock, $data);

		$this->_response_cookie = $this->_require_cookie;	
		$recv = '';
		while(($line = @socket_read($sock,1024)) != false) {
			$recv .=  $line;
		}
		
		 switch($this->_proxy_type) {
				case HttpClient::PROXY_SOCKS4:
					break;
				case HttpClient::PROXY_SOCKS5:
						if($recv) $recv = substr($recv,8);
					break;
		 }
		$arr = explode("\r\n\r\n",$recv);
		
		//处理报头
		$heads = explode("\r\n",array_shift($arr));

		foreach($heads as $line){
			if(trim($line)=='' || $line=="\r\n") continue;
			if (!strncasecmp('HTTP', $line, 4)) {
                    //status
                    $status = explode(' ', $line);
                    $this->_response_status = intval($status[1]);
             }elseif(!strncasecmp('Set-Cookie: ', $line, 12)) {
                     $this->_response_cookie = array_merge($this->_response_cookie,$this->cookie_str2arr(substr($line, 12)));
					 if($this->_keep_cookie){
						$this->_require_cookie = array_merge($this->_require_cookie,$this->_response_cookie);
					 }
              }else {
                    $header = explode(':',$line,2);
                    if(count($header) == 2) $this->_response_header[$header[0]] = trim($header[1]);
              }
		}
		//报文	
		$this->_response_body = implode("\r\n\r\n",$arr);
        socket_close($sock);
    }

    private function create_header() {
        $header = '';
        foreach ($this->_require_header as $k=>$v) {
            $header .= $k.': '.$v."\r\n";
        }
        if(count($this->_require_cookie)) {
            $cookie = '';
            foreach ($this->_require_cookie as $k=>$v) {
                $cookie .= $k.'='.$v.';';
            }
            if(!empty($cookie)) $header .= "Cookie: $cookie\r\n";
        }
        return $header;
    }

    //get 请求
    public function get($uri) {
        $this->_require_uri = $uri;
        $this->init_response();
        $this->send('get');
        $this->init_require();
    }

    public function post($uri,$data=array(),$files=array()) {
        $this->_require_uri = $uri;
        $this->init_response();
        $post = '';
        if(count($files)) {
            $post = $this->post_file($data,$files);
        }else {
            $post = $this->post_text($data);
        }
        $this->_require_header['Content-Length'] = strlen($post);

        $this->send('post',$post);
        $this->init_require();
    }

    private function post_text($data) {
        $post = '';
        if(count($data)) {
            foreach($data as $k=>$v) {
                $post .= '&'.$this->format_post($k,$v);
            }
            $post = substr($post, 1);
        }
        $this->_require_header['Content-Type'] = 'application/x-www-form-urlencoded';
        return $post;
    }

    private function post_file($data,$files) {
        $boundary = "---------------------------".substr(md5(rand(0,32000)),0,10);
        $this->_require_header['Content-Type'] = 'multipart/form-data; boundary='.$boundary;
        $post =  "--$boundary\r\n";
        //附件数据
        foreach($files as $k=>$v) {
            if(is_file($v)) {
                $content = file_get_contents($v);
                $filename = basename($v);
                $file_type = $this->get_mime($v);
                $post.="Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$filename}\"\r\n";
                $post.="Content-Type: {$file_type}\r\n\r\n";
                $post.="$content\r\n";
                $post .="--$boundary";
            }
        }
        //附带文本数据
        if(count($data)) {
            foreach($data as $k=>$v) {
                $post .="\r\nContent-Disposition: form-data; name=\"$k\"\r\n\r\n";
                $post .="$v\r\n";
                $post .="--$boundary";
            }
        }
        $post .="--\r\n\r\n";
        return $post;
    }

    private function format_post($k,$v) {
        $post = '';
        if(is_array($v)) {
            if(count($v)) {
                foreach($v as $_k=>$_v) {
                    $post.= ('&'.$this->format_post($k.'['.$_k.']',$_v));
                }
            }
        }else {
            $post.= ('&'.$k.'='.rawurlencode($v));
        }
        return substr($post, 1);
    }

    private  function get_mime($file) {
        $arr =  explode('.', $file);
        $ext = strtolower($arr[count($arr)-1]);
        if(isset($this->_mimes[$ext])) {
            return $this->_mimes[$ext];
        }else {
            return 'image/jpeg';
        }
    }
}
?>

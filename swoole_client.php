<?php

   //daemonize 设置为守护进程后，相对路径可能会出错，请使用绝对路径写文件。
/*    
wget http://pecl.php.net/get/swoole-1.9.22.tgz
tar zxf swoole-1.9.22.tgz
cd swoole-1.9.22
phpize
./configure --with-php-config=/usr/local/php/bin/php-config
make && make install */

	//创建Server对象，监听 127.0.0.1:9502端口，类型为SWOOLE_SOCK_UDP

	//client

	//$str = json_encode( array('time'=>time(),'0'=>'127.0.0.1:9502', 'client'=>'WOOLE_SOCK_UDP') );
	
	$str  = json_encode( array('logname'=>'xxxx','logstring'=>12345678) );
	
	while( true ){
		
		if ( ! $socket){
		 $socket = stream_socket_client("udp://127.0.0.1:9502", $errno, $errstr, 3 );

		}
		fwrite($socket, $str);		
	}
	fclose($socket);
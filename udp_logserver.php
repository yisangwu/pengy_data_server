<?php

	/**
	 * log server
	 * swoole.so
	 * daemonize 设置为守护进程后，相对路径可能会出错，请使用绝对路径写文件。
	 * 
	 */
	defined( 'DS' ) OR define( 'DS', DIRECTORY_SEPARATOR );
	define( 'PATHROOT', __DIR__ );
	defined( 'DATA_LOG' ) OR define( 'DATA_LOG', PATHROOT . DS . 'datalog' . DS );	
	defined( 'SVR_LOG' ) OR define( 'SVR_LOG', PATHROOT . DS . 'svrlog' . DS );

	//host,port
	//SWOOLE_PROCESS 使用进程模式，业务代码在Worker进程中执行
	//SWOOLE_SOCK_UDP 创建udp socket
	$serv = new swoole_server("127.0.0.1", 9502, SWOOLE_PROCESS, SWOOLE_SOCK_UDP ); 

	//conf
 	$serv->set(array(
		'worker_num'      => 2,     //设置启动的Worker进程数
		'task_worker_num' => 4,     //配置Task进程的数量，配置此参数后将会启用task功能。所以Server务必要注册onTask、onFinish2个事件回调函数。如果没有注册，服务器程序将无法启动。
		'daemonize'       => false, //置daemonize => 1时，程序将转入后台作为守护进程运行。
		//Listen队列长度，如 backlog => 128，最多同时有多少个等待accept的连接
		'backlog'         => 128,   
		'dispatch_mode'   => 1,     //数据包分发策略。1，轮循模式，收到会轮循分配给每一个worker进程
		//打开EOF检测，此选项将检测客户端连接发来的数据,当数据包结尾是指定的字符串时才会投递给Worker进程。
		'open_eof_check'  => true,  
		//启用EOF自动分包。当设置open_eof_check后，底层检测数据是否以特定的字符串结尾来进行数据缓冲。
		//启用open_eof_split参数后，无论参数open_eof_check是否设置，open_eof_split都将生效。
		'open_eof_split'  => true,
		//与 open_eof_check 或者 open_eof_split 配合使用，设置EOF字符串。
		//package_eof最大只允许传入8个字节的字符串
		'package_eof'     => PHP_EOL,
		//指定swoole错误日志文件。在swoole运行期发生的异常信息会记录到这个文件中。默认会打印到屏幕。
		//# Master进程
		//$ Manager进程
		//* Worker进程
		//^ Task进程
		'log_file'        => SVR_LOG.'err.log',
		//设置swoole_server错误日志打印的等级，范围是0-5。低于log_level设置的日志信息不会抛出。
		//*默认是0 也就是所有级别都打印
		//0 =>DEBUG
		//1 =>TRACE
		//2 =>INFO
		//3 =>NOTICE
		//4 =>WARNING
		//5 =>ERROR
		'log_level'       => 4,
	));

 	//Start
 	$serv->on('Start', function ($serv) {
		swoole_set_process_name("py_datasvr_master");
	});

	//Packet
	$serv->on('Packet', function ($serv, $data, $clientInfo) {
		$serv->task( $data ); 
	});

	//onManagerStart
	$serv->on('ManagerStart', function($serv){
		swoole_set_process_name("py_datasvr_manager");
	});

	// //WorkerStart -ok
	// $serv->on('WorkerStart', function($serv, $worker_id){
 // 		if ( $worker_id >= $serv->setting['worker_num']){
 //            swoole_set_process_name("py_datasvr_task");
 //        }else{
 //            swoole_set_process_name("py_datasvr_worker");
 //        }
	// });

	//WorkerStart -ok
	$serv->on('WorkerStart', function($serv){
		// true表示当前的进程是Task工作进程
		// false表示当前的进程是Worker进程
 		if ( $serv->taskworker ){
            swoole_set_process_name("py_datasvr_task");
        }else{
            swoole_set_process_name("py_datasvr_worker");
        }
	});


	//Task
	$serv->on('Task', function($serv, $task_id, $from_id, $data) {
		$data = trim( $data );
		if( empty($data)){
			return FALSE;
		}
		//to array
		$data_arr = json_decode( $data,TRUE );
		if( empty($data_arr)||!is_array($data_arr) ){
			return FALSE;
		}
		$logname   = $data_arr['logname'];
		$logstring = $data_arr['logstring'];
		if( empty($logname)||empty($logstring) ){
			return FALSE;
		}
		//path
		$path = DATA_LOG .$logname.DS.date('Ym').DS;
		if( !is_dir($path) ){
			if( !@mkdir( $path,0775,TRUE) ){ //递归模式创建目录
				return FALSE;
			}
		}
		//每天一个文件
		if( $ext ){
			$file = $path.$logname.date('Ymd').$ext;
		}else{
			$file = $path.$logname.date('Ymd');
		}
		
		//写入文件
		@file_put_contents( $file, $logstring.PHP_EOL, FILE_APPEND );
	});

	/**
	 * finish
	 */
	$serv->on('Finish', function ($serv,$task_id, $data) {
		return TRUE;
	});

	$serv->start();

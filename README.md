# py_data_server

日志server  UDP， 基于swoole 
多机器部署， 或者 log 与业务机分离的情况下， 启用UDP server收集

以分类日志名，每天一个文件

日志目录
 | 
 |—— logname1
 |     |
 |     |_ 20190909
 |     |_ 20190910
 |
 |—— logname2
 |—— ...

# yii2-logstash

## Yii2配置

- 将` LogStashFileTarget.php` 放置在 `app/libraries/logs`下

- 修改 `app/config/web.php` 添加`LogstashFileTarget` 部分

  ```php
  <?php
    ....
     'log' => [
              'traceLevel' => YII_DEBUG ? 3 : 0,
              'targets' => [
                  ....
                  [
                      'class' => 'app\libraries\log\LogstashFileTarget',
                      'levels' => ['error', 'warning'],
                      'except' => ['yii\web\HttpException:404', 'yii\web\HttpException:403', 'yii\debug\Module::checkAccess',  'yii\web\HttpException:400', 'yii\base\UserException', 'yii\web\HttpException:401'],
                      'logFile' => '@runtime/logs/logstash.log',
                  ]
                ....
              ],
          ],
  .....
    
  ?>
  ```

  ​

## Logstatsh配置文件

- 将`yii2.conf`放置在`/etc/logstash/conf.d`下

  ```shell
  input {
    file {
      type => "prod"
      path => "$project_path/app/runtime/logs/logstash.log"
      #start_position => "end"
      codec => "json"
    }
  }


  output {
  	if [type] == "prod" {
             elasticsearch {
                  hosts => ["$elasticsearch_host:$elasticsearch_port"]
                  index => "yii-web-%{+YYYY-MM-dd}"
                  document_type => "yii2"
                  workers => 1
                  flush_size => 20000
                  idle_flush_time => 10
             }
  	}
  }
  ```

- 重启 logstatsh

  ```shell
  # /etc/init.d/logstash 
  Usage:  {start|stop|force-stop|status|reload|restart|configtest}
  ```

  ​


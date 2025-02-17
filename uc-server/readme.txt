=========================================
 UCenter 2.0 全新安装文档
=========================================

1. 安装前准备 PHP 7、MySQL 5.7、Redis（如果需要使用 API 接口）

2. 将 upload 文件夹下所有的文件上传

3. 设置如下文件夹权限为可写权限， Linux 系统设置 777 
	./data
	./data/avatar
	./data/backup
	./data/cache
	./data/logs
	./data/tmp
	./data/view

3. 通过浏览器访问 https://您的域名/UC目录/install/， 根据提示填写 MySQL 配置信息、管理员账号信息

4. 完成安装

=========================================
 UCenter 2.0 升级安装文档
=========================================

1、确保 PHP 必须为 PHP 7，如不满足请先升级

2、备份旧 UCenter 目录 ./uc_server 和数据库

3、将 upload 文件夹下所有的文件上传覆盖到旧 UCenter 目录原文件

4、如果 UCenter 应用为 Discuz! X3.4 版本，请在 ./data/config.inc.php 结尾添加

define('UC_PASSWORD_WITH_SALT', true);

5、如果需要使用 API 接口，请先准备 Redis，然后在 ./data/config.inc.php 结尾添加

define('UC_REDIS_HOST', '127.0.0.1');
define('UC_REDIS_PORT', 6379);
define('UC_REDIS_CONNECT', 1);
define('UC_REDIS_TIMEOUT', 0);
define('UC_REDIS_PASS', '');
define('UC_REDIS_DB', 0);
define('UC_REDIS_KEYPREFIX', 'uc_');

酌情修改相关配置

6、通过浏览器访问 https://您的域名/UC目录/ 登录管理中心

7、完成升级
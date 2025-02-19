#!/bin/sh
set -e

# 如果挂载目录是空的，从镜像中复制文件
if [ -z "$(ls -A /var/www/html/uc-server)" ]; then
    cp -a /tmp/uc-server/. /var/www/html/uc-server/
    chown -R www-data:www-data /var/www/html/uc-server
fi

# 执行原来的命令
exec "$@"

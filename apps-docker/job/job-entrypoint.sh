#!/bin/sh
set -e

# 如果挂载目录是空的，从镜像中复制文件
if [ -z "$(ls -A /var/www/html/job)" ]; then
    cp -a /tmp/job/. /var/www/html/job/
    chown -R www-data:www-data /var/www/html/job
fi

# 执行原来的命令
exec "$@"

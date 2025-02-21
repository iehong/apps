FROM php:7.4-fpm-alpine

# 安装依赖和核心构建工具
RUN sed -i 's/dl-cdn.alpine.com/mirrors.aliyun.com/g' /etc/apk/repositories && \
  apk update && apk add --no-cache \
  # GD 依赖
  freetype-dev \
  libjpeg-turbo-dev \
  libpng-dev \
  # mysqli 依赖
  mariadb-dev \
  # Redis 和 Opcache 构建工具
  $PHPIZE_DEPS \
  # 安装 msmtp
  msmtp

# 配置并安装 GD 扩展（启用 JPEG 和 Freetype）
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd

# 安装 mysqli 和 opcache 扩展
RUN docker-php-ext-install mysqli opcache

# 安装 Redis 扩展
RUN pecl install redis \
  && docker-php-ext-enable redis

# 清理构建文件和缓存
RUN apk del $PHPIZE_DEPS \
  && rm -rf /tmp/pear \
  && docker-php-source delete

RUN mkdir -p /var/www/html/job
VOLUME /var/www/html/job

COPY ./apps-docker/job/job-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/job-entrypoint.sh

COPY ./job/uploads /tmp/job

# 复制 msmtprc 配置文件
COPY ./apps-docker/bbs/msmtprc /etc/msmtprc
RUN chmod 600 /etc/msmtprc
RUN chown www-data:www-data /etc/msmtprc

# 设置 sendmail 链接到 msmtp
RUN ln -sf /usr/bin/msmtp /usr/sbin/sendmail

ENTRYPOINT ["job-entrypoint.sh"]
CMD ["php-fpm"]

FROM php:fpm-alpine

# 安装依赖和核心构建工具
RUN apk update && apk add --no-cache \
  # GD 依赖
  freetype-dev \
  libjpeg-turbo-dev \
  libpng-dev \
  # mysqli 依赖
  mariadb-dev \
  # Redis 和 Opcache 构建工具
  $PHPIZE_DEPS

# 配置并安装 GD 扩展（启用 JPEG 和 Freetype）
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd

# 安装 mysqli 和 opcache 扩展
RUN docker-php-ext-install mysqli opcache

# # 安装 Redis 扩展
# RUN pecl install redis \
#   && docker-php-ext-enable redis

# 清理构建文件和缓存
RUN apk del $PHPIZE_DEPS \
  && rm -rf /tmp/pear \
  && docker-php-source delete

COPY --chown=www-data:www-data ./bbs/upload /var/www/html/bbs
COPY --chown=www-data:www-data ./apps-docker/bbs/login_mobile /var/www/html/bbs/source/plugin/login_mobile

FROM swoole-mongodb
COPY . $PATH_SERVER/hyperf
WORKDIR $PATH_SERVER/hyperf
RUN composer update; \
    rm -rf runtime/container; \
    php bin/hyperf.php vendor:publish jiajushe/hyperf-helper --id helper_function; \
    php bin/hyperf.php vendor:publish jiajushe/hyperf-helper --id res_code
CMD supervisord && rm -rf runtime/container && php bin/hyperf.php start
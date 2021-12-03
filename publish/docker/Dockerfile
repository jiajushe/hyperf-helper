FROM swoole-mongodb-redis
WORKDIR $PATH_SERVER/hyperf
CMD supervisord && rm -rf runtime/container && php bin/hyperf.php start
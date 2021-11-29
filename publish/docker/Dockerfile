FROM swoole-mongodb
#COPY . $PATH_SERVER/hyperf
WORKDIR $PATH_SERVER/hyperf
CMD supervisord && rm -rf runtime/container && php bin/hyperf.php start
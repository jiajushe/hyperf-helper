FROM swoole-mongodb
COPY . $PATH_SERVER/hyperf
WORKDIR $PATH_SERVER/hyperf
#  mongod -f $PATH_MONGODB_CONFIG/mongodb_noauth.conf; \
#  mongo $PATH_MONGODB_CONFIG/mongodb_auth.js; \
#  mongod -f $PATH_MONGODB_CONFIG/mongodb_noauth.conf --shutdown; \
CMD supervisord && rm -rf runtime/container && php bin/hyperf.php start
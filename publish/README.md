更新类库

composer update

php bin/hyperf.php vendor:publish jiajushe/hyperf-helper

创建数据卷目录

mkdir -p /server/docker-volumes/swoole-mongodb-redis

***************************************************************************************

创建mongodb配置文件

cp ./volumes/config/mongodb/mongodb.conf.default ./volumes/config/mongodb/mongodb.conf

cp ./volumes/config/mongodb/mongodb_noauth.conf.default ./volumes/config/mongodb/mongodb_noauth.conf

创建mongodb管理员脚本

cp ./volumes/config/mongodb/mongodb_auth.js.default ./volumes/config/mongodb/mongodb_auth.js

***********************************************************************************

创建supervisor mongod 守护进程配置文件

cp ./volumes/config/supervisor/mongod.conf.default ./volumes/config/supervisor/mongod.conf

cp ./volumes/config/supervisor/redis.conf.default ./volumes/config/supervisor/redis.conf

***********************************************************************

创建php配置文件

cp ./volumes/config/php/php.ini.default ./volumes/config/php/php.ini

********************************************************************

创建redis配置文件

cp ./volumes/config/redis/redis.conf.default ./volumes/config/redis/redis.conf

********************************************************************


复制配置文件到宿主数据卷目录

cp -r ./volumes/config /server/docker-volumes/swoole-mongodb-redis

****************************************************************************

创建.env

cp ./.env.example ./.env

**********************************************************************

创建镜像

docker-compose build

运行镜像

docker-compose up -d

***********************************************************************

创建mongodb管理员 在容器内运行

supervisord

mongo /server/config/mongodb/mongodb_auth.js.default

修改 mongod 守护进程配置文件

vim /etc/supervisor/conf.d/mongod.conf ， mongodb_noauth.conf 修改为 mongodb.conf

mongod -f /server/config/mongodb/mongodb_noauth.conf --shutdown

supervisorctl update

****************************************************************

开发运行

docker run --name swoole-mongodb-redis -it --network home-club \
-v /server/docker-volumes/swoole-mongodb-redis/data/mongodb/databases:/server/data/mongodb/databases \
-v /server/docker-volumes/swoole-mongodb-redis/data/mongodb/logs:/server/data/mongodb/logs \
-v /server/docker-volumes/swoole-mongodb-redis/data/supervisor/logs:/server/data/supervisor/logs \
-v /server/docker-volumes/swoole-mongodb-redis/data/redis:/server/data/redis \
-v /server/docker-volumes/swoole-mongodb-redis/config:/server/config \
-v /server/docker-volumes/swoole-mongodb-redis/config/supervisor:/etc/supervisor/conf.d \
-v /home/smart/my_project/saas/server/swoole-mongodb-redis:/server/hyperf \
-p 27030:27017 \
-p 6379:6379 \
swoole-mongodb-redis \
bash

supervisord

rm -rf runtime/container && php bin/hyperf.php start
创建数据卷目录

mkdir -p /server/docker-volumes/saas-auth

***************************************************************************************

创建mongodb配置文件

cp ./volumes/config/mongodb/mongodb.conf.default ./volumes/config/mongodb/mongodb.conf

cp ./volumes/config/mongodb/mongodb_noauth.conf.default ./volumes/config/mongodb/mongodb_noauth.conf

创建mongodb管理员脚本

cp ./volumes/config/mongodb/mongodb_auth.js.default ./volumes/config/mongodb/mongodb_auth.js

***********************************************************************************

创建supervisor mongod 守护进程配置文件

cp ./volumes/config/supervisor/mongod.conf.default ./volumes/config/supervisor/mongod.conf

***********************************************************************

创建php配置文件

cp ./volumes/config/php/php.ini.default ./volumes/config/php/php.ini

********************************************************************


复制配置文件到宿主数据卷目录

cp -r ./volumes/config /server/docker-volumes/saas-auth

****************************************************************************

创建.env

cp ./.env.example ./.env

**********************************************************************

容器内php配置文件目录

/server/config/php

*****************************************************************

容器内mongodb配置文件目录

/server/config/mongodb，默认使用配置文件 mongodb_noauth.conf

容器内mongodb数据库目录

/server/data/mongodb/databases

容器内mongodb记录目录

/server/data/mongodb/logs

*****************************************************************

容器内supervisor 主配置文件

/etc/supervisor/supervisord.conf

容器内supervisor 子配置文件目录

/etc/supervisor/conf.d

supervisor mongod 守护进程配置文件

/etc/supervisor/conf.d/mongod.conf

supervisor 记录文件目录

/server/data/supervisor/logs

****************************************************************

创建mongodb管理员 在容器内运行

supervisord

mongo /server/config/mongodb/mongodb_auth.js

修改 mongod 守护进程配置文件

vim /etc/supervisor/conf.d/mongod.conf ， mongodb_noauth.conf 修改为 mongodb.conf

mongod -f /server/config/mongodb/mongodb_noauth.conf --shutdown

supervisorctl update

****************************************************************

开发运行

docker run --name saas-auth -it --network home-club \
-v /server/docker-volumes/saas-auth/data/mongodb/databases:/server/data/mongodb/databases \
-v /server/docker-volumes/saas-auth/data/mongodb/logs:/server/data/mongodb/logs \
-v /server/docker-volumes/saas-auth/config/mongodb:/server/config/mongodb \
-v /server/docker-volumes/saas-auth/config/php:/server/config/php \
-v /server/docker-volumes/saas-auth/config/supervisor:/etc/supervisor/conf.d \
-v /server/docker-volumes/saas-auth/data/supervisor/logs:/server/data/supervisor/logs \
-v /home/smart/my_project/saas/server/saas-auth:/server/hyperf \
-p 27017:27017 \
saas-auth \
bash

rm -rf runtime/container && php bin/hyperf.php start
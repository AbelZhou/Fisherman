# Fisherman
一个遵循psr4的业务类库，集成了数据库与缓存层。  
主要适用于单纯想使用sql，不想使用orm的朋友。  
成天用orm都快用傻了，sql都不会写了。不如Fuck ORM。

## 安装
```bash
composer require abelzhou/Fisherman
```


## 使用
### 构造项目目录
```bash
vendor/bin/fisherman init
```

构造完项目目录后，会生成如下文件及目录
```
├── Conf
│   ├── local
│   │   ├── cache.yml
│   │   ├── config.yml
│   │   └── db.yml
│   ├── rls
│   └── test
├── Model
│   └── DBName
├── Module
│   └── DBName
├── bootstrap.php
├── composer.json
└── .gitigore
```
* Conf：配置文件目录
    * 环境文件夹(local|rls|test),取决于"RUNTIME_ENV"环境变量。Default：local
        * cache.yml memcache缓存配置（可更换引擎，目前只实现memcache）
        * config.yml 项目配置文件
        * db.yml 数据库配置
* model：底层数据交互目录，原则上一个数据库一个文件夹
* module：业务逻辑目录，原则上一个数据库一个文件夹
* bootstrap.php 引导文件，在某些web或者常驻内存项目中，需要引入该文件让业务类库生效。
* composer.json 



### 修改conf
构造完项目后，需要手工处理两个文件才能生成对应的model和module  
1、config.yml  
2、db.yml  
```bash
# 修改项目名称 默认为demo
vim Conf/local/config.yml
# 修改数据库连接必要条件  这里假设需要test数据库下的业务
vim Conf/local/db.yml
```


### 生成对应的model以及module
```bash
vendor/bin/fisherman make:model test user -m User
```
详细信息参考
```bash
vedor/bin/fisherman -h
```



### 配置说明
#### cache.yml
```yaml
memcached: #引擎tag
  - host: # host 可以配置多个
      127.0.0.1 
    port:
      11211
    weight: #权重
      10
```

#### config.yml
```yaml
project:
  name: # 项目名称，项目名称会影响之后生成的model与module类的命名空间前缀  
    Demo
cache:
  engine: # 选择的缓存引擎
    memcached
  prefix: # 缓存前缀
    prefix_
  expire:  # 默认的过期时间
    300
db:
  engine: # 数据库引擎
    mysql
log:
  dir: # 日志输出目录 每天一个文件
    /../Logs/
  level: # 日志输出的默认等级
    0
```

#### db.yml
```yaml
test: #dbtag 配置标签，标签不能重复，一般为每个数据库配置一个标签
  writer: #写库只能配置一个
    host:
      127.0.0.1
    port:
      3306
    user:
      root
    password:
      123456
    database:
      test
    charset:
      utf8
  reader: # 从库可以配置多个
    - host:
        127.0.0.1
      port:
        3306
      user:
        root
      password:
        123456
      database:
        test
      charset:
        utf8
```
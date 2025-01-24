# deepseekweb
通过调用deepseek API KEY在网页端进行对话

本项目实现二级缓存，每次的问询先从本地缓存中查询是否有相同的问询，如果不存在则从数据库中查询

需要进行以下配置即可使用

1、创建数据库
直接执行ai_chat_db.sql中的语句

2、配置数据库信息
在db_config.php中填入对应的数据库连接信息

3、配置API KEY
在config.php中填入deepseek的API KEY

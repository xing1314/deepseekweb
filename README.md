```markdown
# 🚀 DeepSeek Web - 智能对话接口解决方案

通过简洁的Web界面与DeepSeek AI进行高效对话，支持二级缓存加速响应，降低API调用成本。

## 🌟 核心特性
- ⚡ 智能二级缓存体系
  - 优先查询本地缓存
  - 缓存未命中时自动查询数据库
  - 双重保障降低API调用频率
- 🔒 安全配置管理
  - 独立配置文件保护敏感信息
  - 环境隔离部署支持
- 📦 开箱即用
  - 简洁的Web交互界面
  - 清晰的SQL初始化脚本

## 🛠️ 快速开始

### 环境要求
- PHP 8.0+
- MySQL 5.7+
- Composer（依赖管理）
```
### 安装步骤
初始化数据库
```sql
-- 执行 ai_chat_db.sql 中的SQL语句
mysql -u root -p < ai_chat_db.sql
```

### ⚙️ 配置指南
1. 数据库配置 `db_config.php`
```php
<?php
define('DB_HOST', 'your_database_host');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'ai_chat_db');
?>
```

2. API密钥配置 `config.php`
```php
<?php
define('DEEPSEEK_API_KEY', 'your_api_key_here');
?>
```

## 🖥️ 使用示例
启动Web服务后：
1. 访问 `http://localhost/index.php`
2. 在输入框中输入对话内容
3. 系统将优先从缓存返回历史结果
4. 新查询将自动存储到数据库并更新缓存

## 🤝 贡献指南
欢迎通过Issue和PR参与贡献：
1. Fork项目仓库
2. 创建特性分支 (`git checkout -b feature/awesome-feature`)
3. 提交修改 (`git commit -am 'Add awesome feature'`)
4. 推送分支 (`git push origin feature/awesome-feature`)
5. 新建Pull Request

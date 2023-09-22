# image-process
responsive image processing based on files in Azure Blob Storage.

# 准备一个Blob 存储

获取 blob 存储容器的访问字符串
然后保存到环境变量中

# 本地开发
cd image-process
php -S localhost:8000

安装 composer
安装 microsoft/azure-storage-blob
PHP 启用 GD 库

# App Service 部署
az login 
...

RESOURCE_GROUP=CN3
WEBAPP_NAME=img
```
zip -r deploy.zip .
az webapp deploy -g $RESOURCE_GROUP -n $WEBAPP_NAME --src-path deploy.zip --type zip
```
App Servie PHP 8.2 版本直接支持 GD 库，无需安装。

# 配置自定义域名

# 把密钥信息等保存到环境变量中

更多图片处理功能

文件不存在的检测
图片路径放到REQUEST_URI上。

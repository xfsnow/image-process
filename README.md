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
https://learn.microsoft.com/zh-cn/azure/app-service/configure-common?tabs=portal#configure-app-settings

# 配置 CDN 服务
https://docs.azure.cn/zh-cn/cdn/cdn-how-to-use

## 添加自定义域名

## HTTPS 证书管理
必须通过 Azure Key Vault 管理，先要在 Azure AD 里注册一个应用，记下AAD应用的 Application (client) ID。创建一个应用的 Secrect，记下其 Secrect Value。

创建 Key Vault，在 Access Policy 里给 AAD 应用赋权，
Key Permissions
Secret Permissions
Certificate Permissions
都要选中。

回到 CDN 控制台，填写
密钥保管库 DNS 名称 => Key Vault Overview 页的 Vault URI
Azure Active Directory 客户端 ID => AAD应用的 Application (client) ID
Azure Active Directory 密码 => AAD应用的 Secrect Value

证书管理里
选择现有证书

更多图片处理功能

文件不存在的检测
图片路径放到REQUEST_URI上。

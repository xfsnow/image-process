# Responsive Image Processing

When developing web applications, we often save the original images and then generate various sizes of thumbnails, as well as perform simple operations such as flipping, cropping, and rotating on the images. This use case is very common, so much so that mainstream cloud platforms provide PaaS services that add image processing logic based on CDN services, achieving different image processing functions through different parameters. For example, the CDN service in Azure China region provides such a feature - [Azure CDN Image Processing](https://docs.azure.cn/zh-cn/cdn/cdn-image-processing).

Unfortunately, Azure overseas regions do not yet have this managed service, but it is very convenient to implement such an image processing solution by combining existing managed services, especially using [Azure App Service](https://azure.microsoft.com/products/app-service/) as the core computing service, which not only [supports various mainstream development languages](https://learn.microsoft.com/en-us/azure/app-service/overview#built-in-languages-and-frameworks), but also comes with common extensions, making it even easier to develop small image processing applications.

The overall architecture of this solution is very simple.

![Architecture diagram of image processing](doc/image-process-arch.png)

# Prepare a Blob Storage Container

The uploaded original images are stored in Azure Blob Storage. Refer to the [official documentation to create a Blob Storage container](https://learn.microsoft.com/azure/storage/blobs/storage-quickstart-blobs-portal#create-a-container), which will not be repeated here. Record the name of the created Blob Storage container and save it as an environment variable `AZURE_BLOB_CONTAINER`.

Then upload a few image files to this storage container, as described in [upload a block blob](https://learn.microsoft.com/azure/storage/blobs/storage-quickstart-blobs-portal#upload-a-block-blob), for example, I uploaded an image file named Microsoft.png for later development demonstration.

Record the connection string of the Blob Storage container. In the Security + networking of the storage account, find Access keys, click the Show button under key1 in the main pane, then click the copy icon to copy the connection string to the clipboard, and save it as an environment variable `AZURE_BLOB_CONNECTION` for use by the image processing application later.

![Get the connection string of the Blob Storage container](doc/blob-connection.png)

# Local Development
This application initially uses PHP 8.2.1 and requires the GD extension for image processing. Use PHP Composer to install microsoft/azure-storage-blob.

After cloning the current source code repository to your local machine,
```sh
cd image-process
php -S localhost:8000
```
to run the local test site. Open `http://localhost:8000/?filename=Microsoft.png&width=100&height=100` in a browser to see the effect of image processing.

# App Service 部署

参考官方文档创建一个 App Service 实例，海外 Azure 支持免费档，足够我们测试和演示使用了。创建好的 App Service 实例所在资源组和名称记录下来，启用一个本地 Shell 保存为2个常量方便后续使用命令行部署。 

把前面保存的Blob存储容器的连接字符串和名称设置成 App Service的应用设置项目，可参考[官方文档](https://docs.microsoft.com/azure/app-service/configure-common#configure-app-settings)。

最后把代码打包部署上去即可。

```
RESOURCE_GROUP=my_resource_group
WEBAPP_NAME=my_app_service_name

az webapp config appsettings set -g $RESOURCE_GROUP -n $WEBAPP_NAME --settings AZURE_BLOB_CONNECTION="my_blob_connection"

az webapp config appsettings set -g $RESOURCE_GROUP -n $WEBAPP_NAME --settings AZURE_BLOB_CONTAINER="my_blob_container"

zip -r deploy.zip .
az webapp deploy -g $RESOURCE_GROUP -n $WEBAPP_NAME --src-path deploy.zip --type zip
```
App Servie PHP 8.2 版本直接支持 GD 库，无需安装。

到此，我们已经完成了一个简单的图片处理应用的开发和部署，可以在浏览器中访问 `https://my_app_service_name.azurewebsites.net/index.php?filename=Microsoft.png&width=100&height=100` 查看效果。更多图片处理的效果及参数，请参见[源码](index.php)。

接下来就是配置 CDN 服务，让图片处理应用能够通过 CDN 服务提供的功能，实现图片处理的加速。

# 配置 CDN 服务
先确认 CDN 服务已经注册为 resource provider。先到订阅的 Settings 找到 Resource provider，然后搜索 CDN，如果没有找到，点击 Register 按钮注册。

![注册 CDN 服务为 Azure resource provider](doc/cdn-register.webp)

创建CDN实例，在 Offering 页点 Explore other offerings，再点 Azure CDN Standard from Microsoft (classic) 。
![创建 Azure CDN 1](doc/azure-cdn1.png)
再按提示选择订阅、资源组，填写 CDN profile名称等即可。

![创建 Azure CDN 2](doc/azure-cdn2.png)

CDN profile 创建好后，添加一个 Endpoint。在 CDN Profile 的 overview 页，右边主窗格点 +Endpoint 按钮，按提示填写 Endpoint 名称如`my_cdn_endpoint`、源站类型选择 `Web App`。

Origin hostname 从下拉菜单中选择前面部署好的 App Service 实例，比如 `my_app_service_name.azurewebsites.net`。其它的保持默认，点击最底下的 Add 按钮。

![创建 Azure CDN 3](doc/azure-cdn3.png)

现在我们的图片处理都是通过参数来控制的，所以需要在 CDN Endpoint 的配置里把每个查询字符串的参数分别缓存。在 CDN Endpoint 的左侧导航菜单找到 Setting 下的 Caching Rules，右边主窗格点 Query string caching behavior菜单选择 Cache every unique URL，点击最上面的 Save 按钮。

![创建 Azure CDN 4](doc/azure-cdn4.png)

配置 CDN 的 Rules engine，这里使用简单的图片缓存 1小时的规则。在 Setting 下点击 Rules Engine，在 Global 右边点击 + Add action，在下拉菜单中选择 Cache Expiration。

在 Cache behavior 菜单选择 Set if missing，然后 Days 填写 1，然后点击上面的 Save 按钮即可。

![配置 CDN 的 Rules engine](doc/azure-cdn5.png)

到此，CDN 服务已经配置好了，可以在浏览器中访问 `https://my_cdn_endpoint.azureedge.net/index.php?filename=Microsoft.png&width=100&height=100` 查看效果。

## 添加自定义域名

要添加自定义域名，首先要在 DNS 服务商那里添加一个 CNAME 记录，指向 CDN Endpoint 的自定义域名。比如我在 阿里云的域名解析控制台添加了一个 CNAME 记录，指向 `my_cdn_endpoint.azureedge.net`。

然后回到 Azure CDN Endpoint 的配置里，找到 Custom domains，点击 +Custom domain 按钮，按提示填写自定义域名，点击最底下的 Add 按钮。

![添加 CDN 的自定义域名](doc/azure-cdn6.png)

新添加上的自定义域名，其 Custom HTTPS 状态为 disabled。点击这条记录，进入 Custom domain HTTPS 管理页。
点击 On 按钮；
Certificate management type 选择 CDN managed；
Minimum TLS version 选择 TLS 1.2。
点击上面的 Save 按钮。默认情况下 Azure 会托管地为自定义域名申请一个证书，这个过程可能需要几分钟时间，请耐心等待下成的状态逐个变成完成。

![配置 CDN 的自定义域名](doc/azure-cdn7.png)

到此，我们的图片处理应用已经可以通过自定义域名访问了，比如我在浏览器中访问 `https://my_cdn_domain/index.php?filename=Microsoft.png&width=100&height=100` 查看效果。

# Azure 中国区域的CDN配置
Azure 中国区域和 Azure 海外区域的 CDN 服务有一些差异，主要是在证书管理上。Azure 中国区域的 CDN 服务不支持自定义域名的证书管理，只能通过 Azure Key Vault 管理证书。所以我们需要先在 Azure Key Vault 里创建一个证书，然后在 CDN Endpoint 的配置里选择现有证书。前述的 Blob Storage 和 App Service 的配置不变。

## 在 Microsoft Entra ID 中注册一个应用

在 Microsoft Entra ID 中左侧导航链接中 Manage 下点击 App registrations，然后点击 New registration 按钮，按提示填写应用名称比如“CDN HTTPS”。
Supported account types 选择 Accounts in this organizational directory only。
点击 Register 按钮。

![在 Microsoft Entra ID 中注册一个应用](doc/cn-cdn1.png)

注册好的应用点击到 Overview 页，记录下 Application (client) ID。

然后点击左侧导航链接的 Certificates & secrets，点击 New client secret 按钮，按提示填写 Secret description，Expires 选择 730 days，点击 Add 按钮。

![给AAD应用添加 secret](doc/cn-cdn2.png)
添加成功后，立刻在其 Value 处点击复制图标，把 Secret value 复制到剪贴板。注意这个 Secret value 只会显示一次，所以一定要立刻复制保存好。

![给AAD应用添加 secret](doc/cn-cdn3.png)

## 在 Azure Key Vault 里创建一个证书
参考官方文档[创建一个 Key Vault](https://docs.azure.cn/zh-cn/key-vault/general/quick-create-portal)，然后[上传一个用于 CDN 域名的证书官方文档](https://docs.microsoft.com/azure/key-vault/certificates/quick-create-portal#create-a-certificate)。

## 在 Key Vault 给 Entra 应用赋权

在 Key Vault 左侧导航链接点击 Access Policies，点击 + Create 按钮，在 Create an access policy 页把 Key Permissions、Secret Permissions、Certificate Permissions 都选中，然后点击 Next 按钮。

![设置 Access Policies](doc/cn-cdn4.png)

在 2 Principal 页点击 Select principal 按钮，然后在搜索框中输入应用名称，比如“CDN HTTPS”，然后点击搜索结果中的应用名称，点击 Select 按钮。然后点击 Next 按钮。

![选择 principal](doc/cn-cdn5.png)

Application 页不用修改，直接点击 Review + create 按钮，然后点击 Create 按钮。

回到 Key Vault 的 Overview 页，记录下 Vault URI。

## 配置 CDN Profile
创建 CDN Profile 的操作和海外 Azure 相同，也需要先到自己的 DNS 解析处添加 CNAME 记录，把自定义域名指向 CDN Endpoint 的自定义域名。之后添加 Endpoint 的操作不相同。点击 + Endpoint 按钮，按提示填写 Custom domain 、ICP number和Origin。

Acceleration type 选择 Web acceleration，Origin domain type 选择 Web App，Origin domain 的下拉菜单选择已经部署好的 App Service。点击 App 按钮。

![创建 CDN Endpoint](doc/cn-cdn6.png)

与海外 Azure 不同之处，在创建 CDN Profile 后，需要点击主窗格的 Manage 按钮去继续配置自定义域名的 SSL 证书。

![打开 Manage](doc/cn-cdn7.png)

点击左侧导航菜单最下面的“配置”，在主窗格中密钥保管库 DNS 名称填写前面记录的 Vault URI，Azure Active Directory 客户端 ID 填写前面记录的 Application (client) ID，Azure Active Directory 密码填写前面记录的 Secret value，然后点击 Save 按钮。

![配置 CDN 的密钥保管库](doc/cn-cdn8.png)

通过上述配置，CDN 服务就可以从 Key Vault 里获取证书了。然后点击左侧导航菜单“安全管理”下点击“证书管理”，点击 “+添加一张 SSL 证书”按钮。按提示填写名称。
在证书源中选择“使用已有证书”。
在证书的下拉菜单中选择 CDN 域名需要Key Vault中保存的证书。
绑定域名的下拉菜单中选择“全部”。最后点击下面的“创建”按钮。

![配置 CDN 的证书](doc/cn-cdn9.png)

这里，再点击左侧导航菜单中的“域名管理”，可以看到自定义域名的“是否启用 HTTPS”为“是”，表示 SSL 证书已配置成功。

# TODO
1. 更多图片处理功能
2. 文件不存在的检测
3. 图片路径和图片处理参数都放到REQUEST_URI上。
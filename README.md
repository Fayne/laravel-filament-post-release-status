<p align="center"><img src="https://res.cloudinary.com/fayne/image/upload/v1787643269/fayne_logo.png" width="300"></p>

# Laravel Filament Post Release Status

简体中文 | [English](docs/README.en.md)

适用于 **PHP 8.4+、Laravel 13、Filament 5** 的发布状态与历史记录包。依赖 CMS Core 提供的发布锁、生命周期与冻结快照，可用于普通单站后台和多租户工作台，不强制依赖 Page 或 Menu。

## 功能与边界

| 能力 | 说明 |
| --- | --- |
| 待发布内容 | 展示页面内容、菜单树及设置、站点设置的新增、修改和删除 |
| 内容语言 | 默认汇总当前区域全部启用语言，也可筛选单一语言 |
| 变更详情 | 首屏只显示名称、路径等摘要，点击“查看变更”后按需读取字段差异 |
| 整站发布 | 配合 Core 路由配置，统一发布全部区域与语言，支持域名和 URL 路径区分区域 |
| 发布保护 | 发布期间由 Core 阻止目标租户的 CMS 写入，进度通过 GET 读取 |
| 发布历史 | 查看保存的发布快照与结果；仅成功发布推进比较基线 |
| 后台界面 | 原生 Filament 控件，中英文、深色模式和窄屏布局 |

本包提供管理界面，不负责前端构建和部署，也不提供定时发布。组件、部件和字段结构定义不出现在待发布内容列表中，但仍保留在冻结快照与历史审计中。

## 1. 安装

先按照 [CMS Core 安装说明](../laravel-filament-cms-core/README.md) 配置宿主、认证和包的软件源，再执行：

```bash
composer require fayne/laravel-filament-post-release-status
php artisan migrate
php artisan cms:init
php artisan filament:assets
```

私有包及其依赖的软件源必须配置在宿主根 Composer 中；依赖包内部的 `repositories` 不会自动传递给宿主。本包没有独立的数据表或配置文件；发布数据及配置由 Core 管理。区域内容库的 Page 迁移需按 Core/Page 的说明单独执行。

`cms:init` 同步权限目录并保留已有角色的自定义授权。已有角色需由管理员按需授予新增权限，不会因为安装本包自动获得发布权限。Core 的初始建表迁移面向新数据库，已有项目请自行制定数据升级方案。

## 2. 注册面板与权限

在已有的 CMS PanelProvider 中追加插件，保留宿主原有插件、认证和中间件：

```php
use Fayne\PostReleaseStatus\PostReleaseStatusPlugin;

// 在已有的 ->plugins([...]) 中追加：
PostReleaseStatusPlugin::make(),
```

注册到普通单站管理面板或具有租户上下文的工作台；不要注册到没有租户上下文的平台管理面板。多租户开关、管理员 guard 与发布写入保护按 Core 配置。

| 权限 | 用途 |
| --- | --- |
| `tenant.publication.view` | 查看发布状态与按需变更详情 |
| `tenant.publication.history` | 查看发布历史 |
| `tenant.content.publish` | 开始发布 |

平台管理员修改租户品牌、语言或域名时，同样受目标租户的发布锁保护。

状态页与冻结服务共用一个单例 `ReleaseSnapshotRegistry`，未配置整站路由时比较当前可发布候选与同一站点、租户、区域、语言最后成功快照；启用 v2 时比较同一网站全部范围的最后成功清单。Page 使用当前启用页面，不使用旧发布版本或暂存版本；Menu 比较菜单属性及树。公共设置、组件/部件定义和字段顺序变化归为全局变化，内部时间戳不产生待发布变化，动态内容中同名字段保留。历史只读已保存快照；缺少旧快照时明确显示不可比较，需要完整发布建立基线。

## 构建与回调

配置 `CMS_FRONTEND_DRIVER=astro`、`CMS_BUILD_WEBHOOK_URL`、`CMS_DEPLOYMENT_WEBHOOK_SECRET`，以及 `filament-cms.deployment.build_api_token`。构建端使用 Bearer token，并通过该租户已启用域名访问下列接口：

- `/headless/releases/{release_id}/manifest`：冻结上下文、摘要与实体快照。
- `/headless/releases/{release_id}/pages`：保持 `data[].projection` 页面输出结构，支持 `?path=/...`；不返回内部原始晋升内容。
- `/headless/releases/{release_id}/menus`：保持 `data[].projection` 树结构，`data[].menu` 提供名称、slug、settings 等菜单属性。
- `/headless/releases/{release_id}/global-settings`：保持 `{data: 公共设置键值}` 结构。

构建必须仅从同一 release 的以上接口读取，不能混读实时 `/headless/global-settings`。前端构建器必须将设置、页面和菜单请求指向相同 release_id 的冻结地址。Manifest 中 `global_page_definitions`、`global_menu_definitions` 是定义快照；渲染用页面/菜单投影已经冻结解析结果。

构建 webhook JSON 包含 `site_id`、`release_id`、`attempt_id`、`manifest_digest`。签名保留现有 `X-Nova-CMS-Timestamp` 与 `X-Nova-CMS-Signature: sha256=...`，HMAC 输入为 `timestamp.body`。成功或失败后 POST `/headless/releases/callback`，JSON 为 `{release_id, attempt_id, success}`，可带 `error`，同样签名。只有成功回调推进基线并从冻结原始内容创建页面发布版本；按 release 唯一键重试不重复创建。失败不晋升页面。旧 tenant-null 历史快照页面/菜单接口保留兼容，新的生命周期快照要求 token 与域名租户一致。

有效 `filament-cms.publication_routing` 非空时启用 v2，发布全部配置区域和启用语言，筛选仅影响显示。整站比较按 scope、实体类型和 ID 区分跨库同 ID；路由变化计入全站影响。历史使用当时冻结配置，旧 v1 历史仍可查看。只有 v1 成功记录而无 v2 基线时，会明确要求完整发布建立整站基线。未配置路由的旧 Astro 合同仍拒绝多语言或多区域发布。

v2 先验证构建器签名能力，旧服务/无效签名在创建锁和记录前显示中文验证提示。构建请求包含精确冻结 `payload_base64`、generation 和全部 targets，ACK 与回调验证身份及摘要。成功回调必须证明全部目标已统一启用；未知或混合结果保持锁并显示待确认状态。v2 API 的页面路径歧义及多范围菜单、设置请求需要明确 scope，枚举可按冻结 target/scope 过滤。详细签名协议见 Core README。

## 恢复与验收

发布期间服务端阻止租户写入（423）；页面进度使用认证 GET 请求，不使用 Livewire POST 轮询。构建请求超时或未确认接受不会自动解锁。v2 操作员使用 `cms:publication-recover <deployment> --operator=... --reason=...`，服务端先查询签名状态；已启用则幂等完成原发布，未启用需持久撤销确认后才解锁，未知或不可达继续锁定。v1 操作员先确认外部任务确实停止，再使用 `php artisan cms:publication-recover --help` 所列参数执行恢复，填写原因；恢复会使旧回调失效，不能将恢复当作发布成功。

本地包测试使用各自 vendor 和内存 SQLite；Core 并发测试另需专用 MySQL 和 `CMS_TEST_MYSQL_CONFIG`。CI 包矩阵包含本包，MySQL 矩阵包含 Core 发布锁并发。真实浏览器、外部 Astro 适配与构建、生产回调和部署分别验收，本地测试通过不代表这些已完成。

## 发布状态界面

发布页沿用 Filament 原生控件，展示发布状态、变更统计、语言/区域筛选和默认收起的字段差异。全站影响属于变更的子集，不重复列出；不在详情中展示原始晋升快照或数据库连接信息。支持深色模式和窄屏，查看与发布权限保持独立。

安装或更新包后运行 `php artisan filament:assets` 发布包内样式，无需为本包额外配置 Tailwind 扫描或重新编译主题。

发布状态的待发布列表与新增、修改、删除、全站影响统计仅展示内容相关变更，不展示页面或菜单的组件、部件及字段结构定义。实际页面内容、菜单树与菜单设置、站点设置继续展示。结构定义仍参与冻结发布快照和历史审计，此规则只影响发布状态页面。

## 语言汇总与按需加载详情

发布状态默认汇总当前区域内全部已启用语言；选择具体语言后仅缩小显示范围。未启用整站路由时，按各语言独立基线比较，发布按钮旁会注明实际发布语言；该汇总不会自动改变旧版发布范围。整站模式保持全部区域、语言一起发布。部分语言缺少可靠基线时明确提示，不隐藏其他可比较语言。

首屏只输出名称、路径、语言、变更类型等摘要；点击“查看变更”才通过已认证、短期签名的 GET 请求加载对应条目的字段差异。请求重新检查租户、查看权限及访问账户；内容已变化或引用过期时提示刷新列表。请求不返回原始晋升快照和连接信息，并禁止缓存。历史仍读取冻结数据。

此优化延后字段差异格式化及详细 HTML 的生成和传输；识别哪些内容发生变化仍需读取和比较快照，不等于完全消除首屏比较开销。

## 开发与验证

```bash
composer install
composer test
composer format
```

测试使用隔离的内存 SQLite。格式检查不修改文件；需要格式化时运行 `composer format:fix`。`composer.lock`、依赖目录和测试缓存不纳入包源码。

## 许可证

本包为私有软件，使用和分发须获得授权，详见 [LICENSE.md](LICENSE.md)。

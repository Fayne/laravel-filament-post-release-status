# 发布状态与发布历史

适用 Laravel 13、PHP 8.4、Filament 5。独立 Composer 包 `fayne/laravel-filament-post-release-status`；依赖 Core，不强制安装 Page/Menu。宿主已配置本地 path repository，安装后自动发现服务提供者；在租户面板、或关闭多租户的单站管理面板注册 `Fayne\PostReleaseStatus\PostReleaseStatusPlugin::make()`，不要注册到没有租户上下文的平台面板。

安装后执行 `php artisan migrate`（区域内容库也需运行 Page 迁移），再运行 `php artisan cms:init` 同步权限。查看状态、历史、开始发布分别需要 `tenant.publication.view`、`tenant.publication.history`、`tenant.content.publish`。平台管理员修改租户品牌、语言、域名时同样受目标租户发布锁保护，不扩大平台权限。

初始化保留已有角色的自定义授权，不自动覆盖旧角色。升级现有宿主时，需由有权限的管理员在相应租户权限池及角色中按需启用新增的状态、历史查看权限；首次初始化的角色模板包含这些权限。本次仅验证独立测试库，没有对现有应用数据库执行迁移或权限初始化。

状态页与冻结服务共用一个单例 `ReleaseSnapshotRegistry`，未配置整站路由时比较当前可发布候选与同一站点、租户、区域、语言最后成功快照；启用 v2 时比较同一网站全部范围的最后成功清单。Page 使用当前启用页面，不使用旧发布版本或暂存版本；Menu 比较菜单属性及树。公共设置、组件/部件定义和字段顺序变化归为全局变化，内部时间戳不产生待发布变化，动态内容中同名字段保留。历史只读已保存快照；缺少旧快照时明确显示不可比较，需要完整发布建立基线。

## 构建与回调

配置 `CMS_FRONTEND_DRIVER=astro`、`CMS_BUILD_WEBHOOK_URL`、`CMS_DEPLOYMENT_WEBHOOK_SECRET`，以及 `filament-cms.deployment.build_api_token`。构建端使用 Bearer token，并通过该租户已启用域名访问下列接口：

- `/headless/releases/{release_id}/manifest`：冻结上下文、摘要与实体快照。
- `/headless/releases/{release_id}/pages`：保持 `data[].projection` 页面输出结构，支持 `?path=/...`；不返回内部原始晋升内容。
- `/headless/releases/{release_id}/menus`：保持 `data[].projection` 树结构，`data[].menu` 提供名称、slug、settings 等菜单属性。
- `/headless/releases/{release_id}/global-settings`：保持 `{data: 公共设置键值}` 结构。

构建必须仅从同一 release 的以上接口读取，不能混读实时 `/headless/global-settings`。外部 Astro 项目不在本工作区，必须将其设置请求改为带 release_id 的冻结地址，页面、菜单同理；不得在未完成该适配时宣称外部构建已完全使用冻结数据。Manifest 中 `global_page_definitions`、`global_menu_definitions` 是定义快照；渲染用页面/菜单投影已经冻结解析结果。

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

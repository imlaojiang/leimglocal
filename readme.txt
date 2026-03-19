=== Leimg Local - 图片本地化 ===

Contributors: laojiang
Tags: images, localize, paste, screenshot, gutenberg, classic editor
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

将文章中的外部图片本地化到媒体库，支持传统编辑器与古登堡，支持粘贴截图自动上传。

== Description ==

Leimg Local 是一款用于 WordPress 的图片本地化插件，适合内容采编、图文转载和日常站点运维场景。

* 一键将编辑器内容中的外链图片下载到媒体库，并替换为本地 URL。
* 同时支持传统编辑器（TinyMCE）与古登堡块编辑器（Gutenberg）。
* 支持截图/剪贴板图片粘贴后自动上传并插入内容（可在设置中关闭）。
* 支持基础开关配置，便于按站点需求启用或关闭功能。
* 符合 WordPress 开发规范，兼容 PHP 7.4+。

插件发布页： https://www.itbulu.com/leimglocal.html
公众号：老蒋朋友圈
开发主页： https://www.laojiang.me/

== Installation ==

1. 上传插件目录到 `/wp-content/plugins/leimglocal`，或在后台「插件 → 安装插件」上传 ZIP。
2. 在「插件」中启用「Leimg Local - 图片本地化」。
3. 可选：在「设置 → 图片本地化」中配置「粘贴时自动上传」等选项。

== Usage ==

1. 打开一篇文章（新建或编辑）。
2. 点击编辑器中的「本地化图片」按钮。
3. 等待插件处理外链图片并替换为本地地址。
4. 点击「更新 / 发布」保存修改。

如需启用截图粘贴自动上传，请在「设置 → 图片本地化」中开启相关选项。

== Frequently Asked Questions ==

= 本地化会修改已发布文章吗？ =

只有在你点击「本地化图片」后，当前编辑器中的内容会被替换。需要保存文章后才会写入数据库。

= 粘贴截图后没有自动上传？ =

请确认「设置 → 图片本地化」中已勾选「粘贴时自动上传」，且当前用户有上传权限。

= 为什么部分图片下载失败？ =

可能是外链图片地址不可访问、存在防盗链策略、服务器网络限制，或图片格式不被支持。建议先确认图片 URL 可公开访问。

== Changelog ==

= 1.0.0 =
* 首次发布。
* 支持一键本地化外链图片到媒体库。
* 支持传统编辑器与古登堡。
* 支持粘贴截图自动上传。
* 提供后台设置页。

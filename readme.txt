=== Hacklog Remote Attachment ===
Contributors: ihacklog
Donate link: http://ihacklog.com/about
Tags: attachment,manager,admin,image,thumbnail,ftp,remote
Requires at least: 3.2.1
Tested up to: 3.2.1
Stable tag: 1.1.1

Adds remote attachments support for your WordPress blog.

== Description ==
Features: Adds remote attachments support for your WordPress blog.
use this plugin, you can upload any files to remote ftp servers(be aware that your FTP server must has Apache or other HTTP server daemon) in WordPress.
*Support both single user site AND multisite.
* support upload files to remote FTP server.
* support delete files on remote FTP server.
* works just like the files are saved on your local server-_-.
* you can move remote server files to local server if you'd like to do so.
* you can move local server files to remote server if you'd like to do so.

For MORE information,please visit the [plugin homepage](http://ihacklog.com/?p=5001 "plugin homepage") for any questions about the plugin.
[installation guide](http://ihacklog.com/?p=4993 "installation guide") 
version 1.1.0 added compatibility with watermark plugins
Simplified Chinese(zh_CN) language po and mo files. By [荒野无灯](http://ihacklog.com "荒野无灯weblog") @  荒野无灯weblog 

目前有中、英文两种语言。
更多信息请访问[插件主页](http://ihacklog.com/?p=5001 "plugin homepage") 获取关于插件的更多信息，使用技巧等.
简体中文语言包(zh_CN) po 和 mo 文件由 [荒野无灯](http://ihacklog.com "荒野无灯weblog") @  荒野无灯weblog 提供。
1.0.2 增加自动创建远程目录功能。解决在某些FTP服务器出现“在远程服务器创建目录失败”的问题。
[安装指导](http://ihacklog.com/?p=4993 "安装指导") 
1.1.0 增加与水印插件的兼容性，使上传到远程服务器的图片同样可以加上水印

== Installation ==

1. Upload the whole fold `hacklog-remote-attachment` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin via `Settings` -> `Hacklog Remote Attachment` menu and it's OK now,you can upload attachments(iamges,videos,audio,etc.) to the remote FTP server.


== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png


  



== Frequently Asked Questions ==
[FAQ](http://ihacklog.com/?p=5001 "FAQ") 


== Upgrade Notice ==




== Changelog ==

= 1.0.0 =
released the first version.

= 1.0.1 =
fixed a small bug(cron delete).

= 1.0.2 =
added: remote direcotry auto-make functions.If the remote dir does not exists,the plugin will try to make it.
added: put index.html to disable directory browsing

= 1.1.0 =
handle image file in the later(in HOOK wp_generate_attachment_metadata) for compatibility with watermark plugins
removed the scheduled hook,now delete the orig image file just after uploaded.

= 1.1.1 =
fixed a bug,when uploading a non-image file the image upload handler will handle the non-image file.
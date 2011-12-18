=== Hacklog Remote Attachment ===
Contributors: ihacklog
Donate link: http://ihacklog.com/donate
Tags: attachment,manager,admin,images,thumbnail,ftp,remote
Requires at least: 3.3
Tested up to: 3.3
Stable tag: 1.2.2

Adds remote attachments support for your WordPress blog.

== Description ==
Features: Adds remote attachments support for your WordPress blog.

use this plugin, you can upload any files to remote ftp servers(be aware that your FTP server must has Apache or other HTTP server daemon) in WordPress.

* Support both single user site AND multisite.
* support upload files to remote FTP server.
* support delete files on remote FTP server.
* works just like the files are saved on your local server-_-.
* with this plugin,you can move all your local server files to remote server.
* after you've uninstall this plugin,you can move remote server files to local server if you'd like to do so.

For MORE information,please visit the [plugin homepage](http://ihacklog.com/?p=5001 "plugin homepage") for any questions about the plugin.

[installation guide](http://ihacklog.com/?p=4993 "installation guide") 

* version 1.1.0 added compatibility with watermark plugins
* version 1.2.0 added duplicated file checking,so that the existed remote files will not be overwrote.
* version 1.2.1 fixed the bug when uploading new theme or plugin this plugin may cause it to fail.

* 1.0.2 增加自动创建远程目录功能。解决在某些FTP服务器出现“在远程服务器创建目录失败”的问题。
* 1.1.0 增加与水印插件的兼容性，使上传到远程服务器的图片同样可以加上水印
* 1.2.0 增加重复文件检测，避免同名文件被覆盖。更新和完善了帮助信息。
* 1.2.1 修正在后台上传主题或插件时的bug.

更多信息请访问[插件主页](http://ihacklog.com/?p=5001 "plugin homepage") 获取关于插件的更多信息，使用技巧等.
[安装指导](http://ihacklog.com/?p=4993 "安装指导") 

== Installation ==

1. Upload the whole fold `hacklog-remote-attachment` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin via `Settings` -> `Hacklog Remote Attachment` menu and it's OK now,you can upload attachments(iamges,videos,audio,etc.) to the remote FTP server.
4. If your have moved all your local server files to remote server,then you can `UPDATE THE DATABASE` so that all your attachments URLs will be OK.
You can visit [plugin homepage](http://ihacklog.com/?p=5001 "plugin homepage") for detailed installation guide.

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png


  



== Frequently Asked Questions ==
[FAQ](http://ihacklog.com/?p=5001 "FAQ") 


== Upgrade Notice ==




== Changelog ==
= 1.2.2 =
* fixed: DO NOT delete the options when the plugin is deactivating.

= 1.2.1 =
* fixed the bug when uploading new theme or plugin this plugin may cause it to fail.

= 1.2.0 =
* fixed: use new WP_Screen API to display help information
* improved: check FTP connection in the `Plugins` page
* fixed: added duplicated file checking,so that the existed remote files will not be overwrote.

= 1.1.6 =
* fixed: changed upload file permission to 0644 ,changed created directory permission to 0755
* fixed: get the right subdir when the post publish date was different from the media upload date.

= 1.1.5 =
* fixed: when no thumbnails,do not run foreach in function upload_images

= 1.1.4 =
* changed remote path to FTP remote path and added HTTP remote path to support the mapping relationship between FTP remote path and HTTP remote path
* fixed:when connection failed,delete the file on local server.
 
= 1.1.3 =
* fixed a bug(when remote path is root directory of FTP server)
* added FTP connection timeout option
* added FTP connection error message returning

= 1.1.1 =
* fixed a bug,when uploading a non-image file the image upload handler will handle the non-image file. (bug bring in version 1.1.0)
= 1.1.2 =
* fixed a bug in public static function upload_images(bug bring in version 1.1.1)

= 1.1.0 =
* handle image file in the later(in HOOK wp_generate_attachment_metadata) for compatibility with watermark plugins
* removed the scheduled hook,now delete the orig image file just after uploaded.

= 1.0.2 =
* added: remote direcotry auto-make functions.If the remote dir does not exists,the plugin will try to make it.
* added: put index.html to disable directory browsing

= 1.0.1 =
* fixed a small bug(cron delete).


= 1.0.0 =
* released the first version.












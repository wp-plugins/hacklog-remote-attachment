<?php

/**
 * $Id$
 * $Revision$
 * $Date$
 * @package Hacklog Remote Attachment
 * @encoding UTF-8
 * @author 荒野无灯 <HuangYeWuDeng>
 * @link http://ihacklog.com
 * @copyright Copyright (C) 2011 荒野无灯
 * @license http://www.gnu.org/licenses/
 */
class hacklogra
{
	const textdomain = 'hacklog-remote-attachment';
	const plugin_name = 'Hacklog Remote Attachment';
	const opt_space = 'hacklogra_remote_filesize';
	const opt_primary = 'hacklogra_options';
	const version = '1.1.6';
	private static $img_ext = array('jpg', 'jpeg', 'png', 'gif', 'bmp');
	private static $ftp_user = 'admin';
	private static $ftp_pwd = 'admin';
	private static $ftp_server = '172.30.16.31';
	private static $ftp_port = 21;
	private static $ftp_timeout = 30;
	private static $subdir = '';
	private static $ftp_remote_path = 'wp-files';
	private static $http_remote_path = 'wp-files';
	private static $remote_url = '';
	private static $remote_baseurl = '';
	private static $local_basepath = '';
	private static $local_path = '';
	private static $local_url = '';
	private static $local_baseurl = '';
	private static $fs = null;

	public function __construct()
	{
		self::init();
		add_filter('wp_handle_upload', array(__CLASS__, 'upload_and_send'));
		add_filter('media_send_to_editor', array(__CLASS__, 'replace_attachurl'), -999);
		add_filter('attachment_link', array(__CLASS__, 'replace_baseurl'), -999);
		//生成缩略图后立即上传生成的文件并删除本地文件,this must after watermark generate
		add_filter('wp_generate_attachment_metadata', array(__CLASS__, 'upload_images'), 999);
		//删除远程附件
		add_action('wp_delete_file', array(__CLASS__, 'delete_remote_file'));
		//menu
		add_action('admin_menu', array(__CLASS__, 'plugin_menu'));
		//should load before 'admin_menu' hook ... so,use init hook  
		add_action('init', array(__CLASS__, 'load_textdomain'));
		add_action('admin_init', array(__CLASS__, 'add_my_contextual_help'));
		//frontend filter,filter on image only
		add_filter('wp_get_attachment_url', array(__CLASS__, 'replace_baseurl'), -999);
	}

############################## PRIVATE FUNCTIONS ##############################################

	private static function update_options()
	{
		$value = self::get_default_opts();
		$keys = array_keys($value);
		foreach ($keys as $key)
		{
			if (!empty($_POST[$key]))
			{
				$value[$key] = addslashes($_POST[$key]);
			}
		}
		$value['remote_baseurl'] = rtrim($value['remote_baseurl'], '/');
		$value['ftp_remote_path'] = rtrim($value['ftp_remote_path'], '/');
		$value['http_remote_path'] = rtrim($value['http_remote_path'], '/');
		if (update_option(self::opt_primary, $value))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * get file extension
	 * @static
	 * @param $path
	 * @return mixed
	 */
	private static function get_ext($path)
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}

	/**
	 * to see if a file is an image file.
	 * @static
	 * @param $path
	 * @return bool
	 */
	private static function is_image_file($path)
	{
		return in_array(self::get_ext($path), self::$img_ext);
	}

	/**
	 * get the default options
	 * @static
	 * @return array
	 */
	private static function get_default_opts()
	{
		return array(
			'ftp_user' => self::$ftp_user,
			'ftp_pwd' => self::$ftp_pwd,
			'ftp_server' => self::$ftp_server,
			'ftp_port' => self::$ftp_port,
			'ftp_timeout' => self::$ftp_timeout,
			'ftp_remote_path' => self::$ftp_remote_path,
			'http_remote_path' => self::$http_remote_path,
			'remote_baseurl' => self::$remote_baseurl,
		);
	}

	/**
	 * increase the filesize,keep the filesize tracked.
	 * @static
	 * @param $file
	 * @return void
	 */
	private static function update_filesize_used($file)
	{
		if (file_exists($file))
		{
			$filesize = filesize($file);
			$previous_value = get_option(self::opt_space);
			$to_save = $previous_value + $filesize;
			update_option(self::opt_space, $to_save);
		}
	}

	/**
	 * decrease the filesize when a remote file is deleted.
	 * @static
	 * @param $fs
	 * @param $file
	 * @return void
	 */
	private static function decrease_filesize_used($fs, $file)
	{
		if ($fs->exists($file))
		{
			$filesize = $fs->size($file);
			$previous_value = get_option(self::opt_space);
			$to_save = $previous_value - $filesize;
			update_option(self::opt_space, $to_save);
		}
	}

	/**
	 * like  wp_handle_upload_error in file.php under wp-admin/includes
	 * @param type $file
	 * @param type $message
	 * @return type 
	 */
	function handle_upload_error($message)
	{
		return array('error' => $message);
	}

	/**
	 * report upload error
	 * @return type 
	 */
	private static function raise_upload_error()
	{
		return call_user_func(array(__CLASS__, 'handle_upload_error'), sprintf('%s:' . __('upload file to remote server failed!', self::textdomain), self::plugin_name));
	}

	/**
	 * report FTP connection error
	 * @return type 
	 */
	private static function raise_connection_error()
	{
		return call_user_func(array(__CLASS__, 'handle_upload_error'), sprintf('%s:' . self::$fs->errors->get_error_message(), self::plugin_name));
	}

############################## PUBLIC FUNCTIONS ##############################################
	/**
	 * init
	 * @static
	 * @return void
	 */

	public static function init()
	{
		register_activation_hook(HACKLOG_RA_LOADER, array(__CLASS__, 'my_activation'));
		register_deactivation_hook(HACKLOG_RA_LOADER, array(__CLASS__, 'my_deactivation'));
		$opts = get_option(self::opt_primary);
		self::$ftp_user = $opts['ftp_user'];
		self::$ftp_pwd = $opts['ftp_pwd'];
		self::$ftp_server = $opts['ftp_server'];
		self::$ftp_port = $opts['ftp_port'];
		self::$ftp_timeout = $opts['ftp_timeout'] > 0 ? $opts['ftp_timeout'] : 30;

		$opts['ftp_remote_path'] = rtrim($opts['ftp_remote_path'], '/');
		$opts['http_remote_path'] = rtrim($opts['http_remote_path'], '/');
		$opts['remote_baseurl'] = rtrim($opts['remote_baseurl'], '/');
		$upload_dir = wp_upload_dir();
		//be aware of / in the end
		self::$local_basepath = $upload_dir['basedir'];
		self::$local_path = $upload_dir['path'];
		self::$local_baseurl = $upload_dir['baseurl'];
		self::$local_url = $upload_dir['url'];
		self::$subdir = $upload_dir['subdir'];
		//if the post publish date was different from the media upload date,the time should take from the database.
		if (get_option('uploads_use_yearmonth_folders') && isset( $_REQUEST['post_id'] ))
		{
			$post_id = (int) $_REQUEST['post_id'];
			if ($post = get_post($post_id))
			{
				if (substr($post->post_date, 0, 4) > 0)
				{
					$time = $post->post_date;
					$y = substr($time, 0, 4);
					$m = substr($time, 5, 2);
					$subdir = "/$y/$m";
					self::$subdir = $subdir;
				}
			}
		}
		//后面不带 /
		self::$ftp_remote_path = $opts['ftp_remote_path'];
		self::$http_remote_path = $opts['http_remote_path'];
		//此baseurl与options里面的不同！
		self::$remote_baseurl = '.' == self::$http_remote_path ? $opts['remote_baseurl'] :
				$opts['remote_baseurl'] . '/' . self::$http_remote_path;
		self::$remote_url = self::$remote_baseurl . self::$subdir;
	}

	/**
	 * do the stuff once the plugin is installed
	 * @static
	 * @return void
	 */
	public static function my_activation()
	{
		add_option(self::opt_space, 0);
		$opt_primary = self::get_default_opts();
		add_option(self::opt_primary, $opt_primary);
	}

	/**
	 * do cleaning stuff when the plugin is deactivated.
	 * @static
	 * @return void
	 */
	public static function my_deactivation()
	{
		//delete_option(self::opt_space);
		delete_option(self::opt_primary);
	}

	private static function get_opt($key, $defaut='')
	{
		$opts = get_option(self::opt_primary);
		return isset($opts[$key]) ? $opts[$key] : $defaut;
	}

	/**
	 * humanize file size.
	 * @static
	 * @param $bytes
	 * @return string
	 */
	public static function human_size($bytes)
	{
		$types = array('B', 'KB', 'MB', 'GB', 'TB');
		for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++)
			;
		return (round($bytes, 2) . " " . $types[$i]);
	}

	/**
	 * load the textdomain on init
	 * @static
	 * @return void
	 */
	public static function load_textdomain()
	{
		load_plugin_textdomain('hacklog-remote-attachment', false, dirname(plugin_basename(HACKLOG_RA_LOADER)) . '/languages/');
	}

	/**
	 * set up ftp connection
	 * @static
	 * @param $args
	 * @return bool
	 */
	public static function setup_ftp($args)
	{
		require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
		//ftpext or ftpsockets
		$method = 'ftpext';
		if (!class_exists("WP_Filesystem_ftpext"))
		{
			$abstraction_file = ABSPATH . 'wp-admin/includes/class-wp-filesystem-' . $method . '.php';
			if (!file_exists($abstraction_file))
			{
				return false;
			}
			require_once($abstraction_file);
		}
		$method = "WP_Filesystem_$method";
		self::$fs = new $method($args);
		//Define the timeouts for the connections. Only available after the construct is called to allow for per-transport overriding of the default.
		if (!defined('FS_CONNECT_TIMEOUT'))
			define('FS_CONNECT_TIMEOUT', self::$ftp_timeout);
		if (!defined('FS_TIMEOUT'))
			define('FS_TIMEOUT', 30);

		if (is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code())
			return false;

		if (!self::$fs->connect())
			return false; //There was an erorr connecting to the server.


			
// Set the permission constants if not already set.
		if (!defined('FS_CHMOD_DIR'))
			define('FS_CHMOD_DIR', 0755);
		if (!defined('FS_CHMOD_FILE'))
			define('FS_CHMOD_FILE', 0644);

		return true;
	}

	/**
	 * do connecting to server.DO NOT call this on any page that not needed!
	 * if can not connect to remote server successfully,the plugin will refuse to work
	 * @static
	 * @return void
	 */
	public static function connect_remote_server()
	{
		//if object not inited.
		if (null == self::$fs)
		{
			$credentials = array(
				'hostname' => self::$ftp_server,
				'port' => self::$ftp_port,
				'username' => self::$ftp_user,
				'password' => self::$ftp_pwd,
				'ssl' => FALSE,
			);
			if (!self::setup_ftp($credentials))
			{
				return FALSE;
			}
		}
		return self::$fs;
	}

	/**
	 * the hook is in function get_attachment_link()
	 * @static
	 * @param $html
	 * @return mixed
	 */
	public static function replace_attachurl($html)
	{
		$html = str_replace(self::$local_url, self::$remote_url, $html);
		return $html;
	}

	/**
	 * the hook is in function media_send_to_editor
	 * @static
	 * @param $html
	 * @return mixed
	 */
	public static function replace_baseurl($html)
	{
		$html = str_replace(self::$local_baseurl, self::$remote_baseurl, $html);
		return $html;
	}

	/**
	 * handle orig image file and other files.
	 * @static
	 * @param $file
	 * @return array|mixed
	 */
	public static function upload_and_send($file)
	{
		if (!self::connect_remote_server())
		{
			//failed ,delete the orig file
			unlink($file['file']);
			return self::raise_connection_error();
		}
		$upload_error_handler = 'wp_handle_upload_error';

		$local_basename = basename($file['file']);
		$localfile = $file['file'];
		$remotefile = self::$ftp_remote_path . self::$subdir . '/' . $local_basename;
		$remote_subdir = dirname($remotefile);
		$remote_subdir = str_replace('\\', '/', $remote_subdir);
		if (!self::$fs->is_dir($remote_subdir))
		{
			//make sure the dir on FTP server is exists.
			$subdir = explode('/', $remote_subdir);
			$i = 0;
			$dir_needs = '';
			while (isset($subdir[$i]) && !empty($subdir[$i])) {
				$dir_needs .= $subdir[$i] . '/';
				!self::$fs->is_dir($dir_needs) && self::$fs->mkdir($dir_needs, 0755);
				//disable directory browser
				!self::$fs->is_file($dir_needs . 'index.html') && self::$fs->put_contents($dir_needs . 'index.html', 'Silence is golden.');
				++$i;
			}
			if (!self::$fs->is_dir($remote_subdir))
			{
				return call_user_func($upload_error_handler, &$file, sprintf('%s:' . __('failed to make dir on remote server!Please check your FTP permissions.', self::textdomain), self::plugin_name));
			}
		}
		//如果是图片，此处不处理，因为要与水印插件兼容的原因　
		if (self::is_image_file($file['file']))
		{
			return $file;
		}
		$content = file_get_contents($localfile);
		//        return array('error'=> $remotefile);
		if (!self::$fs->put_contents($remotefile, $content, 0644))
		{
			return call_user_func($upload_error_handler, &$file, sprintf('%s:' . __('upload file to remote server failed!', self::textdomain), self::plugin_name));
		}
		unset($content);
		//uploaded successfully
		self::update_filesize_used($localfile);
		//delete the local file
		unlink($file['file']);
		$file['url'] = str_replace(self::$local_url, self::$remote_url, $file['url']);
		return $file;
	}

	/**
	 * 上传缩略图到远程服务器并删除本地服务器文件
	 * @static
	 * @param $metadata from function wp_generate_attachment_metadata
	 * @return array
	 */
	public static function upload_images($metadata)
	{
		if (!self::is_image_file($metadata['file']))
		{
			return $metadata;
		}

		if (!self::connect_remote_server())
		{
			return self::raise_connection_error();
		}

		//deal with fullsize image file
		if (!self::upload_file($metadata['file']))
		{
			return self::raise_upload_error();
		}

		if (isset($metadata['sizes']) && count($metadata['sizes']) > 0)
		{
			foreach ($metadata['sizes'] as $image_size => $image_item)
			{
				$relative_filepath = dirname($metadata['file']) . DIRECTORY_SEPARATOR . $metadata['sizes'][$image_size]['file'];
				if (!self::upload_file($relative_filepath))
				{
					return self::raise_upload_error();
				}
			}
		}
		return $metadata;
	}

	/**
	 * upload single file to remote  FTP  server, used by upload_images
	 * @param type $relative_path the path relative to upload basedir
	 * @return type 
	 */
	private static function upload_file($relative_path)
	{
		$local_filepath = self::$local_basepath . DIRECTORY_SEPARATOR . $relative_path;
		$local_basename = basename($local_filepath);
		$remotefile = self::$ftp_remote_path . self::$subdir . '/' . $local_basename;
		$file_data = file_get_contents($local_filepath);
		if (!self::$fs->put_contents($remotefile, $file_data, 0644))
		{
			return FALSE;
		}
		else
		{
			//更新占用空间
			self::update_filesize_used($local_filepath);
			unlink($local_filepath);
			unset($file_data);
			return TRUE;
		}
	}

	/**
	 * 删除远程服务器上的单个文件
	 * @static
	 * @param $file
	 * @return void
	 */
	public static function delete_remote_file($file)
	{
		$file = str_replace(self::$local_basepath, self::$http_remote_path, $file);
		if (strpos($file, self::$http_remote_path) !== 0)
		{
			$file = self::$ftp_remote_path . '/' . $file;
		}

		self::connect_remote_server();
		self::decrease_filesize_used(self::$fs, $file);
		self::$fs->delete($file, false, 'f');
	}

	/**
	 * @see http://codex.wordpress.org/Function_Reference/add_contextual_help
	 * method to find current_screen:
	 * function check_current_screen() {
	  if( !is_admin() ) return;
	  global $current_screen;
	  print_r( $current_screen );
	  }
	  add_action( 'admin_notices', 'check_current_screen' );
	 * @return void
	 */
	public function add_my_contextual_help()
	{
		$current_screen = 'settings_page_' . plugin_basename(HACKLOG_RA_LOADER);
		$text = '<p><strong>' . __('Explanation of some Options:') . '</strong></p>' .
				'<p>' . __('the <strong>Remote base URL</strong> is the URL to your Ftp root path.') . '</p>' .
				'<p>' . __('the <strong>Remote path</strong> is the directory which you save attachment under it and it is relative to your Ftp root path.') . '</p>' .
				'<p><strong>' . __('For more information:') . '</strong></p>' .
				'<p>' . __('<a href="http://ihacklog.com/?p=5001" target="_blank">Plugin Home Page</a>') . '</p>';
		add_contextual_help($current_screen, $text);
	}

	/**
	 * add menu page
	 * @see http://codex.wordpress.org/Function_Reference/add_options_page
	 * @static
	 * @return void
	 */
	public static function plugin_menu()
	{
		add_options_page(__('Hacklog Remote Attachment Options', self::textdomain), __('Remote Attachment', self::textdomain), 'manage_options', HACKLOG_RA_LOADER, array(__CLASS__, 'plugin_options')
		);
	}

	public static function show_message($message, $type = 'e')
	{
		if (empty($message))
			return;
		$font_color = 'e' == $type ? '#FF0000' : '#4e9a06';
		$html = '<!-- Last Action --><div id="message" class="updated fade"><p>';
		$html .= "<span style='color:{$font_color};'>" . $message . '</span><br />';
		$html .= '</p></div>';
		echo $html;
	}

	/**
	 * option page
	 * @static
	 * @return void
	 */
	public static function plugin_options()
	{
		$msg = '';
		$msg_type = 'm';

		//update options
		if (isset($_POST['submit']))
		{
			if (self::update_options())
			{
				$msg = __('Options updated.', self::textdomain);
				$msg_type = 'm';
			}
			else
			{
				$msg = __('Nothing changed.', self::textdomain);
				$msg_type = 'e';
			}
			$credentials = array(
				'hostname' => $_POST['ftp_server'],
				'port' => $_POST['ftp_port'],
				'username' => $_POST['ftp_user'],
				'password' => $_POST['ftp_pwd'],
				'ssl' => FALSE,
			);
			if (self::setup_ftp($credentials))
			{
				$msg .= '<br />' . __('Connected successfully.', self::textdomain);
			}
			else
			{
				$msg .= '<br />' . __('Failed to connect to remote server!', self::textdomain);
			}
		}

		//tools
		if (isset($_GET['hacklog_do']))
		{
			global $wpdb;
			switch ($_GET['hacklog_do'])
			{
				case 'replace_old_post_attach_url':
					$orig_url = self::$local_baseurl;
					$new_url = self::$remote_baseurl;
					$sql = "UPDATE $wpdb->posts set post_content=replace(post_content,'$orig_url','$new_url')";
					break;
				case 'recovery_post_attach_url':
					$orig_url = self::$remote_baseurl;
					$new_url = self::$local_baseurl;
					$sql = "UPDATE $wpdb->posts set post_content=replace(post_content,'$orig_url','$new_url')";
					break;
			}
			if (($num_rows = $wpdb->query($sql)) > 0)
			{
				$msg = sprintf('%d ' . __('posts has been updated.', self::textdomain), $num_rows);
				$msg_type = 'm';
			}
			else
			{
				$msg = __('no posts been updated.', self::textdomain);
				$msg_type = 'e';
			}
		}
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2> <?php _e('Hacklog Remote Attachment Options', self::textdomain) ?></h2>
			<?php
			self::show_message($msg, $msg_type);
			?>
			<form name="form1" method="post"
				  action="<?php echo admin_url('options-general.php?page=' . plugin_basename(HACKLOG_RA_LOADER)); ?>">
				<table width="100%" cellpadding="5" class="form-table">
					<tr valign="top">
						<th scope="row"><label for="ftp_server"><?php _e('Ftp server', self::textdomain) ?>:</label></th>
						<td>
							<input name="ftp_server" type="text" class="regular-text" size="100" id="ftp_server"
								   value="<?php echo self::get_opt('ftp_server'); ?>"/>
							<span class="description"><?php _e('the IP or domain name of remote file server.', self::textdomain) ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="ftp_port"><?php _e('Ftp server port', self::textdomain) ?>:</label></th>
						<td>
							<input name="ftp_port" type="text" class="small-text" size="60" id="ftp_port"
								   value="<?php echo self::get_opt('ftp_port'); ?>"/>
							<span class="description"><?php _e('the listenning port of remote FTP server.', self::textdomain) ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="ftp_user"><?php _e('Ftp username', self::textdomain) ?>:</label></th>
						<td>
							<input name="ftp_user" type="text" class="regular-text" size="60" id="ftp_user"
								   value="<?php echo self::get_opt('ftp_user'); ?>"/>
							<span class="description"><?php _e('the Ftp username.', self::textdomain) ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="ftp_pwd"><?php _e('Ftp password', self::textdomain) ?>:</label></th>
						<td>
							<input name="ftp_pwd" type="password" class="regular-text" size="60" id="ftp_pwd"
								   value="<?php echo self::get_opt('ftp_pwd'); ?>"/>
							<span class="description"><?php _e('the password.', self::textdomain) ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="ftp_timeout"><?php _e('Ftp timeout', self::textdomain) ?>:</label></th>
						<td>
							<input name="ftp_timeout" type="text" class="small-text" size="30" id="ftp_timeout"
								   value="<?php echo self::get_opt('ftp_timeout'); ?>"/>
							<span class="description"><?php _e('FTP connection timeout.', self::textdomain) ?></span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="remote_baseurl"><?php _e('Remote base URL', self::textdomain) ?>
								:</label></th>
						<td>
							<input name="remote_baseurl" type="text" class="regular-text" size="60" id="remote_baseurl"
								   value="<?php echo self::get_opt('remote_baseurl'); ?>"/>
							<span class="description"><?php _e('Remote base URL,the URL to your Ftp root path.for example: <strong>http://www.your-domain.com</strong>.', self::textdomain) ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="ftp_remote_path"><?php _e('FTP Remote path', self::textdomain) ?>:</label></th>
						<td>
							<input name="ftp_remote_path" type="text" class="regular-text" size="60" id="ftp_remote_path"
								   value="<?php echo self::get_opt('ftp_remote_path'); ?>"/>
							<span class="description"><?php _e('the relative path to your FTP main directory.Use "<strong>.</strong>" for FTP main(root) directory.You can use sub-directory Like <strong>wp-files</strong>', self::textdomain) ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="http_remote_path"><?php _e('HTTP Remote path', self::textdomain) ?>:</label></th>
						<td>
							<input name="http_remote_path" type="text" class="regular-text" size="60" id="http_remote_path"
								   value="<?php echo self::get_opt('http_remote_path'); ?>"/>
							<span class="description"><?php _e('the relative path to your HTTP main directory.Use "<strong>.</strong>" for HTTP main(root) directory.You can use sub-directory Like <strong>wp-files</strong>', self::textdomain) ?></span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" name="submit"
						   value="<?php _e('Save Options', self::textdomain) ?> &raquo;"/>
				</p>
			</form>
		</div>
		<div class="wrap">
			<hr/>
			<h2> <?php _e('Hacklog Remote Attachment Status', self::textdomain) ?></h2>

			<p style="color:#999999;font-size:14px;">
		<?php _e('Space used on remote server:', self::textdomain); ?><?php echo self::human_size(get_option(hacklogra::opt_space)); ?>
			</p>
			<hr/>
			<h2>Tools</h2>

			<p style="color:#f00;font-size:14px;"><strong><?php _e('warning:', self::textdomain); ?></strong>
		<?php _e("if you haven't moved all your attachments OR dont't know what below means,please <strong>DO NOT</strong> click the link below!", self::textdomain); ?>
			</p>

			<h3><?php _e('Move', self::textdomain); ?></h3>

			<p style="color:#4e9a06;font-size:14px;">
		<?php _e('if you have moved all your attachments to the remote server,then you can click', self::textdomain); ?>
				<a onclick="return confirm('<?php _e('Are your sure to do this?Make sure you have backuped your database tables.', self::textdomain); ?>');"
				   href="<?php echo admin_url('options-general.php?page=' . plugin_basename(HACKLOG_RA_LOADER)); ?>&hacklog_do=replace_old_post_attach_url"><strong><?php _e('here', self::textdomain); ?></strong></a><?php _e(' to update the database.', self::textdomain); ?>
			</p>

			<h3><?php _e('Recovery', self::textdomain); ?></h3>

			<p style="color:#4e9a06;font-size:14px;">
		<?php _e('if you have moved all your attachments from the remote server to local server,then you can click', self::textdomain); ?>
				<a onclick="return confirm('<?php _e('Are your sure to do this?Make sure you have backuped your database tables.', self::textdomain); ?>');"
				   href="<?php echo admin_url('options-general.php?page=' . plugin_basename(HACKLOG_RA_LOADER)); ?>&hacklog_do=recovery_post_attach_url"><strong><?php _e('here', self::textdomain); ?></strong></a><?php _e(' to update the database.', self::textdomain); ?>
			</p>
		</div>
		<?php
	}

}

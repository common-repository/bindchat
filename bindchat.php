<?php
/**
 * @package Bindchat
 */

/*
Plugin Name: Bindchat
Plugin URI: http://bindchat.com/plugins/wordpress/?return=true
Description: 启用必聊网在线客服插件: 1) 点击插件列表中的Bindchat插件下方的 "启用" 链接按钮来启用插件， 2) 注册必聊网帐户<a href="http://bindchat.com/plugins/wordpress/?return=true">申请站点ID</a>， 3) 前往“Bindchat配置”页面，输入并保存站点ID， 4)拖放“外观”里面的“必聊网在线客服”小工具到您需要的位置区域。
Version: 2.1.0
Author: Bindchat 
Author URI: http://bindchat.com/plugins/wordpress/
License: GPLv2 or later
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define('BINDCHAT_VERSION', '1.1.0');



if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/admin.php';
}

function bindchat_init() {
	global $bindchat_api_host, $bindchat_api_port;

    $bindchat_api_host = 'bindchat.com';

	$bindchat_api_port = 80;
}
add_action('init', 'bindchat_init');
add_action('get_footer', 'bindchat_footer');

function bindchat_http_post($request, $host, $path, $port = 80, $ip=null) {
	$bindchat_ua = 'Bindchat/' . constant( 'BINDCHAT_VERSION' );
	$content_length = strlen( $request );
	$http_host = $host;
	
	if ( function_exists( 'wp_remote_post' ) ) {
		$http_args = array(
			'body'			=> $request,
			'headers'		=> array(
				'Content-Type'	=> 'application/x-www-form-urlencoded; ' .
									'charset=' . get_option( 'blog_charset' ),
				'Host'			=> $host,
				'User-Agent'	=> $bindchat_ua
			),
			'httpversion'	=> '1.0',
			'timeout'		=> 15
		);
		$bc_url = "http://{$http_host}{$path}";
		$response = wp_remote_post( $bc_url, $http_args );
		if ( is_wp_error( $response ) )
			return '';

		return array( $response['headers'], $response['body'] );
	} else {
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= 'Content-Type: application/x-www-form-urlencoded; charset=' . get_option('blog_charset') . "\r\n";
		$http_request .= "Content-Length: {$content_length}\r\n";
		$http_request .= "User-Agent: {$bindchat_ua}\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		
		$response = '';
		if( false != ( $fs = @fsockopen( $http_host, $port, $errno, $errstr, 10 ) ) ) {
			fwrite( $fs, $http_request );

			while ( !feof( $fs ) )
				$response .= fgets( $fs, 1160 ); // One TCP-IP packet
			fclose( $fs );
			$response = explode( "\r\n\r\n", $response, 2 );
		}
		return $response;
	}
}

function bindchat_check_site_id_status( $siteid, $ip = null ) {
	global $bindchat_api_host, $bindchat_api_port;
	$home = urlencode( get_option('home') );
	$response = bindchat_http_post("url=$home", $bindchat_api_host, "/plugins/wordpress/verify/{$siteid}", $bindchat_api_port, $ip);
	return $response;
}

function bindchat_verify_site_id( $siteid, $ip = null ) {
	$response = bindchat_check_site_id_status( $siteid, $ip );
	if ( !is_array($response) || !isset($response[1]) || $response[1] != 'valid' && $response[1] != 'invalid' )
		return 'failed';
	return $response[1];
}

function bindchat_footer() {
	bindchat_widget_script();
}

    function bindchat_widget_script() {
        $siteid = get_option('bindchat_site_id');
	    if (!empty($siteid)) {
?>
            <script>
            (function (w, d, e, g, r, a, m) {
             w['BindChatObject'] = r;
             w[r] = w[r] || function () {
             (w[r].q = w[r].q || []).push(arguments)
             }, w[r].t = 1 * new Date();
             a = d.createElement(e),
             m = d.getElementsByTagName(e)[0];
             a.async = 1;
             a.src = g;
             m.parentNode.insertBefore(a, m)
             })(window, document, 'script', '//www.bindchat.com/api/js/all.js', 'bindchat');

        bindchat('create', '<?php echo $siteid;?>', '<?php echo $_SERVER["HTTP_HOST"];?>');
        </script>
            <noscript>
            <a href="http://www.bindchat.com/sites/contact/<?php echo $siteid;?>" target="_blank">Feedback?</a> 
            powered by <a href="http://www.bindchat.com/welcome">Bindchat</a>
            </noscript>
<?php
        }
    }
?>

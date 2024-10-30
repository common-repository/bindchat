<?php
add_action( 'admin_menu', 'bindchat_admin_menu' );
add_action( 'admin_footer', 'bindchat_admin_footer' );


bindchat_admin_warnings();

function bindchat_admin_warnings() {

	if ( !get_option('bindchat_site_id')) {
		function bindchat_warning() {
			echo "
			<div id='bindchat-warning' class='updated fade'><p><strong>".__('Bindchat is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your Bindchat Site ID</a> for it to work.'), "admin.php?page=bindchat/bindchat.php")."</p></div>
			";
		}
		add_action('admin_notices', 'bindchat_warning');
		return;
	}
}

function bindchat_nonce_field($action = -1) {
    return wp_nonce_field($action);
}
$bindchat_nonce = 'bindchat-update-siteid';

function bindchat_conf() {

	if ( isset($_POST['submit']) ) {
		$siteid = preg_replace('/[^a-h0-9\-]/i', '', $_POST['siteid'] );

		if (empty($siteid)) {
			$id_status = 'empty';
			$ms[] = 'new_id_empty';
			delete_option('bindchat_site_id');
        } else {
            $id_status = bindchat_verify_site_id($siteid);
        }

        if ( $id_status == 'valid' ) {
            update_option('bindchat_site_id', $siteid);
            $ms[] = 'new_id_valid';
        } else if ( $id_status == 'invalid' ) {
            $ms[] = 'new_id_invalid';
        } else if ( $id_status == 'failed' ) {
            $ms[] = 'new_id_failed';
        }


    }

    if ( empty( $id_status) ||  $id_status != 'valid') {
		$siteid = get_option('bindchat_site_id');
		if ( empty( $siteid ) ) {
			if ( empty( $id_status ) || $id_status != 'failed' ) {
				if ( bindchat_verify_site_id('0000-000-00-0000') == 'failed' )
					$ms[] = 'no_connection';
				else
					$ms[] = 'id_empty';
			}
			$id_status = 'empty';
		} else {
			$id_status = bindchat_verify_site_id($siteid);
		}
		if ( $id_status == 'valid' ) {
			$ms[] = 'id_valid';
		} else if ( $id_status == 'invalid' ) {
			$ms[] = 'id_invalid';
		} else if ( !empty($siteid) && $id_status == 'failed' ) {
			$ms[] = 'id_failed';
		}
	}

    $messages = array(
            'new_id_empty' => array('color' => 'BB3220', 'text' => __('您的站点ID已被清除。')),
            'new_id_valid' => array('color' => '4AB915', 'text' => __('站点ID有效，现在可以使用。请检查页面右下角是否显示聊天窗口。')),
            'new_id_invalid' => array('color' => 'BB3220', 'text' => __('您输入的站点ID无效，请重新输入验证。')),
            'new_id_failed' => array('color' => 'BB3220', 'text' => sprintf(__('暂时不能验证，因为不能连接到 <a href="%s" style="color:#fff" target="_blank">bindchat.com</a> 服务器，请检查！'), 'http://bindchat.com/plugins/wordpress/?return=true')),
            'id_empty' => array('color' => '005C9C', 'text' => sprintf(__('请输入站点ID。(<a href="%s" style="color:#fff">获取站点ID。</a>)'), 'http://bindchat.com/plugins/wordpress/?return=true')),
            'no_connection' => array('color' => 'BB3220', 'text' => sprintf(__('连接到 <a href="%s" style="color:#fff" target="_blank">bindchat.com</a> 服务器失败，请检查！'), 'http://bindchat.com/plugins/wordpress/?return=true')),
            'id_valid' => array('color' => '4AB915', 'text' => __('此站点ID有效。')),
            'id_invalid' => array('color' => 'BB3220', 'text' => __('站点ID无效。')),
            'id_failed' => array('color' => 'BB3220', 'text' => __('之前的站点ID已认证，但是现在不能连接到bindchat.com，请稍后重试。'))
            );
?>
<?php if ( !empty($_POST['submit'] ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
    <div id="bindchat-config" class="narrow">
    <form action="" method="post" style="margin: auto; width: 400px;">
        <p>
        必聊网可以在任何网站中嵌入Bindchat在线客服应用，在使用之前需要一个站点ID，
        如果您还没有申请，可以去 <a href="http://bindchat.com/plugins/wordpress/?return=true" target="_blank">Bindchat.com</a> 注册申请。
        </p>
        <h3><label for="siteid"><?php _e('Bindchat 站点ID'); ?></label></h3>
        <?php foreach ( $ms as $m ) : ?>
	    <p style="padding: .5em; background-color: #<?php echo $messages[$m]['color']; ?>; color: #fff; font-weight: bold;"><?php echo $messages[$m]['text']; ?></p>
        <?php endforeach; ?>
        <p>
           <input id="siteid" name="siteid" type="text" value="<?php echo get_option('bindchat_site_id'); ?>"/>
           (<a href="http://bindchat.com/plugins/wordpress/?return=true">这是什么？</a>)
        </p>
        <p class="submit">
            <input type="submit" name="submit" value="保存设置»" class="button button-primary"/>
        </p>
<?php bindchat_nonce_field($bindchat_nonce) ?>
    </form>
    </div>
</div>
<?php
}

function bindchat_admin_menu() {
	if ( class_exists( 'Jetpack' ) ) {
		add_action( 'jetpack_admin_menu', 'bindchat_load_menu' );
	} else {
		bindchat_load_menu();
	}
}

function bindchat_load_menu() {
	if ( class_exists( 'Jetpack' ) ) {
        if(function_exists('add_menu_page')) {
            add_menu_page('jetpack', '必聊网', 'manage_options', plugin_dir_path(__FILE__).'/bindchat.php', 'bindchat_conf', plugins_url('img/logo_16x16.png', __FILE__ ));
        }
	} else {
        if(function_exists('add_menu_page')) {
		    add_menu_page('bindchat', '必聊网', 'administrator', plugin_dir_path(__FILE__).'/bindchat.php', 'bindchat_conf', plugins_url('img/logo_16x16.png', __FILE__ ));
        }
	}

}

function bindchat_admin_footer() {
    bindchat_widget_script();
}
?>

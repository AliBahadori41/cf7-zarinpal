<?php
/*
Plugin Name: Zarinpal Contact form 7 payment
Plugin URI: https://github.com/AliBahadori41/cf7-zarinpal
Description: اتصال فرم های Contact Form 7 به درگاه پرداخت ZarinPal
Author: Ali Bahadori
Author URI: https://bahadori.dev
Version: 1.0
*/

if (!defined('ABSPATH')) exit;

register_activation_hook(__FILE__, "cf7ZarinpalActivate");
register_deactivation_hook(__FILE__, "cf7_zarinpal_deactivate");
register_uninstall_hook(__FILE__, "cf7_zarinpal_uninstall");


/**
 * Active plugin
 */
function cf7ZarinpalActivate()
{
    global $wpdb;

    $table_name = $wpdb->prefix . "cf7_zarinpal_transactions";

    $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
        id mediumint(11) NOT NULL AUTO_INCREMENT,
        idform bigint(11) DEFAULT '0' NOT NULL,
        transaction_authority VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
        transaction_reference VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULLABLE,
        gateway VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
        cost bigint(11) DEFAULT '0' NOT NULL,
        created_at bigint(11) DEFAULT '0' NOT NULL,
        email VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci  NULL,
        description VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
        user_mobile VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci  NULL,
        status VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
        PRIMARY KEY id (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);

    // remove ajax from contact form 7 to allow for php redirects
    function wp_config_put($slash = '')
    {
        $config = file_get_contents(ABSPATH . "wp-config.php");
        $config = preg_replace("/^([\r\n\t ]*)(\<\?)(php)?/i", "<?php define('WPCF7_LOAD_JS', false);", $config);
        file_put_contents(ABSPATH . $slash . "wp-config.php", $config);
    }

    if (file_exists(ABSPATH . "wp-config.php") && is_writable(ABSPATH . "wp-config.php")) {
        wp_config_put();
    } else if (file_exists(dirname(ABSPATH) . "/wp-config.php") && is_writable(dirname(ABSPATH) . "/wp-config.php")) {
        wp_config_put('/');
    } else {
?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been activated.', 'cf7_zarinpal'); ?></p>
        </div>
    <?php
        exit;
    }

    $cf7_zarinpal_options = array(
        'merchant_id' => '',
        'callback_url' => '',
        'currency' => 'IRR'
    );

    add_option("cf7_zarinpal", $cf7_zarinpal_options);
}


/**
 * De-Active plugin
 */
function cf7_zarinpal_deactivate()
{
    function wp_config_delete($slash = '')
    {
        $config = file_get_contents(ABSPATH . "wp-config.php");
        $config = preg_replace("/( ?)(define)( ?)(\()( ?)(['\"])WPCF7_LOAD_JS(['\"])( ?)(,)( ?)(0|1|true|false)( ?)(\))( ?);/i", "", $config);
        file_put_contents(ABSPATH . $slash . "wp-config.php", $config);
    }

    if (file_exists(ABSPATH . "wp-config.php") && is_writable(ABSPATH . "wp-config.php")) {
        wp_config_delete();
    } else if (file_exists(dirname(ABSPATH) . "/wp-config.php") && is_writable(dirname(ABSPATH) . "/wp-config.php")) {
        wp_config_delete('/');
    } else if (file_exists(ABSPATH . "wp-config.php") && !is_writable(ABSPATH . "wp-config.php")) {
    ?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7_zarinpal'); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
            function goBack() {
                window.history.back();
            }
        </script>
    <?php
        exit;
    } else if (file_exists(dirname(ABSPATH) . "/wp-config.php") && !is_writable(dirname(ABSPATH) . "/wp-config.php")) {
    ?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7_zarinpal'); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
            function goBack() {
                window.history.back();
            }
        </script>
    <?php
        exit;
    } else {
    ?>
        <div class="error">
            <p><?php _e('wp-config.php is not writable, please make wp-config.php writable - set it to 0777 temporarily, then set back to its original setting after this plugin has been deactivated.', 'cf7_zarinpal'); ?></p>
        </div>
        <button onclick="goBack()">Go Back and try again</button>
        <script>
            function goBack() {
                window.history.back();
            }
        </script>
    <?php
        exit;
    }


    delete_option("cf7_zarinpal");
    delete_option("cf7_zarinpal_my_plugin_notice_shown");
}


/**
 * Uninstall plguin
 */
function cf7_zarinpal_uninstall()
{
}

// display activation notice
add_action('admin_notices', 'cf7_zarinpal_my_plugin_admin_notices');
function cf7_zarinpal_my_plugin_admin_notices()
{
    if (!get_option('cf7_zarinpal_my_plugin_notice_shown')) {
        echo "<div class='updated'><p><a href='admin.php?page=cf7_zarinpal_admin_table'>برای تنظیم اطلاعات درگاه  کلیک کنید</a>.</p></div>";
        update_option("cf7_zarinpal_my_plugin_notice_shown", "true");
    }
}


include_once(ABSPATH . 'wp-admin/includes/plugin.php');
if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {

    // add paypal menu under contact form 7 menu
    add_action('admin_menu', 'cf7_zarinpal_admin_menu', 20);
    function cf7_zarinpal_admin_menu()
    {
        $addnew = add_submenu_page(
            'wpcf7',
            __('تنظیمات زرین پال', 'contact-form-7'),
            __('تنظیمات زرین پال', 'contact-form-7'),
            'wpcf7_edit_contact_forms',
            'cf7_zarinpal_admin_table',
            'cf7_zarinpal_admin_table'
        );

        $addnew = add_submenu_page(
            'wpcf7',
            __('لیست تراکنش ها', 'contact-form-7'),
            __('لیست تراکنش ها', 'contact-form-7'),
            'wpcf7_edit_contact_forms',
            'cf7_zarinpal_admin_transactions_list',
            'cf7_zarinpal_admin_transactions_list'
        );
    }
} else {
    /**
     * Show notice if contact form is not installed or not activated.
     */
    function cf7_zarinpal_my_admin_notice()
    {
        ?>
        <div class="error">
			<p> <?php echo _e('<b> افزونه درگاه بانکی برای افزونه Contact Form 7 :</b> Contact Form 7 باید فعال باشد ', 'my-text-domain') ?>
		</div>
        <?php
    }
    add_action('admin_notices', 'cf7_zarinpal_my_admin_notice');
}

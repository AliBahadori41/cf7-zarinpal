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


function getTableName()
{
    global $wpdb;
    return $wpdb->prefix . "cf7_zarinpal_transactions";
}

/**
 * Active plugin
 */
function cf7ZarinpalActivate()
{

    $table_name = getTableName();

    $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
        id mediumint(11) NOT NULL AUTO_INCREMENT,
        idform bigint(11) DEFAULT '0' NOT NULL,
        transaction_authority VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
        transaction_reference VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NULL,
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
        'error_color' => '#f44336',
        'sucess_color' => '#8BC34A',
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

    /**
     * Add zarinpal menu item to sidebar.
     */
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
    add_action('admin_menu', 'cf7_zarinpal_admin_menu', 20);

    function cf7_zarinpal_after_send_mail($cf7)
    {
        global $wpdb;
        global $postid;
        $postid = $cf7->id();


        $enable = get_post_meta($postid, "_cf7_zarinpal_enable", true);
        $email = get_post_meta($postid, "_cf7_zarinpal_email", true);

        if ($enable == "1") {
            if ($email == "2") {

                $param = [];

                $result = request_payment($param);

                if (is_string($result)) {
                    echo "خطا دراتصال به درگاه : " . $result;
                } else {

                    $wpdb->insert(getTableName(), []);

                    if ($result['data']['code'] === 100) {
                    }
                    if ($result['data']['code'] === 101) {
                    } else {
                    }
                }
            }
        }
    }
    add_action('wpcf7_mail_sent', 'cf7_zarinpal_after_send_mail');




    function cf7_zarinpal_admin_table()
    {
        $zarinpal_setting = get_option('cf7_zarinpal');

        delete_option('cf7_zarinpal');

        if (isset($_POST['zarinpal_setting_submit'])) {

            $options['merchant_id'] = sanitize_text_field($_POST['merchant_id']);
            $options['callback_url'] = sanitize_text_field($_POST['callback_url']);
            $options['error_color'] = sanitize_text_field($_POST['error_color']);
            $options['sucess_color'] = sanitize_text_field($_POST['sucess_color']);
            $options['currency'] = sanitize_text_field($_POST['currency']);
            $options['successfull_transaction_text'] = wp_filter_post_kses($_POST['successfull_transaction_text']);
            $options['unsuccessfull_transaction_text'] = wp_filter_post_kses($_POST['unsuccessfull_transaction_text']);
            update_option("cf7_zarinpal", $options);

            echo "<br /><div class='updated'><p><strong>";
            _e("تغییرات ذخیره شد.");
            echo "</strong></p></div>";
        }


        if($zarinpal_setting['currency'] == 'IRR') {
            $irr_selected = true;
        } else {
            $irr_selected = false;
        }

    ?>
        <div class="wrap">
            <h1>
                تنظیمات درگاه پرداخت زرین پال
            </h1>
        </div>

        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="merchant_id">
                                مرچنت کد
                            </label>
                        </th>
                        <td>
                            <input required dir="ltr" value="<?php echo $zarinpal_setting['merchant_id']; ?>"
                             type="text" name="merchant_id" id="merchant_id" placeholder="مرچنت کد" class="regular-text" style="display: block;">
                            <span class="description">
                                برای دریافت مرچنت کد درگاه خود به پنل کاربری
                                <a target="_blank" href="https://zarinpal.com">زرین پال</a>
                                خود مراجعه کنید
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="currency">
                                ارز درگاه
                            </label>
                        </th>
                        <td>
                            <select required name="currency" id="currency" style="display: block;">
                                <option <?php $irr_selected === true ? 'selected' : '';?> value="IRR">ریال</option>
                                <option <?php $irr_selected === false ? 'selected' : '' ;?> value="IRT">تومان</option>
                            </select>
                            <span class="description">
                                مبلغ پرداختی برحسب ارز انتخاب شده به درگاه ارسال می گردد.
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="callback_url">
                                آدرس بازگشت از درگاه
                            </label>
                        </th>
                        <td>
                            <input dir="ltr" value="<?php echo $zarinpal_setting['callback_url']; ?>"
                             required type="text" name="callback_url" id="callback_url" placeholder="آدرس بازگشت از درگاه" class="regular-text" style="display: block;">
                            <span class="description">
                                یک برگه ایجاد کنید و این کد کوتاه [zarinpal_result_payment] را در ان قرار دهید.
                                <br>
                                <strong>
                                    این مورد اجباری می باشد.
                                </strong>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="successfull_transaction_text">
                                متن تراکنش موفق :
                            </label>
                        </th>
                        <td>
                            <textarea type="text" name="successfull_transaction_text" id="successfull_transaction_text" placeholder="متن" class="regular-text" style="display: block;"><?php echo $zarinpal_setting['successfull_transaction_text']; ?></textarea>
                            <span class="description">
                                متنی که میخواهید در هنگام موفقیت آمیز بودن تراکنش نشان دهید
                                <br>
                                برای نمایش شناسه تراکنش درصورت موفق بود تراکنش از کد کوتاه
                                [zarinpal_transaction_ref]
                                استفاده نمایید
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="unsuccessfull_transaction_text">
                                متن تراکنش ناموفق :
                            </label>
                        </th>
                        <td>
                            <textarea type="text" name="unsuccessfull_transaction_text" id="unsuccessfull_transaction_text" placeholder="متن" class="regular-text" style="display: block;"><?php echo $zarinpal_setting['unsuccessfull_transaction_text']; ?></textarea>
                            <span class="description">
                                متنی که میخواهید در هنگام موفقیت آمیز نبودن تراکنش نشان دهید
                                <br>
                                برای نمایش خطایی که از سمت زرین پال ارسال می شود می توانید از کد کوتاه
                                [zarinpal_error_message]
                                استفاده کنید.
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sucess_color">
                                رنگ متن تراکنش موفق
                            </label>
                        </th>
                        <td>
                            <input
                            value="<?php echo $zarinpal_setting['sucess_color']; ?>"
                             required dir="ltr" type="text" name="sucess_color" id="sucess_color" placeholder="نام یا کد رنگ" class="regular-text" style="display: block;color: <?php echo $zarinpal_setting['sucess_color']; ?>">
                            <span class="description">
                                مانند : #8BC34A یا نام رنگ green
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="error_color">
                                رنگ متن تراکنش ناموفق
                            </label>
                        </th>
                        <td>
                            <input 
                            value="<?php echo $zarinpal_setting['error_color']; ?>"
                             required dir="ltr" type="text" name="error_color" id="error_color" placeholder="نام یا کد رنگ" class="regular-text" style="display: block;color: <?php echo $zarinpal_setting['error_color']; ?>">
                            <span class="description">
                                مانند : #f44336 یا نام رنگ red
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <input type="submit" name="zarinpal_setting_submit" id="submit" class="button button-primary" value="ذخیرهٔ تنظیمات">
                        </th>
                    </tr>

                </tbody>
            </table>
        </form>
    <?php
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


/**
 * Send payment request to zarinpal.
 */
function request_payment(array $param)
{

    $jsonData = json_encode($param);
    $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/request.json');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Zarinpal Rest Api v4');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));
    $result = curl_exec($ch);
    $err = curl_error($ch);
    $result = json_decode($result, true, JSON_PRETTY_PRINT);
    curl_close($ch);

    return $err ? $err : $result;
}

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
        price bigint(11) DEFAULT '0' NOT NULL,
        created_at timestamp DEFAULT '0' NOT NULL,
        currency VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
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
        'successfull_transaction_text' => null,
        'unsuccessfull_transaction_text' => null,
        'currency' => 'IRR'
    );

    add_option("cf7_zarinpal", $cf7_zarinpal_options);
}


function cf7ZarinpalCreatePage($title, $body)
{
    $tmp = '
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>' . $title . '</title>
	</head>
	<link rel="stylesheet"  media="all" type="text/css" href="' . plugins_url('style.css', __FILE__) . '">
	<body class="vipbody">	
	<div class="mrbox2" > 
	<h3><span>' . $title . '</span></h3>
	' . $body . '	
	</div>
	</body>
	</html>';
    return $tmp;
}

/**
 * Return a reable message for user.
 */
function cf7ZarinpalCreateMessage($title, $body, $onlyText = null)
{
    if ($onlyText != null) {
        return $onlyText;
    }

    $title = in_array($title, [null, ""]) ? 'وضعیت تراکنش' : $title;

    $tmp = "<div style='border:#CCC 1px solid; width:90%;border-radius: 20px;padding: 20px;'>$title : $body </div>";

    return $tmp;
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


    add_action('wpcf7_before_send_mail', 'cf7_zarinpal_before_send_mail');
    function cf7_zarinpal_before_send_mail($cf7)
    {
    }

    function cf7_zarinpal_after_send_mail($cf7)
    {
        global $wpdb;
        global $postid;
        $postid = $cf7->id();


        $enable = get_post_meta($postid, "_cf7_zarinpal_enable", true);
        $email = get_post_meta($postid, "_cf7_zarinpal_email", true);

        if ($enable == "1") {
            if ($email == "2") {

                $zarinpal_setting = get_option('cf7_zarinpal');
                $amount = get_post_meta($postid, "_cf7_zarinpal_price", true);

                $submission = WPCF7_Submission::get_instance();
                $user_email = '';
                $user_mobile = '';
                $description = '';
                $user_price = '';

                if ($submission) {
                    $data = $submission->get_posted_data();
                    $user_email = isset($data['user_email']) ? $data['user_email'] : "";
                    $user_mobile = isset($data['user_mobile']) ? $data['user_mobile'] : "";
                    $description = isset($data['description']) ? $data['description'] : "پرداخت برای فرم ارتباط با ما. شماره فرم : " . $postid;
                    $user_price = isset($data['user_price']) ? $data['user_price'] : "";
                }

                if ($amount == "" || $amount == null) {
                    $amount = $user_price;
                }

                $param = [
                    "merchant_id" => $zarinpal_setting['merchant_id'],
                    "amount" => $amount,
                    "callback_url" => $zarinpal_setting['callback_url'],
                    'description' => $description,
                    'currency' => $zarinpal_setting['currency'],
                    'metadata' => [
                        'mobile' => strlen($user_mobile) == 0 ? "0" : $user_mobile,
                        'email' => $user_email ? $user_email : "0",
                    ],
                ];

                $result = request_payment('request', $param);

                if (is_string($result)) {
                    echo "خطا دراتصال به درگاه : " . $result;
                } else {
                    if ($result['data']['code'] === 100) {

                        $authority = $result['data']['authority'];

                        $wpdb->insert(getTableName(), [
                            'idform' => $postid,
                            'transaction_authority' => $authority,
                            'gateway' => 'Zarinpal',
                            'price' => $amount,
                            'created_at' => date('Y/m/d H:i:s'),
                            'email' => $user_email,
                            'currency' => $zarinpal_setting['currency'],
                            'user_mobile' => $user_mobile,
                            'description' => $description,
                            'status' => 'none',
                        ], ['%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']);
                       
                        header('Location: https://www.zarinpal.com/pg/StartPay/' . $authority);
                        exit;
                    } else {

                        $tmp = "<p>کد خطا : ". $result['errors']['code'] ." </br>". error_message($result['errors']['code']) ."</p>";
                        echo cf7ZarinpalCreatePage('خطا : ', $tmp);
                        exit;
                    }
                }
            }
        }
    }
    add_action('wpcf7_mail_sent', 'cf7_zarinpal_after_send_mail');

    function cf7_zarinpal_admin_table()
    {
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

        $zarinpal_setting = get_option('cf7_zarinpal');

        if ($zarinpal_setting['currency'] == 'IRR') {
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
                            <input required dir="ltr" value="<?php echo $zarinpal_setting['merchant_id']; ?>" type="text" name="merchant_id" id="merchant_id" placeholder="مرچنت کد" class="regular-text" style="display: block;">
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
                                <option <?php echo $irr_selected === true ? 'selected' : ''; ?> value="IRR">ریال</option>
                                <option <?php echo $irr_selected === false ? 'selected' : ''; ?> value="IRT">تومان</option>
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
                            <input dir="ltr" value="<?php echo $zarinpal_setting['callback_url']; ?>" required type="text" name="callback_url" id="callback_url" placeholder="آدرس بازگشت از درگاه" class="regular-text" style="display: block;">
                            <span class="description">
                                یک برگه ایجاد کنید و این کد کوتاه [zarinpal_payment_result] را در ان قرار دهید.
                                <br>
                                <strong style="color:red;">
                                    *
                                    این مورد اجباری می باشد.
                                </strong>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="successfull_transaction_text">
                                متن تراکنش موفق
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
                                متن تراکنش ناموفق
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
                            <input value="<?php echo $zarinpal_setting['sucess_color']; ?>" required dir="ltr" type="text" name="sucess_color" id="sucess_color" placeholder="نام یا کد رنگ" class="regular-text" style="display: block;color: <?php echo $zarinpal_setting['sucess_color']; ?>">
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
                            <input value="<?php echo $zarinpal_setting['error_color']; ?>" required dir="ltr" type="text" name="error_color" id="error_color" placeholder="نام یا کد رنگ" class="regular-text" style="display: block;color: <?php echo $zarinpal_setting['error_color']; ?>">
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

    function cf7_zarinpal_verify_transaction($atts)
    {
        $status = $_GET['Status'];
        $table_name = getTableName();
        $authority = $_GET['Authority'];
        $zarinpal_setting = get_option('cf7_zarinpal');
        global $wpdb;

        $cf_Form = $wpdb->get_row("SELECT * FROM $table_name WHERE transaction_authority =  '$authority'");
    
        if (! $cf_Form) {
            $body = '<b style="color:' . $zarinpal_setting['error_color'] . ';">تراکنشی که قصد تایید آن را دارید پیدا نشد.</b>';
            return cf7ZarinpalCreateMessage("تراکنش پیدا نشد", $body);
        }

        if ($status == 'OK') {
            $params = [
                'merchant_id' => $zarinpal_setting['merchant_id'],
                'authority' => $authority,
                'amount' => $cf_Form->cost,
            ];
    
            $result = request_payment('verify', $params);
    
            if (is_string($result)) {
                $body = 'خطا در اتصال به درگاه : ' . $result;
                return cf7ZarinpalCreateMessage(null, $body);
            }
            else  {
                if ($result['data']['code'] === 100) {
                    $res=$result ['data']['ref_id'];
                    $wpdb->update($wpdb->prefix . 'cfZ7_transaction', array('status' => 'success', 'transid' => $res), array('transid' => $authority), array('%s', '%s'), array('%s'));
                    $body = '<b style="color:' . $sucess_color . ';">' . stripslashes(str_replace('[transaction_id]',   $res, $Theme_Message)) . '</b>';
                    return cf7ZarinpalCreateMessage("", $body);
                } elseif ($result['data']['code'] === 101) {
                    $res=$result ['data']['ref_id'];
                    $wpdb->update($wpdb->prefix . 'cfZ7_transaction', array('status' => 'success', 'transid' => $res), array('transid' => $authority), array('%s', '%s'), array('%s'));
                    $body = '<b style="color:' . $sucess_color . ';">' . stripslashes(str_replace('[transaction_id]',   $res, $Theme_Message)) . '</b>';
                    return cf7ZarinpalCreateMessage("", $body);
                } else {
                    $wpdb->update($wpdb->prefix . 'cfZ7_transaction', array('status' => 'error'), array('transid' =>  $authority), array('%s'), array('%s'));
                    $body = '<b style="color:' . $error_color . ';">' . $theme_error_message . '</b>';
                    $body .= '</br>';
                    $body .= ' خطا : ';
                    $body .= error_message($result['errors']['code']);
                    return cf7ZarinpalCreateMessage("", $body);
                }
            }
        }
        else if($status == 'NOK') {
            
            updateTransctionStatus($authority, $status = 'canceled');
            $body = 'پرداخت توسط کاربر لغو شد.';
            return cf7ZarinpalCreateMessage("", $body);
        }
    
    }
    add_shortcode('zarinpal_payment_result', 'cf7_zarinpal_verify_transaction');
    

    function cf7_zarinpal_editor_panels($panels)
    {
        $new_page = array(
            'PricePay' => array(
                'title' => __('اطلاعات پرداخت', 'contact-form-7'),
                'callback' => 'cf7_zarinpal_admin_after_additional_settings'
            )
        );

        $panels = array_merge($panels, $new_page);

        return $panels;
    }
    add_filter('wpcf7_editor_panels', 'cf7_zarinpal_editor_panels');

    function cf7_zarinpal_admin_after_additional_settings($cf7)
    {

        if (isset($_GET['post'])) {
            $post_id = sanitize_text_field($_GET['post']);
            $enable = get_post_meta($post_id, "_cf7_zarinpal_enable", true);
            $price = get_post_meta($post_id, "_cf7_zarinpal_price", true);

            if ($enable == "1") {
                $checked = "CHECKED";
            } else {
                $checked = "";
            }

            $admin_table_output = "";
            $admin_table_output .= "<form>";
            $admin_table_output .= "<div id='additional_settings-sortables' class='meta-box-sortables ui-sortable'><div id='additionalsettingsdiv' class='postbox'>";
            $admin_table_output .= "<div class='handlediv' title='Click to toggle'><br></div><h3 class='hndle ui-sortable-handle'> <span>اطلاعات پرداخت برای فرم</span></h3>";
            $admin_table_output .= "<div class='inside'>";
            $admin_table_output .= "<div class='mail-field'>";
            $admin_table_output .= "<input name='enable' id='cf71' value='1' type='checkbox' $checked>";
            $admin_table_output .= "<label for='cf71'>فعال سازی امکان پرداخت آنلاین</label>";
            $admin_table_output .= "</div>";
            $currency_type = get_option('cf7_zarinpal')['currency'] == 'IRR' ? 'ریال' : 'تومان';
            $admin_table_output .= "<table>";
            $admin_table_output .= "<tr><td>مبلغ: </td><td><input type='text' name='price' style='text-align:left;direction:ltr;' value='$price'></td><td>(مبلغ به " . $currency_type . ")</td></tr>";
            $admin_table_output .= "</table>";
            $admin_table_output .= "<br> برای اتصال به درگاه پرداخت میتوانید از نام فیلدهای زیر استفاده نمایید ";
            $admin_table_output .= "<br />
                <span style='color:#F00;'>
                user_email نام فیلد دریافت ایمیل کاربر بایستی user_email انتخاب شود.
                <br />
                description نام فیلد  توضیحات پرداخت بایستی description انتخاب شود.
                <br />
                user_mobile نام فیلد  موبایل بایستی user_mobile انتخاب شود.
                <br />
                user_price اگر کادر مبلغ در بالا خالی باشد می توانید به کاربر اجازه دهید مبلغ را خودش انتخاب نماید . کادر متنی با نام user_price ایجاد نمایید
                <br/>
                مانند [text* user_price]
                </span>	";
            $admin_table_output .= "<input type='hidden' name='email' value='2'>";

            $admin_table_output .= "<input type='hidden' name='post' value='$post_id'>";

            $admin_table_output .= "</td></tr></table></form>";
            $admin_table_output .= "</div>";
            $admin_table_output .= "</div>";
            $admin_table_output .= "</div>";
        } else {
            $admin_table_output = "<p>ابتدا باید یک فرم بسازید و بعد از ساخت می توانید قیمت ان را تعیین کنید</p>";
        }
        echo $admin_table_output;
    }
    add_action('wpcf7_admin_after_additional_settings', 'cf7_zarinpal_admin_after_additional_settings');

    function cf7_zarinpal_save_contact_form($cf7)
    {

        $post_id = sanitize_text_field($_POST['post']);

        if (!empty($_POST['enable'])) {
            $enable = sanitize_text_field($_POST['enable']);
            update_post_meta($post_id, "_cf7_zarinpal_enable", $enable);
        } else {
            update_post_meta($post_id, "_cf7_zarinpal_enable", 0);
        }

        $price = sanitize_text_field($_POST['price']);
        update_post_meta($post_id, "_cf7_zarinpal_price", $price);

        $email = sanitize_text_field($_POST['email']);
        update_post_meta($post_id, "_cf7_zarinpal_email", $email);
    }
    add_action('wpcf7_save_contact_form', 'cf7_zarinpal_save_contact_form');


    function updateTransctionStatus(string $authority, string $status)
    {
        global $wpdb;
        $table_name = getTableName();
        $wpdb->get_row("SELECT * FROM $table_name WHERE transaction_authority =  '$authority'");
        $wpdb->update(
            $table_name,
            ['status' => $status],
            ['transaction_authority' => $authority],
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


/**
 * Send payment request to zarinpal.
 */
function request_payment(string $action , array $param)
{

    $jsonData = json_encode($param);
    $ch = curl_init("https://api.zarinpal.com/pg/v4/payment/$action.json");
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

/**
 * Zarinpal error message.
 * 
 * @param int $code
 * @return string
 */
function error_message($code)
{
    $message = null;

    switch ($code) {
        case $code == -9:
            $message = ('اطلاعات ارسال شده نادرست می باشد.');
            $message .= "<br>" . ('1- مرچنت کد داخل تنظیمات وارد نشده باشد');
            $message .= "<br>" . ('2- مبلغ پرداختی کمتر یا بیشتر از حد مجاز می باشد');
        break; 
        case $code == -10:
            $message = ('ای پی یا مرچنت كد پذیرنده صحیح نیست.');
        break; 
        case $code == -11:
            $message = ('مرچنت کد فعال نیست، پذیرنده مشکل خود را به امور مشتریان زرین‌پال ارجاع دهد.');
        break; 
        case $code == -12:
            $message = ('تلاش بیش از دفعات مجاز در یک بازه زمانی کوتاه به امور مشتریان زرین پال اطلاع دهید');
        break; 
        case $code == -15:
            $message = ('درگاه پرداخت به حالت تعلیق در آمده است، پذیرنده مشکل خود را به امور مشتریان زرین‌پال ارجاع دهد.');
        break; 
        case $code == -16:
            $message = ('سطح تایید پذیرنده پایین تر از سطح نقره ای است.');
        break; 
        case $code == -17:
            $message = ('محدودیت پذیرنده در سطح آبی');
        break; 
        case $code == -30:
            $message = ('پذیرنده اجازه دسترسی به سرویس تسویه اشتراکی شناور را ندارد.');
        break; 
        case $code == -31:
            $message = ('حساب بانکی تسویه را به پنل اضافه کنید. مقادیر وارد شده برای تسهیم درست نیست. پذیرنده جهت استفاده از خدمات سرویس تسویه اشتراکی شناور، باید حساب بانکی معتبری به پنل کاربری خود اضافه نماید.');
        break; 
        case $code == -32:
            $message = ('مبلغ وارد شده از مبلغ کل تراکنش بیشتر است.');
        break; 
        case $code == -33:
            $message = ('درصدهای وارد شده صحیح نیست.');
        break; 
        case $code == -34:
            $message = ('مبلغ وارد شده از مبلغ کل تراکنش بیشتر است.');
        break; 
        case $code == -35:
            $message = ('تعداد افراد دریافت کننده تسهیم بیش از حد مجاز است.');
        break; 
        case $code == -36:
            $message = ('حداقل مبلغ جهت تسهیم باید 10000 ریال باشد');
        break; 
        case $code == -37:
            $message = ('یک یا چند شماره شبای وارد شده برای تسهیم از سمت بانک غیر فعال است.');
        break; 
        case $code == -38:
            $message = ('خط،عدم تعریف صحیح شبا،لطفا دقایقی دیگر تلاش کنید.');
        break; 
        case $code == -39:
            $message = ('خطایی رخ داده است به امور مشتریان زرین پال اطلاع دهید');
        break; 
        case $code == -50:
            $message = ('مبلغ پرداخت شده با مقدار مبلغ ارسالی در متد وریفای متفاوت است.');
        break; 
        case $code == -51:
            $message = ('پرداخت ناموفق');
        break; 
        case $code == -52:
            $message = ('خطای غیر منتظره‌ای رخ داده است. پذیرنده مشکل خود را به امور مشتریان زرین‌پال ارجاع دهد.');
        break; 
        case $code == -53:
            $message = ('پرداخت متعلق به این مرچنت کد نیست.');
        break; 
        case $code == -54:
            $message = ('اتوریتی نامعتبر است.');
        break;
    }

    return $message;
}
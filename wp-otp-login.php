<?php
/**
 * Wordpress OTP Login
 *
 * @wordpress-plugin
 * Plugin Name:       Wordpress OTP Login
 * Plugin URI: https://github.com/nimrod-cohen/wp-otp-login
 * Description:       Allow to log in to wordpress via one time password
 * Version:           1.1.1
 * Author:            nimrod-cohen
 * Author URI:        https://github.com/nimrod-cohen/wp-otp-login
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-otp-login
 * Domain Path:       /languages
 */

if (!class_exists('WPOTPLogin')) {

  class WPOTPLogin {
    public static $PROVIDERS = [];
    public static $_instance = null;

    const OVERRIDE_LOGIN = "wpotp_login_override";

    public static function instance() {
      if (self::$_instance === null) {
        self::$_instance = new self();
      }
      return self::$_instance;
    }

    private function __construct() {
      add_action('wp_ajax_nopriv_send_otp', [$this, 'check_login_method']);
      add_action('wp_ajax_nopriv_verify_otp_code', [$this, 'verify_otp_code']);
      add_action('init', [$this, 'redirect_login_page']);
      add_action('plugins_loaded', [$this, 'load_languages']);
      add_shortcode('otp-login-page', [$this, 'render_login_box']);

      //admin
      add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
      add_action('admin_menu', [$this, 'add_settings_page']);
      add_action('admin_bar_menu', [$this, 'add_settings_to_admin_bar'], 100);

      add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
      add_action('wp_ajax_save_otp_settings', [$this, 'save_settings']);
      add_filter('auth_cookie_expiration', [$this, 'set_cookie_expiration'], 10, 3);
      add_action('wp_logout', [$this, 'clear_login_method']);

      add_action('admin_init', function () {
        $updater = new GitHubPluginUpdater(__FILE__);
      });

      if (!session_id()) {
        session_start();
      }
    }

    public function set_cookie_expiration($expiration, $user_id, $remember) {
      $expire = get_option('wpotp_session_expiration_days') ?? false;
      return $expire ? intval($expire) * DAY_IN_SECONDS : $expiration;
    }

    public function add_settings_link($links) {
      $settings_link = '<a href="options-general.php?page=wp-otp-login-settings">Settings</a>';
      array_unshift($links, $settings_link);
      return $links;
    }

    public static function version() {
      if (!function_exists('get_plugin_data')) { //allowing plugin data to be available for frontend calls
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
      }

      $plugin_data = get_plugin_data(__FILE__);
      return $plugin_data['Version'];
    }

    public static function log($msg) {
      if (is_array($msg) || is_object($msg)) {
        $msg = print_r($msg, true);
      }

      $date = date("Y-m-d");
      $datetime = date("Y-m-d H:i:s");
      file_put_contents(
        ABSPATH . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "debug-otp-$date.log",
        "$datetime | $msg\r\n",
        FILE_APPEND);
    }

    function load_languages() {
      load_plugin_textdomain('wp-otp-login', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    function is_plugin_active() {
      return get_option('wpotp_otp_enabled') == "true";
    }

    function redirect_login_page() {

      global $pagenow;

      $requested_url = site_url($_SERVER["REQUEST_URI"]);
      $requested_url = strtok($requested_url, '?');
      $custom_login_page = get_option('wpotp_custom_login_page');

      //if we're on the custom login page already, just load the assets and return.
      if (strpos($requested_url, $custom_login_page) !== false) {
        if (is_user_logged_in()) {
          wp_redirect(site_url('/'));
        } else {
          $this->enqueue_assets();
        }
        return;
      }

      //are we trying to access the wp-login.php page?
      if (!$this->is_plugin_active() || $pagenow != "wp-login.php" || is_user_logged_in()) {
        return;
      }

      //if the user is part of the exclusion list, allow login
      if ($_SESSION[self::OVERRIDE_LOGIN] ?? false === true) {
        return;
      }

      wp_redirect($custom_login_page);
      exit();
    }

    function enqueue_assets() {
      wp_enqueue_style('wp-buttons-style', includes_url('css/buttons.min.css'));
      wp_enqueue_style('wp-login-style', admin_url('css/login.min.css'));
      wp_enqueue_style('wp-forms-style', admin_url('css/forms.min.css'));
      $cachebust = "?time=" . date('Y_m_d_H');
      wp_enqueue_script('wpotp-login-js', plugins_url('js/otp.js' . $cachebust, __FILE__), ['wpjsutils']);
      wp_enqueue_style('wpotp-login-style', plugins_url('css/otp.css' . $cachebust, __FILE__));

      wp_localize_script('wpotp-login-js', 'otpInfo',
        [
          'ajax_url' => admin_url('admin-ajax.php'),
          'messages' => [
            "please_wait_time" => __("Please wait %s seconds before resubmitting", 'wp-otp-login')
          ]
        ]);
    }

    function verify_otp_code() {
      try {
        $otp_code = $_REQUEST["otp_code"];
        $user_id = $_REQUEST["user_id"];

        $otp_code = preg_replace('/[[:^print:]]/', '', $otp_code);

        $saved_otp = get_user_meta($user_id, 'otp_code', true);
        if (!$saved_otp) {
          wp_send_json(["error" => true, "message" => __("Incorrect code", "wp-otp-login")]); //this dies
        }

        $saved_otp = json_decode($saved_otp, true);
        if (empty($saved_otp) || (time() - intval($saved_otp["ts"])) >= 600) {
          wp_send_json(["error" => true, "message" => __("Incorrect code", "wp-otp-login")]); //this dies
        }

        $saved_otp = $saved_otp["code"];

        if (empty($saved_otp) || empty($otp_code)) {
          wp_send_json(["error" => true, "message" => __("Incorrect code", "wp-otp-login")]); //this dies
        }

        if ($saved_otp == $otp_code) {
          wp_clear_auth_cookie(); // Clear any existing auth cookies
          wp_set_current_user($user_id);
          wp_set_auth_cookie($user_id, true);

          $redirect = apply_filters('wpotp/redirect-successful-login', site_url('/'), $user_id);

          wp_send_json(["error" => false, "redirect" => $redirect, "message" => __("Please wait while you are being redirected", "wp-otp-login")]); //this dies
        } else {
          wp_send_json(["error" => true, "message" => __("Incorrect code", "wp-otp-login")]); //this dies
        }
      } catch (Exception $ex) {
        self::log("verify_otp_code error");
        self::log($ex->getMessage());
        wp_send_json(["error" => true, "message" => __("Incorrect code", "wp-otp-login")]); //this dies
      } finally {
        die;
      }
    }

    function find_user_by_phone($phone) {
      $phoneMetaField = get_option('wpotp_phone_meta_field');
      global $wpdb;

      $sql = $wpdb->prepare("SELECT wp_users.ID
    FROM wp_users
    INNER JOIN wp_usermeta ON wp_users.ID = wp_usermeta.user_id
    WHERE wp_usermeta.meta_key = %s
    AND wp_usermeta.meta_value = %s", [$phoneMetaField, $phone]);

//    INNER JOIN wp_usermeta AS mt1 ON wp_users.ID = mt1.user_id and mt1.meta_key = 'wp_capabilities'
//    $sql .= " AND mt1.meta_value LIKE '%student%'";

      $row = $wpdb->get_row($sql, ARRAY_A);

      // Check if a user was found
      if (!empty($row)) {
        return $row["ID"];
      }

      return null;
    }

    function find_user_by_email($email) {
      global $wpdb;

      // Prepare the SQL query to fetch the user ID based on the lowercase email
      $query = $wpdb->prepare("
          SELECT ID
          FROM $wpdb->users
          WHERE LOWER(user_email) = LOWER(%s)
      ", $email);

      // Execute the query and fetch the user ID
      $user_id = $wpdb->get_var($query);

      return $user_id;
    }

    function send_otp_phone($phone, $code) {
      //check if production
      if (wp_get_environment_type() != "production") {
        return ["error" => false, "message" => "not production"];
      }

      $sender = new OTP_019();
      $result = $sender->sendCode($phone, sprintf(get_option("wpotp_message"), $code), $code);

      return $result;
    }

    function send_otp_email($email, $code) {
      $sender = new OTP_CutomerIO();

      $result = $sender->sendCode($email, sprintf(get_option("wpotp_message"), $code), $code);

      return $result;
    }

    function clear_login_method() {
      if (isset($_SESSION[self::OVERRIDE_LOGIN])) {
        unset($_SESSION[self::OVERRIDE_LOGIN]);
      }
    }

    function check_login_method() {
      $identifier = $_POST["identifier"] ?? null;

      if (empty($identifier)) {
        throw new Exception(__("Missing user identifier", "wp-otp-login"));
      }

      $excludeList = array_reduce(
        explode(',', get_option('wpotp_exclude_list') ?? ''),
        function ($carry, $item) {
          $item = strtolower(trim($item));
          if (!empty($item)) {
            $carry[] = $item;
          }
          return $carry;
        },
        []
      );

      $identifier = strtolower($identifier);

      if (in_array($identifier, $excludeList)) {
        //check if session started, and set session to allow login
        $this->send_override($identifier);
      } else {
        $this->send_otp();
      }
    }

    function send_override($identifier) {
      $_SESSION[self::OVERRIDE_LOGIN] = true;
      wp_send_json([
        "error" => false,
        "action" => "exclude_user",
        "redirect" => wp_login_url() . '?login=1&email=' . urlencode($identifier)
      ]);
    }

    function send_otp() {
      try {
        $emailEnabled = get_option('wpotp_cio_enabled') == "true";
        $phoneEnabled = get_option('wpotp_019_enabled') == "true";

        $userId = null;
        $identifier = isset($_POST["identifier"]) ? $_POST["identifier"] : null;

        self::log("REQUEST ARRIVED - OTP for identifier: $identifier ==>");

        if ($emailEnabled && !empty($identifier) && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
          $userId = $this->find_user_by_email($identifier);
          self::log("found user $userId by email: $identifier");

        } else if ($phoneEnabled && !empty($identifier)) {

          $phone = apply_filters('wpotp/cleanup-phone', $identifier);
          $userId = $this->find_user_by_phone($phone);
          self::log("found user $userId by phone: $phone");
        }

        if (!$userId) {
          throw new Exception(__("Could not find user by this identifier", "wp-otp-login"));
        }

        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        update_user_meta($userId, 'otp_code', json_encode(["code" => $code, "ts" => time()]));
        self::log("OTP for user $userId is $code");

        $user = new WP_User($userId);
        $phoneMetaField = get_option('wpotp_phone_meta_field');
        $phone = get_user_meta($userId, $phoneMetaField, true);

        if ($emailEnabled) {
          $response = $this->send_otp_email($user->user_email, $code);
          self::log("email response was: " . json_encode($response));
        }
        if ($phoneEnabled && !empty($phone)) {
          $response = $this->send_otp_phone($phone, $code);
          self::log("sms response was: " . json_encode($response));
        }

        $mean = "";
        if ($emailEnabled && $phoneEnabled) {
          $mean = __("email account and mobile number", "wp-otp-login");
        } else if ($emailEnabled) {
          $mean = __("email account", "wp-otp-login");
        } else if ($phoneEnabled) {
          $mean = __("phone", "wp-otp-login");
        }
        $message = __("At this moment a one time code has been sent to your %s", "wp-otp-login");
        $message = sprintf($message, $mean);

        echo json_encode(["error" => false, "user_id" => $userId, "message" => $message]);
        die;
      } catch (Exception $ex) {
        self::log($ex->getMessage());
        echo json_encode(["error" => true, "message" => $ex->getMessage()]);
        die;
      }
    }

    function save_settings() {
      $values = array_filter($_POST, function ($key) {return $key !== 'action';}, ARRAY_FILTER_USE_KEY);

      foreach ($values as $key => $value) {
        update_option($key, trim($value), true);
      }

      echo json_encode(["message" => "options saved successfully"]);
      die;
    }

    function enqueue_admin_assets($hook) {
      if ($hook != 'settings_page_wp-otp-login-settings') {
        return;
      }

      wp_enqueue_script('wpotp-js', plugins_url('js/admin.js', __FILE__), ['wpjsutils']);
    }

    function add_settings_to_admin_bar($admin_bar) {
      $admin_bar->add_menu([
        'id' => 'wp-otp-login-settings',
        'title' => '<span style="font-family: dashicons" class="dashicons dashicons-admin-network"></span> OTP',
        'href' => admin_url('options-general.php?page=wp-otp-login-settings')
      ]);
    }

    function add_settings_page() {
      add_options_page(
        'WP OTP Login Settings', // Page title
        'WP OTP Login', // Menu title
        'manage_options', // Capability required to access
        'wp-otp-login-settings', // Menu slug
        [$this, 'render_settings_page']// Callback function to render the page
      );
    }

    function render_settings_page() {
      include_once __DIR__ . "/admin/settings.php";
    }

    function render_login_box() {
      include_once __DIR__ . "/login/login.php";
    }
  }
  interface OTP_Provider {
    public function sendCode($identifier, $message, $code);
  }

  $providers_folder = __DIR__ . "/providers/";
  $files = glob($providers_folder . '*.php');
  foreach ($files as $file) {
    require_once $file;
  }

  $directory = __DIR__ . '/includes/';
  $files = glob($directory . '/*.php');
  foreach ($files as $file) {
    require_once $file;
  }
}

$wpotplogin = WPOTPLogin::instance();

?>
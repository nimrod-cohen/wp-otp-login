<div class="wrap otp-settings-screen">
  <div style="display: grid; grid-template-columns: 1fr max-content; align-items:center">
    <h1>WP OTP Login Settings</h1>
    <small><?php echo "version " . WPOTPLogin::version(); ?></small>
  </div>
  <p>set up wordpress one time password settings</p>
  <span>Instructions:</span>
  <ol>
    <li>Create a custom login page, in your pages section and choose a url for it</li>
    <li>Publish your page and make sure it is accessible to non logged in visitors</li>
    <li>Add a shortcode component to the page: [otp-login-page] </li>
    <li><strong>Finally</strong> Return to this page, check the "Enable OTP Login" checkbox in the General tab and set the login url</li>
  </ol>
  <h2 class="nav-tab-wrapper">
      <a href="#" data-tab-id="tab_general" class="nav-tab nav-tab-active">General</a>
      <a href="#" data-tab-id="tab_exclusion" class="nav-tab">Exclusion List</a>
      <a href="#" data-tab-id="tab_019" class="nav-tab nav-tab">019</a>
      <a href="#" data-tab-id="tab_cio" class="nav-tab">CustomerIO</a>
      <!-- Add more tabs here if needed -->
  </h2>
  <div id="tab_general" class="settings-tab">
    <table class="otp-settings form-table">
      <tr valign="top">
        <th scope="row">Enable OTP Login<br/><span class="description" style="font-weight:100;color:red">do not enable before the custom login page is public and ready</span></th>
        <td><input class="widefat input" type="checkbox" name="wpotp_otp_enabled" <?php echo get_option('wpotp_otp_enabled') == "true" ? "checked" : ""; ?> /></td>
      </tr>
      <tr valign="top">
        <th scope="row">Custom login page</th>
        <td><input class="widefat input" type="text" name="wpotp_custom_login_page" value="<?php echo esc_attr(get_option('wpotp_custom_login_page')); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">OTP message<br/><span class="description" style="font-weight:100;">use %s where the code goes</span></th>
        <td><input class="widefat input" type="text" name="wpotp_message" value="<?php echo esc_attr(get_option('wpotp_message')); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">Phone meta field<br/><span class="description" style="font-weight:100;">the phone has to be retreived from a user meta field</span></th>
        <td><input class="widefat input" type="text" name="wpotp_phone_meta_field" value="<?php echo esc_attr(get_option('wpotp_phone_meta_field')); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">Expriation Days<br/><span class="description" style="font-weight:100;">number of days until session expires</span></th>
        <td><input class="widefat input" type="text" name="wpotp_session_expiration_days" value="<?php echo esc_attr(get_option('wpotp_session_expiration_days')); ?>" /></td>
      </tr>
    </table>
    <p class="submit"><input type="button" class="button button-primary save-settings" value="Save Changes"></p>
  </div>
  <div id="tab_019" class="settings-tab" style="display: none;">
    <table class="otp-settings form-table">
      <tr valign="top">
        <th scope="row">Enable OTP SMS</th>
        <td><input class="widefat input" type="checkbox" name="wpotp_019_enabled" <?php echo get_option('wpotp_019_enabled') == "true" ? "checked" : ""; ?> /></td>
      </tr>
      <tr valign="top">
        <th scope="row">API URL</th>
        <td><input class="widefat input" type="text" name="wpotp_019_api_url" value="<?php echo esc_attr(get_option('wpotp_019_api_url')); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">Username</th>
        <td><input class="widefat input" type="text" name="wpotp_019_username" value="<?php echo esc_attr(get_option('wpotp_019_username')); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">Source<br/><span class="description" style="font-weight:100;">(The name/number shown in the text as sender)</span></th>
        <td>
          <input class="widefat input" type="text" name="wpotp_019_source" value="<?php echo esc_attr(get_option('wpotp_019_source')); ?>" />
        </td>
      </tr>
      <tr valign="top">
        <th scope="row">Bearer</th>
        <td>
          <textarea class="widefat input" type="text" name="wpotp_019_bearer"><?php echo esc_attr(get_option('wpotp_019_bearer')); ?></textarea>
        </td>
      </tr>
    </table>
    <p class="submit"><input type="button" class="button button-primary save-settings" value="Save Changes"></p>
  </div>
  <div id="tab_cio" class="settings-tab" style="display: none;">
    <table class="otp-settings form-table">
      <tr valign="top">
        <th scope="row">Enable OTP emails</th>
        <td><input class="widefat input" type="checkbox" name="wpotp_cio_enabled" <?php echo get_option('wpotp_cio_enabled') == "true" ? "checked" : ""; ?> /></td>
      </tr>
      <tr valign="top">
        <th scope="row">CustomerIO Broadcast ID</th>
        <td><input class="widefat input" type="text" name="wpotp_cio_broadcast_id" value="<?php echo get_option('wpotp_cio_broadcast_id'); ?>" /></td>
      </tr>
    </table>
    <p class="submit"><input type="button" class="button button-primary save-settings" value="Save Changes"></p>
  </div>
  <div id="tab_exclusion" class="settings-tab" style="display: none;">
    <table class="otp-settings form-table">
      <tr valign="top">
        <th scope="row">Exclude these users from logining in with OTP</th>
        <td>
          <textarea rows="10" class="widefat input" type="checkbox" name="wpotp_exclude_list"><?php echo esc_attr(get_option('wpotp_exclude_list')); ?></textarea>
        </td>
      </tr>
    </table>
    <p class="submit"><input type="button" class="button button-primary save-settings" value="Save Changes"></p>
  </div>
</div>
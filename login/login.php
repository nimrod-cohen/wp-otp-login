<?php
$emailEnabled = get_option('wpotp_cio_enabled') == "true";
$phoneEnabled = get_option('wpotp_019_enabled') == "true";
?>
<div class="login-form wp-otp-login">
  <div id="login_error" class="notice notice-error hidden">
  </div>
  <form name="loginform" id="loginform">
    <div data-step-id="1">
      <p class="content">
        <?php if ($emailEnabled && $phoneEnabled) {?>
          <label for="otp_identifier"><?php _e("Email Address or mobile number", "wp-otp-login");?></label><br/>
        <?php } else if ($emailEnabled) {?>
          <label for="otp_identifier"><?php _e("Email Address", "wp-otp-login");?></label><br/>
        <?php } else if ($phoneEnabled) {?>
          <label for="otp_identifier"><?php _e("Phone Number", "wp-otp-login");?></label><br/>
        <?php }?>
        <input type="text" name="otp_identifier" id="otp_identifier" class="input" value="" size="20" autocapitalize="off" autocomplete="email" required="required" style="margin:0 0 16px 0;">
      </p>
      <div class="actions">
        <input type="button" id="request-otp" class="button button-primary button-large" value="<?php _e("Send my code", "wp-otp-login");?>">
        <div class="secondary-area">
          <a href="#" id="go-to-step-2" class="secondary hidden"><?php _e("I received the code", "wp-otp-login");?></a>
        </div>
        <span class="step-loader hidden" />
      </div>
    </div>
    <div class="hidden" data-step-id="2">
      <p class="content">
        <label for="otp_code"><?php _e("Enter OTP code", "wp-otp-login");?></label><br/>
        <input type="text" name="otp_code" id="otp_code" class="input" value="" size="20" autocapitalize="off" autocomplete="off" required="required" style="margin:0 0 16px 0;">
      </p>
      <div class="actions">
        <input type="button" id="submit-otp-code" class="button button-primary button-large" value="<?php _e("Enter site", "wp-otp-login");?>">
        <div class="secondary-area">
          <a href="#" id="back-to-step-1"><?php _e("Back", "wp-otp-login");?></a>
        </div>
        <span class="step-loader hidden" />
      </div>
    </div>
    <div class="hidden" data-step-id="3">
      <p class="content">
      </p>
      <div class="actions">
          <span>&nbsp;</span>
          <div class="secondary-area">
            <span class="step-loader hidden" />
          </div>
      </div>
    </div>
  </form>
</div>

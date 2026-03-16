<?php

class OTP_CutomerIO implements OTP_Provider {
  function __construct() {
    WPOTPLogin::$PROVIDERS[] = $this;
  }

  function sendCode($identifier, $message, $code) {
    // Use CustomerIO if available, otherwise fall back to wp_mail
    if ( class_exists( 'CustomerIO' ) ) {
      $cio = new CustomerIO();
      return $cio->sendBroadcast(get_option('wpotp_cio_broadcast_id'),
        ["code" => $code, "message" => $message], [$identifier]);
    }

    // Fallback: send OTP via wp_mail
    $site_name = get_bloginfo( 'name' );
    $subject   = sprintf( __( 'Your login code for %s', 'wp-otp-login' ), $site_name );
    $body      = ! empty( $message ) ? $message : sprintf( __( 'Your login code is: %s', 'wp-otp-login' ), $code );
    $headers   = [ 'Content-Type: text/plain; charset=UTF-8' ];

    $sent = wp_mail( $identifier, $subject, $body, $headers );

    if ( $sent ) {
      return [ 'error' => false, 'message' => 'Email sent via wp_mail' ];
    }

    return [ 'error' => true, 'message' => 'Failed to send email via wp_mail' ];
  }
}

$otpCustomerIO = new OTP_CutomerIO();
<?php

class OTP_019 implements OTP_Provider {
  function __construct() {
    WPOTPLogin::$PROVIDERS[] = $this;
  }

  function sendCode($identifier, $message, $code) {
    $url = get_option('wpotp_019_api_url');
    $bearer = get_option('wpotp_019_bearer');
    $source = get_option('wpotp_019_source');
    $username = get_option('wpotp_019_username');

    // Set headers
    $headers = array(
      'Content-Type' => 'application/json',
      'Authorization' => "Bearer " . $bearer
    );

    // Set JSON data
    $data = [
      'sms' => [
        'user' => ['username' => $username],
        'source' => $source,
        'destinations' => ['phone' => $identifier],
        'message' => $message,
        'add_dynamic' => '0',
        'add_unsubscribe' => '0',
        'response' => '0',
        'includes_international' => '0'
      ]];

    // Setup the POST request arguments
    $args = array(
      'headers' => $headers,
      'body' => json_encode($data)
    );

    // Send the POST request
    $response = wp_remote_post($url, $args);

    // Check for errors and handle the response
    if (is_wp_error($response)) {
      // Error handling
      $error_message = $response->get_error_message();
      return ["error" => true, "message" => $error_message];
    } else {
      // Successful request
      $body = wp_remote_retrieve_body($response);

      //check for 999 error
      $json = json_decode($body, true);

      if ($json["status"] ?? null !== 0) {
        return ["error" => true, "body" => $body];
      }

      return ["error" => false, "body" => $body];
    }
  }
}

$otp019 = new OTP_019();
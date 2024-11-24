<?php

class OTP_CutomerIO implements OTP_Provider {
  function __construct() {
    WPOTPLogin::$PROVIDERS[] = $this;
  }

  function sendCode($identifier, $message, $code) {
    $cio = new CustomerIO();

    return $cio->sendBroadcast(get_option('wpotp_cio_broadcast_id'),
      ["code" => $code, "message" => $message], [$identifier]);
  }
}

$otpCustomerIO = new OTP_CutomerIO();
<?php

class ApiError extends Api {

  private $message;

  function __construct(string $message = 'Internal Error') {
    $this->message = $message;
    parent::__construct();
  }

  function handle() {
    echo $this->message;
    return STATE_INTERNAL_ERROR;
  }

}
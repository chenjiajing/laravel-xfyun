<?php

namespace ChenJiaJing\XunFeiYun;

use App\Models\NewRetailXunFeiYun;
use ChenJiaJing\XunFeiYun\Tools\ArrayTools;
use ChenJiaJing\XunFeiYun\Tools\CacheTools;
use ChenJiaJing\XunFeiYun\Tools\HttpTools;
use WebSocket\Client;
use XunFeiYun\Exceptions\InvalidArgumentException;
use XunFeiYun\Exceptions\InvalidResponseException;
use XunFeiYun\Exceptions\LocalCacheException;

class BaseXunFeiYun
{

  /**
   * @var
   */
  public $config;

  public $authorization = '';

  /**
   * BaseWeWork constructor.
   * @param array $options
   */
  public function __construct(array $options)
  {
    if (empty($options['api_key'])) {
      throw new InvalidArgumentException("Missing Config -- [api_key]");
    }
    if (empty($options['api_secret'])) {
      throw new InvalidArgumentException("Missing Config -- [api_secret]");
    }
    if (empty($options['app_id'])) {
      throw new InvalidArgumentException("Missing Config -- [app_id]");
    }
    $this->config = new ArrayTools($options);
  }



}
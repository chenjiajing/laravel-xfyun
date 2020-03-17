<?php
namespace ChenJiaJing\XunFeiYun;

use ChenJiaJing\XunFeiYun\Tools\ArrayTools;
use XunFeiYun\Exceptions\InvalidArgumentException;

class BaseXunFeiYun
{

  /**
   * @var
   */
  public $config;

  public $access_token = '';

  /**
   * BaseWeWork constructor.
   * @param array $options
   */
  public function __construct(array $options)
  {
    if (empty($options['corpid'])) {
      throw new InvalidArgumentException("Missing Config -- [corpid]");
    }

    if (empty($options['corpsecret'])) {
      throw new InvalidArgumentException("Missing Config -- [corpid]");
    }
    $this->config = new ArrayTools($options);
  }
}
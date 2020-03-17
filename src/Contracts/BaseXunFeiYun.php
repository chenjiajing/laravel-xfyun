<?php
namespace ChenJiaJing\XunFeiYun;

use ChenJiaJing\XunFeiYun\Tools\ArrayTools;
use ChenJiaJing\XunFeiYun\Tools\CacheTools;
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
    if (empty($options['apikey'])) {
      throw new InvalidArgumentException("Missing Config -- [apikey]");
    }

    if (empty($options['apisecret'])) {
      throw new InvalidArgumentException("Missing Config -- [apisecret]");
    }
    $this->config = new ArrayTools($options);
  }

  /**
   * 注册当前请求接口
   * @param string $url 接口地址
   * @param string $method 当前接口方法
   * @param array $arguments 请求参数
   * @return mixed
   * @throws InvalidResponseException
   * @throws LocalCacheException
   */
  protected function registerApi(&$url, $method, $arguments = [])
  {
    $this->currentMethod = ['method' => $method, 'arguments' => $arguments];
    if (empty($this->access_token)) {
      $this->authorization = $this->getAuthorization();
    }
    return $url = str_replace('AUTHORIZATION', $this->authorization, $url);
  }

  /**
   * @return int|string
   * @throws LocalCacheException
   * @throws InvalidResponseException
   */
  public function getAuthorization(){
    if(!empty($this->authorization)){
      return $this->authorization;
    }

    $cache = $this->config->get('appid').'_authorization';
    $this->authorization = CacheTools::getCache($cache);
    if(!empty($this->authorization)){
      return $this->authorization;
    }
    // 如果缓存中没有，则重新获取
    $api_key = '';
    $signature = '';
    $authorization_origin = "api_key={$api_key},algorithm='hmac-sha256',headers='host date request-line',signature={$signature}";

    if(!empty($result['access_token'])){
      CacheTools::setCache($cache,$result['access_token'],7000);
    }
    return  $this->access_token  =  $result['access_token'];
  }

}
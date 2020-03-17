<?php
namespace ChenJiaJing\XunFeiYun;

use ChenJiaJing\XunFeiYun\Tools\ArrayTools;
use ChenJiaJing\XunFeiYun\Tools\CacheTools;
use ChenJiaJing\XunFeiYun\Tools\HttpTools;
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
    if (empty($this->authorization)) {
      return $this->getAuthorizationUrl($url);
    }
    return $url;
  }

  /**
   * @return int|string
   * @throws LocalCacheException
   * @throws InvalidResponseException
   */
  public function getAuthorizationUrl($url){
    if(!empty($this->authorization)){
      return $this->authorization;
    }

    $cache = $this->config->get('appid').'_authorization';
    $this->authorization = CacheTools::getCache($cache);
    if(!empty($this->authorization)){
      return $this->authorization;
    }
    // 如果缓存中没有，则重新获取
    $api_key = $this->config->get('apikey');
    $host = 'tts-api.xfyun.cn';
    $date = gmstrftime("%a, %d %b %Y %T %Z",time());;
    info($date);
    $signature_origin = "host: {$host}\ndate: {$date}\nrequest-line";
    info($signature_origin);
    $signature_sha= hash_hmac('sha256',$signature_origin,$this->config->get('apisecret'));
    info($signature_sha);
    $signature = base64_encode($signature_sha);
    info($signature);
    $authorization_origin = "api_key={$api_key},algorithm='hmac-sha256',headers='host date request-line',signature={$signature}";
    info($authorization_origin);
    $authorization = base64_encode($authorization_origin);
    info($authorization);
    $url = str_replace(['AUTHORIZATION','DATE','HOST'],[$authorization,$date,$host], $url);
    info($url);
    return  $url;
  }
  /**
   * 以GET获取接口数据并转为数组
   * @param string $url 接口地址
   * @return array
   * @throws InvalidResponseException
   * @throws LocalCacheException
   */
  protected function httpGetForJson($url)
  {
    try {
      return HttpTools::json2arr(HttpTools::get($url));
    } catch (InvalidResponseException $e) {
      if (isset($this->currentMethod['method']) && empty($this->isTry)) {
        if (in_array($e->getCode(), ['40014', '40001', '41001', '42001'])) {
        //  $this->delAccessToken();
          $this->isTry = true;
          return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
        }
      }
      throw new InvalidResponseException($e->getMessage(), $e->getCode());
    }
  }


}
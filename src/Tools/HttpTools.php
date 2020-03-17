<?php


namespace ChenJiaJing\XunFeiYun\Tools;


use XunFeiYun\Contracts\MyCurlFile;
use XunFeiYun\Exceptions\InvalidArgumentException;
use XunFeiYun\Exceptions\InvalidResponseException;
use XunFeiYun\Exceptions\LocalCacheException;

class HttpTools
{

  /**
   * 以get访问模拟访问
   * @param string $url 访问URL
   * @param array $query GET数
   * @param array $options
   * @return boolean|string
   * @throws LocalCacheException
   */
  public static function get($url, $query = [], $options = [])
  {
    $options['query'] = $query;
    return self::doRequest('get', $url, $options);
  }

  /**
   * 以post访问模拟访问
   * @param string $url 访问URL
   * @param array $data POST数据
   * @param array $options
   * @return boolean|string
   * @throws LocalCacheException
   */
  public static function post($url, $data = [], $options = [])
  {
    $options['data'] = $data;
    return self::doRequest('post', $url, $options);
  }

  /**
   * CURL模拟网络请求
   * @param string $method 请求方法
   * @param string $url 请求方法
   * @param array $options 请求参数[headers,data,ssl_cer,ssl_key]
   * @return boolean|string
   * @throws LocalCacheException
   */
  public static function doRequest($method, $url, $options = [])
  {
    $curl = curl_init();
    // GET参数设置
    if (!empty($options['query'])) {
      $url .= (stripos($url, '?') !== false ? '&' : '?') . http_build_query($options['query']);
    }
    // CURL头信息设置
    if (!empty($options['headers'])) {
      curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
    }
    // POST数据设置
    if (strtolower($method) === 'post') {
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, self::_buildHttpData($options['data']));
    }
    // 证书文件设置
    if (!empty($options['ssl_cer'])) if (file_exists($options['ssl_cer'])) {
      curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
      curl_setopt($curl, CURLOPT_SSLCERT, $options['ssl_cer']);
    } else throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_cer]");
    // 证书文件设置
    if (!empty($options['ssl_key'])) if (file_exists($options['ssl_key'])) {
      curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
      curl_setopt($curl, CURLOPT_SSLKEY, $options['ssl_key']);
    } else throw new InvalidArgumentException("Certificate files that do not exist. --- [ssl_key]");
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    list($content) = [curl_exec($curl), curl_close($curl)];
    // 清理 CURL 缓存文件
    if (!empty(CacheTools::$cache_curl)) foreach (CacheTools::$cache_curl as $key => $file) {
      CacheTools::delCache($file);
      unset(CacheTools::$cache_curl[$key]);
    }
    return $content;
  }


  /**
   * POST数据过滤处理
   * @param array $data 需要处理的数据
   * @param boolean $build 是否编译数据
   * @return array|string
   * @throws LocalCacheException
   */
  private static function _buildHttpData($data, $build = true)
  {
    if (!is_array($data)) return $data;
    foreach ($data as $key => $value) if (is_object($value) && $value instanceof \CURLFile) {
      $build = false;
    } elseif (is_object($value) && isset($value->datatype) && $value->datatype === 'MY_CURL_FILE') {
      $build = false;
      $mycurl = new \ChenJiaJing\WeWork\MyCurlFile((array)$value);
      $data[$key] = $mycurl->get();
      array_push(CacheTools::$cache_curl, $mycurl->tempname);
    } elseif (is_string($value) && class_exists('CURLFile', false) && stripos($value, '@') === 0) {
      if (($filename = realpath(trim($value, '@'))) && file_exists($filename)) {
        $build = false;
        $data[$key] = self::createCurlFile($filename);
      }
    }
    return $build ? http_build_query($data) : $data;
  }


  /**
   * 创建CURL文件对象
   * @param $filename
   * @param string $mimetype
   * @param string $postname
   * @return \CURLFile|string
   * @throws LocalCacheException
   */
  public static function createCurlFile($filename, $mimetype = null, $postname = null)
  {
    if (is_string($filename) && file_exists($filename)) {
      if (is_null($postname)) $postname = basename($filename);
      if (is_null($mimetype)) $mimetype = CacheTools::getExtMine(pathinfo($filename, 4));
      if (function_exists('curl_file_create')) {
        return curl_file_create($filename, $mimetype, $postname);
      }
      return "@{$filename};filename={$postname};type={$mimetype}";
    }
    return $filename;
  }



  /**
   * 解析JSON内容到数组
   * @param string $json
   * @return array
   * @throws InvalidResponseException
   */
  public static function json2arr($json)
  {
    $result = json_decode($json, true);
    if (empty($result)) {
      throw new InvalidResponseException('invalid response.', '0');
    }
    info($result['errcode']);
    if (!empty($result['errcode'])) {
   //   throw new InvalidResponseException($result['errmsg'], $result['errcode'], $result);
    }
    return $result;
  }


  /**
   * 数组转xml内容
   * @param array $data
   * @return null|string|string
   */
  public static function arr2json($data)
  {
    $json = json_encode(self::buildEnEmojiData($data), JSON_UNESCAPED_UNICODE);
    return $json === '[]' ? '{}' : $json;
  }

  /**
   * 数组对象Emoji编译处理
   * @param array $data
   * @return array
   */
  public static function buildEnEmojiData(array $data)
  {
    foreach ($data as $key => $value) {
      if (is_array($value)) {
        $data[$key] = self::buildEnEmojiData($value);
      } elseif (is_string($value)) {
        $data[$key] = self::emojiEncode($value);
      } else {
        $data[$key] = $value;
      }
    }
    return $data;
  }


  /**
   * Emoji原形转换为String
   * @param string $content
   * @return string
   */
  public static function emojiEncode($content)
  {
    return json_decode(preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($string) {
      return addslashes($string[0]);
    }, json_encode($content)));
  }



}

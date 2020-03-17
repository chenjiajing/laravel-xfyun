<?php


namespace ChenJiaJing\XunFeiYun\Tools;


use XunFeiYun\Exceptions\LocalCacheException;

class CacheTools
{

    /**
     * 缓存路径
     * @var null
     */
    public static $cache_path = null;

    public static $cache_callable = [
        'set'=>null,  // 设置缓存
        'get'=>null,  // 获取缓存
        'del'=>null,  //  删除缓存
        'put'=>null,  // 写入文件
    ];

  /**网络缓存
   * @var array
   */
    public static $cache_curl = [];

    public static  function getCache($name){
      if (is_callable(self::$cache_callable['get'])) {
        return call_user_func_array(self::$cache_callable['get'], func_get_args());
      }
      $file = self::_getCacheName($name);
      if (file_exists($file) && ($content = file_get_contents($file))) {
        $data = unserialize($content);
        if (isset($data['expired']) && (intval($data['expired']) === 0 || intval($data['expired']) >= time())) {
          return $data['value'];
        }
        self::delCache($name);
      }
      return null;
    }

    public static function delCache($name){
      if (is_callable(self::$cache_callable['del'])) {
        return call_user_func_array(self::$cache_callable['del'], func_get_args());
      }
      $file = self::_getCacheName($name);
      return file_exists($file) ? unlink($file) : true;
    }

    public static function setCache($name,$value = '',$expired = 3600){
      if(is_callable(self::$cache_callable['set'])){
         call_user_func_array(self::$cache_callable['set'],func_get_args());
      }
      $file = self::_getCacheName($name);
      $data = ['name' => $name, 'value' => $value, 'expired' => time() + intval($expired)];
      if (!file_put_contents($file, serialize($data))) {
        throw new LocalCacheException('local cache error.', '0');
      }
      return $file;
    }


  /**
   * 应用缓存目录
   * @param string $name
   * @return string
   */
  private static function _getCacheName($name)
  {
    if (empty(self::$cache_path)) {
      self::$cache_path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
    }
    self::$cache_path = rtrim(self::$cache_path, '/\\') . DIRECTORY_SEPARATOR;
    file_exists(self::$cache_path) || mkdir(self::$cache_path, 0755, true);
    return self::$cache_path . $name;
  }


  /**
   * 根据文件后缀获取文件类型
   * @param string|array $ext 文件后缀
   * @param array $mine 文件后缀MINE信息
   * @return string
   * @throws LocalCacheException
   */
  public static function getExtMine($ext, $mine = [])
  {
    $mines = self::getMines();
    foreach (is_string($ext) ? explode(',', $ext) : $ext as $e) {
      $mine[] = isset($mines[strtolower($e)]) ? $mines[strtolower($e)] : 'application/octet-stream';
    }
    return join(',', array_unique($mine));
  }


  /**
   * 写入文件
   * @param string $name 文件名称
   * @param string $content 文件内容
   * @return string
   * @throws LocalCacheException
   */
  public static function pushFile($name, $content)
  {
    if (is_callable(self::$cache_callable['put'])) {
      return call_user_func_array(self::$cache_callable['put'], func_get_args());
    }
    $file = self::_getCacheName($name);
    if (!file_put_contents($file, $content)) {
      throw new LocalCacheException('local file write error.', '0');
    }
    return $file;
  }

  /**
   * 获取所有文件扩展的类型
   * @return array
   * @throws LocalCacheException
   */
  private static function getMines()
  {
    $mines = self::getCache('all_ext_mine');
    if (empty($mines)) {
      $content = file_get_contents('http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');
      preg_match_all('#^([^\s]{2,}?)\s+(.+?)$#ism', $content, $matches, PREG_SET_ORDER);
      foreach ($matches as $match) foreach (explode(" ", $match[2]) as $ext) $mines[$ext] = $match[1];
      self::setCache('all_ext_mine', $mines);
    }
    return $mines;
  }

}

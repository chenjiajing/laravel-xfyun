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


  /**
   * @return int|string
   * @throws LocalCacheException
   * @throws InvalidResponseException
   */
  public function getAuthorizationUrl($url)
  {
    $host          = 'ws-api.xfyun.cn';
    $time          = date('D, d M Y H:i:s', strtotime('-8 hour')) . ' GMT';
    $authorization = self::sign($time, $host);
    $url           = str_replace(['AUTHORIZATION', 'DATE', 'HOST'], [$authorization, urlencode($time), $host], $url);
    return $url;
  }


  public function sign($time, $host)
  {
    $api_secret           = $this->config->get('api_secret');
    $api_key              = $this->config->get('api_key');
    $signature_origin     = "host: " . $host . "\n";
    $signature_origin     .= 'date: ' . $time . "\n";
    $signature_origin     .= 'GET /v2/tts HTTP/1.1';
    $signature_sha        = hash_hmac('sha256', $signature_origin, $api_secret, true);
    $signature_sha        = base64_encode($signature_sha);
    $authorization_origin = 'api_key="' . $api_key . '", algorithm="hmac-sha256", ';
    $authorization_origin .= 'headers="host date request-line", signature="' . $signature_sha . '"';
    $authorization        = base64_encode($authorization_origin);
    return $authorization;
  }

  /**
   * 以GET获取接口数据并转为数组
   * @param string $url 服务地址
   * @return
   * @throws InvalidResponseException
   * @throws LocalCacheException
   */
  protected function wsForResult($url, $content)
  {
    $client = new Client($url);
    $app_id = $this->config->get('app_id');
    //拼接要发送的信息
    $message = self::createMsgData($app_id, $content);
    try {
      $client->send(json_encode($message, true));
      $date      = date('YmdHis', time());
      $file_name = $date . '.pcm';
      // todo 判断文件夹是否存在
      $path_folder = public_path() . '/audio/';
      if(!is_dir($path_folder)){
        mkdir($path_folder, 0777, true);
      }
      $save_path   = $path_folder . $file_name;
      $audio_file = fopen($save_path, 'ab');
      $response   = $client->receive();
      $response   = json_decode($response, true);

      do {
        if ($response['code']) {
          return $response;
        }
        $audio = base64_decode($response['data']['audio']);
        fwrite($audio_file, $audio);
        $response = $client->receive();
        $response = json_decode($response, true);
        if ($response['data']['status'] == 2) {
          $audio = base64_decode($response['data']['audio']);
          fwrite($audio_file, $audio);
        }
      } while ($response['data']['status'] != 2);
      fclose($audio_file);
      if (file_exists($save_path)) {
        $new_save_path = str_replace('pcm', 'wav', $save_path);
        // -y 表示无需询问,直接覆盖输出文件;
        // -f s16le 用于设置文件格式为 s16le ;
        // -ar 16k 用于设置音频采样频率为 16k;
        // -ac 1 用于设置通道数为 1;
        // -i input.raw 用于设置输入文件为 input.pcm; output.wav 为输出文件.

        // 根据环境返回 ffmpeg路径

        // linux
        if (PATH_SEPARATOR == ':') {
          $ffmpeg_path = 'D:\ffmpeg\ffmpeg.exe';
        //windows
        } else {
          $ffmpeg_path = 'D:\ffmpeg\ffmpeg.exe';
        }
        exec($ffmpeg_path.' -y -f s16le -ar 16k -ac 1 -i ' . $save_path . ' ' . $new_save_path);
      }
      return [
        'code' => 0,
        'msg'  => '合成成功',
        'data' => [
          'audio_name' => $file_name,
          'audio_url'  => './audio/' . $file_name,
        ]
      ];
    } catch (\Exception $e) {
      return [
        'code' => -1,
        'msg'  => $e->getMessage(),
      ];
    } finally {
      $client->close();
    }
  }


  /**
   * 生成要发送的消息体
   * @param $app_id
   * @param $draft_content
   * @return array
   */
  public static function createMsgData($app_id, $draft_content)
  {
    $aue    = 'raw';
    $auf    = 'audio/L16;rate=16000';
    $vcn    = 'xiaoyan';
    $speed  = 10;
    $volume = 50;
    $pitch  = 50;
    $tte    = 'utf8';
    $reg    = '2';
    $ram    = '0';
    $rdn    = '0';
    return [
      'common'   => [
        'app_id' => $app_id,
      ],
      'business' => [
        'aue'    => $aue,
        'auf'    => $auf,
        'vcn'    => $vcn,
        'speed'  => (int)$speed,
        'volume' => (int)$volume,
        'pitch'  => (int)$pitch,
        'tte'    => $tte,
        'reg'    => $reg,
        'ram'    => $ram,
        'rdn'    => $rdn,
      ],
      'data'     => [
        'status' => 2,
        'text'   => base64_encode($draft_content),
      ],
    ];
  }
}
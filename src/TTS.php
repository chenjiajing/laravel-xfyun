<?php

namespace ChenJiaJing\XunFeiYun;

use WebSocket\Client;
use XunFeiYun\Exceptions\InvalidResponseException;
use XunFeiYun\Exceptions\LocalCacheException;

class TTS extends BaseXunFeiYun
{
  /** 文字合成语音接口
   * @throws \XunFeiYun\Exceptions\InvalidResponseException
   * @return false|string
   * @throws \XunFeiYun\Exceptions\LocalCacheException
   */
  public function compose($content)
  {
    $url = "wss://tts-api.xfyun.cn/v2/tts?authorization=AUTHORIZATION&date=DATE&host=HOST";
    $authorization_url  = $this->getAuthorizationUrl($url);
    return $this->wsForResult($authorization_url,$content);
  }

  /**
   * @return int|string
   * @throws LocalCacheException
   * @throws InvalidResponseException
   */
  public function getAuthorizationUrl($url)
  {
    $host          = 'tts-api.xfyun.cn';
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
      $new_file_name = $date . '.wav';
      // todo 判断文件夹是否存在
      $path_folder = config('xfyun.file_save_dir')??public_path() . '/audio/';
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
      $new_save_path = str_replace('pcm', 'wav', $save_path);
      if (file_exists($save_path)) {
        // linux
        if (PATH_SEPARATOR == ':') {
          $ffmpeg_path = config('xfyun.tts.ffmpeg_config.linux_path');
          //windows
        } else {
          $ffmpeg_path = config('xfyun.tts.ffmpeg_config.window_path');
        }
        exec($ffmpeg_path.config('xfyun.tts.ffmpeg_config.instruct') . $save_path . ' ' . $new_save_path);
      }
      return [
        'code' => 0,
        'msg'  => '合成成功',
        'data' => [
          'audio_name' => $new_file_name,
          'audio_url'  => $new_save_path,
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
    $business_config =  config('xfyun.tts.business')??[];
    return [
      'common'   => [
        'app_id' => $app_id,
      ],
      'business' => $business_config,
      'data'     => [
        'status' => 2,
        'text'   => base64_encode($draft_content),
      ],
    ];
  }
}
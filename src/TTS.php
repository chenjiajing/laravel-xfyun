<?php

namespace ChenJiaJing\XunFeiYun;

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
}
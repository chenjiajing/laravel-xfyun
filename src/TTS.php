<?php

namespace ChenJiaJing\XunFeiYun;

class TTS extends BaseXunFeiYun
{

  public function compose($data = [])
  {
    $url = "wss://tts-api.xfyun.cn/v2/tts?authorization=AUTHORIZATION&date=DATE&host=HOST";
    return $this->registerApi($url, __FUNCTION__, func_get_args());
    return $this->httpPostForJson($url, $data);
  }
}
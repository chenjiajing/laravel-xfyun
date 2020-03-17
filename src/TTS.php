<?php

namespace ChenJiaJing\XunFeiYun;

class TTS extends BaseXunFeiYun
{

  public function compose()
  {
    $url = "wss://tts-api.xfyun.cn/v2/tts?authorization=AUTHORIZATION&date=DATE&host=HOST";
     $this->registerApi($url, __FUNCTION__, func_get_args());
    return $this->httpGetForJson($url);
  }
}
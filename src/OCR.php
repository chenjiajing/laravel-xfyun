<?php


namespace ChenJiaJing\XunFeiYun;


class OCR extends BaseXunFeiYun
{

  public function handWriting($image_path)
  {
    $day_time = strtotime('1970-1-1T00:00:00 UTC');
    // OCR手写文字识别服务webapi接口地址
    $api     = "http://webapi.xfyun.cn/v1/service/v1/ocr/handwriting";
    $app_id  = $this->config->get('app_id');
    $api_key = $this->config->get('app_key');;
    $current_time = time();
    $param        = "";
    $check_sum    = "";
    // 语种设置和是否返回文本位置信息
    $Param = array(
      "language" => "cn|en",
      "location" => "false",
    );
    // 文件上传地址
    $image     = file_get_contents($image_path);
    $image     = base64_encode($image);
    $Post      = array(
      'image' => $image,
    );
    $XParam    = base64_encode(json_encode($Param));
    $XCheckSum = md5($api_key . $current_time . $XParam);
    $headers   = array();
    $headers[] = 'X-CurTime:' . $current_time;
    $headers[] = 'X-Param:' . $XParam;
    $headers[] = 'X-Appid:' . $app_id;
    $headers[] = 'X-CheckSum:' . $XCheckSum;
    $headers[] = 'Content-Type:application/x-www-form-urlencoded; charset=utf-8';
    return $this->http_request($api, $Post, $headers);
  }

  /**
   * 发送post请求
   * @param string $url 请求地址
   * @param array $post_data post键值对数据
   * @return string
   */
  public function http_request($url, $post_data, $headers)
  {
    $postdata = http_build_query($post_data);
    $options  = array(
      'http' => array(
        'method'  => 'POST',
        'header'  => $headers,
        'content' => $postdata,
        'timeout' => 15 * 60 // 超时时间（单位:s）
      )
    );
    $context  = stream_context_create($options);
    $result   = file_get_contents($url, false, $context);
    // 错误码链接：https://www.xfyun.cn/document/error-code (code返回错误码时必看)
    return $result;
    return "success";
  }
}
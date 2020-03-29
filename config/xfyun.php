<?php

return [
  'tts' => [
    'business'      => [
      'aue'    => 'raw',
      'auf'    => 'audio/L16;rate=16000',
      'vcn'    => 'xiaoyan',
      'speed'  => 10,
      'volume' => 50,
      'pitch'  => 50,
      'tte'    => 'utf8',
      'reg'    => '2',
      'ram'    => '0',
      'rdn'    => '0',
    ],
    'file_save_dir' => public_path() . '/audio/',
    // 指令提示
    // -y 表示无需询问,直接覆盖输出文件;
    // -f s16le 用于设置文件格式为 s16le ;
    // -ar 16k 用于设置音频采样频率为 16k;
    // -ac 1 用于设置通道数为 1;
    // -i input.raw 用于设置输入文件为 input.pcm; output.wav 为输出文件.
    // 根据环境返回 ffmpeg路径
    'ffmpeg_config' => [
      'window_path' => 'D:\ffmpeg\ffmpeg.exe',  // windows 路径
      'linux_path'  => '/usr/local/ffmpeg/bin/ffmpeg', // linux 路径
      'instruct'    => ' -y -f s16le -ar 16k -ac 1 -i ',  // 操作指令
    ]
  ],
  'OCR' => [

  ]
];
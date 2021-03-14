<?php

header('Access-Control-Allow-Origin: ' . '*');
header('Access-Control-Allow-Method: ' . 'GET,POST,HEAD,OPTIONS');
header('Access-Control-Allow-Headers: ' . 'Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: ' . 'true');
header('Access-Control-Max-Age: ' . '864000');
header("Cache-Control:no-cache,no-store,must-revalidate,max-age=0");
header("Cache-Control:pre-check=0,post-check=0,false");
header("Pragma:no-cache");
header('Content-Type: application/json');
header('Content-Disposition: inline; filename="data.json"');

switch($_SERVER["REQUEST_METHOD"]){
  case 'GET':
    $urls = isset($_GET['url'])?explode("|",$_GET['url']):[];
    $dot = isset($_GET['dot'])?$_GET['dot']:50;
    break;
  case 'POST':
    if(isset($_POST['url'])){
      if(is_array($_POST['url'])){
        $urls = json_decode($_POST['url']);
      }else {
        $urls = explode("|",$_POST['url']);
      }
    }else{
      $urls = [];
    }
    $dot = isset($_POST['dot'])?$_POST['dot']:50;
    break;
  default:
    $urls = [];
    $dot = 50;
}

$number = $dot;

function pickColor($gd_obj, $x, $y){
    $rgb = imagecolorat($gd_obj, $x, $y);
    $array['red'] = ($rgb >> 16) & 0xFF;
    $array['green'] = ($rgb >> 8) & 0xFF;
    $array['blue'] = $rgb & 0xFF;
    return $array;
}

function rgb2hex ( $rgb ) {
	return "#" . implode( "", array_map( function( $value ) {
		return substr( "0" . dechex( $value ), -2 ) ;
	}, $rgb ) ) ;
}

$items = [];

foreach($urls as $url){
  $original_image = imagecreatefromstring(file_get_contents($url));
  $pick_image = imagecreatetruecolor($number, $number);

  imagecopyresampled($pick_image, $original_image, 0, 0, 0, 0, $number, $number, imagesx($original_image), imagesx($original_image));

  $arr = [];
  $hasColorList = [];

  for($y=0; $y<$number; $y++){
    $y_arr = [];
      for($x=0; $x<$number; $x++){
          $rgb = pickColor($pick_image, $x, $y);
          $rgbHex = rgb2hex( [ $rgb['red'],$rgb['green'],$rgb['blue']]);
          if(isset($hasColorList[$rgbHex])){
            $hasColorList[$rgbHex]['count'] += 1;
          }else{
            $hasColorList[$rgbHex] = [
              'code' => $rgbHex,
              'count' => 1,
              'rgb' => [
                'red' => $rgb['red'],
                'green' => $rgb['green'],
                'blue' => $rgb['blue']
              ],
              'tool_url' => 'http://www.kitaq.net/lib/rgb/10to16.cgi?r=' . $rgb['red'] . '&g=' . $rgb['green'] . '&b=' . $rgb['blue']
            ];
          }
          $y_arr[] = $rgbHex;
      }
      $arr[] = $y_arr;
  }

  uasort($hasColorList, function ($value1, $value2) {
     return $value2['count'] - $value1['count'];
  });

  $items[] =  [
    'url' => $url,
    'dot' => $arr,
    'hasColor' => $hasColorList,
  ];
}

echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

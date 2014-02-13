<html>
<head></head>
<body>
<?php
$params = array(
    'method'  => 'user.gettopartists', // API функция
    'user'    => 'Nikluxo_Bkmz', // имя пользователя чьи чарты мы хотим видеть
    'limit' => '100500',
    'period'  => '12month', // период за который мы хотим видеть чарты
    'api_key' => '***REMOVED***', // ваш API key
);
 
$request = file_get_contents('http://ws.audioscrobbler.com/2.0/?' . http_build_query($params, '', '&'));
$xml = new SimpleXMLElement($request);
//$charts = '';
$i = $xml->topartists->artist->count();
//foreach ($xml->topartists->artist as $artist)
//{
//    $charts .= '<li>';
//    $charts .= '<a href="' . $artist->artist->url . '">' . $artist->name . '</a> — ';
//    $charts .= $artist->name ;
//    $charts .= '</li>' . "\n";
//
//}
 
//echo '<ul>' . $charts . '</ul>';
echo '<span>count = '. $i . '</span>';

?>
</body>
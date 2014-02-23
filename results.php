<html>
    <head>
        <?php
            ini_set('max_execution_time', 300);
            require 'gd.php';
            $user1 = filter_input(INPUT_POST, 'user1', FILTER_SANITIZE_SPECIAL_CHARS);
            $user2 = filter_input(INPUT_POST, 'user2', FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
        <title>Euler.fm | <?php echo $user1 . ' and '.  $user2;?></title>
        <link type="text/css" rel="stylesheet" href="css/style.css">
        <link rel="shortcut icon" href="favicon.png" />

    </head>
    <body>

        <h2>Euler.fm</h2>
        <form method="POST" action="results.php">
            <p>
                <label for="input_user1">first user:&nbsp&nbsp&nbsp&nbsp&nbsp
                    <input id="input_user1" name="user1" type="text" value="<?php echo $user1;?>"/>
                </label>
            </p>
            <p>
                <label for="input_user2">second user:
                    <input id="input_user2" name="user2" type="text" value="<?php echo $user2;?>"/>
                </label>
            </p>
            <p>
                <input type="submit"/>
            </p>
        </form>
    <?php

        function xml2array($xmlObject, $out = array()) {
            foreach ($xmlObject as $node) {
                $out[$node['name']] = intval($node['playcount']);
            }
            return $out;
        }

        function multiRequest($data, $options = array()) {
            // array of curl handles
            $curly = array();
            // data to be returned
            $result = array();
            // multi handle
            $mh = curl_multi_init();

            // loop through $data and create curl handles
            // then add them to the multi-handle
            foreach ($data as $id => $d) {

                $curly[$id] = curl_init();
                $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
                curl_setopt($curly[$id], CURLOPT_URL,            $url);
                curl_setopt($curly[$id], CURLOPT_HEADER,         0);
                curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
    //            curl_setopt($curly[$id], CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
                // post?
                if (is_array($d)) {
                    if (!empty($d['post'])) {
                        curl_setopt($curly[$id], CURLOPT_POST,       1);
                        curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
                    }
                }
                // extra options?
                if (!empty($options)) {
                    curl_setopt_array($curly[$id], $options);
                }
                curl_multi_add_handle($mh, $curly[$id]);
            }
            // execute the handles

            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while($running > 0);  
            // get content and remove handles
            foreach($curly as $id => $c) {
                $request = curl_multi_getcontent($c);
    //            $xml = new SimpleXMLElement($request);
                $xml = json_decode($request, true);
    //            $result[$id] =  xml2array($xml->topartists->artist);
                $result[$id] = xml2array($xml['topartists']['artist']);
                curl_multi_remove_handle($mh, $c);
            }
            // all done
            curl_multi_close($mh);
            return $result;
        }

        $params1 = array(
            'method'  => 'user.gettopartists', // API функция
            'user'    => $user1, // имя пользователя чьи чарты мы хотим видеть
            'limit'   => '100500',
            'period'  => 'overall', // период за который мы хотим видеть чарты
            'api_key' => '***REMOVED***', // ваш API key
            'format'  => 'json'
        );

        $params2 = array(
            'method'  => 'user.gettopartists', // API функция
            'user'    => $user2, // имя пользователя чьи чарты мы хотим видеть
            'limit'   => '100500',
            'period'  => 'overall', // период за который мы хотим видеть чарты
            'api_key' => '***REMOVED***', // ваш API key
            'format'  => 'json'
        );

        $data = array(
            'http://ws.audioscrobbler.com/2.0/?' . http_build_query($params1, '', '&'),
            'http://ws.audioscrobbler.com/2.0/?' . http_build_query($params2, '', '&')
        );    

        $r = multiRequest($data);
        
        $i = count($r[0]);
        $j = count($r[1]);
//        var_dump($r);
        $result = array_intersect_key($r[0], $r[1]);
        
        foreach ($result as $key => $value) {
            $result[$key] = min($r[0][$key], $r[1][$key]);
        }
        arsort($result);
        $k = count($result);
        
        echo '<span>results for <strong>' . $user1 . '</strong> and <strong>' . $user2 . '</strong></span><br>';
        echo '<span>artist count = '. $i . ' and ' . $j . '</span><br>';
        echo '<span>'. $k . ' artists in common</span><br>';

        if ($i < $j) {
            list($user2, $user1) = array($user1, $user2); // swap users
        }
        
        $filename = 'images/' . $user1 . '-' . $user2 . '.png';

        echo '<span class="red">red</span> - ' . $user1 . '<br><span class = "blue">blue</span> - ' . $user2 . '<br>';
        echo '<img src = "' . $filename . '">';
    ?>
        
    <?php
        makeImage($i, $j, $k, $filename, array_diff($r[0], $result), array_diff_key($r[1], $result), $result);
    ?>
    </body>
</html>
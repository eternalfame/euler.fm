<html>
    <head>
        <?php
            ini_set('max_execution_time', 300);
            require 'gd.php';
            $user1 = filter_input(INPUT_GET, 'user1', FILTER_SANITIZE_SPECIAL_CHARS);
            $user2 = filter_input(INPUT_GET, 'user2', FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
        <title>Euler.fm | <?php echo $user1 . ' and '.  $user2;?></title>
        <link type="text/css" rel="stylesheet" href="css/style.css">
        <link rel="shortcut icon" href="favicon.png" />

    </head>
    <body>

        <h2>Euler.fm</h2>
        <form method="GET" action="results.php">
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
		function xml2array($artistList): array {
			$out = [];
			foreach ($artistList as $node) {
				$out[$node['name']] = (int) $node['playcount'];
			}
			return $out;
		}
		
		function multiRequest(array $urls, array $options = []): array {
			$curly = [];
			$result = [];
			$mh = curl_multi_init();

			foreach ($urls as $id => $url) {
				$curly[$id] = curl_init($url);
				curl_setopt_array($curly[$id], [
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_HEADER => 0
				] + $options);
				curl_multi_add_handle($mh, $curly[$id]);
			}

			do {
				curl_multi_exec($mh, $running);
			} while ($running > 0);

			foreach ($curly as $id => $c) {
				$response = curl_multi_getcontent($c);
				$json = json_decode($response, true);
				$result[$id] = xml2array($json['artists']['artist'] ?? []);
				curl_multi_remove_handle($mh, $c);
			}

			curl_multi_close($mh);
			return $result;
		}

		function buildParams(string $user, int $pages = 5): array {
			$baseParams = [
				'method' => 'library.getArtists',
				'limit' => '2000',
				'period' => 'overall',
				'api_key' => '***REMOVED***',
				'format' => 'json'
			];

			$urls = [];
			for ($i = 1; $i <= $pages; $i++) {
				$params = $baseParams + ['user' => $user, 'page' => $i];
				$urls[] = 'http://ws.audioscrobbler.com/2.0/?' . http_build_query($params);
			}
			return $urls;
		}

		$allUrls = array_merge(
			buildParams($user1, 3),
			buildParams($user2, 3)
		);

		$responses = multiRequest($allUrls);
        
		$arr1 = array_merge(...array_slice($responses, 0, 3));
		$arr2 = array_merge(...array_slice($responses, 3, 3));
		
		$count1 = 0;
		foreach ($arr1 as $name => $val) {
			$count1 += $val;
		}
		$count2 = 0;
		foreach ($arr2 as $name => $val) {
			$count2 += $val;
		}

		$commonArtists = array_intersect_key($arr1, $arr2);
		$commonArtistListenCount = 0;
		foreach ($commonArtists as $name => &$val) {
			$val = min($arr1[$name], $arr2[$name]);
			$commonArtistListenCount += $val;
		}
		unset($val);
		arsort($commonArtists);
		

		echo "<span>results for <strong>$user1</strong> and <strong>$user2</strong></span><br>";
		echo "<span>artist count = " . count($arr1) . " and " . count($arr2) . "</span><br>";
		echo "<span>listen count = $count1 and $count2</span><br>";
		echo "<span>" . count($commonArtists) . " artists in common</span><br>";
		echo "<span>" . $commonArtistListenCount . " listens to common artists</span><br>";

		if ($count1 < $count2) {
			list($user1, $user2) = [$user2, $user1];
		}

		echo "<span class='red'>red</span> - $user1<br><span class='blue'>blue</span> - $user2<br>";

		makeImage($count1, $count2, $commonArtistListenCount, array_diff_key($arr1, $commonArtists), array_diff_key($arr2, $commonArtists), $commonArtists);
	?>
	
    </body>
</html>
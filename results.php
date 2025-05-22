<?php
	error_reporting(E_ERROR | E_PARSE);
	ini_set('max_execution_time', 300);
	require 'gd.php';
	$user1 = filter_input(INPUT_GET, 'user1', FILTER_SANITIZE_SPECIAL_CHARS);
	$user2 = filter_input(INPUT_GET, 'user2', FILTER_SANITIZE_SPECIAL_CHARS);
	if (isset($_GET['period'])) {
		$period = $_GET["period"];
		if (!in_array($period, ['12month', '6month', '3month', '1month', '7day'])) {
			$period = 'overall';
		}
	} else {
		$period = 'overall';
	}
	
	$MAX_PER_PAGE = 500;

	function xml2array($artistList): array {
		$out = [];
		foreach ($artistList as $node) {
			$out[$node['name']] = $node;
		}
		return $out;
	}
	
	function multiRequest(array $urls, array $options = []): array {		
		$mh = curl_multi_init();

		$requests = [];
		foreach ($urls as $id => $url) {
			$requests[$id] = curl_init($url);
			curl_setopt_array($requests[$id], [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false
			] + $options);
			curl_multi_add_handle($mh, $requests[$id]);
		}
		
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running > 0);

		$result = [];
		foreach ($requests as $request) {
			$response = curl_multi_getcontent($request);
			$result[] = json_decode($response, true);
			
			curl_multi_remove_handle($mh, $request);
			curl_close($request);
		}
		curl_multi_close($mh);
		return $result;
	}
		
	function parseMultiRequest($result) {
		$result2 = [];
		foreach($result as $id => $data) {
			$result2[$id] = xml2array($data['topartists']['artist'] ?? []);
		}
		return $result2;
	}
		
	function buildParams(string $method, string $user, array $options = [], int $pages = 5): array {
		$baseParams = [
			'user' => $user,
			'method' => $method,
			'api_key' => getenv("LASTFM_TOKEN"),
			'format' => 'json'
		] + $options;

		$urls = [];
		for ($i = 1; $i <= $pages; $i++) {
			$params = $baseParams + ['page' => $i];
			$urls[] = 'https://ws.audioscrobbler.com/2.0/?' . http_build_query($params);
		}
		return $urls;
	}
	
	function getArtistCount($user1, $user2, $period) {
		// stupid last.fm API does not have a method to retrieve the total artists count for a period.
		$urls = array_merge(
			buildParams('user.getTopArtists', $user1, ['period' => $period, 'limit' => 1], 1), 
			buildParams('user.getTopArtists', $user2, ['period' => $period, 'limit' => 1], 1)
		);
		
		$responses = multiRequest($urls);		
		return [(int)$responses[0]['topartists']['@attr']['total'], (int)$responses[1]['topartists']['@attr']['total']];
	}
	
	[$user1ArtistCount, $user2ArtistCount] = getArtistCount($user1, $user2, $period);

	$user1PageCount = ceil($user1ArtistCount / $MAX_PER_PAGE);
	$user2PageCount = ceil($user2ArtistCount / $MAX_PER_PAGE);
	
	$allUrls = array_merge(
		buildParams('user.getTopArtists', $user1, ['period' => $period, 'limit' => $MAX_PER_PAGE], $user1PageCount),
		buildParams('user.getTopArtists', $user2, ['period' => $period, 'limit' => $MAX_PER_PAGE], $user2PageCount)
	);

	$responses = parseMultiRequest(multiRequest($allUrls));
	
	$arr1 = array_merge(...array_slice($responses, 0, $user1PageCount));
	$arr2 = array_merge(...array_slice($responses, $user1PageCount, $user2PageCount));
	
	$count1 = 0;
	foreach ($arr1 as $name => $val) {
		$count1 += (int)$val['playcount'];
	}
	$count2 = 0;
	foreach ($arr2 as $name => $val) {
		$count2 += (int)$val['playcount'];
	}
	
	$commonArtists = array_intersect_key($arr1, $arr2);
	$commonArtistListenCount = 0;

	foreach ($commonArtists as $name => &$val) {
		$val['playcount'] = min($arr1[$name]['playcount'], $arr2[$name]['playcount']);
		$commonArtistListenCount += (int)$val['playcount'];
	}
	unset($val);
	array_multisort(array_column($commonArtists, 'playcount'), SORT_DESC, $commonArtists);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Euler.FM </title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="css-less/basics.less" rel="stylesheet/less" type="text/css">
        <script src="js/less.js"></script>
    </head>
    <body class="red">
        <header>
            <div class="container">
                <div class="logo">
                    <a href="">e<b>⋂</b>ler.fm</a>
                </div>
                <p>
                    the newest <span>geek</span> way to learn your music compatibility
                    with your friends
                </p>
            </div>
        </header>
        <div class="container">
            <section class="form">
				<form method="GET" action="results.php">
					<label>
						<span>enter the 1st username</span>
						<input type="text" name='user1' value="<?php echo $user1;?>"/>
					</label>                
					<b>⋂</b>
					<label>
						<span>enter the 2nd username</span>
						<input type="text" name='user2' value="<?php echo $user2;?>"/>
					</label>                 
					<input type="hidden" name='period' value="<?php echo $period; ?>"/>
					<input type="submit" value="GO!" id="getResults">
				</form>
            </section>

            <section class="about">
                <h2>About</h2>
                <p>
                    <a href="/">Euler.fm</a> helps you to... Now that we know who you are, I know who I am. I'm not a mistake! It all makes sense! In a comic, you know how you can tell who the arch-villain's going to be? He's the exact opposite of the hero. And most times they're friends, like you and me! I should've known way back when... You know why, David? Because of the kids. They called me Mr Glass.
                </p>
            </section>

            <section class="results">
                <h2>Results</h2>

                <ul class="res-period">
                    <li <?php if ($period == 'overall') {echo 'class="active"';}?>>
                        <a href="?period=overall<?php echo "&user1=" . $user1 . "&user2=" . $user2?>">overall</a>
                    </li>
                    <li <?php if ($period == '12month') {echo 'class="active"';}?>>
                        <a href="?period=12month<?php echo "&user1=" . $user1 . "&user2=" . $user2?>">12 months</a>
                    </li>
                    <li <?php if ($period == '6month') {echo 'class="active"';}?>>
                        <a href="?period=6month<?php echo "&user1=" . $user1 . "&user2=" . $user2?>">6 months</a>
                    </li>
                    <li <?php if ($period == '3month') {echo 'class="active"';}?>>
                        <a href="?period=3month<?php echo "&user1=" . $user1 . "&user2=" . $user2?>">3 months</a>
                    </li>
                    <li <?php if ($period == '1month') {echo 'class="active"';}?>>
                        <a href="?period=1month<?php echo "&user1=" . $user1 . "&user2=" . $user2?>">last month</a>
                    </li>
                    <li <?php if ($period == '7day') {echo 'class="active"';}?>>
                        <a href="?period=7day<?php echo "&user1=" . $user1 . "&user2=" . $user2?>">last week</a>
                    </li>
                </ul>
				
				<div style='text-align:center; max-width: 100%'>                
					<?php
						makeImage($count1, $count2, $commonArtistListenCount, array_diff_key($arr1, $commonArtists), array_diff_key($arr2, $commonArtists), $commonArtists);
					?>
				</div>
				                
                <div class="res-users">
                    <div class="res-users-1">
                        <h4>
                            <a href="https://www.last.fm/user/<?php echo $user1;?>"><?php echo $user1;?></a> only (<?php echo $user1ArtistCount;?> artists)
                        </h4>
                        <ul>
							<?php
								$i = 0;
								foreach ($arr1 as $name => $val) {
									echo ('<li>
										<span>' . $val['playcount'] . '</span>
										<a href="' . $val['url'] . '">' . $val['name'] .'</a>
									</li>');
									if (++$i == 10) break;
								}
							?>
                        </ul>
                    </div>
                    
                    <div class="res-users-n">
                        <h4>
                            together (<?php echo count($commonArtists);?> artists)
                        </h4>
                        <ul>
							<?php
								$i = 0;
								foreach ($commonArtists as $name => $val) {
									echo ('<li>
										<span>' . $arr1[$name]['playcount'] . '</span>
										<a href="' . $val['url'] . '">' . $val['name'] .'</a>
										<span>' . $arr2[$name]['playcount'] . '</span>
									</li>');
									if (++$i == 10) break;
								}
							?>
                        </ul>
                    </div>
                    
                    <div class="res-users-2">
                        <h4>
                            <a href="https://www.last.fm/user/<?php echo $user2;?>"><?php echo $user2;?></a> only (<?php echo $user2ArtistCount;?> artists)
                        </h4>
                        <ul>
						<?php
							$i = 0;
								foreach ($arr2 as $name => $val) {
									echo ('<li>
										<span>' . $val['playcount'] . '</span>
										<a href="' . $val['url'] . '">' . $val['name'] .'</a>
									</li>');
									if (++$i == 10) break;
								}
							?>
                        </ul>
                    </div>
                </div>
            </section>    
            <section class="buttons">
                <a class="button" href="/">Wow! It's like magic! Let's do it one more time!</a>
            </section>
        </div>
    </body>
</html>
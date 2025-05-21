<?php
	if (isset($_GET['period'])) {
		$period = $_GET["period"];
		if (!in_array($period, ['12month', '6month', '3month', '1month', '7day'])) {
			$period = 'overall';
		}
	} else {
		$period = 'overall';
	}
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
						<input type="text" name="user1">
					</label>                
					<b>⋂</b>
					<label>
						<span>enter the 2nd username</span>
						<input type="text" name="user2">
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
        </div>
    </body>
</html>

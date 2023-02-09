<?php

include __DIR__.'/vendor/autoload.php';
include 'config2.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\MessageBuilder;

date_default_timezone_set('America/chicago');

$discord = new Discord([
    'token' => $TOKEN,
]);

// Check connection
if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $conn -> connect_error;
  exit();
}

function wh_log($log_msg)
{                 
	$log_filename = "log";
	if (!file_exists($log_filename)) 
	{                 
		mkdir($log_filename, 0777, true);
	}             
	$log_file_data = $log_filename.'/log_' . date('d-M-Y') . '.log';
	// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
	file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
}

function db_log($message,$action)
{
        global $conn;
        $userName = filter_var($message->author->username,FILTER_SANITIZE_STRING);
        $userNick = filter_var($message->member->nick,FILTER_SANITIZE_STRING);
        if (isset($userNick) && $userNick != '') {
                $lowName = strtolower($userNick);
                $usedName = $userNick;
        } else {
                $lowName = strtolower($userName);
                $usedName = $userName;
        }
        $updated_at = date("D d-M-Y h:i:sa");
        $sql = ("INSERT INTO `junglist_stats` (`command`,`user`,`p_updated_at`) VALUES (
                '".$action."',
                '".mysqli_real_escape_string($conn, $usedName)."','".
                $updated_at."'
        ) ON DUPLICATE KEY UPDATE
        `command_count`=command_count+1"
        );
        //var_dump($sql);
        $result = $conn->query($sql);
        //var_dump($result);
}


function clean_string($string) {
  $s = trim($string);
  $s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters
  $s = preg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space
  return $s;
}

function week_number( $date = 'today' )
{
    return ceil( date( 'j', strtotime( $date ) ) / 7 );
}

$discord->on('ready', function ($discord) {
	global $GUILD_ID;
	global $NAME;
	echo "$NAME is ready!", PHP_EOL;
	$guild = $discord->guilds[$GUILD_ID];
	$guild->members[$discord->id]->setNickname($NAME);

    //$discord->getLoop()->addPeriodicTimer(10, function() use ($discord) {
    //});


    // Listen for messages.
    $discord->on('message', function ($message, $discord) {

        $weekDays = array('!monday','!tuesday','!wednesday','!thursday','!friday','!saturday','!sunday');
	$letters = array ('a' => '🇦', 'b'=>'🇧', 'c'=>'🇨', 'd'=>'🇩','e'=>'🇪','f'=>'🇫','g'=>'🇬','h'=>'🇭','i'=>'🇮','j'=>'🇯','k'=>'🇰','l'=>'🇱','m'=>'🇲','n'=>'🇳','o'=>'🇴','p'=>'🇵','q'=>'🇶','r'=>'🇷','s'=>'🇸','t'=>'🇹','u'=>'🇺','v'=>'🇻','w'=>'🇼','x'=>'🇽','y'=>'🇾','z'=>'🇿');
	
	if ($message->content && ! $message->author->bot) {
		global $conn;
		$userName = filter_var($message->author->username,FILTER_SANITIZE_STRING);
		$userNick = filter_var($message->member->nick,FILTER_SANITIZE_STRING);
		$theMessage = filter_var($message->content,FILTER_SANITIZE_STRING);
		$theMessage = mysqli_real_escape_string($conn, $theMessage);
		if (isset($userNick) && $userNick != '') {
			$lowName = strtolower($userNick);
			$usedName = $userNick;
		} else {
			$lowName = strtolower($userName);
			$usedName = $userName;
		}
		$updated_at = date("D d-M-Y h:i:sa");
		$sql = ("INSERT INTO `last_seen` (`username`,`user_id`, `the_message`, `updated_at`) VALUES (
			'".mysqli_real_escape_string($conn, $usedName)."',".
			mysqli_real_escape_string($conn, $message->user_id).",'".
			mysqli_real_escape_string($conn, $message->content)."',
			'".$updated_at."'
		) ON DUPLICATE KEY UPDATE 
		`the_message`='".mysqli_real_escape_string($conn, $message->content)."',
		`message_count`=message_count+1,
		`updated_at`='".$updated_at."'"
		);
		$result = $conn->query($sql);
	}

	if (str_contains(strtolower($message->content),'!stats') && ! $message->author->bot && $message->author->username == 'Section31') {
		global $conn;
		wh_log("[ junglist -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
		db_log($message,"!stats");
		$bots = ['junglist', 'bot'];
		$embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
		$whichBot = explode(" ", $message->content);
		if (in_array($whichBot[1], $bots)) {
			$bot = $whichBot[1];
			//var_dump($bot);
			exec("ps ax | grep $bot.php", $results);
			$botpid = explode(' ',$results[0]);
			$thePid = $botpid[0];
			//var_dump($thePid);
			unset($results);
			exec("ps -p $thePid -o etime",$results);
			//var_dump($results);
			$uptime = $results[1];
			if (str_contains($uptime,'-')) {
				$days = explode('-',$uptime);
				$totalUptime = "$days[0] days ";
				$remainingTime = explode(':',$days[1]);
				$totalUptime .= "$remainingTime[0] hours $remainingTime[1] minutes $remainingTime[2] seconds";

			} else {
				$remainingTime = explode(':',$uptime);
				if (sizeof($remainingTime) == 2) {
					$totalUptime = "$remainingTime[0] minutes $remainingTime[1] seconds";
				}
				if (sizeof($remainingTime) == 3) {
					$totalUptime = "$remainingTime[0] hours $remainingTime[1] minutes $remainingTime[2] seconds";
				}
			}
			$embed->setTitle("::: $bot Stats :::" )
	 ->setType($embed::TYPE_RICH)
	 ->setDescription('')
	 ->setColor('blue');
			$embed->addField([
				'name' => "Uptime",
				'value' => "$totalUptime",
				'inline' => true,
			]);
			$embed->addField([
				'name' => "Pid",
				'value' => "$thePid",
				'inline' => true,
			]);
			$sql = "SELECT * FROM last_seen order by message_count";
			$result = $conn->query($sql);
			$rowcount=mysqli_num_rows($result);
			$embed->addField([
				'name' => "Total users",
				'value' => "$rowcount",
				'inline' => false,
			]);
			unset($rowcount);
			unset($result);
			$sql = "SELECT * FROM last_seen where lastupdated > now() - interval 24 hour";
			$result = $conn->query($sql);
			$rowcount=mysqli_num_rows($result);
			$embed->addField([
				'name' => "Active users (24/hrs)",
				'value' => "$rowcount",
				'inline' => true,
			]);
			$sql = "SELECT * FROM last_seen order by message_count DESC limit 5";
			$result = $conn->query($sql);
			$theUsers='';
			while ($row = $result->fetch_assoc()) {
					$messageCount = $row['message_count'];
					$topUser = $row['username'];
					$theUsers .="$topUser - $messageCount \n"; 
			}
			$embed->addField([
				'name' => "Top Users",
				'value' => $theUsers,
				'inline' => false,
			]);
			if ($bot == 'junglist') {
				$sql = "SELECT * FROM junglist_stats order by command_count DESC";
				$result = $conn->query($sql);
				$theCount='';
				while ($row = $result->fetch_assoc()) {
					$actionCount = $row['command_count'];
					$action = $row['command'];
					$theCount .="$action - $actionCount \n"; 
				}
				$embed->addField([
					'name' => "Command Stats",
					'value' => $theCount,
					'inline' => false,
				]);
			}

			if ($bot == 'bot') {
				$sql = "SELECT * FROM bot_stats order by command_count DESC";
				$result = $conn->query($sql);
				$theCount='';
				while ($row = $result->fetch_assoc()) {
					$actionCount = $row['command_count'];
					$action = $row['command'];
					$theCount .="$action - $actionCount \n";
				}
				$embed->addField([
					'name' => "Command Stats",
					'value' => $theCount,
					'inline' => false,
				]);
			}

			$message->channel->sendEmbed($embed);
		}
	}

        if (str_contains(strtolower($message->content),'honk') && ! $message->author->bot) {
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"!honk");
            $honk = '🦤';;
            $honk2 = '📣';
            $message->react($honk)->done(function () {});
            $message->react($honk2)->done(function () {});
	}

        if (str_contains(strtolower($message->content),'donate') && ! $message->author->bot) {
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"!donate");
            $donate = 'https://www.paypal.com/webapps/shoppingcart?flowlogging_id=f8085355e27da&mfid=1669652658200_f8085355e27da#/checkout/openButton';
            $message->reply($donate)->done(function () {});
	}

	$kickWords = ['pendulum','dubstep','live?'];
	$messageWords = explode(" ",strtolower($message->content));
	foreach ($messageWords as $messageWord) {	
		if (in_array($messageWord,$kickWords) && ! $message->author->bot) {
			wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
			db_log($message,$messageWord);
			$guild = $discord->guilds->get('id', $message->guild_id);
			$message->reply("Hey  ".$message->author->username ." ! Watch your mouth!");
			//message->reply($message->author->id)
			$x = '❌';
			$message->react($x)->done(function () {});
			//	$guild->members->kick($message->member);
		}
	}
	
        if (str_contains(strtolower($message->content),'testkickword') && ! $message->author->bot) {
		wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
		$x = '❌';
		$message->react($x)->done(function () {});
		$message->channel->setPermissions($message->member, [], [10], 'Badmouthing');

        }

        if (str_contains(strtolower($message->content),'clown') && ! $message->author->bot) {
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"clown");
            $clown = '🤡';
            $message->react($clown)->done(function () {});
        }

        if (str_contains(strtolower($message->content),'!date') && ! $message->author->bot) {
	$channel = $discord->getChannel('766141517005455396');
	$channel->broadcastTyping();
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("l d-M-Y h:i:sa") ." ]");
	    db_log($message,"!date");
	    #$theDate = "Current Bassdrive time is: " . date("l") ". date("h:i:s a")." ". date("Y-m-d");
	    $theDate = "Current Bassdrive time is: " . date("l d-M-Y h:i:sa");
            $message->reply($theDate)->done(function () {});
        }

        if (str_contains(strtolower($message->content),'!8ball') && ! $message->author->bot) {
	$channel = $discord->getChannel('766141517005455396');
	$channel->broadcastTyping();
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"8ball");
	    $ballAnswers = ['It is certain', 'Reply hazy, try again', 'Don’t count on it', 'It is decidedly so', 'Ask again later', 'My reply is no', 'Without a doubt', 'Better not tell you now', 'My sources say no', 'Yes definitely', 'Cannot predict now	', 'Outlook not so good', 'You may rely on it', 'Concentrate and ask again', 'Very doubtful', 'As I see it, yes', 'Most likely','Outlook good', 'Yes', 'Signs point to yes'];
	    $ballTotals = sizeof($ballAnswers);
	    $theAnswer = rand(0, $ballTotals);
	    $Ball = $message->author->username .' shakes the the 🎱  --  '. $ballAnswers[$theAnswer];
            $message->reply($Ball);
        }

        if (str_contains(strtolower($message->content),'junglist?') && ! $message->author->bot) {
	$channel = $discord->getChannel('766141517005455396');
	$channel->broadcastTyping();
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"junglist?");
            $beard = 'Lol, I am a beard!';
            $message->reply($beard);
        }

        if (strtolower($message->content) =='b0h b0h b0h' && ! $message->author->bot) {
	$channel = $discord->getChannel('766141517005455396');
	$channel->broadcastTyping();
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"b0h b0h b0h");
	    $boh = 'I heard b0h b0h b0h was h0b h0b h0b or ke ke ke';
            $message->react($letters['h'])->done(function () {});
            $message->react($letters['o'])->done(function () {});
            $message->react($letters['b'])->done(function () {});
            $message->reply($boh);
        }

        if (strtolower($message->content) == 'all of you!' && ! $message->author->bot) {
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"all of you");
	    $allOfYou = 'ALL OF YOU LISTEN TO MEE, DON\'T DISTURB HERE. I WILL CALL POLICE CATCH YOU, DON\'T COME TO  MY BANGOLOW HOUSE, UNDERSTAND, O.K. I HATE ALL OF YOU.';
            $message->reply($allOfYou);
        }

        if (strtolower($message->content) =='do a flip' && ! $message->author->bot) {
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"do a flip");
	    $flip = '/( .□.) ︵╰(゜益゜)╯︵ /(.□. /)';
            $message->reply($flip);
        }

        if (strtolower($message->content) =='flip a table' && ! $message->author->bot) {
		wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
		db_log($message,"flip a table");
		$table ="(ﾉ´□｀)ﾉ ┫:･’∵:.┻┻:･’.:┣∵･:. ┳┳";
		$message->reply($table);
        }
	
        if (strtolower($message->content) =='yo yo yo' && ! $message->author->bot) {
		wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
		db_log($message,"yo yo yo");
		$mtv ="I heard yo yo yo was MTV Raps!";
		$message->reply($mtv);
        }

        if (strtolower($message->content) =='you guys' && ! $message->author->bot) {
		wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
		db_log($message,"you guys");
		$youGuys ="you guys are insane";
		$message->reply($youGuys);
        }

	$trackIdResponses = ['Track ID: Wet Arena - Pimple Bee', 'due to delinquent account tune ID privileges will be revoked in 30 days unless payment is received'];
	if (str_contains(strtolower($message->content),'tune id') && ! $message->author->bot) { 
		wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
		db_log($message,"tune id");
		$randResponse = mt_rand(0,count($trackIdResponses)-1);
		$message->reply($trackIdResponses[$randResponse]);
	}
	if (str_contains(strtolower($message->content),'track id') && ! $message->author->bot) { 
		wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
		db_log($message,"track id");
		$randResponse = mt_rand(0,count($trackIdResponses)-1);
		$message->reply($trackIdResponses[$randResponse]);
	}
    


	$jokes = ['yow','!hit'];
	$messageWords = explode(" ",strtolower($message->content));
	foreach ($messageWords as $messageWord) {	
		if (in_array($messageWord, $jokes) && ! $message->author->bot) {
			wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
			db_log($message,"yow");
			global $conn;
			$sql = "SELECT joke FROM jokes order by rand() limit 1";
			$result = $conn->query($sql);
			if ($result) {
				$row = $result->fetch_assoc();
				if ($row['joke'] != null) {
					$message->reply($row['joke']);
				}
			} else {
				$message->reply("hmmm, something went wrong");

			}
		}
	}

        if (str_contains(strtolower($message->content),'vibes') && ! $message->author->bot) {
	    wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"vibes");
            $message->react($letters['v'])->done(function () {});
            $message->react($letters['i'])->done(function () {});
            $message->react($letters['b'])->done(function () {});
            $message->react($letters['e'])->done(function () {});
            $message->react($letters['s'])->done(function () {});
        }

        if (str_contains(strtolower($message->content),'locked') && ! $message->author->bot) {
	    wh_log("[ {$message->author->username} : $message->content " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"locked");
            $lock = '🔒';
            $message->react($lock)->done(function () {});
            $message->react($letters['l'])->done(function () {});
            $message->react($letters['o'])->done(function () {});
            $message->react($letters['c'])->done(function () {});
            $message->react($letters['k'])->done(function () {});
            $message->react($letters['e'])->done(function () {});
            $message->react($letters['d'])->done(function () {});
        }

	$biggupsWords = ['biggup','biggups','biggupz','big up','bigup','bigups'];
	$messageWords = explode(" ",strtolower($message->content));
	foreach ($messageWords as $messageWord) {	
		if (in_array($messageWord, $biggupsWords) && ! $message->author->bot) {
			wh_log("[ {$message->author->username} : $message->content " . date("D d-M-Y h:i:sa") ." ]");
			db_log($message,$messageWord);
			$message->react($letters['b'])->done(function () {});
			$message->react($letters['o'])->done(function () {});
			$message->react($letters['h'])->done(function () {});
			break;
		}
	}


	if (str_contains($message->content,'!seen') && ! $message->author->bot) {
		wh_log("[ {$message->author->username} : $message->content " . date("D d-M-Y h:i:sa") ." ]");
		db_log($message,"!seen");
		$seenUser = explode(' ',$message->content);
		unset($seenUser[0]);
		$userSeen = implode(" ",$seenUser);
		$userSeen = filter_var($userSeen,FILTER_SANITIZE_SPECIAL_CHARS);
		$userSeen = filter_var($userSeen,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
		$lowUser = strtolower($userSeen);
		if ($userSeen[0] == '&') {
			$search = "&#60;@!";
			$userSeen = str_replace($search,'',$lowUser);
			$search = "&#62;";
			$userSeen = str_replace($search,'',$userSeen);
			$sql = "SELECT * from last_seen where user_id='$userSeen'";
			$result = $conn->query($sql);
			if ($row = $result->fetch_assoc()) {
				$message->reply($row['username'] ." was last seen on {$row['updated_at']}, saying: \n{$row['the_message']}");
			}
		} else {
			if (isset($lowUser)) {
				if ($lowUser == '') {
					$message->reply("sorry {$message->author->username}, but I can't do that.");
				} else {
					$sql = "SELECT * from last_seen where LOWER(username)='$lowUser'";
					var_dump($sql);
					$result = $conn->query($sql);
					if ($row = $result->fetch_assoc()) {
						$message->reply($row['username'] ." was last seen on {$row['updated_at']}, saying: \n{$row['the_message']}");
					} else {
						$message->reply("sorry {$message->author->username}, I don't have anything for `$userSeen`");
					}
				}
			}
		}
	}

        if (str_contains($message->content,'!weather') && ! $message->author->bot) {
            wh_log("[ {$message->author->username} : $message->content " . date("D d-M-Y h:i:sa") ." ]");
	    db_log($message,"!weather");
	    $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
	    $weather = explode(' ',$message->content);
            $location = '';
            if (sizeof($weather) > 1) {
                for ($x=1; $x<sizeof($weather); $x++) {
                    $location .= $weather[$x] . '-';
                }
                $location = rtrim($location,'-');
            } else {
                $location = $weather[1];
            }

            $ch = curl_init();
	    $newLocation = filter_var($location,FILTER_SANITIZE_SPECIAL_CHARS);
	    $newLocation = filter_var($newLocation,FILTER_SANITIZE_STRING);
	    $badPlaces = ['/../','/./','/','/.../'];
	    $bad = false;
	    foreach ($badPlaces as $badPlace) {
		    if (strpos($newLocation,$badPlace)) {
			    $bad = true;
			    $message->reply('No haxing plz');
			    break;
		    }
	    }
	    if (!$bad) {
		    curl_setopt($ch, CURLOPT_URL, "https://wttr.in/$location" . "_0tqp.png");
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
		    curl_setopt($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
		    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Connection: Keep-Alive',
			    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.',
			    'Upgrade-Insecure-Requests: 1',
			    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
			    'Accept-Language: en-US,en;q=0.9',
			    'Accept-Encoding: gzip, deflate'
		    ));
		    // $output contains the output string
		    $output = curl_exec($ch);
		    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		    if($httpCode == 404) {
			    $weatherImage = "https://previews.123rf.com/images/mousemd/mousemd1710/mousemd171000009/87405336-404-not-found-concept-glitch-style-vector.jpg";
		    }else{
			    $weatherImage = "https://wttr.in/$location" . "_0tqp.png";
		    }
		    curl_close($ch);
		    #$embed->setImage("https://wttr.in/$location" . "_0tqp.png")
		    $embed->setImage($weatherImage)
	    ->setType($embed::TYPE_RICH)
	    ->setFooter("Perfect Weather for BassDrive")
	    ->setColor('blue');
		    $message->channel->sendEmbed($embed);
	    }
	}
    });
});

$discord->run();

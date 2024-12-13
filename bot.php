<?php

include __DIR__.'/vendor/autoload.php';
include 'config.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\MessageBuilder;

$discord = new Discord([
    'token' => $TOKEN,
]);

date_default_timezone_set('America/chicago');

function wh_log($log_msg)
{
    $log_filename = "log";
    if (!file_exists($log_filename)) 
    {
        // create directory/folder uploads.
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
	$sql = ("INSERT INTO `bot_stats` (`command`,`user`,`p_updated_at`) VALUES (
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

// Check connection
if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $conn -> connect_error;
  exit();
}

$currentShow = getShow();
$currentTwitch = getTwitch();

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

function getSchedule($day) {
    global $conn;
    $week = week_number();
    $day = ucfirst($day);
    $sql = "SELECT * FROM show_info WHERE day='$day' and wk$week=1 order by endtime_ct asc";
    $result = $conn->query($sql);
    $shows = array();
    if ($result->num_rows >0) {
        while($row = $result->fetch_assoc()) {
            $shows[] = $row;
        }
    }
    if ($shows) {
        return $shows;
        //var_dump($shows);
    } else {
        echo $sql;
    }
}

function getTwitch() {
	$ch = curl_init();
	$data = array (
		'channel' => 'bassdrive_radio'
	);

	//$twitchDjs = ['natereflect','amnestydj','sohl_man','powerrinse','barbarousking'];
	$twitchDjs = ['natereflect','amnestydj','sohl_man','powerrinse','illomendnb'];
	$videosApi = 'https://api.twitch.tv/helix/streams?user_login=';

	$twitchLive = null;
	foreach ($twitchDjs as $twitchDj) {
		curl_setopt_array($ch, array(
			CURLOPT_HTTPHEADER => array(
				'Client-ID: ' . $CLIENTID,
				'Authorization: Bearer '. $ACCESSTOKEN ,
			), 
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $videosApi . $twitchDj,
		));

		$response = curl_exec($ch);
		curl_close($ch);

		$jsonResponse = json_decode($response, TRUE);
		if (!isset($jsonResponse['error'])) {
			if ($jsonResponse['data'] != null) {
				if (str_contains(strtolower($jsonResponse['data'][0]['title']), 'bassdrive')) {
					$twitchLive = ['name' => $twitchDj, 'title' => $jsonResponse['data'][0]['title'] ];
					break;
				}
			}
		}
	}
	//var_dump($twitchLive);
	if ($twitchLive) {
		return $twitchLive;
	} else {
		$twitchLive = ['name' => null, 'title' => null ];
	}
}

function getShow() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $BASSDRIVEURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
    $output = strip_tags($output);
    $output = explode(',', $output);

    // close curl resource to free up system resources
    curl_close($ch);
    if (isset($output[7])) {
        $bassdriveShow = clean_string($output[6]) . ' '. clean_string($output[7]);
    } else {
        $bassdriveShow = clean_string($output[6]);
    }
    return $bassdriveShow;
}

function getThumbnail($show) {
    global $conn;
    $showNames = ['Fokuz Recording',
        'Prague Connection',
        'Subfactory',
        'Four Corners',
        'Bankbeats',
        'Skeptics Presents',
        'Vital Habits',
        'Translation Sound',
        'Rinse N Wash',
        'Bass Boom',
        'DrumObsession',
        'DnPea',
        'Context',
        'XPOSURE',
        'Sohl Patrol',
        'River City Rinseout',
        'Atmospheric Alignment',
        'Promo ZO',
        'Balearic Breaks',
        'Bryan Gee',
        'Northern Groove',
        'Greenroom',
        'Random Movement',
        'Power Rinse',
        'Stamina',
        'Trainspotting Sessions',
        'Planet V',
        'Eastside Sessions',
        'Blu Saphir',
        'Mod Con Records',
        'Funked Up',
        'Just Track',
        'Ebb Flow',
        'Impressions',
        'Australian Atmospherics',
        'Contrast',
        'Deep Soul',
        'Onward',
        'Launch',
        'Represent',
        'SixOneOh',
        'Resistance',
        'Crucial Xtra',
        'Phuture Beats',
        'EW New York',
        'Hangover Hotline',
        'Schematic Sound',
        'Crucial X',
        'Deceit FM',
        'Hive',
        'High Defnition',
        'Strictly Science Mixshow',
        'Warm Ears',
        'FuzedFunk',
        'Lab'];

    $hostNames = ['Dreazz & Sub:liminal',
        'Blofeld',
        'Spim'
        ,'Melinki'
        ,'Bank'
        ,'The Skeptics'
        ,'Overt1'
        ,'Rogue State'
        ,'Wadjit'
        ,'DJ Andy'
        ,'Alegria'
        ,'Sweetpea'
        ,'Subtext'
        ,'Ben XO'
        ,'Sohlman'
        ,'Ill Omen'
        ,'Skanka'
        ,'Promo ZO'
        ,'Darren Jay & Jazzy'
        ,'Bryan Gee'
        ,'Dan Soulsmith'
        ,'Stunna'
        ,'Random Movement'
        ,'EvanTheScientist'
        ,'Jamal'
        ,'Method One'
        ,'Amnesty'
        ,'Simplification'
        ,'A-Sides'
        ,'Jay Rome'
        ,'Rodney Rolls'
        ,'DFunk'
        ,'Ashatack'
        ,'Optx'
        ,'Indentation'
        ,'Operon'
        ,'AudioDevice and Friends'
        ,'Donovan Badboy Smith'
        ,'Jay Dubz'
        ,'Handy'
        ,'Squake'
        ,'Reflect'
        ,'John Ohms'
        ,'Spacefunk'
        ,'Kos.Mos Music'
        ,'Overfiend'
        ,'Lamebrane'
        ,'Schematic'
        ,'Spacefunk'
        ,'Buzzy D'
        ,'Bumblebee'
        ,'LJHigh'
        ,'dLo'
        ,'Elementrix and D.E.D'
        ,'Jason Magin'
        ,'Impression'];

    foreach ($showNames as $realName) {
        if (str_contains($show,$realName)) {
            $actualShow = $realName;
            break;
        } else {
            $actualShow = null;
        }
    }

    if (isset($actualShow)) {
        echo 'Actualshow';
        $sql = "SELECT flyer FROM show_info WHERE showname like '%$actualShow%'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc(); 
        if ($row['flyer'] != null) {
            $thumbnail = $row['flyer'];
        }
    } else {
        echo 'actualHOST';
        foreach ($hostNames as $host) {
            if (str_contains(strtolower($show),strtolower($host))) {
                $actualHost = $host;
                break;
            } else {
                $actualHost = null;
            }
        }
        if (isset($actualHost)) {
            $sql = "SELECT flyer FROM show_info WHERE hostname like '%$actualHost%'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc(); 
            if ($row['flyer'] != null) {
                $thumbnail = $row['flyer'];
            }
        }
    }
    if (!isset($thumbnail)) {
        if (str_contains(strtolower($show),"live")) {
            $thumbnail = "https://unicornriot.ninja/wp-content/uploads/2018/09/LiveChannel334x212v2.png";
        }else {
            $thumbnail ="https://cdn.discordapp.com/attachments/918981144207302716/934265946397343815/Bassdrive_TUNE_IN_Blue.jpg";
        }
    }
    return $thumbnail;
}

$discord->on('ready', function ($discord) {
	global $GUILD_ID;
	global $NAME;
	echo "$NAME is ready!", PHP_EOL;
	$guild = $discord->guilds[$GUILD_ID];
	$guild->members[$discord->id]->setNickname($NAME);

    $discord->getLoop()->addPeriodicTimer(10, function() use ($discord) {
        global $currentShow;
	global $currentTwitch;

        $newShow = getShow();
	$newTwitch = getTwitch();
        
        if ($currentShow != $newShow) {
            //echo "-> " . strlen($currentShow) . "---" . strlen($newShow) . "<--\n";
	    wh_log("[ NEW SHOW {$newShow} " . date("D d-M-Y h:i:sa") ." ]"); 
            $currentShow = $newShow;
            $thumbnail = getThumbnail($currentShow);
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $currentShow :::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setThumbnail($thumbnail)
                  ->setColor('blue');
            $channel = $discord->getChannel('766141517005455396');
            $channel->sendMessage(MessageBuilder::new()
                ->addEmbed($embed));
	    $channel = $discord->getChannel('766141517005455396');
	    $channel->broadcastTyping();
        }

	//echo "=====>$currentShow (current) $newShow (newShow) <=====\n";
	if (isset($newTwitch['name'])) {
		if ($currentTwitch['name'] != $newTwitch['name'] && $newTwitch['name'] != null) {
			echo "=====>{$currentTwitch['name']} (current) {$newTwitch['name']} (newTwitch) <=====\n";
			wh_log("[ Current Twitch: {$currentTwitch['name']} NEW TWITCH {$newTwitch['name']} " . date("D d-M-Y h:i:sa") ." ]"); 
			$output = null;
			$retval = null;
			$thumbnail = 'https://cdn.vox-cdn.com/thumbor/Hr-qRdy-IJYsjvfLx3VY8rljpx8=/0x0:2040x1360/1820x1213/filters:focal(857x517:1183x843):format(webp)/cdn.vox-cdn.com/uploads/chorus_image/image/69968838/acastro_190923_twitch_0001.0.0.jpg';
			if ($newTwitch) {
				$currentTwitch['name'] = $newTwitch['name'];
				$embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
				$embed->setTitle("::: [bd] now playing on Twitch ::: {$newTwitch['title']} :::" )
	  ->setType($embed::TYPE_RICH)
	  ->setDescription("Tune in: https://www.twitch.tv/bassdrive")
	  ->setThumbnail($thumbnail)
	  ->setColor('blue');
				$channel = $discord->getChannel('918981144207302716');
				$channel->sendMessage(MessageBuilder::new()
					->addEmbed($embed));
				//exec("node /home/section31/code/twitchChatBot/bot.js host {$newTwitch['name']}",$output,$retval);
			} else {
				//exec("node /home/section31/code/twitchChatBot/bot.js unhost",$output,$retval);
				$currentTwitch['name'] = null;
			}
		}
	}
    });


    // Listen for messages.
    $discord->on('message', function ($message, $discord) {


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
    
        $weekDays = array('!monday','!tuesday','!wednesday','!thursday','!friday','!saturday','!sunday');
        $letters = array ('a' => '🇦', 'b'=>'🇧', 'c'=>'🇨', 'd'=>'🇩','e'=>'🇪','f'=>'🇫','g'=>'🇬','h'=>'🇭','i'=>'🇮','j'=>'🇯','k'=>'🇰','l'=>'🇱','m'=>'🇲','n'=>'🇳','o'=>'🇴','p'=>'🇵','q'=>'🇶','r'=>'🇷','s'=>'🇸','t'=>'🇹','u'=>'🇺','v'=>'🇻','w'=>'🇼','x'=>'🇽','y'=>'🇾','z'=>'🇿');
    

        if (in_array(strtolower($message->content),$weekDays) && ! $message->author->bot) {
	    wh_log("[ Bassdrive Bot - {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]"); 
            $theDay = ltrim(strtolower($message->content),'!');
	    db_log($message,$theDay);
            $theSchedule = getSchedule($theDay);
            $theWeek = week_number();
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("Bassdrive LIVE Schedule for ". ucfirst($theDay))
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setColor('blue');
            foreach ($theSchedule as $show) {
		    $timezone = new DateTimeZone('CST6CDT');
		    $startDateTime = newDateTime('today', $timezone);
		    $endDateTime = newDateTime('today', $timezone);

		    $startTime = (int)explode(':', $show['starttime_ct'])
		    $endTime = (int)explode(':', $show['endtime_ct'])
			    
		    $startDateTime->setTime($startTime[0], $startTime[1]);
		    $endDateTime->setTime($endTime[0], $endTime[1]);

		    $startTimestamp = $startDateTime->getTimestamp();
		    $endTimestamp = $endDateTime->getTimestamp();
		    
                  $embed->addField([
                      'name' => $show['showname'] . ' :: ' . ' w/ ' . $show['hostname'],
                      'value' => "<t:$startTimestamp:t>" . '-' . "<t:$endTimestamp:t>",
                      'inline' => false,
                  ]);
            }
                  $embed->setFooter('*All times CT and are subject to change. (week ' . $theWeek . ')');
            $message->channel->sendEmbed($embed);
        }

        if ($message->content == '!pls' && ! $message->author->bot) {
	    wh_log("[ Bassdrive Bot - {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]"); 
	    db_log($message,'!pls');
            $newShow = getShow();
            $thumbnail = getThumbnail($newShow);
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $newShow :::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
		  ->setColor('blue');
	    $embed->addField([
		    'name' => "192k High grade",
		    'value' => "http://bassdrive.com/bassdrive.m3u",
		    'inline' => false,
	    ]);
	    $embed->addField([
		    'name' => "56k Mid grade",
		    'value' => "http://bassdrive.com/bassdrive6.m3u",
		    'inline' => false,
	    ]);
	    $embed->addField([
		    'name' => "AAC+",
		    'value' => "http://bassdrive.com/bassdrive3.m3u",
		    'inline' => false,
	    ]);
	    $embed->setThumbnail($thumbnail);
            $message->channel->sendEmbed($embed);
        }

        if ($message->content == '!nowplaying' && ! $message->author->bot) {
	    wh_log("[ Bassdrive Bot - {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]"); 
	    db_log($message,"!nowplaying");
            $newShow = getShow();
            $thumbnail = getThumbnail($newShow);
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $newShow :::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setColor('blue')
                  ->setThumbnail($thumbnail);
            $message->channel->sendEmbed($embed);
	    $channel = $discord->getChannel('766141517005455396');
	    $channel->broadcastTyping();
        }
         if (str_contains(strtolower($message->content),'honk') && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"!honk");
            $honk = '🦤';
            $honk2 = '📣';
            $message->react($honk)->done(function () {});
            $message->react($honk2)->done(function () {});
    }

        if (str_contains(strtolower($message->content),'infobot') && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"!honk");
            $theMessage = 'lol, I am a beard -- infobot 🤖';
            $message->reply($theMessage)->done(function () {});
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
            //  $guild->members->kick($message->member);
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
        $ballAnswers = ['It is certain', 'Reply hazy, try again', 'Don’t count on it', 'It is decidedly so', 'Ask again later', 'My reply is no', 'Without a doubt', 'Better not tell you now', 'My sources say no', 'Yes definitely', 'Cannot predict now  ', 'Outlook not so good', 'You may rely on it', 'Concentrate and ask again', 'Very doubtful', 'As I see it, yes', 'Most likely','Outlook good', 'Yes', 'Signs point to yes'];
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

        if (strtolower($message->content) =='ez' && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"ez");
        $salut ="salut";
        $message->reply($salut);
        }

        if (strtolower($message->content) =='a mix' && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"a mix");
        $amix ="a mix is, like, BOH or whats good HOMIE or happening around";
        $message->reply($amix);
        }

        if (strtolower($message->content) =='holy shit' && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"holy shit");
        $holyshit ="i heard holy shit was a religious experience or , and it";
        $message->reply($holyshit);
        }

        if (strtolower($message->content) =='go bananas' && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"go banans");
        $bananas ="i heard go bananas was ┏(-_-)┛ ┗(-_-)┓ ┗(-_-)┛ ┏(-_-)┓";
        $message->reply($bananas);
        }

        if (strtolower($message->content) =='you guys' && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"you guys");
        $youGuys ="you guys are insane";
        $message->reply($youGuys);
        }

        if (strtolower($message->content) =='yo' && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"yo");
        $yo = $message->author->username .": hr0n";
        $message->reply($yo);
        }
    
        if (strtolower($message->content) =='bd disclaimer' && ! $message->author->bot) {
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"disclaimer");
        $disclaimer ="somebody said bd disclaimer was ATTENTION FEDS: the things said in chat are for the lols and not to be taken seriously";
        $message->reply($disclaimer);
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

    if (str_contains(strtolower($message->content),'replay shout') && ! $message->author->bot) { 
        wh_log("[ InfoBot -  {$message->author->username} - $message->content - " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"replay shout");
        $replayShoutResponse = 'I heard that replay shouts is the sign of a true bassdrive soulja!';
        $message->reply($replayShoutResponse);
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

        if (str_contains(strtolower($message->content),'fursday') && ! $message->author->bot) {
        wh_log("[ {$message->author->username} : $message->content " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"fursday");
            $message->react($letters['f'])->done(function () {});
            $message->react($letters['u'])->done(function () {});
            $message->react($letters['r'])->done(function () {});
            $message->react($letters['s'])->done(function () {});
            $message->react($letters['d'])->done(function () {});
            $message->react($letters['a'])->done(function () {});
            $message->react($letters['y'])->done(function () {});
        }

        if (str_contains(strtolower($message->content),'tunesday') && ! $message->author->bot) {
        wh_log("[ {$message->author->username} : $message->content " . date("D d-M-Y h:i:sa") ." ]");
        db_log($message,"tunesday");
            $message->react($letters['t'])->done(function () {});
            $message->react($letters['u'])->done(function () {});
            $message->react($letters['n'])->done(function () {});
            $message->react($letters['e'])->done(function () {});
            $message->react($letters['s'])->done(function () {});
            $message->react($letters['d'])->done(function () {});
            $message->react($letters['a'])->done(function () {});
            $message->react($letters['y'])->done(function () {});
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
        $ogWeather = $weather;
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
            //curl_setopt($ch, CURLOPT_URL, "https://wttr.in/$location" . "_0tqp.png");
            curl_setopt($ch, CURLOPT_URL, "https://wttr.in/$location?format=3");
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
                $weatherInfo = "Can't seem to find that, better ask joe";
            }else{
                //$weatherImage = "https://wttr.in/$location" . "_0tqp.png";
                $weatherInfo = $output;
            }
            curl_close($ch);
            #$embed->setImage("https://wttr.in/$location" . "_0tqp.png")
            $weatherInfo = str_replace("-"," ",$weatherInfo);
            $embed->setDescription($weatherInfo)
        ->setType($embed::TYPE_RICH)
        ->setFooter("Perfect Weather for BassDrive")
        ->setColor('blue');
            $message->channel->sendEmbed($embed);
        }
    }




    });
});

$discord->run();


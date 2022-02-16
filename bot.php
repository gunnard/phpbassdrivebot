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
    $sql = "SELECT * FROM show_info WHERE day='$day' and wk$week=1";
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
	$clientId = 'oiaj85u4lww35nv6eco0yrq5g3zibv';

	$tokenAPI = 'https://id.twitch.tv/oauth2/token
		--data-urlencode
		?grant_type=refresh_token
		&refresh_token=eyJfaWQmNzMtNGCJ9%6VFV5LNrZFUj8oU231/3Aj
		&client_id=fooid
		&client_secret=barbazsecret';

	$data = array (
		'client_id' => $clientId,
		'client_secret' => '1mb2v2fciec6b6z4hck8tkozvvhjf8',
		'grant_type' => 'client_credentials'
	);

	$post_data = json_encode($data);
	$crl = curl_init('https://id.twitch.tv/oauth2/token');
	curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($crl, CURLINFO_HEADER_OUT, true);
	curl_setopt($crl, CURLOPT_POST, true);
	curl_setopt($crl, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($crl, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json'
	));
	$result = curl_exec($crl);
	$result = json_decode($result);
	curl_close($crl);
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
				'Client-ID: ' . $clientId,
				'Authorization: Bearer '. $result->access_token,
			), 
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $videosApi . $twitchDj,
		));

		$response = curl_exec($ch);
		curl_close($ch);

		$jsonResponse = json_decode($response, TRUE);
		if (isset($jsonResponse['data'])) {
			if (count($jsonResponse['data']) != 0) {
				$twitchLive = $twitchDj;
				break;
			}
		}

	}
	if ($twitchLive) {
		return $twitchLive;
	} else {
		return null;
	}
}

function getShow() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://bassdrive.com:8000/7.html");
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

    //bassdriveInfo=$(curl -X GET http://bassdrive.com:8000/7.html -H "Connection: keep-alive" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.
//0.3945.88 Safari/537.36" -H "Upgrade-Insecure-Requests: 1" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9"
 //-H "Accept-Language: en-US,en;q=0.9" -H "Accept-Encoding: gzip, deflate")

$discord->on('ready', function ($discord) {
	global $GUILD_ID;
	global $NAME;
	echo "Bot is ready!", PHP_EOL;
	$guild = $discord->guilds[$GUILD_ID];
	$guild->members[$discord->id]->setNickname($NAME);

    $discord->getLoop()->addPeriodicTimer(10, function() use ($discord) {
        global $currentShow;
	global $currentTwitch;

        $newShow = getShow();
	$newTwitch = getTwitch();
        
        if ($currentShow != $newShow) {
            echo "-> " . strlen($currentShow) . "---" . strlen($newShow) . "<--\n";
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
        }

	echo "=====>$currentTwitch (current) $newTwitch (newTwitch) <=====\n";
	echo "=====>$currentShow (current) $newShow (newShow) <=====\n";
	if ($currentTwitch != $newTwitch && $currentTwitch != null) {
		$output = null;
		$retval = null;
		if ($newTwitch) {
			$currentTwitch = $newTwitch;
			echo "=====>$newTwitch <=====\n";
			exec("node ../twitchChatBot/bot.js host $newTwitch",$output, $retval);
		} else {
			exec("node ../twitchChatBot/bot.js unhost",$output, $retval);
		}
	}
    });


    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        $weekDays = array('!monday','!tuesday','!wednesday','!thursday','!friday','!saturday','!sunday');

        if (in_array(strtolower($message->content),$weekDays) && ! $message->author->bot) {
            $theDay = ltrim(strtolower($message->content),'!');
            $theSchedule = getSchedule($theDay);
            $theWeek = week_number();
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("Bassdrive LIVE Schedule for ". ucfirst($theDay))
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setColor('blue');
            foreach ($theSchedule as $show) {
                  $embed->addField([
                      'name' => $show['showname'] . ' :: ' . ' w/ ' . $show['hostname'],
                      'value' => $show['starttime_ct'] . '-' . $show['endtime_ct'],
                      'inline' => false,
                  ]);
            }
                  $embed->setFooter('*All times CT and are subject to change. (week ' . $theWeek . ')');
            $message->channel->sendEmbed($embed);
        }

        if ($message->content == '!pls' && ! $message->author->bot) {
            $newShow = getShow();
            $thumbnail = getThumbnail($newShow);
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $newShow :::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
		  ->setColor('blue');
	    $embed->addField([
		    'name' => "128k High grade",
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

            $newShow = getShow();
            $thumbnail = getThumbnail($newShow);
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $newShow :::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setColor('blue')
                  ->setThumbnail($thumbnail);
            $message->channel->sendEmbed($embed);
        }
    });
});

$discord->run();

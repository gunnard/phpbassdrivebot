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
        'Fuzed Funk',
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
        $sql = "SELECT flyer FROM show_info WHERE showname like '%$actualShow%'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc(); 
        if ($row['flyer'] != null) {
            $thumbnail = $row['flyer'];
        }
    } else {
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
        if (!isset($thumbnail)) {
            if (str_contains(strtolower($show),"live")) {
                $thumbnail = "https://unicornriot.ninja/wp-content/uploads/2018/09/LiveChannel334x212v2.png";
            }else {
                $thumbnail ="https://cdn.discordapp.com/attachments/918981144207302716/934265946397343815/Bassdrive_TUNE_IN_Blue.jpg";
            }
        }
    }
    return $thumbnail;
}

    //bassdriveInfo=$(curl -X GET http://bassdrive.com:8000/7.html -H "Connection: keep-alive" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.
//0.3945.88 Safari/537.36" -H "Upgrade-Insecure-Requests: 1" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9"
 //-H "Accept-Language: en-US,en;q=0.9" -H "Accept-Encoding: gzip, deflate")

$discord->on('ready', function ($discord) {
    echo "Bot is ready!", PHP_EOL;

    $discord->getLoop()->addPeriodicTimer(10, function() use ($discord) {
        global $currentShow;

        $newShow = getShow();
        
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
            $channel = $discord->getChannel('700507571550945281');
            $channel->sendMessage(MessageBuilder::new()
                ->addEmbed($embed));
        }
    });


    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        $weekDays = array('!monday','!tuesday','!wednesday','!thursday','!friday','!saturday','!sunday');
        if (str_contains(strtolower($message->content),'honk') && ! $message->author->bot) {
            $honk = '🦆';
            $message->react($honk)->done(function () {});
        }

        if (str_contains(strtolower($message->content),'locked') && ! $message->author->bot) {
            $lock = '🔒';
            $message->react($lock)->done(function () {});
        }


        if ($message->content == 'ping' && ! $message->author->bot) {
            // Reply with "pong"
            $message->reply('pong');
        }

        if (str_contains($message->content,'!weather') && ! $message->author->bot) {
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
            curl_setopt($ch, CURLOPT_URL, "https://wttr.in/$location" . "_0tqp.png");
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
            // Test embed
        if ($message->content == '!test' && ! $message->author->bot) {
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle('Testing Embed')
                  ->setType($embed::TYPE_RICH)
                  ->setAuthor('DiscordPHP Bot')
                  ->setDescription('Embed Description')
                  ->setColor(0xe1452d)
                  ->addField([
                      'name' => 'Field 1',
                      'value' => 'Value 1',
                      'inline' => true,
                  ])
                  ->addField([
                      'name' => 'Field 2',
                      'value' => 'Value 2',
                      'inline' => false,
                  ])
                  ->setFooter('Footer Value');
            $message->channel->sendEmbed($embed);
        }
    });
});

$discord->run();

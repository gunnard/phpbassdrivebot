<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\MessageBuilder;

$discord = new Discord([
    'token' => 'OTA3NjkzODMyNTA4OTMyMDk3.YYq5wQ.WxPXBmN50fLTu2EHlSwLK2cgmY8',
]);

$conn = new mysqli("localhost","ubuntu","!Qaytr3ej","bassdrive");

// Check connection
if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $conn -> connect_error;
  exit();
}

$currentShow = getShow();

function clean_string($string) {
  $s = trim($string);
  $s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters

  // this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
  $s = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);

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
    $bassdriveShow = clean_string($output[6]);
    return $bassdriveShow;;
}

    //bassdriveInfo=$(curl -X GET http://bassdrive.com:8000/7.html -H "Connection: keep-alive" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.
//0.3945.88 Safari/537.36" -H "Upgrade-Insecure-Requests: 1" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9"
 //-H "Accept-Language: en-US,en;q=0.9" -H "Accept-Encoding: gzip, deflate")

$discord->on('ready', function ($discord) {
    echo "Bot is ready!", PHP_EOL;

    $discord->getLoop()->addPeriodicTimer(10, function() use ($discord) {
        global $currentShow;
        global $mysqli; 

        $newShow = getShow();
        echo "-> " . strlen($currentShow) . "---" . strlen($newShow) . "<--\n";
        
        if ($currentShow != $newShow) {
            $currentShow = $newShow;
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $currentShow ::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setColor('blue');
            $channel = $discord->getChannel('700507571550945281');
            $channel->sendMessage(MessageBuilder::new()
                ->addEmbed($embed));
        }
    });


    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        $weekDays = array('!monday','!tuesday','!wednesday','!thursday','!friday','!saturday','!sunday');
        if ($message->content == 'ping' && ! $message->author->bot) {
            // Reply with "pong"
            $message->reply('pong');
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
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $newShow ::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setColor('blue');
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

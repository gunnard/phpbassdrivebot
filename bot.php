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

$currentShow = getBassdriveShow();

function getBassdriveShow() {
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
    return $output[6];
}

    //bassdriveInfo=$(curl -X GET http://bassdrive.com:8000/7.html -H "Connection: keep-alive" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.
//0.3945.88 Safari/537.36" -H "Upgrade-Insecure-Requests: 1" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9"
 //-H "Accept-Language: en-US,en;q=0.9" -H "Accept-Encoding: gzip, deflate")

$discord->on('ready', function ($discord) {
    echo "Bot is ready!", PHP_EOL;
    $discord->getLoop()->addPeriodicTimer(5, function() use ($discord) {
        global $currentShow;
        $show = getBassdriveShow();
        if ($currentShow == $show) {
            $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
            $embed->setTitle("::: [bd] now playing ::: $show ::" )
                  ->setType($embed::TYPE_RICH)
                  ->setDescription('Tune in: https://www.bassdrive.com/pop-up')
                  ->setColor('blue')
                  ->setFooter('Footer Value');
            $channel = $discord->getChannel('700507571550945281');
            $channel->sendMessage(MessageBuilder::new()
                ->setContent('Hello, world!')
                ->addEmbed($embed));
        }
    });


    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        if ($message->content == 'ping' && ! $message->author->bot) {
            // Reply with "pong"
            $message->reply('pong');
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

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

$discord = new Discord([
    'token' => $TOKEN,
]);


// Check connection
if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $conn -> connect_error;
  exit();
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
    echo "Bot is ready!", PHP_EOL;
    $guild = $discord->guilds[$GUILD_ID];
    $guild->members[$discord->id]->setNickname($NAME);



    //$discord->getLoop()->addPeriodicTimer(10, function() use ($discord) {
    //});


    // Listen for messages.
    $discord->on('message', function ($message, $discord) {
        $weekDays = array('!monday','!tuesday','!wednesday','!thursday','!friday','!saturday','!sunday');
	$letters = array ('a' => '🇦', 'b'=>'🇧', 'c'=>'🇨', 'd'=>'🇩','e'=>'🇪','f'=>'🇫','g'=>'🇬','h'=>'🇭','i'=>'🇮','j'=>'🇯','k'=>'🇰','l'=>'🇱','m'=>'🇲','n'=>'🇳','o'=>'🇴','p'=>'🇵','q'=>'🇶','r'=>'🇷','s'=>'🇸','t'=>'🇹','u'=>'🇺','v'=>'🇻','w'=>'🇼','x'=>'🇽','y'=>'🇾','z'=>'🇿');
        if (str_contains(strtolower($message->content),'honk') && ! $message->author->bot) {
            $honk = '🦤';;
            $honk2 = '📣';
            $message->react($honk)->done(function () {});
            $message->react($honk2)->done(function () {});
        }

        if (str_contains(strtolower($message->content),'clown') && ! $message->author->bot) {
            $clown = '🤡';
            $message->react($clown)->done(function () {});
        }

        if (str_contains(strtolower($message->content),'vibes') && ! $message->author->bot) {
            $message->react($letters['v'])->done(function () {});
            $message->react($letters['i'])->done(function () {});
            $message->react($letters['b'])->done(function () {});
            $message->react($letters['e'])->done(function () {});
            $message->react($letters['s'])->done(function () {});
        }

        if (str_contains(strtolower($message->content),'locked') && ! $message->author->bot) {
            $lock = '🔒';
            $message->react($lock)->done(function () {});
            $message->react($letters['l'])->done(function () {});
            $message->react($letters['o'])->done(function () {});
            $message->react($letters['c'])->done(function () {});
            $message->react($letters['k'])->done(function () {});
            $message->react($letters['e'])->done(function () {});
            $message->react($letters['d'])->done(function () {});
        }

        if (str_contains(strtolower($message->content),'dubstep') && ! $message->author->bot) {
            $facepalm = '🤦';
            $x = '❌';
            $message->react($facepalm)->done(function () {});
            $message->react($x)->done(function () {});
        }


        if (str_contains(strtolower($message->content),'biggups') && ! $message->author->bot) {
            $message->react($letters['b'])->done(function () {});
            $message->react($letters['o'])->done(function () {});
            $message->react($letters['h'])->done(function () {});
        }

        if (str_contains(strtolower($message->content),'bigup') && ! $message->author->bot) {
            $message->react($letters['b'])->done(function () {});
            $message->react($letters['o'])->done(function () {});
            $message->react($letters['h'])->done(function () {});
        }

        if (str_contains(strtolower($message->content),'big up') && ! $message->author->bot) {
            $message->react($letters['b'])->done(function () {});
            $message->react($letters['o'])->done(function () {});
            $message->react($letters['h'])->done(function () {});
        }

	if (str_contains($message->content,'!seba') && ! $message->author->bot) {
		$promo = 'https://scontent-atl3-1.xx.fbcdn.net/v/t39.30808-6/273246529_472336101007559_1176083929138564907_n.jpg?_nc_cat=111&ccb=1-5&_nc_sid=8bfeb9&_nc_ohc=RUHihSHEs54AX_QM7-7&_nc_ht=scontent-atl3-1.xx&oh=00_AT_0J_2QVap6ow-ZJ-5BDaBiK9mzE7k4Vs6S35KKlHrCew&oe=62015EF6';
		$embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
		$embed->setImage($promo)
	->setType($embed::TYPE_RICH)
	->setColor('blue');
		$message->channel->sendEmbed($embed);
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

    });
});

$discord->run();

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

	$kickWords = ['pendulum','dubstep','infobot','live?'];
	$messageWords = explode(" ",strtolower($message->content));
	foreach ($messageWords as $messageWord) {	
		if (in_array($messageWord,$kickWords) && ! $message->author->bot) {
			$guild = $discord->guilds->get('id', $message->guild_id);
			$message->reply("Hey  ".$message->author->username ." ! Watch your mouth!");
			//message->reply($message->author->id)
			$x = '❌';
			$message->react($x)->done(function () {});
			//	$guild->members->kick($message->member);
		}
	}
	
        if (str_contains(strtolower($message->content),'testkickword') && ! $message->author->bot) {
		$x = '❌';
		$message->react($x)->done(function () {});
		$message->channel->setPermissions($message->member, [], [10], 'Badmouthing');

        }

        if (str_contains(strtolower($message->content),'clown') && ! $message->author->bot) {
            $clown = '🤡';
            $message->react($clown)->done(function () {});
        }

        if (str_contains(strtolower($message->content),'junglist?') && ! $message->author->bot) {
            $beard = 'Lol, I am a beard!';
            $message->reply($beard);
        }

        if (strtolower($message->content) =='b0h b0h b0h' && ! $message->author->bot) {
	    $boh = 'I heard b0h b0h b0h was h0b h0b h0b';
            $message->react($letters['h'])->done(function () {});
            $message->react($letters['o'])->done(function () {});
            $message->react($letters['b'])->done(function () {});
            $message->reply($boh);
        }

        if (strtolower($message->content) =='do a flip' && ! $message->author->bot) {
	    $flip = '/( .□.) ︵╰(゜益゜)╯︵ /(.□. /)';
            $message->reply($flip);
        }

        if (strtolower($message->content) =='flip a table' && ! $message->author->bot) {
		$table ="(ﾉ´□｀)ﾉ ┫:･’∵:.┻┻:･’.:┣∵･:. ┳┳";
		$message->reply($table);
        }

	$tuneId = ['tune id','track id'];
	$trackIdResponses = ['Track ID: Wet Arena - Pimple Bee', 'due to delinquent account tune ID privileges will be revoked in 30 days unless payment is received'];
	$messageWords = explode(" ",strtolower($message->content));
	foreach ($messageWords as $messageWord) {	
		if (in_array($messageWord, $tuneId) && ! $message->author->bot) {
			$randResponse = mt_rand(0,count($tuneId)-1);
			$message->reply($trackIdResponses[$randResponse]);
		}
        }


	$jokes = ['yow','!hit'];
	$messageWords = explode(" ",strtolower($message->content));
	foreach ($messageWords as $messageWord) {	
		if (in_array($messageWord, $jokes) && ! $message->author->bot) {
			global $conn;
			$sql = "SELECT joke FROM jokes order by rand() limit 1";
			$result = $conn->query($sql);
			$row = $result->fetch_assoc();
			if ($row['joke'] != null) {
				$message->reply($row['joke']);
			}
		}
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

	$biggupsWords = ['biggup','biggups','biggupz','big up','bigup','bigups'];
	$messageWords = explode(" ",strtolower($message->content));
	foreach ($messageWords as $messageWord) {	
		if (in_array($messageWord, $biggupsWords) && ! $message->author->bot) {
			$message->react($letters['b'])->done(function () {});
			$message->react($letters['o'])->done(function () {});
			$message->react($letters['h'])->done(function () {});
			break;
		}
	}


	/*if (str_contains($message->content,'!seba') && ! $message->author->bot) {
		$promo = 'https://scontent-atl3-1.xx.fbcdn.net/v/t39.30808-6/273246529_472336101007559_1176083929138564907_n.jpg?_nc_cat=111&ccb=1-5&_nc_sid=8bfeb9&_nc_ohc=RUHihSHEs54AX_QM7-7&_nc_ht=scontent-atl3-1.xx&oh=00_AT_0J_2QVap6ow-ZJ-5BDaBiK9mzE7k4Vs6S35KKlHrCew&oe=62015EF6';
		$embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
		$embed->setImage($promo)
	->setType($embed::TYPE_RICH)
	->setColor('blue');
		$message->channel->sendEmbed($embed);
	}
	 */


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

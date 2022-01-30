#/usr/bin/bash

directory=${PWD}

bassdriveInfo=$(curl -X GET http://bassdrive.com:8000/7.html -H "Connection: keep-alive" -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36" -H "Upgrade-Insecure-Requests: 1" -H "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9" -H "Accept-Language: en-US,en;q=0.9" -H "Accept-Encoding: gzip, deflate") 

IFS=',' read -ra my_array <<< "$bassdriveInfo"

now=$(date)
echo "running $now" >> $directory/out.log

arraylen=${#my_array[@]}
unset 'my_array[0]'
unset 'my_array[1]'
unset 'my_array[2]'
unset 'my_array[3]'
unset 'my_array[4]'
unset 'my_array[5]'
title=${my_array[@]}
title=$(sed 's/.\{14\}$//' <<< "$title")
line=$(head -n 1 ${directory}/bassdrive.txt)

echo "ooooooooooo" >> $directory/out.log
echo "From Web: " $title >> ${directory}/out.log
echo "From File: " $line >> ${directory}/out.log
echo "ooooooooooo" >> $directory/out.log

array=("The Hangover" "Atmospheric Alignment" "The Mod Con" "The just on track" "Insideman" "The funked up" "Rinse N Wash" "TRANSLATION SOUND" "Ashatack" "Represent Radio LIVE!" "Deep Soul" "The Onward Show" "Crucial X" "Promo ZO" "The Prague")

for show in "${array[@]}"
do
	if [[ $title =~ ^($show) ]]; then
		if [[ $line =~ ^($show) ]]; then
			echo "found " $show " .. ignoring update " $title >> ${directory}/out.log
			echo $title > ${directory}/bassdrive.txt
			echo "ooooooooooo" >> $directory/out.log
			exit 0
		fi
	fi
done

if [[ ${#title} -le 3 ]] ; then
	title='Bassdrive Radio'
	echo "11111ooooooooooo" >> $directory/out.log
fi

if [ "$title" != "$line" ]
then 
    echo "found a new show " $title >> ${directory}/out.log
    echo $title > $directory/bassdrive.txt
    echo $title > $directory/newbassdrive.txt
    echo "ooooooooooo" >> $directory/out.log
fi

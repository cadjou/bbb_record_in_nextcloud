# bbb_record_in_nextcloud
This a little script to add into NextCloud the BigBlueButton record 
with the **BigBlueButton NextCloud Application** and the Git **tilmanmoser/bbb-video-download** 

NextCloud et BigBlueButton have to be in 2 different servers that you can link by ssh network disk

After the recording meeting available on the NextCloud App, make a file link of the record and be patient. A new MP4 will be add in the same folder.

When the installation, the script will create a RSA Key specific or change parameters to use yours

## How to install
### On NextCloud Server
- Install the BigBlueButton application on your NextCloud. See https://apps.nextcloud.com/apps/bbb
- Connect to the server with root user and follow this command
>```shell script
>cd /opt/
>git clone https://github.com/cadjou/bbb_record_in_nextcloud.git
>cd bbb_record_in_nextcloud
>rm record_manager.sh
>nano config.php
>```
- Change le server connexion and save the config
>```php
>$serveur = 'myuser@my.server.ent';
>```
- If RSA Key is already used between these servers, it must be with a passphrase.\
So add the passphrase into the config
>```php
>$passphrase = 'My passphrase';
>```
*The others parameters in config.php can be change, but it's not useful*
- The script adds a cron every 5 min by default but cron parameter by
>```php
>$install_cron  = true;
>$cron_minute   = 5;
>```
*If you change install_cron to false, the next execution will remove the cron*
- When the parameters are good, run this PHP file
>```shell script
>php -f record_manager.php
>```
- Without RSA Key, for the first run, answer **Y** or let blank\
The Key will be created and copy the last line into the BBB server to create the access between the servers
>```
>Do you want create RSA Key (Y/n):
>*******************************************
>Copy the public key in /root/.ssh/id_rsa.pub the server distant in ~/.ssh/authorized_keys or
>Run this command on the BBB server
>> echo "ssh-rsa AAAAB{...}9Z5lfERP root_bbb_record" >> ~/.ssh/authorized_keys
>*******************************************
>```
*You should on **none root** user, like admin or what every*
- After that, rerun the php script to install the cron and mount the network disk
>```shell script
>php -f record_manager.php
>Create script /opt/bbb_record_in_nextcloud/mount_video_folder.sh
>Make executable script /opt/bbb_record_in_nextcloud/mount_video_folder.sh
>Create local path /opt/bbb_record_in_nextcloud/video
>Execute script > /opt/bbb_record_in_nextcloud/mount_video_folder.sh myuser@my.server.net '/opt/bbb_record_in_nextcloud/video' 'video' "my passphrase"
>Add Cron
>>Add record e90{...}ad02-1606123711669 // if there is all ready
>```
*Now the script will be executed every 5 min to get the Url file of the BBB recording and get them when they will be available by the BBB serveur*

## On the BigBlueButton Server
- Go to the **tilmanmoser/bbb-video-download** Git to install it. See https://github.com/tilmanmoser/bbb-video-download \
**Don't forget to do the part to automatically create the MP4 video.**
- Connect to the server with root user and follow this command
>```shell script
>cd /opt/
>git clone https://github.com/cadjou/bbb_record_in_nextcloud.git
>cd bbb_record_in_nextcloud
>rm record_manager.php
>nano record_manager.sh
>```
- Change the user with the used for the RSA Key and the Home path and save it
>```shell script
># change to the good user
>user="admin"
>group="admin"
>path_user="/home/admin/"
>```
- Install the cron task
>```shell script
>crontab -e
>```
- add this line bellow
>```shell script
>2/5 * * * * /opt/bbb_record_in_nextcloud/record_manager.sh > /dev/null
>```
*Careful if you change the location. For this example, it's the **/opt/** folder used.*

## How to use
After the recording meeting available on the NextCloud App, make a file link of the record and be patient.\
A new MP4 will be add in the same folder with the same name.


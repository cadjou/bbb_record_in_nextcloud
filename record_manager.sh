#!/bin/bash

# change to the good user
user="admin"
group="admin"
path_user="/home/admin/"

# No need to change, only if you are sure
path_list="${path_user}video/list.txt"
path_record="${path_user}video/record/"
path_presentation="/var/bigbluebutton/published/presentation/"
video_file="video.mp4"

while IFS=" " read f1 f2
do
        path_video=$path_presentation$f2/$video_file
        if [[ -f "$path_video" ]]; then
                echo "$path_video exists."
                cp $path_video $path_record/$f2.mp4
                chown $user:$group $path_record/$f2.mp4
        fi
done < $path_list

#!/usr/bin/env bash

echo "Started Dropbox archiving module"

PICTUREDIR="$1"

# Move recent pictures from Dropbox
mv ~/Dropbox/Camera\ Uploads/* $PICTUREDIR/Camera\ Uploads/ > /dev/null 2>&1
echo "Dropbox archived"

#Remove duplicates from Dropbox
find $PICTUREDIR/Camera\ Uploads/ -name '*-1.jpg' | while read file; do other=`echo $file | sed 's/-1.jpg/.jpg/'`; ls -1 "$other" && rm "$file"; done
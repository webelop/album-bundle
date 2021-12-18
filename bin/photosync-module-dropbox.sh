#!/usr/bin/env bash
set -euf -o pipefail

echo "Started Dropbox archiving module"

dropbox_upload_dir=${MODULE_DROPBOX_UPLOAD_DIR:-"${HOME}/Dropbox/Camera Uploads"}
if [ ! -d "${dropbox_upload_dir}" ]; then
    echo "MODULE_DROPBOX_UPLOAD_DIR must be configured in your configuration file (eg. ~/.photosync) to purge Dropbox Camera Uploads... Exiting module"
    exit 0
fi

PICTUREDIR="$1"
if [ ! -d "${PICTUREDIR}" ]; then
    echo "First argument must be a valid folder. Exiting module"
    exit 0
fi

target_dir=${MODULE_DROPBOX_TARGET:-"Camera Uploads"}
if [ ! -d "${PICTUREDIR}/${target_dir}" ]; then
    mkdir -p "${PICTUREDIR}/${target_dir}"
fi

# Move recent pictures from Dropbox
find "${dropbox_upload_dir}/" -type f -exec mv {} "${PICTUREDIR}/${target_dir}/" \; > /dev/null 2>&1
echo "Dropbox archived"

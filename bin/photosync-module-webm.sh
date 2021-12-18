#!/usr/bin/env bash
set -euf -o pipefail

echo "Started WEBM encoding module"

# Arguments
PICTUREDIR="${1:-}"
MAXDEPTH="${2:-2}"

if [[ -z "$PICTUREDIR" ]] || [[ ! -d "$PICTUREDIR" ]]; then
  echo "1st argument must be a valid directory"
  exit 1
fi

preparePreview () {
  file="$1"

  if [ ! -f "$file" ]; then
    echo "$file is not a file"
    return
  fi

  dir=$(dirname "$file")
  base=$(basename "$file")
  mkdir -p "$dir/.preview"

  if [ ! -f "$dir/.preview/$base.mp4" ]; then
    echo "Encoding $file for web"
    ffmpeg -i "$file" -vf "scale='min(1080,iw)':-2" -c:v libx264 -b:v 600k -c:a aac "$dir/.preview/$base.mp4"
  fi

  if [ ! -f "$dir/.preview/crop/200/200/$base.jpg" ]; then
    echo "Encoding $file for thumbnail"
    duration=$(ffmpeg -i "$file" -hide_banner 2>&1 | grep Duration | awk '{print $2}' | tr -d , | awk -F ':' '{print $3/2}')
    echo "Taking preview at $duration seconds"
    ffmpeg -i "$file" -vcodec mjpeg -vframes 1 -an -f rawvideo -ss "$duration" "$dir/.preview/$base.jpg"

    mkdir -p "$dir/.preview/crop/200/200/"
    echo "Encoding $dir/.preview/$base.jpg to $dir/.preview/crop/200/200/$base.jpg"
    epeg "$dir/.preview/$base.jpg" -m 400 --inset -q 50 "$dir/.preview/crop/200/200/$base.jpg.1"
    convert "$dir/.preview/crop/200/200/$base.jpg.1" -quality 30 -auto-orient -gravity center -crop 400x400+0+0 +repage "$dir/.preview/crop/200/200/$base.jpg"
    rm "$dir/.preview/crop/200/200/$base.jpg.1"
  fi
}

while read file; do
  preparePreview "${file}"
done < <( find "${PICTUREDIR}" -maxdepth "${MAXDEPTH}" \( -iname '*.mp4' -o -iname '*.mov' -o -iname '*.mkv' \) )

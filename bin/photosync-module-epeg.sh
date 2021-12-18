#!/usr/bin/env bash
set -euf -o pipefail

echo "Started EPEG encoding module"

# Arguments
PICTUREDIR="${1:-}"
MAXDEPTH="${2:-2}"

if [[ -z "$PICTUREDIR" ]] || [[ ! -d "$PICTUREDIR" ]]; then
  echo "1st argument must be a valid directory"
  exit 1
fi

preparePreview () {
  file="$1"

  dir=$(dirname "$file")
  base=$(basename "$file")
  orientation=1

  # Generate fitted 1024x780 image (roughly)
  target="$dir/.preview/fit/2048/1560/$base.jpg"
  if [ ! -f "$target" ]; then
    orientation=$(identify -format '%[EXIF:Orientation]')
    mkdir -p "$dir/.preview/fit/2048/1560"
    echo "Encoding $file to $target"
    epeg "$file" -m 2048 -q 65 "$target"
    mogrify -auto-orient "$target"
  fi

  # Generate cropped 400x400 to fit in the 200x200 size (better for retina displays)
  target="$dir/.preview/crop/200/200/$base.jpg"
  if [ ! -f "$target" ]; then
    mkdir -p "$dir/.preview/crop/200/200/"
    echo "Encoding $file to $target"
    epeg "$file" -m 400 --inset -q 50 "$target.1"
    convert "$target.1" -quality 30 -auto-orient -gravity center -crop 400x400+0+0 +repage "$target" \
    rm "$target.1"
  fi
}

while read file; do
  preparePreview "${file}"
done < <( find "$PICTUREDIR" -maxdepth "${MAXDEPTH}" -type f -size +0 \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' \) )

#!/bin/bash

# Create the directory if it doesn't exist
mkdir -p /var/www/visubudget/public/images/social

# Convert SVG files to PNG using Inkscape or other tool if available
if command -v inkscape &> /dev/null; then
  inkscape -z -e social-default.png -w 1200 -h 630 social-default.svg
  inkscape -z -e social-wide.png -w 1200 -h 600 social-wide.svg
  inkscape -z -e social-square.png -w 600 -h 600 social-square.svg
  echo "Converted SVG files to PNG using Inkscape"
elif command -v convert &> /dev/null; then
  convert social-default.svg social-default.png
  convert social-wide.svg social-wide.png
  convert social-square.svg social-square.png
  echo "Converted SVG files to PNG using ImageMagick"
else
  echo "Please install Inkscape or ImageMagick to convert the SVG files to PNG"
  echo "For now, you can use the SVG files directly in your OpenGraph tags"
fi

# Set proper permissions
chmod 644 *.svg *.png 2>/dev/null || true

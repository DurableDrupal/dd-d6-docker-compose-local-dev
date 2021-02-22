#!/bin/bash

#
# from "Little shell script to recursively check a site for broken links"
# at http://giantdorks.org/alain/little-shell-script-to-recursively-check-a-site-for-broken-links/
# by Alain Kelder
#

#
#   - To run:
#     ./web-inventory.sh example.com
#     
#   - See results as they happen:
#     tail -f /tmp/example.com.log
#


# error handling
function err_exit { echo -e 1>&2; exit 1; }

# check if proper arguments are supplied
if [ $# -ne 1 ]; then
  echo -e "\n Usage error!\n Please provide URL to check.\n Example: $0 http://example.com\n"
  exit 1
fi

# check if wget is a valid command
if ! which wget &> /dev/null; then echo wget not found; exit 1; fi

# normalize url for log name
url=$(echo $1 | sed -r 's_https?://__;s/www\.//;s_/_._g;s/\.+/\./g;s/\.$//')

# remove log if exists
if [ -f /tmp/$url.log ]; then
   echo "Removing existing log.."
   rm /tmp/$url.log || err_exit
fi

wget -e robots=off --spider -S -r -nH -nd --delete-after $1 &> /tmp/$url.log &

while [ $(pgrep -l -f $url | grep wget | wc -l) != 0 ]; do
  sleep 3
  total=$(grep "HTTP request sent" /tmp/$url.log | wc -l)
  echo "$total HTTP requests sent thus far"
done

echo -e "\nAll done, calculating response codes.."
echo -e "\nResponse counts, sorted by HTTP code"
grep -A1 "^HTTP request sent" /tmp/$url.log |egrep -o "[0-9]{3} [A-Za-z]+(.*)" |sort |uniq -c |sort -nr || err_exit

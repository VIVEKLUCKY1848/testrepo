#Remove all multiple blank lines and keep only single newline in all the files of directory recursively start
find . -type f -exec sed -i '/^$/d' '{}' ';'
#Remove all multiple blank lines and keep only single newline in all the files of directory recursively end...

#Basic command to delete bash/terminal/command-line history start
history -cw
#Basic command to delete bash/terminal/command-line history end...

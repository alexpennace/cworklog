#!/bin/sh
DATE="`date +%Y-%m-%d_%H%M`"
TARFILE="releases/cwl_mods$DATE.tar.bz2"
DIFFCMD="git diff --name-only HEAD^! --diff-filter=MA"
TARCMD="tar -cjf $TARFILE $(git diff --name-only HEAD^! --diff-filter=MA)"

echo "---"
echo "Adding committed files to $TARFILE"
echo "---"
$DIFFCMD
$TARCMD
echo "---"
echo "Done."


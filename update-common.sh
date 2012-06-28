#!/bin/bash
#
# LICENCE
# This code is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This code is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# @author      Susanne Gottwald <gottwald@zib.de>
# @author      Jens Schwidder <schwidder@zib.de>
# @copyright   Copyright (c) 2011, OPUS 4 development team
# @license     http://www.gnu.org/licenses/gpl.html General Public License
# @version     $Id$

# Common functions used by OPUS4 update scripts
# TODO consistent naming for functions (no idea whats common for shell scripts)
# TODO add message parameter to functions like deleteFolder for additional log info?

set -o errexit

# Enables (1) or disables (0) additional output for debugging.
# TODO Maybe set based on argument or environment?
_DEBUG=0

# Enables (1) or disables (0) dry run mode to create UPDATE.log without making
# any actual modifications to the OPUS4 installation.
# TODO Maybe set based on argument or environment?
_DRYRUN=0

# Executes parameter if DEBUG is enabled
# @param Text for output
function DEBUG() {
    [[ "${_DEBUG}" -eq 1 ]] && echo "$@"
    return 0
}

# Creates file for update log
function INIT_UPDATELOG() {
    if [[ -z $_UPDATELOG ]]; then
        DEBUG "Setup UPDATE log"
        _UPDATELOG=$BASEDIR/UPDATE-$(date -Iseconds).log # TODO change name?
        if [[ ! -f $_UPDATELOG ]]; then
            DEBUG "Write UPDATE log header"
            echo "Following operations were executed during the update:" > $_UPDATELOG
            echo "" >> $_UPDATELOG
        fi
    fi
}

# Writes operations into UPDATE.log
# @param pathtofile
# @param operation
# TODO remove code for initializing update log?
function UPDATELOG() {
    if [[ -z $_UPDATELOG ]]; then
        DEBUG "Setup UPDATE log"
        _UPDATELOG=$BASEDIR/UPDATE-$(date -Iseconds).log # TODO change name?
        if [[ ! -f $_UPDATELOG ]]; then
            DEBUG "Write UPDATE log header"
            echo "Following operations were executed during the update:" > $_UPDATELOG
            echo "" >> $_UPDATELOG
        fi
    fi
    # Format output so that $2 is always at the same position
    printf "%-10s %s\n" "$1" "$2" >> $_UPDATELOG
}

# Return true if Dry-Run mode is disabled
# Can be used to enable/disable execution of commands like this:
# DRYRUN || <command that should not be executed if Dry-Run is enabled>
function DRYRUN() {
    [ "$_DRYRUN" -eq 1 ] && return 0;
    return 1;
}

# Returns list of files names from folder
function getFiles() {
    local FOLDER=$1
    find "$FOLDER" -type f -print0 | while read -r -d $'\0' FILE_PATH; do
        echo $(basename "$FILE_PATH")
    done
}

# Gets value of property from file
# @param $1 Path to file containing property
# @param $2 Name of property
# @returns Value of property in PROP_VALUE
# TODO use echo to return value
# TODO Analyse and maybe break apart for easier reading. Explain.
function getProperty() {
    local FILE="$1"
    local PROP_NAME="$2"
    PROP_VALUE=$(grep -v '^[[:space:]]*;' "$FILE" | grep "^[[:space:]]*$PROP_NAME[[:space:]]*=" | cut -d= -f2 | sed "s/\;.*$//; s/[ \'\"]*$//; s/^[ \'\"]*//")
}

# Replace property value in INI file
# TODO property might be commented out
# TODO property might not be commented out
# TODO handle whitespace flexibly
function setProperty() {
    local FILE="$1"
    local PROP_NAME="$2"
    local PROP_VALUE="$3"
    DEBUG "Setting property $PROP_NAME in file $FILE to $PROP_VALUE."
    sed -i "s|^\([[:space:]]*$PROP_NAME[[:space:]]*=\)|;\1|" "$FILE"
    sed -i "s|^\([[:space:]]*\[production\][[:space:]]*\)|\1\n$PROP_NAME = $PROP_VALUE\n|" "$FILE"
}

# Set property value in script file (does not look for "[production]"
function setPropertyInShellScript() {
    local FILE="$1"
    local PROP_NAME="$2"
    local PROP_VALUE="$3"
    DEBUG "Setting property $PROP_NAME in file $FILE to $PROP_VALUE."
    sed -i "s|^\([[:space:]]*$PROP_NAME[[:space:]]*=.*$\)|#\1\n$PROP_NAME=$PROP_VALUE\n|" "$FILE"
}

# Returns the actual MD5 hash for a file.
function getActualMD5() {
    local FILEPATH="$1"
    echo "$(md5sum $FILEPATH | cut -b 1-32)"
}

# Returns the reference MD5 hash for a file.
function getMD5() {
    local FILEPATH="$1"
    local MD5FILE="$2"
    echo "$(grep $FILEPATH$ $MD5FILE | cut -b 1-32)"
    return
}

# Asks yes/no question
# @return 0 = yes = default, 1 = no
function askYesNo() {
    local QUESTION="$1"
    local ANSWER=''

    while [[ -z $ANSWER ]] || [[ $ANSWER != 'y' ]] && [[ $ANSWER != 'n' ]]; do
        echo -e "$QUESTION \c "
        read ANSWER

        if [[ -z $ANSWER ]]; then
            return 0 # default
        else
            ANSWER=${ANSWER,,} # convert to lowercase
            ANSWER=${ANSWER:0:1} # get first letter
        fi

        if [[ $ANSWER != 'y' ]] && [[ $ANSWER != 'n' ]]; then
            echo "Invalid input."
        fi
    done

    if [[ $ANSWER == 'y' ]]; then
        return 0
    else
        return 1
    fi
}

# Adds entry to UPDATE-conflicts.log
# @param path to file that creates conflict
# TODO what if file already exists when update starts?
# TODO log conflicts to UPDATE-conflicts.log
function addConflict() {
    if [[ -z $CONFLICT ]]; then
        DEBUG "Setup CONFLICT"
        CONFLICT="$BASEDIR/UPDATE-conflicts.log"
        if [[ ! -f $CONFLICT ]]; then
            DEBUG "Write CONFLICT header"
            echo "Following files created conflicts and need to be changed manually:" > $CONFLICT
            echo "" >> $CONFLICT
        fi
    fi
    echo $@ >> $CONFLICT
}

# Checks and prompts user if files are different
# Using MD5 hashes to check if files have been modified.
# @param source folder
# @param destination folder
# @param Path to MD5
# @param file
# Uses global variable MD5_OLD
# TODO use local SRC_FILE and DEST_FILE instead of construction over and over
# TODO create backup before replacing file
function updateFile {
    local SRC="$1" # source folder
    local DEST="$2" # destination folder
    local MD5PATH="$3" # relative path in distribution
    local FILE="$4" # filename
    local BACKUP=0 # flag for backup of old file
    if [[ ! -z $5 ]] && [[ $5 = 'backup' ]]; then
        BACKUP=1
    fi
    if [[ ! -f $DEST/$FILE ]]; then
        # File does not exist at target destination and can be copied
        addFile "$SRC/$FILE" "$DEST/$FILE"
    else
        # File already exists at target destination
        echo "Checking file $DEST/$MD5PATH/$FILE for changes."

        # Get reference MD5 for file
        local MD5_REFERENCE="$(getMD5 $MD5PATH/$FILE $MD5_OLD)"
        DEBUG "MD5 ref = $MD5_REFERENCE"

        # Calculate MD5 for existing file
        local MD5_ACTUAL="$(getActualMD5 $DEST/$FILE)"
        DEBUG "MD5 cur = $MD5_ACTUAL"

        # Compare MD5 values
        if [[ "$MD5_REFERENCE" != "$MD5_ACTUAL" ]]; then
            # Hashes are different;

            # Check if changes are trivial (modified whitespace)
            local DIFF='diff -b -B -q $DEST/$FILE $SRC/$FILE'

            if [ ${#DIFF} != 0 ]; then # TODO IMPORTANT Why does the line look like comment (escape #?)
                # File was changed. User decides which file to keep.
                # TODO Add variable for automatic decision, for entire script, after first decision?
                echo "Conflict for $FILE"

                # TODO Add variable for printing out explanation only once.
                echo -e "You can keep the existing modified file and resolve the"
                echo -e " conflict after the update manually or the file can be"
                echo " replaced by the new file from OPUS4 $VERSION_NEW."

                # TODO Add option for more information
                echo -e "[K]eep modified file or [r]eplace with new file [K/r]? : \c "
                local ANSWER
                read ANSWER # TODO How to make ANSWER local variable?

                # Check and format input
                if [[ -z $ANSWER ]]; then
                    ANSWER='k'
                else
                    ANSWER=${ANSWER,,}
                    ANSWER=${ANSWER:0:1}
                fi

                # TODO Check for invalid input?
                if [[ $ANSWER = 'r' ]]; then
                    if [[ $BACKUP = 1 ]]; then
                        copyFile "$DEST/$FILE" "$DEST/$FILE.backup.$VERSION_OLD"
                    fi
                    # Replace existing file
                    copyFile "$SRC/$FILE" "$DEST/$FILE"

                else
                    # Do not replace file; Log it as conflict
                    addConflict "$DEST/$FILE"
                    copyFile "$SRC/$FILE" "$DEST/$FILE.new.$VERSION_NEW"
                fi
            else
                copyFile "$SRC/$FILE" "$DEST/$FILE"
            fi
        else
            # Installed file was not modified, replace it.
            copyFile "$SRC/$FILE" "$DEST/$FILE"
        fi
    fi
}

# Check folder for modification
# TODO function does not recognize empty folders that have been added
# TODO IMPORANT small change that wrong MD5 reference is found
function checkForModifications() {
    local RESULT=0
    local FOLDER="$1"
    DEBUG "Check $FOLDER for modifications"
    find "$FOLDER" -type f -print0 | while read -r -d $'\0' FILE; do
        # Get relative path for file
        local FILE_PATH=$(echo "$FILE" | sed -e "s|$FOLDER/||")
        DEBUG "Checking $FILE_PATH"

        # Check if file has been modified
        # TODO use path relative to root of distribution
        local FILE_MD5_REFERENCE="$(getMD5 $FILE_PATH $MD5_OLD)"
        DEBUG "MD5 ref = $FILE_MD5_REFERENCE"

        # Calculate MD5 for existing file
        local FILE_MD5_ACTUAL="$(getActualMD5 $FILE)"
        DEBUG "MD5 cur = $FILE_MD5_ACTUAL"

        # Check if file is unknown or has been modified
        if [[ -z "$FILE_MD5_REFERENCE" ]] || [[ "$FILE_MD5_REFERENCE" != "$FILE_MD5_ACTUAL" ]]; then
            # Unknown or modified file; target has been modified
            DEBUG "Modified file $FILE_PATH has been found."
            RESULT=1
        fi
    done
    echo "RESULT = $RESULT"
    return $RESULT;
}

# Copies a file using different functions depending on existence of target file
function copyFile() {
    local SRC="$1"
    local DEST="$2"
    local DEST_DIR=$(dirname "$DEST")
    # Check if target folder for file exists
    if [[ ! -d "$DEST_DIR" ]]; then
        # Create folder if it does not exist
        createFolder "$DEST_DIR"
    fi
    if [[ ! -f $DEST ]]; then
        # target file does not exist
        addFile "$SRC" "$DEST"
    else
        # target file already exists
        replaceFile "$SRC" "$DEST"
    fi
}

# Copies a folder
# TODO check if target already exists
# TODO synchronize folders instead of simple copy?
function copyFolder() {
    local SRC="$1"
    local DEST="$2"
    DRYRUN || cp -R "$SRC" "$DEST"
    DRYRUN || chown -R "$OPUS4_USERNAME:$OPUS4_GROUPNAME" "$DEST"
    UPDATELOG "CREATED" "$DEST"
    DEBUG "Copied folder $SRC to $DEST"
}

# TODO use? rsync -avz --delete $NEW_FRAMEWORK/ $OLD_FRAMEWORK to sync folders

# Copies files from a source to a destination folder recursively
# TODO handle errors
# TODO check if source/target exist
# TODO handle errors
# TODO handle missing target folder
function updateFolder() {
    local SRC="$1"
    local DEST="$2"
    local FLAT=0
    # Third parameter disables (1) recursion
    if [[ ! -z $3 ]] && [[ $3 = 'flat' ]]; then
        local FLAT=1
    fi
    DEBUG "Update folder $DEST from $SRC"
    # Get files and folders in source directory
    local SRC_FILES=$(ls $SRC)
    # Check if target folder exists
    if [[ ! -d $DEST ]]; then
        # Create target folder if it does not exist already
        createFolder "$DEST"
    fi
    # Iterate through files and folders
    local FILE
    for FILE in $SRC_FILES; do
        # Check that it is not a link
        if [[ ! -L $SRC/$FILE ]]; then
            # Check if folder
            if [[ -d $SRC/$FILE ]]; then
                # Call updateFolder recursively
                [[ "$FLAT" -eq 0 ]] && updateFolder "$SRC/$FILE" "$DEST/$FILE"
            else
                copyFile "$SRC/$FILE" "$DEST/$FILE"
            fi
        fi
    done
    return 0 # TODO see comments for deleteFiles
}

# Deletes files that exist at destination but not in source folder recursively
# TODO IMPORTANT handle/ignore symbolic links
# TODO filter deletes based on MD5 list (does that actually work as expected)?
# TODO Make iteration over files more robust (newline, etc.)
function deleteFiles() {
    local SRC="$1"
    local DEST="$2"
    local FLAT=0
    # Third parameter disables (1) recursion
    if [[ ! -z $3 ]] && [[ $3 = 'flat' ]]; then
        local FLAT=1
    fi
    local DEST_FILES=$(ls $DEST)
    # Iterate through destination files
    local FILE
    for FILE in $DEST_FILES; do
        # Check if folder
        if [[ -d $DEST/$FILE ]]; then
            # Check if folder exists in source folder
            if [[ ! -d $SRC/$FILE ]]; then
                # Folder does not exist
                # TODO Delete folder file by file recursively (for log)?
                # TODO Check against MD5 before deleting?
                # Check if folder is link; Delete if not
                if [[ ! -L $DEST/$FILE ]]; then
                    deleteFolder "$DEST/$FILE" 'empty'
                else
                    DEBUG "Not deleted symbolic link $DEST/$FILE"
                fi
            else
                # Folder exists, call deleteFiles recursively
                [[ "$FLAT" -eq 0 ]] && deleteFiles "$SRC/$FILE" "$DEST/$FILE" # TODO problem see comment at return
            fi
        else
            # Check if file exists in source folder
            if [[ ! -f $SRC/$FILE ]]; then
                # File does not exist; Delete file in destination folder
                # TODO Check against MD5 before deleting?
                # TODO check for linked files?
                local MD5PATH=$(echo "$FILE" | sed -e "s|$BASEDIR||")
                local FILE_MD5_REF="$(getMD5 $MD5PATH $MD5_OLD)"

                # Check if file was part of old distribution; it has MD5
                if [[ ! -z $FILE_MD5_REF ]]; then
                    # File was part of old distribution; delete it
                    deleteFile "$DEST/$FILE"
                else
                    DEBUG "Did not delete unknown file $DEST/$FILE"
                fi
            fi
        fi
    done
    return 0 # TODO better way of preventing things to stop when $FLAT = 1? (related to set -o errexit)
}

# TODO add console output to the following functions performing operations?

# Adds a new file to the OPUS4 installation
function addFile() {
    [ -f "$2" ] && ( echo "File '$2' already exists."; exit )
    local ACCESS="$(stat -c %a "$1")"
    DRYRUN || install --preserve-timestamps --mode="$ACCESS" --owner="$OPUS4_USERNAME" --group="$OPUS4_GROUPNAME" "$1" "$2"
    UPDATELOG "ADDED" "$2"
    DEBUG "Added file $2"
}

# Updates an unmodified file of the OPUS4 installation
function replaceFile() {
    local ACCESS="$(stat -c %a "$1")"
    DRYRUN || install --preserve-timestamps --mode="$ACCESS" --owner="$OPUS4_USERNAME" --group="$OPUS4_GROUPNAME" "$1" "$2"
    UPDATELOG "REPLACED" "$2"
    DEBUG "Replaced file $2"
}

# Deletes a file from the OPUS4 installation
function deleteFile() {
    DRYRUN || rm "$1"
    UPDATELOG "DELETED" "$1"
    DEBUG "Deleted file $1"
}

# Deletes a folder from the OPUS4 installation
# TODO When parent folders are deleted, no entries appear in UPDATE log.
function deleteFolder() {
    local FOLDER="$1"
    local DELETE_EMPTY=1
    if [[ ! -z "$2" ]] && [[ "$2" = 'empty' ]]; then
        DELETE_EMPTY=0
    fi
    if [[ $DELETE_EMPTY == 0 ]]; then
        DRYRUN || rmdir --ignore-fail-on-non-empty --parents "$FOLDER"
    else
        DRYRUN || rm -rf "$FOLDER"
    fi
    UPDATELOG "DELETED" "$FOLDER"
    DEBUG "Deleted folder $FOLDER"
}

# Creates a folder
# TODO Can -p always be used or should it be selectable by parameter?
function createFolder() {
    [ "$_DRYRUN" -eq 0 ] && install --owner="$OPUS4_USERNAME" --group="$OPUS4_GROUPNAME" --directory "$1"
    UPDATELOG "CREATED" "$1"
    DEBUG "Created folder $1"
}

# Rename a file
# Used to move modified files out of the way.
function renameFile() {
    DRYRUN || mv "$1" "$2"
    UPDATELOG "RENAMED" "$1 => `basename $2`"
    DEBUG "Renamed file $1"
}

# Move file
# Used to move files to a different folder
function moveFile() {
    DRYRUN || mv "$1" "$2"
    UPDATELOG "MOVED" "$1 => $2"
    DEBUG "Moved file from '$1' to '$2'"
}

# Sets the global variables used across scripts
# TODO Obviously some redundancy here. Fix later.
function setVars() {
    BASEDIR=$OPUS_UPDATE_BASEDIR
    BASE_SOURCE=$OPUS_UPDATE_BASE_SOURCE
    MD5_OLD=$OPUS_UPDATE_MD5_OLD
    MD5_NEW=$OPUS_UPDATE_MD5_NEW
    _UPDATELOG=$OPUS_UPDATE_LOG
    VERSION_OLD=$OPUS_UPDATE_VERSION_OLD
    VERSION_NEW=$OPUS_UPDATE_VERSION_NEW
    SCRIPTPATH=$OPUS_UPDATE_SCRIPTPATH
    OPUS4_USERNAME=$OPUS_UPDATE_USERNAME
    OPUS4_GROUPNAME=$OPUS_UPDATE_GROUPNAME
}

# Calculates the changes between two releases for a folder.
# Compares MD5 hashes of two releases.
# NOTE: This function does not list folders that have been added or removed!
function calculateChanges() {
    local MD5PATH="$1" # relative path in distribution
    local OUTPUT="$2"

    # File already exists at target destination
    echo "Checking if file $DEST/$MD5PATH/$FILE changed between releases."
    echo "Layout changes between releases $VERSION_OLD and $VERSION_NEW:" > "$OUTPUT";

    # Iterate over old MD5 entries to catch MODIFIED and DELETED files
    grep -e "$MD5PATH" "$MD5_OLD" |
    while read LINE; do
        local HASH_OLD="$(echo "$LINE" | cut -b 1-32)"
        local FILE="$(echo "$LINE" | cut -b 34-)"
        # DEBUG $FILE
        # DEBUG $HASH_OLD
        local HASH_NEW="$(getMD5 "$FILE" "$MD5_NEW")"
        # DEBUG $HASH_NEW

        if [[ ! -n $HASH_NEW ]]; then
            echo 'DELETED  =>'"$FILE" >> "$OUTPUT";
        else
            if [[ $HASH_OLD != $HASH_NEW ]]; then
                # File was modified
               echo 'MODIFIED =>'"$FILE" >> "$OUTPUT";
            fi
        fi
    done

    # Iterate over new MD5 entries to catch ADDED files
    grep -e "$MD5PATH" "$MD5_NEW" |
    while read LINE; do
        local HASH_NEW="$(echo "$LINE" | cut -b 1-32)"
        local FILE="$(echo "$LINE" | cut -b 34-)"
        # DEBUG $FILE
        # DEBUG $HASH_OLD
        local HASH_OLD="$(getMD5 "$FILE" "$MD5_OLD")"
        # DEBUG $HASH_NEW
        if [[ ! -n $HASH_OLD ]]; then
            echo 'ADDED    =>'"$FILE" >> "$OUTPUT";
        fi
    done
}


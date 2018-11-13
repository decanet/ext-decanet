#!/bin/bash


function list_backups() {
        for i in `seq 0 10`
        do
                if [ -d /var/backupdecanet/$1/`date --date="$i days ago" +"%Y-%m-%d"` ]
                then
                        ls -1 /var/backupdecanet/$1/`date --date="$i days ago" +"%Y-%m-%d"`|sed -e "s/.tar.gz//g"
                        exit 0;
                fi
        done
}

function list_days() {
        for i in `seq 0 365`
        do
                if [ -f /var/backupdecanet/$2/`date --date="$i days ago" +"%Y-%m-%d"`/$1.tar.gz ]
                then
                        echo `date --date="$i days ago" +"%Y-%m-%d"`
                fi
        done
        exit 0
}

function restore() {
        if [[ `date -d "$3" +"%u"` -lt 7 && $2 == 'ftp' ]]
        then
                echo -e "`date -d "$3 -$(date -d "$3" +"%u") days" +"%Y-%m-%d"`\n`date -d "$3" +"%Y-%m-%d"`" > /etc/decanet/restore/$2/$1
        else
                echo `date -d "$3" +"%Y-%m-%d"` > /etc/decanet/restore/$2/$1
        fi
}

function current() {
        ls -1 /etc/decanet/restore/$1/
}

case $1 in
        list)
                if [ $# -eq 1 ]
                then
                        echo "Backup type needed"
                fi
                list_backups $2;;
        days)
                if [ $# -eq 2 ]
                then
                        echo "Backup type needed";
                        exit 2
                fi

                if [ $# -eq 1 ]
                then
                        echo "Backup name needed";
                        exit 2
                fi
                list_days $2 $3;;
        restore)
                 if [ $# -eq 3 ]
                then
                        echo "Day is needed";
                        exit 2
                fi

                if [ $# -eq 2 ]
                then
                        echo "Backup type needed";
                        exit 2
                fi

                if [ $# -eq 1 ]
                then
                        echo "Backup name needed";
                        exit 2
                fi

                restore $2 $3 $4;;
        status)
                if [ $# -eq 1 ]
                then
                        echo "Backup type needed";
                        exit 2
                fi

                current $2;;
        *)
                echo "Command not found";
                exit 2;;
esac
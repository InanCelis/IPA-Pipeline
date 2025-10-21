#echo "Enter Path of Kinsta Install. Example: /www/install_640/public"
#read hostdirectory

#newhostdirectory=$(cut -d/ -f3 <<< $hostdirectory)

#echo "Please Enter the Kinsta External IP"
#read externalip

#echo Kinsta Port
#read port
#sftpuser=$(echo $newhostdirectory| cut -d'_' -f 1)
#hostname=$sftpuser.kinsta.cloud

echo
echo "Enter the Kinsta Root or User SSH command"
echo
echo "Example ssh root@34.74.12.94 -p 20442"
echo
read sshcom
sshwithuser=`echo $sshcom | cut -d " " -f2` 
onlyuser=`echo $sshwithuser | cut -d "@" -f1` 
onlyip=`echo $sshwithuser | cut -d "@" -f2`
portnum=`echo $sshcom | cut -d " " -f4` 

if [[ $onlyuser == "root" ]] ; then
    echo
    echo "Enter the Kinsta Install username"
    read installuser
    rootuser=true
else
    installuser=$onlyuser
fi

#Get Location of WP-config

if [ -f wp-config.php ] ; then
wpconf="wp-config.php"
else
    if [ -f ../wp-config.php ] ; then
        echo "wp-config one level above"
        wpconf="../wp-config.php"
    else
      echo "Can't fine WP-Config file. Searching Now"
      find . -name "wp-config.php"
      echo
      echo "Enter custom wp-config path? y/n"
      read custompathrequest
      if [[ $custompathrequest == "yes" || $custompathrequest == "y" || $custompathrequest == "YES" || $custompathrequest == "Y" ]] ; then
        echo
        echo "Enter Full Path"
        read wpconf
      else
        echo
        echo "Exiting Script as no wp-config file is found"
        echo
        echo "Exit script? y/n"
        read exitprompt
        
              if [[ $exitprompt == "yes" || $exitprompt == "y" || $exitprompt == "YES" || $exitprompt == "Y" ]] ; then
                echo "Exiting Script"
                exit
              fi

      fi
      
    fi
fi

WPDBNAME=`cat $wpconf | grep DB_NAME | cut -d \' -f 4`
WPDBUSER=`cat $wpconf | grep DB_USER | cut -d \' -f 4`
WPDBPASS=`cat $wpconf | grep DB_PASSWORD | cut -d \' -f 4`
WPDBHOST=`cat $wpconf | grep DB_HOST | cut -d \' -f 4`


#WPDBNAME=`cat wp-config.php | grep DB_NAME | cut -d \' -f 4`
#WPDBUSER=`cat wp-config.php | grep DB_USER | cut -d \' -f 4`
#WPDBPASS=`cat wp-config.php | grep DB_PASSWORD | cut -d \' -f 4`
#WPDBHOST=`cat wp-config.php | grep DB_HOST | cut -d \' -f 4`

if [[ "$WPDBHOST" =~ [:] ]]; then

    if [[ "$WPDBHOST" =~ [localhost] ]]; then
    
    echo
    echo "Database dump started. This can take awhile for large databases"
    # Attempt to use mariadb-dump first
    if command -v mariadb-dump &> /dev/null; then
        echo "Attempting database dump with mariadb-dump (remote host with port)..." >&2
        mariadb-dump --default-character-set=utf8mb4 --no-tablespaces --skip-ssl -P "$dbport" -h "$newdbhost" -u "$WPDBUSER" -p"$WPDBPASS" "$WPDBNAME" > kinsta-migration4321.sql
        local_dump_status=$?
    else
        # If mariadb-dump not found, set status to non-zero to force fallback
        local_dump_status=1
        echo "mariadb-dump not found." >&2
    fi

    # If mariadb-dump failed or wasn't found, try mysqldump as a fallback
    if [ $local_dump_status -ne 0 ]; then
        if command -v mysqldump &> /dev/null; then
            echo "Falling back to mysqldump (remote host with port)..." >&2
            mysqldump --default-character-set=utf8mb4 --no-tablespaces --skip-ssl -P "$dbport" -h "$newdbhost" -u "$WPDBUSER" -p"$WPDBPASS" "$WPDBNAME" > kinsta-migration4321.sql
            local_dump_status=$?
        else
            # Neither found
            local_dump_status=1 # Ensure status is failure
            echo "Neither mariadb-dump nor mysqldump found." >&2
        fi
    fi

    # Now check the final status of the dump operation
    if [ $local_dump_status -eq 0 ] ; then
        echo "Database dump looked successful."
    else
        echo "CRITICAL ERROR: Database dump failed (remote host with port). Check the error messages above." >&2
        echo "Please ensure either 'mariadb-dump' or 'mysqldump' is available on this server and that the database credentials are correct." >&2
        echo "Press enter to proceed (script may not function correctly)."
        read mysqldumpfailenter_critical
    fi

    else # This 'else' corresponds to 'if [[ "$WPDBHOST" =~ [:] ]]'
    echo port detected
    #WPDBHOST=`dig +short $WPDBHOST`
    newdbhost=`echo $WPDBHOST | cut -d: -f1`
    dbport=`echo $WPDBHOST | cut -d: -f2`
    echo "$newdbhost"
    echo "$dbport"
    #newdbhost=`dig +short $newdbhost`

    echo
    echo "Database dump started. This can take awhile for large databases"
    # Attempt to use mariadb-dump first
    if command -v mariadb-dump &> /dev/null; then
        echo "Attempting database dump with mariadb-dump (remote host with port)..." >&2
        mariadb-dump --default-character-set=utf8mb4 --no-tablespaces --skip-ssl -P "$dbport" -h "$newdbhost" -u "$WPDBUSER" -p"$WPDBPASS" "$WPDBNAME" > kinsta-migration4321.sql
        local_dump_status=$?
    else
        # If mariadb-dump not found, set status to non-zero to force fallback
        local_dump_status=1
        echo "mariadb-dump not found." >&2
    fi

    # If mariadb-dump failed or wasn't found, try mysqldump as a fallback
    if [ "$local_dump_status" -ne 0 ]; then # Quote local_dump_status for robustness
        if command -v mysqldump &> /dev/null; then
            echo "Falling back to mysqldump (remote host with port)..." >&2
            mysqldump --default-character-set=utf8mb4 --no-tablespaces --skip-ssl -P "$dbport" -h "$newdbhost" -u "$WPDBUSER" -p"$WPDBPASS" "$WPDBNAME" > kinsta-migration4321.sql
            local_dump_status=$?
        else
            # Neither found
            local_dump_status=1 # Ensure status is failure
            echo "Neither mariadb-dump nor mysqldump found." >&2
        fi
    fi

    # Now check the final status of the dump operation
    if [ "$local_dump_status" -eq 0 ] ; then # Quote local_dump_status for robustness
        echo "Database dump looked successful."
    else
        echo "CRITICAL ERROR: Database dump failed (remote host with port). Check the error messages above." >&2
        echo "Please ensure either 'mariadb-dump' or 'mysqldump' is available on this server and that the database credentials are correct." >&2
        echo "Press enter to proceed (script may not function correctly)."
        read mysqldumpfailenter_critical
    fi
    fi # This 'fi' closes the 'if [[ "$WPDBHOST" =~ [localhost] ]]' block

else
echo no port detected
echo
echo "Database dump started. This can take awhile for large databases"
    # Attempt to use mariadb-dump first
    if command -v mariadb-dump &> /dev/null; then
        echo "Attempting database dump with mariadb-dump (remote host, no explicit port)..." >&2
        mariadb-dump --default-character-set=utf8mb4 --no-tablespaces --skip-ssl -u "$WPDBUSER" -p"$WPDBPASS" --host="$WPDBHOST" "$WPDBNAME" > kinsta-migration4321.sql
        local_dump_status=$?
    else
        # If mariadb-dump not found, set status to non-zero to force fallback
        local_dump_status=1
        echo "mariadb-dump not found." >&2
    fi

    # If mariadb-dump failed or wasn't found, try mysqldump as a fallback
    if [ $local_dump_status -ne 0 ]; then
        if command -v mysqldump &> /dev/null; then
            echo "Falling back to mysqldump (remote host, no explicit port)..." >&2
            mysqldump --default-character-set=utf8mb4 --no-tablespaces --skip-ssl -u "$WPDBUSER" -p"$WPDBPASS" --host="$WPDBHOST" "$WPDBNAME" > kinsta-migration4321.sql
            local_dump_status=$?
        else
            # Neither found
            local_dump_status=1 # Ensure status is failure
            echo "Neither mariadb-dump nor mysqldump found." >&2
        fi
    fi

    # Now check the final status of the dump operation
    if [ $local_dump_status -eq 0 ] ; then
        echo "Database dump looked successful."
    else
        echo "CRITICAL ERROR: Database dump failed (remote host, no explicit port). Check the error messages above." >&2
        echo "Please ensure either 'mariadb-dump' or 'mysqldump' is available on this server and that the database credentials are correct." >&2
        echo "Press enter to proceed (script may not function correctly)."
        read mysqldumpfailenter_critical
    fi

fi

if compgen -c | grep -w "rsync" ; then
#echo "Please Enter the Kinsta External IP"
#read externapiprsync
if [[ $wpconf == "../wp-config.php" ]] ; then
rsync -avz -e "ssh -p $portnum" ../wp-config.php $installuser@$onlyip:/www/*/public/
fi


rsync -avz --exclude 'wp-content/cache' --exclude 'wp-content/wpvividbackups' --exclude 'wp-content/uploads/backwpup*' --exclude 'wp-content/backups*' --exclude 'wp-content/updraft*' --exclude 'wp-content/ai1wm-backups*' --exclude='wp-content/plugins/all-in-one-wp-migration/storage/' --exclude='wp-content/backups-dup-pro/' --exclude 'wp-content/uploads/backupbuddy_backups*' --exclude 'wp-content/uploads/pb_backupbuddy*' --exclude 'wp-snapshots' --exclude './dupx-installer' -e "ssh -p $portnum" ./ $installuser@$onlyip:/www/*/public/
    if [ $? -eq 0 ] ; then
        echo "Rsync looked successful"
    else
        echo "Rsync may of failed. Check the error above. If it seems it didn't finish, Re-try running script to make sure everything was transferred"
        echo
        echo "Press enter to proceed"
        read rsyncfailenter
    fi
else
# trying scp
if compgen -c | grep -w "scp" ; then
#echo "Please Enter the Kinsta External IP"
#read externapip

    if [[ $onlyuser == "root" ]] ; then
    
        echo "Using External IP Already"
   else
        echo "Enter External ip"
        read newexternal
        externalip=$newexternal
    fi
    
if [[ $wpconf == "../wp-config.php" ]] ; then
scp -rCP $portnum ../wp-config.php $installuser@$externalip:$sitefullpath/wp-config.php
fi

echo "Enter the Kinsta Site Path example: /www/install_xxx/public"
read sitefullpath
scp -rCP $portnum ./* $installuser@$externalip:$sitefullpath/
else

echo "Rsync and SCP aren't available"
fi
fi
rm -v kinsta-migration4321.sql

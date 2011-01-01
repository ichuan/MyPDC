#!/bin/sh

backup_dir=/var/www/pdc/application/data/backup/

pg_dump -Upostgres pdc -f ${backup_dir}pdc.`date +%F`.7z -Fc -Z9

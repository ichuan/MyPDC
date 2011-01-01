#!/bin/sh

mkdir -p ../application/data/cache
chmod 777 ../application/data/cache
mkdir -p ../application/data/session
chmod 777 ../application/data/session
mkdir -p ../application/data/log
touch ../application/data/log/ui.log
touch ../application/data/log/apache.log
chmod 777 -R ../application/data/log
cp ../application/configs/application.sample.php ../application/configs/application.php
cp ../application/configs/defines.sample.php ../application/configs/defines.php
mkdir -p ../application/data/static/image/captcha/
chmod 777 ../application/data/static/image/captcha/
mkdir -p ../library/htmlpurifier/standalone/HTMLPurifier/DefinitionCache/
chmod 777 -R ../library/htmlpurifier/standalone/HTMLPurifier/DefinitionCache/
mkdir -p ../application/data/backup
chmod 777 ../application/data/backup

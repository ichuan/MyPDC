#!/bin/sh
openssl req -x509 -nodes -days 365 -newkey rsa:1024 -sha1 -subj \
  '/C=CN/ST=BJ/L=HLG/CN=mypdc.info' -keyout \
  myrsakey.pem -out myrsacert.pem

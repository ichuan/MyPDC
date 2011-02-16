#!/usr/bin/env python
# coding:utf-8
# yc@2011-2-16

import sys, os, pg
from datetime import datetime
from zipfile import ZipFile


if len(sys.argv) != 3:
	print 'usage: python export_code.py <member_id> <out_file>'
	sys.exit(-1)

try:
	member_id = int(sys.argv[1])
	out_file  = sys.argv[2]
except:
	sys.exit(-1)
finally:
	if member_id < 2:
		sys.exit(-1)

langs = {
  1 : '4cs',
  2 : '6502acme',
  3 : '6502kickass',
  4 : '6502tasm',
  5 : '68000devpac',
  6 : 'abap',
  7 : 'actionscript',
  8 : 'actionscript3',
  9 : 'ada',
  10 : 'algol68',
  11 : 'apache',
  12 : 'applescript',
  13 : 'apt_sources',
  14 : 'asm',
  15 : 'asp',
  16 : 'autoconf',
  17 : 'autohotkey',
  18 : 'autoit',
  19 : 'avisynth',
  20 : 'awk',
  21 : 'bash',
  22 : 'basic4gl',
  23 : 'bf',
  24 : 'bibtex',
  25 : 'blitzbasic',
  26 : 'bnf',
  27 : 'boo',
  28 : 'c',
  29 : 'c_mac',
  30 : 'caddcl',
  31 : 'cadlisp',
  32 : 'cfdg',
  33 : 'cfm',
  34 : 'chaiscript',
  35 : 'cil',
  36 : 'clojure',
  37 : 'cmake',
  38 : 'cobol',
  39 : 'cpp',
  40 : 'cpp-qt',
  41 : 'csharp',
  42 : 'css',
  43 : 'cuesheet',
  44 : 'd',
  45 : 'dcs',
  46 : 'delphi',
  47 : 'diff',
  48 : 'div',
  49 : 'dos',
  50 : 'dot',
  51 : 'e',
  52 : 'ecmascript',
  53 : 'eiffel',
  54 : 'email',
  55 : 'erlang',
  56 : 'f1',
  57 : 'fo',
  58 : 'fortran',
  59 : 'freebasic',
  60 : 'fsharp',
  61 : 'gambas',
  62 : 'gdb',
  63 : 'genero',
  64 : 'genie',
  65 : 'gettext',
  66 : 'glsl',
  67 : 'gml',
  68 : 'gnuplot',
  69 : 'go',
  70 : 'groovy',
  71 : 'gwbasic',
  72 : 'haskell',
  73 : 'hicest',
  74 : 'hq9plus',
  75 : 'html4strict',
  76 : 'icon',
  77 : 'idl',
  78 : 'ini',
  79 : 'inno',
  80 : 'intercal',
  81 : 'io',
  82 : 'j',
  83 : 'java',
  84 : 'java5',
  85 : 'javascript',
  86 : 'jquery',
  87 : 'kixtart',
  88 : 'klonec',
  89 : 'klonecpp',
  90 : 'latex',
  91 : 'lb',
  92 : 'lisp',
  93 : 'locobasic',
  94 : 'logtalk',
  95 : 'lolcode',
  96 : 'lotusformulas',
  97 : 'lotusscript',
  98 : 'lscript',
  99 : 'lsl2',
  100 : 'lua',
  101 : 'm68k',
  102 : 'magiksf',
  103 : 'make',
  104 : 'mapbasic',
  105 : 'matlab',
  106 : 'mirc',
  107 : 'mmix',
  108 : 'modula2',
  109 : 'modula3',
  110 : 'mpasm',
  111 : 'mxml',
  112 : 'mysql',
  113 : 'newlisp',
  114 : 'nsis',
  115 : 'oberon2',
  116 : 'objc',
  117 : 'objeck',
  118 : 'ocaml',
  119 : 'ocaml-brief',
  120 : 'oobas',
  121 : 'oracle11',
  122 : 'oracle8',
  123 : 'oxygene',
  124 : 'oz',
  125 : 'pascal',
  126 : 'pcre',
  127 : 'per',
  128 : 'perl',
  129 : 'perl6',
  130 : 'pf',
  131 : 'php',
  132 : 'php-brief',
  133 : 'pic16',
  134 : 'pike',
  135 : 'pixelbender',
  136 : 'plsql',
  137 : 'postgresql',
  138 : 'povray',
  139 : 'powerbuilder',
  140 : 'powershell',
  141 : 'progress',
  142 : 'prolog',
  143 : 'properties',
  144 : 'providex',
  145 : 'purebasic',
  146 : 'python',
  147 : 'q',
  148 : 'qbasic',
  149 : 'rails',
  150 : 'rebol',
  151 : 'reg',
  152 : 'robots',
  153 : 'rpmspec',
  154 : 'rsplus',
  155 : 'ruby',
  156 : 'sas',
  157 : 'scala',
  158 : 'scheme',
  159 : 'scilab',
  160 : 'sdlbasic',
  161 : 'smalltalk',
  162 : 'smarty',
  163 : 'sql',
  164 : 'systemverilog',
  165 : 'tcl',
  166 : 'teraterm',
  167 : 'text',
  168 : 'thinbasic',
  169 : 'tsql',
  170 : 'typoscript',
  171 : 'unicon',
  172 : 'vala',
  173 : 'vb',
  174 : 'vbnet',
  175 : 'verilog',
  176 : 'vhdl',
  177 : 'vim',
  178 : 'visualfoxpro',
  179 : 'visualprolog',
  180 : 'whitespace',
  181 : 'whois',
  182 : 'winbatch',
  183 : 'xbasic',
  184 : 'xml',
  185 : 'xorg_conf',
  186 : 'xpp',
  187 : 'z80',
  188 : 'zxbasic',
}


try:
	db = pg.connect('pdc', 'localhost', 5432, None, None, 'postgres')
except:
	print 'cannot connect to db'
	sys.exit(-1)

zip_fp = ZipFile(open(out_file, 'wb'), mode='w')
obj = db.query(r'select * from code where member_id=%d' % member_id)
readme = []
for i in obj.dictresult():
	try:
		lang = langs[int(i['language_id'])]
	except:
		lang = 'unknown'
	source = '%s/%d.txt' % (lang, i['id'])
	highlight = '%s/%d.html' % (lang, i['id'])
	readme.append('''%s\r\n名称: %s\r\n大小: %d字节\r\n添加日期: %s\r\n描述信息: %s\r\n'''\
				 %(source, i['title'], i['codebytes'], i['created'], i['description']))
	zip_fp.writestr(source, i['code'])
	zip_fp.writestr(highlight, '%s%s' % ('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">', i['highlighted']))
readme = '\r\n-----------------------------\r\n'.join(readme)
zip_fp.writestr('readme.txt', readme)
zip_fp.close()

db.close()

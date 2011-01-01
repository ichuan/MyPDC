#!/usr/bin/env python
# coding: utf-8
# author: yc

import sys, os, pg
from datetime import datetime
sys.path.append(os.path.join(sys.path[0], '../library'))

from pyExcelerator import *

if len(sys.argv) != 3:
    print 'usage: python note2xls.py <member_id> <out_file>'
    sys.exit(-1)

try:
    member_id = int(sys.argv[1])
    out_file  = sys.argv[2]
except:
    sys.exit(-1)
finally:
    if member_id < 2:
        sys.exit(-1)
    
title_style = XFStyle()
title_style.font.name = 'Tahoma'
title_style.font.bold = True

wb = Workbook()
ws0 = wb.add_sheet(u'我的记事本')

col = 0
for title in [u'标题', u'内容', u'标签', u'创建时间', u'标记', u'置顶']:
    ws0.write(0, col, title, title_style)
    col += 1

try:
    db = pg.connect('pdc', 'localhost', 5432, None, None, 'postgres')
except:
    print 'cannot connect to db'
    sys.exit(-1)

obj = db.query('SELECT note.*, array_agg(note_tag.tag_name) AS tags, array_agg(note_tag.counter) AS tag_counter FROM note INNER JOIN note_tag ON note_tag.id=ANY(note.tag_ids) WHERE (note.member_id=%d) GROUP BY note.id, note.member_id, note.title, note.checked, note.created, note.tag_ids, note.top, note.content, note.markdowned,note.tag_ids, note.top ORDER BY note.top DESC, note.id DESC' % member_id)
row = 1
for i in obj.dictresult():
    ws0.write(row, 0, i['title'].decode('utf-8'))
    ws0.write(row, 1, i['content'].decode('utf-8'))
    ws0.write(row, 2, i['tags'].decode('utf-8').strip('{}').replace('",RESERVED,"', ''))
    ws0.write(row, 3, i['created'].decode('utf-8'))
    ws0.write(row, 4, u'是' if i['checked'] == 't' else u'否')
    ws0.write(row, 5, u'是' if i['top'] else u'否')
    row += 1

row += 1
ws0.write(row, 0, u'记事总数', title_style)
ws0.write(row, 1, str(row - 2))
row += 1
ws0.write(row, 0, u'导出时间', title_style)
ws0.write(row, 1, datetime.now().isoformat(' ').split('.')[0])
row += 1
ws0.write(row, 0, u'在线记事本', title_style)
ws0.write(row, 1, 'http://mypdc.info/note')
ws0.set_link(row, 1, 'http://mypdc.info/note', description='MyPDC Note')

ws0.col(0).width = 7000 # title
ws0.col(1).width = 21000 # content
ws0.col(2).width = 4000 # tags
ws0.col(3).width = 6100 # date
ws0.col(4).width = 1500 # mark
ws0.col(5).width = 1500 # top

db.close()
wb.save(out_file)

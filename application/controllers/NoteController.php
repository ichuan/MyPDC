<?php
/**
* NoteController
*
* @author yc <iyanchuan@gmail.com>
*/

class NoteController extends Zend_Controller_Action
{

    public function init()
    {
        $this->view->headTitle()->append(_t('记事本'));
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if (isset($request->filter)){
            $filter = $request->getParam(1);
            switch ($request->filter){
                case 'tag':
                    $request->setParam('tag', $filter);
                    break;
                default:
                    return $this->_helper->message(array('fail', _t('不存在这种语言')));
            }
        }
        $params = array(
            'member_id' => $this->view->user->id,
            'cols'      => array('id', 'member_id', 'title', 'checked', 'created', 'tag_ids', 'top'),
            'tag'       => getArrayFromString($request->getParam('tag', '')),
            'title'     => trim($request->getParam('title', '')),
            'search'    => trim($request->getParam('search', '')),
            'page'      => (int)$request->getParam('page', 1),
            'perpage'   => 10,
        );
        $n = ICE_Global::getModel('Note');
        $paginator = $n->getsByParams($params);
        $this->view->notes = $paginator->getCurrentItems();
        $this->view->pages = $paginator->getPages();
        unset($params['member_id'], $params['cols'], $params['perpage']);
        $this->view->params = $params;
        if (isset($request->new))
            $this->view->new = $request->new;
    }

    public function tagsAction()
    {
        $t = ICE_Global::getModel('Tag');
        $t->resetTableName('note_tag');
        $memberId = $this->view->user->id;
        $id = ICE_Cache::id('note.tags.member' . $memberId);
        $this->view->tags = ICE_Cache::load($id, function() use ($t, $memberId){
            return $t->getsByMemberAndSearch($memberId, null, 50, 0, 'counter DESC')->toArray();
        });
    }

    public function exportAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->view->layout()->disableLayout();
        $request = $this->getRequest();
        if ($request->getUserParam('xls') !== null){
            set_time_limit(60); // 1 miniute
            $py = realpath(APPLICATION_PATH . '/../bin/note2xls.py');
            $outFile = tempnam('/tmp', 'note');
            $cmd = sprintf('python %s %d %s', $py, $this->view->user->id, $outFile);
            exec($cmd, $o, $r);
            if ($r === 0){
                $size = filesize($outFile);
                ICE_Audit::record(sprintf('导出记事为 Excel（大小：%s）', friendlySize($size)), $this->view->user->id, ICE_Audit::NOTE);
                $this->_helper->download($outFile, 'notes.xls', $size, false, true, false);
            }
            unlink($outFile);
        } elseif ($request->getUserParam('rss') !== null){
            $params = array(
                'member_id' => $this->view->user->id,
                'cols'      => array('id', 'member_id', 'title', 'content', 'markdowned', 'created', 'tag_ids'),
                'fetchAll'  => true,
            );
            $n = ICE_Global::getModel('Note');
            $feed = array(
                'title'     => sprintf(_t('%s 的记事本'), $this->view->user->username),
                'link'      => 'http://mypdc.info/note',
                'published' => TIMESTAMP,
                'charset'   => 'utf-8',
                'author'    => $this->view->user->username,
                'email'     => $this->view->user->email,
                'entries'   => array(),
            );
            foreach ($n->getsByParams($params) as $i){
                $i['link']          = 'http://mypdc.info/note';
                $i['description']   = $i['content'];
                $i['content']       = $i['markdowned'];
                $i['lastUpdate']    = strtotime($i['created']);
                foreach (getTagsFromPsqlStr($i['tags']) as $t)
                    $i['category'][] = array('term' => $t, 'scheme' => 'http://mypdc.info/note/tag/' . urlencode($t));
                $feed['entries'][] = $i;
            }
            $zf = Zend_Feed::importArray($feed, 'rss');
            $zf->send();
        }
    }
}


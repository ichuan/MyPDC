<?php

class AjaxController extends Zend_Controller_Action
{
    public function tagsAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->view->layout()->disableLayout();
        $request = $this->getRequest();
        $t = ICE_Global::getModel('Tag');
        $limit = (int)$request->limit;
        if ($limit <= 0)
            $limit = 10;
        $search = $request->q;
        echo json_encode($t->getsByMemberAndSearch($this->view->user->id, $search, $limit)->toArray());
    }

    public function memberAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->view->layout()->disableLayout();
        $request = $this->getRequest();
        $m = ICE_Global::getModel('Member');
        if (isset($request->delete)){
            $id = (int)$request->delete;
            if ($id > 1 && $m->delete('id=' . $id))
                echo 'ok';
        } else if (isset($request->block)){
            $id = (int)$request->block;
            if ($id > 1 && $m->update(array('blocked'=>'true'), 'id=' . $id))
                echo 'ok';
        } else if (isset($request->unblock)){
            $id = (int)$request->unblock;
            if ($id > 1 && $m->update(array('blocked'=>'false'), 'id=' . $id))
                echo 'ok';
        }
        die();
    }

    public function noteAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->view->layout()->disableLayout();
        $request = $this->getRequest();
        $n = ICE_Global::getModel('Note');
        if ($request->getUserParam('content') !== null){
            $id = (int)$request->content;
            if ($id > 0){
                $row = $n->getByParams(array(
                    'id'    => $id,
                    'cols'  => array('markdowned'),
                    'member_id' => $this->view->user->id,
                ));
                if (count($row))
                    echo $row['markdowned'];
            }
        } else if ($request->getUserParam('edit') !== null){
            $id = (int)$request->edit;
            if ($id > 0){
                $row = $n->getByParams(array(
                    'id'    => $id,
                    'cols'  => array('id', 'tag_ids', 'content'),
                    'member_id' => $this->view->user->id,
                ));
                if (count($row))
                    echo json_encode(array('id' => $id, 'content' => $row['content'], 'tags' => getTagsFromPsqlStr($row['tags'])));
            }
        } else if ($request->getUserParam('tags') !== null){
            $t = ICE_Global::getModel('Tag');
            $t->resetTableName('note_tag');
            $limit = (int)$request->limit;
            if ($limit <= 0)
                $limit = 10;
            $search = $request->q;
            echo json_encode($t->getsByMemberAndSearch($this->view->user->id, $search, $limit)->toArray());
        } else if ($request->getUserParam('check') !== null){
            $id = (int)$request->check;
            if ($id > 0){
                if ($n->update(array('checked'   => 'true'), array('id=' . $id, 'member_id=' . $this->view->user->id)))
                    echo '{"result":true}';
                else
                    echo '{"result":false}';
            }
        } else if ($request->getUserParam('uncheck') !== null){
            $id = (int)$request->uncheck;
            if ($id > 0){
                if ($n->update(array('checked'   => 'false'), array('id=' . $id, 'member_id=' . $this->view->user->id)))
                    echo '{"result":true}';
                else
                    echo '{"result":false}';
            }
        } else if ($request->getUserParam('pin') !== null){
            $id = (int)$request->pin;
            if ($id > 0){
                if ($n->update(array('top'   => 1), array('id=' . $id, 'member_id=' . $this->view->user->id)))
                    echo '{"result":true}';
                else
                    echo '{"result":false}';
            }
        } else if ($request->getUserParam('unpin') !== null){
            $id = (int)$request->unpin;
            if ($id > 0){
                if ($n->update(array('top'   => 0), array('id=' . $id, 'member_id=' . $this->view->user->id)))
                    echo '{"result":true}';
                else
                    echo '{"result":false}';
            }
        } else if ($request->getUserParam('delete') !== null){
            $id = (int)$request->delete;
            if ($id > 0){
                $row = $n->getByParams(array(
                    'id'    => $id,
                    'cols'  => array('id', 'tag_ids'),
                    'member_id' => $this->view->user->id,
                ));
                if (count($row)){
                    if ($n->delete(array('id=' . $id, 'member_id=' . $this->view->user->id))){
                        $redis = ICE_Global::get('redis');
                        $redis->decr('stat' . $this->view->user->id . 'totalMemo');
                        $tagIds = getTagIdsFromPsqlStr($row['tag_ids']);
                        if (count($tagIds)){
                            $t = ICE_Global::getModel('Tag');
                            $t->resetTableName('note_tag');
                            $t->decreaseCounter($tagIds);
                        }
                        echo '{"result":true}';
                    } else
                        echo '{"result":false}';
                }
            }
        } else if ($request->getUserParam('memo') == 'new' && $request->isPost() && isset($request->content)){
            $content = trim($request->content);
            if ($content != ''){
                $t = ICE_Global::getModel('Tag');
                $t->resetTableName('note_tag');
                $tags = getArrayFromString($request->tags);
                $tagIds = array();
                if (($id = $request->id) > 0){ // edit
                    $note = $n->getByParams(array(
                        'id'    => $id,
                        'cols'  => array('id', 'member_id', 'tag_ids'),
                        'member_id' => $this->view->user->id,
                    ));
                    if ($note === NULL)
                        return $this->_helper->message(array('fail', _t('不存在此记事')));
                    $note['tags'] = getTagsFromPsqlStr($note['tags']);
                    $note['tag_ids'] = getTagIdsFromPsqlStr($note['tag_ids']);
                    $newTags = array_diff($tags, $note['tags']);
                    $delTags = array_diff($note['tags'], $tags);
                    $decTagIds = $incTagIds = array();
                    // insert new tags
                    foreach ($newTags as $tag){
                        $obj = $t->getByMemberAndName($this->view->user->id, $tag);
                        if ($obj === NULL)
                            $tagIds[] = $t->insert(array(
                                'tag_name'  => $tag,
                                'member_id' => $this->view->user->id,
                            ));
                        else {
                            $tagIds[] = $obj->id;
                            $incTagIds[] = $obj->id;
                        }
                    }
                    // deal with old tags
                    foreach ($note['tags'] as $key => $tag){
                        if (in_array($tag, $delTags))
                            $decTagIds[] = (int)$note['tag_ids'][$key];
                        else
                            $tagIds[] = (int)$note['tag_ids'][$key];
                    }
                    if (count($decTagIds))
                        $t->decreaseCounter($decTagIds);
                    if (count($incTagIds))
                        $t->increaseCounter($incTagIds);
                } else { // new
                    foreach ($tags as $tag){
                        $obj = $t->getByMemberAndName($this->view->user->id, $tag);
                        if ($obj === NULL)
                            $tagIds[] = $t->insert(array(
                                'tag_name'  => $tag,
                                'member_id' => $this->view->user->id,
                            ));
                        else {
                            $tagIds[] = $obj->id;
                            $obj->counter = (int)$obj->counter + 1;
                            $obj->save();
                        }
                    }
                }
                if (count($tagIds) == 0)
                    $tagIds[] = 1;// tricks to enable join query
                require_once 'markdown.php';
                $markdowned = trim(htmlSanitize(Markdown($request->content)));
                $tmp = preg_split('/[\n\r]/', $content, 2);
                if (count($tmp) == 2)
                    $title = $tmp[0];
                else
                    $title = $content;
                $title = utf8SubStr($title, 0, NOTE_TITLE_LEN);
                # $title = str_replace(array("\n",'&nbsp;', '&amp;'), ' ', utf8SubStr(strip_tags($markdowned), 0, NOTE_TITLE_LEN));
                $time = DATETIME;
                if ($request->id){
                    $item = $n->getById($id);
                    $item->title        = $title;
                    $item->markdowned   = $markdowned;
                    $item->content      = $content;
                    $item->tag_ids      = makePgIntArr($tagIds);
                    $item->save();
                    $time = $item->created;
                    ICE_Audit::record(sprintf('修改记事（ID：%d）', $request->id), $this->view->user->id, ICE_Audit::NOTE);
                }
                else {
                    $id = $n->insert(array(
                        'title'         => $title,
                        'member_id'     => $this->view->user->id,
                        'markdowned'    => $markdowned,
                        'content'       => $content,
                        'tag_ids'       => makePgIntArr($tagIds),
                        'checked'       => 'false',
                        'created'       => DATETIME,
                    ));
                    $redis = ICE_Global::get('redis');
                    $redis->incr('stat' . $this->view->user->id . 'totalMemo');
                    ICE_Audit::record(sprintf('新增记事（ID：%d）', $id), $this->view->user->id, ICE_Audit::NOTE);
                }
                if ($id){
                    ICE_Cache::del(ICE_Cache::id('note.tags.member' . $this->view->user->id));
                    echo json_encode(array('id' => $id, 'title' => _h($title, false), 'time' => friendlyDate($time), 'tags' => $tags));
                }
            }
        }
        die();
    }
}


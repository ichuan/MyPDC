<?php

class CodeController extends Zend_Controller_Action
{

    public function init()
    {
        $this->view->headTitle()->append(_t('代码'));
    }
    public function indexAction()
    {
        $request = $this->getRequest();
        $defaultParams = array(
            'member_id' => $this->view->user->id,
            'perpage'   => ITEMS_PER_PAGE,
            'cols'      => array('id', 'title', 'language_id', 'created', 'codebytes'),
        );
        if (!isset($this->view->langs))
            $this->view->langs = include 'geshi/langs.all.php';

        // from /code/language/xx or /code/tag/yy
        if (isset($request->filter)){
            $filter = $request->getParam(1);
            switch ($request->filter){
                case 'language':
                    if (array_search($filter, $this->view->langs) !== FALSE)
                        $request->setParam('language', $filter);
                    break;
                case 'tag':
                    $request->setParam('tag', $filter);
                    break;
                default:
                    return $this->_helper->message(array('fail', _t('不存在这种语言或标签')));
            }
        }
        $c = ICE_Global::getModel('Code');
        $params = array(
            'tag'       => getArrayFromString($request->getParam('tag', '')),
            'language_ids'  => array(),
            'language'  => array(),
            'title'     => trim($request->getParam('title', '')),
            'page'      => (int)$request->getParam('page', 1),
        );
        foreach (getArrayFromString($request->getParam('language', '')) as $lang){
            $i = array_search($lang, $this->view->langs);
            if ($i !== FALSE){
                $params['language_ids'][] = $i;
                $params['language'][] = $lang;
            }
        }

        // for unique caching
        sort($params['tag']);
        sort($params['language']);

        if (ENABLE_CACHE){
            // we cache all pages of a member in ONE cache, so that a member's cache wont occupy too many small files
            // we cache by tags, which can be convenent in cleanning
            // first get an unique cache id
            $id = ICE_Cache::id('code', $this->view->user->id);
            $key = ICE_Cache::id($params);
            $data = ICE_Cache::get($id);
            if ($data === FALSE)
                $data = array();
            if (!isset($data[$key])){
                $paginator = $c->getsByParams(array_merge($defaultParams, $params)); // big query, Ouch! db hited!
                $data[$key][0] = $this->view->codes = $paginator->getCurrentItems(); // Ouch! db hited again!
                $data[$key][1] = $this->view->pages = $paginator->getPages(); // Ouch! COUNT() query performed!
                ICE_Cache::set($id, $data, array('code', 'pages', 'member' . $this->view->user->id));
            } else {
                $this->view->codes = $data[$key][0];
                $this->view->pages = $data[$key][1];
            }
        } else {
            $paginator = $c->getsByParams(array_merge($defaultParams, $params)); // big query, Ouch! db hited!
            $this->view->codes = $paginator->getCurrentItems(); // Ouch! db hited again!
            $this->view->pages = $paginator->getPages(); // Ouch! COUNT() query performed!
        }

        unset($params['language_ids']);
        $this->view->params = $params;
        $this->render('index');
    }

    public function newAction()
    {
        $this->view->langs = include 'geshi/langs.all.php';
        $this->view->op = 'new';
        $request = $this->getRequest();
        if ($request->isPost()){
            if (empty($request->title))
                $this->view->message = _t('请填写这段代码的名称');
            else if (!isset($request->language) || (($languageId = array_search($request->language, $this->view->langs)) === FALSE))
                $this->view->message = _t('请选择正确的编程语言');
            else if (empty($request->code))
                $this->view->message = _t('请填写代码内容');
            $tags = getArrayFromString($request->tags);
            if (isset($this->view->message))
                $this->view->code = array(
                    'title' => $request->title,
                    'code'  => $request->code,
                    'language' => $request->language,
                    'description' => $request->description,
                    'tags'  => $tags,
                );
            else {
                require_once 'markdown.php';
                $c = ICE_Global::getModel('Code');
                $t = ICE_Global::getModel('Tag');
                $tagIds = array();
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
                if (count($tagIds) == 0)
                    $tagIds[] = 1;
                $id = $c->insert(array(
                    'title'         => trim($request->title),
                    'member_id'     => $this->view->user->id,
                    'description'   => $request->description,
                    'markdowned'    => !empty($request->description) ? htmlSanitize(Markdown($request->description)) : '',
                    'code'          => $request->code,
                    'highlighted'   => Highlight($request->code, $request->language),
                    'tag_ids'       => makePgIntArr($tagIds),
                    'language_id'   => $languageId,
                    'created'       => DATETIME,
                    'codebytes'     => strlen($request->code),
                ));
                if ($id){
                    ICE_Audit::record(sprintf('新增代码（ID：%d）', $id), $this->view->user->id, ICE_Audit::CODE);
                    $redis = ICE_Global::get('redis');
                    $redis->incr('stat' . $this->view->user->id . 'totalCode');
                    $this->_redirect('/code/' . $id);
                }
            }
        }
        $this->render('edit');
    }

    public function editAction()
    {
        $request = $this->getRequest();
        $id = (int)$request->getParam(1);
        if ($id <= 0)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        $c = ICE_Global::getModel('Code');
        $code = $c->getByParams(array(
            'id'    => $id,
            'cols'  => array('id', 'title', 'member_id', 'description', 'code', 'tag_ids', 'language_id'),
            'member_id' => $this->view->user->id,
        ));
        if ($code === NULL)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        $code['tags'] = getTagsFromPsqlStr($code['tags']);
        $code['tag_ids'] = getTagIdsFromPsqlStr($code['tag_ids']);

        $this->view->langs = include 'geshi/langs.all.php';
        if (isset($this->view->langs[$code['language_id']]))
            $code['language'] = $this->view->langs[$code['language_id']];
        $this->view->code = $code;

        if ($request->isPost()){
            if (empty($request->title))
                $this->view->message = _t('请填写这段代码的名称');
            else if (!isset($request->language) || (($languageId = array_search($request->language, $this->view->langs)) === FALSE))
                $this->view->message = _t('请选择正确的编程语言');
            else if (empty($request->code))
                $this->view->message = _t('请填写代码内容');
            $tags = getArrayFromString($request->tags);
            if (isset($this->view->message))
                $this->view->code = array(
                    'id'    => $id,
                    'title' => $request->title,
                    'code'  => $request->code,
                    'language' => $request->language,
                    'description' => $request->description,
                    'tags'  => $tags,
                );
            else {
                require_once 'markdown.php';
                $t = ICE_Global::getModel('Tag');
                $item = $c->getById($id);
                $newTags = array_diff($tags, $code['tags']);
                $delTags = array_diff($code['tags'], $tags);
                $tagIds = $decTagIds = $incTagIds = array();
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
                foreach ($code['tags'] as $key => $tag){
                    if (in_array($tag, $delTags))
                        $decTagIds[] = (int)$code['tag_ids'][$key];
                    else
                        $tagIds[] = (int)$code['tag_ids'][$key];
                }
                if (count($decTagIds))
                    $t->decreaseCounter($decTagIds);
                if (count($incTagIds))
                    $t->increaseCounter($incTagIds);
                if (count($tagIds) == 0)
                    $tagIds[] = 1;
                // update code
                $item->title        = trim($request->title);
                $item->description  = $request->description;
                $item->markdowned   = !empty($request->description) ? htmlSanitize(Markdown($request->description)) : '';
                $item->code         = $request->code;
                $item->highlighted  = Highlight($request->code, $request->language);
                $item->tag_ids      = makePgIntArr($tagIds);
                $item->language_id  = $languageId;
                $item->codebytes    = strlen($request->code);
                $item->updated      = DATETIME;
                if ($item->save()){
                    ICE_Audit::record(sprintf('修改代码（ID：%d）', $id), $this->view->user->id, ICE_Audit::CODE);
                    $this->_redirect('/code/' . $id);
                }
            }
        }
        $this->view->op = 'edit';
    }

    public function deleteAction()
    {
        $request = $this->getRequest();
        $id = (int)$request->getParam(1);
        if ($id <= 0)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        $c = ICE_Global::getModel('Code');
        $code = $c->getByParams(array(
            'id'    => $id,
            'cols'  => array('id', 'tag_ids'),
            'member_id' => $this->view->user->id,
        ));
        $tagIds = getTagIdsFromPsqlStr($code['tag_ids']);
        if ($code === NULL)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        $c->delete('id=' . $id);
        if (count($tagIds))
            ICE_Global::getModel('Tag')->decreaseCounter($tagIds);
        ICE_Audit::record(sprintf('删除代码（ID：%d）', $id), $this->view->user->id, ICE_Audit::CODE);
        $redis = ICE_Global::get('redis');
        $redis->decr('stat' . $this->view->user->id . 'totalCode');
        $this->_helper->message(array('success', _t('已删除该代码')));
    }

    public function downloadAction()
    {
        $request = $this->getRequest();
        $id = (int)$request->getParam(1);
        if ($id <= 0)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        $c = ICE_Global::getModel('Code');
        $code = $c->getByParams(array(
            'id'    => $id,
            'cols'  => array('id', 'title', 'code', 'codebytes', 'language_id'),
            'member_id' => $this->view->user->id,
        ));
        if ($code === NULL)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        ICE_Audit::record(sprintf('下载代码（ID：%d）', $id), $this->view->user->id, ICE_Audit::CODE);
        $langs = include 'geshi/langs.all.php';
        $this->_helper->download($code['code'], $code['title'] . '.' . $langs[$code['language_id']], $code['codebytes']);
    }

    public function exportAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->view->layout()->disableLayout();
        $request = $this->getRequest();
            set_time_limit(60); // 1 miniute
            $py = realpath(APPLICATION_PATH . '/../bin/export_code.py');
            $outFile = tempnam('/tmp', 'code');
            $cmd = sprintf('python %s %d %s', $py, $this->view->user->id, $outFile);
            exec($cmd, $o, $r);
            if ($r === 0){
                $size = filesize($outFile);
                ICE_Audit::record(sprintf('导出代码为 Zip（大小：%s）', friendlySize($size)), $this->view->user->id, ICE_Audit::NOTE);
                $this->_helper->download($outFile, 'codes.zip', $size, false, true, false);
            }
            unlink($outFile);
    }

    public function viewAction()
    {
        $request = $this->getRequest();
        $id = (int)$request->getParam(1);
        if ($id <= 0)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        $c = ICE_Global::getModel('Code');
        $code = $c->getByParams(array(
            'id'    => $id,
            'cols'  => array('id', 'title', 'highlighted', 'codebytes', 'language_id', 'markdowned', 'tag_ids', 'created', 'updated'),
            'member_id' => $this->view->user->id,
        ));
        if ($code === NULL)
            return $this->_helper->message(array('fail', _t('不存在此段代码')));
        ;
        $langs = include 'geshi/langs.all.php';
        $tags = getTagsFromPsqlStr($code['tags']);
        if (count($tags))
            $code['tags'] = array_combine($tags, getTagCountersFromPsqlStr($code['tag_counter']));
        else
            $code['tags'] = array();
        $code['lang'] = $langs[$code['language_id']];
        $this->view->code = $code;
    }

    public function tagsAction()
    {
        $t = ICE_Global::getModel('Tag');
        $memberId = $this->view->user->id;
        $id = ICE_Cache::id('code.tags.member' . $memberId);
        $this->view->tags = ICE_Cache::load($id, function() use ($t, $memberId){
            return $t->getsByMemberAndSearch($memberId, null, 50, 0, 'counter DESC')->toArray();
        });
    }

    public function languagesAction()
    {
        $c = ICE_Global::getModel('Code');
        $memberId = $this->view->user->id;
        $id = ICE_Cache::id('code.languages.member' . $memberId);
        $this->view->languages = ICE_Cache::load($id, function() use ($c, $memberId){
            $langs = include 'geshi/langs.all.php';
            $ret = array();
            foreach ($c->getLanguages(array('member_id' => $memberId))->toArray() as $lang)
                $ret[] = array('id' => $lang['language_id'], 'counter' => $lang['counter'], 'name' => $langs[$lang['language_id']]);
            return $ret;
        });
    }
}


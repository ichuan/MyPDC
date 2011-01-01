<?php
/**
* IndexController
*
* @author yc <iyanchuan@gmail.com>
*/

class IndexController extends Zend_Controller_Action
{

    public function indexAction()
    {
        if ($this->view->user !== NULL){
            # format: stat{$member_id}{$stat_name}
            $id = $this->view->user->id;
            $fileds = array_map(function($i) use ($id) { return "stat{$id}{$i}"; }, array('totalCode', 'totalMemo'));
            $redis = ICE_Global::get('redis');
            $stats = $redis->mget($fileds);
            $test = array_filter($stats, function($i) { return $i !== NULL; });
            if (count($test) !== count($fileds)){ // hit db
                $stats = ICE_Global::getModel('Member')->getStatById($id);
                $redis->mset(array_combine($fileds, $stats));
            }
            $this->view->stats = $stats;
        }
    }

    public function feedbackAction() {}

    public function planAction() {}
    public function markdownAction() {}
    public function aboutAction() {}
}


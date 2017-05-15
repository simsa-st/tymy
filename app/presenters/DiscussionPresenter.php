<?php

namespace App\Presenters;

use Nette\Application\UI\NewPostControl;
use Nette\Utils\Strings;
use Tymy;
use Tracy\Debugger;

/**
 * Description of DiscussionPresenter
 *
 * @author matej
 */
class DiscussionPresenter extends SecuredPresenter {

    public function __construct() {
        parent::__construct();
    }
    
    public function startup() {
        parent::startup();
        $this->setLevelCaptions(["0" => ["caption" => "Diskuze", "link" => $this->link("Discussion:")]]);
    }

    public function renderDefault() {
        $discussions = new \Tymy\Discussions($this);
        $d = [];
        foreach ($discussions->fetch() as $dis) {
            $d[] = (object)[
                "caption" => $dis->caption,
                "captionLink" => Strings::webalize($dis->caption),
                "description" => $dis->description,
            ];
        }
        $this->template->discussions = (object)$d;
    }
    
    public function actionNewPost($discussion, $page){
        $post = $this->getHttpRequest()->getPost("post");
        if (trim($post) != "") {
            $addPost = new \Tymy\Discussion($this, FALSE, $page);
            $addPost->recId($discussion)->insert($post);
        }
        $this->setView('discussion');
    }
    
    public function renderDiscussion($discussion, $page, $search) {
        $discussionId = NULL;
        if(!$discussionId = intval($discussion)){
            $allDiscussions = new \Tymy\Discussions($this);
            foreach ($allDiscussions->fetch() as $dis) {
                if (Strings::webalize($dis->caption) == $discussion) {
                    $discussionId = $dis->id;
                    break;
                }
            }
        }

        if (is_null($discussionId) || $discussionId < 1)
            $this->error("Tato diskuze neexistuje");

        $d = new \Tymy\Discussion($this, true, $page);
        $d->recId($discussionId);
        if($search) 
            $d->search($search);
        $data = $d->fetch();
        $data->discussion->webName = Strings::webalize($data->discussion->caption);

        $this->setLevelCaptions(["1" => ["caption" => $data->discussion->caption, "link" => $this->link("Discussion:discussion", [Strings::webalize($data->discussion->caption)]) ] ]);
        
        $this->template->userId = $this->getUser()->getId();
        $this->template->discussion = $data;
        $this->template->nazevDiskuze = Strings::webalize($data->discussion->caption);
        $this->template->currentPage = is_numeric($page) ? $page : 1 ;
        $currentPage = is_numeric($page) ? $page : 1;
        $lastPage = is_numeric($data->paging->numberOfPages) ? $data->paging->numberOfPages : 1 ;
        $this->template->lastPage = $lastPage;
        $this->template->pagination = $this->pagination($lastPage, 1, $currentPage, 5);
        if($this->isAjax())
            $this->redrawControl ("discussion");
    }
    
    protected function createComponentNewPost($discussion) {
        $newpost = new NewPostControl($discussion);
        $newpost->redrawControl();
        return $newpost;
    }
    
    private function pagination($data, $limit = null, $current = null, $adjacents = null) {
        $result = array();

        if (isset($data, $limit) === true) {
            $result = range(1, ceil($data / $limit));

            if (isset($current, $adjacents) === true) {
                if (($adjacents = floor($adjacents / 2) * 2 + 1) >= 1) {
                    $result = array_slice($result, max(0, min(count($result) - $adjacents, intval($current) - ceil($adjacents / 2))), $adjacents);
                }
            }
        }

        return $result;
    }

}
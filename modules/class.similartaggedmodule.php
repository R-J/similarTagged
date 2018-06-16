<?php

class SimilarTaggedModule extends Gdn_Module {
    /**
     * Init the module.
     *
     * Get similar discussions and sets the view.
     * @param string  $sender            [description]
     * @param boolean $applicationFolder [description]
     */
    public function __construct(
        $sender = '',
        $applicationFolder = 'plugins/similartagged'
    ) {
        parent::__construct();
        $this->setData(
            'SimilarDiscussions',
            $this->getData($this->_Sender->Discussion)
        );
        $this->setView(
            $this->fetchViewLocation('similartagged', $applicationFolder)
        );
    }
    /**
     * Returns the configurable asset target. "Panel" by default.
     *
     * @return string The asset target.
     */
    public function assetTarget() {
        return c('SimilarTagged.AssetTarget',  'Panel');
    }

    /**
     * Get similar discussions based on the discussions tags.
     *
     * The method respects category permissions.
     *
     * @param object $discussion The discussion.
     *
     * @return array A list of discussion objects (name and id) which are similar tagged.
     */
    public function getData($discussion) {
        $perms = DiscussionModel::categoryPermissions();
        if (is_array($perms)) {
            Gdn::sql()->whereIn('d.CategoryID', $perms);
        }
        $cacheKey = "SimilarTaggedModule-Discussion-{$discussion->DiscussionID}";
        return Gdn::sql()
            ->select('td.DiscussionID, d.Name')
            ->select('td.TagID', 'COUNT', 'Similarities')
            ->from('TagDiscussion td')
            ->join('Discussion d', 'td.DiscussionID = d.DiscussionID')
            ->where('td.DiscussionID <>', $discussion->DiscussionID)
            ->whereIn(
                'TagID',
                explode(',', val('Tags', $discussion, []))
            )
            ->groupBy('td.DiscussionID')
            ->orderBy('COUNT(td.TagID)', 'DESC')
            ->orderBy('d.DateInserted', 'DESC')
            ->limit(c('SimilarTagged.Limit', 5), 0)
            ->cache($cacheKey, 'get', [Gdn_Cache::FEATURE_EXPIRY => 120])
            ->get()
            ->resultObject();
    }
}

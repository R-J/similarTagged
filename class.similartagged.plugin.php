<?php
$PluginInfo['similarTagged'] = [
    'Name' => 'SimilarTagged',
    'Description' => 'Provides a list of similar tagged discussions.',
    'Version' => '0.0.1',
    'RequiredApplications' => ['Vanilla' => '>= 2.3'],
    'SettingsPermission' => 'Garden.Settings.Manage',
    'SettingsUrl' => '/dashboard/settings/similartagged',
    'MobileFriendly' => true,
    'HasLocale' => true,
    'Author' => 'Robin Jurinka',
    'AuthorUrl' => 'http://open.vanillaforums.com/profile/r_j',
    'License' => 'MIT'
];

class SimilarTaggedPlugin extends Gdn_Plugin {
    public function setup() {
        // touchConfig('similartagged.ConfigName', 'Value');
        // $this->structure()
    }

    public function structure() {
        /*
        Gdn::structure()
            ->table('SomeTable')
            ->column('AcceptedUserID', 'int', 0)
            ->set();
        */
    }

    public function settingsController_similarTagged_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $sender->addSideMenu('dashboard/settings/plugins');
        $sender->setData('Title', t('SimilarTagged Settings'));

        $configurationModule = new configurationModule($sender);

        $configurationModule->initialize(
            [
                'similarTagged.Text' => [
                    'Default' => 'Default',
                    'Options' => ['class' => 'InputBox BigInput']
                ],
                'similarTagged.DropDown' => [
                    'Control' => 'DropDown',
                    'Items' => [
                        'i1' => 'One',
                        'i2' => 'Two',
                        'i3' => 'Three'
                    ],
                    'LabelCode' => 'DropDown Element',
                    'Options' => ['IncludeNull' => true]
                ],
                'similarTagged.Category' => [
                    'Control' => 'CategoryDropDown',
                    'LabelCode' => 'Category Drop Down',
                    'Description' => 'Bla bla',
                    'Options' => ['IncludeNull' => true]
                ],

                'similarTagged.Toggle' => [
                    'Control' => 'CheckBox',
                    'Description' => 'Toggle values',
                    'Default' => true
                ],
                'similarTagged.Radio' => [
                    'Control' => 'RadioList',
                    'Items' => [
                        'Item1' => 'One',
                        'Item2' => 'Two'
                    ],
                    'Default' => 'Item1'
                ],
                'similarTagged.Picture' => [
                    'Control' => 'ImageUpload',
                    'LabelCode' => 'Please upload a picture'
                ]
            ]
        );
        $configurationModule->renderAll();
    }

    public function discussionController_beforeDiscussionRender_handler($sender, $args) {
        // If this discussion has no tags, there should be no similar
        // discussions shown.
        if (val('Tags', $sender->Discussion, false) === false) {
            return;
        }

        $discussionIDs = $this->getSimilar($sender->Discussion->DiscussionID);

        // $perms = DiscussionModel::categoryPermissions();
        // 
        // $discussions = $sender->DiscussionModel->getIn($discussionIDs)->resultObject();
        $discussions = $sender->DiscussionModel->get(
            c('similarTagged.Limit', 50),
            '',
            ['DiscussionID in' => $discussionIDs]
        )->resultObject();
        decho($discussions, 'discussions', true);
    }

    protected function getSimilar($discussionID) {
        // Get from cache if available.
        $cacheKey = 'SimilarTagged_'.$discussionID;
        $discussionIDs = Gdn::cache()->get($cacheKey);
        if ($discussionIDs !== Gdn_Cache::CACHEOP_FAILURE) {
            return $discussionIDs;
        }

        $px = Gdn::database()->DatabasePrefix;

        $sql = <<< EOS
SELECT SharedTags.DiscussionID
FROM (
    SELECT count(TagID) AS "CountShared", DiscussionID
    FROM {$px}TagDiscussion
    WHERE TagID IN (
      SELECT TagID
      FROM {$px}TagDiscussion
      WHERE DiscussionID = :DiscussionID1
    )
    GROUP BY DiscussionID
) AS SharedTags
LEFT JOIN (
    SELECT count(TagID) AS "CountAll", DiscussionID
    FROM {$px}TagDiscussion
    WHERE DiscussionID IN (
        SELECT DiscussionID
        FROM {$px}TagDiscussion
        WHERE TagID IN (
          SELECT TagID
          FROM {$px}TagDiscussion
          WHERE DiscussionID = :DiscussionID2
        )
    )
    GROUP BY DiscussionID
) AS AllTags ON SharedTags.DiscussionID = AllTags.DiscussionID
ORDER BY CountShared DESC, CountAll - CountShared ASC
EOS;
        $result = Gdn::database()->query(
            $sql,
            [':DiscussionID1' => $discussionID, ':DiscussionID2' => $discussionID]
        )->resultArray();

        // Remove the current discussion from the result set.
        $discussionIDs = array_diff(
            array_column($result, 'DiscussionID'),
            [$discussionID]
        );

        // Store for faster retrieval.
        Gdn::cache()->store(
            $cacheKey,
            $discussionIDs,
//TODO: change 18 to 180!!!
            [Gdn_Cache::FEATURE_EXPIRY => 18] // 3 minutes
        );

        return $discussionIDs;
    }
}

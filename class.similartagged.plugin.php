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
        if (val('Tags', $sender->Discussion, false) === false) {
            return;
        }
        $this->getSimilar($sender->Discussion->DiscussionID);
    }

    protected function getSimilar($discussionID) {
        // There _must_ be some caching done!
        //    "SimilarTagged" + DiscussionID = array of DiscussionIDs

        $perms = DiscussionModel::categoryPermissions();
        $cacheKey = 'SimilarTagged_'.$discussionID.'_'.implode('_', $perms);
decho($cacheKey, 'key', true);


        // Get all discussions that have been tagged with the same tags.
        $px = Gdn::database()->DatabasePrefix;
        $sql = 'SELECT td1.DiscussionID, td1.TagID, td1.CategoryID ';
        $sql .= "FROM `{$px}TagDiscussion` td1 ";
        $sql .= 'WHERE td1.DiscussionID IN ( ';
        $sql .= '  SELECT td2.DiscussionID ';
        $sql .= "  FROM `{$px}TagDiscussion` td2 ";
        $sql .= '  WHERE td2.TagID IN ( ';
        $sql .= '    SELECT td3.TagID ';
        $sql .= "    FROM `{$px}TagDiscussion` td3 ";
        $sql .= '    WHERE td3.DiscussionID = :DiscussionID ';
        $sql .= '  ) ';
        $sql .= ') ';
        // Add Category permission check.
        if ($perms !== true) {
            $sql .= "AND td1.CategoryID IN ('";
            $sql .= implode("','", $perms);
            $sql .= "') ";
        }
        $result = Gdn::database()->query(
            $sql,
            [':DiscussionID' => $discussionID]
        )->resultObject();

        // Group results by discussions.
        $discussions = [];
        foreach ($result as $item) {
            $TagIDs = val('TagIDs', $discussions[$item->DiscussionID], []);
            $TagIDs[] = $item->TagID;
            $discussions[$item->DiscussionID] = [
                'CategoryID' => $item->CategoryID,
                'TagIDs' => $TagIDs
            ];
        }
decho($discussions);

        // Loop through discussions to find the most similar discussions.
        $discussion = $discussions[$discussionID];
        unset($discussions[$discussionID]);
        $matchingDiscussions = [];
        $countCurrent = count($discussion['TagIDs']);
        foreach ($discussions as $id => $item) {
            // Number of tags in both discussions.
            $countBoth = count(array_intersect($discussion['TagIDs'], $item['TagIDs']));
            if ($countBoth == 0) {
                // If there are no matching elements, don't proceed.
                continue;
            }
            // Save DiscussionID
            $matchingDiscussions[] = [
                'DiscussionID' => $id,
                'MatchingTags' => $countBoth,
                'NonMatchingTags' => $countCurrent + count($item['TagIDs']) - 2 * $countBoth
            ];
        }
decho($matchingDiscussions, 'unsorted');

        // Sort result.
        usort($matchingDiscussions, function($a, $b) {
            if ($a['MatchingTags'] > $b['MatchingTags']) {
                return -1;
            } elseif ($a['MatchingTags'] < $b['MatchingTags']) {
                return 1;
            } else {
                if ($a['NonMatchingTags'] < $b['NonMatchingTags']) {
                    return -1;
                } elseif ($a['NonMatchingTags'] > $b['NonMatchingTags']) {
                    return 1;
                } else {
                    return 0;
                }
            }
        });
decho($matchingDiscussions, 'sorted');
    }
}

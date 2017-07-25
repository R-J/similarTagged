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

    public function pluginController_similarTagged_create($sender, $args) {
        // 1. This should only be done if the requested discussion has >= 2 tags
        // 2. There _must_ be some caching done!
        //    "SimilarTagged" + DiscussionID = array of DiscussionIDs

        // Get ID from slug.
        $discussionID = intval(val(0, $args, 154));
        decho($discussionID, 'DiscussionID: ');

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
        $result = Gdn::database()->query(
            $sql,
            [':DiscussionID' => $discussionID]
        )->resultObject();
        decho($result, 'Result from db:');

        // Group results by discussions.
        $discussions = [];
        foreach ($result as $item) {
            decho($item);
            $discussions[$item->DiscussionID][] = $item->TagID;
        }

        // Loop through discussions to find 
        $currentDiscussion = $discussions[$discussionID];
        unset($discussions[$discussionID]);
        foreach ($discussions as $discussion) {
            decho(array)
        }
        decho($discussions);
    }
}

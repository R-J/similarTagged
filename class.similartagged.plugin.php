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
                'similarTagged.Limit' => [
                    'Default' => '5',
                    'LabelCode' => 'Number of similar tagged discussions to show',
                    // 'Description' => 'Bla bla',
                    'Options' => ['class' => 'InputBox']
                ],
                'similarTagged.AssetTarget' => [
                    'Default' => 'Panel',
                    'LabelCode' => 'Where the plugin should be rendered.',
                    'Description' => 'If your theme doesn\'t provide other options, "Panel" would most probably be correct',
                    'Options' => ['class' => 'InputBox BigInput']
                ],


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

        $similarTagsModule = new SimilarTagsModule($sender);
        $sender->addModule($similarTagsModule);


        // $discussions = $this->getSimilarDiscussions($sender->Discussion->DiscussionID);
        // $discussions = $this->getSimilar($sender->Discussion->DiscussionID);
    }
}

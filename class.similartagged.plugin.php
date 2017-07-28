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
    /**
     * Init config with sane values.
     *
     * @return void.
     */
    public function setup() {
        touchConfig(
            [
                'similarTagged.AssetTarget' => 'Panel',
                'similarTagged.Limit' => 5
            ]
        );
    }

    /**
     * Create simple settings page.
     *
     * @param SettingsController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function settingsController_similarTagged_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        $sender->addSideMenu('dashboard/settings/plugins');
        $sender->setData('Title', t('Similar Tagged Settings'));

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

    /**
     * Attach module to discussions.
     *
     * @param DiscussionController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function discussionController_beforeDiscussionRender_handler($sender) {
        // If this discussion has no tags, there could be no similar
        // discussions shown.
        if (val('Tags', $sender->Discussion, false) === false) {
            return;
        }

        // require(__DIR__.'/modules/class.similartaggedmodule.php');
        $similarTaggedModule = new SimilarTaggedModule($sender);
        $similarTaggedModule->setView($sender->fetchViewLocation('similartagged', '', 'plugins/similarTagged'));
        $similarTaggedModule->setData(
            'Discussions',
            $similarTaggedModule->getData($sender->Discussion->DiscussionID)
        );

        $sender->addModule($similarTaggedModule);
    }
}

<?php defined('APPLICATION') or die;

class SimilarTaggedModule extends Gdn_Module {

    public function __construct($sender = '', $applicationFolder = false) {
        if ($sender !== '' && $sender->ClassName = 'DiscussionController') {
            $this->setData(
                'Discussions',
                $this->getData($sender->Discussion->DiscussionID)
            );
        }

        parent::__construct($sender, $applicationFolder);
    }

    public function assetTarget() {
        return c('similarTagged.AssetTarget',  'Panel');
    }

    public function getData($discussionID) {
        // Construct permission check if needed.
        $permissionCheck = '';
        $perms = DiscussionModel::categoryPermissions();
        if ($perms !== true) {
            $permissionCheck = 'AND d.CategoryID IN('.implode(',', $perms).')';
        }

        $px = Gdn::database()->DatabasePrefix;

        $sql = <<< EOS
SELECT SharedTags.DiscussionID, CountShared, CountAll, CountAll - CountShared, d.CategoryID, d.Name
FROM (
    SELECT count(TagID) AS "CountShared", DiscussionID
    FROM {$px}TagDiscussion
    WHERE TagID IN (
      SELECT TagID
      FROM {$px}TagDiscussion
      WHERE DiscussionID = :DiscussionID1
    )
    GROUP BY DiscussionID
) AS SharedTags LEFT JOIN (
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
LEFT JOIN {$px}Discussion d ON SharedTags.DiscussionID = d.DiscussionID
WHERE d.DiscussionID <> :DiscussionID3
{$permissionCheck}
ORDER BY CountShared DESC, CountAll - CountShared ASC
LIMIT :Limit
EOS;

        return Gdn::database()->query(
            $sql,
            [
                ':DiscussionID1' => $discussionID,
                ':DiscussionID2' => $discussionID,
                ':DiscussionID3' => $discussionID,
                ':Limit' => c('similarTagged.Limit', 5)
            ]
        )->resultArray();
    }

    public function toString() {
        ob_start();
        ?>
<div class="Box BoxSimilarTagged">
    <h4><?= t('Similar Tagged') ?></h4>
    <ul class="PanelInfo">
    <?php foreach ($this->data as $discussion): ?>
        <li>
            <?= discussionUrl($discussion) ?>
        </li>
    <?php endforeach ?>
    </ul>
</div>
        <?php
        return ob_get_clean();
    }
}

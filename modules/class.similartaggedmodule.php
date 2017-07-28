<?php defined('APPLICATION') or die;

class SimilarTaggedModule extends Gdn_Module {
    /**
     * Returns the configurable asset target. "Panel" by default.
     *
     * @return string The asset target.
     */
    public function assetTarget() {
        return c('similarTagged.AssetTarget',  'Panel');
    }

    /**
     * Get similar discussions based on the discussions tags.
     *
     * The result is ordered by
     * 1. criterion: number of matching tags (descending) and
     * 2. criterion: number of non matching tags (ascending)
     *
     * The method respects category permissions.
     *
     * @param int $discussionID The discussion id.
     *
     * @return array A list of discussion names and ids which are similar tagged.
     */
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
}

<?php defined('APPLICATION') or die; ?>
<div class="Box BoxSimilarTagged">
    <?= panelHeading(t('Similar Tagged Discussions')) ?>
    <ul class="PanelInfo">
    <?php foreach ($this->data('SimilarDiscussions') as $discussion): ?>
        <li>
            <?php
            echo anchor(
                Gdn_Format::Text($discussion->Name),
                discussionUrl($discussion)
            );
            ?>
        </li>
    <?php endforeach ?>
    </ul>
</div>

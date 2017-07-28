<?php defined('APPLICATION') or die; ?>
<div class="Box BoxSimilarTagged">
    <h4><?= t('Similar Tagged') ?></h4>
    <ul class="PanelInfo">
    <?php foreach ($this->data('Discussions') as $discussion): ?>
        <li>
            <?php
            echo anchor(
                Gdn_Format::Text($discussion['Name']),
                discussionUrl($discussion)
            );
            ?>
        </li>
    <?php endforeach ?>
    </ul>
</div>

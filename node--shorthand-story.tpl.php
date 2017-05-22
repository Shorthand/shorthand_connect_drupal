<?php

/**
 * @file
 * Theme implementation to display a shorthand story node.
 */
?>

<div>
    <div class="content clearfix"<?php print $content_attributes; ?>>

      <?php if(!empty($story_id)): ?>
        <?php print $story_body; ?>
        <?php print $story_extra_html; ?>
      <?php endif; ?>

    </div>
</div>

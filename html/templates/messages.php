<?php defined('ABSPATH') || die; ?>
<?php foreach( $this->list_messages()  as $message ) :  ?>
<div class="notice is-dismissible <?php print $message['type'] ?? 'info' ?>">
    <?php print $message['content'] ?? '' ?>
</div>
<?php endforeach; ?>

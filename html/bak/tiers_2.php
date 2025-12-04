<?php defined('ABSPATH') or exit; ?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>
    <?php $this->show_messages() ?>
        <div class="coder-tiers">
            <ul class="tier-list">
                <?php foreach ($this->list_tiers as $tier) : ?>
                    <li class="item">
                        <label class="tier button-primary" data-tier="<?php
                            print $tier['tier'] ?>"><?php
                            print $tier['tier'] ?>
                        </label>
                        <?php foreach ($tier['roles'] as $role): ?>
                            <span class="role button" data-role="<?php
                                print $role ?>"><?php
                                print $role ?></span>
                        <?php endforeach; ?>
                        <a href="<?php
                            print $this->action_remove($tier['tier']);
                            ?>" target="_self" class="remove right">
                            <span class="dashicons dashicons-remove"></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
            </ul>
            <div class="tierform">
                <form method="post" name="newtier" action="<?php echo $this->get_formurl ?>">
                        <?php wp_nonce_field('coder_tiers_manage'); ?>
                        <input type="hidden" name="action" value="coder_tiers_save">
                        <input type="hidden" name="ct_action" value="save_all">
                        <input type="text" name="tier" value="" placeholder="<?php
                            print __('Name your new tiers here!','coder_tiers');
                        ?>" class=""/>
                        <button class="button-primary right" type="submit" name="action" value="create">
                            <?php print __('Add','coder_tiers') ?>
                        </button>
                </form>
            </div>
        </div>            
        <h3><span class="dashicons dashicons-info-outline"></span>Developer notes</h3>
    <p>Drag any tier over another tier's container to register in the role list.</p>
    <p>You may add more than one tier to the form input, separated by spaces.</p>
    <p>Use <code>apply_filter('coder_tiers', [])</code> to fetch all tiers listed here from other plugins.</p>
    <p>Use <code>apply_filter('coder_acl', false , $tier )</code> to validate any role present against <code>$tier</code> access.</p>
    <p>Use <code>apply_filter('coder_role', $role )</code> to register the active role in the session.</p>
</div>

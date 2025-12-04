<?php defined('ABSPATH') or exit; ?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php print get_admin_page_title() ?></h1>

    <?php if (isset($_GET['updated'])) : ?>
        <div class="updated notice is-dismissible"><p>Saved!</p></div>
    <?php endif; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('coder_tiers_manage'); ?>
        <input type="hidden" name="action" value="coder_tiers_save">
        <input type="hidden" name="ct_action" value="save_all">
        <ul class="tiers">
        </ul>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:200px"><?php print __('Tier','coder_tiers') ?></th>
                    <th><?php print __('Roles','coder_tiers') ?></th>
                    <th><?php print __('Actions','coder_tiers') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($this->has_tiers) : ?>
                    <?php foreach ($this->list_tiers as $tier) : ?>
                        <tr>
                            <td>
                                <input type="text" name="title" value="<?php
                                    print $tier['title']; ?>"
                                    placeholder="<?php print $tier['tier'] ?>" />
                            </td>
                            <td class="roles">
                                <?php if(count($tier['roles'])): ?>
                                <span><?php print implode('</span><span>', $tier['roles']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="right">
                                    <option value="">— none —</option>
                                    <?php foreach ($this->list_available($tier['tier']) as $role ) : ?>
                                        <option value="<?php
                                            print $role ?>" ><?php
                                            print $role; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No tiers yet</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>
                        <?php wp_nonce_field('coder_tiers_manage'); ?>
                        <input type="hidden" name="action" value="coder_tiers_save">
                        <input type="hidden" name="ct_action" value="add">
                        <input name="name" id="ct_name" type="text" required />
                    </td>
                    <td></td>
                    <td>
                        <button class="button button-primary right" type="submit" name="create"><?php
                            print __('Create Tier','coder_tiers');
                        ?></button>
                        <button class="button button-primary right" type="submit" name="save"><?php
                            print __('Save','coder_tiers');
                        ?></button>
                    </td>
                </tr>
            </tfoot>
        </table>
        <p class="submit">
        </p>
    </form>
    <h3>Developer notes</h3>
    <p>Use the global functions <code>coder_tiers()</code> and <code>coder_tier( $tier )</code> to integrate with this service. You can also set loaded tiers for the request via <code>CoderTiers::instance()-&gt;set_loaded_tiers( array )</code>.</p>
</div>

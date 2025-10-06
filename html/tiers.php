<?php defined('ABSPATH') or exit; ?>

<div class="wrap">
    <h1>Coder Tiers</h1>
    <p>Manage hierarchical tiers. A tier has a machine name (unique) and a human title. Set <em>parent</em> to attach a sub-tier relationship.</p>

    <?php if (isset($_GET['updated'])) : ?>
        <div class="updated notice is-dismissible"><p>Saved.</p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('coder_tiers_manage'); ?>
        <input type="hidden" name="action" value="coder_tiers_save">
        <input type="hidden" name="ct_action" value="save_all">

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Tier name (machine)</th>
                    <th>Title</th>
                    <th>Parent</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows) : foreach ($rows as $r) : ?>
                        <tr>
                            <td><?php echo intval($r['id']); ?></td>
                            <td><input type="text" name="rows[<?php echo intval($r['id']); ?>][name]" value="<?php echo esc_attr($r['name']); ?>" /></td>
                            <td><input type="text" name="rows[<?php echo intval($r['id']); ?>][title]" value="<?php echo esc_attr($r['title']); ?>" /></td>
                            <td>
                                <select name="rows[<?php echo intval($r['id']); ?>][parent_id]">
                                    <option value="">— none —</option>
                                    <?php foreach ($rows as $p) : if (intval($p['id']) === intval($r['id'])) continue; ?>
                                        <option value="<?php echo intval($p['id']); ?>" <?php selected($p['id'], $r['parent_id']); ?>><?php echo esc_html($p['name'] . ' — ' . $p['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="4">No tiers yet.</td></tr>
<?php endif; ?>
            </tbody>
        </table>

        <p class="submit"><button class="button button-primary" type="submit">Save changes</button></p>
    </form>

    <h2>Add new tier</h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<?php wp_nonce_field('coder_tiers_manage'); ?>
        <input type="hidden" name="action" value="coder_tiers_save">
        <input type="hidden" name="ct_action" value="add">

        <table class="form-table">
            <tr>
                <th scope="row"><label for="ct_name">Name</label></th>
                <td><input name="name" id="ct_name" type="text" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ct_title">Title</label></th>
                <td><input name="title" id="ct_title" type="text" required /></td>
            </tr>
            <tr>
                <th scope="row"><label for="ct_parent">Parent</label></th>
                <td>
                    <select name="parent_id" id="ct_parent">
                        <option value="">— none —</option>
                        <?php foreach ($rows as $p) : ?>
                            <option value="<?php echo intval($p['id']); ?>"><?php echo esc_html($p['name'] . ' — ' . $p['title']); ?></option>
<?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit"><button class="button button-primary" type="submit">Add tier</button></p>
    </form>

    <h3>Developer notes</h3>
    <p>Use the global functions <code>coder_tiers()</code> and <code>coder_tier( $tier )</code> to integrate with this service. You can also set loaded tiers for the request via <code>CoderTiers::instance()-&gt;set_loaded_tiers( array )</code>.</p>
</div>

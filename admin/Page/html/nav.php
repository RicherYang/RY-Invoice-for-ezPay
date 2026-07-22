<?php defined('ABSPATH') or exit; ?>

<?php $navs = apply_filters('ry-invoice-navs', []); ?>

<nav class="nav-tab-wrapper wp-clearfix">
    <?php foreach ($navs as $nav) {
        printf(
            '<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
            esc_url(add_query_arg('page', $nav['slug'], admin_url('admin.php'))),
            $show_type === $nav['slug'] ? 'nav-tab-active' : '',
            esc_html($nav['name'])
        );
    } ?>
</nav>

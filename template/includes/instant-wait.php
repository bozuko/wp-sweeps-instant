<?
global $facebook_user, $last_entry, $config;

$instant_win = Snap::inst('SweepsInstant');
?>
<div class="grey-box clearfix">
<?
echo apply_filters('the_content', $config->field('wait_html')->getValue());
?>
</div>


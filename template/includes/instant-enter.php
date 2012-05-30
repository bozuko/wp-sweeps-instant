<?
global $facebook_user, $last_entry, $config;
?>
<div class="grey-box clearfix">
<?
$instant_win = Snap::inst('SweepsInstant');
echo apply_filters('the_content', $config->field('enter_html')->getValue());
?>

<form action="<?= add_query_arg('sweep_reenter', '1') ?>" method="POST" class="reenter-form" onsubmit="return false;">
    <p style="text-align:center;">
        <input type="submit" class="nice large radius button blue play-again" value="Enter" />
    </p>
</form>

</div>
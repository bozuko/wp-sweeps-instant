<?
global $sweeps_campaign;

$title = $sweeps_campaign->getValue('facebook_share_win_title');
$caption = $sweeps_campaign->getValue('facebook_share_win_caption');
$description = $sweeps_campaign->getValue('facebook_share_win_description');
$image = $sweeps_campaign->getValue('facebook_share_win_image');

$attrs = array();
if( $title ){
    $attrs[] = 'data-title="'.esc_attr( $title ).'"';
}
if( $caption ){
    $attrs[] = 'data-caption="'.esc_attr( $caption).'"';
}
if( $description ){
    $attrs[] = 'data-description="'.esc_attr( $description ).'"';
}
if( $image ){
    $src = wp_get_attachment_image_src( $image, 'og' );
    $attrs[] = 'data-image="'.$src[0].'"';
}

?>
<div class="grey-box clearfix iw-result iw-result-start">
<? echo apply_filters('the_content', $sweeps_campaign->getValue('iw_win_html') ) ?>
<? /* Add a share button */ ?>
<div class="share-buttons">
<a class="nice large blue radius button facebook-share" <?= implode(' ', $attrs) ?>>Share with Friends</a>
</div>
</div>

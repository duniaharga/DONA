<?php
/** Optional, per-product 360 video and detail media. Originals stay in Media Library. */
defined('ABSPATH') || exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) return;
    $feature_fields=[['key'=>'field_dona_hotspots_tab','label'=>'Clickable + features','type'=>'tab']];
    for($i=1;$i<=3;$i++) {
        $name='dona_hotspot_'.$i;
        $feature_fields[]=['key'=>'field_'.$name,'name'=>$name,'label'=>'Feature point '.$i,'type'=>'group','layout'=>'block','sub_fields'=>[
            ['key'=>'field_'.$name.'_title','name'=>'title','label'=>'Feature title','type'=>'text','instructions'=>'Leave empty to hide this point. Use only verified product features.'],
            ['key'=>'field_'.$name.'_description','name'=>'description','label'=>'Feature description','type'=>'textarea','rows'=>3],
            ['key'=>'field_'.$name.'_x','name'=>'x','label'=>'Horizontal position (%)','type'=>'number','min'=>0,'max'=>100,'default_value'=>[1=>35,2=>65,3=>50][$i],'instructions'=>'0 = left edge of the bag image; 100 = right edge.'],
            ['key'=>'field_'.$name.'_y','name'=>'y','label'=>'Vertical position (%)','type'=>'number','min'=>0,'max'=>100,'default_value'=>[1=>30,2=>50,3=>70][$i],'instructions'=>'0 = top of the bag image; 100 = bottom.'],
            ['key'=>'field_'.$name.'_image','name'=>'image','label'=>'Close-up image (optional)','type'=>'image','return_format'=>'id','preview_size'=>'thumbnail','library'=>'all'],
        ]];
    }
    acf_add_local_field_group([
        'key'=>'group_dona_product_experience', 'title'=>'DONA — 360° & Explore the details',
        'fields'=>array_merge([
            ['key'=>'field_dona_360_tab','label'=>'360° view','type'=>'tab'],
            ['key'=>'field_dona_360_video','name'=>'dona_360_video','label'=>'360° rotation video (optional)','type'=>'file','return_format'=>'id','library'=>'all','mime_types'=>'mp4,webm','instructions'=>'Optional: one complete rotation of the bag alone, without a model. The interactive photo and + feature points work without a video.'],
            ['key'=>'field_dona_spin_sheet','name'=>'dona_spin_sheet','label'=>'360° image sheet (4 columns × 2 rows)','type'=>'image','return_format'=>'id','instructions'=>'Eight ordered views, from front clockwise around the bag. Use evenly sized cells.'],
            ['key'=>'field_dona_spin_columns','name'=>'dona_spin_columns','label'=>'Rotation sheet column edges (optional)','type'=>'text','instructions'=>'For uneven sheets: five source pixel positions, including 0 and the full image width. Leave empty for four equal columns.'],
            ['key'=>'field_dona_explorer_demo','name'=>'dona_explorer_demo','label'=>'Single-image demo','type'=>'true_false','ui'=>1,'instructions'=>'Show an explicit demo notice. A single photograph does not provide true 360° rotation.'],
            ['key'=>'field_dona_360_poster','name'=>'dona_360_poster','label'=>'Interactive bag image','type'=>'image','return_format'=>'id','preview_size'=>'medium','library'=>'all','instructions'=>'Choose a bag-only product image without a model. Falls back to the featured product image. Position the + feature points on this image.'],
        ],$feature_fields,[
            ['key'=>'field_dona_explore_tab','label'=>'Explore the details','type'=>'tab'],
            ['key'=>'field_dona_explore_heading','name'=>'dona_explore_heading','label'=>'Section heading','type'=>'text','default_value'=>'Explore the details'],
            ['key'=>'field_dona_explore_intro','name'=>'dona_explore_intro','label'=>'Short introduction (optional)','type'=>'textarea','rows'=>3,'new_lines'=>'','instructions'=>'Describe only verified features or real production steps shown in the media.'],
            ['key'=>'field_dona_explore_gallery','name'=>'dona_explore_gallery','label'=>'Detail images and videos','type'=>'dona_mixed_media','instructions'=>'Choose close-up images, videos, or both; drag to reorder. These are separate from the existing product/color galleries. Edit each attachment caption in Media Library to add a visible explanation. The section is hidden when empty.'],
        ]),
        'location'=>[[['param'=>'post_type','operator'=>'==','value'=>'product']]],
        'position'=>'normal','style'=>'default','active'=>true,
    ]);
});

function dona_360_video_id($product_id) {
    $id=absint(get_post_meta($product_id,'dona_360_video',true));
    return in_array(get_post_mime_type($id),['video/mp4','video/webm'],true) ? $id : 0;
}
function dona_explore_media_ids($product_id) {
    return dona_mixed_gallery_ids($product_id,'dona_explore_');
}
function dona_bag_explorer_image_id($product_id) {
    $id=absint(get_post_meta($product_id,'dona_360_poster',true));
    return wp_attachment_is_image($id) ? $id : absint(get_post_thumbnail_id($product_id));
}
function dona_bag_feature_points($product_id) {
    $points=[];
    for($i=1;$i<=3;$i++) {
        $prefix='dona_hotspot_'.$i.'_';$title=trim((string)get_post_meta($product_id,$prefix.'title',true));
        if($title==='')continue;
        $x=get_post_meta($product_id,$prefix.'x',true);$y=get_post_meta($product_id,$prefix.'y',true);
        $points[]=['index'=>$i,'title'=>$title,'description'=>(string)get_post_meta($product_id,$prefix.'description',true),'x'=>max(0,min(100,is_numeric($x)?(float)$x:50)),'y'=>max(0,min(100,is_numeric($y)?(float)$y:50)),'image'=>absint(get_post_meta($product_id,$prefix.'image',true))];
    }
    return $points;
}
function dona_360_button() {
    if (!is_product())return '';
    $id=get_queried_object_id();if(!dona_bag_explorer_image_id($id))return '';
    $label=(dona_360_video_id($id)||get_post_meta($id,'dona_spin_sheet',true)||get_post_meta($id,'dona_explorer_demo',true))?'View in 360°':'Explore the bag';
    return '<p class="dona-360-action"><button type="button" class="uk-button uk-button-text dona-view-360" uk-toggle="target: #dona-360-modal" aria-haspopup="dialog">'.esc_html($label).'</button></p>';
}
add_shortcode('dona_view_360','dona_360_button');


add_action('wp_enqueue_scripts',function () {
    if (!is_product()) return;
    $id=get_queried_object_id();
    if (!dona_bag_explorer_image_id($id) && !dona_explore_media_ids($id)) return;
    $path=get_stylesheet_directory();$uri=get_stylesheet_directory_uri();
    wp_enqueue_style('dona-product-experience',$uri.'/css/product-experience.css',['dona-header'],filemtime($path.'/css/product-experience.css'));
    if(dona_bag_explorer_image_id($id))wp_enqueue_script('dona-product-experience',$uri.'/js/product-experience.js',[],filemtime($path.'/js/product-experience.js'),true);
});
// The editable native Builder section must not leave padding when a bag has no media.
add_action('wp_head',function () {
    if(is_product() && !dona_explore_media_ids(get_queried_object_id()))echo '<style>.dona-product-explore-section{display:none}</style>';
},30);

add_shortcode('dona_explore_details',function () {
    if(!is_product())return '';
    $id=get_queried_object_id();$media=dona_explore_media_ids($id);if(!$media)return '';
    $heading=get_post_meta($id,'dona_explore_heading',true)?:'Explore the details';
    $intro=get_post_meta($id,'dona_explore_intro',true);
    ob_start(); ?>
    <div class="dona-explore-details" aria-labelledby="dona-explore-title">
      <h2 id="dona-explore-title"><?php echo esc_html($heading); ?></h2>
      <?php if($intro): ?><div class="dona-explore-intro"><?php echo wpautop(esc_html($intro)); ?></div><?php endif; ?>
      <div class="dona-explore-grid">
        <?php foreach($media as $attachment): $url=wp_get_attachment_url($attachment); if(!$url)continue; $caption=wp_get_attachment_caption($attachment); ?>
        <figure>
          <?php if(wp_attachment_is_image($attachment)):
            echo wp_get_attachment_image($attachment,'large',false,['loading'=>'lazy','sizes'=>'(max-width: 639px) 100vw, 50vw']);
          else: ?>
            <video controls playsinline preload="none" aria-label="<?php echo esc_attr(get_the_title($attachment)?:'Product detail video'); ?>"><source src="<?php echo esc_url($url); ?>" type="<?php echo esc_attr(get_post_mime_type($attachment)); ?>">Your browser cannot play this video. <a href="<?php echo esc_url($url); ?>">Open product detail video</a></video>
          <?php endif; ?>
          <?php if($caption): ?><figcaption><?php echo wp_kses_post($caption); ?></figcaption><?php endif; ?>
        </figure>
        <?php endforeach; ?>
      </div>
    </div>
    <?php return ob_get_clean();
});

add_action('wp_footer',function () {
    if(!is_product())return;
    $id=get_queried_object_id();$poster=dona_bag_explorer_image_id($id);if(!$poster)return;
    $sheet=absint(get_post_meta($id,'dona_spin_sheet',true));
    $video=dona_360_video_id($id);$points=dona_bag_feature_points($id);if($sheet)$points=array_slice($points,0,2);
    $poster_url=wp_get_attachment_image_url($poster,'large');
    $image_meta=wp_get_attachment_metadata($poster);
    ?>
    <div id="dona-360-modal" class="uk-flex-top" uk-modal aria-labelledby="dona-360-title">
      <div class="uk-modal-dialog uk-modal-body uk-margin-auto-vertical">
        <button class="uk-modal-close-default" type="button" uk-close aria-label="Close bag explorer"></button>
        <h2 id="dona-360-title"><?php echo esc_html(get_the_title($id)); ?></h2>
        <?php if(get_post_meta($id,'dona_explorer_demo',true) && !$video && !$sheet): ?><p class="dona-bag-demo-note">Interactive demo using one photograph. Full 360° rotation requires images from multiple angles or a rotation video.</p><?php endif; ?>
        <p id="dona-360-help"><?php echo $points ? 'Select a + point to explore this bag’s details.' : 'Explore the bag. Available sizes appear below.'; ?></p>
        <?php if($video): ?><div class="dona-bag-modes" role="group" aria-label="Product view"><button type="button" data-bag-mode="photo" aria-pressed="true">Bag details</button><button type="button" data-bag-mode="rotation" aria-pressed="false">360° rotation</button></div><?php endif; ?>
        <?php if($sheet): ?>
        <div class="dona-spin-viewer" data-sheet="<?php echo esc_url(wp_get_attachment_url($sheet)); ?>" data-sheet-height="<?php echo absint((wp_get_attachment_metadata($sheet)['height']??0)); ?>" data-columns="<?php echo esc_attr(get_post_meta($id,'dona_spin_columns',true)); ?>">
          <p>Move your mouse to rotate, or drag and swipe. Select a + to explore a detail.</p>
          <div class="dona-spin-stage"><div class="dona-spin-image" role="img" aria-label="<?php echo esc_attr(get_the_title($id).' — front view'); ?>"></div></div>
          <div class="dona-spin-controls"><button type="button" data-spin-step="-1" aria-label="Rotate bag left">←</button><label>Rotate bag<input type="range" min="0" max="7" step="1" value="0" aria-valuetext="0 degrees"></label><button type="button" data-spin-step="1" aria-label="Rotate bag right">→</button><output>0°</output></div>
          <fieldset class="dona-spin-sizes"><legend>Preview size</legend><button type="button" data-spin-scale="0.72" aria-pressed="false">M</button><button type="button" data-spin-scale="0.86" aria-pressed="true">L</button><button type="button" data-spin-scale="1" aria-pressed="false">XL</button></fieldset>
          <p class="dona-spin-size-note">Illustrative size comparison only; M, L and XL do not change the purchase size.</p>
        </div>
        <?php endif; ?>
        <div class="dona-bag-photo-stage" <?php if($sheet)echo 'hidden'; ?>>
          <img data-dona-bag-src="<?php echo esc_url($poster_url); ?>" width="<?php echo absint($image_meta['width']??1000); ?>" height="<?php echo absint($image_meta['height']??1000); ?>" alt="<?php echo esc_attr(get_the_title($id)); ?>">
          <?php foreach($points as $point): ?><button type="button" class="dona-bag-hotspot" data-x="<?php echo esc_attr($point['x']); ?>" data-y="<?php echo esc_attr($point['y']); ?>" aria-label="<?php echo esc_attr('Show details: '.$point['title']); ?>" aria-expanded="false" aria-controls="dona-bag-feature-<?php echo $point['index']; ?>"><span aria-hidden="true">+</span></button><?php endforeach; ?>
        </div>
        <?php foreach($points as $point): ?>
          <section id="dona-bag-feature-<?php echo $point['index']; ?>" class="dona-bag-feature-panel" tabindex="-1" hidden>
            <button type="button" class="dona-bag-feature-close" aria-label="Close feature details">×</button>
            <h3><?php echo esc_html($point['title']); ?></h3>
            <?php if($point['description']):echo wpautop(esc_html($point['description']));endif; ?>
            <?php if(wp_attachment_is_image($point['image'])): ?><img data-dona-detail-src="<?php echo esc_url(wp_get_attachment_image_url($point['image'],'large')); ?>" alt="<?php echo esc_attr(get_post_meta($point['image'],'_wp_attachment_image_alt',true)?:$point['title']); ?>"><?php endif; ?>
          </section>
        <?php endforeach; ?>
        <?php if($video): ?>
        <div class="dona-360-stage" hidden><video controls muted playsinline preload="none" data-dona-360-src="<?php echo esc_url(wp_get_attachment_url($video)); ?>" poster="<?php echo esc_url($poster_url); ?>" aria-label="360 degree rotation of <?php echo esc_attr(get_the_title($id)); ?>"></video></div>
        <div class="dona-360-controls" hidden><button type="button" data-dona-360-step="-45" aria-label="Rotate bag left 45 degrees">←</button><label>Rotate bag<input type="range" min="0" max="359" value="0" step="1" aria-describedby="dona-360-help" disabled></label><button type="button" data-dona-360-step="45" aria-label="Rotate bag right 45 degrees">→</button><output aria-live="off">0°</output></div>
        <?php endif; ?>
        <div class="dona-bag-sizes" hidden><h3>Size</h3><div role="group" aria-label="Choose bag size"></div><p class="dona-bag-size-status" role="status"></p></div>
        <p class="dona-360-status" role="status"></p>
      </div>
    </div>
    <?php
},12);

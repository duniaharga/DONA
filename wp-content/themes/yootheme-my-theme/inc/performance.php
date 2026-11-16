<?php
/** Faster frontend delivery, preserving originals and native Builder editing. */
add_action('after_setup_theme',function () {
    if (!class_exists('YOOtheme\\Event')) return;
    YOOtheme\Event::on('metadata.load',function ($metadata) {
        if (is_admin()) return $metadata;
        $config=YOOtheme\app(YOOtheme\Config::class);
        if ($config->get('app.isCustomizer')) return $metadata;
        if ($metadata->tag==='script' && $metadata->src) $metadata->attributes['defer']=true;
        if ($metadata->name==='style:theme' && wp_get_environment_type()==='local') {
            $file=basename(parse_url((string)$metadata->href,PHP_URL_PATH)??'');
            $path=get_stylesheet_directory().'/css/'.$file;
            if(preg_match('/^theme(?:\.[a-zA-Z0-9_-]+)*\.css$/D',$file)&&is_file($path)) {
                $metadata->attributes['href']=get_stylesheet_directory_uri().'/css/theme-delivery.php?file='.rawurlencode($file).'&v='.substr(hash_file('sha256',$path),0,12);
            }
        }
        return $metadata;
    });
},30);

function dona_webp_delivery_url($url) {
    if (is_admin() || !is_string($url) || !preg_match('/\.(png|jpe?g)$/i',parse_url($url,PHP_URL_PATH)??'')) return $url;
    $uploads=wp_upload_dir();
    if (!str_starts_with($url,$uploads['baseurl'].'/')) return $url;
    $relative=rawurldecode(substr($url,strlen($uploads['baseurl'])+1));
    if(str_contains($relative,'..'))return $url;
    return is_file($uploads['basedir'].'/'.$relative.'.webp') ? $url.'.webp' : $url;
}
add_filter('wp_get_attachment_image_attributes',function ($attributes) {
    if(isset($attributes['src']))$attributes['src']=dona_webp_delivery_url($attributes['src']);
    if(!empty($attributes['srcset']))$attributes['srcset']=preg_replace_callback('/(https?:\/\/[^\s,]+)(\s+\d+w)/',fn($m)=>dona_webp_delivery_url($m[1]).$m[2],$attributes['srcset']);
    return $attributes;
},50);

// Remove generated delivery copies when their owning attachment is deliberately deleted.
add_action('delete_attachment',function ($id) {
    $file=get_post_meta($id,'_wp_attached_file',true);
    if(!$file || str_contains($file,'..'))return;
    $uploads=wp_upload_dir();$paths=[$file];$directory=dirname($file);
    $metadata=wp_get_attachment_metadata($id);
    foreach(($metadata['sizes']??[]) as $size)$paths[]=$directory.'/'.$size['file'];
    if(!empty($metadata['original_image']))$paths[]=$directory.'/'.$metadata['original_image'];
    foreach($paths as $relative){$path=$uploads['basedir'].'/'.$relative.'.webp';if(is_file($path))wp_delete_file($path);}
});

// Studio serves HTML without compression; negotiate gzip without caching private pages.
add_action('template_redirect',function () {
    if(wp_get_environment_type()!=='local' || is_admin() || wp_doing_ajax() || is_feed() || is_robots() || headers_sent() || !function_exists('ob_gzhandler') || ini_get('zlib.output_compression'))return;
    if(defined('REST_REQUEST')&&REST_REQUEST)return;
    if(interface_exists('YOOtheme\\Config')&&YOOtheme\app(YOOtheme\Config::class)->get('app.isCustomizer'))return;
    foreach(headers_list() as $header)if(stripos($header,'Content-Encoding:')===0)return;
    ob_start('ob_gzhandler');
},-1);

// YOOtheme 4 background images are otherwise discovered only after its JS executes.
add_action('template_redirect',function () {
    if((!is_page('about')&&!is_front_page()) || is_admin())return;
    if(interface_exists('YOOtheme\\Config')&&YOOtheme\app(YOOtheme\Config::class)->get('app.isCustomizer'))return;
    ob_start(function ($html) {
        $tags=new WP_HTML_Tag_Processor($html);$urls=[];$limit=is_front_page()?2:1;
        while($tags->next_tag('DIV')) {
            if($tags->get_attribute('loading')!=='eager'||!$tags->has_class('uk-background-norepeat'))continue;
            $original=$tags->get_attribute('data-src');
            if(!$original || !str_starts_with($original,'/wp-content/uploads/'))continue;
            $original=home_url($original);$url=dona_webp_delivery_url($original);
            foreach((array)json_decode((string)$tags->get_attribute('data-sources'),true) as $source){
                if(($source['type']??'')!=='image/webp'||empty($source['srcset']))continue;
                $choices=explode(',',$source['srcset']);$candidate=preg_split('/\s+/',trim(end($choices)))[0];
                if(str_starts_with($candidate,'/wp-content/'))$url=home_url($candidate);
                break;
            }
            $fallback_type=wp_check_filetype(parse_url($original,PHP_URL_PATH))['type']?:'image/png';
            $style=(string)$tags->get_attribute('style');
            $style.=';background-image:url("'.$original.'");background-image:image-set(url("'.$url.'") type("image/webp"),url("'.$original.'") type("'.$fallback_type.'"))';
            $tags->set_attribute('style',$style);$tags->remove_attribute('uk-img');$urls[]=$url;if(count($urls)>=$limit)break;
        }
        $html=$tags->get_updated_html();
        // A lightweight first frame paints before the decorative hero video buffers.
        if (is_front_page()) {
            $hover_tags=new WP_HTML_Tag_Processor($html);
            while($hover_tags->next_tag('IMG')) {
                if($hover_tags->has_class('el-hover-image'))$hover_tags->set_attribute('loading','lazy');
            }
            $html=$hover_tags->get_updated_html();
            $video_tags=new WP_HTML_Tag_Processor($html);
            while($video_tags->next_tag('VIDEO')) {
                $src=(string)$video_tags->get_attribute('src');
                if(!str_ends_with(parse_url($src,PHP_URL_PATH)??'','/2026/11/gemini_generated_video_47f419a6.mp4'))continue;
                $uploads=wp_upload_dir();
                if(!is_file($uploads['basedir'].'/2026/11/dona-editorial-hero-optimized.mp4') || !is_file($uploads['basedir'].'/2026/11/dona-editorial-hero-poster.webp'))continue;
                $video_tags->set_attribute('src',$uploads['baseurl'].'/2026/11/dona-editorial-hero-optimized.mp4');
                $poster=$uploads['baseurl'].'/2026/11/dona-editorial-hero-poster.webp';
                $video_tags->set_attribute('poster',$poster);
                $urls[]=$poster;
            }
            $html=$video_tags->get_updated_html();
        }
        $preloads='';foreach($urls as $url)$preloads.='<link rel="preload" as="image" type="image/webp" href="'.esc_url($url).'" fetchpriority="high">';
        return $preloads?str_ireplace('</head>',$preloads.'</head>',$html):$html;
    });
},1);

// Closed UIkit overlays must stay hidden before UIkit adds its component classes.
add_action('wp_head',function () {
    if(is_admin())return;
    echo '<style id="dona-first-paint">[uk-offcanvas]:not(.uk-open),[data-uk-offcanvas]:not(.uk-open),[uk-modal]:not(.uk-open),[data-uk-modal]:not(.uk-open){display:none}.single-product [uk-accordion]>:not(.uk-open)>.uk-accordion-content{display:none}.single-product .dona-product-details-column{min-width:0}@media(max-width:959px){.single-product .dona-product-details-column{order:-1}}</style>';
},1);

add_action('wp_head',function () {
    if(!is_product())return;
    echo '<noscript><style>.single-product select.dona-color-select,.single-product select.dona-size-select{position:static!important;width:100%!important;height:auto!important;clip-path:none!important;opacity:1!important;overflow:visible!important}</style></noscript>';
},2);

<?php
/** Extend Rank Math's existing graph only where the visible About page needs a page entity. */
add_filter('rank_math/json_ld', function ($data) {
    if (!is_page('about')) return $data;
    $organization = null;$breadcrumb = null;
    foreach ($data as $node) {
        if (!is_array($node)) continue;
        $types=(array)($node['@type']??[]);
        if (array_intersect(['WebPage','AboutPage'],$types)) return $data;
        if(in_array('Organization',$types,true))$organization=$node['@id']??null;
        if(in_array('BreadcrumbList',$types,true))$breadcrumb=$node['@id']??null;
    }
    $page=['@type'=>'AboutPage','@id'=>get_permalink().'#webpage','url'=>get_permalink(),'name'=>get_the_title(),
        'description'=>get_post_meta(get_queried_object_id(),'rank_math_description',true),'inLanguage'=>get_bloginfo('language'),
        'about'=>$organization?['@id'=>$organization]:['@type'=>'Organization','@id'=>home_url('/#organization'),'name'=>'DONA','url'=>home_url('/')]];
    if($breadcrumb)$page['breadcrumb']=['@id'=>$breadcrumb];
    $data['dona_about_page']=$page;
    return $data;
},99);

<?php
/** Native, editable collection cards above the Shop's existing product grid. */
function dona_shop_collections_layout($value, $collection_ids=[19,20,21]) {
    if (!is_string($value)) return $value;
    $config=json_decode($value,true);
    $key='dona-shop-archive-product';
    if (!isset($config['templates'][$key]['layout'])) return $value;
    $visit=function (&$node) use (&$visit,$collection_ids) {
        if (!is_array($node)) return;
        if (($node['type']??'')==='grid' && str_contains($node['props']['class']??'','dona-shop-collections')) {
            $node['props']['id']='collections';
            $node['props']['name']='All DONA collections';
            $node['props']['grid_large']=(string)min(4,count($collection_ids));
            $node['props']['grid_medium']=(string)min(4,count($collection_ids));
            $node['children']=array_map(function ($id) {
                return ['type'=>'grid_item','source'=>[
                    'query'=>['name'=>'productCats','field'=>['name'=>'customProductCat','arguments'=>['id'=>(int)$id]]],
                    'props'=>['title'=>['name'=>'name'],'image'=>['name'=>'thumbnail.url'],
                        'image_alt'=>['name'=>'name'],'link'=>['name'=>'link']],
                ]];
            },$collection_ids);
        }
        foreach ($node as &$child) if (is_array($child)) $visit($child);
    };
    $visit($config['templates'][$key]['layout']);
    return wp_json_encode($config);
}
// Make the fix effective on the next shop render, including before an admin visit.
add_filter('option_yootheme',function ($value) {
    if (is_admin() || get_option('dona_shop_collections_grid_version')==='1') return $value;
    return dona_shop_collections_layout($value);
});
// Save the same native element change once, so it remains editable in Builder.
add_action('admin_init',function () {
    if (!current_user_can('manage_options') || get_option('dona_shop_collections_grid_version')==='1') return;
    $ids=[];
    $collections=get_terms(['taxonomy'=>'pa_collection','hide_empty'=>false]);
    if (!is_wp_error($collections)) foreach ($collections as $collection) {
        $category=get_term_by('slug',$collection->slug,'product_cat');
        if ($category) $ids[]=$category->term_id;
    }
    if (!$ids) $ids=[19,20,21];
    $original=get_option('yootheme');
    $updated=dona_shop_collections_layout($original,$ids);
    if ($updated===$original) return;
    update_option('yootheme',$updated);
    update_option('dona_shop_collections_grid_version','1',false);
});
// Keep Color/Collection filters, but omit Type from the Shop's attribute filter UI.
add_filter('woocommerce_attribute_taxonomies',function ($attributes) {
    if (is_admin() || !is_shop()) return $attributes;
    return array_values(array_filter($attributes,fn($attribute)=>$attribute->attribute_name!=='bag_type'));
});

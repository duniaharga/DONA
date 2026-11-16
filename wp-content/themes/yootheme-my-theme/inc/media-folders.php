<?php
/** Media folders built into the DONA child theme, using WordPress attachment taxonomy. */
add_action('init', function () {
    register_taxonomy('dona_media_folder', 'attachment', [
        'labels'=>['name'=>'Media Folders','singular_name'=>'Media Folder','add_new_item'=>'Add New Folder','parent_item'=>'Parent Folder'],
        'public'=>false,'hierarchical'=>true,'show_ui'=>true,'show_in_menu'=>false,'show_admin_column'=>true,
        'rewrite'=>false,'query_var'=>false,'update_count_callback'=>'_update_generic_term_count',
        'capabilities'=>['manage_terms'=>'manage_options','edit_terms'=>'manage_options','delete_terms'=>'manage_options','assign_terms'=>'upload_files'],
    ]);
});
add_action('admin_menu', function () {
    add_media_page('Media Folders','Folders','manage_options','edit-tags.php?taxonomy=dona_media_folder&post_type=attachment');
});
function dona_media_folder_terms() {
    $terms=get_terms(['taxonomy'=>'dona_media_folder','hide_empty'=>false]);
    return is_wp_error($terms) ? [] : $terms;
}
function dona_media_folder_query($args, $folder) {
    if ($folder === '' || $folder === 'all') return $args;
    $filter=['taxonomy'=>'dona_media_folder'];
    if ($folder === '0') $filter['operator']='NOT EXISTS';
    else { $filter['field']='term_id'; $filter['terms']=[absint($folder)]; $filter['include_children']=true; }
    $existing=$args['tax_query'] ?? [];
    $args['tax_query']=$existing ? ['relation'=>'AND',$existing,$filter] : [$filter];
    return $args;
}
add_filter('ajax_query_attachments_args', function ($args) {
    $value=$_REQUEST['query']['dona_folder'] ?? '';
    return dona_media_folder_query($args, is_scalar($value) ? sanitize_text_field(wp_unslash($value)) : '');
});
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query() || get_current_screen()?->id !== 'upload') return;
    $value=$_GET['dona_folder'] ?? '';
    if (!is_scalar($value)) return;
    $args=dona_media_folder_query([],sanitize_text_field(wp_unslash($value)));
    if (isset($args['tax_query'])) $query->set('tax_query',$args['tax_query']);
});
add_filter('attachment_fields_to_edit', function ($fields,$post) {
    if (!current_user_can('edit_post',$post->ID)) return $fields;
    $selected=wp_get_object_terms($post->ID,'dona_media_folder',['fields'=>'ids']);
    $html='<select name="attachments['.absint($post->ID).'][dona_folder]" aria-label="Media folder"><option value="0">Uncategorized</option>';
    foreach (dona_media_folder_terms() as $term) $html.='<option value="'.$term->term_id.'" '.selected(in_array($term->term_id,is_array($selected)?$selected:[],true),true,false).'>'.esc_html($term->name).'</option>';
    $fields['dona_folder']=['label'=>'Folder','input'=>'html','html'=>$html.'</select>'];
    return $fields;
},10,2);
add_filter('attachment_fields_to_save', function ($post,$attachment) {
    if (isset($attachment['dona_folder']) && current_user_can('edit_post',$post['ID'])) {
        $id=absint($attachment['dona_folder']);
        if (!$id || term_exists($id,'dona_media_folder')) wp_set_object_terms($post['ID'],$id?[$id]:[],'dona_media_folder');
    }
    return $post;
},10,2);
add_action('wp_ajax_dona_move_media',function () {
    check_ajax_referer('dona_media_folders','nonce');
    if (!current_user_can('upload_files')) wp_send_json_error(['message'=>'You cannot organize media.'],403);
    $folder=absint($_POST['folder'] ?? 0);
    if ($folder && !term_exists($folder,'dona_media_folder')) wp_send_json_error(['message'=>'Folder does not exist.'],400);
    $ids=isset($_POST['ids']) && is_array($_POST['ids']) ? array_unique(array_map('absint',$_POST['ids'])) : [];
    if (!$ids || count($ids)>200) wp_send_json_error(['message'=>'Select between 1 and 200 media files.'],400);
    foreach ($ids as $id) if (get_post_type($id)!=='attachment' || !current_user_can('edit_post',$id)) wp_send_json_error(['message'=>'You cannot edit a selected file.'],403);
    foreach ($ids as $id) { $result=wp_set_object_terms($id,$folder?[$folder]:[],'dona_media_folder'); if (is_wp_error($result)) wp_send_json_error(['message'=>$result->get_error_message()],500); }
    wp_send_json_success(['count'=>count($ids)]);
});
add_filter('bulk_actions-upload',function ($actions) {
    $actions['dona_folder_0']='Move to Uncategorized';
    foreach (dona_media_folder_terms() as $term) $actions['dona_folder_'.$term->term_id]='Move to '.$term->name;
    return $actions;
});
add_filter('handle_bulk_actions-upload',function ($redirect,$action,$ids) {
    if (!str_starts_with($action,'dona_folder_')) return $redirect;
    $folder=absint(substr($action,12));
    if ($folder && !term_exists($folder,'dona_media_folder')) return $redirect;
    foreach ($ids as $id) if (current_user_can('edit_post',$id)) wp_set_object_terms($id,$folder?[$folder]:[],'dona_media_folder');
    return $redirect;
},10,3);
add_action('admin_enqueue_scripts',function () {
    $screen=get_current_screen();
    if (!$screen || !in_array($screen->base,['upload','post','post-new'],true)) return;
    wp_enqueue_media();
    wp_enqueue_script('dona-media-folders',get_stylesheet_directory_uri().'/js/media-folders.js',['media-views','jquery'],filemtime(__DIR__.'/../js/media-folders.js'),true);
    $terms=array_map(function ($term) {return ['id'=>$term->term_id,'name'=>$term->name,'parent'=>$term->parent,'count'=>$term->count];},dona_media_folder_terms());
    wp_localize_script('dona-media-folders','donaMediaFolders',['terms'=>$terms,'selected'=>isset($_GET['dona_folder']) && is_scalar($_GET['dona_folder'])?sanitize_text_field(wp_unslash($_GET['dona_folder'])):'all','nonce'=>wp_create_nonce('dona_media_folders'),'manage'=>admin_url('edit-tags.php?taxonomy=dona_media_folder&post_type=attachment'),'library'=>admin_url('upload.php'),'ajax'=>admin_url('admin-ajax.php')]);
    if ($screen->base==='upload') wp_enqueue_style('dona-media-folders',get_stylesheet_directory_uri().'/css/media-folders.css',[],filemtime(__DIR__.'/../css/media-folders.css'));
});

// Attachment folder counts include unattached files as well as attached files.
add_action('admin_init',function () {
    if (!get_option('dona_native_folder_migration') || get_option('dona_media_folder_count_version')==='1') return;
    $terms=dona_media_folder_terms();
    wp_update_term_count_now(array_map(fn($term)=>$term->term_taxonomy_id,$terms),'dona_media_folder');
    update_option('dona_media_folder_count_version','1',false);
});

add_filter('manage_edit-dona_media_folder_columns',function ($columns) {
    unset($columns['posts']); $columns['dona_files']='Files'; return $columns;
});
add_filter('manage_dona_media_folder_custom_column',function ($output,$column,$term_id) {
    if ($column!=='dona_files') return $output;
    $term=get_term($term_id,'dona_media_folder');
    return '<a href="'.esc_url(add_query_arg('dona_folder',$term_id,admin_url('upload.php'))).'">'.absint($term->count).'</a>';
},10,3);

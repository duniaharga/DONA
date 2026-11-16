/* Native WordPress media queries, folder filter, selection moves and drag/drop. */
(function ($) {
    if (!window.wp?.media?.view?.AttachmentsBrowser || !window.donaMediaFolders) return;
    const config=donaMediaFolders;
    const terms=[];
    function flatten(parent,depth){config.terms.filter(term=>term.parent===parent).forEach(term=>{terms.push({...term,label:'— '.repeat(depth)+term.name});flatten(term.id,depth+1);});}
    flatten(0,0);
    const FolderFilter=wp.media.view.AttachmentFilters.extend({
        id:'dona-media-folder-filter',
        createFilters:function(){
            this.filters={all:{text:'All folders',props:{dona_folder:'all'}},uncategorized:{text:'Uncategorized',props:{dona_folder:'0'}}};
            terms.forEach(term=>{this.filters['folder_'+term.id]={text:term.label,props:{dona_folder:String(term.id)}};});
            if (!this.model.has('dona_folder')) this.model.set('dona_folder',document.body.classList.contains('upload-php')?config.selected:'all');
            this.listenTo(this.model,'change:dona_folder',function(){
                if(!document.body.classList.contains('upload-php') || this.$el.closest('.media-modal').length)return;
                config.selected=String(this.model.get('dona_folder'));
                $('#dona-media-sidebar a[data-folder]').removeAttr('aria-current').filter(function(){return this.dataset.folder===config.selected;}).attr('aria-current','page');
            });
        }
    });
    const original=wp.media.view.AttachmentsBrowser.prototype.createToolbar;
    wp.media.view.AttachmentsBrowser.prototype.createToolbar=function(){
        original.apply(this,arguments);
        this.toolbar.set('dona-folders',new FolderFilter({controller:this.controller,model:this.collection.props,priority:-75}).render());
        this.toolbar.get('dona-folders').$el.attr('aria-label','Filter media by folder');
    };
    $(function(){
        if (!document.body.classList.contains('upload-php')) return;
        const sidebar=$('<aside id="dona-media-sidebar" aria-label="Media folders"></aside>');
        sidebar.append($('<h2>').text('Media Folders'));
        const list=$('<nav aria-label="Folder list"></nav>').appendTo(sidebar);
        function folderLink(id,label,depth=0){
            const url=new URL(config.library);if(id!=='all')url.searchParams.set('dona_folder',id);
            const link=$('<a>').attr({href:url.href,'data-folder':id}).text(label).css('paddingLeft',(10+depth*16)+'px');
            if(String(id)===String(config.selected))link.attr('aria-current','page');
            list.append(link);
        }
        folderLink('all','All files');folderLink('0','Uncategorized');
        terms.forEach(term=>folderLink(term.id,term.name+' ('+term.count+')',(term.label.match(/— /g)||[]).length));
        sidebar.append($('<a class="button dona-manage-folders">').attr('href',config.manage).text('Create / manage folders'));
        const target=$('<select aria-label="Destination folder"><option value="0">Uncategorized</option></select>');
        terms.forEach(term=>target.append($('<option>').val(term.id).text(term.label)));
        sidebar.append($('<label for="dona-move-target">').text('Move selected files to'));
        target.attr('id','dona-move-target');sidebar.append(target);
        const move=$('<button type="button" class="button">Move selected files</button>');sidebar.append(move);
        const status=$('<p role="status"></p>').appendTo(sidebar);
        sidebar.append($('<p class="description">').text('Use Bulk select, or drag a media thumbnail onto a folder. File URLs stay the same.'));
        $('#wpbody-content').prepend(sidebar);document.body.classList.add('dona-has-media-folders');
        function selectedIds(){
            const ids=$('.attachments .attachment.selected').map(function(){return Number(this.dataset.id);}).get().filter(Boolean);
            if(ids.length)return ids;
            return $('#the-list input[name="media[]"]:checked').map(function(){return Number(this.value);}).get();
        }
        function moveFiles(ids,folder){
            if(!ids.length){status.text('Use Bulk select to choose media files first.');return;}
            move.prop('disabled',true);status.text('Moving files…');
            $.post(config.ajax,{action:'dona_move_media',nonce:config.nonce,ids,folder}).done(response=>{
                if(response.success){status.text(response.data.count+' file(s) moved.');const url=new URL(config.library);if(config.selected!=='all')url.searchParams.set('dona_folder',config.selected);window.location.href=url.href;}
                else status.text(response.data?.message||'Unable to move files.');
            }).fail(()=>status.text('Unable to move files. Please try again.')).always(()=>move.prop('disabled',false));
        }
        move.on('click',()=>moveFiles(selectedIds(),target.val()));
        $(document).on('mouseenter','.attachments .attachment',function(){this.draggable=true;});
        $(document).on('dragstart','.attachments .attachment',function(event){
            const ids=selectedIds();const id=Number(this.dataset.id);if(!ids.includes(id))ids.splice(0,ids.length,id);
            event.originalEvent.dataTransfer.setData('application/x-dona-media',JSON.stringify(ids));
        });
        list.on('click','a[data-folder]',function(event){
            const filter=$('#wp-media-grid #dona-media-folder-filter');
            if(!filter.length)return;event.preventDefault();
            const key=this.dataset.folder==='all'?'all':(this.dataset.folder==='0'?'uncategorized':'folder_'+this.dataset.folder);
            filter.val(key).trigger('change');
        });
        list.on('dragover','a[data-folder]',function(event){if(this.dataset.folder!=='all'){event.preventDefault();$(this).addClass('dona-drop-target');}})
            .on('dragleave','a',function(){$(this).removeClass('dona-drop-target');})
            .on('drop','a[data-folder]',function(event){
                event.preventDefault();$(this).removeClass('dona-drop-target');if(this.dataset.folder==='all')return;
                try{moveFiles(JSON.parse(event.originalEvent.dataTransfer.getData('application/x-dona-media')),this.dataset.folder);}catch(_){status.text('Drag a media thumbnail onto a folder.');}
            });
    });
})(jQuery);

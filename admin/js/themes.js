var themeManage = {
    area_drop_options: {
        placeholder: 'block_drop',
        forcePlaceholderSize: true,
        connectWith: '.area_drop',
        containment: $('#block_add').parents('.item'),
        axis: 'y'
    },
    init: function() {
        // Return if we're not on the themes page
        if (!$('.page-themes').length) {return;}

        helpToggler.init();

        // Adding available blocks
        $('#block_instance_add').live('click', function() {
            themeManage.add_block($('#block_instance_title').val(), $('#block_instance_type').val());
        });

        // Add a block to an area
        $('.area_available').live('click', function() {
            // Clone the block, it has the right name and id
            var block = $(this).closest('.block_drag').clone();
            // Change the clone to have the controls we need
            block.find('.instance_controls,small').remove();
            block.append('<div class="close">&nbsp;</div><div class="handle">&nbsp;</div>');
            // Add the block to the target area
            var target = $('#'+($(this).attr('class').match(/target_([\w-]+)/)[1]));
            target.append(block);
            themeManage.refresh_areas();
            return false;
        });

        // Remove a block from an area
        $('.close').live('click', function() {
            $(this).parent().remove();
            themeManage.refresh_areas();
        });

        // Calculate a hash of the initial state so we can tell if save is required
        themeManage.initial_data_hash = themeManage.data_hash();

        // Sort blocks in areas
        // @todo Move the options to a property, so they're not repeated in save_areas.
        $('.area_drop').sortable({
            items: '.block_drag',
            placeholder: 'block_drop',
            forcePlaceholderSize: true,
            connectWith: '.area_drop',
            containment: $('#block_add').parents('.item'),
            update: themeManage.refresh_areas,
            remove: themeManage.refresh_areas,
            axis: 'y'
        });
        themeManage.refresh_areas();

        // Save areas
        $('#save_areas').click(function() {
            themeManage.save_areas();
        });

        // Warn user about unsaved changes
        window.onbeforeunload = function() {
            if (themeManage.changed()) {
                spinner.start(); spinner.stop();
                return _t('You did not save the changes you made. \nLeaving this page will result in the lose of data.');
            }
        };
    },
    refresh_areas: function() {
        $('.area_drop').sortable('refresh');
        $('.area_drop').each(function() {
            var area = $(this);
            if (area.find('.block_drag').length == 0) {
                area.find('.no_blocks').show();
            } else {
                area.find('.no_blocks').hide();
            }
        });
        if (themeManage.changed()) {
            $('#save_areas').removeAttr('disabled');
        } else {
            $('#save_areas').attr('disabled', 'disabled');
        }
    },
    add_block: function (title, type) {
        spinner.start();
        $('#block_add').load(
            habari.url.ajaxAddBlock,
            {title:title, type:type}
        );
        spinner.stop();
    },
    delete_block: function (id) {
        spinner.start();
        $('#block_add').load(
            habari.url.ajaxDeleteBlock,
            {block_id:id}
        );
        spinner.stop();
    },
    save_areas: function() {
        spinner.start();
        var output = {};
        $('.area_drop_outer').each(function() {
            var area = $('h2', this).data('areaname');
            output[area] = [];
            $('.block_drag', this).each(function(){
                m = $(this).attr('class').match(/block_instance_(\d+)/)
                output[area].push(m[1]);
            });
        });
        habari_ajax.post(
            habari.url.ajaxSaveAreas,
            {area_blocks:output, scope:$('#scope_id').val(), changed:themeManage.changed()},
            {'block_areas': '#scope_container'},
            // Can't simply refresh the sortable because we've reloaded the element
            function(data) {
                $('.area_drop').sortable({
                    placeholder: 'block_drop',
                    forcePlaceholderSize: true,
                    connectWith: '.area_drop',
                    containment: $('#block_add').parents('.item'),
                    update: themeManage.refresh_areas,
                    remove: themeManage.refresh_areas,
                    axis: 'y'
                });
                // We've saved, reset the hash
                themeManage.initial_data_hash = themeManage.data_hash();
                themeManage.refresh_areas();
            }
        );
    },
    change_scope: function() {
        spinner.start();
        var output = {};
        habari_ajax.post(
            habari.url.ajaxSaveAreas,
            {scope:$('#scope_id').val()},
            {'block_areas': '#scope_container'},
            // Can't simply refresh the sortable because we've reloaded the element
            function(data) {
                $('.area_drop').sortable({
                    placeholder: 'block_drop',
                    forcePlaceholderSize: true,
                    connectWith: '.area_drop',
                    containment: $('#block_add').parents('.item'),
                    update: themeManage.refresh_areas,
                    remove: themeManage.refresh_areas,
                    axis: 'y'
                });
                // We've saved, reset the hash
                themeManage.initial_data_hash = themeManage.data_hash();
                themeManage.refresh_areas();
            }
        );
    },
    changed: function() {
        return themeManage.initial_data_hash != themeManage.data_hash();
    },
    data_hash: function() {
        var output = '';
        $('.area_drop_outer').each(function() {
            var area = $('h2', this).text();
            output += area;
            $('.block_drag', this).each(function(){
                m = $(this).attr('class').match(/block_instance_(\d+)/)
                output += m[1];
            });
        });
        return crc32(output);
    }

};

$(document).ready(function(){
    themeManage.init();
});
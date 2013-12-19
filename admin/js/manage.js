var itemManage = {
    init: function() {
        if ($('.page-users, .page-options, .page-user, .page-tags, .page-plugins, .page-groups').length !== 0) {
            $("input#search").keyup(function (e) {
                var str = $('input#search').val();
                itemManage.simpleFilter(str);
            });
        }

        if (!$('.item.controls input[type=checkbox]')) {return;}

        /* for all manage pages except for comments, add an ajax call to the
         * delete button
         */
        if ( $('.manage.comments').length === 0 ) {
            $('.item.controls input.button.delete').click(function () {
                itemManage.update( 'delete' );
                return false;
            });
        }

        $('.item.controls input.rename.button').click(function() {
            itemManage.rename();
        });
    },
    expand: function(item) {
        $('.item').removeClass('expanded');

        item.addClass('expanded');

        $('.more', item).click(function() {
            itemManage.contract($(this).parent());
        });
    },
    contract: function(item) {
        item.removeClass('expanded');
    },
    selected: {},
    searchCache: [],
    searchRows: [],
    simpleFilter: function( search ) {
        search = $.trim( search.toLowerCase() );

        // cache search items on first call
        if ( itemManage.searchCache.length === 0 ) {
            itemManage.searchRows = $('li.item, .item.plugin, .item.tag, div.settings, .container.plugins, .item.group');
            itemManage.searchCache = itemManage.searchRows.map(function() {
                return $(this).text().toLowerCase();
            });
        }

        itemManage.searchCache.each(function(i) {
            if ( this.search( search ) == -1 ) {
                $(itemManage.searchRows[i]).addClass('hidden');
            } else {
                $(itemManage.searchRows[i]).removeClass('hidden');
            }
        });

        if ($('div.settings').length !== 0 || $('.container.plugins:visible').length > 1) {
            $('select[name=navigationdropdown]').val('all');
        }
    },
    checkEverything: function() {
        itemManage.fetch(0, $('.currentposition .total').text(), false, true);
    },
    purge: function () {
        itemManage.update( 'purge' );
    },
    update: function( action, id ) {
        spinner.start();
        var query = {};
        if ( id === undefined ) {
            query.selected = JSON.parse($('#manage_selected_items').attr('value'));
        }
        else {
            query.selected = id;
        }

        query.action = action;
        query.timestamp = $('input[name=timestamp]').attr('value');
        query.nonce = $('input[name=nonce]').attr('value');
        query.digest = $('input[name=digest]').attr('value');


        if ( $('.manage.users').length !== 0 ) {
            query.reassign = $('select#reassign').attr('value');
        }

        elItem = $('#item-' + id);

        if (elItem.length > 0 || action == 'delete') {
            elItem.fadeOut();
        }

        habari_ajax.post(
            itemManage.updateURL,
            query,
            function( result ) {
                if ( $('.timeline').length ) {
                    /* TODO: calculate new offset and limit based on filtering
                     * and the current action
                     */
                    loupeInfo = timeline.getLoupeInfo();
                    itemManage.fetch( 0, loupeInfo.limit, true );
                    timeline.updateLoupeInfo();
                }
                else {
                    itemManage.fetch( 0, 20, false );
                }

                // if we deleted stuff, scroll to the top of the new page
                if ( action == 'delete' ) {
                    window.scroll(0,0);
                }

                itemManage.selected = {};
            }
        );
    },
    rename: null,
    remove: function( id ) {
        itemManage.update( 'delete', id );
    },
    fetch: function( offset, limit, resetTimeline, silent ) {
        offset = ( offset === undefined ) ? 0 : offset;
        limit = ( limit === undefined ) ? 20: limit;
        silent = ( silent === undefined ) ? false: silent;
        spinner.start();

        habari_ajax.get(
            itemManage.fetchURL,
            '&search=' + liveSearch.getSearchText() + '&offset=' + offset + '&limit=' + limit,
            function(json) {
                if (silent) {
                    itemManage.selected = json.item_ids;
                } else {
                    itemManage.fetchReplace.html(json.items);
                    // if we have a timeline, replace its content
                    if ( resetTimeline && $('.timeline').length !== 0 ) {
                        // we hide and show the timeline to fix a firefox display bug
                        $('.years').html(json.timeline).hide();
                        setTimeout( function() {
                            $('.years').show();
                            timeline.reset();
                        }, 100 );
                        $('input.checkbox').rangeSelect();
                    }
                    else {
                        $('input.checkbox').rangeSelect();
                    }
                    findChildren();
                }
            }
        );
    }
};

$(document).ready(function(){
    itemManage.init();
});
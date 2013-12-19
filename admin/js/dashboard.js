var dashboard = {
    init: function() {
        $('.modules').sortable({
            items: '.module:not(.add-item-module)',
            handle: 'div.handle',
            opacity: 0.9,
            stop: function() {
                dashboard.update();
            }
        });

        $('.options').live('click', function(){
            var li = $(this).closest('li');
            if(li.hasClass('viewingoptions')) {
                li.toggleClass('viewingoptions');
            }
            else {
                spinner.start();
                $('.optionswindow .optionsform', li).load(
                    habari.url.ajaxDashboard,
                    {'action': 'configModule', 'moduleid': li.data('module-id')},
                    function(){
                        li.toggleClass('viewingoptions');
                        spinner.stop();
                    }
                );
            }
        });

        $('.close', '.modules').live('click', function() {
            // grab the module ID from the parent DIV data attribute.
            dashboard.remove( $(this).parents('.module').data('module-id') );
        });

        $('.optionsform form').live('submit', function(){
            return dashboard.post(this);
        });
        findChildren();
    },
    update: function() {
        spinner.start();
        // disable dragging and dropping while we update
        $('.modules').sortable('disable');
        var query = [];
        $('.module', '.modules').not('.ui-sortable-helper').each( function(i) {
            query.push($(this).data('module-id'));
        } );
        query.action = 'updateModules';
        habari_ajax.post(
            habari.url.ajaxDashboard,
            {'moduleOrder': query, 'action': 'updateModules'},
            function() {
                $('.modules').sortable('enable');
            }
        );
    },
    updateModule: function() {
        //spinner.start();
        // disable dragging and dropping while we update
        // here we would update the modules options then
        // reload the modules
    },
    add: function() {
        spinner.start();
        // disable dragging and dropping while we update
        $('.modules').sortable('disable');
        var query = {};
        query.action = 'addModule';
        query.module_name = $('#dash_additem option:selected').val();
        habari_ajax.post(
            habari.url.ajaxDashboard,
            query,
            {modules: '.modules'},
            function(){
                $('.modules').sortable('refresh');
                $('.modules').sortable('enable');
            }
        );
    },
    remove: function( id ) {
        spinner.start();
        // disable dragging and dropping while we update
        $('.modules').sortable('destroy');
        var query = {};
        query.action = 'removeModule';
        query.moduleid = id;
        habari_ajax.post(
            habari.url.ajaxDashboard,
            query,
            {modules: '.modules'},
            dashboard.init
        );
    },
    post: function(blockform) {
        var form = $(blockform);
        $.ajax({
            success: function(data){
                form.parents('.optionsform').html(data);
            },
            error: function(data, err) {
                console.log(data, err);
            },
            type: 'POST',
            url: habari.url.ajaxConfigModule,
            data: form.serialize(),
            dataType: 'html'
        });
        return false;
    }
};

$(document).ready(function() {
    dashboard.init();
});
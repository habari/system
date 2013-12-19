var groupManage = {
    init: function(users) {
        this.users = users;

        for(var z in this.users) {
            if (users.hasOwnProperty(z)) {
                $('#assign_user').append($('<option value="' + this.users[z].id + '">' + this.users[z].username + '</option>'));
                if (this.users[z].member) {
                    this.addMember(this.users[z].id);
                }
            }
        }

        this.userscanAll();

        $('#add_user').click(function() {
            groupManage.addMember($('#assign_user').val());
        });

        // Apply permission deny/allow toggle rules
        $('.bool-permissions input[type=checkbox],.crud-permissions input[type=checkbox]').change(function(){
            if ($(this).attr('checked')) {
                if ($(this).hasClass('bitflag-deny')) {
                    $('input[type=checkbox]', $(this).parents('tr')).filter(function(){return !$(this).hasClass('bitflag-deny');}).attr('checked', false);
                }
                else {
                    $('input[type=checkbox].bitflag-deny', $(this).parents('tr')).attr('checked', false);
                }
            }
        });

    },
    removeMember: function(member, id) {
        name = this.users[id].username;

        if (this.users[id].member) {
            if ($('#user_' + id).val() === 0) {
                $('#user_' + id).val('1');
                $('#currentusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
            }
            else {
                $('#removedusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
                $('#user_' + id).val('0');
            }
        }
        else {
            $('#assign_user').append($('<option value="' + id + '">' + name + '</option>'));
            $('#add_users').show();
            $('#user_' + id).val('0');
        }

        $(member).remove();

        this.userscanAll();
        return false;
    },
    addMember: function(id) {
        $('#assign_user option[value=' + id + ']').remove();

        $('#user_' + id).val('1');

        if (this.users[id].member) {
            $('#currentusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
        }
        else {
            $('#newusers .memberlist').append('<a href="#" onclick="groupManage.removeMember(this,'+id+');" class="user">' + this.users[id].username + '</a>');
        }
        this.userscanAll();
    },
    userscanAll: function() {
        this.userscan('#currentusers');
        this.userscan('#removedusers');
        this.userscan('#newusers');
        if ($('#add_users option').length > 0) {
            $('#add_users').show();
        }
        else {
            $('#add_users').hide();
        }
    },
    userscan: function(div) {
        if ($(div + ' .user').length > 0) {
            $(div).show();
        }
        else {
            $(div).hide();
        }
    }
};
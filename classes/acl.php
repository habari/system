<?php

/** Defines the 'acl' php object class. 
 *
 * <code>
 * $acl = new acl('registrar');
 * $acl->adduser(1); // Add user 1 to this acl.
 * $acl->adduser(1, 10); // Add user 1 to this acl with karma 10
 * $acl->adduser( $user ); // Add user $user to this acl.
 * $acl->deluser(2); // Remove user 2 from this acl
 * $acl->deluser( $user ); // Remove user $user from this acl
 * $list = $acl->members();
 * </code>
 *
 */
class acl {

/**
 * CONSTRUCTOR
 *
 * Create a new ACL object, either by name, or by object
 *
 * @param mixed The name of the ACL, or the object type of the object
 * for which the ACL is to be created. You may also pass in an actual
 * object, if desired.
 * @param int The ID number of the object in question. If it is a
 * name-based ACL, rather than an object-based ACL, this must be set to
 * 0
 *
 * <code>
 * $acl = new acl( 'registrar' ); // Name-based
 * $acl = new acl( $page ); // Object-based - ID of $page is used
 * $acl = new acl( 'story', 22 ); // ACL for story record ID 22
 * </code>
 */
function __construct($obj, $ID = 0) {

    // Was the argument an object?
    if ( is_object( $obj ) ) {
        $this->name = get_class( $obj );
        $this->id   = $obj->id;
    } else {
        $this->name = $obj;
        $this->id   = $ID;
    }

    // And if it's an object ...
    if ($this->id != 0) {
        $this->object = ( is_object( $obj ) ) ?
            $obj :
            new $obj( array ( 'ID' => $ID ));
        $this->type = 'object';
    }
}

/** function adduser()
 *
 * Adds the specified user (either an ID, or an user object)
 * to the acl
 *
 * <code>
 * $acl->adduser(25);
 * $acl->adduser( $user, 8 );
 * </code>
 *
 * @param mixed User object or ID
 * @param int Karma
 */
function adduser($user, $karma = 0) {
    // User object or ID?
    if ( is_object( $user )) {
        $ID = $user->id;
    } else {
        $ID = $user;
    }

    DB::query("INSERT INTO acl
        (userid, objecttype, objectID, karma)
        values
        (?, '?', ?, ?)",
        array( $ID, $this->name, $this->id, $karma));
}

/** function deluser()
 *
 * <code>
 * $acl->deluser(25);
 * $acl->deluser( $user );
 * </code>
 *
 * Removes the specified user (identified by ID, or by object)
 * from the acl
 *
 * @param mixed User object or ID
 * @return void
 */
function deluser($user) {
    // User object or ID?
    if ( is_object( $user )) {
        $ID = $user->id;
    } else {
        $ID = $user;
    }

    DB::query("DELETE FROM acl
        WHERE userid = ?
        AND   objecttype = '?'
        AND   objectID   = ?",
        array( $ID, $this->name, $this->id)) ;
}

/*
 * Returns a list of userids which are in a particular ACL
 * Two optional arguments. The first is the karma level you're
 * interested in, and the second is a bolean - 1 if you want ONLY that
 * level, or 0 (or undefined) if you want that level and greater.
 *
 * @param int $karma
 * @param binary $exclusive
 * @return array $members
 */
function members( $karma = 0, $exclusive = 0) {
    return acl::all_members( $karma, $exclusive, $this );
}

/**
 * Return a list of the admins for this ACL.
 *
 * @return array $admins
 */
function admins() {
    return $this->members( 10 );
}

/**
 * Return a list of the publishers (and above) for this ACL. If the
 * $exclusive argument is set to 1, only the publishers (ie, nobody
 * above) are returned.
 *
 * @param binary $exclusive
 * @return array $publishers
 */
function publishers( $exclusive = 0 ) {
    return $this->members( 8, $exclusive );
}

/**
 * Return a list of the drafters (and above) for this ACL. If the
 * $exclusive argument is set to 1, only the drafters, (ie, nobody
 * above) are returned.
 */
function drafters( $exclusive = 0 ) {
    return $this->members( 5, $exclusive );
}

/**
 * Return a list of the unprivileged members of the ACL. If the
 * $exclusive argument is set to 1, only the unprivileged, (ie, nobody
 * above) are returned.
 */
function unwashed( $exclusive = 0 ) {
    return $this->members( 0, $exclusive );
}

/**
 * Returns a list of members of all ACLs, with a number of optional
 * qualifiers. The members object method is just a special case of
 * this.
 *
 * @param int $karma The karma level that you want to know about
 * @param binary $exclusive 1 for just this karma, 0 for this level and
 * above
 * @param acl $acl Optionally, limit to just one ACL
 * @return array $members person ID numbers (NOT objects)
 */
function all_members( $karma = 0, $exclusive = 0, $acl = '' ) {
    // Limit to a particular karma
    if ($karma or $exclusive) {
        if ($exclusive) {
            $andkarma = " AND karma = $karma ";
        } else {
            $andkarma = " AND karma >= $karma ";
        }
    } else {
        $andkarma = '';
    }

    if ( $acl ) {
        $q = "SELECT userid FROM acl
            WHERE objecttype = '%s' and objectID = %s
            $andkarma";
        $q_args = array( $acl->name, $acl->id );
    } else {
        $andkarma = $andkarma ? 
            preg_replace('/^ AND/', ' WHERE', $andkarma) : '';
        $q = "SELECT DISTINCT userid 
            FROM acl $andkarma";
        $q_args = array();
    }

    $res = DB::get_results( $q, $q_args );
    if ($res) {
        foreach ($res as $row) {
            $members[] = $row->userid;
        }
        return $members;
    } else {
        return array();
    }
}

/**
 * Returns the user's karma with respect to the ACL, if any
 *
 * @param User $user
 * @return int karma
 */
function karma( $user ) {
    $row = DB::get_row("SELECT karma FROM acl
        WHERE objecttype = ?
        AND   objectID   = ?
        AND   userid     = ?",
        array( $this->name, $this->id, $user->id ) );
    return is_object( $row ) ? $row->karma : 0;
}

} // End class acl

// vim: filetype=php
?>

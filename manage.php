<?php

// management interface for SU

$dir = getcwd();
chdir('/mydisks/a/users/boincadm/projects/test2/html/user');
require_once("../inc/util.inc");
chdir($dir);

require_once("su_db.inc");

function show_projects() {
    page_head('Projects');
    $projects = SUproject::enum();
    if ($projects) {
        start_table('table-striped');
        row_heading_array(array(
            'Name<br><small>click for details</small>',
            'URL',
            'created',
            'allocation'
        ));
        foreach ($projects as $p) {
            table_row(
                '<a href="manage.php?action=show_project&id='.$p->id.'">'.$p->name.'</a>',
                $p->url,
                date_str($p->create_time),
                $p->allocation
            );
        }
        end_table();
    } else {
        echo 'No projects yet';
    }
    echo '<p><a class="btn btn-default" href="manage.php?action=add_project_form">Add project</a>
    ';
    echo "<p></p>";
    page_tail();
}

function array_to_string($arr) {
    $x = false;
    foreach ($arr as $wd) {
        if ($x) {
            $x .= ", $wd";
        } else {
            $x = $wd;
        }
    }
    return $x;
}

function su_show_project() {
    $id = get_int('id');
    $project = SUProject::lookup_id($id);
    page_head($project->name);
    start_table();
    row2("name", $project->name);
    row2("URL", $project->url);
    row2("Created", date_str($project->create_time));
    row2("Allocation", $project->allocation);
    $pks = SUProjectKeyword::enum("project_id=$project->id");
    $sci = array();
    $loc = array();
    if ($pks) {
        foreach ($pks as $pk) {
            $kwd = SUKeyword::lookup_id($pk->keyword_id);
            if ($kwd->category == SCIENCE) {
                $sci[] = $kwd->word;
            }
            if ($kwd->category == LOCATION) {
                $loc[] = $kwd->word;
            }
        }
    } else {
        $x = "None";
    }
    row2("Science keywords", array_to_string($sci));
    row2("Location keywords", array_to_string($loc));
    end_table();
    echo '<a class="btn btn-success" href="manage.php?action=edit_project_form&id='.$project->id.'">Edit</a>
    ';
    page_tail();
}

// return list of all keywords of given category
// List items are array (id, word)
//
function keyword_subset($keywords, $category) {
    $x = array();
    foreach ($keywords as $k) {
        if ($k->category != $category) continue;
        $x[] = array($k->id, $k->word);
    }
    return $x;
}

// given keyword list (of the form above),
// return boolean array of ones a project has
//
function keyword_flags($keywords, $category, $project_id) {
    $pks = SUProjectKeyword::enum();
    $pwords = array();
    foreach ($pks as $pk) {
        // could do this more efficiently with a join
        //
        $kw = SUKeyword::lookup_id($pk->keyword_id);
        $pwords[] = $kw->id;
    }

    $flags = array();
    foreach ($keywords as $k) {
        $flags[] = in_array($k[0], $pwords);
    }
    return $flags;
}

function add_project_form() {
    page_head("Add project");
    form_start('manage.php');
    form_input_hidden('action', 'add_project_action');
    form_input_text('URL', 'url');
    form_input_text('Name', 'name');
    form_input_text('Allocation', 'alloc', '', 'number');
    $keywds = SUKeyword::enum();
    $sci_keywds = keyword_subset($keywds, SCIENCE);
    $loc_keywds = keyword_subset($keywds, LOCATION);
    if (count($sci_keywds)>0) {
        form_select_multiple(
            'Science keywords<br><small>use ctrl to select multiple</small>',
            'sci_keywds',
            $sci_keywds,
            null
        );
    }
    if (count($loc_keywds)>0) {
        form_select_multiple(
            'Location keywords<br><small>use ctrl to select multiple</small>',
            'loc_keywds',
            $loc_keywds,
            null
        );
    }
    form_input_text(
        'New science keywords<br><small>comma-separated</small>',
        'new_sci_keywds'
    );
    form_input_text(
        'New location keywords<br><small>comma-separated</small>',
        'new_loc_keywds'
    );
    form_submit('OK');
    form_end();
    page_tail();
}

function edit_project_form() {
    $id = get_int('id');
    $p = SUProject::lookup_id($id);
    page_head("Edit project");
    form_start('manage.php');
    form_input_hidden('action', 'edit_project_action');
    form_input_hidden('id', $id);
    form_input_text('URL', 'url', $p->url, 'text', 'disabled');
    form_input_text('Name', 'name', $p->name);
    form_input_text('Allocation', 'alloc', $p->allocation, 'number');
    $keywds = SUKeyword::enum();
    $sci_keywds = keyword_subset($keywds, SCIENCE);
    $sci_flags = keyword_flags($sci_keywds, SCIENCE, $p->id);
    $loc_keywds = keyword_subset($keywds, LOCATION);
    $loc_flags = keyword_flags($loc_keywds, LOCATION, $p->id);
    form_select_multiple(
        'Science keywords<br><small>use ctrl to select multiple</small>',
        'sci_keywds',
        $sci_keywds,
        $sci_flags
    );
    form_select_multiple(
        'Location keywords<br><small>use ctrl to select multiple</small>',
        'loc_keywds',
        $loc_keywds,
        $loc_flags
    );
    form_input_text(
        'New science keywords<br><small>comma-separated</small>',
        'new_sci_keywds'
    );
    form_input_text(
        'New location keywords<br><small>comma-separated</small>',
        'new_loc_keywds'
    );
    form_submit('OK');
    form_end();
    page_tail();
}

function add_keywords($str, $project_id, $category) {
    $x = explode(',', $str);
    $n = 0;
    foreach ($x as $w) {
        $w = trim($w);
        if ($w == '') continue;
        $kw_id = SUKeyword::insert("(word, category) values ('$w', $category)");
        SUProjectKeyword::insert("(project_id, keyword_id) values ($project_id, $kw_id)");
        $n++;
    }
    return $n;
}

function add_project_action() {
    $url = get_str('url');
    $name = get_str('name');
    $alloc = get_str('alloc');
    $now = time();
    $project_id = SUProject::insert(
        "(url, name, create_time, allocation) values ('$url', '$name', $now, $alloc)"
    );
    if (!$project_id) {
        error_page("insert failed");
    }
    $nsci = add_keywords(get_str('new_sci_keywds'), $project_id, SCIENCE);
    $nloc = add_keywords(get_str('new_loc_keywds'), $project_id, LOCATION);
    page_head("Project added");
    echo '
        Added project.
    ';
    if ($nsci) {
        echo "<p>Added $nsci science keywords.\n";
    }
    if ($nloc) {
        echo "<p>Added $nloc location keywords.\n";
    }
    echo '
        <p>
        <a href="manage.php">Return to management page</a>
    ';
    page_tail();

}

// given a list of keyword IDs,
// add or remove project/keyword associations
//
function update_keywords($p, $new_kw_ids, $category) {
    // get list of associations
    //
    $old_kw_assocs = SUProjectKeyword::enum("project_id=$p->id");

    // get corresponding list of keyword objects
    //
    $old_kws = array_map(
        function($x){return SUKeyword::lookup_id($x->keyword_id);},
        $old_kw_assocs
    );

    // filter by category
    //
    $old_kws = array_filter($old_kws,
        function($x) use ($category) {return $x->category == $category;}
    );

    // convert to list of IDs
    //
    $old_kw_ids = array_map(
        function($x) {return $x->id;},
        $old_kws
    );

    // remove old ones not in new list
    //
    foreach ($old_kw_ids as $id) {
        if (!in_array($id, $new_kw_ids)) {
            SUProjectKeyword::delete("project_id=$p->id and keyword_id=$id");
        }
    }

    // add new ones
    //
    foreach ($new_kw_ids as $id) {
        if (!in_array($id, $old_kw_ids)) {
            SUProjectKeyword::insert(
                "(project_id, keyword_id) values ($p->id, $id)"
            );
        }
    }
}

function edit_project_action() {
    $id = get_int('id');
    $name = get_str('name');
    $alloc = get_str('alloc');
    $p = SUProject::lookup_id($id);
    if (!$p) {
        error_page("no such project");
    }
    $p->update("name='$name', allocation=$alloc");

    // add or remove existing keywords
    //
    update_keywords($p, get_array('sci_keywds'), SCIENCE);
    update_keywords($p, get_array('loc_keywds'), LOCATION);

    // add new keywords
    //
    $nsci = add_keywords(get_str('new_sci_keywds'), $p->id, SCIENCE);
    $nloc = add_keywords(get_str('new_loc_keywds'), $p->id, LOCATION);

    page_head("Project updated");
    echo '
        <p>
        <a href="manage.php">Return to management page</a>
    ';
    page_tail();
}

$action = get_str('action', true);

switch($action) {
case 'show_projects':
case null:
    show_projects();
    break;
case 'show_project':
    su_show_project();
    break;
case 'add_project_form':
    add_project_form();
    break;
case 'add_project_action':
    add_project_action();
    break;
case 'edit_project_form':
    edit_project_form();
    break;
case 'edit_project_action':
    edit_project_action();
    break;
default:
    error_page("no such action $action");
}

?>
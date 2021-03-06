<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2017 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// user home page

require_once("../inc/util.inc");
require_once("../inc/su.inc");

function main() {
    $user = get_logged_in_user();
    page_head("Account");
    echo '
        <p>
        <p><a href="user_history.php">Accounting history</a>
        <p><a href="user_hosts.php">Computers</a>
        <p><a href="user_projects.php">Projects</a>
    ';
    prefs_show($user);
    show_problem_projects($user);
    page_tail();
}

main();

?>

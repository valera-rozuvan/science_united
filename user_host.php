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

require_once("../inc/util.inc");
require_once("../inc/su.inc");

$user = get_logged_in_user();
$host_id = get_int('host_id');
$host = BoincHost::lookup_id($host_id);
if ($host->userid != $user->id) {
    error_page("not your host");
}
page_head("Computer details");
show_host_detail($host, $user, true);
echo "<h3>Projects</h3>\n";
show_host_projects($host);
page_tail();
?>

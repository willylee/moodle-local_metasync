<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_metasync
 * @copyright  2014 Willy Lee (wlee@carleton.edu)
 * @copyright  2014 Paul Holden (pholden@greenhead.ac.uk)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/grouplib.php');
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Get a list of parent courses for a given course ID
 *
 * @param int|null $courseid or null for all parents
 * @return array of course IDs
 */
function local_metasync_parent_courses($courseid = null) {
    global $DB;

    $conditions = array('enrol' => 'meta', 'status' => ENROL_INSTANCE_ENABLED);
    if ($courseid !== null) {
        $conditions['customint1'] = $courseid;
    }

    return $DB->get_records_menu('enrol', $conditions, 'sortorder', 'id, courseid');
}

/**
 * Get a list of all child courses for a given course ID
 *
 * @param int $courseid
 * @return array of course IDs
 */
function local_metasync_child_courses($courseid) {
    global $DB;

    return $DB->get_records_menu('enrol', array('enrol' => 'meta', 'courseid' => $courseid, 'status' => ENROL_INSTANCE_ENABLED), 'sortorder', 'id, customint1');
}

/**
 * Run synchronization process
 *
 * @param progress_trace $trace
 * @return void
 */
function local_metasync_sync(progress_trace $trace) {
    global $DB;

    $courseids = local_metasync_parent_courses();
    foreach (array_unique($courseids) as $courseid) {
        $parent = get_course($courseid);

        $trace->output($parent->fullname, 1);

        $children = local_metasync_child_courses($parent->id);
        foreach ($children as $childid) {
            $child = get_course($childid);
            $trace->output($child->fullname, 2);

            if (! $metagroup = $DB->get_record('groups', array('courseid' => $parent->id, 'idnumber' => $child->id))) {
                $metagroup = new stdClass();
                $metagroup->courseid = $parent->id;
                $metagroup->idnumber = $child->id;
                $metagroup->name = $child->shortname;

                $metagroup->id = groups_create_group($metagroup, false, false);
            }

            $trace->output($metagroup->name, 3);
            $coursecontext = context_course::instance($childid);
            $allusers = get_enrolled_users($coursecontext);
            $activeusers = get_enrolled_users($coursecontext, '', 0, 'u.*', null, 0, 0, true);
            $suspendedusers = array_udiff($allusers, $activeusers, 'local_metasync_compare_user_objects');
            // We need to add active users to appropriate groups.
            foreach ($activeusers as $user) {
                groups_add_member($metagroup, $user->id, 'local_metasync', $courseid);
            }
            // We may need to remove suspended users from child course groups, but only if added by local_metasync.
            foreach ($suspendedusers as $user) {
                if ($metamember = $DB->get_record('groups_members', array('groupid' => $metagroup->id, 'userid' => $user->id, 'component' => 'local_metasync'))) {
                    groups_remove_member($metagroup, $user->id);
                }
            }
        }
    }
}

/**
 * Compare user objects
 *
 * @param object $obj1
 * @param object $obj2
 */
function local_metasync_compare_user_objects($obj1, $obj2) {
    return $obj1->id - $obj2->id;
}

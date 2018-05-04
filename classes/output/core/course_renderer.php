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

namespace theme_pteboost\output\core;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use completion_info;

class course_renderer extends \core_course_renderer {
    
    public function course_section_cm_list($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $DB, $USER;

        $output = '';
        $modinfo = get_fast_modinfo($course);
        if (is_object($section)) {
            $section = $modinfo->get_section_info($section->section);
        } else {
            $section = $modinfo->get_section_info($section);
        }
        $completioninfo = new completion_info($course);

        // check if we are currently in the process of moving a module with JavaScript disabled
        $ismoving = $this->page->user_is_editing() && ismoving($course->id);
        if ($ismoving) {
            $movingpix = new pix_icon('movehere', get_string('movehere'), 'moodle', array('class' => 'movetarget'));
            $strmovefull = strip_tags(get_string("movefull", "", "'$USER->activitycopyname'"));
        }

        // Get the list of modules visible to user (excluding the module being moved if there is one)
        $moduleshtml = array();
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $modnumber) {
                $mod = $modinfo->cms[$modnumber];

                if ($ismoving and $mod->id == $USER->activitycopy) {
                    // do not display moving mod
                    continue;
                }

                if ($modulehtml = $this->course_section_cm_list_item($course,
                        $completioninfo, $mod, $sectionreturn, $displayoptions)) {
                    $moduleshtml[$modnumber] = $modulehtml;
                }
            }
        }

        $sectionoutput = '';
        if (!empty($moduleshtml) || $ismoving) {
            foreach ($moduleshtml as $modnumber => $modulehtml) {
                if ($ismoving) {
                    $movingurl = new moodle_url('/course/mod.php', array('moveto' => $modnumber, 'sesskey' => sesskey()));
                    $sectionoutput .= html_writer::tag('li',
                            html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                            array('class' => 'movehere'));
                }

                $sectionoutput .= $modulehtml;
            }

            if ($ismoving) {
                $movingurl = new moodle_url('/course/mod.php', array('movetosection' => $section->id, 'sesskey' => sesskey()));
                $sectionoutput .= html_writer::tag('li',
                        html_writer::link($movingurl, $this->output->render($movingpix), array('title' => $strmovefull)),
                        array('class' => 'movehere'));
            }
        }

        // Always output the section module list.

        if ($this->page->user_is_editing() || $course->id == SITEID){
            $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));
        }else{
            if($course->format == "pteweeks"){
                $sequences = explode(",", $section->sequence);
                $options[] = array();
                foreach ($sequences as $key => $sequence){
                    $sql = 'SELECT 
                                CM.instance AS "activityinstance",
                                M.name AS "activitytype"
                            FROM 
                                    {course_modules} AS CM 
                            JOIN
                                    {modules} AS M ON CM.module = M.id 
                            WHERE 
                                    CM.id = :cmid';
                    $record = $DB->get_record_sql($sql,array('cmid'=>$sequence));
                    $options[$record->activitytype."_".$sequence] = $DB->get_field($record->activitytype,"name",array('id'=>$record->activityinstance));                
                }
                $output .= html_writer::select($options, "pteactivity", $selected = '',$nothing = array('' => 'choosedots'),array('class' => 'pteactivity'));
            }else{
                $output .= html_writer::tag('ul', $sectionoutput, array('class' => 'section img-text'));
            }
        }
        return $output;
    }    
}

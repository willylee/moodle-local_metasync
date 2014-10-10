Moodle Meta-course Group Synchronization
=========================================

Metasync creates and maintains groups in metacourses that reflect the enrollment of the linked courses.

Metasync is based on local_metagroup by Paul Holden https://github.com/paulholden/moodle-local_metagroups

Requirements
------------
- Moodle 2.6 (build 2013111800 or later)
- Meta-course enrolment (build 2013110500 or later)

Installation
------------
Copy the metasync folder into your Moodle /local directory and visit your Admin Notification page to complete the installation.

Usage
-----
After installation you may need to synchronize existing meta-course groups, to do this run the cli/sync.php script (use the --help
switch for further instructions on script usage).

Any future amendments to enrollments in 'child' courses will be reflected in 'parent' course groups.

Author
------
Willy Lee (wlee@carleton.edu)

Based on local_metagroups by
Paul Holden (pholden@greenhead.ac.uk)

Changes
-------
Release 1.0 (build 2014)
- First release.

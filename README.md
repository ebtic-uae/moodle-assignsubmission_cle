# moodle-assignsubmission_cle
Moodle registered plugin CLE
============================

The Collaborative Learning Environment (CLE) is an assignment plugin for Moodle that allows groups to work in real-time on assignments and provides insight to teachers into individual student contributions and group collaboration via usage statistics.

CLE assignment submission plugin for Moodle 2.8.
- https://github.com/ebtic-uae/moodle-assignsubmission_cle

CLE installation
============================
This document describes how CLE can be installed in Moodle

Moodle CLE Plugin Installation
--------------------------------

Download moodle-assignsubmission_cle.zip from GitHub at https://github.com/ebtic-uae/moodle-assignsubmission_cle
Log into Moodle as Admin user, go to ADMINISTRATION->Site administration->Plugins->Install plugins
Choose "Assignment/Submission plugin(assignsubmission)" as "Plugin type"
Upload "CLE.zip"
Tick "Acknowledgement"
After CLE plugin was installed, upgrade Moodle database
Set up CLE settings:
Etherpad server location, Etherpad API key
Host
MySQL user for etherpad, Password, Database

Etherpad Installation
--------------------------------

For CLE to work, etherpad needs to be installed.
Go to http://www.etherpad.org and download etherpad. As etherpad is written in javascript, you will have to have Node (https://nodejs.org ) installed.
If you want to use the statistics feature of CLE, etherpad needs to be setup to use MySQL (please refer to the installation documents of ehterpad)
Once etherpad is installed, take note of * the API key (you can find this in the setting.json file) * the URL where etherpad is installed
Also in the settings.json, make sure to enable admin access, as you need this in order to install etherpad plugins
Once etherpad is up and running, go to the plugins page (etherpad-url/admin/plugins) and install
copy_paste_images (this allows to paste pictures into the assignments
disable_change_author_name (this ensures that students keep the name they inherit from Moodle
webrtc (if you want to have audio/video support).
headings (so that students can structure their work better).

auth_session Installation
--------------------------------

*To handle etherpad and moodle in the different server, auth_session needs to be installed. *Go to your etherpad admin portal, for example http://cle.ebtic.org:9001/admin/ *In the Plugin manager, find auth_session plugin in the available plugins, then install it

FAQ
============================

Q - How about security?
A - Be aware that in case of you using SSL, some browsers take issue if an iframe from a SSL-enabled website points to a non-encrypted site. In other words, if your moodle installation is accessible through SSL, then etherpad should also be SSL enabled.

Q - how about etherpad living on a different server?

A - You can have etherpad live on a different machine - however, due to authentication depending on cookies that are written across moodle and etherpad, the machines either have to live under the same domain (different subnets), or you have to use a trick (see Fun with pad.php)

Q - How safe are the chats from teachers?

A - as it is currently implemented, teachers cannot see the text chat. However, if you enable the webrtc plugin in etherpad, they might be able to engage in video chat. This needs to be further investigated

Q - Can I write parts of the text in word?

A - Etherpad allows in principle to import, however we do discourage that (or suggest to even disable the feature alltogether), as it deletes all propr text, and it does take away the ability of the teacher to understand which student wrote what.

Q - Is the statistic that the teacher gets fool proof?

A - The statistic is based on interactions that happen on the assignment tool, within etherpad. Therefore, if two students decide to work together by sitting next to each other, and one of them is typing, then the system will only "see" the work of the typing student. The teacher needs to use common sense and not forget that machines are not infailable.

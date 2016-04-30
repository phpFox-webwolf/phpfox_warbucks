# phpfox_warbucks
Stand-alone program to delete orphaned files and convert photo attachments into regular attachments
Warning:  This program will delete files from the file folder in your phpfox installation.  It is supposed to only delete orphaned files, but it could delete others.  Make sure you have a full backup of your website before using this program.

It will also make changes to your database, if you select "Fix", that will make photo attachments into regular attachments.  For this reason, it would be wise to have a database backup before using this option.

To install, move the warbucks directory to your phpfox's root directory.  Run the program by pointing your browser at http://yourdomain.ext/warbucks.

DO NOT leave this directory on your site.  Delete it when you are finished.  It is not secure and could be used for mischief.

If you wish to convert photo attachments to regular attachments, click on the "Fix" button.  This will work without further dialog and report it's results.

Click on the "Show button to see a list of orphaned files.  If any orphaned files are found, the program will give you the option to delete them.  By clicking "Delete" and confirming, the displayed files will be deleted.

This program is not designed to work with sites using CDN.

This program works by, for each module, storing all filenames in the file/pic/module folder in an array.  Then, the array elements are unset for each database item found for that filename.  After the process is complete, all that should be left are the orphaned files.  This information is displayed in the "Show" mode and deleted if you select "Delete".

The "Fix" option will scan through the text in the modules that use attachments to parse out the filenames for inline attachments.  The resulting array will be compared to the attachment table.  If the item_id is 0 (indicating a photo attachment), it will be set to the proper item_id.  Then, the total attachments for that item in the module table will be incremented.  This will essentially turn a photo attachment into a regular attachment.

I have tested this as well as I can, but I am sure that I have not covered all possible problems that could occur, so use it at your own risk.

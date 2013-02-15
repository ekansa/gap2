gap2
====

Geographic Annotation Platform

Intro
-----
This is a "work in progress", meaning that there's some progress, but it still largely does not work. :)
There's lots to do with this. It mainly does OK with interacting with the Edina/Unlock Geoparser API. It can
get results from the API, parse the result XML data and load those data into a MySQL database. Some of the 
GapVis functinality sorta works.


Requirements
------------
MySQL 5.0+, PHP 5.2+, Zend (up to 1.9), Apache, GapVis


Configuration
-------------
Edit the "/application/config_ini" file, add your local database info (name, username, password) and also
add user acount / password information for the Unlock/Edina geoparser. Save the file as "config.ini"


Additional Libraries / Code / Frameorks
---------------------------------------

(1) Zend
You'll need to add the Zend Framework (up to version 1.9) to the "/library/" directory as such:

/library/Zend/

Go here to get Zend:
http://framework.zend.com/downloads/latest

(2) GapVis
Right now, GapVis is the only thing with any polish. You'll need to add the GapVis javascript, 
css, and html to the "/public/gapvis" directory. When you add it, you need to change some settings
for your local host (especially the "API_ROOT"). 

Get GapVis here:
https://github.com/nrabinowitz/gapvis

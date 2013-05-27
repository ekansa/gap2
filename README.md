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

Also, the file "database-schema.txt" has a series of SQL queries needed to create the tables for the database.


Additional Libraries / Code / Frameworks
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


License and Liability Disclaimer:
---------------------------------------
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


Copyright, Eric C. Kansa (2013)
This code is licened under the GNU Lesser General Public License, version 3.0 (LGPL-3.0):
http://opensource.org/licenses/lgpl-3.0.html


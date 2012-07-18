var menu = '\
<a name="top"></a>\n\
<div id="menuContainer">\n\
<div id="menu">\n\
<table><tbody><tr>\n\
<td class="col1">\n\
    <ul class="menuList">\n\
        <li class="menuLabel"><span class="menuTitle">Installation</span>\n\
            <ul>\n\
                <li><a href="'+basepath+lang+'/installation/required.html">Pré-requis</a></li>\n\
                <li><a href="'+basepath+lang+'/installation/installation.html">Installation</a></li>\n\
                <li><a href="#">Configuration</a></li>\n\
                <li><a href="#">Mise à jour</a></li>\n\
            </ul>\n\
        </li>\n\
        <li class="menuLabel"><span class="menuTitle">Introduction</span>\n\
            <ul>\n\
                <li><a href="#">Premiers pas</a></li>\n\
                <li><a href="#">Modèle-Vue-Contrôleur</a></li>\n\
                <li><a href="'+basepath+lang+'/introduction/license.html">Licence</a></li>\n\
                <li><a href="'+basepath+lang+'/introduction/about.html">A propos</a></li>\n\
            </ul>\n\
        </li>\n\
    </ul>\n\
</td>\n\
<td class="col2">\n\
    <ul class="menuList">\n\
        <li class="menuLabel"><span class="menuTitle">Généralités</span>\n\
            <ul>\n\
                <li><a href="'+basepath+lang+'/general/structure.html">Structure / squelette    </a></li>\n\
                <li><a href="'+basepath+lang+'/general/functions.html">Fonctions procédurales</a></li>\n\
                <li><a href="'+basepath+lang+'/general/controllers.html">Contrôleurs</a></li>\n\
                <li><a href="#">URLS</a></li>\n\
                <li><a href="#">Scaffolding</a></li>\n\
                <li><a href="#">Surcharges</a></li>\n\
                <li><a href="#">Traduction</a></li>\n\
                <li><a href="#">Conventions</a></li>\n\
                <li><a href="#">Documentation</a></li>\n\
            </ul>\n\
        </li>\n\
    </ul>\n\
</td>\n\
<td class="col3">\n\
    <ul class="menuList">\n\
        <li class="menuLabel"><span class="menuTitle">Les classes</span>\n\
            <ul>\n\
                <li><a href="#">Cache</a></li>\n\
                <li><a href="#">Cli</a></li>\n\
                <li><a href="#">Config</a></li>\n\
                <li><a href="#">Cookie</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/cron.html">Cron</a></li>\n\
                <li><a href="#">Controller</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/date.html">Date</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/db.html">Db</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/email.html">Email</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/error.html">Error</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/file.html">File</a></li>\n\
                <li><a href="#">Form</a></li>\n\
                <li><a href="#">Generator</a></li>\n\
                <li><a href="#">Html</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/i18n.html">I18n</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/image.html">Image</a></li>\n\
                <li><a href="#">Json</a></li>\n\
                <li><a href="#">Log</a></li>\n\
                <li><a href="#">Request</a></li>\n\
                <li><a href="#">Route</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/search.html">Search</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/security.html">Security</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/session.html">Session</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/string.html">String</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/template.html">Template</a></li>\n\
                <li><a href="#">Upload</a></li>\n\
                <li><a href="#">Useragent</a></li>\n\
                <li><a href="'+basepath+lang+'/classes/uri.html">Uri</a></li>\n\
                <li><a href="#">Xml</a></li>\n\
            </ul>\n\
        </li>\n\
    </ul>\n\
</td>\n\
<td class="col4">\n\
    <ul class="menuList">\n\
        <li class="menuLabel"><span class="menuTitle">Tutoriaux</span>\n\
            <ul>\n\
                <li><a href="#">Authentifier un utilisateur</a></li>\n\
                <li><a href="#">Créer une page</a></li>\n\
                <li><a href="#">Créer un formulaire</a></li>\n\
            </ul>\n\
        </li>\n\
    </ul>\n\
</td>\n\
</tr>\n\
</tbody>\n\
</table>\n\
</div>\n\
<div id="menuButton">Menu</div>\n\
</div>';

var header = '\
<div id="header">\n\
    <h4>Guide de l&apos;utilisateur Version 0.1</h4>\n\
    <div id="breadCrumb">\n\
        <a href="#" class="homeLink">Accueil</a>\n\
        <a href="#" class="guideLink">Guide FR</a>\n\
    </div>\n\
</div>';

var footer = '\
<div id="footer">\n\
<a href="#top">Haut de page</a>\n\
&nbsp;&nbsp;&nbsp;&bull;&nbsp;&nbsp;\n\
<a href="#" class="homeLink">Accueil</a>\n\
&nbsp;&nbsp;&nbsp;&bull;&nbsp;&nbsp;\n\
<a href="#" class="guideLink">Accueil guide FR</a>\n\
<br />\n\
<a href="http://www.php-oxygen.com">PHP Oxygen</a>\n\
&nbsp;&nbsp;&nbsp;&bull;&nbsp;&nbsp;\n\
<span class="copyright">Copyright &copy; 2011</span>\n\
&nbsp;&nbsp;&nbsp;&bull;&nbsp;&nbsp;\n\
<span class="author">Sébastien HEYD</span>\n\
</div>';
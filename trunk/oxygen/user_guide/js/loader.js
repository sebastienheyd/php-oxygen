var myScripts = new Array(
    basepath+'js/jquery.min.js', 
    basepath+'js/shCore.js', 
    basepath+'js/shBrushApache.js', 
    basepath+'js/shBrushBash.js', 
    basepath+'js/shBrushPhp.js', 
    basepath+'js/shBrushJscript.js', 
    basepath+'js/shBrushCss.js', 
    basepath+'js/shBrushSql.js', 
    basepath+'js/shBrushXml.js', 
    basepath+'js/shBrushSmarty.js', 
    basepath + lang+'/variables.js', 
    basepath+'js/user_guide.js');
 
if( document.createElement && document.childNodes ) {
    for(i = 0; i < myScripts.length; i++) 
    {
        script = '<script type="text/javascript" src="'+myScripts[i]+'" ></script>';
        document.write(script); 
    }
}
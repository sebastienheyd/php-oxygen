$(document).ready(function(){
   display();
   menuSlide();
   breadCrumbItems();
   variablesDisplay();
   parseCode();
   SyntaxHighlighter.all();
});

function parseCode()
{    
    $.each($('php'), function(){
        $(this).replaceWith('<pre class="brush:php;">'+$(this).text()+'</pre>');
    });
    $.each($('js, javascript'), function(){
        $(this).replaceWith('<pre class="brush:js;">'+$(this).text()+'</pre>');
    });
    $.each($('css'), function(){
        $(this).replaceWith('<pre class="brush:css;">'+$(this).text()+'</pre>');
    });
    $.each($('xml'), function(){
        $(this).replaceWith('<pre class="brush:xml;">'+$(this).text()+'</pre>');
    });
    $.each($('sql'), function(){
        $(this).replaceWith('<pre class="brush:sql;">'+$(this).text()+'</pre>');
    });
}

function display()
{
    $('body').wrapInner('<div id="content" />');
    $('body').prepend(header);
    $('body').prepend(menu);
    $('body').append(footer);
}

function breadCrumbItems()
{
    if(basepath == '../')
    {
        guideLink = $('#breadCrumb a.guideLink');
        label = guideLink.text();
        guideLink.remove();
        $('#breadCrumb').append('<span>'+label+'</span>');
    }
    else
    {
        if(typeof(bc) != 'undefined')
        {
            $.each(bc, function(k, v){
                $('#breadCrumb').append('<a href="'+v+'">'+k+'</a>');
            });
        }
        $('#breadCrumb').append('<span>'+$('h1').text()+'</span>');
    }
}

function variablesDisplay()
{
    document.title = $('h1').text()+' : PHP Oxygen';
    
    var base = (basepath == 'null') ? '' : basepath;
    $('a.homeLink').attr('href', base + 'index.html');
    $('a.guideLink').attr('href', base + lang + '/index.html'); 
    
    separators($('#breadCrumb a, #breadCrumb span'));
}

function menuSlide()
{
    $('#menu a[href="#"]').parent('li').addClass('disabled');
    
    height = $('#menuContainer').height()-7 ;
    $('#menuContainer').css('margin-top', -height+'px');

    $('#menuButton').click(function(){
        if($('#menuContainer').css('margin-top') == '0px')
        {
            $('#menuContainer').animate({marginTop:-height+'px'}, 350, 'swing');
        }   
        else
        {
            $('#menuContainer').animate({marginTop:'0px'}, 350, 'swing');                
        }    
    });    
}

function separators(arr)
{
    $.each(arr, function(key, data){
        if(key >= 1)
        {
            $(data).before('&nbsp;>&nbsp;');            
        }
    });
}
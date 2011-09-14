<?php
class f_generator_Locales
{
    public static function parse()
    {
        $files = f_utils_File::find(PROJECT_DIR, '*.html');

        $translations = array();

        if(count($files) > 0)
        {
            foreach($files as $file)
            {
                $fContent = file_get_contents($file);
                preg_match_all('/\{t.*\}(.*)\{\/t\}*/iU', $fContent, $matches);   
                $translations = array_unique(array_merge($translations, $matches[1]));
            }
        }

        $files = f_utils_File::find(PROJECT_DIR, '*.php');

        if(count($files) > 0)
        {
            foreach($files as $file)
            {
                $fContent = file_get_contents($file);
                preg_match_all('/__\([\'"](.*)[\'"]+[\s,\)]/iU', $fContent, $matches);
                $translations = array_unique(array_merge($translations, $matches[1]));
            }
        }
    }
}
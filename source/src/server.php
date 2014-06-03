<?php
/**
 * server.php
 *
 * This script is called by the JavaScript front-end. It serves all of the
 * content as JSON objects (grabbed via AJAX calls), which are then jammed
 * into the correct HTML elements by the front end.
 *
 * @author Ian Adamson, Jenn Lemke, Everett Williams, Ken Olson
 */

require_once("settings.php");

/**
 * API
 *
 * Handles API interactions. Also containers a number of helper methods
 * used by other methods in the class.
 */
class API {

    /**
     * Look for image with alt=main image first, then default to first image.
     * getMainImage returns an HTML string with selected image.
     *
     * @param an &$html reference.
     * @return a string containing HTML
     **/
    public static function getMainImage(&$html) {
        $retImage = null;
        $numImage = 0;

        // Load HTML into DOM object
        $dom = new domDocument;
        $dom->loadHTML($html);
        $dom->preserveWhiteSpace = false;
        $dom->validateOnParse = true;

        // Get the image array from the HTML
        $images = $dom->getElementsByTagName('img');

        if ($images->length > 0) {
            
            // Set the default image to return
            $retImage = "<img id='main_image' alt='main image' src='".$images->item(0)->getAttribute('src')."'/>";

            // Search for the main image
            foreach ($images as $image) {
                if (strtolower($image->getAttribute('alt')) == "main image") {
                    $retImage = "<img id='main_image' alt='main image' src='".$image->getAttribute('src')."'/>";
                }
            }
            return $retImage;
        }
    }
    
    /**
     * Fix the images so that the URLs are absolute instead of relative.
     *
     * @param A string containing HTML code.
     * @return void
     */
    public static function fixImages(&$html) {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
           $image->setAttribute('src', ROOT_ADDRESS . $image->getAttribute('src'));
        }
        $html = $dom->saveHTML();
    }
    
    /**
     * Fix the character encoding issue.
     * 
     * @param A string containing HTML code.
     * @return void
     */
    public static function fixEncoding(&$html) {
        $replace = array(
            '&acirc;&#128;&#153;' => '’'
        );
        
        $html = str_replace(array_keys($replace), array_values($replace), $html);        
    }
    
    /**
     * Attempts to find cached content for the given query string, and returns
     * it if found.
     *
     * @param A query string.
     * @return A JSON string.
     */
    private static function getCache($api_call) {
        // TODO: Figure out how we want to cache stuff.
    }

    /**
     * Caches the given content using the API call as the key.
     *
     * @param A query string.
     * @param A JSON string.
     * @return void
     */
    private static function setCache($api_call, $content) {
        // TODO: Figure out how we want to cache stuff.
    }

    /**
     * Sorts an array by a specific key's values.
     * Credit to http://stackoverflow.com/users/831498/piraba for this function.
     *
     * @param The array
     * @param The key
     * @return The sorted array
     * @author http://stackoverflow.com/users/831498/piraba
     */
    private static function subval_sort($a,$subkey) {
        foreach($a as $k=>$v) {
            $b[$k] = strtolower($v[$subkey]);
        }
        try {
                asort($b);
            } catch (Exception $e) {
                echo 'Error! '.$b;
            }
        foreach($b as $key=>$val) {
            $c[] = $a[$key];
        }
        return $c;
    }

    /**
     * Make an API call to the wiki and return the result.
     *
     * @param A query string.
     * @return A JSON string.
     */
    private static function makeCall($api_call) {
        try {
            // Try to get the data via the API using cURL
            $url = API_ADDRESS.'?'.$api_call;
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_USERAGENT, 'Wiki_Kiosk/1.0');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec($ch);
            curl_close($ch);

            // Got the data; cache it using the API query string as the key.
            // But only if caching is turned on.
            //if(ALLOW_CACHING) API::setCache($api_call, $data);
            return $data;
        } catch(Exception $e) {
            // Uh oh! cURL failed or something. Get the cached copy of the data.
            //if(ALLOW_CACHING) return API::getCache($api_call);
            return 0;
        }
    }

    /**
     * Checks if a given page exists on the Wiki
     *
     * @param An article name.
     * @return A boolean value; true if the page exists, false otherwise.
     */
    private static function articleExists($article) {
        $article = str_replace(' ', '_', $article);
        $call = "action=query&prop=revisions&titles=".$article."&rvprop=content&format=json&rvparse=0"; 
        $result = json_decode(API::makeCall($call), true);
        
        if(array_key_exists(-1, $result['query']['pages'])) {
           return false;
        }
        return true;
    }

    /**
     * Get full project list.
     *
     * Output the complete list of projects as a JSON object; used
     * to populate the main project index.
     *
     * @param void
     * @return void
     */
    public static function getProjectList() {
        // Get the category listing via the API wrapper function, then decode the JSON and peel off some superfluous arrays.
        // Also, sort the list.
        $list = API::makeCall('format=json&action=query&cmtitle=Category:'.PROJECT_CATEGORY.'&list=categorymembers&cmlimit=500');
        $list = json_decode($list, true);
        $list = $list['query']['categorymembers'];

        // Clean titles for presentation
        foreach($list as &$item) {
            // Strip "CCAT" from titles if present
            if(substr($item['title'], 0, 5)=="CCAT ") {
                $item['title'] = substr($item['title'], 5);
            }
        }

        $list = API::subval_sort($list, 'title');

        // Thanks, Seseme Street.
        $alphabet = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');

        $return_string = '';

        // Loop through each letter of the alphabet (as defined above; if we need to add cyrillic characters or umlauts or something, this needs to be
        // modified).
        foreach($alphabet as $letter) {
            $printed_header = false;

            // Loop through each item on the list. If it starts with the letter we're on, print it out.
            foreach($list as $item) {
                if(strtolower(substr($item['title'], 0, 1)) == $letter && substr($item['title'], -3)!="/OM") {
                    // If this is the first item we're printing, print the section header first.
                    if(!$printed_header) {
                        $return_string .= '<span><h1>'.strtoupper($letter).'</h1></span>';
                        $printed_header = true;
                    }
                    $return_string .= '<a href="#" class="listitem" data-id="'.$item['pageid'].'"><div class="project_item"><h2>'.ucwords($item['title']).'</h2></div></a>';
                }
            }
        }

        return $return_string;
    }

    /**
     * Rebuild maintenance schedule.
     *
     * @param void
     * @return a string containing HTML
     */
    private static function buildMaintenanceSchedule() {
        // Get category listing
        $list = API::makeCall('format=json&action=query&cmtitle=Category:'.PROJECT_CATEGORY.'&list=categorymembers&cmlimit=500');
        $list = json_decode($list, true);
        $list = $list['query']['categorymembers'];

        // Remove any articles that aren't maintenance articles
        $temp = '';
        foreach($list as $key => $item) {
            if(substr($item['title'], -3)=="/OM") {
                //unset($list[$key]);
                $temp .= $item['pageid'].'|';
            }
        }
        $temp = rtrim($temp, '|');

        // Grab all of the maintenance pages with one API call
        $maintPage = API::makeCall('format=json&action=query&export&pageids='.$temp);
        $maintPage = json_decode($maintPage, true);
        $maintPage = new SimpleXMLElement($maintPage['query']['export']['*']);
        
        // Parse the pages to produce the maintenance schedule
        $maintSchedule = array();
        $activeCategory = 0;
        
        foreach($maintPage->page as $page) {
            // Get the location of the schedule section (and add 16 so we start at the end of the header)
            $scheduleSectionStart = strpos($page->revision->text, "=== Schedule ===")+16;
            
            // Get title of current article (sans CCAT prefix)
            $pageTitle = $page->title;
            if(substr($pageTitle, 0, 5)=="CCAT ") {
                $pageTitle = substr($pageTitle, 5, -3);
            }
            
            // if this evaluates to 0 (false) that means there's no schedule
            if($scheduleSectionStart-16) {
                $scheduleSectionEnd = strpos($page->revision->text, "==", $scheduleSectionStart);
                $scheduleSectionLength = $scheduleSectionEnd - $scheduleSectionStart;
                
                // Trim the text down to just the schedule section
                $scheduleSection = substr($page->revision->text, $scheduleSectionStart, $scheduleSectionLength);
                
                // Look at each string
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $scheduleSection) as $line){
                    if($line && $line[0] == ';') {
                        // Set maintenance schedule section
                        $activeCategory = trim(substr($line, 1));
                    }
                    if($line && $activeCategory && $line[0] == '*') {
                        // Add item to the current section
                        $maintSchedule[$activeCategory][] = '<span>('.$pageTitle.')</span> '.substr($line, 1);
                    }
                } 
                
                
            }
        }
        
        // Format the HTML output
        // TODO
        $returnValue = '';
        $keys = array(
            'Weekly',
            'Monthly',
            'months',
            'Yearly',
            'years',
            'Daily'
        );
        
        foreach($keys as $targetKey) {
            foreach($maintSchedule as $key=>$value) {
                if(substr($key, -strlen($targetKey)) === $targetKey) {
                    $returnValue .= '<h2>'.$key.'</h2>';
                    $returnValue .= '<ul>';
                    foreach($maintSchedule[$key] as $task) {
                        $returnValue .= '<li>'.$task.'</li>';
                    }
                    $returnValue .= '</ul>';
                    unset($maintSchedule[$key]);
                }
            }
        }
        
        foreach($maintSchedule as $key=>$value) {
            $returnValue .= '<h2>'.$key.'</h2>';
            $returnValue .= '<ul>';
            foreach($maintSchedule[$key] as $task) {
                $returnValue .= '<li>'.$task.'</li>';
            }
            $returnValue .= '</ul>';
        }
        
        // Return the maintenance schedule
        return $returnValue;
    }

    /**
     * Get the maintenance schedule overview.
     *
     * Output the maintenance schedule for a specific project as a JSON object.
     *
     * @param void
     * @return a string containing HTML
     */
    public static function getMaintenanceSchedule() {
        
        /**
         * Get data
         **/
        // TODO - Caching, maybe.
        // Load cache
        // Check cache age -- if too old:        
            $maintSched = API::buildMaintenanceSchedule();
            // Save cache: $maintSched and current datetime
        // If not too old:
            // Display cache
        
        /**
         * Build output
         **/
        $return = '<span><h1>Maintenance Schedule Overview</h1></span>'; // PLACEHOLDER
        $return .= '<div id="schedule">'.$maintSched.'</div>';
        
        return $return;
    }

    /**
     * Get a brief overview of a project.
     *
     * Ouput a summery overview of a project as a JSON object.
     *
     * @param A unique project identifier
     * @return a string containing HTML
     */
    public static function getBriefOverview($projectID) {
        // Make the API calls
        $article = API::makeCall('format=json&action=parse&prop=text&section=1&pageid='.$projectID);
        $article = json_decode($article, true);
 
        // Fix relative image URLs so that they're absolute instead.
        API::fixImages($article['parse']['text']['*']);
        API::fixEncoding($article['parse']['text']['*']);
  
        // If the article title starts with "CCAT ", strip that off.
        if(substr($article['parse']['title'], 0, 5)=="CCAT ") {
            $article['parse']['title_truncated'] = substr($article['parse']['title'], 5);
        } else {
            $article['parse']['title_truncated'] = $article['parse']['title'];
        }
        
        /*
        // Get the overview section
        $dom = new DOMDocument;
        $dom->validateOnParse = true;
        @$dom->loadHTML($article['parse']['text']['*']);
        $beforeNextHeader = True;
        $hTwo= $dom->getElementsByTagName('h2'); // Desired tag
        $overTitle = $hTwo->item(1)->nodeValue; 
        $tempVar = '';  
        
        foreach($dom->getElementsByTagName('h2') as $node) {
            $key = $dom->saveHtml($node);
            $matches[$key] = array();
    
            while(($node = $node->nextSibling) && $beforeNextHeader) {
                if($node->nodeName == 'h2'){
                    $beforeNextHeader = False;   
                }
                if($node->nodeName == 'p') {
                    $tempVar .= $dom->saveHtml($node);
                }
            }
        }
        */

        // Add back button (and init return string)
        $return_string = '<div class="button_container back"><a href="#" class="button_link" data-action="back" data-to="maintenance_overview"><div class="button_inner"><< Back</div></a></div>';

        // Print the title and content of the article from the API call
        $return_string .= '<h1 class="article_header">'.ucwords($article['parse']['title_truncated']).'</h1>';
        $return_string .= '<div class="article">'.$article['parse']['text']['*'].'</div>';
        
        // Full article button
        $return_string .= '<div class="button_container full_article"><a href="#" class="button_link" data-action="article" data-id="'.$projectID.'"><div class="button_inner">View Article</div></a></div>';
        
        // Maintenance button - only show if a /OM article exists
        if(API::articleExists($article['parse']['title'].'/OM')) {
            $temp = str_replace(' ', '_', $article['parse']['title'].'/OM');
            $return_string .= '<div class="button_container full_maintenance"><a href="#" class="button_link" data-action="maintenance"  data-title="'.$temp.'"><div class="button_inner">View Maintenance</div></a></div>';    
        } else {
            $return_string .= '<div class="button_container full_maintenance"><div class="button_inner">No Maintenance Article</div></div>';
        }

        return $return_string;
    }
 
    /**
     * Get the full maintenance page for an article.
     *
     * Output the maintenace page as a string of HTML
     *
     * @param a unique project identifier
     * @return a string containing HTML
     */
    public static function getMaintenancePage($projectID) {
        //Grabs entire article and parses it
        //TODO: Link with full_maintenence button and display on entire panel\
        $article = API::makeCall('format=json&action=parse&prop=text&page='.$projectID);
        $article = json_decode($article, true);
        
        // If the article title starts with "CCAT ", strip that off.
        if(substr($article['parse']['title'], 0, 5)=="CCAT ") {
            $article['parse']['title_truncated'] = substr($article['parse']['title'], 5);
        } else {
            $article['parse']['title_truncated'] = $article['parse']['title'];
        }
        
        // Fix relative image URLs so that they're absolute instead.
        API::fixImages($article['parse']['text']['*']);

        $return_string = '<div class="button_container back"><a href="#" class="button_link" data-action="back" data-to="split_panel"><div class="button_inner"><< Back</div></a></div>';
        
        //Print the title of the article from the API call
        $return_string .= '<span><h1>'.ucwords($article['parse']['title_truncated']).'</h1></span>';
        $return_string .= '<div class="article">'.$article['parse']['text']['*'].'</div>';   
        
        return $return_string;
    }

    /**
     * Get the complete article for a project
     *
     * Output the complete overview of a project as HTML.
     *
     * @param A unique project identifier
     * @return A string containing HTML
     */
    public static function getFullArticle($projectID) {
        //$return = {}; // DO STUFF HERE
        //Grabs entire article and parses it
        //TODO: Link with full_article button and display on entire panel
        $article = API::makeCall('format=json&action=parse&prop=text&pageid='.$projectID);
        $article = json_decode($article, true);
        
        // If the article title starts with "CCAT ", strip that off.
        if(substr($article['parse']['title'], 0, 5)=="CCAT ") {
            $article['parse']['title_truncated'] = substr($article['parse']['title'], 5);
        } else {
            $article['parse']['title_truncated'] = $article['parse']['title'];
        }

        // Fix relative image URLs so that they're absolute instead.
        API::fixImages($article['parse']['text']['*']);

        $return_string = '<div class="button_container back"><a href="#" class="button_link" data-action="back" data-to="split_panel"><div class="button_inner"><< Back</div></a></div>';
        
        //Print the title of the article from the API call
        $return_string .= '<span><h1>'.ucwords($article['parse']['title_truncated']).'</h1></span>';
        $return_string .= '<div class="article">'.$article['parse']['text']['*'].'</div>';   
        
        return $return_string;
    }

}


/*****************************************************************************
 ** Main code
 *****************************************************************************/

if(isset($_POST['action'])) $action = $_POST['action'];
if(isset($_POST['id'])) $projectID = $_POST['id'];
if(isset($_POST['title'])) $projectTitle = $_POST['title'];

// TODO: SANITIZE ID. Not necessary for $action because it's just going into a switch.
//       Probably just filter out non-alphanumeric chars (plus underscore/dash?)

switch($action) {
    case "index":
        echo API::getProjectList();
        break;
    case "fullmaintenence":
    //Fill the Full panel with the Full Maintenence Info
        echo API::getMaintenancePage($projectID);
        break;
    case "maintenance":
        echo API::getMaintenanceSchedule();
        break;
    case "briefoverview":
        echo API::getBriefOverview($projectID);
        break;
    case "fullarticle":
        echo API::getFullArticle($projectID);
        break;
}

/* EOF server.php */

<?php

    global $title;
    global $repos;
    $embed = false;
    $title  = "git";
    $repo_index = "index.aux";

    //$repos = file($repo_index);
    $repos = array(
        "/home/zack/scm/bartel.git",
        "/home/zack/scm/rpminfo.git",
        "/home/zack/scm/linux.git",
        "/home/zack/scm/cnas.git",
        "/home/zack/scm/cnas-sm.git",
        "/home/zack/scm/cnas-logos.git",
        "/home/zack/scm/cnas-release.git",
        "/home/zack/scm/cnas-aimsim.git",
        "/home/zack/scm/git-php.git",
        "/home/zack/scm/gobot.git",
    );

    if (isset($_GET['dl']))
        if ($_GET['dl'] == 'targz') 
            write_targz(get_repo_path($_GET['p']));
        else if ($_GET['dl'] == 'zip')
            write_zip(get_repo_path($_GET['p']));

    if (!$embed)
        html_header();

    html_breadcrumbs();

    if (isset($_GET['p']))  { 
        html_summary($_GET['p']);
        if ($_GET['a'] == "commitdiff")
            html_diff($_GET['p'], $_GET['h'], $_GET['hb']);
        else
            html_browse($_GET['p']);
    }
    else
        html_home();

    if (!$embed)
        html_footer();

    function html_summary($proj)    {
        $repo = get_repo_path($proj);
        html_desc($repo);
        if (!isset($_GET['t']) && !isset($_GET['b']))
            html_shortlog($repo, 6);
    }

    function html_browse($proj)   {

        if (isset($_GET['b']))
            html_blob($proj, $_GET['b']);
        else    {
            if (isset($_GET['t']))
                $tree = $_GET['t'];
            else 
                $tree = "HEAD";
        }

        html_tree($proj, $tree); 
    }

    function html_blob($proj, $blob)    {
        $repo = get_repo_path($proj);
        $out = array();
        exec("GIT_DIR=$repo git-cat-file blob $blob", &$out);
        echo highlight_code(implode("\n",$out));
            //highlight_string(implode("\n",$out));
    }

    function html_diff($proj, $commit, $parent)    {
        $repo = get_repo_path($proj);
        $out = array();
        exec("GIT_DIR=$repo git-diff $parent $commit", &$out);
        echo highlight_code(implode("\n",$out));
    }

    function html_tree($proj, $tree)   {
        $t = git_ls_tree(get_repo_path($proj), $tree);
        echo "<table>\n";
        foreach ($t as $obj)    {
            $perm = perm_string($obj['perm']);

            if ($obj['type'] == 'tree')
                $objlink = "<a href=\"{$_SERVER['SCRIPT_NAME']}?p=$proj&t={$obj['hash']}\">{$obj['file']}</a>\n";
            else if ($obj['type'] == 'blob')
                $objlink = "<a href=\"{$_SERVER['SCRIPT_NAME']}?p=$proj&b={$obj['hash']}\">{$obj['file']}</a>\n";

            echo "<tr><td>$perm</td><td>$objlink</td></tr>\n";
        }
        echo "</table>\n";
    }

    function html_shortlog($repo, $count)   {
        echo "<table>\n";
        $c = git_commit($repo, "HEAD");
        for ($i = 0; $i < $count && $c; $i++)  {
            $date = date("D m/d/Y G:i", $c['date']);
            $cid = $c['commit_id'];
            $pid = $c['parent'];
            $diff = "<a href=\"{$_SERVER['SCRIPT_NAME']}?p={$_GET['p']}&a=commitdiff&h=$cid&hb=$pid\">commitdiff</a>";
            echo "<tr><td>$date</td><td>{$c['author']}</td><td>{$c['message']}</td><td>$diff</td></tr>\n"; 
            $c = git_commit($repo, $c["parent"]);
        }
        echo "</table>\n";
    }

    function html_desc($repo)    {
        
        $desc = file_get_contents("$repo/description"); 
        $owner = get_file_owner($repo);
        $last =  get_last($repo);

        echo "<table>\n";
        echo "<tr><td>description</td><td>$desc</td></tr>\n";
        echo "<tr><td>owner</td><td>$owner</td></tr>\n";
        echo "<tr><td>last change</td><td>$last</td></tr>\n";
        echo "</table>\n";
    }

    function html_home()    {

        global $repos; 
        echo "<table>\n";
        echo "<tr><th>Project</th><th>Description</th><th>Owner</th><th>Last Changed</th><th>Download</th></tr>\n";
        foreach ($repos as $repo)   {
            $desc = short_desc(file_get_contents("$repo/description")); 
            $owner = get_file_owner($repo);
            $last =  get_last($repo);
            $proj = get_project_link($repo);
            $dlt = get_project_link($repo, "targz");
            $dlz = get_project_link($repo, "zip");
            echo "<tr><td>$proj</td><td>$desc</td><td>$owner</td><td>$last</td><td>$dlt | $dlz</td></tr>\n";
        }
        echo "</table>";
    }

    function html_header()  {
        global $title;
        
        echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
        echo "<head>\n";
        echo "\t<title>$title</title>\n";
        echo "\t<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\"/>\n";
        echo "</head>\n";
        echo "<body>\n";
        
    }

    function html_footer()  {
        echo "</body>\n";
        echo "</html>\n";
    }

    function git_tree_head($gitdir) {
        return git_tree($gitdir, "HEAD");
    }

    function git_tree($gitdir, $tree) {

        $out = array();
        $command = "GIT_DIR=$gitdir git-ls-tree --name-only $tree";
        exec($command, &$out);
        var_dump($out);
    }

    function get_git($repo) {

        if (file_exists("$repo/.git"))
            $gitdir = "$repo/.git";
        else
            $gitdir = $repo;
        return $gitdir;
    }

    function get_file_owner($path)  {
        $s = stat($path);
        $pw = posix_getpwuid($s["uid"]);
        return preg_replace("/[,;]/", "", $pw["gecos"]);
//        $out = array();
//        $own = exec("GIT_DIR=$path git-rev-list  --header --max-count=1 HEAD | grep -a committer | cut -d' ' -f2-3" ,&$out);
//        return $own;
    }

    function get_last($repo)    {
        $out = array();
        $date = exec("GIT_DIR=$repo git-rev-list  --header --max-count=1 HEAD | grep -a committer | cut -f5-6 -d' '", &$out);
        return date("D m/d/y G:i", $date);
    }

    function get_project_link($repo, $type = false)    {
        $path = basename($repo);
        if (!$type)
            return "<a href=\"{$_SERVER['SCRIPT_NAME']}?p=$path\">$path</a>";
        else if ($type == "targz")
            return "<a href=\"{$_SERVER['SCRIPT_NAME']}?p=$path&dl=targz\">.tar.gz</a>";
        else if ($type == "zip")
            return "<a href=\"{$_SERVER['SCRIPT_NAME']}?p=$path&dl=zip\">.zip</a>";
    }

    function git_commit($repo, $cid)  {
        $out = array();
        $commit = array();

        if (strlen($cid) <= 0)
            return 0;

        exec("GIT_DIR=$repo git-rev-list  --header --max-count=1 $cid", &$out);

        $commit["commit_id"] = $out[0];
        $g = explode(" ", $out[1]);
        $commit["tree"] = $g[1];

        $g = explode(" ", $out[2]);
        $commit["parent"] = $g[1];

        $g = explode(" ", $out[3]);
        $commit["author"] = "{$g[1]} {$g[2]}";
//TODO: Dudes with 3+ names
        
        $commit["date"] = "{$g[4]} {$g[5]}";
        $commit["message"] = "";
        $size = count($out);
        for ($i = 6; $i < $size; $i++)
            $commit["message"] .= $out[$i];
        return $commit;
    }

    function get_repo_path($proj)   {
        global $repos;
    
        foreach ($repos as $repo)   {
            $path = basename($repo);
            if ($path == $proj)
                return $repo;
        }
    }

    function git_ls_tree($repo, $tree) {
        $ary = array();
            
        $out = array();
        //Have to strip the \t between hash and file
        exec("GIT_DIR=$repo git-ls-tree $tree | sed -e 's/\t/ /g'", &$out);

        foreach ($out as $line) {
            $entry = array();
            $arr = explode(" ", $line);
            $entry['perm'] = $arr[0];
            $entry['type'] = $arr[1];
            $entry['hash'] = $arr[2];
            $entry['file'] = $arr[3];
            $ary[] = $entry;
        }
        return $ary;
    }

    function write_targz($repo) {
        $p = basename($repo);
        $proj = explode(".", $p);
        $proj = $proj[0]; 
        exec("cd /tmp && git-clone $repo && rm -Rf /tmp/$proj/.git && tar czvf $proj.tar.gz $proj && rm -Rf /tmp/$proj");
        
        $filesize = filesize("/tmp/$proj.tar.gz");
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers
        header("Content-Transfer-Encoding: binary");
        header("Content-Type: application/x-tar-gz");
        header("Content-Length: " . $filesize);
        header("Content-Disposition: attachment; filename=\"$proj.tar.gz\";" );
        echo file_get_contents("/tmp/$proj.tar.gz");
        die();
    }

    function write_zip($repo) {
        $p = basename($repo);
        $proj = explode(".", $p);
        $proj = $proj[0]; 
        exec("cd /tmp && git-clone $repo && rm -Rf /tmp/$proj/.git && zip -r $proj.zip $proj && rm -Rf /tmp/$proj");
        
        $filesize = filesize("/tmp/$proj.zip");
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers
        header("Content-Transfer-Encoding: binary");
        header("Content-Type: application/x-zip");
        header("Content-Length: " . $filesize);
        header("Content-Disposition: attachment; filename=\"$proj.zip\";" );
        echo file_get_contents("/tmp/$proj.zip");
        die();
    }

    function perm_string($perms)    {

        //This sucks
        switch ($perms) {
            case '040000':
                return 'drwxr-xr-x';
            case '100644':
                return '-rw-r--r--';
            case '100755':
                return '-rwxr-xr-x';
            case '120000':
                return 'lrwxrwxrwx';

            default:
                return '----------';
        }
    }

    function short_desc($desc)  {
        $trunc = false;
        $short = "";
        $d = explode(" ", $desc);
        foreach ($d as $str)    {
            if (strlen($short) < 25)
                $short .= "$str ";
            else    {
                $trunc = true;
                break;
            }
        }

        if ($trunc)
            $short .= "...";

        return $short;
    }

    function html_breadcrumbs()  {
        $crumb = "<a href=\"{$_SERVER['SCRIPT_NAME']}\">projects</a> / ";

        if (isset($_GET['p']))
            $crumb .= "<a href=\"{$_SERVER['SCRIPT_NAME']}?p={$_GET['p']}\">{$_GET['p']}</a> / ";
        
        if (isset($_GET['b']))
            $crumb .= "blob";

        if (isset($_GET['t']))
            $crumb .= "tree";

        if ($_GET['a'] == 'commitdiff')
            $crumb .= 'commitdiff';

        echo $crumb;
    }

    function zpr ($arr) {
        print "<pre>" .print_r($arr, true). "</pre>";
    }

    function highlight_code($code) {

        define(COLOR_DEFAULT, '000');
        define(COLOR_FUNCTION, '00b'); //also for variables, numbers and constants
        define(COLOR_KEYWORD, '070');
        define(COLOR_COMMENT, '800080');
        define(COLOR_STRING, 'd00');

        // Check it if code starts with PHP tags, if not: add 'em.
        if(substr($code, 0, 2) != '<?') {
            $code = "<?\n".$code."\n?>";
            $add_tags = true;
        }
        
        $code = highlight_string($code, true);

        // Remove the first "<code>" tag from "$code" (if any)
        if(substr($code, 0, 6) == '<code>') {
           $code = substr($code, 6, (strlen($code) - 13));
        }

        // Replacement-map to replace deprecated "<font>" tag with "<span>"
        $xhtml_convmap = array(
           '<font' => '<span',
           '</font>' => '</span>',
           'color="' => 'style="color:',
           '<br />' => '<br/>',
           '#000000">' => '#'.COLOR_DEFAULT.'">',
           '#0000BB">' => '#'.COLOR_FUNCTION.'">',
           '#007700">' => '#'.COLOR_KEYWORD.'">',
           '#FF8000">' => '#'.COLOR_COMMENT.'">',
           '#DD0000">' => '#'.COLOR_STRING.'">'
        );

        // Replace "<font>" tags with "<span>" tags, to generate a valid XHTML code
        $code = strtr($code, $xhtml_convmap);

        //strip default color (black) tags
        $code = substr($code, 25, (strlen($code) -33));

        //strip the PHP tags if they were added by the script
        if($add_tags) {
            
            $code = substr($code, 0, 26).substr($code, 36, (strlen($code) - 74));
        }

        return $code;
    }

?>

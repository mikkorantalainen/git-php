<?php

    global $title;
    global $repos;
    $title  = "git";
    $repo_index = "index.aux";

    //$repos = file($repo_index);
    $repos = array(
        "/home/zack/scm/bartel.git",
        "/home/zack/scm/rpminfo.git",
        "/home/zack/scm/linux.git",
        "/home/zack/scm/cnas.git",
    );

    html_header();

    if (isset($_GET['p']))  { 
    }
    else
        html_home();

    html_footer();

    function html_home()    {

        global $repos; 
        echo "<table>\n";
        echo "<tr><th>Project</th><th>Description</th><th>Owner</th><th>Last Changed</th></tr>\n";
        foreach ($repos as $repo)   {
            $desc = file_get_contents("$repo/description"); 
            $owner = get_file_owner($repo);
            $last =  get_last($repo);
            $proj = get_project_link($repo);
            echo "<tr><td>$proj</td><td>$desc</td><td>$owner</td><td>$last</td></tr>\n";
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
        return date("D m/d/Y G:i", $date);
    }

    function get_project_link($repo)    {
        $path = pathinfo($repo);
        return "<a href=\"{$_SERVER['SCRIPT_NAME']}?p={$path['basename']}\">$repo</a>";
    }

    function zpr ($arr) {
        print "<pre>" .print_r($arr, true). "</pre>";
    }
?>

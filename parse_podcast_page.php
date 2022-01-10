<?php

include("getID3-master/getid3/getid3.php");

// We consider MP3s that are shorter than 900 seconds are excerpts.
$excerpt_threshold = 900;

function mp3_is_excerpt($mp3_path)
{
  global $excerpt_threshold;

  $get_id3 = new getID3;
  $file_info = $get_id3->analyze($mp3_path);
  $length = $file_info['playtime_seconds'];

  return $length < $excerpt_threshold;
}

function parse_podcast_page($page_url)
{
  global $dry_run, $day, $month, $year;
  global $full_pages_dir, $full_mp3s_dir;
  global $no_mp3_url_pages_dir, $no_mp3_pages_dir;
  global $excerpts_pages_dir, $excerpts_mp3s_dir;

  $tmp_prefix = "$year$month${day}_";
  $page_url_base = basename($page_url);

  $file_contents = @file_get_contents($page_url);
  if ($file_contents == false)
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't download the page " .
                   "['$page_url']\033[0m\n");
    return;
  }
  else if ($dry_run)
  {
    echo "Would download podcast page: $page_url\n";
  }
  else
  {
    $downloaded_page_tmp_path = "$full_pages_dir/$tmp_prefix$page_url_base";
    file_put_contents($downloaded_page_tmp_path, $file_contents);

    echo "Downloaded podcast page: $page_url\n" .
         "                     to: $downloaded_page_tmp_path\n";
  }

  $dom = new DOMDocument("1.0");
  @$dom->loadHTMLFile($page_url);
  if ($dom == false)
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't load the HTML file " .
                   "['$page_url']\033[0m\n");
    return;
  }

  $scripts = $dom->getElementsByTagName('script');
  foreach ($scripts as $script)
  {
    if ($script->getAttribute("type") != "application/ld+json")
    {
      continue;
    }

    $script_json = json_decode($script->textContent, true);
    if ($script_json["@type"] != "AudioObject")
    {
      continue;
    }

    $mp3_url = $script_json["contentUrl"];
    $prefix = $tmp_prefix . substr(md5($mp3_url), 0, 8) . "_";
    break;
  }

  if (!isset($mp3_url))
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't find the URL of the " .
                   "mp3\033[0m\n");

    $no_mp3_url_page_path = "$no_mp3_url_pages_dir/$page_url_base";
    rename($downloaded_page_tmp_path, $no_mp3_url_page_path);
    echo "Moved podcast page: $downloaded_page_tmp_path\n" .
         "                to: $no_mp3_url_page_path\n";

    return;
  }

  if ($dry_run)
  {
    echo "Would download mp3: $mp3_url\n";
  }
  else
  {
    $file_contents = @file_get_contents($mp3_url);
    if ($file_contents == false)
    {
      fwrite(STDERR, "\033[01;33mWARNING: couldn't download the mp3 " .
                     "['$mp3_url']\033[0m\n");

      $no_mp3_page_path = "$no_mp3_pages_dir/$page_url_base";
      rename($downloaded_page_tmp_path, $no_mp3_page_path);
      echo "Moved podcast page: $downloaded_page_tmp_path\n" .
           "                to: $no_mp3_page_path\n";

      return;
    }
    else
    {
      $mp3_url_base = basename($mp3_url);
      $downloaded_mp3_path = "$full_mp3s_dir/$prefix$mp3_url_base";
      file_put_contents($downloaded_mp3_path, $file_contents);
      echo "Downloaded mp3: $mp3_url\n" .
           "            to: $downloaded_mp3_path\n";
    }
  } 

  if (!$dry_run)
  {
    $downloaded_page_path = "$full_pages_dir/$prefix$page_url_base";
    rename($downloaded_page_tmp_path, $downloaded_page_path);

    echo "Moved podcast page: $downloaded_page_tmp_path\n" .
         "                to: $downloaded_page_path\n";
  }

  if (mp3_is_excerpt($downloaded_mp3_path))
  {
    echo "Excerpt detected: $downloaded_mp3_path\n";

    $excerpt_page_path = "$excerpts_pages_dir/$prefix$page_url_base";
    $excerpt_mp3_path = "$excerpts_mp3s_dir/$prefix$mp3_url_base";

    rename($downloaded_page_path, $excerpt_page_path);
    rename($downloaded_mp3_path, $excerpt_mp3_path);

    echo "Moved podcast page: $downloaded_page_path\n" .
         "                to: $excerpt_page_path\n";
    echo "Moved mp3 page: $downloaded_mp3_path\n" .
         "            to: $excerpt_mp3_path\n";
  } 
}

?>

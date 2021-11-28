<?php

function parse_podcast_page($page_url)
{
  global $dry_run, $output_dir, $day, $month, $year;

  $prefix = "$year$month${day}_";

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
    file_put_contents("$output/www_europe1_fr/pages/$prefix"
                      . basename($page_url), $file_contents);

    echo "Downloaded podcast page: $page_url\n";
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
  }

  if (!isset($mp3_url))
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't find the URL of the " .
                   "mp3\033[0m\n");
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
      touch("$output/www_europe1_fr/mp3s/$prefix unavailable");
      return;
    }
    else
    {
      file_put_contents("$output_dir/www_europe1_fr/mp3s/$prefix"
                        . basename($mp3_url), $file_contents);
      echo "Downloaded mp3: $page_url\n";
    }
  }
}

?>

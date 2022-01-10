#!/usr/bin/php
<?php

include("global.php");

$root_output_dir = "output_europe1";
$full_pages_dir = "$root_output_dir/full/pages";
$full_mp3s_dir = "$root_output_dir/full/mp3s";
$no_mp3_url_pages_dir = "$root_output_dir/full/no_mp3_url_pages";
$no_mp3_pages_dir = "$root_output_dir/full/no_mp3_pages";
$excerpts_pages_dir = "$root_output_dir/excerpts/pages";
$excerpts_mp3s_dir = "$root_output_dir/excerpts/mp3s";

$root_url = "https://www.europe1.fr/emissions/On-va-s-gener";

$day = "XX";
$month = "XX";
$year = "XX";

/******************************************************************************/

include("parse_podcast_page.php");
include("create_output_dirs.php");

for ($i = 1; $i < 63; $i++)
{
  global $day, $month, $year;

  $url = "$root_url/$i";

  $dom = new DOMDocument("1.0");
  @$dom->loadHTMLFile($url);

  if ($dom == false)
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't load the HTML file "
                   . "['$url']\033[0m\n");
    return;
  }

  $uls = $dom->getElementsByTagName("ul");
  foreach ($uls as $ul)
  {
    $class = $ul->getAttribute("class");
    if ($class != "listing_emissions")
    {
      continue;
    }

    $lis = $ul->getElementsByTagName("li");
    foreach ($lis as $li)
    {
      foreach($li->childNodes as $child_node)
      {
        if (!$child_node instanceof DOMElement 
            || $child_node->textContent == "Date"
            || $child_node->textContent == "Nom")
        {
          continue;
        }

        if ($child_node->getAttribute("class") == "date")
        {
          $split_date = explode("/", $child_node->textContent);
          $day = $split_date[0];
          $month = $split_date[1];
          $year = $split_date[2];
        }
        else if ($child_node->getAttribute("class") == "titre")
        {
          $href = $child_node->getAttribute("href"); 
          parse_podcast_page("$href");
        }

        usleep($delay_us);
      }
    }
  }
}


#!/usr/bin/php
<?php

include("global.php");

$root_output_dir = "output_podcast-onvasgener";

$root_url = "http://www.podcast-onvasgener.fr/";
$ignored_day_hrefs = array(
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-04-10-12-1263063",
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-06-10-09-39676/",
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-13-10-09-39686/"
);
$fixed_day_hrefs = array(
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-17-04-14-2096143"
=> "https://www.europe1.fr/emissions/On-va-s-gener/On-va-s-gener-17-04-2014-153956",
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-BEST-OF-2109293"
=> "https://www.europe1.fr/emissions/On-va-s-gener/On-va-s-gener-Best-Of-01-05-14-153482",
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-08-05-13-1510341"
=> "https://www.europe1.fr/emissions/On-va-s-gener/On-va-s-gener-Best-of-08-05-13-120950",
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-30-09-13-1658445"
=> "https://www.europe1.fr/emissions/On-va-s-gener/On-va-s-gener-30-09-13-134518",
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-01-10-13-1659817"
=> "https://www.europe1.fr/emissions/On-va-s-gener/On-va-s-gener-01-10-13-139008",
"http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-02-10-13-1661163"
=> "https://www.europe1.fr/emissions/On-va-s-gener/On-va-s-gener-02-10-13-134528",
"" // Very hacky, but works since only one link is empty...
=> "https://www.europe1.fr/emissions/On-va-s-gener/On-va-s-gener-30-11-12-105142",
"hhttp://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-29-03-10-165957/"
=> "http://www.europe1.fr/MediaCenter/Emissions/On-va-s-gener/Sons/On-va-s-gener-29-03-10-165957/"
);

$sources = array("www_europe1_fr");

$month_str_to_num = array("Janvier" => "01", 
                          "Fevrier" => "02",
                          "Mars" => "03",
                          "Avril" => "04",
                          "Mai" => "05",
                          "Juin" => "06",
                          "Juillet" => "07",
                          "Aout" => "08",
                          "Septembre" => "09",
                          "Octobre" => "10",
                          "Novembre" => "11",
                          "Decembre" => "12");

$day = "XX";
$month = "XX";
$year = "XX";

/******************************************************************************/

include("parse_podcast_page.php");
include("create_output_dirs.php");

function parse($base_url, $href, $regexp, $function)
{
  global $delay_us;

  $dom = new DOMDocument("1.0");
  @$dom->loadHTMLFile("$base_url$href");
  if ($dom == false)
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't load the HTML file "
                   . "['$href']\033[0m\n");
    return;
  }

  $as = $dom->getElementsByTagName("a");
  foreach ($as as $a)
  {
    $href = $a->getAttribute("href");
    $text = $a->textContent;
    if (preg_match($regexp, $text, $matches))
    {
      if (($num_matches = count($matches)) > 2)
      {
        fwrite(STDERR, "\033[01;33mWARNING: multiple REGEXP matches "
                       . "['$regexp', '$text']\033[0m\n");
      }

      $function($base_url, $href, $matches[1]);
      usleep($delay_us);
    }
  }
}

function parse_day($base_url, $href, $match)
{
  global $ignored_day_hrefs, $fixed_day_hrefs, $sources, $day;

  $day = $match;

  if (in_array($href, $ignored_day_hrefs))
  {
    fwrite(STDERR, "\033[01;32mNOTICE: ignoring URL ['$href']\033[0m\n");
    return;
  }
  else if (array_key_exists($href, $fixed_day_hrefs))
  {
    $new_href = $fixed_day_hrefs[$href];
    fwrite(STDERR, "\033[01;32mNOTICE: replacing page URL ['$href', "
                   . "'$new_href']\033[0m\n");

    $href = $new_href;
  }

  if (preg_match("/www.europe1.fr/i", $href))
  {
    if (!in_array("www_europe1_fr", $sources))
    {
      return;
    }

    parse_podcast_page($href);
  }
  else
  {
    fwrite(STDERR, "\033[01;33mWARNING: source not supported "
                   . "['$href']\033[0m\n");
  }
}

function parse_month($base_url, $href, $match)
{
  global $month_str_to_num, $month;

  $month = $month_str_to_num[$match];

  parse($base_url, $href, "/([0-3][0-9]) [0-1][0-9] 20[0-9][0-9]/i",
        "parse_day");
}

function parse_year($base_url, $href, $match)
{
  global $year;

  $year = $match;

  parse($base_url, $href,
        "/(Janvier|Fevrier|Mars|Avril|Mai|Juin|Juillet|Aout|Septembre|Octobre|"
        . "Novembre|Decembre)/i",
        "parse_month");
}

function parse_root($base_url, $href) 
{
  parse($base_url, $href, "/20([0-9][0-9])/i", "parse_year");
}

/******************************************************************************/

parse_root($root_url, "");

?>

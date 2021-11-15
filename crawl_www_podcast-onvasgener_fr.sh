#!/usr/bin/php
<?php

/*
 * This script uses http://www.podcast-onvasgener.fr/ to get a list of all
 * pages. I found out afterwards that I could just have used
 * https://www.europe1.fr/emissions/On-va-s-gener to
 * https://www.europe1.fr/emissions/On-va-s-gener/62.
 *
 * It would probably have been more reliable.
 */

$delay_us = 10000;

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

function parse_www_europe1_fr($page_url)
{
  global $day, $month, $year;

  $prefix = $year . $month . $day . "_";

  echo "Page: " . $page_url . "\n";

  $file_contents = @file_get_contents($page_url);
  if ($file_contents == false)
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't download the page " .
                   "['$page_url']\033[0m\n");
    return;
  }
  else
  {
    file_put_contents("output/www_europe1_fr/pages/" . $prefix
                      . basename($page_url), $file_contents);
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

  echo "mp3: " . $mp3_url . "\n";

  $file_contents = @file_get_contents($mp3_url);
  if ($file_contents == false)
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't download the mp3 " .
                   "['$mp3_url']\033[0m\n");
    touch("output/www_europe1_fr/mp3s/" . $prefix . "unavailable");
    return;
  }
  else
  {
    file_put_contents("output/www_europe1_fr/mp3s/" . $prefix
                      . basename($mp3_url), $file_contents);
  }
}

function parse($base_url, $href, $regexp, $function)
{
  $dom = new DOMDocument("1.0");
  @$dom->loadHTMLFile($base_url . $href);
  if ($dom == false)
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't load the HTML file "
                   . "['$href']\033[0m\n");
    return;
  }

  $anchors = $dom->getElementsByTagName("a");
  foreach ($anchors as $element)
  {
    $href = $element->getAttribute('href');
    $text = $element->textContent;
    if (preg_match($regexp, $text, $matches))
    {
      if (($num_matches = count($matches)) > 2)
      {
        fwrite(STDERR, "\033[01;33mWARNING: multiple REGEXP matches " .
               "['$regexp', '$text']\033[0m\n");
      }

      usleep($delay_us);
      $function($base_url, $href, $matches[1]);
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
    fwrite(STDERR, "\033[01;32mNOTICE: replacing page URL ['$href', " .
                   "'$new_href']\033[0m\n");

    $href = $new_href;
  }

  if (preg_match("/www.europe1.fr/i", $href))
  {
    if (!in_array("www_europe1_fr", $sources))
    {
      return;
    }

    parse_www_europe1_fr($href);
  }
  else
  {
    fwrite(STDERR, "\033[01;33mWARNING: source not supported " .
                   "['$href']\033[0m\n");
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

if (!file_exists("output/www_europe1_fr/pages"))
{
  mkdir("output/www_europe1_fr/pages/", 0755, true);
}

if (!file_exists("output/www_europe1_fr/mp3s"))
{
  mkdir("output/www_europe1_fr/mp3s/", 0755, true);
}

parse_root($root_url, "");

?>

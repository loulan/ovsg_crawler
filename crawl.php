#!/usr/bin/php
<?php

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

function parse_www_europe1_fr($page_url)
{
  global $day, $month, $year;

  $dom = new DOMDocument("1.0");
  @$dom->loadHTMLFile($page_url);

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

  echo "Page: " . $page_url . "\n";

  if (!isset($mp3_url))
  {
    fwrite(STDERR, "\033[01;33mWARNING: couldn't find the URL of the " .
                   "mp3!\033[0m\n");
    return;
  }

  echo "mp3: " . $mp3_url . "\n";

  if (!file_exists("output/www_europe1_fr/pages"))
  {
    mkdir("output/www_europe1_fr/pages/", 0755, true);
  }

  if (!file_exists("output/www_europe1_fr/mp3s"))
  {
    mkdir("output/www_europe1_fr/mp3s/", 0755, true);
  }

  $prefix = $year . $month . $day . "_";

  file_put_contents("output/www_europe1_fr/pages/" . $prefix
                    . basename($page_url), file_get_contents($page_url));
  file_put_contents("output/www_europe1_fr/mp3s/" . $prefix
                    . basename($mp3_url), file_get_contents($mp3_url));
}

function parse($base_url, $href, $regexp, $function)
{
  $dom = new DOMDocument("1.0");
  @$dom->loadHTMLFile($base_url . $href);

  $anchors = $dom->getElementsByTagName("a");
  foreach ($anchors as $element)
  {
    $href = $element->getAttribute('href');
    $text = $element->textContent;
    if (preg_match($regexp, $text, $matches))
    {
      if (($num_matches = count($matches)) > 2)
      {
       fwrite(STDERR, "\033[01;33mWARNING: multiple matches for '$regexp' in " . 
                      "'$text'!\033[0m\n");
      }

      $function($base_url, $href, $matches[1]);
    }
  }

  usleep(1000);
}

function parse_day($base_url, $href, $match)
{
  global $sources, $day;
  $day = $match;

  if(preg_match("/www.europe1.fr/i", $href))
  {
    if (!in_array("www_europe1_fr", $sources))
    {
      return;
    }

    parse_www_europe1_fr($href);
  }
  else
  {
    fwrite(STDERR, "\033[01;33mWARNING: source not supported [$href]\033[0m\n");
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

parse_root("http://www.podcast-onvasgener.fr/", "");

?>

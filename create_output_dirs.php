<?php

if (!$dry_run)
{
  if (!file_exists("$root_output_dir/www_europe1_fr/pages"))
  {
    mkdir("$root_output_dir/www_europe1_fr/pages/", 0755, true);
  }

  if (!file_exists("$root_output_dir/europe1/mp3s"))
  {
    mkdir("$root_output_dir/europe1/mp3s/", 0755, true);
  }
}

?>

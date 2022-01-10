<?php

if (!$dry_run)
{
  if (!file_exists($full_pages_dir))
  {
    mkdir($full_pages_dir, 0755, true);
  }

  if (!file_exists($full_mp3s_dir))
  {
    mkdir($full_mp3s_dir, 0755, true);
  }

  if (!file_exists($no_mp3_url_pages_dir))
  {
    mkdir($no_mp3_url_pages_dir, 0755, true);
  }

  if (!file_exists($no_mp3_pages_dir))
  {
    mkdir($no_mp3_pages_dir, 0755, true);
  }

  if (!file_exists($excerpts_pages_dir))
  {
    mkdir($excerpts_pages_dir, 0755, true);
  }

  if (!file_exists($excerpts_mp3s_dir))
  {
    mkdir($excerpts_mp3s_dir, 0755, true);
  }
}

?>
